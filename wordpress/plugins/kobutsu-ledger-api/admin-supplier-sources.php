<?php

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/supplier-sources-admin-view.php';
require_once __DIR__ . '/includes/supplier-sources-admin-actions.php';
require_once __DIR__ . '/includes/supplier-sources-admin-helpers.php';
require_once __DIR__ . '/includes/supplier-sources-admin-query.php';

function kobutsu_ledger_get_supplier_sources(WP_REST_Request $request): WP_REST_Response
{
    return rest_ensure_response(array_map(
        'kobutsu_ledger_format_supplier_source_row',
        kobutsu_ledger_admin_get_supplier_sources()
    ));
}
