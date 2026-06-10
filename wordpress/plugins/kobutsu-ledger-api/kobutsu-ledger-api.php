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

require_once __DIR__ . '/admin-supplier-sources.php';
require_once __DIR__ . '/admin-ec-sales.php';

const KOBUTSU_LEDGER_DB_VERSION = '0.3.0';

register_activation_hook(__FILE__, 'kobutsu_ledger_activate');
add_action('plugins_loaded', 'kobutsu_ledger_maybe_upgrade');
add_action('rest_api_init', 'kobutsu_ledger_register_routes');
add_action('admin_menu', 'kobutsu_ledger_register_admin_menu');
add_action('admin_init', 'kobutsu_ledger_handle_admin_action');
add_action('admin_init', 'kobutsu_ledger_handle_supplier_sources_admin_action');
add_action('admin_init', 'kobutsu_ledger_handle_ec_sales_admin_action');
add_filter('rest_pre_serve_request', 'kobutsu_ledger_local_rest_headers', 10, 4);

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

function kobutsu_ledger_local_rest_headers($served, $result, $request, $server)
{
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return $served;
    }

    $origin = get_http_origin();
    $allowed_origins = [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:8081',
        'http://127.0.0.1:8081',
    ];

    if ($origin && in_array($origin, $allowed_origins, true)) {
        header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    }

    return $served;
}

