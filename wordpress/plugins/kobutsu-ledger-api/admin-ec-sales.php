<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/ec-sales-admin-view.php';
require_once __DIR__ . '/includes/ec-sales-admin-actions.php';

function kobutsu_ledger_get_ec_sales(WP_REST_Request $request): WP_REST_Response
{
    $rows = kobutsu_ledger_admin_get_ec_sales();

    return rest_ensure_response(array_map('kobutsu_ledger_format_ec_sale_row', $rows));
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
