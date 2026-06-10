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
require_once __DIR__ . '/admin-launch-settings.php';
require_once __DIR__ . '/includes/admin-menu.php';
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/rest.php';
require_once __DIR__ . '/includes/sync/supplier-sources.php';

const KOBUTSU_LEDGER_DB_VERSION = '0.3.2';

kobutsu_ledger_register_hooks(__FILE__);


function kobutsu_ledger_get_items(WP_REST_Request $request): WP_REST_Response
{
    global $wpdb;

    $items = $wpdb->get_results(
        "SELECT i.id, i.sku, i.category, i.item_name, i.accessories, i.condition_label, i.description, i.photo_url, i.status,
            p.purchase_date, p.supplier_name_raw, p.seller_identification, p.purchase_price_jpy, p.source_order_no,
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
            "SELECT i.id, i.sku, i.category, i.item_name, i.accessories, i.condition_label, i.description, i.photo_url, i.status,
                p.purchase_date, p.supplier_name_raw, p.seller_identification, p.purchase_price_jpy, p.source_order_no,
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
        'accessories' => $request['accessories'] ?: '',
        'condition_label' => $request['condition_label'] ?: '',
        'description' => $request['description'] ?? '',
        'photo_url' => $request['photo_url'] ?: '',
        'status' => $request['status'] ?: 'in_stock',
    ], ['%s', '%s', '%s', '%s', '%s', '%s', '%s']);

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
        'purchase_date' => kobutsu_ledger_date_or_null($request['acquired_at'] ?? ''),
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

