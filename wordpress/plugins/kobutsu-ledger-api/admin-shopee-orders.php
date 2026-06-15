<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_init', 'kobutsu_ledger_handle_shopee_orders_admin_action');

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

function kobutsu_ledger_handle_shopee_orders_admin_action(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['kobutsu_shopee_orders_action'])) {
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('この操作を実行する権限がありません。', 'kobutsu-ledger'));
    }

    $action = sanitize_key((string) wp_unslash($_POST['kobutsu_shopee_orders_action']));
    if ($action !== 'import_shopee_orders') {
        return;
    }

    check_admin_referer('kobutsu_import_shopee_orders');

    $result = kobutsu_ledger_import_shopee_orders_upload();
    kobutsu_ledger_shopee_orders_admin_redirect([
        'kobutsu_message' => !empty($result['ok']) ? 'import_success' : 'import_failed',
        'order_tab' => 'history',
        'imported' => (string) (int) ($result['imported'] ?? 0),
        'skipped' => (string) (int) ($result['skipped'] ?? 0),
        'skip_header' => (string) (int) ($result['skip_reasons']['header'] ?? 0),
        'skip_empty' => (string) (int) ($result['skip_reasons']['empty'] ?? 0),
        'skip_note' => (string) (int) ($result['skip_reasons']['note'] ?? 0),
        'skip_missing_order' => (string) (int) ($result['skip_reasons']['missing_order_id'] ?? 0),
        'skip_duplicate' => (string) (int) ($result['skip_reasons']['duplicate'] ?? 0),
        'skip_db_error' => (string) (int) ($result['skip_reasons']['db_error'] ?? 0),
        'error' => (string) ($result['message'] ?? ''),
    ]);
}

function kobutsu_ledger_import_shopee_orders_upload(): array
{
    if (empty($_FILES['shopee_orders_csv']) || !is_array($_FILES['shopee_orders_csv'])) {
        return [
            'ok' => false,
            'message' => 'CSVファイルが選択されていません。',
            'imported' => 0,
            'skipped' => 0,
        ];
    }

    $file = $_FILES['shopee_orders_csv'];
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

    $result = kobutsu_ledger_import_shopee_orders_csv($tmp_name);
    kobutsu_ledger_save_shopee_order_import_batch($result, (string) ($file['name'] ?? ''));

    return $result;
}

function kobutsu_ledger_import_shopee_orders_csv(string $file_path): array
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
    $skip_reasons = kobutsu_ledger_empty_shopee_order_skip_reasons();
    $line_number = 0;

    while (($row = fgetcsv($handle)) !== false) {
        $line_number++;
        if ($headers === []) {
            if (kobutsu_ledger_is_shopee_orders_header($row)) {
                $headers = $row;
            }
            continue;
        }

        if (kobutsu_ledger_is_shopee_orders_header($row)) {
            $skip_reasons['header']++;
            continue;
        }

        if (kobutsu_ledger_csv_row_is_empty($row)) {
            $skip_reasons['empty']++;
            continue;
        }

        if (kobutsu_ledger_is_shopee_orders_note_row($row)) {
            $skip_reasons['note']++;
            continue;
        }

        $record = kobutsu_ledger_map_shopee_order_row($headers, $row, $line_number);
        if ($record === null) {
            $skip_reasons['missing_order_id']++;
            continue;
        }

        $insert_result = kobutsu_ledger_insert_shopee_order($record);
        if ($insert_result === 'inserted') {
            $imported++;
        } elseif ($insert_result === 'duplicate') {
            $skip_reasons['duplicate']++;
        } else {
            $skip_reasons['db_error']++;
        }
    }

    fclose($handle);

    if ($headers === []) {
        return [
            'ok' => false,
            'message' => 'Shopee orders CSV のヘッダー行を検出できませんでした。',
            'imported' => 0,
            'skipped' => kobutsu_ledger_total_shopee_order_skips($skip_reasons),
            'skip_reasons' => $skip_reasons,
        ];
    }

    return [
        'ok' => true,
        'message' => '',
        'imported' => $imported,
        'skipped' => kobutsu_ledger_total_shopee_order_skips($skip_reasons),
        'skip_reasons' => $skip_reasons,
    ];
}

