<?php

if (!defined('ABSPATH')) {
    exit;
}

function kobutsu_ledger_create_supplier_source(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;

    $sku = $request['sku'] ?: $request['management_no'];
    if (!$sku) {
        return new WP_Error('kobutsu_missing_sku', 'SKU または管理番号は必須です。', ['status' => 400]);
    }

    $purchase_price = kobutsu_ledger_parse_money($request['purchase_price']);
    $sale_money = kobutsu_ledger_parse_money($request['sale_amount'] ?: $request['sale_price']);
    $shipping_cost = kobutsu_ledger_parse_money($request['shipping_cost']);

    $wpdb->query('START TRANSACTION');

    $saved = kobutsu_ledger_save_supplier_source(
        null,
        $request,
        $purchase_price,
        $sale_money,
        $shipping_cost
    );

    if (!$saved) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('kobutsu_insert_failed', '仕入元データを登録できませんでした。', ['status' => 500]);
    }

    $row = kobutsu_ledger_admin_get_supplier_source_by_sku((string) $sku);
    if (!$row) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('kobutsu_not_found', '仕入元データが見つかりません。', ['status' => 500]);
    }

    if (!kobutsu_ledger_sync_supplier_source_dependents($row)) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('kobutsu_sync_failed', '仕入元データの関連テーブル同期に失敗しました。', ['status' => 500]);
    }

    $wpdb->query('COMMIT');

    $row = kobutsu_ledger_admin_get_supplier_source_by_sku((string) $sku);
    if (!$row) {
        return new WP_Error('kobutsu_not_found', '仕入元データが見つかりません。', ['status' => 500]);
    }

    return new WP_REST_Response(kobutsu_ledger_format_supplier_source_row($row), 201);
}

