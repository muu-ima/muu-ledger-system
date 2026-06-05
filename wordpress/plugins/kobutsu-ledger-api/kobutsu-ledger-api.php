<?php
/**
 * Plugin Name: Kobutsu Ledger API
 * Description: 古物台帳システム用の REST API とカスタムテーブルを追加します。
 * Version: 0.1.0
 * Author: Kobutsu Ledger
 */

if (!defined('ABSPATH')) {
    exit;
}

const KOBUTSU_LEDGER_DB_VERSION = '0.2.0';

register_activation_hook(__FILE__, 'kobutsu_ledger_activate');
add_action('plugins_loaded', 'kobutsu_ledger_maybe_upgrade');
add_action('rest_api_init', 'kobutsu_ledger_register_routes');

function kobutsu_ledger_activate(): void
{
    kobutsu_ledger_create_tables();
    update_option('kobutsu_ledger_db_version', KOBUTSU_LEDGER_DB_VERSION);
}

function kobutsu_ledger_maybe_upgrade(): void
{
    if (get_option('kobutsu_ledger_db_version') !== KOBUTSU_LEDGER_DB_VERSION) {
        kobutsu_ledger_activate();
    }
}

function kobutsu_ledger_table(string $name): string
{
    global $wpdb;

    return $wpdb->prefix . 'kobutsu_' . $name;
}