function kobutsu_ledger_empty_shopee_order_skip_reasons(): array
{
    return [
        'header' => 0,
        'empty' => 0,
        'note' => 0,
        'missing_order_id' => 0,
        'duplicate' => 0,
        'db_error' => 0,
    ];
}

function kobutsu_ledger_total_shopee_order_skips(array $skip_reasons): int
{
    return array_sum(array_map('intval', $skip_reasons));
}

function kobutsu_ledger_is_shopee_orders_header(array $row): bool
{
    $normalized = array_map('kobutsu_ledger_normalize_csv_header', $row);

    return in_array('order id', $normalized, true)
        && in_array('order status', $normalized, true)
        && in_array('order creation date', $normalized, true)
        && (
            in_array('sku reference no.', $normalized, true)
            || in_array('sku reference no', $normalized, true)
        );
}

function kobutsu_ledger_is_shopee_orders_note_row(array $row): bool
{
    $first_value = trim((string) ($row[0] ?? ''));
    if ($first_value === '') {
        return false;
    }

    $order_no = preg_match('/^[A-Za-z0-9_-]{8,}$/', $first_value) === 1;
    if ($order_no) {
        return false;
    }

    foreach (array_slice($row, 1) as $value) {
        if (trim((string) $value) !== '') {
            return false;
        }
    }

    return true;
}

function kobutsu_ledger_map_shopee_order_row(array $headers, array $row, int $line_number): ?array
{
    $raw_payload = [];
    foreach ($headers as $index => $header) {
        $key = trim((string) $header);
        if ($key === '') {
            continue;
        }
        $raw_payload[$key] = (string) ($row[$index] ?? '');
    }

    $order_no = kobutsu_ledger_csv_value_any($headers, $row, ['Order ID', 'Order No.', 'Order Number']);
    if ($order_no === '') {
        return null;
    }

    $country = strtoupper(substr(kobutsu_ledger_csv_value_any($headers, $row, ['Country']), 0, 2));
    $currency = kobutsu_ledger_detect_shopee_order_currency($headers, $country);
    $row_hash = hash('sha256', (string) wp_json_encode($raw_payload, JSON_UNESCAPED_UNICODE));
    $raw_payload['_source'] = 'shopee_orders_csv';
    $raw_payload['_line_number'] = $line_number;
    $raw_json = wp_json_encode($raw_payload, JSON_UNESCAPED_UNICODE);
    if ($raw_json === false) {
        $raw_json = '';
    }

    return [
        'order_no' => $order_no,
        'order_status' => kobutsu_ledger_csv_value_any($headers, $row, ['Order Status', 'Status']),
        'order_created_at' => kobutsu_ledger_parse_csv_datetime(kobutsu_ledger_csv_value_any($headers, $row, ['Order Creation Date', 'Order Created Time'])),
        'order_paid_at' => kobutsu_ledger_parse_csv_datetime(kobutsu_ledger_csv_value_any($headers, $row, ['Order Paid Time', 'Order Paid Date', 'Paid Time'])),
        'order_completed_at' => kobutsu_ledger_parse_csv_datetime(kobutsu_ledger_csv_value_any($headers, $row, ['Order Complete Time', 'Order Completed Time'])),
        'ship_time' => kobutsu_ledger_parse_csv_datetime(kobutsu_ledger_csv_value_any($headers, $row, ['Ship Time'])),
        'estimated_ship_out_at' => kobutsu_ledger_parse_csv_datetime(kobutsu_ledger_csv_value_any($headers, $row, ['Estimated Ship Out Date', 'Estimated Ship Out Time'])),
        'buyer_username' => kobutsu_ledger_csv_value_any($headers, $row, ['Username (Buyer)', 'Buyer Username']),
        'country' => $country,
        'parent_sku' => kobutsu_ledger_csv_value_any($headers, $row, ['Parent SKU Reference No.', 'Parent SKU Reference No', 'Parent SKU']),
        'sku' => kobutsu_ledger_csv_value_any($headers, $row, ['SKU Reference No.', 'SKU Reference No', 'SKU']),
        'product_name' => kobutsu_ledger_csv_value_any($headers, $row, ['Product Name', 'Item Name']),
        'variation_name' => kobutsu_ledger_csv_value_any($headers, $row, ['Variation Name', 'Variation']),
        'quantity' => (int) kobutsu_ledger_parse_csv_decimal(kobutsu_ledger_csv_value_any($headers, $row, ['Quantity'])),
        'returned_quantity' => (int) kobutsu_ledger_parse_csv_decimal(kobutsu_ledger_csv_value_any($headers, $row, ['Returned quantity', 'Returned Quantity'])),
        'gross_amount' => kobutsu_ledger_parse_csv_decimal(kobutsu_ledger_csv_value_any($headers, $row, ['Product Subtotal', 'Original Price', 'Deal Price'])),
        'total_amount' => kobutsu_ledger_parse_csv_decimal(kobutsu_ledger_csv_value_any($headers, $row, ["Products' Price Paid by Buyer", "Products' Price Paid by Buyer ($currency)"])),
        'grand_total' => kobutsu_ledger_parse_csv_decimal(kobutsu_ledger_csv_value_any($headers, $row, ['Grand Total'])),
        'currency' => $currency,
        'tracking_number' => kobutsu_ledger_csv_value_any($headers, $row, ['Tracking Number*', 'Tracking Number']),
        'shipping_option' => kobutsu_ledger_csv_value_any($headers, $row, ['Shipping Option']),
        'shipment_method' => kobutsu_ledger_csv_value_any($headers, $row, ['Shipment Method']),
        'cancel_reason' => kobutsu_ledger_csv_value_any($headers, $row, ['Cancel reason', 'Cancel Reason']),
        'return_refund_status' => kobutsu_ledger_csv_value_any($headers, $row, ['Return / Refund Status', 'Return/Refund Status']),
        'reference_id' => 'shopee_order:' . $row_hash,
        'source_line_number' => $line_number,
        'raw_payload' => $raw_json,
    ];
}

