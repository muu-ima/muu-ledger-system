<?php

if (!defined('ABSPATH')) {
    exit;
}

function kobutsu_ledger_get_items(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;

    $items_table = kobutsu_ledger_table('items');
    $purchases_table = kobutsu_ledger_table('purchases');
    $sales_table = kobutsu_ledger_table('sales');
    $shopee_orders_table = kobutsu_ledger_table('shopee_orders');
    $query_collation = preg_replace('/[^A-Za-z0-9_]/', '', (string) $wpdb->collate);
    if ($query_collation === '') {
        $query_collation = 'utf8mb4_unicode_ci';
    }

    $items = $wpdb->get_results(
        "SELECT i.id, i.sku, i.category, i.item_name, i.quantity, i.accessories, i.condition_label, i.description, i.photo_url, i.status,
            p.purchase_date, p.transaction_type, p.supplier_name_raw, p.seller_identification, p.seller_address,
            p.seller_name, p.seller_age, p.seller_occupation, p.purchase_price_jpy, p.source_order_no,
            s.sale_date, s.marketplace, s.order_no, s.sale_type, s.sale_amount, s.sale_currency, s.sale_amount_jpy,
            s.buyer_country, s.buyer_id, s.buyer_name, s.buyer_city, s.buyer_state, s.buyer_postal_code,
            s.buyer_address1, s.buyer_address2, s.buyer_address3, s.tracking_no, s.shipping_site,
            so.order_no AS shopee_order_no, so.order_status AS shopee_order_status, DATE(so.order_created_at) AS shopee_order_date,
            CASE
                WHEN so.order_no IS NOT NULL
                    AND s.order_no IS NOT NULL
                    AND s.order_no <> ''
                    AND so.order_no COLLATE $query_collation = s.order_no COLLATE $query_collation
                THEN 'order_id'
                WHEN so.order_no IS NOT NULL THEN 'sku'
                ELSE ''
            END AS shopee_match_type,
            so.buyer_username AS shopee_buyer_username, so.country AS shopee_country,
            so.gross_amount AS shopee_gross_amount, so.total_amount AS shopee_total_amount,
            so.grand_total AS shopee_grand_total, so.currency AS shopee_currency,
            so.tracking_number AS shopee_tracking_number, so.shipping_option AS shopee_shipping_option,
            so.shipment_method AS shopee_shipment_method
        FROM $items_table i
        LEFT JOIN $purchases_table p ON p.item_id = i.id
        LEFT JOIN $sales_table s ON s.item_id = i.id
        LEFT JOIN $shopee_orders_table so ON so.id = (
            SELECT so2.id
            FROM $shopee_orders_table so2
            WHERE so2.sku COLLATE $query_collation = i.sku COLLATE $query_collation
                AND (
                    s.order_no IS NULL
                    OR s.order_no = ''
                    OR so2.order_no COLLATE $query_collation = s.order_no COLLATE $query_collation
                )
            ORDER BY
                CASE
                    WHEN s.order_no IS NOT NULL
                        AND s.order_no <> ''
                        AND so2.order_no COLLATE $query_collation = s.order_no COLLATE $query_collation
                    THEN 0
                    ELSE 1
                END,
                so2.order_created_at DESC,
                so2.id DESC
            LIMIT 1
        )
        ORDER BY COALESCE(p.purchase_date, i.created_at) DESC, i.id DESC
        LIMIT 100",
        ARRAY_A
    );

    return rest_ensure_response(array_map('kobutsu_ledger_format_row', $items));
}

