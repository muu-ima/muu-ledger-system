<?php

if (!defined('ABSPATH')) {
    exit;
}

function kobutsu_ledger_render_supplier_sources_admin_page(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('このページにアクセスする権限がありません。', 'kobutsu-ledger'));
    }

    $edit_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
    $message = isset($_GET['kobutsu_message']) ? sanitize_key($_GET['kobutsu_message']) : '';
    $edit_row = $edit_id ? kobutsu_ledger_admin_get_supplier_source_by_id($edit_id) : null;
    $rows = kobutsu_ledger_admin_get_supplier_sources();

    ?>
    <div class="wrap kobutsu-ledger-admin">
        <style>
            .kobutsu-admin-row-actions {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }

            .kobutsu-admin-row-actions form {
                margin: 0;
            }
        </style>
        <h1>仕入れ管理</h1>
        <p>仕入元データをカスタムテーブルとして保存した原票ビューです。</p>

        <?php if ($message === 'saved') : ?>
            <div class="notice notice-success is-dismissible"><p>保存しました。</p></div>
        <?php elseif ($message === 'deleted') : ?>
            <div class="notice notice-success is-dismissible"><p>削除しました。</p></div>
        <?php elseif ($message === 'missing') : ?>
            <div class="notice notice-error is-dismissible"><p>対象データが見つかりませんでした。</p></div>
        <?php elseif ($message === 'update_failed') : ?>
            <div class="notice notice-error is-dismissible"><p>仕入元データを更新できませんでした。</p></div>
        <?php endif; ?>

        <?php if ($edit_row) : ?>
            <?php kobutsu_ledger_render_supplier_source_edit_form($edit_row); ?>
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
                            <div class="kobutsu-admin-row-actions">
                                <a class="button button-small" href="<?php echo esc_url(add_query_arg(['page' => 'kobutsu-supplier-sources', 'edit' => (int) $row['id']], admin_url('admin.php'))); ?>">編集</a>
                                <form method="post" onsubmit="return confirm('この仕入元データを削除しますか？');">
                                    <?php wp_nonce_field('kobutsu_supplier_source_delete_' . (int) $row['id']); ?>
                                    <input type="hidden" name="kobutsu_source_action" value="delete">
                                    <input type="hidden" name="source_id" value="<?php echo esc_attr((string) $row['id']); ?>">
                                    <button type="submit" class="button button-small button-link-delete">削除</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function kobutsu_ledger_render_supplier_source_edit_form(array $row): void
{
    ?>
    <h2>編集: <?php echo esc_html($row['sku'] ?: ('Source #' . $row['id'])); ?></h2>
    <form method="post" class="kobutsu-ledger-edit-form">
        <?php wp_nonce_field('kobutsu_supplier_source_save_' . (int) $row['id']); ?>
        <input type="hidden" name="kobutsu_source_action" value="save">
        <input type="hidden" name="source_id" value="<?php echo esc_attr((string) $row['id']); ?>">

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row"><label for="kobutsu_source_sku">SKU</label></th>
                    <td><input id="kobutsu_source_sku" name="sku" type="text" class="regular-text" value="<?php echo esc_attr($row['sku']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="kobutsu_source_order_no">Order no.</label></th>
                    <td><input id="kobutsu_source_order_no" name="order_no" type="text" class="regular-text" value="<?php echo esc_attr($row['order_no']); ?>"></td>
                </tr>
            </tbody>
        </table>

        <?php submit_button('保存'); ?>
    </form>
    <?php
}
