<?php

if (!defined('ABSPATH')) {
    exit;
}

function kobutsu_ledger_admin_get_supplier_sources(): array
{
    global $wpdb;

    return $wpdb->get_results(
        kobutsu_ledger_supplier_sources_select_sql() . '
        ORDER BY COALESCE(acquired_at, created_at) DESC, id DESC
        LIMIT 100',
        ARRAY_A
    ) ?: [];
}

function kobutsu_ledger_admin_get_supplier_source_by_sku(string $sku): ?array
{
    global $wpdb;

    $row = $wpdb->get_row(
        $wpdb->prepare(
            kobutsu_ledger_supplier_sources_select_sql() . ' WHERE sku = %s',
            $sku
        ),
        ARRAY_A
    );

    return $row ?: null;
}

function kobutsu_ledger_admin_get_supplier_source_by_id(int $source_id): ?array
{
    global $wpdb;

    $row = $wpdb->get_row(
        $wpdb->prepare(
            kobutsu_ledger_supplier_sources_select_sql() . ' WHERE id = %d',
            $source_id
        ),
        ARRAY_A
    );

    return $row ?: null;
}

function kobutsu_ledger_supplier_sources_select_sql(): string
{
    return 'SELECT id, item_id, source_row_no, sku, order_no, account_name, sold_at, sold_at_raw,
        acquired_at, acquired_at_raw, buyer_country, mag, sale_amount, sale_currency,
        purchased_flag, purchase_price_jpy, shipping_cost_jpy, points, notes, packer, shipping_site,
        actual_weight_g, dimensional_weight_g, package_length_cm, package_width_cm,
        package_height_cm, size_memo, shipping_chat_at_raw, item_name, supplier_name_raw, first_mail_at_raw,
        receipt_printed_at_raw, domestic_tracking_no, sls_tracking_no, yamato_slip_flag,
        balance_checked_flag
    FROM ' . kobutsu_ledger_table('supplier_sources');
}