function kobutsu_ledger_create_tables(): void
{
    global $wpdb;

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();
    $suppliers = kobutsu_ledger_table('suppliers');
    $supplier_sources = kobutsu_ledger_table('supplier_sources');
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

    dbDelta("CREATE TABLE $supplier_sources (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        item_id bigint(20) unsigned NULL,
        source_row_no int unsigned NOT NULL DEFAULT 0,
        sku varchar(120) NOT NULL,
        order_no varchar(120) NOT NULL DEFAULT '',
        account_name varchar(120) NOT NULL DEFAULT '',
        sold_at date NULL,
        sold_at_raw varchar(40) NOT NULL DEFAULT '',
        acquired_at date NULL,
        acquired_at_raw varchar(40) NOT NULL DEFAULT '',
        buyer_country varchar(120) NOT NULL DEFAULT '',
        mag varchar(80) NOT NULL DEFAULT '',
        sale_amount decimal(14,2) NOT NULL DEFAULT 0,
        sale_currency char(3) NOT NULL DEFAULT 'USD',
        purchase_price_jpy int NOT NULL DEFAULT 0,
        shipping_cost_jpy int NOT NULL DEFAULT 0,
        points varchar(80) NOT NULL DEFAULT '',
        notes text NULL,
        packer varchar(120) NOT NULL DEFAULT '',
        shipping_site varchar(120) NOT NULL DEFAULT '',
        actual_weight_g int NOT NULL DEFAULT 0,
        dimensional_weight_g int NOT NULL DEFAULT 0,
        package_length_cm decimal(8,2) NOT NULL DEFAULT 0,
        package_width_cm decimal(8,2) NOT NULL DEFAULT 0,
        package_height_cm decimal(8,2) NOT NULL DEFAULT 0,
        item_name text NOT NULL,
        supplier_name_raw varchar(191) NOT NULL DEFAULT '',
        first_mail_at_raw varchar(40) NOT NULL DEFAULT '',
        receipt_printed_at_raw varchar(40) NOT NULL DEFAULT '',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY sku (sku),
        KEY item_id (item_id),
        KEY order_no (order_no),
        KEY acquired_at (acquired_at),
        KEY supplier_name_raw (supplier_name_raw)
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
    register_rest_route('kobutsu/v1', '/supplier-sources', [
        [
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'kobutsu_ledger_get_supplier_sources',
            'permission_callback' => 'kobutsu_ledger_can_read',
        ],
        [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'kobutsu_ledger_create_supplier_source',
            'permission_callback' => 'kobutsu_ledger_can_write',
            'args' => kobutsu_ledger_rest_args(),
        ],
    ]);

    register_rest_route('kobutsu/v1', '/ec-sales', [
        'methods' => WP_REST_Server::READABLE,
        'callback' => 'kobutsu_ledger_get_ec_sales',
        'permission_callback' => 'kobutsu_ledger_can_read',
    ]);

    register_rest_route('kobutsu/v1', '/ec-sales/(?P<id>\d+)', [
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'kobutsu_ledger_update_ec_sale',
        'permission_callback' => 'kobutsu_ledger_can_write',
    ]);

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
    if (defined('WP_DEBUG') && WP_DEBUG) {
        return true;
    }

    return current_user_can('edit_posts');
}

function kobutsu_ledger_rest_args(): array
{
    return [
        'source_row_no' => ['required' => false, 'sanitize_callback' => 'absint'],
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
        'points' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'shipping_note' => ['required' => false, 'sanitize_callback' => 'sanitize_textarea_field'],
        'packer' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'shipping_site' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'actual_weight_g' => ['required' => false, 'sanitize_callback' => 'absint'],
        'dimensional_weight_g' => ['required' => false, 'sanitize_callback' => 'absint'],
        'package_length_cm' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'package_width_cm' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'package_height_cm' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'mag' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'first_mail_at' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'receipt_printed_at' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
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
    $response->set_param('id', $item_id);

    $item_response = kobutsu_ledger_get_item($response);
    if (is_wp_error($item_response)) {
        return $item_response;
    }

    return new WP_REST_Response($item_response->get_data(), 201);
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

    $data = [
        'source_row_no' => (int) ($request['source_row_no'] ?: 0),
        'sku' => $sku,
        'order_no' => $request['order_no'] ?: '',
        'account_name' => $request['account_name'] ?: '',
        'sold_at' => $request['sold_at'] ?: null,
        'sold_at_raw' => $request['sold_at'] ?: '',
        'acquired_at' => $request['acquired_at'] ?: null,
        'acquired_at_raw' => $request['acquired_at'] ?: '',
        'buyer_country' => $request['buyer_country'] ?: '',
        'mag' => $request['mag'] ?: '',
        'sale_amount' => $sale_money['amount'],
        'sale_currency' => $sale_money['currency'],
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
        'item_name' => $request['item_name'],
        'supplier_name_raw' => $request['acquired_from'] ?: '',
        'first_mail_at_raw' => $request['first_mail_at'] ?: '',
        'receipt_printed_at_raw' => $request['receipt_printed_at'] ?: '',
        'updated_at' => $now,
    ];
    $formats = [
        '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
        '%f', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%f',
        '%f', '%f', '%s', '%s', '%s', '%s', '%s',
    ];

    if ($item_id !== null) {
        $data = ['item_id' => $item_id] + $data;
        array_unshift($formats, '%d');
    }

    return (bool) $wpdb->replace(kobutsu_ledger_table('supplier_sources'), $data, $formats);
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
        'purchase_date' => $source['acquired_at'] ?: null,
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
    ])));

    $sale_data = [
        'item_id' => $item_id,
        'marketplace' => 'shopee',
        'account_name' => (string) ($source['account_name'] ?? ''),
        'order_no' => (string) ($source['order_no'] ?? ''),
        'sale_date' => $source['sold_at'] ?: null,
        'sale_amount' => (float) ($source['sale_amount'] ?? 0),
        'sale_currency' => (string) (($source['sale_currency'] ?? '') ?: 'USD'),
        'sale_amount_jpy' => strtoupper((string) ($source['sale_currency'] ?? '')) === 'JPY'
            ? (int) round((float) ($source['sale_amount'] ?? 0))
            : 0,
        'buyer_country' => (string) ($source['buyer_country'] ?? ''),
        'shipping_site' => (string) ($source['shipping_site'] ?? ''),
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
            ['%d', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%d', '%d', '%f', '%f', '%f', '%s', '%s'],
            ['%d']
        );

        return $sale_updated !== false;
    }

    $sale_inserted = $wpdb->insert(
        kobutsu_ledger_table('sales'),
        $sale_data,
        ['%d', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%d', '%d', '%f', '%f', '%f', '%s', '%s']
    );

    return (bool) $sale_inserted;
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

function kobutsu_ledger_register_admin_menu(): void
{
    add_menu_page(
        '古物台帳',
        '古物台帳',
        'edit_posts',
        'kobutsu-ledger',
        'kobutsu_ledger_render_admin_page',
        'dashicons-clipboard',
        26
    );

    add_menu_page(
        '仕入れ管理',
        '仕入れ管理',
        'edit_posts',
        'kobutsu-supplier-sources',
        'kobutsu_ledger_render_supplier_sources_admin_page',
        'dashicons-cart',
        27
    );

    add_menu_page(
        'EC販売',
        'EC販売',
        'edit_posts',
        'kobutsu-ec-sales',
        'kobutsu_ledger_render_ec_sales_admin_page',
        'dashicons-chart-line',
        28
    );
}

function kobutsu_ledger_render_admin_page(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('このページにアクセスする権限がありません。', 'kobutsu-ledger'));
    }

    $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
    $message = isset($_GET['kobutsu_message']) ? sanitize_key($_GET['kobutsu_message']) : '';
    $edit_item = $edit_id ? kobutsu_ledger_admin_get_item($edit_id) : null;
    $items = kobutsu_ledger_admin_get_items();

    ?>
    <div class="wrap kobutsu-ledger-admin">
        <h1>古物台帳</h1>
        <p>カスタムテーブルに保存された仕入れ・販売データを管理します。</p>

        <?php if ($message === 'saved') : ?>
            <div class="notice notice-success is-dismissible"><p>保存しました。</p></div>
        <?php elseif ($message === 'deleted') : ?>
            <div class="notice notice-success is-dismissible"><p>削除しました。</p></div>
        <?php elseif ($message === 'missing') : ?>
            <div class="notice notice-error is-dismissible"><p>対象データが見つかりませんでした。</p></div>
        <?php endif; ?>

        <?php if ($edit_item) : ?>
            <?php kobutsu_ledger_render_admin_edit_form($edit_item); ?>
        <?php endif; ?>

        <h2>登録データ</h2>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th style="width: 160px;">SKU</th>
                    <th>商品名</th>
                    <th style="width: 120px;">仕入日</th>
                    <th style="width: 140px;">仕入先</th>
                    <th style="width: 110px;">仕入金額</th>
                    <th style="width: 120px;">販売日</th>
                    <th style="width: 120px;">販売先</th>
                    <th style="width: 130px;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$items) : ?>
                    <tr><td colspan="9">登録データはありません。</td></tr>
                <?php endif; ?>
                <?php foreach ($items as $item) : ?>
                    <tr>
                        <td><?php echo esc_html((string) $item['id']); ?></td>
                        <td><strong><?php echo esc_html($item['sku']); ?></strong></td>
                        <td><?php echo esc_html($item['item_name']); ?></td>
                        <td><?php echo esc_html($item['purchase_date']); ?></td>
                        <td><?php echo esc_html($item['supplier_name_raw']); ?></td>
                        <td><?php echo esc_html(number_format((int) $item['purchase_price_jpy'])); ?>円</td>
                        <td><?php echo esc_html($item['sale_date']); ?></td>
                        <td><?php echo esc_html($item['marketplace']); ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'kobutsu-ledger', 'edit' => (int) $item['id']], admin_url('admin.php'))); ?>">編集</a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('このデータを削除しますか？');">
                                <?php wp_nonce_field('kobutsu_ledger_delete_' . (int) $item['id']); ?>
                                <input type="hidden" name="kobutsu_action" value="delete">
                                <input type="hidden" name="item_id" value="<?php echo esc_attr((string) $item['id']); ?>">
                                <button type="submit" class="button button-small button-link-delete">削除</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function kobutsu_ledger_handle_admin_action(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['kobutsu_action'])) {
        return;
    }

    $action = sanitize_key((string) wp_unslash($_POST['kobutsu_action']));
    $item_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;

    if (!$item_id) {
        kobutsu_ledger_admin_redirect(['kobutsu_message' => 'missing']);
    }

    if ($action === 'delete') {
        check_admin_referer('kobutsu_ledger_delete_' . $item_id);
        kobutsu_ledger_admin_delete_item($item_id);
        kobutsu_ledger_admin_redirect(['kobutsu_message' => 'deleted']);
    }

    if ($action === 'save') {
        check_admin_referer('kobutsu_ledger_save_' . $item_id);
        kobutsu_ledger_admin_update_item($item_id);
        kobutsu_ledger_admin_redirect(['kobutsu_message' => 'saved', 'edit' => $item_id]);
    }
}

