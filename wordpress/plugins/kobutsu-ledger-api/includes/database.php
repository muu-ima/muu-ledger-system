<?php

if (!defined('ABSPATH')) {
    exit;
}

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
    $allowed_origins = kobutsu_ledger_allowed_rest_origins();

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
        purchased_flag varchar(20) NOT NULL DEFAULT '',
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
        size_memo varchar(40) NOT NULL DEFAULT '',
        shipping_chat_at_raw varchar(40) NOT NULL DEFAULT '',
        item_name text NOT NULL,
        supplier_name_raw varchar(191) NOT NULL DEFAULT '',
        first_mail_at_raw varchar(40) NOT NULL DEFAULT '',
        receipt_printed_at_raw varchar(40) NOT NULL DEFAULT '',
        domestic_tracking_no varchar(191) NOT NULL DEFAULT '',
        sls_tracking_no varchar(191) NOT NULL DEFAULT '',
        yamato_slip_flag varchar(20) NOT NULL DEFAULT '',
        balance_checked_flag varchar(20) NOT NULL DEFAULT '',
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
