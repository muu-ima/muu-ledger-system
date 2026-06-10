<?php

if (!defined('ABSPATH')) {
    exit;
}

function kobutsu_ledger_save_supplier_source(
    ?int $item_id,
    WP_REST_Request $request,
    array $purchase_price,
    array $sale_money,
    array $shipping_cost
): bool {
    global $wpdb;

    $sku = $request['sku'] ?: $request['management_no'];
    $now = current_time('mysql');
    $source_row_no = kobutsu_ledger_next_supplier_source_row_no(
        $sku,
        (int) ($request['source_row_no'] ?: 0)
    );

    $data = [
        'source_row_no' => $source_row_no,
        'sku' => $sku,
        'order_no' => $request['order_no'] ?: '',
        'account_name' => $request['account_name'] ?: '',
        'sold_at' => kobutsu_ledger_date_or_null($request['sold_at'] ?? ''),
        'sold_at_raw' => $request['sold_at'] ?: '',
        'acquired_at' => kobutsu_ledger_date_or_null($request['acquired_at'] ?? ''),
        'acquired_at_raw' => $request['acquired_at'] ?: '',
        'buyer_country' => $request['buyer_country'] ?: '',
        'mag' => $request['mag'] ?: '',
        'sale_amount' => $sale_money['amount'],
        'sale_currency' => $sale_money['currency'],
        'purchased_flag' => $request['purchased_flag'] ?: '',
        'purchase_price_jpy' => $purchase_price['amount_jpy'],
        'shipping_cost_jpy' => $shipping_cost['amount_jpy'],
        'points' => $request['points'] ?: '',
        'notes' => $request['shipping_note'] ?: '',
        'packer' => $request['packer'] ?: '',
        'shipping_site' => $request['shipping_site'] ?: '',
        'actual_weight_g' => (int) ($request['actual_weight_g'] ?: 0),
        'dimensional_weight_g' => (int) ($request['dimensional_weight_g'] ?: 0),
        'package_length_cm' => (float) ($request['package_length_cm'] ?: 0),
        'package_width_cm' => (float) ($request['package_width_cm'] ?: 0),
        'package_height_cm' => (float) ($request['package_height_cm'] ?: 0),
        'size_memo' => $request['size_memo'] ?: '',
        'shipping_chat_at_raw' => $request['shipping_chat_at'] ?: '',
        'item_name' => $request['item_name'],
        'supplier_name_raw' => $request['acquired_from'] ?: '',
        'first_mail_at_raw' => $request['first_mail_at'] ?: '',
        'receipt_printed_at_raw' => $request['receipt_printed_at'] ?: '',
        'domestic_tracking_no' => $request['domestic_tracking_no'] ?: '',
        'sls_tracking_no' => $request['sls_tracking_no'] ?: '',
        'yamato_slip_flag' => $request['yamato_slip_flag'] ?: '',
        'balance_checked_flag' => $request['balance_checked_flag'] ?: '',
        'updated_at' => $now,
    ];
    $formats = [
        '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
        '%f', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%f',
        '%f', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
    ];

    if ($item_id !== null) {
        $data = ['item_id' => $item_id] + $data;
        array_unshift($formats, '%d');
    }

    return (bool) $wpdb->replace(kobutsu_ledger_table('supplier_sources'), $data, $formats);
}

function kobutsu_ledger_next_supplier_source_row_no(string $sku, int $requested_row_no = 0): int
{
    global $wpdb;

    if ($requested_row_no > 0) {
        return $requested_row_no;
    }

    $existing_row_no = (int) $wpdb->get_var(
        $wpdb->prepare(
            'SELECT source_row_no FROM ' . kobutsu_ledger_table('supplier_sources') . ' WHERE sku = %s',
            $sku
        )
    );
    if ($existing_row_no > 0) {
        return $existing_row_no;
    }

    $max_row_no = (int) $wpdb->get_var(
        'SELECT COALESCE(MAX(source_row_no), 0) FROM ' . kobutsu_ledger_table('supplier_sources')
    );

    return $max_row_no + 1;
}