function kobutsu_ledger_csv_value_any(array $headers, array $row, array $labels): string
{
    foreach ($labels as $label) {
        $value = kobutsu_ledger_csv_value($headers, $row, $label);
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

function kobutsu_ledger_parse_csv_datetime(string $value): ?string
{
    $value = trim($value);
    if ($value === '' || $value === '-') {
        return null;
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return null;
    }

    return gmdate('Y-m-d H:i:s', $timestamp);
}

function kobutsu_ledger_detect_shopee_order_currency(array $headers, string $country = ''): string
{
    foreach ($headers as $header) {
        if (preg_match('/\(([A-Z]{3})\)/', (string) $header, $matches) === 1) {
            return $matches[1];
        }
    }

    $country_currency_map = [
        'PH' => 'PHP',
        'SG' => 'SGD',
    ];

    if (isset($country_currency_map[$country])) {
        return $country_currency_map[$country];
    }

    return '';
}

function kobutsu_ledger_insert_shopee_order(array $record): string
{
    global $wpdb;

    $table = kobutsu_ledger_table('shopee_orders');
    $exists = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table WHERE reference_id = %s",
        $record['reference_id']
    ));
    if ($exists > 0) {
        return 'duplicate';
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
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%d',
            '%f',
            '%f',
            '%f',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            '%s',
        ]
    );

    return $result !== false ? 'inserted' : 'db_error';
}