function kobutsu_ledger_create_tables(): void
{
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $suppliers = kobutsu_ledger_table('suppliers');
    $items = kobutsu_ledger_table('items');
    $purchases = kobutsu_ledger_table('purchases');
    $sales = kobutsu_ledger_table('sales');
    $settlements = kobutsu_ledger_table('sales_settlements');
    $payment_transactions = kobutsu_ledger_table('payment_transactions');
    $exchange_rates = kobutsu_ledger_table('exchange_rates');
    $import_batches = kobutsu_ledger_table('import_batches');

    dbDelta("CREATE TABLE $suppliers (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        supplier_name varchar(191) NOT NULL,
        supplier_code varchar(80) NOT NULL DEFAULT '',
        channel varchar(80) NOT NULL DEFAULT '',
        identification_method varchar(191) NOT NULL DEFAULT '',
        address text NULL,
        contact text NULL,
        notes text NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY supplier_name (supplier_name),
        KEY supplier_code (supplier_code),
        KEY channel (channel)
    ) $charset_collate;");

    dbDelta("CREATE TABLE $items (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        sku varchar(120) NOT NULL,
        item_name text NOT NULL,
        category varchar(120) NOT NULL DEFAULT '',
        quantity int unsigned NOT NULL DEFAULT 1,
        condition_label varchar(80) NOT NULL DEFAULT '',
        accessories text NULL,
        description text NULL,
        photo_url text NULL,
        status varchar(40) NOT NULL DEFAULT 'in_stock',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY sku (sku),
        KEY category (category),
        KEY status (status)
    ) $charset_collate;");

    dbDelta("CREATE TABLE $purchases (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        item_id bigint(20) unsigned NOT NULL,
        supplier_id bigint(20) unsigned NULL,
        purchase_date date NULL,
        transaction_type varchar(40) NOT NULL DEFAULT 'buy',
        supplier_name_raw varchar(191) NOT NULL DEFAULT '',
        purchase_price_jpy int NOT NULL DEFAULT 0,
        seller_identification varchar(191) NOT NULL DEFAULT '',
        seller_address text NULL,
        seller_name varchar(191) NOT NULL DEFAULT '',
        seller_age varchar(40) NOT NULL DEFAULT '',
        seller_occupation varchar(120) NOT NULL DEFAULT '',
        source_order_no varchar(120) NOT NULL DEFAULT '',
        notes text NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY item_id (item_id),
        KEY supplier_id (supplier_id),
        KEY purchase_date (purchase_date),
        KEY source_order_no (source_order_no)
    ) $charset_collate;");

    dbDelta("CREATE TABLE $sales (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        item_id bigint(20) unsigned NOT NULL,
        marketplace varchar(80) NOT NULL DEFAULT '',
        account_name varchar(120) NOT NULL DEFAULT '',
        order_no varchar(120) NOT NULL DEFAULT '',
        sale_date date NULL,
        sale_type varchar(40) NOT NULL DEFAULT 'sold',
        sale_amount decimal(14,2) NOT NULL DEFAULT 0,
        sale_currency char(3) NOT NULL DEFAULT 'USD',
        sale_amount_jpy int NOT NULL DEFAULT 0,
        buyer_country varchar(120) NOT NULL DEFAULT '',
        buyer_id varchar(191) NOT NULL DEFAULT '',
        buyer_name varchar(191) NOT NULL DEFAULT '',
        buyer_city varchar(120) NOT NULL DEFAULT '',
        buyer_state varchar(120) NOT NULL DEFAULT '',
        buyer_postal_code varchar(40) NOT NULL DEFAULT '',
        buyer_address1 text NULL,
        buyer_address2 text NULL,
        buyer_address3 text NULL,
        tracking_no varchar(191) NOT NULL DEFAULT '',
        shipping_site varchar(120) NOT NULL DEFAULT '',
        actual_weight_g int NOT NULL DEFAULT 0,
        dimensional_weight_g int NOT NULL DEFAULT 0,
        package_length_cm decimal(8,2) NOT NULL DEFAULT 0,
        package_width_cm decimal(8,2) NOT NULL DEFAULT 0,
        package_height_cm decimal(8,2) NOT NULL DEFAULT 0,
        notes text NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY order_no (order_no),
        KEY item_id (item_id),
        KEY sale_date (sale_date),
        KEY marketplace (marketplace),
        KEY buyer_id (buyer_id)
    ) $charset_collate;");

    dbDelta("CREATE TABLE $settlements (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        sale_id bigint(20) unsigned NULL,
        order_no varchar(120) NOT NULL DEFAULT '',
        payout_date date NULL,
        payout_id varchar(120) NOT NULL DEFAULT '',
        total_fees decimal(14,2) NOT NULL DEFAULT 0,
        ad_fee decimal(14,2) NOT NULL DEFAULT 0,
        ebay_fee decimal(14,2) NOT NULL DEFAULT 0,
        payout_amount decimal(14,2) NOT NULL DEFAULT 0,
        sale_exchange_rate decimal(10,4) NOT NULL DEFAULT 0,
        payout_exchange_rate decimal(10,4) NOT NULL DEFAULT 0,
        received_amount_jpy int NOT NULL DEFAULT 0,
        overseas_shipping_jpy int NOT NULL DEFAULT 0,
        fee_tax_refund_jpy int NOT NULL DEFAULT 0,
        consumption_tax_refund_jpy int NOT NULL DEFAULT 0,
        profit_jpy int NOT NULL DEFAULT 0,
        profit_rate decimal(8,4) NOT NULL DEFAULT 0,
        days_to_sell int NOT NULL DEFAULT 0,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY sale_id (sale_id),
        KEY order_no (order_no),
        KEY payout_date (payout_date),
        KEY payout_id (payout_id)
    ) $charset_collate;");

    dbDelta("CREATE TABLE $payment_transactions (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        transaction_date date NULL,
        transaction_type varchar(80) NOT NULL DEFAULT '',
        order_no varchar(120) NOT NULL DEFAULT '',
        buyer_username varchar(191) NOT NULL DEFAULT '',
        buyer_name varchar(191) NOT NULL DEFAULT '',
        net_amount decimal(14,2) NOT NULL DEFAULT 0,
        payout_currency char(3) NOT NULL DEFAULT 'USD',
        payout_date date NULL,
        payout_id varchar(120) NOT NULL DEFAULT '',
        payout_method varchar(191) NOT NULL DEFAULT '',
        payout_status varchar(80) NOT NULL DEFAULT '',
        item_id_external varchar(120) NOT NULL DEFAULT '',
        transaction_id_external varchar(120) NOT NULL DEFAULT '',
        item_title text NULL,
        sku varchar(120) NOT NULL DEFAULT '',
        quantity int NOT NULL DEFAULT 0,
        gross_transaction_amount decimal(14,2) NOT NULL DEFAULT 0,
        transaction_currency char(3) NOT NULL DEFAULT 'USD',
        exchange_rate decimal(10,5) NOT NULL DEFAULT 0,
        reference_id varchar(191) NOT NULL DEFAULT '',
        description text NULL,
        raw_payload longtext NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY transaction_date (transaction_date),
        KEY transaction_type (transaction_type),
        KEY order_no (order_no),
        KEY payout_id (payout_id),
        KEY sku (sku)
    ) $charset_collate;");

    dbDelta("CREATE TABLE $exchange_rates (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        rate_date date NOT NULL,
        currency_code char(3) NOT NULL,
        rate_jpy decimal(10,4) NOT NULL,
        source varchar(191) NOT NULL DEFAULT 'mizuho',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY rate_date_currency (rate_date,currency_code),
        KEY currency_code (currency_code)
    ) $charset_collate;");

    dbDelta("CREATE TABLE $import_batches (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        source_name varchar(120) NOT NULL,
        original_filename varchar(255) NOT NULL DEFAULT '',
        status varchar(40) NOT NULL DEFAULT 'pending',
        imported_rows int NOT NULL DEFAULT 0,
        error_rows int NOT NULL DEFAULT 0,
        notes text NULL,
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        completed_at datetime NULL,
        PRIMARY KEY  (id),
        KEY source_name (source_name),
        KEY status (status)
    ) $charset_collate;");
}

function kobutsu_ledger_register_routes(): void
{
    register_rest_route('kobutsu/v1', '/items', [
        [
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'kobutsu_ledger_get_items',
            'permission_callback' => 'kobutsu_ledger_can_read',
        ],
        [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'kobutsu_ledger_create_item',
            'permission_callback' => 'kobutsu_ledger_can_write',
            'args' => kobutsu_ledger_rest_args(),
        ],
    ]);

    register_rest_route('kobutsu/v1', '/items/(?P<id>\d+)', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'kobutsu_ledger_get_item',
        'permission_callback' => 'kobutsu_ledger_can_read',
    ]);

    register_rest_route('kobutsu/v1', '/schema', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'kobutsu_ledger_get_schema',
        'permission_callback' => 'kobutsu_ledger_can_read',
    ]);
}

