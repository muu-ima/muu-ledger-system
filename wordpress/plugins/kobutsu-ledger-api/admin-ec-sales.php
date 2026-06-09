<?php

if (!defined('ABSPATH')) {
    exit;
}

function kobutsu_ledger_render_ec_sales_admin_page(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('このページにアクセスする権限がありません。', 'kobutsu-ledger'));
    }

    $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
    $message = isset($_GET['kobutsu_message']) ? sanitize_key($_GET['kobutsu_message']) : '';
    $active_view = isset($_GET['view']) ? sanitize_key($_GET['view']) : 'all';
    $search = isset($_GET['s']) ? sanitize_text_field((string) wp_unslash($_GET['s'])) : '';
    $edit_sale = $edit_id ? kobutsu_ledger_admin_get_ec_sale($edit_id) : null;
    $rows = kobutsu_ledger_admin_get_ec_sales($active_view, $search);
    $views = kobutsu_ledger_ec_sales_admin_views();

    ?>
    <div class="wrap kobutsu-ledger-admin">
        <?php kobutsu_ledger_render_ec_sales_admin_styles(); ?>

        <div class="kobutsu-shopify-shell">
            <div class="kobutsu-shopify-header">
                <div>
                    <h1>EC販売</h1>
                    <p>販売データと精算データを、行ごとにすばやく更新できます。</p>
                </div>
                <div class="kobutsu-shopify-actions">
                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=kobutsu-ec-sales')); ?>">表示をリセット</a>
                </div>
            </div>

            <?php if ($message === 'saved') : ?>
                <div class="notice notice-success is-dismissible"><p>保存しました。</p></div>
            <?php elseif ($message === 'deleted') : ?>
                <div class="notice notice-success is-dismissible"><p>削除しました。</p></div>
            <?php elseif ($message === 'missing') : ?>
                <div class="notice notice-error is-dismissible"><p>対象データが見つかりませんでした。</p></div>
            <?php endif; ?>

            <?php if ($edit_sale) : ?>
                <?php kobutsu_ledger_render_ec_sale_edit_form($edit_sale); ?>
            <?php endif; ?>

            <div class="kobutsu-shopify-card">
                <div class="kobutsu-shopify-toolbar">
                    <nav class="kobutsu-shopify-tabs" aria-label="EC販売ステータス">
                        <?php foreach ($views as $view_key => $view_label) : ?>
                            <a
                                class="<?php echo esc_attr($active_view === $view_key ? 'is-active' : ''); ?>"
                                href="<?php echo esc_url(add_query_arg(['page' => 'kobutsu-ec-sales', 'view' => $view_key, 's' => $search], admin_url('admin.php'))); ?>"
                            >
                                <?php echo esc_html($view_label); ?>
                            </a>
                        <?php endforeach; ?>
                    </nav>
                    <form method="get" class="kobutsu-shopify-search">
                        <input type="hidden" name="page" value="kobutsu-ec-sales">
                        <input type="hidden" name="view" value="<?php echo esc_attr($active_view); ?>">
                        <label class="screen-reader-text" for="kobutsu_ec_sales_search">EC販売を検索</label>
                        <input id="kobutsu_ec_sales_search" name="s" type="search" placeholder="SKU / 商品名 / Order no." value="<?php echo esc_attr($search); ?>">
                        <button type="submit" class="button">検索</button>
                    </form>
                </div>

                <div class="kobutsu-shopify-table-wrap">
                    <table class="widefat fixed striped kobutsu-shopify-table">
                        <thead>
                            <tr>
                                <th style="width: 44px;"><span class="screen-reader-text">選択</span></th>
                                <th style="width: 210px;">商品</th>
                                <th style="width: 145px;">Order no.</th>
                                <th style="width: 118px;">販売日</th>
                                <th style="width: 150px;">販売額</th>
                                <th style="width: 118px;">Payout</th>
                                <th style="width: 120px;">受取金額</th>
                                <th style="width: 120px;">最終損益</th>
                                <th style="width: 90px;">利益率</th>
                                <th style="width: 96px;">売れるまで</th>
                                <th style="width: 150px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$rows) : ?>
                                <tr><td colspan="11">EC販売データはありません。</td></tr>
                            <?php endif; ?>
                            <?php foreach ($rows as $row) : ?>
                                <?php $form_id = 'kobutsu-ec-sale-row-' . (int) $row['sale_id']; ?>
                    <tr>
                        <td><input type="checkbox" aria-label="<?php echo esc_attr($row['sku'] . ' を選択'); ?>"></td>
                        <td>
                            <strong><?php echo esc_html($row['item_name'] ?: '商品名未設定'); ?></strong>
                            <span class="kobutsu-muted"><?php echo esc_html($row['sku'] ?: ('Sale #' . $row['sale_id'])); ?></span>
                        </td>
                        <td><input form="<?php echo esc_attr($form_id); ?>" name="order_no" type="text" value="<?php echo esc_attr($row['order_no']); ?>"></td>
                        <td><input form="<?php echo esc_attr($form_id); ?>" name="sale_date" type="date" value="<?php echo esc_attr($row['sale_date']); ?>"></td>
                        <td>
                            <div class="kobutsu-money-input">
                                <input form="<?php echo esc_attr($form_id); ?>" name="sale_amount" type="number" step="0.01" value="<?php echo esc_attr((string) $row['sale_amount']); ?>">
                                <input form="<?php echo esc_attr($form_id); ?>" name="sale_currency" type="text" maxlength="3" value="<?php echo esc_attr($row['sale_currency']); ?>">
                            </div>
                        </td>
                        <td><input form="<?php echo esc_attr($form_id); ?>" name="payout_date" type="date" value="<?php echo esc_attr($row['payout_date']); ?>"></td>
                        <td><input form="<?php echo esc_attr($form_id); ?>" name="received_amount_jpy" type="number" value="<?php echo esc_attr((string) $row['received_amount_jpy']); ?>"></td>
                        <td><input form="<?php echo esc_attr($form_id); ?>" name="profit_jpy" type="number" value="<?php echo esc_attr((string) $row['profit_jpy']); ?>"></td>
                        <td><input form="<?php echo esc_attr($form_id); ?>" name="profit_rate" type="number" step="0.0001" value="<?php echo esc_attr((string) $row['profit_rate']); ?>"></td>
                        <td><input form="<?php echo esc_attr($form_id); ?>" name="days_to_sell" type="number" value="<?php echo esc_attr((string) $row['days_to_sell']); ?>"></td>
                        <td class="kobutsu-row-actions">
                            <form id="<?php echo esc_attr($form_id); ?>" method="post">
                                <?php wp_nonce_field('kobutsu_ec_sale_quick_update_' . (int) $row['sale_id']); ?>
                                <input type="hidden" name="kobutsu_ec_sale_action" value="quick_update">
                                <input type="hidden" name="sale_id" value="<?php echo esc_attr((string) $row['sale_id']); ?>">
                                <input type="hidden" name="current_view" value="<?php echo esc_attr($active_view); ?>">
                                <input type="hidden" name="current_search" value="<?php echo esc_attr($search); ?>">
                                <button type="submit" class="button button-primary button-small">更新</button>
                            </form>
                            <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'kobutsu-ec-sales', 'edit' => (int) $row['sale_id']], admin_url('admin.php'))); ?>">詳細</a>
                            <form method="post" onsubmit="return confirm('このEC販売データを削除しますか？商品・仕入れデータは残ります。');">
                                <?php wp_nonce_field('kobutsu_ec_sale_delete_' . (int) $row['sale_id']); ?>
                                <input type="hidden" name="kobutsu_ec_sale_action" value="delete">
                                <input type="hidden" name="sale_id" value="<?php echo esc_attr((string) $row['sale_id']); ?>">
                                <button type="submit" class="button button-small button-link-delete">削除</button>
                            </form>
                        </td>
                    </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php
}

