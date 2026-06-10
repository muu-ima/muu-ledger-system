<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/ec-sales-admin-view.php';

function kobutsu_ledger_get_ec_sales(WP_REST_Request $request): WP_REST_Response
{
    $rows = kobutsu_ledger_admin_get_ec_sales();

    return rest_ensure_response(array_map('kobutsu_ledger_format_ec_sale_row', $rows));
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

function kobutsu_ledger_admin_get_ec_sales(string $view = 'all', string $search = ''): array
{
    global $wpdb;

    $where = [];
    $args = [];

    if ($view === 'unsettled') {
        $where[] = '(ss.id IS NULL OR ss.received_amount_jpy = 0)';
    } elseif ($view === 'profit') {
        $where[] = 'ss.profit_jpy > 0';
    } elseif ($view === 'loss') {
        $where[] = 'ss.profit_jpy < 0';
    } elseif ($view === 'shipped') {
        $where[] = '(s.tracking_no <> "" OR s.shipping_site <> "")';
    }

    if ($search !== '') {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where[] = '(i.sku LIKE %s OR i.item_name LIKE %s OR s.order_no LIKE %s)';
        $args[] = $like;
        $args[] = $like;
        $args[] = $like;
    }

    $sql = kobutsu_ledger_ec_sales_select_sql();
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY COALESCE(s.sale_date, s.created_at) DESC, s.id DESC LIMIT 100';

    if ($args) {
        $sql = $wpdb->prepare($sql, ...$args);
    }

    return $wpdb->get_results($sql, ARRAY_A) ?: [];
}

function kobutsu_ledger_admin_get_ec_sale(int $sale_id): ?array
{
    global $wpdb;

    $row = $wpdb->get_row(
        $wpdb->prepare(kobutsu_ledger_ec_sales_select_sql() . ' WHERE s.id = %d', $sale_id),
        ARRAY_A
    );

    return $row ?: null;
}

function kobutsu_ledger_ec_sales_select_sql(): string
{
    return 'SELECT
        s.id AS sale_id, s.item_id, s.marketplace, s.account_name, s.order_no,
        COALESCE(s.sale_date, "") AS sale_date, s.sale_type, s.sale_amount,
        s.sale_currency, s.sale_amount_jpy, s.buyer_country, s.buyer_id,
        s.buyer_name, s.tracking_no, s.shipping_site, s.actual_weight_g,
        s.dimensional_weight_g, s.package_length_cm, s.package_width_cm,
        s.package_height_cm, COALESCE(s.notes, "") AS sale_notes,
        COALESCE(i.sku, "") AS sku, COALESCE(i.item_name, "") AS item_name,
        COALESCE(CAST(p.purchase_date AS CHAR), ssr.acquired_at_raw, "") AS purchase_date,
        COALESCE(p.purchase_price_jpy, 0) AS purchase_price_jpy,
        COALESCE(ss.id, 0) AS settlement_id,
        COALESCE(ss.payout_date, "") AS payout_date,
        COALESCE(ss.payout_id, "") AS payout_id,
        COALESCE(ss.total_fees, 0) AS total_fees,
        COALESCE(ss.ad_fee, 0) AS ad_fee,
        COALESCE(ss.ebay_fee, 0) AS ebay_fee,
        COALESCE(ss.payout_amount, 0) AS payout_amount,
        COALESCE(ss.sale_exchange_rate, 0) AS sale_exchange_rate,
        COALESCE(ss.payout_exchange_rate, 0) AS payout_exchange_rate,
        COALESCE(ss.received_amount_jpy, 0) AS received_amount_jpy,
        COALESCE(ss.overseas_shipping_jpy, 0) AS overseas_shipping_jpy,
        COALESCE(ss.fee_tax_refund_jpy, 0) AS fee_tax_refund_jpy,
        COALESCE(ss.consumption_tax_refund_jpy, 0) AS consumption_tax_refund_jpy,
        COALESCE(ss.profit_jpy, 0) AS profit_jpy,
        COALESCE(ss.profit_rate, 0) AS profit_rate,
        COALESCE(ss.days_to_sell, 0) AS days_to_sell
    FROM ' . kobutsu_ledger_table('sales') . ' s
    LEFT JOIN ' . kobutsu_ledger_table('items') . ' i ON i.id = s.item_id
    LEFT JOIN ' . kobutsu_ledger_table('purchases') . ' p ON p.item_id = s.item_id
    LEFT JOIN ' . kobutsu_ledger_table('supplier_sources') . ' ssr ON ssr.item_id = s.item_id
    LEFT JOIN ' . kobutsu_ledger_table('sales_settlements') . ' ss ON ss.sale_id = s.id';
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

    $wpdb->query('START TRANSACTION');

    $wpdb->update(kobutsu_ledger_table('sales'), [
        'marketplace' => sanitize_text_field((string) wp_unslash($_POST['marketplace'] ?? '')),
        'account_name' => sanitize_text_field((string) wp_unslash($_POST['account_name'] ?? '')),
        'order_no' => $order_no,
        'sale_date' => kobutsu_ledger_admin_date_or_null($_POST['sale_date'] ?? ''),
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
        'sale_exchange_rate' => (float) ($_POST['sale_exchange_rate'] ?? 0),
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

    $wpdb->query('START TRANSACTION');

    $wpdb->update(kobutsu_ledger_table('sales'), [
        'order_no' => $order_no,
        'sale_date' => kobutsu_ledger_admin_date_or_null($_POST['sale_date'] ?? $sale['sale_date']),
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
        'sale_exchange_rate' => (float) $sale['sale_exchange_rate'],
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

    $wpdb->query('START TRANSACTION');

    $wpdb->update(kobutsu_ledger_table('sales'), [
        'order_no' => $order_no,
        'sale_date' => kobutsu_ledger_admin_date_or_null($payload['sale_date'] ?? $sale['sale_date']),
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
        'total_fees' => (float) $sale['total_fees'],
        'ad_fee' => (float) $sale['ad_fee'],
        'ebay_fee' => (float) $sale['ebay_fee'],
        'payout_amount' => (float) $sale['payout_amount'],
        'sale_exchange_rate' => (float) $sale['sale_exchange_rate'],
        'payout_exchange_rate' => (float) $sale['payout_exchange_rate'],
        'received_amount_jpy' => kobutsu_ledger_payload_int($payload, 'received_amount_jpy', (int) $sale['received_amount_jpy']),
        'overseas_shipping_jpy' => (int) $sale['overseas_shipping_jpy'],
        'fee_tax_refund_jpy' => (int) $sale['fee_tax_refund_jpy'],
        'consumption_tax_refund_jpy' => (int) $sale['consumption_tax_refund_jpy'],
        'profit_jpy' => kobutsu_ledger_payload_int($payload, 'profit_jpy', (int) $sale['profit_jpy']),
        'profit_rate' => kobutsu_ledger_payload_float($payload, 'profit_rate', (float) $sale['profit_rate']),
        'days_to_sell' => (int) $sale['days_to_sell'],
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

function kobutsu_ledger_admin_delete_ec_sale(int $sale_id): void
{
    global $wpdb;

    $wpdb->query('START TRANSACTION');
    $wpdb->delete(kobutsu_ledger_table('sales_settlements'), ['sale_id' => $sale_id], ['%d']);
    $wpdb->delete(kobutsu_ledger_table('sales'), ['id' => $sale_id], ['%d']);
    $wpdb->query('COMMIT');
}

function kobutsu_ledger_admin_format_money(float $amount, string $currency): string
{
    $currency = strtoupper($currency ?: 'USD');

    return $currency . ' ' . number_format($amount, 2);
}

function kobutsu_ledger_admin_format_yen(int $amount): string
{
    return '¥' . number_format($amount);
}

function kobutsu_ledger_admin_format_rate(float $rate): string
{
    if ($rate === 0.0) {
        return '';
    }

    return number_format($rate, 2) . '%';
}

function kobutsu_ledger_ec_sales_fallback_days_to_sell(array $row): string
{
    $purchase_date = (string) ($row['purchase_date'] ?? '');
    $sale_date = (string) ($row['sale_date'] ?? '');

    if (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $purchase_date) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $sale_date)
    ) {
        return '';
    }

    $purchase_ts = strtotime($purchase_date);
    $sale_ts = strtotime($sale_date);
    if ($purchase_ts === false || $sale_ts === false) {
        return '';
    }

    $diff_days = (int) round(($sale_ts - $purchase_ts) / 86400);
    if ($diff_days < 0) {
        return '';
    }

    return (string) $diff_days;
}

function kobutsu_ledger_format_ec_sale_row(array $row): array
{
    $days_to_sell = '';
    if (!empty($row['settlement_id'])) {
        $days_to_sell = (string) ((int) $row['days_to_sell']);
    } else {
        $days_to_sell = kobutsu_ledger_ec_sales_fallback_days_to_sell($row);
    }

    return [
        'sale_id' => (int) $row['sale_id'],
        'bundled_flag' => '',
        'sku' => (string) $row['sku'],
        'order_no' => (string) $row['order_no'],
        'purchase_date' => (string) $row['purchase_date'],
        'listed_at' => '',
        'sold_at' => (string) $row['sale_date'],
        'payout_at' => (string) $row['payout_date'],
        'item_name' => (string) $row['item_name'],
        'purchase_price_jpy' => (int) $row['purchase_price_jpy'],
        'sale_amount_raw' => kobutsu_ledger_admin_format_money((float) $row['sale_amount'], (string) $row['sale_currency']),
        'sale_amount_jpy' => (int) $row['sale_amount_jpy'],
        'total_fees_raw' => (float) $row['total_fees'],
        'ad_fee_raw' => (float) $row['ad_fee'],
        'marketplace_fee_raw' => (float) $row['ebay_fee'],
        'payout_amount_raw' => (float) $row['payout_amount'],
        'sale_exchange_rate' => (float) $row['sale_exchange_rate'],
        'payout_exchange_rate' => (float) $row['payout_exchange_rate'],
        'received_amount_jpy' => (int) $row['received_amount_jpy'],
        'overseas_shipping_yen' => (int) $row['overseas_shipping_jpy'],
        'fee_tax_refund_jpy' => (int) $row['fee_tax_refund_jpy'],
        'purchase_tax_refund_jpy' => (int) $row['consumption_tax_refund_jpy'],
        'profit_jpy' => (int) $row['profit_jpy'],
        'profit_rate' => (float) $row['profit_rate'],
        'days_to_sell' => $days_to_sell,
        'domestic_tracking_no' => (string) $row['tracking_no'],
        'sls_tracking_no' => (string) $row['shipping_site'],
        'settlement_note' => (string) $row['sale_notes'],
    ];
}

function kobutsu_ledger_payload_text(array $payload, string $key, string $fallback): string
{
    if (!array_key_exists($key, $payload)) {
        return $fallback;
    }

    return sanitize_text_field((string) $payload[$key]);
}

function kobutsu_ledger_payload_float(array $payload, string $key, float $fallback): float
{
    if (!array_key_exists($key, $payload)) {
        return $fallback;
    }

    return (float) $payload[$key];
}

function kobutsu_ledger_payload_int(array $payload, string $key, int $fallback): int
{
    if (!array_key_exists($key, $payload)) {
        return $fallback;
    }

    return (int) $payload[$key];
}
