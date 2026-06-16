<?php

if (!defined('ABSPATH')) {
    exit;
}

function kobutsu_ledger_parse_money($value): array
{
    $raw = trim((string) $value);
    if ($raw === '') {
        return [
            'amount' => 0.0,
            'amount_jpy' => 0,
            'currency' => 'USD',
        ];
    }

    $currency = 'USD';
    $normalized = strtoupper($raw);

    if (str_contains($raw, '¥') || str_contains($normalized, 'JPY')) {
        $currency = 'JPY';
    } elseif (
        str_contains($raw, '₱') ||
        str_contains($normalized, 'PHP')
    ) {
        $currency = 'PHP';
    } elseif (
        str_contains($normalized, 'SGD') ||
        str_contains($normalized, 'SDG')
    ) {
        $currency = 'SGD';
    } elseif (
        str_contains($normalized, 'AUD') ||
        str_contains($raw, 'AU$') ||
        str_contains($raw, 'A$')
    ) {
        $currency = 'AUD';
    } elseif (
        str_contains($normalized, 'CAD') ||
        str_contains($raw, 'C$') ||
        str_contains($raw, 'CA$')
    ) {
        $currency = 'CAD';
    } elseif (
        str_contains($normalized, 'GBP') ||
        str_contains($raw, '£') ||
        str_contains($raw, '￡')
    ) {
        $currency = 'GBP';
    } elseif (
        str_contains($normalized, 'EUR') ||
        str_contains($raw, '€')
    ) {
        $currency = 'EUR';
    } elseif (
        str_contains($normalized, 'BRL') ||
        str_contains($raw, 'R$')
    ) {
        $currency = 'BRL';
    } elseif (
        str_contains($normalized, 'USD') ||
        str_contains($raw, '$')
    ) {
        $currency = 'USD';
    }

    $amount = (float) preg_replace('/[^0-9.\-]/', '', $raw);

    return [
        'amount' => $amount,
        'amount_jpy' => $currency === 'JPY' ? (int) round($amount) : 0,
        'currency' => $currency,
    ];
}

function kobutsu_ledger_date_or_null($value): ?string
{
    $date = sanitize_text_field((string) $value);

    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null;
}

function kobutsu_ledger_ensure_supplier(string $supplier_name): ?int
{
    global $wpdb;

    $supplier_name = trim($supplier_name);
    if ($supplier_name === '') {
        return null;
    }

    $table = kobutsu_ledger_table('suppliers');
    $supplier_id = $wpdb->get_var(
        $wpdb->prepare("SELECT id FROM $table WHERE supplier_name = %s", $supplier_name)
    );

    if ($supplier_id) {
        return (int) $supplier_id;
    }

    $inserted = $wpdb->insert($table, [
        'supplier_name' => $supplier_name,
    ], ['%s']);

    return $inserted ? (int) $wpdb->insert_id : null;
}

function kobutsu_ledger_format_row(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'management_no' => (string) $row['sku'],
        'sku' => (string) $row['sku'],
        'order_no' => (string) ($row['order_no'] ?? $row['source_order_no'] ?? ''),
        'source_order_no' => (string) ($row['source_order_no'] ?? ''),
        'category' => (string) $row['category'],
        'item_name' => (string) $row['item_name'],
        'quantity' => (int) ($row['quantity'] ?? 1),
        'accessories' => (string) ($row['accessories'] ?? ''),
        'condition_label' => (string) ($row['condition_label'] ?? ''),
        'description' => (string) ($row['description'] ?? ''),
        'photo_url' => (string) ($row['photo_url'] ?? ''),
        'acquired_at' => (string) ($row['purchase_date'] ?? ''),
        'purchase_type' => (string) ($row['transaction_type'] ?? ''),
        'acquired_from' => (string) ($row['supplier_name_raw'] ?? ''),
        'seller_identification' => (string) ($row['seller_identification'] ?? ''),
        'seller_address' => (string) ($row['seller_address'] ?? ''),
        'seller_name' => (string) ($row['seller_name'] ?? ''),
        'seller_age' => (string) ($row['seller_age'] ?? ''),
        'seller_occupation' => (string) ($row['seller_occupation'] ?? ''),
        'purchase_price' => (int) ($row['purchase_price_jpy'] ?? 0),
        'sold_at' => (string) ($row['sale_date'] ?? ''),
        'sold_to' => (string) ($row['marketplace'] ?? ''),
        'sale_type' => (string) ($row['sale_type'] ?? ''),
        'sale_price' => (int) ($row['sale_amount_jpy'] ?? 0),
        'sale_amount' => (float) ($row['sale_amount'] ?? 0),
        'sale_currency' => (string) ($row['sale_currency'] ?? 'USD'),
        'buyer_country' => (string) ($row['buyer_country'] ?? ''),
        'buyer_id' => (string) ($row['buyer_id'] ?? ''),
        'buyer_name' => (string) ($row['buyer_name'] ?? ''),
        'buyer_city' => (string) ($row['buyer_city'] ?? ''),
        'buyer_state' => (string) ($row['buyer_state'] ?? ''),
        'buyer_postal_code' => (string) ($row['buyer_postal_code'] ?? ''),
        'buyer_address1' => (string) ($row['buyer_address1'] ?? ''),
        'buyer_address2' => (string) ($row['buyer_address2'] ?? ''),
        'buyer_address3' => (string) ($row['buyer_address3'] ?? ''),
        'tracking_no' => (string) ($row['tracking_no'] ?? ''),
        'shipping_site' => (string) ($row['shipping_site'] ?? ''),
        'status' => (string) ($row['status'] ?: 'in_stock'),
    ];
}