function kobutsu_ledger_get_item(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;

    $items_table = kobutsu_ledger_table('items');
    $purchases_table = kobutsu_ledger_table('purchases');
    $sales_table = kobutsu_ledger_table('sales');
    $shopee_orders_table = kobutsu_ledger_table('shopee_orders');
    $query_collation = preg_replace('/[^A-Za-z0-9_]/', '', (string) $wpdb->collate);
    if ($query_collation === '') {
        $query_collation = 'utf8mb4_unicode_ci';
    }

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT i.id, i.sku, i.category, i.item_name, i.quantity, i.accessories, i.condition_label, i.description, i.photo_url, i.status,
                p.purchase_date, p.transaction_type, p.supplier_name_raw, p.seller_identification, p.seller_address,
                p.seller_name, p.seller_age, p.seller_occupation, p.purchase_price_jpy, p.source_order_no,
                s.sale_date, s.marketplace, s.order_no, s.sale_type, s.sale_amount, s.sale_currency, s.sale_amount_jpy,
                s.buyer_country, s.buyer_id, s.buyer_name, s.buyer_city, s.buyer_state, s.buyer_postal_code,
                s.buyer_address1, s.buyer_address2, s.buyer_address3, s.tracking_no, s.shipping_site,
                so.order_no AS shopee_order_no, so.order_status AS shopee_order_status, DATE(so.order_created_at) AS shopee_order_date,
                CASE
                    WHEN so.order_no IS NOT NULL
                        AND s.order_no IS NOT NULL
                        AND s.order_no <> ''
                        AND so.order_no COLLATE $query_collation = s.order_no COLLATE $query_collation
                    THEN 'order_id'
                    WHEN so.order_no IS NOT NULL THEN 'sku'
                    ELSE ''
                END AS shopee_match_type,
                so.buyer_username AS shopee_buyer_username, so.country AS shopee_country,
                so.gross_amount AS shopee_gross_amount, so.total_amount AS shopee_total_amount,
                so.grand_total AS shopee_grand_total, so.currency AS shopee_currency,
                so.tracking_number AS shopee_tracking_number, so.shipping_option AS shopee_shipping_option,
                so.shipment_method AS shopee_shipment_method
            FROM $items_table i
            LEFT JOIN $purchases_table p ON p.item_id = i.id
            LEFT JOIN $sales_table s ON s.item_id = i.id
            LEFT JOIN $shopee_orders_table so ON so.id = (
                SELECT so2.id
                FROM $shopee_orders_table so2
                WHERE so2.sku COLLATE $query_collation = i.sku COLLATE $query_collation
                    AND (
                        s.order_no IS NULL
                        OR s.order_no = ''
                        OR so2.order_no COLLATE $query_collation = s.order_no COLLATE $query_collation
                    )
                ORDER BY
                    CASE
                        WHEN s.order_no IS NOT NULL
                            AND s.order_no <> ''
                            AND so2.order_no COLLATE $query_collation = s.order_no COLLATE $query_collation
                        THEN 0
                        ELSE 1
                    END,
                    so2.order_created_at DESC,
                    so2.id DESC
                LIMIT 1
            )
            WHERE i.id = %d",
            (int) $request['id']
        ),
        ARRAY_A
    );

    if (!$row) {
        return new WP_Error('kobutsu_not_found', '台帳データが見つかりません。', ['status' => 404]);
    }

    return rest_ensure_response(kobutsu_ledger_format_row($row));
}

