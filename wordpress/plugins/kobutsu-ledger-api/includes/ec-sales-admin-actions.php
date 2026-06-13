<?php

if (!defined('ABSPATH')) {
    exit;
}

function kobutsu_ledger_update_ec_sale(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    $sale_id = absint($request['id']);
    $sale = kobutsu_ledger_admin_get_ec_sale($sale_id);

    if (!$sale) {
        return new WP_Error('kobutsu_ec_sale_not_found', 'EC販売データが見つかりません。', ['status' => 404]);
    }

    kobutsu_ledger_update_ec_sale_from_payload($sale_id, $request->get_json_params() ?: [], $sale);

    $updated_sale = kobutsu_ledger_admin_get_ec_sale($sale_id);
    if (!$updated_sale) {
        return new WP_Error('kobutsu_ec_sale_not_found', 'EC販売データが見つかりません。', ['status' => 404]);
    }

    return rest_ensure_response(kobutsu_ledger_format_ec_sale_row($updated_sale));
}

function kobutsu_ledger_handle_ec_sales_admin_action(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['kobutsu_ec_sale_action'])) {
        return;
    }

    $action = sanitize_key((string) wp_unslash($_POST['kobutsu_ec_sale_action']));
    $sale_id = isset($_POST['sale_id']) ? absint($_POST['sale_id']) : 0;

    if (!$sale_id) {
        kobutsu_ledger_ec_sales_admin_redirect(['kobutsu_message' => 'missing']);
    }

    if ($action === 'delete') {
        check_admin_referer('kobutsu_ec_sale_delete_' . $sale_id);
        kobutsu_ledger_admin_delete_ec_sale($sale_id);
        kobutsu_ledger_ec_sales_admin_redirect(['kobutsu_message' => 'deleted']);
    }

    if ($action === 'quick_update') {
        check_admin_referer('kobutsu_ec_sale_quick_update_' . $sale_id);
        kobutsu_ledger_admin_quick_update_ec_sale($sale_id);
        kobutsu_ledger_ec_sales_admin_redirect(kobutsu_ledger_ec_sales_current_filter_args([
            'kobutsu_message' => 'saved',
        ]));
    }

    if ($action === 'save') {
        check_admin_referer('kobutsu_ec_sale_save_' . $sale_id);
        kobutsu_ledger_admin_update_ec_sale($sale_id);
        kobutsu_ledger_ec_sales_admin_redirect(['kobutsu_message' => 'saved', 'edit' => $sale_id]);
    }
}

function kobutsu_ledger_ec_sales_admin_redirect(array $args): void
{
    wp_safe_redirect(add_query_arg(array_merge(['page' => 'kobutsu-ec-sales'], $args), admin_url('admin.php')));
    exit;
}

function kobutsu_ledger_ec_sales_current_filter_args(array $args = []): array
{
    $view = isset($_POST['current_view']) ? sanitize_key((string) wp_unslash($_POST['current_view'])) : 'all';
    $search = isset($_POST['current_search']) ? sanitize_text_field((string) wp_unslash($_POST['current_search'])) : '';

    if ($view !== '' && $view !== 'all') {
        $args['view'] = $view;
    }

    if ($search !== '') {
        $args['s'] = $search;
    }

    return $args;
}