function kobutsu_ledger_get_ec_sales(WP_REST_Request $request): WP_REST_Response
{
    $rows = kobutsu_ledger_admin_get_ec_sales();

    return rest_ensure_response(array_map('kobutsu_ledger_format_ec_sale_row', $rows));
}

function kobutsu_ledger_update_ec_sale(WP_REST_Request $request): WP_REST_Response|WP_Error
{
    $sale_id = absint($request['id']);
    $sale = kobutsu_ledger_admin_get_ec_sale($sale_id);

    if (!$sale) {
        return new WP_Error('kobutsu_ec_sale_not_found', 'EC販売データが見つかりません。', ['status' => 404]);
    }

    kobutsu_ledger_update_ec_sale_from_payload($sale_id, $request->get_json_params() ?: [], $sale);

    $updated_sale = kobutsu_ledger_admin_get_ec_sale($sale_id);
    if (!$updated_sale) {
        return new WP_Error('kobutsu_ec_sale_not_found', 'EC販売データが見つかりません。', ['status' => 404]);
    }

    return rest_ensure_response(kobutsu_ledger_format_ec_sale_row($updated_sale));
}

function kobutsu_ledger_handle_ec_sales_admin_action(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['kobutsu_ec_sale_action'])) {
        return;
    }

    $action = sanitize_key((string) wp_unslash($_POST['kobutsu_ec_sale_action']));
    $sale_id = isset($_POST['sale_id']) ? absint($_POST['sale_id']) : 0;

    if (!$sale_id) {
        kobutsu_ledger_ec_sales_admin_redirect(['kobutsu_message' => 'missing']);
    }

    if ($action === 'delete') {
        check_admin_referer('kobutsu_ec_sale_delete_' . $sale_id);
        kobutsu_ledger_admin_delete_ec_sale($sale_id);
        kobutsu_ledger_ec_sales_admin_redirect(['kobutsu_message' => 'deleted']);
    }

    if ($action === 'quick_update') {
        check_admin_referer('kobutsu_ec_sale_quick_update_' . $sale_id);
        kobutsu_ledger_admin_quick_update_ec_sale($sale_id);
        kobutsu_ledger_ec_sales_admin_redirect(kobutsu_ledger_ec_sales_current_filter_args([
            'kobutsu_message' => 'saved',
        ]));
    }

    if ($action === 'save') {
        check_admin_referer('kobutsu_ec_sale_save_' . $sale_id);
        kobutsu_ledger_admin_update_ec_sale($sale_id);
        kobutsu_ledger_ec_sales_admin_redirect(['kobutsu_message' => 'saved', 'edit' => $sale_id]);
    }
}

