<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', 'kobutsu_ledger_handle_payments_admin_action');

function kobutsu_ledger_register_payments_admin_menu(): void
{
    add_menu_page(
        'ペイメント',
        'ペイメント',
        'edit_posts',
        'kobutsu-payments',
        'kobutsu_ledger_render_payments_admin_page',
        'dashicons-money',
        30
    );
}

function kobutsu_ledger_handle_payments_admin_action(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['kobutsu_payments_action'])) {
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('この操作を実行する権限がありません。', 'kobutsu-ledger'));
    }

    $action = sanitize_key((string) wp_unslash($_POST['kobutsu_payments_action']));
    if ($action !== 'import_shopee_payments') {
        return;
    }

    check_admin_referer('kobutsu_import_shopee_payments');

    $result = kobutsu_ledger_import_shopee_payments_upload();
    kobutsu_ledger_payments_admin_redirect([
        'kobutsu_message' => !empty($result['ok']) ? 'import_success' : 'import_failed',
        'imported' => (string) (int) ($result['imported'] ?? 0),
        'skipped' => (string) (int) ($result['skipped'] ?? 0),
        'error' => (string) ($result['message'] ?? ''),
    ]);
}

function kobutsu_ledger_import_shopee_payments_upload(): array
{
    if (empty($_FILES['shopee_payments_csv']) || !is_array($_FILES['shopee_payments_csv'])) {
        return [
            'ok' => false,
            'message' => 'CSVファイルが選択されていません。',
            'imported' => 0,
            'skipped' => 0,
        ];
    }

    $file = $_FILES['shopee_payments_csv'];
    if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return [
            'ok' => false,
            'message' => 'CSVファイルをアップロードできませんでした。',
            'imported' => 0,
            'skipped' => 0,
        ];
    }

    $tmp_name = (string) ($file['tmp_name'] ?? '');
    if ($tmp_name === '' || !is_uploaded_file($tmp_name)) {
        return [
            'ok' => false,
            'message' => 'アップロードファイルを確認できませんでした。',
            'imported' => 0,
            'skipped' => 0,
        ];
    }

    return kobutsu_ledger_import_shopee_payments_csv($tmp_name);
}

function kobutsu_ledger_import_shopee_payments_csv(string $file_path): array
{
    $handle = fopen($file_path, 'rb');
    if ($handle === false) {
        return [
            'ok' => false,
            'message' => 'CSVファイルを開けませんでした。',
            'imported' => 0,
            'skipped' => 0,
        ];
    }

    $headers = [];
    $imported = 0;
    $skipped = 0;
    $line_number = 0;

    while (($row = fgetcsv($handle)) !== false) {
        $line_number++;
        if ($headers === []) {
            if (kobutsu_ledger_is_shopee_payments_header($row)) {
                $headers = $row;
            }
            continue;
        }

        if (kobutsu_ledger_is_shopee_payments_header($row) || kobutsu_ledger_csv_row_is_empty($row)) {
            $skipped++;
            continue;
        }

        $record = kobutsu_ledger_map_shopee_payment_row($headers, $row, $line_number);
        if ($record === null) {
            $skipped++;
            continue;
        }

        if (kobutsu_ledger_insert_payment_transaction($record)) {
            $imported++;
        } else {
            $skipped++;
        }
    }

    fclose($handle);

    if ($headers === []) {
        return [
            'ok' => false,
            'message' => 'Shopee payments CSV のヘッダー行を検出できませんでした。',
            'imported' => 0,
            'skipped' => $skipped,
        ];
    }

    return [
        'ok' => true,
        'message' => '',
        'imported' => $imported,
        'skipped' => $skipped,
    ];
}

function kobutsu_ledger_is_shopee_payments_header(array $row): bool
{
    $normalized = array_map('kobutsu_ledger_normalize_csv_header', $row);

    return in_array('order id', $normalized, true)
        && in_array('username (buyer)', $normalized, true)
        && in_array('order creation date', $normalized, true);
}