function kobutsu_ledger_admin_redirect(array $args): void
{
    wp_safe_redirect(add_query_arg(array_merge(['page' => 'kobutsu-ledger'], $args), admin_url('admin.php')));
    exit;
}

function kobutsu_ledger_admin_get_items(): array
{
    global $wpdb;

    return $wpdb->get_results(
        "SELECT i.id, i.sku, i.item_name, p.purchase_date, p.supplier_name_raw,
            p.purchase_price_jpy, s.sale_date, s.marketplace
        FROM " . kobutsu_ledger_table('items') . " i
        LEFT JOIN " . kobutsu_ledger_table('purchases') . " p ON p.item_id = i.id
        LEFT JOIN " . kobutsu_ledger_table('sales') . " s ON s.item_id = i.id
        ORDER BY i.id DESC
        LIMIT 100",
        ARRAY_A
    ) ?: [];
}

function kobutsu_ledger_admin_get_item(int $item_id): ?array
{
    global $wpdb;

    $row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT i.id, i.sku, i.item_name, i.category, i.description, i.status,
                p.purchase_date, p.supplier_name_raw, p.purchase_price_jpy,
                p.seller_identification, p.source_order_no,
                s.sale_date, s.marketplace, s.account_name, s.sale_amount,
                s.sale_currency, s.buyer_country, s.shipping_site,
                s.actual_weight_g, s.dimensional_weight_g,
                s.package_length_cm, s.package_width_cm, s.package_height_cm,
                s.notes AS sale_notes
            FROM " . kobutsu_ledger_table('items') . " i
            LEFT JOIN " . kobutsu_ledger_table('purchases') . " p ON p.item_id = i.id
            LEFT JOIN " . kobutsu_ledger_table('sales') . " s ON s.item_id = i.id
            WHERE i.id = %d",
            $item_id
        ),
        ARRAY_A
    );

    return $row ?: null;
}