function kobutsu_ledger_admin_update_ec_sale(int $sale_id): void
{
    global $wpdb;

    $sale = kobutsu_ledger_admin_get_ec_sale($sale_id);
    if (!$sale) {
        return;
    }

    $now = current_time('mysql');
    $order_no = sanitize_text_field((string) wp_unslash($_POST['order_no'] ?? ''));
    $sale_currency = strtoupper(substr(sanitize_text_field((string) wp_unslash($_POST['sale_currency'] ?? 'USD')), 0, 3));
    $sale_amount = (float) ($_POST['sale_amount'] ?? 0);
    $sale_date = kobutsu_ledger_admin_date_or_null($_POST['sale_date'] ?? '');
    $sale_exchange_rate = (float) ($_POST['sale_exchange_rate'] ?? 0);
    $sale_exchange_rate = kobutsu_ledger_resolve_sale_exchange_rate(
        $sale_exchange_rate,
        $sale_date,
        $sale_currency
    );

    $wpdb->query('START TRANSACTION');

    $wpdb->update(kobutsu_ledger_table('sales'), [
        'marketplace' => sanitize_text_field((string) wp_unslash($_POST['marketplace'] ?? '')),
        'account_name' => sanitize_text_field((string) wp_unslash($_POST['account_name'] ?? '')),
        'order_no' => $order_no,
        'sale_date' => $sale_date,
        'sale_amount' => $sale_amount,
        'sale_currency' => $sale_currency ?: 'USD',
        'sale_amount_jpy' => $sale_currency === 'JPY' ? (int) round($sale_amount) : 0,
        'buyer_country' => sanitize_text_field((string) wp_unslash($_POST['buyer_country'] ?? '')),
        'tracking_no' => sanitize_text_field((string) wp_unslash($_POST['tracking_no'] ?? '')),
        'shipping_site' => sanitize_text_field((string) wp_unslash($_POST['shipping_site'] ?? '')),
        'notes' => sanitize_textarea_field((string) wp_unslash($_POST['sale_notes'] ?? '')),
        'updated_at' => $now,
    ], ['id' => $sale_id], ['%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%s', '%s', '%s'], ['%d']);

    $settlement_data = [
        'sale_id' => $sale_id,
        'order_no' => $order_no,
        'payout_date' => kobutsu_ledger_admin_date_or_null($_POST['payout_date'] ?? ''),
        'payout_id' => sanitize_text_field((string) wp_unslash($_POST['payout_id'] ?? '')),
        'total_fees' => (float) ($_POST['ad_fee'] ?? 0) + (float) ($_POST['ebay_fee'] ?? 0),
        'ad_fee' => (float) ($_POST['ad_fee'] ?? 0),
        'ebay_fee' => (float) ($_POST['ebay_fee'] ?? 0),
        'payout_amount' => (float) ($_POST['payout_amount'] ?? 0),
        'sale_exchange_rate' => $sale_exchange_rate,
        'payout_exchange_rate' => (float) ($_POST['payout_exchange_rate'] ?? 0),
        'received_amount_jpy' => (int) ($_POST['received_amount_jpy'] ?? 0),
        'overseas_shipping_jpy' => (int) ($_POST['overseas_shipping_jpy'] ?? 0),
        'fee_tax_refund_jpy' => (int) ($_POST['fee_tax_refund_jpy'] ?? 0),
        'consumption_tax_refund_jpy' => (int) ($_POST['consumption_tax_refund_jpy'] ?? 0),
        'profit_jpy' => (int) ($_POST['profit_jpy'] ?? 0),
        'profit_rate' => (float) ($_POST['profit_rate'] ?? 0),
        'days_to_sell' => (int) ($_POST['days_to_sell'] ?? 0),
        'updated_at' => $now,
    ];

    if (!empty($sale['settlement_id'])) {
        $wpdb->update(
            kobutsu_ledger_table('sales_settlements'),
            $settlement_data,
            ['id' => (int) $sale['settlement_id']],
            ['%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%s'],
            ['%d']
        );
    } else {
        $wpdb->insert(
            kobutsu_ledger_table('sales_settlements'),
            $settlement_data,
            ['%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%s']
        );
    }

    $wpdb->query('COMMIT');
}