function kobutsu_ledger_can_read(): bool
{
    return true;
}

function kobutsu_ledger_can_write(): bool
{
    return current_user_can('edit_posts');
}

function kobutsu_ledger_rest_args(): array
{
    return [
        'sku' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'management_no' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'category' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'item_name' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
        'description' => ['required' => false, 'sanitize_callback' => 'sanitize_textarea_field'],
        'acquired_at' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
        'acquired_from' => ['required' => true, 'sanitize_callback' => 'sanitize_text_field'],
        'seller_identification' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'purchase_price' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'order_no' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'account_name' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'buyer_country' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'sold_at' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'sold_to' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'sale_amount' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'sale_price' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'shipping_cost' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'shipping_note' => ['required' => false, 'sanitize_callback' => 'sanitize_textarea_field'],
        'packer' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'shipping_site' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'actual_weight_g' => ['required' => false, 'sanitize_callback' => 'absint'],
        'dimensional_weight_g' => ['required' => false, 'sanitize_callback' => 'absint'],
        'package_length_cm' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'package_width_cm' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'package_height_cm' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'status' => ['required' => false, 'sanitize_callback' => 'sanitize_key'],
    ];
}

function kobutsu_ledger_get_items(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;

    $items = $wpdb->get_results(
        "SELECT i.id, i.sku, i.category, i.item_name, i.description, i.status,
            p.purchase_date, p.supplier_name_raw, p.seller_identification, p.purchase_price_jpy,
            s.sale_date, s.marketplace, s.sale_amount, s.sale_currency, s.sale_amount_jpy
        FROM " . kobutsu_ledger_table('items') . " i
        LEFT JOIN " . kobutsu_ledger_table('purchases') . " p ON p.item_id = i.id
        LEFT JOIN " . kobutsu_ledger_table('sales') . " s ON s.item_id = i.id
        ORDER BY COALESCE(p.purchase_date, i.created_at) DESC, i.id DESC
        LIMIT 100",
        ARRAY_A
    );

    return rest_ensure_response(array_map('kobutsu_ledger_format_row', $items));
}

function kobutsu_ledger_get_item(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT i.id, i.sku, i.category, i.item_name, i.description, i.status,
                p.purchase_date, p.supplier_name_raw, p.seller_identification, p.purchase_price_jpy,
                s.sale_date, s.marketplace, s.sale_amount, s.sale_currency, s.sale_amount_jpy
            FROM " . kobutsu_ledger_table('items') . " i
            LEFT JOIN " . kobutsu_ledger_table('purchases') . " p ON p.item_id = i.id
            LEFT JOIN " . kobutsu_ledger_table('sales') . " s ON s.item_id = i.id
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
    if (str_contains($raw, '¥')) {
        $currency = 'JPY';
    } elseif (str_contains($raw, 'AUD') || str_contains($raw, 'AU$')) {
        $currency = 'AUD';
    } elseif (str_contains($raw, '€')) {
        $currency = 'EUR';
    } elseif (str_contains($raw, '£') || str_contains($raw, '￡')) {
        $currency = 'GBP';
    } elseif (str_contains($raw, 'c$')) {
        $currency = 'CAD';
    }

    $amount = (float) preg_replace('/[^0-9.\-]/', '', $raw);

    return [
        'amount' => $amount,
        'amount_jpy' => $currency === 'JPY' ? (int) round($amount) : 0,
        'currency' => $currency,
    ];
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
        'description' => $request['description'] ?? '',
        'status' => $request['status'] ?: 'in_stock',
    ], ['%s', '%s', '%s', '%s', '%s']);

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
        'purchase_date' => $request['acquired_at'] ?: null,
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
    return new WP_REST_Response(kobutsu_ledger_get_item($response)->get_data(), 201);
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
        'category' => (string) $row['category'],
        'item_name' => (string) $row['item_name'],
        'description' => (string) ($row['description'] ?? ''),
        'acquired_at' => (string) ($row['purchase_date'] ?? ''),
        'acquired_from' => (string) ($row['supplier_name_raw'] ?? ''),
        'seller_identification' => (string) ($row['seller_identification'] ?? ''),
        'purchase_price' => (int) ($row['purchase_price_jpy'] ?? 0),
        'sold_at' => (string) ($row['sale_date'] ?? ''),
        'sold_to' => (string) ($row['marketplace'] ?? ''),
        'sale_price' => (int) ($row['sale_amount_jpy'] ?? 0),
        'sale_amount' => (float) ($row['sale_amount'] ?? 0),
        'sale_currency' => (string) ($row['sale_currency'] ?? 'USD'),
        'status' => (string) ($row['status'] ?: 'in_stock'),
    ];
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