function kobutsu_ledger_create_item(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;

    $sku = $request['sku'] ?: $request['management_no'];
    if (!$sku) {
        return new WP_Error('kobutsu_missing_sku', 'SKU または管理番号は必須です。', ['status' => 400]);
    }

    $wpdb->query('START TRANSACTION');

    $purchase_price = kobutsu_ledger_parse_money($request['purchase_price']);
    $sale_money = kobutsu_ledger_parse_money($request['sale_amount'] ?: $request['sale_price']);
    $shipping_cost = kobutsu_ledger_parse_money($request['shipping_cost']);

    $inserted = $wpdb->insert(kobutsu_ledger_table('items'), [
        'sku' => $sku,
        'category' => $request['category'] ?: '',
        'item_name' => $request['item_name'],
        'accessories' => $request['accessories'] ?: '',
        'condition_label' => $request['condition_label'] ?: '',
        'description' => $request['description'] ?? '',
        'photo_url' => $request['photo_url'] ?: '',
        'status' => $request['status'] ?: 'in_stock',
    ], ['%s', '%s', '%s', '%s', '%s', '%s', '%s']);

    if (!$inserted) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('kobutsu_insert_failed', '商品データを登録できませんでした。', ['status' => 500]);
    }

    $item_id = (int) $wpdb->insert_id;
    $supplier_id = null;

    if ($request['acquired_from']) {
        $supplier_id = kobutsu_ledger_ensure_supplier((string) $request['acquired_from']);
    }

    $purchase_inserted = $wpdb->insert(kobutsu_ledger_table('purchases'), [
        'item_id' => $item_id,
        'supplier_id' => $supplier_id,
        'purchase_date' => kobutsu_ledger_date_or_null($request['acquired_at'] ?? ''),
        'supplier_name_raw' => $request['acquired_from'] ?: '',
        'purchase_price_jpy' => $purchase_price['amount_jpy'],
        'seller_identification' => $request['seller_identification'] ?: '',
        'source_order_no' => $request['order_no'] ?: '',
    ], ['%d', '%d', '%s', '%s', '%d', '%s', '%s']);

    if (!$purchase_inserted) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('kobutsu_insert_failed', '仕入データを登録できませんでした。', ['status' => 500]);
    }

    if (
        $request['sold_at'] ||
        $request['sold_to'] ||
        $request['sale_amount'] ||
        $request['sale_price'] ||
        $request['order_no']
    ) {
        $sale_inserted = $wpdb->insert(kobutsu_ledger_table('sales'), [
            'item_id' => $item_id,
            'marketplace' => $request['sold_to'] ?: 'ebay',
            'account_name' => $request['account_name'] ?: '',
            'order_no' => $request['order_no'] ?: '',
            'sale_date' => $request['sold_at'] ?: null,
            'sale_amount' => $sale_money['amount'],
            'sale_currency' => $sale_money['currency'],
            'sale_amount_jpy' => $sale_money['amount_jpy'],
            'buyer_country' => $request['buyer_country'] ?: '',
            'shipping_site' => $request['shipping_site'] ?: '',
            'actual_weight_g' => (int) ($request['actual_weight_g'] ?: 0),
            'dimensional_weight_g' => (int) ($request['dimensional_weight_g'] ?: 0),
            'package_length_cm' => (float) ($request['package_length_cm'] ?: 0),
            'package_width_cm' => (float) ($request['package_width_cm'] ?: 0),
            'package_height_cm' => (float) ($request['package_height_cm'] ?: 0),
            'notes' => trim(implode("\n", array_filter([
                $request['shipping_note'] ? '備考: ' . $request['shipping_note'] : '',
                $request['packer'] ? '梱包者: ' . $request['packer'] : '',
                $shipping_cost['amount_jpy'] ? '送料: ' . $request['shipping_cost'] : '',
            ]))),
        ], ['%d', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%d', '%d', '%f', '%f', '%f', '%s']);

        if (!$sale_inserted) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('kobutsu_insert_failed', '販売データを登録できませんでした。', ['status' => 500]);
        }
    }

    $wpdb->query('COMMIT');

    $response = new WP_REST_Request('GET', '/kobutsu/v1/items/' . $item_id);
    $response->set_param('id', $item_id);

    $item_response = kobutsu_ledger_get_item($response);
    if (is_wp_error($item_response)) {
        return $item_response;
    }

    return new WP_REST_Response($item_response->get_data(), 201);
}

