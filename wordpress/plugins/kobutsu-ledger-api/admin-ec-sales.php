<?php

if (!defined('ABSPATH')) {
    exit;
}

function kobutsu_ledger_render_ec_sales_admin_page(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('このページにアクセスする権限がありません。', 'kobutsu-ledger'));
    }

    kobutsu_ledger_handle_ec_sales_admin_action();

    $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
    $message = isset($_GET['kobutsu_message']) ? sanitize_key($_GET['kobutsu_message']) : '';
    $edit_sale = $edit_id ? kobutsu_ledger_admin_get_ec_sale($edit_id) : null;
    $rows = kobutsu_ledger_admin_get_ec_sales();

    ?>
    <div class="wrap kobutsu-ledger-admin">
        <h1>EC販売</h1>
        <p>EC販売の販売データと精算データを管理します。商品・仕入れ本体は削除せず、EC販売側の行だけを編集・削除します。</p>

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

        <h2>EC販売データ</h2>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th style="width: 140px;">SKU</th>
                    <th>商品名</th>
                    <th style="width: 140px;">Order no.</th>
                    <th style="width: 105px;">販売日</th>
                    <th style="width: 110px;">販売額</th>
                    <th style="width: 105px;">Payout</th>
                    <th style="width: 110px;">受取金額</th>
                    <th style="width: 110px;">最終損益</th>
                    <th style="width: 85px;">利益率</th>
                    <th style="width: 95px;">操作</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows) : ?>
                    <tr><td colspan="11">EC販売データはありません。</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td><?php echo esc_html((string) $row['sale_id']); ?></td>
                        <td><strong><?php echo esc_html($row['sku']); ?></strong></td>
                        <td><?php echo esc_html($row['item_name']); ?></td>
                        <td><?php echo esc_html($row['order_no']); ?></td>
                        <td><?php echo esc_html($row['sale_date']); ?></td>
                        <td><?php echo esc_html(kobutsu_ledger_admin_format_money((float) $row['sale_amount'], $row['sale_currency'])); ?></td>
                        <td><?php echo esc_html($row['payout_date']); ?></td>
                        <td><?php echo esc_html(kobutsu_ledger_admin_format_yen((int) $row['received_amount_jpy'])); ?></td>
                        <td><?php echo esc_html(kobutsu_ledger_admin_format_yen((int) $row['profit_jpy'])); ?></td>
                        <td><?php echo esc_html(kobutsu_ledger_admin_format_rate((float) $row['profit_rate'])); ?></td>
                        <td>
                            <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'kobutsu-ec-sales', 'edit' => (int) $row['sale_id']], admin_url('admin.php'))); ?>">編集</a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('このEC販売データを削除しますか？商品・仕入れデータは残ります。');">
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
    <?php
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

function kobutsu_ledger_admin_get_ec_sales(): array
{
    global $wpdb;

    return $wpdb->get_results(
        kobutsu_ledger_ec_sales_select_sql() . '
        ORDER BY COALESCE(s.sale_date, s.created_at) DESC, s.id DESC
        LIMIT 100',
        ARRAY_A
    ) ?: [];
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