function kobutsu_ledger_update_supplier_source(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;

    $source_id = (int) $request['id'];
    $current = kobutsu_ledger_admin_get_supplier_source_by_id($source_id);
    if (!$current) {
        return new WP_Error('kobutsu_not_found', '仕入元データが見つかりません。', ['status' => 404]);
    }

    $param_or_current = static function (string $key, $fallback) use ($request) {
        return $request->has_param($key) ? $request->get_param($key) : $fallback;
    };
    $merged = [
        'source_row_no' => $current['source_row_no'],
        'sku' => (string) $param_or_current('sku', $current['sku']),
        'order_no' => (string) $param_or_current('order_no', $current['order_no']),
        'account_name' => (string) $param_or_current('account_name', $current['account_name']),
        'sold_at' => (string) $param_or_current('sold_at', $current['sold_at_raw'] ?: $current['sold_at']),
        'acquired_at' => (string) $param_or_current('acquired_at', $current['acquired_at_raw'] ?: $current['acquired_at']),
        'buyer_country' => (string) $param_or_current('buyer_country', $current['buyer_country']),
        'mag' => (string) $param_or_current('mag', $current['mag']),
        'sale_amount' => (string) $param_or_current('sale_amount', kobutsu_ledger_admin_format_supplier_source_sale_amount($current)),
        'purchased_flag' => (string) $param_or_current('purchased_flag', $current['purchased_flag']),
        'purchase_price' => (string) $param_or_current('purchase_price', '¥' . number_format((int) $current['purchase_price_jpy'])),
        'shipping_cost' => (string) $param_or_current('shipping_cost', '¥' . number_format((int) $current['shipping_cost_jpy'])),
        'points' => (string) $param_or_current('points', $current['points']),
        'shipping_note' => (string) $param_or_current('shipping_note', $current['notes']),
        'packer' => (string) $param_or_current('packer', $current['packer']),
        'shipping_site' => (string) $param_or_current('shipping_site', $current['shipping_site']),
        'actual_weight_g' => (string) $param_or_current('actual_weight_g', $current['actual_weight_g']),
        'dimensional_weight_g' => (string) $param_or_current('dimensional_weight_g', $current['dimensional_weight_g']),
        'package_length_cm' => (string) $param_or_current('package_length_cm', $current['package_length_cm']),
        'package_width_cm' => (string) $param_or_current('package_width_cm', $current['package_width_cm']),
        'package_height_cm' => (string) $param_or_current('package_height_cm', $current['package_height_cm']),
        'size_memo' => (string) $param_or_current('size_memo', $current['size_memo']),
        'shipping_chat_at' => (string) $param_or_current('shipping_chat_at', $current['shipping_chat_at_raw']),
        'item_name' => (string) $param_or_current('item_name', $current['item_name']),
        'acquired_from' => (string) $param_or_current('acquired_from', $current['supplier_name_raw']),
        'first_mail_at' => (string) $param_or_current('first_mail_at', $current['first_mail_at_raw']),
        'receipt_printed_at' => (string) $param_or_current('receipt_printed_at', $current['receipt_printed_at_raw']),
        'domestic_tracking_no' => (string) $param_or_current('domestic_tracking_no', $current['domestic_tracking_no']),
        'sls_tracking_no' => (string) $param_or_current('sls_tracking_no', $current['sls_tracking_no']),
        'yamato_slip_flag' => (string) $param_or_current('yamato_slip_flag', $current['yamato_slip_flag']),
        'balance_checked_flag' => (string) $param_or_current('balance_checked_flag', $current['balance_checked_flag']),
        'status' => (string) $param_or_current('status', !empty($current['sold_at']) ? 'sold' : 'in_stock'),
    ];

    $purchase_price = kobutsu_ledger_parse_money($merged['purchase_price']);
    $sale_money = kobutsu_ledger_parse_money($merged['sale_amount']);
    $shipping_cost = kobutsu_ledger_parse_money($merged['shipping_cost']);
    $update_request = new WP_REST_Request('POST', '/kobutsu/v1/supplier-sources/' . $source_id);
    foreach ($merged as $key => $value) {
        $update_request->set_param($key, $value);
    }

    $wpdb->query('START TRANSACTION');

    $saved = kobutsu_ledger_save_supplier_source(
        !empty($current['item_id']) ? (int) $current['item_id'] : null,
        $update_request,
        $purchase_price,
        $sale_money,
        $shipping_cost
    );
    if (!$saved) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('kobutsu_update_failed', '仕入元データを更新できませんでした。', ['status' => 500]);
    }

    $updated_row = kobutsu_ledger_admin_get_supplier_source_by_sku((string) $merged['sku']);
    if (!$updated_row) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('kobutsu_not_found', '更新後の仕入元データが見つかりません。', ['status' => 500]);
    }

    if (!kobutsu_ledger_sync_supplier_source_dependents($updated_row)) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('kobutsu_sync_failed', '関連テーブル同期に失敗しました。', ['status' => 500]);
    }

    $wpdb->query('COMMIT');

    return rest_ensure_response(kobutsu_ledger_format_supplier_source_row($updated_row));
}

function kobutsu_ledger_handle_supplier_sources_admin_action(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['kobutsu_source_action'])) {
        return;
    }

    $action = sanitize_key((string) wp_unslash($_POST['kobutsu_source_action']));
    $source_id = isset($_POST['source_id']) ? absint($_POST['source_id']) : 0;

    if (!$source_id) {
        kobutsu_ledger_supplier_sources_admin_redirect(['kobutsu_message' => 'missing']);
    }

    if ($action === 'delete') {
        check_admin_referer('kobutsu_supplier_source_delete_' . $source_id);
        kobutsu_ledger_admin_delete_supplier_source($source_id);
        kobutsu_ledger_supplier_sources_admin_redirect(['kobutsu_message' => 'deleted']);
    }
}

function kobutsu_ledger_supplier_sources_admin_redirect(array $args): void
{
    wp_safe_redirect(add_query_arg(array_merge(['page' => 'kobutsu-supplier-sources'], $args), admin_url('admin.php')));
    exit;
}

function kobutsu_ledger_admin_delete_supplier_source(int $source_id): void
{
    global $wpdb;

    $wpdb->delete(kobutsu_ledger_table('supplier_sources'), ['id' => $source_id], ['%d']);
}