function kobutsu_ledger_update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;

    $item_id = (int) $request['id'];
    $current = kobutsu_ledger_admin_get_item($item_id);
    if (!$current) {
        return new WP_Error('kobutsu_not_found', '台帳データが見つかりません。', ['status' => 404]);
    }

    $supplier_name = trim((string) ($request['acquired_from'] ?? $current['supplier_name_raw'] ?? ''));
    $supplier_id = $supplier_name !== '' ? kobutsu_ledger_ensure_supplier($supplier_name) : null;
    $purchase_exists = (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM ' . kobutsu_ledger_table('purchases') . ' WHERE item_id = %d',
        $item_id
    ));
    $sales_exists = (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM ' . kobutsu_ledger_table('sales') . ' WHERE item_id = %d',
        $item_id
    ));
    $param_or_current = static function (string $key, $fallback) use ($request) {
        return $request->has_param($key) ? $request->get_param($key) : $fallback;
    };

    $purchase_price = kobutsu_ledger_parse_money(
        $param_or_current('purchase_price', $current['purchase_price_jpy'] ?? 0)
    );
    $sale_money = kobutsu_ledger_parse_money(
        $param_or_current('sale_amount', $current['sale_amount'] ?? 0)
    );

    $wpdb->query('START TRANSACTION');

    $updated = $wpdb->update(kobutsu_ledger_table('items'), [
        'sku' => (string) $param_or_current('sku', $current['sku']),
        'item_name' => (string) $param_or_current('item_name', $current['item_name']),
        'category' => (string) $param_or_current('category', $current['category']),
        'accessories' => (string) $param_or_current('accessories', $current['accessories'] ?? ''),
        'condition_label' => (string) $param_or_current('condition_label', $current['condition_label'] ?? ''),
        'description' => (string) $param_or_current('description', $current['description'] ?? ''),
        'photo_url' => (string) $param_or_current('photo_url', $current['photo_url'] ?? ''),
        'status' => (string) $param_or_current('status', $current['status']),
        'updated_at' => current_time('mysql'),
    ], ['id' => $item_id], ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'], ['%d']);
    if ($updated === false) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('kobutsu_update_failed', '商品データを更新できませんでした。', ['status' => 500]);
    }

    $purchase_data = [
        'item_id' => $item_id,
        'supplier_id' => $supplier_id,
        'purchase_date' => kobutsu_ledger_date_or_null((string) $param_or_current('acquired_at', $current['purchase_date'] ?? '')),
        'supplier_name_raw' => $supplier_name,
        'purchase_price_jpy' => $purchase_price['amount_jpy'],
        'seller_identification' => (string) $param_or_current('seller_identification', $current['seller_identification'] ?? ''),
        'source_order_no' => (string) $param_or_current('order_no', $current['source_order_no'] ?? ''),
        'updated_at' => current_time('mysql'),
    ];
    if ($purchase_exists) {
        $updated = $wpdb->update(
            kobutsu_ledger_table('purchases'),
            $purchase_data,
            ['item_id' => $item_id],
            ['%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s'],
            ['%d']
        );
        if ($updated === false) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('kobutsu_update_failed', '仕入データを更新できませんでした。', ['status' => 500]);
        }
    } else {
        $inserted = $wpdb->insert(
            kobutsu_ledger_table('purchases'),
            $purchase_data + ['created_at' => current_time('mysql')],
            ['%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );
        if (!$inserted) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('kobutsu_insert_failed', '仕入データを登録できませんでした。', ['status' => 500]);
        }
    }

    $marketplace = (string) $param_or_current('sold_to', $current['marketplace'] ?? '');
    $order_no = (string) $param_or_current('order_no', $current['source_order_no'] ?? '');
    $sale_date = kobutsu_ledger_date_or_null((string) $param_or_current('sold_at', $current['sale_date'] ?? ''));
    if ($sales_exists || $marketplace !== '' || $order_no !== '' || $sale_date !== null) {
        $sale_data = [
            'item_id' => $item_id,
            'marketplace' => $marketplace,
            'account_name' => (string) $param_or_current('account_name', $current['account_name'] ?? ''),
            'order_no' => $order_no,
            'sale_date' => $sale_date,
            'sale_amount' => $sale_money['amount'],
            'sale_currency' => $sale_money['currency'],
            'sale_amount_jpy' => $sale_money['amount_jpy'],
            'buyer_country' => (string) $param_or_current('buyer_country', $current['buyer_country'] ?? ''),
            'shipping_site' => (string) $param_or_current('shipping_site', $current['shipping_site'] ?? ''),
            'actual_weight_g' => (int) $param_or_current('actual_weight_g', $current['actual_weight_g'] ?? 0),
            'dimensional_weight_g' => (int) $param_or_current('dimensional_weight_g', $current['dimensional_weight_g'] ?? 0),
            'package_length_cm' => (float) $param_or_current('package_length_cm', $current['package_length_cm'] ?? 0),
            'package_width_cm' => (float) $param_or_current('package_width_cm', $current['package_width_cm'] ?? 0),
            'package_height_cm' => (float) $param_or_current('package_height_cm', $current['package_height_cm'] ?? 0),
            'notes' => (string) $param_or_current('description', $current['sale_notes'] ?? ''),
            'updated_at' => current_time('mysql'),
        ];
        if ($sales_exists) {
            $updated = $wpdb->update(
                kobutsu_ledger_table('sales'),
                $sale_data,
                ['item_id' => $item_id],
                ['%d', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%d', '%d', '%f', '%f', '%f', '%s', '%s'],
                ['%d']
            );
            if ($updated === false) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('kobutsu_update_failed', '販売データを更新できませんでした。', ['status' => 500]);
            }
        } else {
            $inserted = $wpdb->insert(
                kobutsu_ledger_table('sales'),
                $sale_data + ['created_at' => current_time('mysql')],
                ['%d', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%d', '%d', '%f', '%f', '%f', '%s', '%s', '%s']
            );
            if (!$inserted) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('kobutsu_insert_failed', '販売データを登録できませんでした。', ['status' => 500]);
            }
        }
    }

    $wpdb->query('COMMIT');

    $response = new WP_REST_Request('GET', '/kobutsu/v1/items/' . $item_id);
    $response->set_param('id', $item_id);

    return kobutsu_ledger_get_item($response);
}

