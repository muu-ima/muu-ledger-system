<?php

if (!defined('ABSPATH')) {
    exit;
}

function kobutsu_ledger_register_hooks(string $plugin_file): void
{
    register_activation_hook($plugin_file, 'kobutsu_ledger_activate');
    register_deactivation_hook($plugin_file, 'kobutsu_ledger_unschedule_exchange_rate_fetch');
    add_action('plugins_loaded', 'kobutsu_ledger_maybe_upgrade');
    add_action('rest_api_init', 'kobutsu_ledger_register_routes');
    add_action('admin_menu', 'kobutsu_ledger_register_admin_menu');
    add_action('admin_init', 'kobutsu_ledger_handle_admin_action');
    add_action('admin_init', 'kobutsu_ledger_handle_supplier_sources_admin_action');
    add_action('admin_init', 'kobutsu_ledger_handle_ec_sales_admin_action');
    add_filter('rest_pre_serve_request', 'kobutsu_ledger_local_rest_headers', 10, 4);
}