function kobutsu_ledger_sync_supplier_source_dependents(array $source): bool
{
    global $wpdb;

    $sku = trim((string) ($source['sku'] ?? ''));
    if ($sku === '') {
        return false;
    }

    $now = current_time('mysql');
    $item_name = (string) ($source['item_name'] ?? '');
    $status = !empty($source['sold_at']) ? 'sold' : 'in_stock';
    $item_id = isset($source['item_id']) ? (int) $source['item_id'] : 0;

    if ($item_id <= 0) {
        $item_id = (int) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT id FROM ' . kobutsu_ledger_table('items') . ' WHERE sku = %s',
                $sku
            )
        );
    }

    if ($item_id > 0) {
        $updated = $wpdb->update(
            kobutsu_ledger_table('items'),
            [
                'item_name' => $item_name,
                'status' => $status,
                'updated_at' => $now,
            ],
            ['id' => $item_id],
            ['%s', '%s', '%s'],
            ['%d']
        );

        if ($updated === false) {
            return false;
        }
    } else {
        $inserted = $wpdb->insert(
            kobutsu_ledger_table('items'),
            [
                'sku' => $sku,
                'item_name' => $item_name,
                'status' => $status,
                'updated_at' => $now,
            ],
            ['%s', '%s', '%s', '%s']
        );

        if (!$inserted) {
            return false;
        }

        $item_id = (int) $wpdb->insert_id;
    }

    $source_updated = $wpdb->update(
        kobutsu_ledger_table('supplier_sources'),
        [
            'item_id' => $item_id,
            'updated_at' => $now,
        ],
        ['sku' => $sku],
        ['%d', '%s'],
        ['%s']
    );

    if ($source_updated === false) {
        return false;
    }

    $supplier_name = trim((string) ($source['supplier_name_raw'] ?? ''));
    $supplier_id = $supplier_name !== '' ? kobutsu_ledger_ensure_supplier($supplier_name) : null;
    $purchase_exists = (int) $wpdb->get_var(
        $wpdb->prepare(
            'SELECT COUNT(*) FROM ' . kobutsu_ledger_table('purchases') . ' WHERE item_id = %d',
            $item_id
        )
    );

    $purchase_data = [
        'item_id' => $item_id,
        'supplier_id' => $supplier_id,
        'purchase_date' => kobutsu_ledger_date_or_null($source['acquired_at'] ?? ''),
        'supplier_name_raw' => $supplier_name,
        'purchase_price_jpy' => (int) ($source['purchase_price_jpy'] ?? 0),
        'source_order_no' => (string) ($source['order_no'] ?? ''),
        'updated_at' => $now,
    ];

    if ($purchase_exists > 0) {
        $purchase_updated = $wpdb->update(
            kobutsu_ledger_table('purchases'),
            $purchase_data,
            ['item_id' => $item_id],
            ['%d', '%d', '%s', '%s', '%d', '%s', '%s'],
            ['%d']
        );

        if ($purchase_updated === false) {
            return false;
        }
    } else {
        $purchase_inserted = $wpdb->insert(
            kobutsu_ledger_table('purchases'),
            $purchase_data,
            ['%d', '%d', '%s', '%s', '%d', '%s', '%s']
        );

        if (!$purchase_inserted) {
            return false;
        }
    }

    $should_sync_sale = $source['sold_at']
        || $source['order_no']
        || (float) ($source['sale_amount'] ?? 0) > 0
        || $source['account_name']
        || $source['buyer_country'];

    if (!$should_sync_sale) {
        return true;
    }

    $sale_exists = (int) $wpdb->get_var(
        $wpdb->prepare(
            'SELECT COUNT(*) FROM ' . kobutsu_ledger_table('sales') . ' WHERE item_id = %d',
            $item_id
        )
    );

    $sale_notes = trim(implode("\n", array_filter([
        !empty($source['notes']) ? '備考: ' . $source['notes'] : '',
        !empty($source['packer']) ? '梱包者: ' . $source['packer'] : '',
        !empty($source['points']) ? 'ポイント: ' . $source['points'] : '',
        !empty($source['shipping_chat_at_raw']) ? '発送チャット: ' . $source['shipping_chat_at_raw'] : '',
    ])));

    $sale_data = [
        'item_id' => $item_id,
        'marketplace' => 'shopee',
        'account_name' => (string) ($source['account_name'] ?? ''),
        'order_no' => (string) ($source['order_no'] ?? ''),
        'sale_date' => kobutsu_ledger_date_or_null($source['sold_at'] ?? ''),
        'sale_amount' => (float) ($source['sale_amount'] ?? 0),
        'sale_currency' => (string) (($source['sale_currency'] ?? '') ?: 'USD'),
        'sale_amount_jpy' => strtoupper((string) ($source['sale_currency'] ?? '')) === 'JPY'
            ? (int) round((float) ($source['sale_amount'] ?? 0))
            : 0,
        'buyer_country' => (string) ($source['buyer_country'] ?? ''),
        'tracking_no' => (string) ($source['domestic_tracking_no'] ?? ''),
        'shipping_site' => (string) ($source['sls_tracking_no'] ?? ''),
        'actual_weight_g' => (int) ($source['actual_weight_g'] ?? 0),
        'dimensional_weight_g' => (int) ($source['dimensional_weight_g'] ?? 0),
        'package_length_cm' => (float) ($source['package_length_cm'] ?? 0),
        'package_width_cm' => (float) ($source['package_width_cm'] ?? 0),
        'package_height_cm' => (float) ($source['package_height_cm'] ?? 0),
        'notes' => $sale_notes,
        'updated_at' => $now,
    ];

    if ($sale_exists > 0) {
        $sale_updated = $wpdb->update(
            kobutsu_ledger_table('sales'),
            $sale_data,
            ['item_id' => $item_id],
            ['%d', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%f', '%s', '%s'],
            ['%d']
        );

        return $sale_updated !== false;
    }

    $sale_inserted = $wpdb->insert(
        kobutsu_ledger_table('sales'),
        $sale_data,
        ['%d', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%f', '%s', '%s']
    );

    return (bool) $sale_inserted;
}