function kobutsu_ledger_get_schema(): WP_REST_Response
{
    return rest_ensure_response([
        'version' => KOBUTSU_LEDGER_DB_VERSION,
        'tables' => [
            'suppliers' => [
                'source' => 'supplier_master_sample.csv',
                'purpose' => '仕入先マスタ、本人確認、住所、連絡先',
            ],
            'supplier_sources' => [
                'source' => 'supplier_master_sample.csv',
                'purpose' => '仕入れ管理の原票。仕入元データの列を保持し、古物台帳へ反映する前後の確認に使う',
            ],
            'items' => [
                'source' => 'purchases_sample.csv / ledger_sample.csv',
                'purpose' => 'SKU単位の商品、品目、商品名、状態、写真',
            ],
            'purchases' => [
                'source' => 'purchases_sample.csv / ledger_sample.csv',
                'purpose' => '受入れ、仕入先、仕入金額、古物営業法上の相手方情報',
            ],
            'sales' => [
                'source' => 'ledger_sample.csv / supplier_master_sample.csv',
                'purpose' => '払出し、eBay注文、販売先、配送、買主住所',
            ],
            'sales_settlements' => [
                'source' => 'ec_sales_sample.csv',
                'purpose' => '販売金額、手数料、為替、送料、損益',
            ],
            'payment_transactions' => [
                'source' => 'sales_payments_sample.csv',
                'purpose' => 'eBay/Payoneerの入金・手数料の原明細',
            ],
            'exchange_rates' => [
                'source' => 'exchange_rates_sample.csv',
                'purpose' => '日別通貨別の円換算レート',
            ],
            'import_batches' => [
                'source' => 'all csv files',
                'purpose' => 'CSV取込履歴とエラー件数',
            ],
        ],
    ]);
}

function kobutsu_ledger_get_payments(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;

    $payment_transactions = kobutsu_ledger_table('payment_transactions');
    $import_batches = kobutsu_ledger_table('import_batches');

    $transactions = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, transaction_date, order_no, buyer_username, net_amount, payout_currency,
                payout_date, payout_method, payout_status, gross_transaction_amount,
                transaction_currency, reference_id, created_at
            FROM $payment_transactions
            WHERE transaction_type = %s
            ORDER BY id DESC
            LIMIT 500",
            'shopee_payment'
        ),
        ARRAY_A
    );

    $batches = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, source_name, original_filename, status, imported_rows, error_rows,
                notes, created_at, completed_at
            FROM $import_batches
            WHERE source_name = %s
            ORDER BY id DESC
            LIMIT 100",
            'shopee_payments'
        ),
        ARRAY_A
    );

    return rest_ensure_response([
        'transactions' => array_map('kobutsu_ledger_format_payment_api_row', $transactions),
        'batches' => array_map('kobutsu_ledger_format_payment_api_row', $batches),
    ]);
}

function kobutsu_ledger_get_shopee_orders(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;

    $shopee_orders = kobutsu_ledger_table('shopee_orders');
    $import_batches = kobutsu_ledger_table('import_batches');

    $orders = $wpdb->get_results(
        "SELECT id, order_no, order_status, order_created_at, order_paid_at,
            order_completed_at, ship_time, estimated_ship_out_at, buyer_username,
            country, parent_sku, sku, product_name, variation_name, quantity,
            returned_quantity, gross_amount, total_amount, grand_total, currency,
            tracking_number, shipping_option, shipment_method, cancel_reason,
            return_refund_status, source_line_number, created_at
        FROM $shopee_orders
        ORDER BY id DESC
        LIMIT 500",
        ARRAY_A
    );

    $batches = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id, source_name, original_filename, status, imported_rows, error_rows,
                notes, created_at, completed_at
            FROM $import_batches
            WHERE source_name = %s
            ORDER BY id DESC
            LIMIT 100",
            'shopee_orders'
        ),
        ARRAY_A
    );

    return rest_ensure_response([
        'orders' => array_map('kobutsu_ledger_format_payment_api_row', $orders),
        'batches' => array_map('kobutsu_ledger_format_payment_api_row', $batches),
    ]);
}

function kobutsu_ledger_get_exchange_rates(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;

    $exchange_rates = kobutsu_ledger_table('exchange_rates');
    $rates = $wpdb->get_results(
        "SELECT id, rate_date, base_currency, quote_currency, rate, source,
            is_manual_override, fetched_at, notes, created_at, updated_at
        FROM $exchange_rates
        ORDER BY rate_date DESC, base_currency ASC, source ASC
        LIMIT 500",
        ARRAY_A
    );

    return rest_ensure_response([
        'rates' => array_map('kobutsu_ledger_format_payment_api_row', $rates),
        'last_fetch' => kobutsu_ledger_exchange_rate_last_fetch(),
        'next_fetch_at' => kobutsu_ledger_exchange_rate_next_fetch_label(),
    ]);
}

function kobutsu_ledger_format_payment_api_row(array $row): array
{
    $formatted = [];
    foreach ($row as $key => $value) {
        $formatted[$key] = $value === null ? '' : $value;
    }

    return $formatted;
}