function kobutsu_ledger_ec_sales_admin_redirect(array $args): void
{
    wp_safe_redirect(add_query_arg(array_merge(['page' => 'kobutsu-ec-sales'], $args), admin_url('admin.php')));
    exit;
}

function kobutsu_ledger_ec_sales_current_filter_args(array $args = []): array
{
    $view = isset($_POST['current_view']) ? sanitize_key((string) wp_unslash($_POST['current_view'])) : 'all';
    $search = isset($_POST['current_search']) ? sanitize_text_field((string) wp_unslash($_POST['current_search'])) : '';

    if ($view !== '' && $view !== 'all') {
        $args['view'] = $view;
    }

    if ($search !== '') {
        $args['s'] = $search;
    }

    return $args;
}

function kobutsu_ledger_ec_sales_admin_views(): array
{
    return [
        'all' => 'すべて',
        'unsettled' => '未精算',
        'profit' => '利益あり',
        'loss' => '赤字',
        'shipped' => '配送あり',
    ];
}

function kobutsu_ledger_admin_get_ec_sales(string $view = 'all', string $search = ''): array
{
    global $wpdb;

    $where = [];
    $args = [];

    if ($view === 'unsettled') {
        $where[] = '(ss.id IS NULL OR ss.received_amount_jpy = 0)';
    } elseif ($view === 'profit') {
        $where[] = 'ss.profit_jpy > 0';
    } elseif ($view === 'loss') {
        $where[] = 'ss.profit_jpy < 0';
    } elseif ($view === 'shipped') {
        $where[] = '(s.tracking_no <> "" OR s.shipping_site <> "")';
    }

    if ($search !== '') {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where[] = '(i.sku LIKE %s OR i.item_name LIKE %s OR s.order_no LIKE %s)';
        $args[] = $like;
        $args[] = $like;
        $args[] = $like;
    }

    $sql = kobutsu_ledger_ec_sales_select_sql();
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY COALESCE(s.sale_date, s.created_at) DESC, s.id DESC LIMIT 100';

    if ($args) {
        $sql = $wpdb->prepare($sql, ...$args);
    }

    return $wpdb->get_results($sql, ARRAY_A) ?: [];
}

function kobutsu_ledger_admin_get_ec_sale(int $sale_id): ?array
{
    global $wpdb;

    $row = $wpdb->get_row(
        $wpdb->prepare(kobutsu_ledger_ec_sales_select_sql() . ' WHERE s.id = %d', $sale_id),
        ARRAY_A
    );

    return $row ?: null;
}

function kobutsu_ledger_ec_sales_select_sql(): string
{
    return 'SELECT
        s.id AS sale_id, s.item_id, s.marketplace, s.account_name, s.order_no,
        COALESCE(s.sale_date, "") AS sale_date, s.sale_type, s.sale_amount,
        s.sale_currency, s.sale_amount_jpy, s.buyer_country, s.buyer_id,
        s.buyer_name, s.tracking_no, s.shipping_site, s.actual_weight_g,
        s.dimensional_weight_g, s.package_length_cm, s.package_width_cm,
        s.package_height_cm, COALESCE(s.notes, "") AS sale_notes,
        COALESCE(i.sku, "") AS sku, COALESCE(i.item_name, "") AS item_name,
        COALESCE(p.purchase_date, "") AS purchase_date,
        COALESCE(p.purchase_price_jpy, 0) AS purchase_price_jpy,
        COALESCE(ss.id, 0) AS settlement_id,
        COALESCE(ss.payout_date, "") AS payout_date,
        COALESCE(ss.payout_id, "") AS payout_id,
        COALESCE(ss.total_fees, 0) AS total_fees,
        COALESCE(ss.ad_fee, 0) AS ad_fee,
        COALESCE(ss.ebay_fee, 0) AS ebay_fee,
        COALESCE(ss.payout_amount, 0) AS payout_amount,
        COALESCE(ss.sale_exchange_rate, 0) AS sale_exchange_rate,
        COALESCE(ss.payout_exchange_rate, 0) AS payout_exchange_rate,
        COALESCE(ss.received_amount_jpy, 0) AS received_amount_jpy,
        COALESCE(ss.overseas_shipping_jpy, 0) AS overseas_shipping_jpy,
        COALESCE(ss.fee_tax_refund_jpy, 0) AS fee_tax_refund_jpy,
        COALESCE(ss.consumption_tax_refund_jpy, 0) AS consumption_tax_refund_jpy,
        COALESCE(ss.profit_jpy, 0) AS profit_jpy,
        COALESCE(ss.profit_rate, 0) AS profit_rate,
        COALESCE(ss.days_to_sell, 0) AS days_to_sell
    FROM ' . kobutsu_ledger_table('sales') . ' s
    LEFT JOIN ' . kobutsu_ledger_table('items') . ' i ON i.id = s.item_id
    LEFT JOIN ' . kobutsu_ledger_table('purchases') . ' p ON p.item_id = s.item_id
    LEFT JOIN ' . kobutsu_ledger_table('sales_settlements') . ' ss ON ss.sale_id = s.id';
}