function kobutsu_ledger_save_shopee_order_import_batch(array $result, string $filename): void
{
    global $wpdb;

    $table = kobutsu_ledger_table('import_batches');
    $notes = wp_json_encode([
        'source' => 'shopee_orders_csv',
        'message' => (string) ($result['message'] ?? ''),
        'skip_reasons' => is_array($result['skip_reasons'] ?? null) ? $result['skip_reasons'] : [],
    ], JSON_UNESCAPED_UNICODE);

    $wpdb->insert(
        $table,
        [
            'source_name' => 'shopee_orders',
            'original_filename' => sanitize_file_name($filename),
            'status' => !empty($result['ok']) ? 'completed' : 'failed',
            'imported_rows' => (int) ($result['imported'] ?? 0),
            'error_rows' => (int) ($result['skipped'] ?? 0),
            'notes' => $notes !== false ? $notes : '',
            'completed_at' => current_time('mysql'),
        ],
        [
            '%s',
            '%s',
            '%s',
            '%d',
            '%d',
            '%s',
            '%s',
        ]
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

        <?php kobutsu_ledger_render_shopee_orders_admin_notice(); ?>

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
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('kobutsu_import_shopee_orders'); ?>
        <input type="hidden" name="kobutsu_shopee_orders_action" value="import_shopee_orders">
        <input type="file" name="shopee_orders_csv" accept=".csv,text/csv" required>
        <?php submit_button('CSVを取り込む', 'primary', 'submit', false); ?>
    </form>
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
                <th>スキップ内訳</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($batches === []) : ?>
                <tr>
                    <td colspan="6">取り込み履歴はまだありません。</td>
                </tr>
            <?php else : ?>
                <?php foreach ($batches as $batch) : ?>
                    <tr>
                        <td><?php echo esc_html((string) ($batch['completed_at'] ?: $batch['created_at'])); ?></td>
                        <td><?php echo esc_html((string) $batch['original_filename']); ?></td>
                        <td><?php echo esc_html((string) $batch['status']); ?></td>
                        <td><?php echo esc_html(number_format((int) $batch['imported_rows'])); ?></td>
                        <td><?php echo esc_html(number_format((int) $batch['error_rows'])); ?></td>
                        <td><?php echo esc_html(kobutsu_ledger_shopee_order_import_batch_skip_details($batch)); ?></td>
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

function kobutsu_ledger_shopee_order_import_batch_skip_details(array $batch): string
{
    $notes = json_decode((string) ($batch['notes'] ?? ''), true);
    if (!is_array($notes) || !is_array($notes['skip_reasons'] ?? null)) {
        return '';
    }

    return kobutsu_ledger_format_shopee_order_skip_details($notes['skip_reasons']);
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

function kobutsu_ledger_render_shopee_orders_admin_notice(): void
{
    $message = isset($_GET['kobutsu_message']) ? sanitize_key((string) wp_unslash($_GET['kobutsu_message'])) : '';
    if ($message === '') {
        return;
    }

    if ($message === 'import_success') {
        $imported = isset($_GET['imported']) ? (int) $_GET['imported'] : 0;
        $skipped = isset($_GET['skipped']) ? (int) $_GET['skipped'] : 0;
        $skip_details = kobutsu_ledger_shopee_order_skip_details_from_request();
        printf(
            '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
            esc_html(sprintf(
                'Shopee orders CSV を取り込みました。保存 %d 件、スキップ %d 件。%s',
                $imported,
                $skipped,
                $skip_details !== '' ? ' 内訳: ' . $skip_details : ''
            ))
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

function kobutsu_ledger_shopee_order_skip_details_from_request(): string
{
    $details = [
        'header' => isset($_GET['skip_header']) ? (int) $_GET['skip_header'] : 0,
        'empty' => isset($_GET['skip_empty']) ? (int) $_GET['skip_empty'] : 0,
        'note' => isset($_GET['skip_note']) ? (int) $_GET['skip_note'] : 0,
        'missing_order_id' => isset($_GET['skip_missing_order']) ? (int) $_GET['skip_missing_order'] : 0,
        'duplicate' => isset($_GET['skip_duplicate']) ? (int) $_GET['skip_duplicate'] : 0,
        'db_error' => isset($_GET['skip_db_error']) ? (int) $_GET['skip_db_error'] : 0,
    ];

    return kobutsu_ledger_format_shopee_order_skip_details($details);
}

function kobutsu_ledger_format_shopee_order_skip_details(array $details): string
{
    $labels = [
        'header' => 'ヘッダー行',
        'empty' => '空行',
        'note' => 'メモ行',
        'missing_order_id' => '注文IDなし',
        'duplicate' => '重複',
        'db_error' => 'DB保存失敗',
    ];
    $parts = [];
    foreach ($labels as $key => $label) {
        $count = (int) ($details[$key] ?? 0);
        if ($count > 0) {
            $parts[] = sprintf('%s %d 件', $label, $count);
        }
    }

    return implode('、', $parts);
}

function kobutsu_ledger_shopee_orders_admin_redirect(array $args = []): void
{
    wp_safe_redirect(add_query_arg(
        array_merge(['page' => 'kobutsu-shopee-orders'], $args),
        admin_url('admin.php')
    ));
    exit;
}