function kobutsu_ledger_csv_row_is_empty(array $row): bool
{
    foreach ($row as $value) {
        if (trim((string) $value) !== '') {
            return false;
        }
    }

    return true;
}

function kobutsu_ledger_map_shopee_payment_row(array $headers, array $row, int $line_number): ?array
{
    $raw_payload = [];
    foreach ($headers as $index => $header) {
        $key = trim((string) $header);
        if ($key === '') {
            continue;
        }
        $raw_payload[$key] = (string) ($row[$index] ?? '');
    }

    $order_no = kobutsu_ledger_csv_value($headers, $row, 'Order ID');
    if ($order_no === '') {
        return null;
    }

    $transaction_date = kobutsu_ledger_parse_csv_date(kobutsu_ledger_csv_value($headers, $row, 'Order Creation Date'));
    $payout_date = kobutsu_ledger_parse_csv_date(kobutsu_ledger_csv_value($headers, $row, 'Payout Completed Date'));
    $gross_amount = kobutsu_ledger_parse_csv_decimal(kobutsu_ledger_csv_value($headers, $row, 'Original Product Price'));
    $net_amount = kobutsu_ledger_parse_csv_decimal(kobutsu_ledger_csv_value($headers, $row, 'Total Released Amount'));

    $row_hash = hash('sha256', (string) wp_json_encode($raw_payload, JSON_UNESCAPED_UNICODE));
    $raw_payload['_source'] = 'shopee_payments_csv';
    $raw_payload['_line_number'] = $line_number;
    $raw_json = wp_json_encode($raw_payload, JSON_UNESCAPED_UNICODE);
    if ($raw_json === false) {
        $raw_json = '';
    }

    return [
        'transaction_date' => $transaction_date,
        'transaction_type' => 'shopee_payment',
        'order_no' => $order_no,
        'buyer_username' => kobutsu_ledger_csv_value($headers, $row, 'Username (Buyer)'),
        'buyer_name' => '',
        'net_amount' => $net_amount,
        'payout_currency' => 'PHP',
        'payout_date' => $payout_date,
        'payout_id' => '',
        'payout_method' => kobutsu_ledger_csv_value($headers, $row, 'Buyer Payment Method'),
        'payout_status' => $payout_date !== null ? 'completed' : '',
        'item_id_external' => '',
        'transaction_id_external' => '',
        'item_title' => null,
        'sku' => '',
        'quantity' => 0,
        'gross_transaction_amount' => $gross_amount,
        'transaction_currency' => 'PHP',
        'exchange_rate' => 0,
        'reference_id' => 'shopee:' . $row_hash,
        'description' => 'Shopee payments CSV',
        'raw_payload' => $raw_json,
    ];
}

function kobutsu_ledger_csv_value(array $headers, array $row, string $label): string
{
    $target = kobutsu_ledger_normalize_csv_header($label);
    foreach ($headers as $index => $header) {
        $normalized = kobutsu_ledger_normalize_csv_header((string) $header);
        if ($normalized === $target || str_starts_with($normalized, $target)) {
            return trim((string) ($row[$index] ?? ''));
        }
    }

    return '';
}

function kobutsu_ledger_normalize_csv_header(string $header): string
{
    $header = preg_replace('/\s+/u', ' ', trim($header));

    return strtolower((string) $header);
}

function kobutsu_ledger_parse_csv_date(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
        return null;
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return null;
    }

    return gmdate('Y-m-d', $timestamp);
}

function kobutsu_ledger_parse_csv_decimal(string $value): float
{
    $value = trim($value);
    if ($value === '') {
        return 0.0;
    }

    $value = str_replace([',', '₱', '¥', '$', ' '], '', $value);

    return is_numeric($value) ? (float) $value : 0.0;
}

