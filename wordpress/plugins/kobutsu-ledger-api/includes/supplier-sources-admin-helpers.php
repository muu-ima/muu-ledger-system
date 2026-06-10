<?php

if (!defined('ABSPATH')) {
    exit;
}

function kobutsu_ledger_format_supplier_source_row(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'item_id' => isset($row['item_id']) ? (int) $row['item_id'] : 0,
        'source_row_no' => (int) $row['source_row_no'],
        'sku' => (string) $row['sku'],
        'order_no' => (string) $row['order_no'],
        'account_name' => (string) $row['account_name'],
        'sold_at' => (string) ($row['sold_at'] ?? ''),
        'sold_at_raw' => (string) ($row['sold_at_raw'] ?? ''),
        'acquired_at' => (string) ($row['acquired_at'] ?? ''),
        'acquired_at_raw' => (string) ($row['acquired_at_raw'] ?? ''),
        'buyer_country' => (string) $row['buyer_country'],
        'mag' => (string) $row['mag'],
        'sale_amount' => (float) $row['sale_amount'],
        'sale_currency' => (string) $row['sale_currency'],
        'purchased_flag' => (string) ($row['purchased_flag'] ?? ''),
        'purchase_price_jpy' => (int) $row['purchase_price_jpy'],
        'shipping_cost_jpy' => (int) $row['shipping_cost_jpy'],
        'points' => (string) $row['points'],
        'notes' => (string) ($row['notes'] ?? ''),
        'packer' => (string) $row['packer'],
        'shipping_site' => (string) $row['shipping_site'],
        'actual_weight_g' => (int) $row['actual_weight_g'],
        'dimensional_weight_g' => (int) $row['dimensional_weight_g'],
        'package_length_cm' => (float) $row['package_length_cm'],
        'package_width_cm' => (float) $row['package_width_cm'],
        'package_height_cm' => (float) $row['package_height_cm'],
        'size_memo' => (string) ($row['size_memo'] ?? ''),
        'shipping_chat_at_raw' => (string) ($row['shipping_chat_at_raw'] ?? ''),
        'item_name' => (string) $row['item_name'],
        'supplier_name_raw' => (string) $row['supplier_name_raw'],
        'first_mail_at_raw' => (string) $row['first_mail_at_raw'],
        'receipt_printed_at_raw' => (string) $row['receipt_printed_at_raw'],
        'domestic_tracking_no' => (string) ($row['domestic_tracking_no'] ?? ''),
        'sls_tracking_no' => (string) ($row['sls_tracking_no'] ?? ''),
        'yamato_slip_flag' => (string) ($row['yamato_slip_flag'] ?? ''),
        'balance_checked_flag' => (string) ($row['balance_checked_flag'] ?? ''),
    ];
}

function kobutsu_ledger_admin_format_supplier_source_sale_amount(array $row): string
{
    $currency = (string) ($row['sale_currency'] ?? 'USD');
    $amount = (float) ($row['sale_amount'] ?? 0);

    if ($amount === 0.0) {
        return '';
    }

    if ($currency === 'JPY') {
        return '¥' . number_format((int) round($amount));
    }

    return $currency . number_format($amount, 2, '.', '');
}
