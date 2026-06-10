<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/ec-sales-admin-view.php';
require_once __DIR__ . '/includes/ec-sales-admin-actions.php';
require_once __DIR__ . '/includes/ec-sales-admin-helpers.php';
require_once __DIR__ . '/includes/ec-sales-admin-query.php';

function kobutsu_ledger_get_ec_sales(WP_REST_Request $request): WP_REST_Response
{
    $rows = kobutsu_ledger_admin_get_ec_sales();

    return rest_ensure_response(array_map('kobutsu_ledger_format_ec_sale_row', $rows));
}