function kobutsu_ledger_insert_payment_transaction(array $record): bool
{
    global $wpdb;

    $table = kobutsu_ledger_table('payment_transactions');
    $exists = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE reference_id = %s",
        $record['reference_id']
    ));
    if ($exists > 0) {
        return false;
    }

    $result = $wpdb->insert(
        $table,
        $record,
        [
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%f',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%f',
            '%s',
            '%f',
            '%s',
            '%s',
            '%s',
        ]
    );

    return $result !== false;
}

function kobutsu_ledger_get_recent_payment_transactions(int $limit = 50): array
{
    global $wpdb;

    $table = kobutsu_ledger_table('payment_transactions');

    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE transaction_type = %s ORDER BY id DESC LIMIT %d",
        'shopee_payment',
        $limit
    ), ARRAY_A);
}

function kobutsu_ledger_render_payments_admin_page(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('このページを表示する権限がありません。', 'kobutsu-ledger'));
    }

    $rows = kobutsu_ledger_get_recent_payment_transactions();
    ?>
    <div class="wrap">
        <h1>ペイメント</h1>

        <?php kobutsu_ledger_render_payments_admin_notice(); ?>

        <p>Shopee payments CSV を原票として取り込み、後続の精算補完で参照できる形で保存します。</p>

        <h2>Shopee payments CSV 取り込み</h2>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('kobutsu_import_shopee_payments'); ?>
            <input type="hidden" name="kobutsu_payments_action" value="import_shopee_payments">
            <input type="file" name="shopee_payments_csv" accept=".csv,text/csv" required>
            <?php submit_button('CSVを取り込む', 'primary', 'submit', false); ?>
        </form>

        <h2>直近の Shopee ペイメント原票</h2>
        <table class="widefat striped" style="max-width: 1200px;">
            <thead>
                <tr>
                    <th>注文ID</th>
                    <th>注文日</th>
                    <th>支払い完了日</th>
                    <th>購入者</th>
                    <th>販売額(PHP)</th>
                    <th>払出額(PHP)</th>
                    <th>支払方法</th>
                    <th>取込日時</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rows === []) : ?>
                    <tr>
                        <td colspan="8">Shopee ペイメント原票はまだ取り込まれていません。</td>
                    </tr>
                <?php else : ?>
                    <?php foreach ($rows as $row) : ?>
                        <tr>
                            <td><?php echo esc_html((string) $row['order_no']); ?></td>
                            <td><?php echo esc_html((string) $row['transaction_date']); ?></td>
                            <td><?php echo esc_html((string) $row['payout_date']); ?></td>
                            <td><?php echo esc_html((string) $row['buyer_username']); ?></td>
                            <td><?php echo esc_html(number_format((float) $row['gross_transaction_amount'], 2)); ?></td>
                            <td><?php echo esc_html(number_format((float) $row['net_amount'], 2)); ?></td>
                            <td><?php echo esc_html((string) $row['payout_method']); ?></td>
                            <td><?php echo esc_html((string) $row['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

function kobutsu_ledger_render_payments_admin_notice(): void
{
    $message = isset($_GET['kobutsu_message']) ? sanitize_key((string) wp_unslash($_GET['kobutsu_message'])) : '';
    if ($message === '') {
        return;
    }

    if ($message === 'import_success') {
        $imported = isset($_GET['imported']) ? (int) $_GET['imported'] : 0;
        $skipped = isset($_GET['skipped']) ? (int) $_GET['skipped'] : 0;
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(sprintf('Shopee payments CSV を取り込みました。保存 %d 件、スキップ %d 件。', $imported, $skipped))
        );
        return;
    }

    if ($message === 'import_failed') {
        $error = isset($_GET['error']) ? sanitize_text_field((string) wp_unslash($_GET['error'])) : '取り込みに失敗しました。';
        printf(
            '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
            esc_html($error)
        );
    }
}

function kobutsu_ledger_payments_admin_redirect(array $args = []): void
{
    wp_safe_redirect(add_query_arg(
        array_merge(['page' => 'kobutsu-payments'], $args),
        admin_url('admin.php')
    ));
    exit;
}
