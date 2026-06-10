<?php

if (!defined('ABSPATH')) {
    exit;
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

    register_rest_route('kobutsu/v1', '/supplier-sources/(?P<id>\d+)', [
        'methods' => WP_REST_Server::EDITABLE,
        'callback' => 'kobutsu_ledger_update_supplier_source',
        'permission_callback' => 'kobutsu_ledger_can_write',
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
        [
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'kobutsu_ledger_get_item',
            'permission_callback' => 'kobutsu_ledger_can_read',
        ],
        [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => 'kobutsu_ledger_update_item',
            'permission_callback' => 'kobutsu_ledger_can_write',
        ],
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
        'accessories' => ['required' => false, 'sanitize_callback' => 'sanitize_textarea_field'],
        'condition_label' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'photo_url' => ['required' => false, 'sanitize_callback' => 'esc_url_raw'],
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
        'purchased_flag' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'points' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'shipping_note' => ['required' => false, 'sanitize_callback' => 'sanitize_textarea_field'],
        'packer' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'shipping_site' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'actual_weight_g' => ['required' => false, 'sanitize_callback' => 'absint'],
        'dimensional_weight_g' => ['required' => false, 'sanitize_callback' => 'absint'],
        'package_length_cm' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'package_width_cm' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'package_height_cm' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'size_memo' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'shipping_chat_at' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'mag' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'first_mail_at' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'receipt_printed_at' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'domestic_tracking_no' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'sls_tracking_no' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'yamato_slip_flag' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'balance_checked_flag' => ['required' => false, 'sanitize_callback' => 'sanitize_text_field'],
        'status' => ['required' => false, 'sanitize_callback' => 'sanitize_key'],
    ];
}
