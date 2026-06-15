<?php

if (!defined('ABSPATH')) {
    exit;
}

function kobutsu_ledger_register_shopee_orders_admin_menu(): void
{
    add_menu_page(
        'Shopeeオーダー',
        'Shopeeオーダー',
        'edit_posts',
        'kobutsu-shopee-orders',
        'kobutsu_ledger_render_shopee_orders_admin_page',
        'dashicons-list-view',
        31
    );
}

function kobutsu_ledger_render_shopee_orders_admin_page(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('このページを表示する権限がありません。', 'kobutsu-ledger'));
    }

    $active_tab = kobutsu_ledger_current_shopee_orders_tab();
    ?>
    <div class="wrap">
        <h1>Shopeeオーダー</h1>

        <?php kobutsu_ledger_render_shopee_orders_admin_tabs($active_tab); ?>

        <?php if ($active_tab === 'import') : ?>
            <?php kobutsu_ledger_render_shopee_orders_import_panel(); ?>
        <?php elseif ($active_tab === 'history') : ?>
            <?php kobutsu_ledger_render_shopee_orders_history_panel(); ?>
        <?php else : ?>
            <?php kobutsu_ledger_render_shopee_orders_records_panel(); ?>
        <?php endif; ?>
    </div>
    <?php
}

function kobutsu_ledger_current_shopee_orders_tab(): string
{
    $tab = isset($_GET['order_tab']) ? sanitize_key((string) wp_unslash($_GET['order_tab'])) : 'import';
    $allowed_tabs = ['import', 'records', 'history'];

    return in_array($tab, $allowed_tabs, true) ? $tab : 'import';
}

function kobutsu_ledger_render_shopee_orders_admin_tabs(string $active_tab): void
{
    $tabs = [
        'import' => 'CSV取り込み',
        'records' => 'オーダー原票',
        'history' => '取り込み履歴',
    ];
    ?>
    <nav class="nav-tab-wrapper" aria-label="Shopeeオーダー表示">
        <?php foreach ($tabs as $tab => $label) : ?>
            <a
                class="nav-tab <?php echo $active_tab === $tab ? 'nav-tab-active' : ''; ?>"
                href="<?php echo esc_url(add_query_arg(['page' => 'kobutsu-shopee-orders', 'order_tab' => $tab], admin_url('admin.php'))); ?>"
            >
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php
}

function kobutsu_ledger_render_shopee_orders_import_panel(): void
{
    ?>
    <p>Shopee orders CSV を受付オーダーの原票として取り込み、後続のEC販売・ペイメント照合で参照できる形で保存します。</p>

    <h2>Shopee orders CSV 取り込み</h2>
    <p>CSV取り込み処理は次のステップで追加します。</p>
    <?php
}

function kobutsu_ledger_render_shopee_orders_records_panel(): void
{
    $rows = kobutsu_ledger_get_recent_shopee_orders();
    ?>
    <h2>直近の Shopee オーダー原票</h2>
    <table class="widefat striped" style="max-width: 1400px;">
        <thead>
            <tr>
                <th>注文ID</th>
                <th>状態</th>
                <th>注文日</th>
                <th>支払い日時</th>
                <th>SKU</th>
                <th>購入者</th>
                <th>国</th>
                <th>数量</th>
                <th>金額</th>
                <th>配送番号</th>
                <th>取込日時</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows === []) : ?>
                <tr>
                    <td colspan="11">Shopee オーダー原票はまだ取り込まれていません。</td>
                </tr>
            <?php else : ?>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td><?php echo esc_html((string) $row['order_no']); ?></td>
                        <td><?php echo esc_html((string) $row['order_status']); ?></td>
                        <td><?php echo esc_html((string) $row['order_created_at']); ?></td>
                        <td><?php echo esc_html((string) $row['order_paid_at']); ?></td>
                        <td><?php echo esc_html((string) $row['sku']); ?></td>
                        <td><?php echo esc_html((string) $row['buyer_username']); ?></td>
                        <td><?php echo esc_html((string) $row['country']); ?></td>
                        <td><?php echo esc_html(number_format((int) $row['quantity'])); ?></td>
                        <td><?php echo esc_html(kobutsu_ledger_format_shopee_order_amount($row)); ?></td>
                        <td><?php echo esc_html((string) $row['tracking_number']); ?></td>
                        <td><?php echo esc_html((string) $row['created_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}

function kobutsu_ledger_render_shopee_orders_history_panel(): void
{
    $batches = kobutsu_ledger_get_recent_shopee_order_import_batches();
    ?>
    <h2>取り込み履歴</h2>
    <table class="widefat striped" style="max-width: 1200px;">
        <thead>
            <tr>
                <th>実行日時</th>
                <th>ファイル名</th>
                <th>状態</th>
                <th>保存件数</th>
                <th>スキップ件数</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($batches === []) : ?>
                <tr>
                    <td colspan="5">取り込み履歴はまだありません。</td>
                </tr>
            <?php else : ?>
                <?php foreach ($batches as $batch) : ?>
                    <tr>
                        <td><?php echo esc_html((string) ($batch['completed_at'] ?: $batch['created_at'])); ?></td>
                        <td><?php echo esc_html((string) $batch['original_filename']); ?></td>
                        <td><?php echo esc_html((string) $batch['status']); ?></td>
                        <td><?php echo esc_html(number_format((int) $batch['imported_rows'])); ?></td>
                        <td><?php echo esc_html(number_format((int) $batch['error_rows'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}

function kobutsu_ledger_get_recent_shopee_orders(int $limit = 50): array
{
    global $wpdb;

    $table = kobutsu_ledger_table('shopee_orders');

    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table ORDER BY id DESC LIMIT %d",
        $limit
    ), ARRAY_A);
}

function kobutsu_ledger_get_recent_shopee_order_import_batches(int $limit = 10): array
{
    global $wpdb;

    $table = kobutsu_ledger_table('import_batches');

    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE source_name = %s ORDER BY id DESC LIMIT %d",
        'shopee_orders',
        $limit
    ), ARRAY_A);
}

function kobutsu_ledger_format_shopee_order_amount(array $row): string
{
    $currency = trim((string) ($row['currency'] ?? ''));
    $amount = (float) ($row['grand_total'] ?: $row['total_amount'] ?: $row['gross_amount']);
    if ($currency === '') {
        return number_format($amount, 2);
    }

    return $currency . ' ' . number_format($amount, 2);
}
