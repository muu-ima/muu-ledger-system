<?php

if (!defined('ABSPATH')) {
    exit;
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

    kobutsu_ledger_register_launch_settings_menu();
}