function kobutsu_ledger_render_ec_sale_edit_form(array $sale): void
{
    ?>
    <h2>編集: <?php echo esc_html($sale['sku'] ?: ('Sale #' . $sale['sale_id'])); ?></h2>
    <form method="post" class="kobutsu-ledger-edit-form">
        <?php wp_nonce_field('kobutsu_ec_sale_save_' . (int) $sale['sale_id']); ?>
        <input type="hidden" name="kobutsu_ec_sale_action" value="save">
        <input type="hidden" name="sale_id" value="<?php echo esc_attr((string) $sale['sale_id']); ?>">

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">商品</th>
                    <td>
                        <strong><?php echo esc_html($sale['sku']); ?></strong>
                        <span><?php echo esc_html($sale['item_name']); ?></span>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_ec_order_no">Order no.</label></th>
                    <td><input id="kobutsu_ec_order_no" name="order_no" type="text" class="regular-text" value="<?php echo esc_attr($sale['order_no']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_ec_sale_date">販売日</label></th>
                    <td><input id="kobutsu_ec_sale_date" name="sale_date" type="date" value="<?php echo esc_attr($sale['sale_date']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_ec_marketplace">販売先</label></th>
                    <td><input id="kobutsu_ec_marketplace" name="marketplace" type="text" class="regular-text" value="<?php echo esc_attr($sale['marketplace']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_ec_account_name">アカウント</label></th>
                    <td><input id="kobutsu_ec_account_name" name="account_name" type="text" class="regular-text" value="<?php echo esc_attr($sale['account_name']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_ec_sale_amount">販売額</label></th>
                    <td>
                        <input id="kobutsu_ec_sale_amount" name="sale_amount" type="number" step="0.01" value="<?php echo esc_attr((string) $sale['sale_amount']); ?>">
                        <input name="sale_currency" type="text" maxlength="3" size="4" value="<?php echo esc_attr($sale['sale_currency']); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_ec_buyer_country">国</label></th>
                    <td><input id="kobutsu_ec_buyer_country" name="buyer_country" type="text" class="regular-text" value="<?php echo esc_attr($sale['buyer_country']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_ec_tracking_no">国内送り状</label></th>
                    <td><input id="kobutsu_ec_tracking_no" name="tracking_no" type="text" class="regular-text" value="<?php echo esc_attr($sale['tracking_no']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_ec_shipping_site">SLS送り状</label></th>
                    <td><input id="kobutsu_ec_shipping_site" name="shipping_site" type="text" class="regular-text" value="<?php echo esc_attr($sale['shipping_site']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_ec_payout_date">出金日</label></th>
                    <td><input id="kobutsu_ec_payout_date" name="payout_date" type="date" value="<?php echo esc_attr($sale['payout_date']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_ec_payout_id">Payout ID</label></th>
                    <td><input id="kobutsu_ec_payout_id" name="payout_id" type="text" class="regular-text" value="<?php echo esc_attr($sale['payout_id']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row">手数料・Payout</th>
                    <td>
                        <label>広告費 <input name="ad_fee" type="number" step="0.01" value="<?php echo esc_attr((string) $sale['ad_fee']); ?>"></label>
                        <label>Shopee手数料 <input name="ebay_fee" type="number" step="0.01" value="<?php echo esc_attr((string) $sale['ebay_fee']); ?>"></label>
                        <label>Payout金額 <input name="payout_amount" type="number" step="0.01" value="<?php echo esc_attr((string) $sale['payout_amount']); ?>"></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">為替・受取</th>
                    <td>
                        <label>販売時為替 <input name="sale_exchange_rate" type="number" step="0.0001" value="<?php echo esc_attr((string) $sale['sale_exchange_rate']); ?>"></label>
                        <label>出金時為替 <input name="payout_exchange_rate" type="number" step="0.0001" value="<?php echo esc_attr((string) $sale['payout_exchange_rate']); ?>"></label>
                        <label>受取金額 <input name="received_amount_jpy" type="number" value="<?php echo esc_attr((string) $sale['received_amount_jpy']); ?>"> 円</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">損益</th>
                    <td>
                        <label>海外送料 <input name="overseas_shipping_jpy" type="number" value="<?php echo esc_attr((string) $sale['overseas_shipping_jpy']); ?>"> 円</label>
                        <label>手数料還付 <input name="fee_tax_refund_jpy" type="number" value="<?php echo esc_attr((string) $sale['fee_tax_refund_jpy']); ?>"> 円</label>
                        <label>消費税還付 <input name="consumption_tax_refund_jpy" type="number" value="<?php echo esc_attr((string) $sale['consumption_tax_refund_jpy']); ?>"> 円</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">集計結果</th>
                    <td>
                        <label>最終損益 <input name="profit_jpy" type="number" value="<?php echo esc_attr((string) $sale['profit_jpy']); ?>"> 円</label>
                        <label>利益率 <input name="profit_rate" type="number" step="0.0001" value="<?php echo esc_attr((string) $sale['profit_rate']); ?>"></label>
                        <label>売れるまで <input name="days_to_sell" type="number" value="<?php echo esc_attr((string) $sale['days_to_sell']); ?>"> 日</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_ec_sale_notes">備考</label></th>
                    <td><textarea id="kobutsu_ec_sale_notes" name="sale_notes" class="large-text" rows="4"><?php echo esc_textarea($sale['sale_notes']); ?></textarea></td>
                </tr>
            </tbody>
        </table>

        <?php submit_button('保存'); ?>
    </form>
    <hr>
    <?php
}