function kobutsu_ledger_render_admin_edit_form(array $item): void
{
    ?>
    <h2>編集: <?php echo esc_html($item['sku']); ?></h2>
    <form method="post" class="kobutsu-ledger-edit-form">
        <?php wp_nonce_field('kobutsu_ledger_save_' . (int) $item['id']); ?>
        <input type="hidden" name="kobutsu_action" value="save">
        <input type="hidden" name="item_id" value="<?php echo esc_attr((string) $item['id']); ?>">

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="kobutsu_sku">SKU</label></th>
                    <td><input id="kobutsu_sku" name="sku" type="text" class="regular-text" value="<?php echo esc_attr($item['sku']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_item_name">商品名</label></th>
                    <td><input id="kobutsu_item_name" name="item_name" type="text" class="large-text" value="<?php echo esc_attr($item['item_name']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_category">品目</label></th>
                    <td><input id="kobutsu_category" name="category" type="text" class="regular-text" value="<?php echo esc_attr($item['category']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_status">状態</label></th>
                    <td>
                        <select id="kobutsu_status" name="status">
                            <?php foreach (['in_stock' => '在庫', 'sold' => '売却', 'returned' => '返品', 'disposed' => '処分'] as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($item['status'], $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_purchase_date">仕入日</label></th>
                    <td><input id="kobutsu_purchase_date" name="purchase_date" type="date" value="<?php echo esc_attr($item['purchase_date']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_supplier">仕入先</label></th>
                    <td><input id="kobutsu_supplier" name="supplier_name_raw" type="text" class="regular-text" value="<?php echo esc_attr($item['supplier_name_raw']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_purchase_price">仕入金額</label></th>
                    <td><input id="kobutsu_purchase_price" name="purchase_price_jpy" type="number" min="0" value="<?php echo esc_attr((string) $item['purchase_price_jpy']); ?>"> 円</td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_order_no">Order no.</label></th>
                    <td><input id="kobutsu_order_no" name="source_order_no" type="text" class="regular-text" value="<?php echo esc_attr($item['source_order_no']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_seller_identification">仕入れ確認</label></th>
                    <td><input id="kobutsu_seller_identification" name="seller_identification" type="text" class="regular-text" value="<?php echo esc_attr($item['seller_identification']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_sale_date">販売日</label></th>
                    <td><input id="kobutsu_sale_date" name="sale_date" type="date" value="<?php echo esc_attr($item['sale_date']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_marketplace">販売先</label></th>
                    <td><input id="kobutsu_marketplace" name="marketplace" type="text" class="regular-text" value="<?php echo esc_attr($item['marketplace']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_account_name">アカウント</label></th>
                    <td><input id="kobutsu_account_name" name="account_name" type="text" class="regular-text" value="<?php echo esc_attr($item['account_name']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_sale_amount">販売額</label></th>
                    <td>
                        <input id="kobutsu_sale_amount" name="sale_amount" type="number" step="0.01" min="0" value="<?php echo esc_attr((string) $item['sale_amount']); ?>">
                        <input name="sale_currency" type="text" maxlength="3" size="4" value="<?php echo esc_attr($item['sale_currency']); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_buyer_country">国</label></th>
                    <td><input id="kobutsu_buyer_country" name="buyer_country" type="text" class="regular-text" value="<?php echo esc_attr($item['buyer_country']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_shipping_site">発送サイト</label></th>
                    <td><input id="kobutsu_shipping_site" name="shipping_site" type="text" class="regular-text" value="<?php echo esc_attr($item['shipping_site']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row">荷姿</th>
                    <td>
                        <label>実重g <input name="actual_weight_g" type="number" min="0" value="<?php echo esc_attr((string) $item['actual_weight_g']); ?>"></label>
                        <label>体積重g <input name="dimensional_weight_g" type="number" min="0" value="<?php echo esc_attr((string) $item['dimensional_weight_g']); ?>"></label>
                        <label>縦cm <input name="package_length_cm" type="number" step="0.01" min="0" value="<?php echo esc_attr((string) $item['package_length_cm']); ?>"></label>
                        <label>横cm <input name="package_width_cm" type="number" step="0.01" min="0" value="<?php echo esc_attr((string) $item['package_width_cm']); ?>"></label>
                        <label>高さcm <input name="package_height_cm" type="number" step="0.01" min="0" value="<?php echo esc_attr((string) $item['package_height_cm']); ?>"></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_sale_notes">備考</label></th>
                    <td><textarea id="kobutsu_sale_notes" name="sale_notes" class="large-text" rows="4"><?php echo esc_textarea($item['sale_notes']); ?></textarea></td>
                </tr>
            </tbody>
        </table>

        <?php submit_button('保存'); ?>
    </form>
    <hr>
    <?php
}

function kobutsu_ledger_admin_update_item(int $item_id): void
{
    global $wpdb;

    $supplier_name = sanitize_text_field((string) wp_unslash($_POST['supplier_name_raw'] ?? ''));
    $supplier_id = $supplier_name !== '' ? kobutsu_ledger_ensure_supplier($supplier_name) : null;

    $wpdb->query('START TRANSACTION');

    $wpdb->update(kobutsu_ledger_table('items'), [
        'sku' => sanitize_text_field((string) wp_unslash($_POST['sku'] ?? '')),
        'item_name' => sanitize_text_field((string) wp_unslash($_POST['item_name'] ?? '')),
        'category' => sanitize_text_field((string) wp_unslash($_POST['category'] ?? '')),
        'status' => sanitize_key((string) wp_unslash($_POST['status'] ?? 'in_stock')),
        'updated_at' => current_time('mysql'),
    ], ['id' => $item_id], ['%s', '%s', '%s', '%s', '%s'], ['%d']);

    $wpdb->update(kobutsu_ledger_table('purchases'), [
        'supplier_id' => $supplier_id,
        'purchase_date' => kobutsu_ledger_admin_date_or_null($_POST['purchase_date'] ?? ''),
        'supplier_name_raw' => $supplier_name,
        'purchase_price_jpy' => absint($_POST['purchase_price_jpy'] ?? 0),
        'seller_identification' => sanitize_text_field((string) wp_unslash($_POST['seller_identification'] ?? '')),
        'source_order_no' => sanitize_text_field((string) wp_unslash($_POST['source_order_no'] ?? '')),
        'updated_at' => current_time('mysql'),
    ], ['item_id' => $item_id], ['%d', '%s', '%s', '%d', '%s', '%s', '%s'], ['%d']);

    $sales_exists = (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM ' . kobutsu_ledger_table('sales') . ' WHERE item_id = %d',
        $item_id
    ));

    $sale_data = [
        'item_id' => $item_id,
        'marketplace' => sanitize_text_field((string) wp_unslash($_POST['marketplace'] ?? '')),
        'account_name' => sanitize_text_field((string) wp_unslash($_POST['account_name'] ?? '')),
        'order_no' => sanitize_text_field((string) wp_unslash($_POST['source_order_no'] ?? '')),
        'sale_date' => kobutsu_ledger_admin_date_or_null($_POST['sale_date'] ?? ''),
        'sale_amount' => (float) ($_POST['sale_amount'] ?? 0),
        'sale_currency' => strtoupper(substr(sanitize_text_field((string) wp_unslash($_POST['sale_currency'] ?? 'USD')), 0, 3)),
        'buyer_country' => sanitize_text_field((string) wp_unslash($_POST['buyer_country'] ?? '')),
        'shipping_site' => sanitize_text_field((string) wp_unslash($_POST['shipping_site'] ?? '')),
        'actual_weight_g' => absint($_POST['actual_weight_g'] ?? 0),
        'dimensional_weight_g' => absint($_POST['dimensional_weight_g'] ?? 0),
        'package_length_cm' => (float) ($_POST['package_length_cm'] ?? 0),
        'package_width_cm' => (float) ($_POST['package_width_cm'] ?? 0),
        'package_height_cm' => (float) ($_POST['package_height_cm'] ?? 0),
        'notes' => sanitize_textarea_field((string) wp_unslash($_POST['sale_notes'] ?? '')),
        'updated_at' => current_time('mysql'),
    ];

    if ($sales_exists) {
        $wpdb->update(
            kobutsu_ledger_table('sales'),
            $sale_data,
            ['item_id' => $item_id],
            ['%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%f', '%s', '%s'],
            ['%d']
        );
    } else {
        $wpdb->insert(
            kobutsu_ledger_table('sales'),
            $sale_data,
            ['%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%d', '%d', '%f', '%f', '%f', '%s', '%s']
        );
    }

    $wpdb->query('COMMIT');
}

function kobutsu_ledger_admin_delete_item(int $item_id): void
{
    global $wpdb;

    $wpdb->query('START TRANSACTION');
    $wpdb->delete(kobutsu_ledger_table('sales'), ['item_id' => $item_id], ['%d']);
    $wpdb->delete(kobutsu_ledger_table('purchases'), ['item_id' => $item_id], ['%d']);
    $wpdb->delete(kobutsu_ledger_table('items'), ['id' => $item_id], ['%d']);
    $wpdb->query('COMMIT');
}

function kobutsu_ledger_admin_date_or_null($value): ?string
{
    $date = sanitize_text_field((string) wp_unslash($value));

    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null;
}