function kobutsu_ledger_update_item(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    global $wpdb;

    $item_id = (int) $request['id'];
    $current = kobutsu_ledger_admin_get_item($item_id);
    if (!$current) {
        return new WP_Error('kobutsu_not_found', '台帳データが見つかりません。', ['status' => 404]);
    }

    $supplier_name = trim((string) ($request['acquired_from'] ?? $current['supplier_name_raw'] ?? ''));
    $supplier_id = $supplier_name !== '' ? kobutsu_ledger_ensure_supplier($supplier_name) : null;
    $purchase_exists = (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM ' . kobutsu_ledger_table('purchases') . ' WHERE item_id = %d',
        $item_id
    ));
    $sales_exists = (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM ' . kobutsu_ledger_table('sales') . ' WHERE item_id = %d',
        $item_id
    ));
    $param_or_current = static function (string $key, $fallback) use ($request) {
        return $request->has_param($key) ? $request->get_param($key) : $fallback;
    };

    $purchase_price = kobutsu_ledger_parse_money(
        $param_or_current('purchase_price', $current['purchase_price_jpy'] ?? 0)
    );
    $sale_money = kobutsu_ledger_parse_money(
        $param_or_current('sale_amount', $current['sale_amount'] ?? 0)
    );

    $wpdb->query('START TRANSACTION');

    $updated = $wpdb->update(kobutsu_ledger_table('items'), [
        'sku' => (string) $param_or_current('sku', $current['sku']),
        'item_name' => (string) $param_or_current('item_name', $current['item_name']),
        'category' => (string) $param_or_current('category', $current['category']),
        'accessories' => (string) $param_or_current('accessories', $current['accessories'] ?? ''),
        'condition_label' => (string) $param_or_current('condition_label', $current['condition_label'] ?? ''),
        'description' => (string) $param_or_current('description', $current['description'] ?? ''),
        'photo_url' => (string) $param_or_current('photo_url', $current['photo_url'] ?? ''),
        'status' => (string) $param_or_current('status', $current['status']),
        'updated_at' => current_time('mysql'),
    ], ['id' => $item_id], ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s'], ['%d']);
    if ($updated === false) {
        $wpdb->query('ROLLBACK');
        return new WP_Error('kobutsu_update_failed', '商品データを更新できませんでした。', ['status' => 500]);
    }

    $purchase_data = [
        'item_id' => $item_id,
        'supplier_id' => $supplier_id,
        'purchase_date' => kobutsu_ledger_date_or_null((string) $param_or_current('acquired_at', $current['purchase_date'] ?? '')),
        'supplier_name_raw' => $supplier_name,
        'purchase_price_jpy' => $purchase_price['amount_jpy'],
        'seller_identification' => (string) $param_or_current('seller_identification', $current['seller_identification'] ?? ''),
        'source_order_no' => (string) $param_or_current('order_no', $current['source_order_no'] ?? ''),
        'updated_at' => current_time('mysql'),
    ];
    if ($purchase_exists) {
        $updated = $wpdb->update(
            kobutsu_ledger_table('purchases'),
            $purchase_data,
            ['item_id' => $item_id],
            ['%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s'],
            ['%d']
        );
        if ($updated === false) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('kobutsu_update_failed', '仕入データを更新できませんでした。', ['status' => 500]);
        }
    } else {
        $inserted = $wpdb->insert(
            kobutsu_ledger_table('purchases'),
            $purchase_data + ['created_at' => current_time('mysql')],
            ['%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );
        if (!$inserted) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('kobutsu_insert_failed', '仕入データを登録できませんでした。', ['status' => 500]);
        }
    }

    $marketplace = (string) $param_or_current('sold_to', $current['marketplace'] ?? '');
    $order_no = (string) $param_or_current('order_no', $current['source_order_no'] ?? '');
    $sale_date = kobutsu_ledger_date_or_null((string) $param_or_current('sold_at', $current['sale_date'] ?? ''));
    if ($sales_exists || $marketplace !== '' || $order_no !== '' || $sale_date !== null) {
        $sale_data = [
            'item_id' => $item_id,
            'marketplace' => $marketplace,
            'account_name' => (string) $param_or_current('account_name', $current['account_name'] ?? ''),
            'order_no' => $order_no,
            'sale_date' => $sale_date,
            'sale_amount' => $sale_money['amount'],
            'sale_currency' => $sale_money['currency'],
            'sale_amount_jpy' => $sale_money['amount_jpy'],
            'buyer_country' => (string) $param_or_current('buyer_country', $current['buyer_country'] ?? ''),
            'shipping_site' => (string) $param_or_current('shipping_site', $current['shipping_site'] ?? ''),
            'actual_weight_g' => (int) $param_or_current('actual_weight_g', $current['actual_weight_g'] ?? 0),
            'dimensional_weight_g' => (int) $param_or_current('dimensional_weight_g', $current['dimensional_weight_g'] ?? 0),
            'package_length_cm' => (float) $param_or_current('package_length_cm', $current['package_length_cm'] ?? 0),
            'package_width_cm' => (float) $param_or_current('package_width_cm', $current['package_width_cm'] ?? 0),
            'package_height_cm' => (float) $param_or_current('package_height_cm', $current['package_height_cm'] ?? 0),
            'notes' => (string) $param_or_current('description', $current['sale_notes'] ?? ''),
            'updated_at' => current_time('mysql'),
        ];
        if ($sales_exists) {
            $updated = $wpdb->update(
                kobutsu_ledger_table('sales'),
                $sale_data,
                ['item_id' => $item_id],
                ['%d', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%d', '%d', '%f', '%f', '%f', '%s', '%s'],
                ['%d']
            );
            if ($updated === false) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('kobutsu_update_failed', '販売データを更新できませんでした。', ['status' => 500]);
            }
        } else {
            $inserted = $wpdb->insert(
                kobutsu_ledger_table('sales'),
                $sale_data + ['created_at' => current_time('mysql')],
                ['%d', '%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%d', '%d', '%f', '%f', '%f', '%s', '%s', '%s']
            );
            if (!$inserted) {
                $wpdb->query('ROLLBACK');
                return new WP_Error('kobutsu_insert_failed', '販売データを登録できませんでした。', ['status' => 500]);
            }
        }
    }

    $wpdb->query('COMMIT');

    $response = new WP_REST_Request('GET', '/kobutsu/v1/items/' . $item_id);
    $response->set_param('id', $item_id);

    return kobutsu_ledger_get_item($response);
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
            "SELECT i.id, i.sku, i.item_name, i.category, i.accessories, i.condition_label, i.description, i.photo_url, i.status,
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
    return kobutsu_ledger_date_or_null(wp_unslash($value));
}
