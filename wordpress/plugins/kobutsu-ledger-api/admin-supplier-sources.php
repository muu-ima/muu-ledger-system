<?php

if (!defined('ABSPATH')) {
    exit;
}

function kobutsu_ledger_get_supplier_sources(WP_REST_Request $request): WP_REST_Response
{
    return rest_ensure_response(array_map(
        'kobutsu_ledger_format_supplier_source_row',
        kobutsu_ledger_admin_get_supplier_sources()
    ));
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

function kobutsu_ledger_render_supplier_sources_admin_page(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('このページにアクセスする権限がありません。', 'kobutsu-ledger'));
    }

    $message = isset($_GET['kobutsu_message']) ? sanitize_key($_GET['kobutsu_message']) : '';
    $rows = kobutsu_ledger_admin_get_supplier_sources();

    ?>
    <div class="wrap kobutsu-ledger-admin">
        <h1>仕入れ管理</h1>
        <p>仕入元データをカスタムテーブルとして保存した原票ビューです。</p>

        <?php if ($message === 'deleted') : ?>
            <div class="notice notice-success is-dismissible"><p>削除しました。</p></div>
        <?php elseif ($message === 'missing') : ?>
            <div class="notice notice-error is-dismissible"><p>対象データが見つかりませんでした。</p></div>
        <?php endif; ?>

        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 70px;">No</th>
                    <th style="width: 160px;">SKU</th>
                    <th style="width: 150px;">Order no.</th>
                    <th style="width: 110px;">アカウント</th>
                    <th style="width: 95px;">販売日</th>
                    <th style="width: 95px;">仕入日</th>
                    <th style="width: 100px;">国</th>
                    <th style="width: 100px;">販売額</th>
                    <th style="width: 100px;">仕入れ</th>
                    <th style="width: 100px;">送料</th>
                    <th>商品名</th>
                    <th style="width: 130px;">仕入先</th>
                    <th style="width: 90px;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows) : ?>
                    <tr><td colspan="13">仕入元データはありません。</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td><?php echo esc_html((string) ($row['source_row_no'] ?: $row['id'])); ?></td>
                        <td><strong><?php echo esc_html($row['sku']); ?></strong></td>
                        <td><?php echo esc_html($row['order_no']); ?></td>
                        <td><?php echo esc_html($row['account_name']); ?></td>
                        <td><?php echo esc_html($row['sold_at'] ?: $row['sold_at_raw']); ?></td>
                        <td><?php echo esc_html($row['acquired_at'] ?: $row['acquired_at_raw']); ?></td>
                        <td><?php echo esc_html($row['buyer_country']); ?></td>
                        <td><?php echo esc_html($row['sale_currency'] . ' ' . number_format((float) $row['sale_amount'], 2)); ?></td>
                        <td><?php echo esc_html(number_format((int) $row['purchase_price_jpy'])); ?>円</td>
                        <td><?php echo esc_html(number_format((int) $row['shipping_cost_jpy'])); ?>円</td>
                        <td><?php echo esc_html($row['item_name']); ?></td>
                        <td><?php echo esc_html($row['supplier_name_raw']); ?></td>
                        <td>
                            <form method="post" onsubmit="return confirm('この仕入元データを削除しますか？');">
                                <?php wp_nonce_field('kobutsu_supplier_source_delete_' . (int) $row['id']); ?>
                                <input type="hidden" name="kobutsu_source_action" value="delete">
                                <input type="hidden" name="source_id" value="<?php echo esc_attr((string) $row['id']); ?>">
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

function kobutsu_ledger_admin_get_supplier_sources(): array
{
    global $wpdb;

    return $wpdb->get_results(
        kobutsu_ledger_supplier_sources_select_sql() . '
        ORDER BY COALESCE(acquired_at, created_at) DESC, id DESC
        LIMIT 100',
        ARRAY_A
    ) ?: [];
}

function kobutsu_ledger_admin_get_supplier_source_by_sku(string $sku): ?array
{
    global $wpdb;

    $row = $wpdb->get_row(
        $wpdb->prepare(
            kobutsu_ledger_supplier_sources_select_sql() . ' WHERE sku = %s',
            $sku
        ),
        ARRAY_A
    );

    return $row ?: null;
}

function kobutsu_ledger_supplier_sources_select_sql(): string
{
    return 'SELECT id, item_id, source_row_no, sku, order_no, account_name, sold_at, sold_at_raw,
        acquired_at, acquired_at_raw, buyer_country, mag, sale_amount, sale_currency,
        purchase_price_jpy, shipping_cost_jpy, points, notes, packer, shipping_site,
        actual_weight_g, dimensional_weight_g, package_length_cm, package_width_cm,
        package_height_cm, shipping_chat_at_raw, item_name, supplier_name_raw, first_mail_at_raw,
        receipt_printed_at_raw, domestic_tracking_no, sls_tracking_no, yamato_slip_flag,
        balance_checked_flag
    FROM ' . kobutsu_ledger_table('supplier_sources');
}

function kobutsu_ledger_format_supplier_source_row(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'item_id' => isset($row['item_id']) ? (int) $row['item_id'] : 0,
        'source_row_no' => (int) $row['source_row_no'],
        'sku' => (string) $row['sku'],
        'order_no' => (string) $row['order_no'],
        'account_name' => (string) $row['account_name'],
        'sold_at' => (string) ($row['sold_at'] ?? ''),
        'sold_at_raw' => (string) ($row['sold_at_raw'] ?? ''),
        'acquired_at' => (string) ($row['acquired_at'] ?? ''),
        'acquired_at_raw' => (string) ($row['acquired_at_raw'] ?? ''),
        'buyer_country' => (string) $row['buyer_country'],
        'mag' => (string) $row['mag'],
        'sale_amount' => (float) $row['sale_amount'],
        'sale_currency' => (string) $row['sale_currency'],
        'purchase_price_jpy' => (int) $row['purchase_price_jpy'],
        'shipping_cost_jpy' => (int) $row['shipping_cost_jpy'],
        'points' => (string) $row['points'],
        'notes' => (string) ($row['notes'] ?? ''),
        'packer' => (string) $row['packer'],
        'shipping_site' => (string) $row['shipping_site'],
        'actual_weight_g' => (int) $row['actual_weight_g'],
        'dimensional_weight_g' => (int) $row['dimensional_weight_g'],
        'package_length_cm' => (float) $row['package_length_cm'],
        'package_width_cm' => (float) $row['package_width_cm'],
        'package_height_cm' => (float) $row['package_height_cm'],
        'shipping_chat_at_raw' => (string) ($row['shipping_chat_at_raw'] ?? ''),
        'item_name' => (string) $row['item_name'],
        'supplier_name_raw' => (string) $row['supplier_name_raw'],
        'first_mail_at_raw' => (string) $row['first_mail_at_raw'],
        'receipt_printed_at_raw' => (string) $row['receipt_printed_at_raw'],
        'domestic_tracking_no' => (string) ($row['domestic_tracking_no'] ?? ''),
        'sls_tracking_no' => (string) ($row['sls_tracking_no'] ?? ''),
        'yamato_slip_flag' => (string) ($row['yamato_slip_flag'] ?? ''),
        'balance_checked_flag' => (string) ($row['balance_checked_flag'] ?? ''),
    ];
}

function kobutsu_ledger_admin_delete_supplier_source(int $source_id): void
{
    global $wpdb;

    $wpdb->delete(kobutsu_ledger_table('supplier_sources'), ['id' => $source_id], ['%d']);
}