function kobutsu_ledger_admin_quick_update_ec_sale(int $sale_id): void
{
    global $wpdb;

    $sale = kobutsu_ledger_admin_get_ec_sale($sale_id);
    if (!$sale) {
        return;
    }

    $now = current_time('mysql');
    $order_no = sanitize_text_field((string) wp_unslash($_POST['order_no'] ?? $sale['order_no']));
    $sale_currency = strtoupper(substr(sanitize_text_field((string) wp_unslash($_POST['sale_currency'] ?? $sale['sale_currency'])), 0, 3));
    $sale_amount = (float) ($_POST['sale_amount'] ?? $sale['sale_amount']);
    $sale_date = kobutsu_ledger_admin_date_or_null($_POST['sale_date'] ?? $sale['sale_date']);
    $sale_exchange_rate = kobutsu_ledger_resolve_sale_exchange_rate(
        (float) $sale['sale_exchange_rate'],
        $sale_date,
        $sale_currency
    );

    $wpdb->query('START TRANSACTION');

    $wpdb->update(kobutsu_ledger_table('sales'), [
        'order_no' => $order_no,
        'sale_date' => $sale_date,
        'sale_amount' => $sale_amount,
        'sale_currency' => $sale_currency ?: 'USD',
        'sale_amount_jpy' => $sale_currency === 'JPY' ? (int) round($sale_amount) : 0,
        'updated_at' => $now,
    ], ['id' => $sale_id], ['%s', '%s', '%f', '%s', '%d', '%s'], ['%d']);

    $settlement_data = [
        'sale_id' => $sale_id,
        'order_no' => $order_no,
        'payout_date' => kobutsu_ledger_admin_date_or_null($_POST['payout_date'] ?? $sale['payout_date']),
        'payout_id' => (string) $sale['payout_id'],
        'total_fees' => (float) $sale['total_fees'],
        'ad_fee' => (float) $sale['ad_fee'],
        'ebay_fee' => (float) $sale['ebay_fee'],
        'payout_amount' => (float) $sale['payout_amount'],
        'sale_exchange_rate' => $sale_exchange_rate,
        'payout_exchange_rate' => (float) $sale['payout_exchange_rate'],
        'received_amount_jpy' => (int) ($_POST['received_amount_jpy'] ?? $sale['received_amount_jpy']),
        'overseas_shipping_jpy' => (int) $sale['overseas_shipping_jpy'],
        'fee_tax_refund_jpy' => (int) $sale['fee_tax_refund_jpy'],
        'consumption_tax_refund_jpy' => (int) $sale['consumption_tax_refund_jpy'],
        'profit_jpy' => (int) ($_POST['profit_jpy'] ?? $sale['profit_jpy']),
        'profit_rate' => (float) ($_POST['profit_rate'] ?? $sale['profit_rate']),
        'days_to_sell' => (int) ($_POST['days_to_sell'] ?? $sale['days_to_sell']),
        'updated_at' => $now,
    ];

    if (!empty($sale['settlement_id'])) {
        $wpdb->update(
            kobutsu_ledger_table('sales_settlements'),
            $settlement_data,
            ['id' => (int) $sale['settlement_id']],
            ['%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%s'],
            ['%d']
        );
    } else {
        $wpdb->insert(
            kobutsu_ledger_table('sales_settlements'),
            $settlement_data,
            ['%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%s']
        );
    }

    $wpdb->query('COMMIT');
}