function kobutsu_ledger_admin_update_ec_sale(int $sale_id): void
{
    global $wpdb;

    $sale = kobutsu_ledger_admin_get_ec_sale($sale_id);
    if (!$sale) {
        return;
    }

    $now = current_time('mysql');
    $order_no = sanitize_text_field((string) wp_unslash($_POST['order_no'] ?? ''));
    $sale_currency = strtoupper(substr(sanitize_text_field((string) wp_unslash($_POST['sale_currency'] ?? 'USD')), 0, 3));
    $sale_amount = (float) ($_POST['sale_amount'] ?? 0);

    $wpdb->query('START TRANSACTION');

    $wpdb->update(kobutsu_ledger_table('sales'), [
        'marketplace' => sanitize_text_field((string) wp_unslash($_POST['marketplace'] ?? '')),
        'account_name' => sanitize_text_field((string) wp_unslash($_POST['account_name'] ?? '')),
        'order_no' => $order_no,
        'sale_date' => kobutsu_ledger_admin_date_or_null($_POST['sale_date'] ?? ''),
        'sale_amount' => $sale_amount,
        'sale_currency' => $sale_currency ?: 'USD',
        'sale_amount_jpy' => $sale_currency === 'JPY' ? (int) round($sale_amount) : 0,
        'buyer_country' => sanitize_text_field((string) wp_unslash($_POST['buyer_country'] ?? '')),
        'tracking_no' => sanitize_text_field((string) wp_unslash($_POST['tracking_no'] ?? '')),
        'shipping_site' => sanitize_text_field((string) wp_unslash($_POST['shipping_site'] ?? '')),
        'notes' => sanitize_textarea_field((string) wp_unslash($_POST['sale_notes'] ?? '')),
        'updated_at' => $now,
    ], ['id' => $sale_id], ['%s', '%s', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%s', '%s', '%s'], ['%d']);

    $settlement_data = [
        'sale_id' => $sale_id,
        'order_no' => $order_no,
        'payout_date' => kobutsu_ledger_admin_date_or_null($_POST['payout_date'] ?? ''),
        'payout_id' => sanitize_text_field((string) wp_unslash($_POST['payout_id'] ?? '')),
        'total_fees' => (float) ($_POST['ad_fee'] ?? 0) + (float) ($_POST['ebay_fee'] ?? 0),
        'ad_fee' => (float) ($_POST['ad_fee'] ?? 0),
        'ebay_fee' => (float) ($_POST['ebay_fee'] ?? 0),
        'payout_amount' => (float) ($_POST['payout_amount'] ?? 0),
        'sale_exchange_rate' => (float) ($_POST['sale_exchange_rate'] ?? 0),
        'payout_exchange_rate' => (float) ($_POST['payout_exchange_rate'] ?? 0),
        'received_amount_jpy' => (int) ($_POST['received_amount_jpy'] ?? 0),
        'overseas_shipping_jpy' => (int) ($_POST['overseas_shipping_jpy'] ?? 0),
        'fee_tax_refund_jpy' => (int) ($_POST['fee_tax_refund_jpy'] ?? 0),
        'consumption_tax_refund_jpy' => (int) ($_POST['consumption_tax_refund_jpy'] ?? 0),
        'profit_jpy' => (int) ($_POST['profit_jpy'] ?? 0),
        'profit_rate' => (float) ($_POST['profit_rate'] ?? 0),
        'days_to_sell' => (int) ($_POST['days_to_sell'] ?? 0),
        'updated_at' => $now,
    ];

    if (!empty($sale['settlement_id'])) {
        $wpdb->update(
            kobutsu_ledger_table('sales_settlements'),
            $settlement_data,
            ['id' => (int) $sale['settlement_id']],
            ['%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%s'],
            ['%d']
        );
    } else {
        $wpdb->insert(
            kobutsu_ledger_table('sales_settlements'),
            $settlement_data,
            ['%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%s']
        );
    }

    $wpdb->query('COMMIT');
}

function kobutsu_ledger_admin_quick_update_ec_sale(int $sale_id): void
{
    global $wpdb;

    $sale = kobutsu_ledger_admin_get_ec_sale($sale_id);
    if (!$sale) {
        return;
    }

    $now = current_time('mysql');
    $order_no = sanitize_text_field((string) wp_unslash($_POST['order_no'] ?? $sale['order_no']));
    $sale_currency = strtoupper(substr(sanitize_text_field((string) wp_unslash($_POST['sale_currency'] ?? $sale['sale_currency'])), 0, 3));
    $sale_amount = (float) ($_POST['sale_amount'] ?? $sale['sale_amount']);

    $wpdb->query('START TRANSACTION');

    $wpdb->update(kobutsu_ledger_table('sales'), [
        'order_no' => $order_no,
        'sale_date' => kobutsu_ledger_admin_date_or_null($_POST['sale_date'] ?? $sale['sale_date']),
        'sale_amount' => $sale_amount,
        'sale_currency' => $sale_currency ?: 'USD',
        'sale_amount_jpy' => $sale_currency === 'JPY' ? (int) round($sale_amount) : 0,
        'updated_at' => $now,
    ], ['id' => $sale_id], ['%s', '%s', '%f', '%s', '%d', '%s'], ['%d']);

    $settlement_data = [
        'sale_id' => $sale_id,
        'order_no' => $order_no,
        'payout_date' => kobutsu_ledger_admin_date_or_null($_POST['payout_date'] ?? $sale['payout_date']),
        'payout_id' => (string) $sale['payout_id'],
        'total_fees' => (float) $sale['total_fees'],
        'ad_fee' => (float) $sale['ad_fee'],
        'ebay_fee' => (float) $sale['ebay_fee'],
        'payout_amount' => (float) $sale['payout_amount'],
        'sale_exchange_rate' => (float) $sale['sale_exchange_rate'],
        'payout_exchange_rate' => (float) $sale['payout_exchange_rate'],
        'received_amount_jpy' => (int) ($_POST['received_amount_jpy'] ?? $sale['received_amount_jpy']),
        'overseas_shipping_jpy' => (int) $sale['overseas_shipping_jpy'],
        'fee_tax_refund_jpy' => (int) $sale['fee_tax_refund_jpy'],
        'consumption_tax_refund_jpy' => (int) $sale['consumption_tax_refund_jpy'],
        'profit_jpy' => (int) ($_POST['profit_jpy'] ?? $sale['profit_jpy']),
        'profit_rate' => (float) ($_POST['profit_rate'] ?? $sale['profit_rate']),
        'days_to_sell' => (int) ($_POST['days_to_sell'] ?? $sale['days_to_sell']),
        'updated_at' => $now,
    ];

    if (!empty($sale['settlement_id'])) {
        $wpdb->update(
            kobutsu_ledger_table('sales_settlements'),
            $settlement_data,
            ['id' => (int) $sale['settlement_id']],
            ['%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%s'],
            ['%d']
        );
    } else {
        $wpdb->insert(
            kobutsu_ledger_table('sales_settlements'),
            $settlement_data,
            ['%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%s']
        );
    }

    $wpdb->query('COMMIT');
}

function kobutsu_ledger_update_ec_sale_from_payload(
    int $sale_id,
    array $payload,
    array $sale
): void {
    global $wpdb;

    $now = current_time('mysql');
    $order_no = kobutsu_ledger_payload_text($payload, 'order_no', (string) $sale['order_no']);
    $sale_currency = strtoupper(substr(kobutsu_ledger_payload_text($payload, 'sale_currency', (string) $sale['sale_currency']), 0, 3));
    $sale_amount = kobutsu_ledger_payload_float($payload, 'sale_amount', (float) $sale['sale_amount']);

    $wpdb->query('START TRANSACTION');

    $wpdb->update(kobutsu_ledger_table('sales'), [
        'order_no' => $order_no,
        'sale_date' => kobutsu_ledger_admin_date_or_null($payload['sale_date'] ?? $sale['sale_date']),
        'sale_amount' => $sale_amount,
        'sale_currency' => $sale_currency ?: 'USD',
        'sale_amount_jpy' => $sale_currency === 'JPY' ? (int) round($sale_amount) : 0,
        'tracking_no' => kobutsu_ledger_payload_text($payload, 'domestic_tracking_no', (string) $sale['tracking_no']),
        'shipping_site' => kobutsu_ledger_payload_text($payload, 'sls_tracking_no', (string) $sale['shipping_site']),
        'updated_at' => $now,
    ], ['id' => $sale_id], ['%s', '%s', '%f', '%s', '%d', '%s', '%s', '%s'], ['%d']);

    $settlement_data = [
        'sale_id' => $sale_id,
        'order_no' => $order_no,
        'payout_date' => kobutsu_ledger_admin_date_or_null($payload['payout_date'] ?? $sale['payout_date']),
        'payout_id' => (string) $sale['payout_id'],
        'total_fees' => (float) $sale['total_fees'],
        'ad_fee' => (float) $sale['ad_fee'],
        'ebay_fee' => (float) $sale['ebay_fee'],
        'payout_amount' => (float) $sale['payout_amount'],
        'sale_exchange_rate' => (float) $sale['sale_exchange_rate'],
        'payout_exchange_rate' => (float) $sale['payout_exchange_rate'],
        'received_amount_jpy' => kobutsu_ledger_payload_int($payload, 'received_amount_jpy', (int) $sale['received_amount_jpy']),
        'overseas_shipping_jpy' => (int) $sale['overseas_shipping_jpy'],
        'fee_tax_refund_jpy' => (int) $sale['fee_tax_refund_jpy'],
        'consumption_tax_refund_jpy' => (int) $sale['consumption_tax_refund_jpy'],
        'profit_jpy' => kobutsu_ledger_payload_int($payload, 'profit_jpy', (int) $sale['profit_jpy']),
        'profit_rate' => kobutsu_ledger_payload_float($payload, 'profit_rate', (float) $sale['profit_rate']),
        'days_to_sell' => (int) $sale['days_to_sell'],
        'updated_at' => $now,
    ];

    if (!empty($sale['settlement_id'])) {
        $wpdb->update(
            kobutsu_ledger_table('sales_settlements'),
            $settlement_data,
            ['id' => (int) $sale['settlement_id']],
            ['%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%s'],
            ['%d']
        );
    } else {
        $wpdb->insert(
            kobutsu_ledger_table('sales_settlements'),
            $settlement_data,
            ['%d', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%s']
        );
    }

    $wpdb->query('COMMIT');
}

function kobutsu_ledger_admin_delete_ec_sale(int $sale_id): void
{
    global $wpdb;

    $wpdb->query('START TRANSACTION');
    $wpdb->delete(kobutsu_ledger_table('sales_settlements'), ['sale_id' => $sale_id], ['%d']);
    $wpdb->delete(kobutsu_ledger_table('sales'), ['id' => $sale_id], ['%d']);
    $wpdb->query('COMMIT');
}

function kobutsu_ledger_admin_format_money(float $amount, string $currency): string
{
    $currency = strtoupper($currency ?: 'USD');

    return $currency . ' ' . number_format($amount, 2);
}

function kobutsu_ledger_admin_format_yen(int $amount): string
{
    return '¥' . number_format($amount);
}

function kobutsu_ledger_admin_format_rate(float $rate): string
{
    if ($rate === 0.0) {
        return '';
    }

    return number_format($rate, 2) . '%';
}

function kobutsu_ledger_format_ec_sale_row(array $row): array
{
    return [
        'sale_id' => (int) $row['sale_id'],
        'bundled_flag' => '',
        'sku' => (string) $row['sku'],
        'order_no' => (string) $row['order_no'],
        'purchase_date' => (string) $row['purchase_date'],
        'listed_at' => '',
        'sold_at' => (string) $row['sale_date'],
        'payout_at' => (string) $row['payout_date'],
        'item_name' => (string) $row['item_name'],
        'purchase_price_jpy' => (int) $row['purchase_price_jpy'],
        'sale_amount_raw' => kobutsu_ledger_admin_format_money((float) $row['sale_amount'], (string) $row['sale_currency']),
        'sale_amount_jpy' => (int) $row['sale_amount_jpy'],
        'total_fees_raw' => (float) $row['total_fees'],
        'ad_fee_raw' => (float) $row['ad_fee'],
        'marketplace_fee_raw' => (float) $row['ebay_fee'],
        'payout_amount_raw' => (float) $row['payout_amount'],
        'sale_exchange_rate' => (float) $row['sale_exchange_rate'],
        'payout_exchange_rate' => (float) $row['payout_exchange_rate'],
        'received_amount_jpy' => (int) $row['received_amount_jpy'],
        'overseas_shipping_yen' => (int) $row['overseas_shipping_jpy'],
        'fee_tax_refund_jpy' => (int) $row['fee_tax_refund_jpy'],
        'purchase_tax_refund_jpy' => (int) $row['consumption_tax_refund_jpy'],
        'profit_jpy' => (int) $row['profit_jpy'],
        'profit_rate' => (float) $row['profit_rate'],
        'days_to_sell' => (int) $row['days_to_sell'],
        'domestic_tracking_no' => (string) $row['tracking_no'],
        'sls_tracking_no' => (string) $row['shipping_site'],
        'settlement_note' => (string) $row['sale_notes'],
    ];
}

function kobutsu_ledger_payload_text(array $payload, string $key, string $fallback): string
{
    if (!array_key_exists($key, $payload)) {
        return $fallback;
    }

    return sanitize_text_field((string) $payload[$key]);
}

function kobutsu_ledger_payload_float(array $payload, string $key, float $fallback): float
{
    if (!array_key_exists($key, $payload)) {
        return $fallback;
    }

    return (float) $payload[$key];
}

function kobutsu_ledger_payload_int(array $payload, string $key, int $fallback): int
{
    if (!array_key_exists($key, $payload)) {
        return $fallback;
    }

    return (int) $payload[$key];
}

function kobutsu_ledger_render_ec_sales_admin_styles(): void
{
    ?>
    <style>
        .kobutsu-shopify-shell {
            color: #202223;
            max-width: 1600px;
        }

        .kobutsu-shopify-header {
            align-items: center;
            display: flex;
            justify-content: space-between;
            margin: 18px 0;
        }

        .kobutsu-shopify-header h1 {
            font-size: 28px;
            font-weight: 650;
            margin: 0 0 6px;
        }

        .kobutsu-shopify-header p {
            color: #6d7175;
            margin: 0;
        }

        .kobutsu-shopify-actions {
            display: flex;
            gap: 8px;
        }

        .kobutsu-shopify-card {
            background: #fff;
            border: 1px solid #dcdfe4;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(31, 33, 36, 0.08);
            overflow: hidden;
        }

        .kobutsu-shopify-toolbar {
            align-items: center;
            border-bottom: 1px solid #e1e3e5;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            padding: 12px 14px;
        }

        .kobutsu-shopify-tabs {
            display: flex;
            gap: 4px;
        }

        .kobutsu-shopify-tabs a {
            border-radius: 8px;
            color: #202223;
            padding: 7px 12px;
            text-decoration: none;
        }

        .kobutsu-shopify-tabs a.is-active {
            background: #e3f1df;
            box-shadow: inset 0 0 0 1px #a7cfa2;
            font-weight: 650;
        }

        .kobutsu-shopify-search {
            display: flex;
            gap: 8px;
            margin: 0;
        }

        .kobutsu-shopify-search input[type="search"] {
            min-width: 280px;
        }

        .kobutsu-shopify-table-wrap {
            overflow-x: auto;
        }

        .kobutsu-shopify-table {
            border: 0;
            min-width: 1320px;
        }

        .kobutsu-shopify-table th {
            background: #f6f6f7;
            color: #6d7175;
            font-size: 12px;
            font-weight: 650;
        }

        .kobutsu-shopify-table td {
            vertical-align: middle;
        }

        .kobutsu-shopify-table input[type="text"],
        .kobutsu-shopify-table input[type="date"],
        .kobutsu-shopify-table input[type="number"] {
            border-color: #c9cccf;
            border-radius: 8px;
            max-width: 100%;
            min-height: 34px;
            width: 100%;
        }

        .kobutsu-muted {
            color: #6d7175;
            display: block;
            font-size: 12px;
            margin-top: 4px;
        }

        .kobutsu-money-input {
            display: grid;
            gap: 6px;
            grid-template-columns: minmax(72px, 1fr) 48px;
        }

        .kobutsu-row-actions {
            display: flex;
            gap: 6px;
        }

        .kobutsu-row-actions form {
            margin: 0;
        }

        @media (max-width: 960px) {
            .kobutsu-shopify-header,
            .kobutsu-shopify-toolbar {
                align-items: flex-start;
                flex-direction: column;
            }

            .kobutsu-shopify-tabs {
                flex-wrap: wrap;
            }

            .kobutsu-shopify-search,
            .kobutsu-shopify-search input[type="search"] {
                width: 100%;
            }
        }
    </style>
    <?php
}
