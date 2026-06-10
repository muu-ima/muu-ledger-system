<?php

if (!defined('ABSPATH')) {
    exit;
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