function kobutsu_ledger_update_ec_sale_from_payload(
    int $sale_id,
    array $payload,
    array $sale
): void {
    global $wpdb;

    $now = current_time('mysql');
    $order_no = kobutsu_ledger_payload_text($payload, 'order_no', (string) $sale['order_no']);
    $sale_currency = strtoupper(substr(kobutsu_ledger_payload_text($payload, 'sale_currency', (string) $sale['sale_currency']), 0, 3));
    $sale_amount = kobutsu_ledger_payload_float($payload, 'sale_amount', (float) $sale['sale_amount']);
    $sale_date = kobutsu_ledger_admin_date_or_null($payload['sale_date'] ?? $sale['sale_date']);
    $sale_exchange_rate = kobutsu_ledger_payload_float($payload, 'sale_exchange_rate', (float) $sale['sale_exchange_rate']);
    $sale_exchange_rate = kobutsu_ledger_resolve_sale_exchange_rate(
        $sale_exchange_rate,
        $sale_date,
        $sale_currency
    );

    $wpdb->query('START TRANSACTION');

    $wpdb->update(kobutsu_ledger_table('sales'), [
        'order_no' => $order_no,
        'sale_date' => $sale_date,
        'sale_amount' => $sale_amount,
        'sale_currency' => $sale_currency ?: 'USD',
        'sale_amount_jpy' => $sale_currency === 'JPY' ? (int) round($sale_amount) : 0,
        'tracking_no' => kobutsu_ledger_payload_text($payload, 'domestic_tracking_no', (string) $sale['tracking_no']),
        'shipping_site' => kobutsu_ledger_payload_text($payload, 'sls_tracking_no', (string) $sale['shipping_site']),
        'updated_at' => $now,
    ], ['id' => $sale_id], ['%s', '%s', '%f', '%s', '%d', '%s', '%s', '%s'], ['%d']);

    $settlement_data = [
        'sale_id' => $sale_id,
        'order_no' => $order_no,
        'payout_date' => kobutsu_ledger_admin_date_or_null($payload['payout_date'] ?? $sale['payout_date']),
        'payout_id' => (string) $sale['payout_id'],
        'total_fees' => kobutsu_ledger_payload_float($payload, 'ad_fee', (float) $sale['ad_fee'])
            + kobutsu_ledger_payload_float($payload, 'marketplace_fee', (float) $sale['ebay_fee']),
        'ad_fee' => kobutsu_ledger_payload_float($payload, 'ad_fee', (float) $sale['ad_fee']),
        'ebay_fee' => kobutsu_ledger_payload_float($payload, 'marketplace_fee', (float) $sale['ebay_fee']),
        'payout_amount' => kobutsu_ledger_payload_float($payload, 'payout_amount', (float) $sale['payout_amount']),
        'sale_exchange_rate' => $sale_exchange_rate,
        'payout_exchange_rate' => kobutsu_ledger_payload_float($payload, 'payout_exchange_rate', (float) $sale['payout_exchange_rate']),
        'received_amount_jpy' => kobutsu_ledger_payload_int($payload, 'received_amount_jpy', (int) $sale['received_amount_jpy']),
        'overseas_shipping_jpy' => kobutsu_ledger_payload_int($payload, 'overseas_shipping_jpy', (int) $sale['overseas_shipping_jpy']),
        'fee_tax_refund_jpy' => kobutsu_ledger_payload_int($payload, 'fee_tax_refund_jpy', (int) $sale['fee_tax_refund_jpy']),
        'consumption_tax_refund_jpy' => kobutsu_ledger_payload_int($payload, 'purchase_tax_refund_jpy', (int) $sale['consumption_tax_refund_jpy']),
        'profit_jpy' => kobutsu_ledger_payload_int($payload, 'profit_jpy', (int) $sale['profit_jpy']),
        'profit_rate' => kobutsu_ledger_payload_float($payload, 'profit_rate', (float) $sale['profit_rate']),
        'days_to_sell' => kobutsu_ledger_payload_int($payload, 'days_to_sell', (int) $sale['days_to_sell']),
        'updated_at' => $now,
    ];

    if (!empty($sale['settlement_id'])) {
        $wpdb->update(
            kobutsu_ledger_table('sales_settlements'),
            $settlement_data,
            ['id' => (int) $sale['settlement_id']],
            ['%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%s'],
            ['%d']
        );
    } else {
        $wpdb->insert(
            kobutsu_ledger_table('sales_settlements'),
            $settlement_data,
            ['%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%s']
        );
    }

    $wpdb->query('COMMIT');
}

function kobutsu_ledger_resolve_sale_exchange_rate(
    float $current_rate,
    ?string $sale_date,
    string $sale_currency
): float {
    if ($current_rate > 0) {
        return $current_rate;
    }

    if (!$sale_date || $sale_currency === '') {
        return 0.0;
    }

    return kobutsu_ledger_find_exchange_rate($sale_date, $sale_currency, 'JPY');
}

function kobutsu_ledger_admin_delete_ec_sale(int $sale_id): void
{
    global $wpdb;

    $wpdb->query('START TRANSACTION');
    $wpdb->delete(kobutsu_ledger_table('sales_settlements'), ['sale_id' => $sale_id], ['%d']);
    $wpdb->delete(kobutsu_ledger_table('sales'), ['id' => $sale_id], ['%d']);
    $wpdb->query('COMMIT');
}
