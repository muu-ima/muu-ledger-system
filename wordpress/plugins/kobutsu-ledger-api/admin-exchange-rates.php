<?php

if (!defined('ABSPATH')) {
    exit;
}

const KOBUTSU_LEDGER_EXCHANGE_RATE_API_KEY_OPTION = 'kobutsu_ledger_exchange_rate_api_key';
const KOBUTSU_LEDGER_EXCHANGE_RATE_API_SOURCE = 'exchangerate_api';
const KOBUTSU_LEDGER_EXCHANGE_RATE_MANUAL_SOURCE = 'manual';

add_action('admin_init', 'kobutsu_ledger_handle_exchange_rates_admin_action');

function kobutsu_ledger_exchange_rate_target_currencies(): array
{
    return ['USD', 'PHP', 'SGD', 'GBP', 'EUR', 'CAD', 'AUD', 'BRL'];
}

function kobutsu_ledger_exchange_rate_api_key(): string
{
    return trim((string) get_option(KOBUTSU_LEDGER_EXCHANGE_RATE_API_KEY_OPTION, ''));
}

function kobutsu_ledger_register_exchange_rates_admin_menu(): void
{
    add_menu_page(
        '為替レート',
        '為替レート',
        'edit_posts',
        'kobutsu-exchange-rates',
        'kobutsu_ledger_render_exchange_rates_admin_page',
        'dashicons-money-alt',
        29
    );
}

function kobutsu_ledger_handle_exchange_rates_admin_action(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['kobutsu_exchange_rate_action'])) {
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('この操作を実行する権限がありません。', 'kobutsu-ledger'));
    }

    $action = sanitize_key((string) wp_unslash($_POST['kobutsu_exchange_rate_action']));

    if ($action === 'save_settings') {
        check_admin_referer('kobutsu_exchange_rate_settings');
        kobutsu_ledger_save_exchange_rate_settings();
        kobutsu_ledger_exchange_rates_admin_redirect(['kobutsu_message' => 'settings_saved']);
    }

    if ($action === 'fetch_today') {
        check_admin_referer('kobutsu_exchange_rate_fetch_today');
        $result = kobutsu_ledger_fetch_exchange_rates_for_date(current_time('Y-m-d'));
        kobutsu_ledger_exchange_rates_admin_redirect(kobutsu_ledger_exchange_rate_result_args($result));
    }

    if ($action === 'save_manual_rate') {
        check_admin_referer('kobutsu_exchange_rate_manual');
        $result = kobutsu_ledger_save_manual_exchange_rate();
        kobutsu_ledger_exchange_rates_admin_redirect(kobutsu_ledger_exchange_rate_result_args($result));
    }
}

function kobutsu_ledger_save_exchange_rate_settings(): void
{
    $should_clear = !empty($_POST['clear_api_key']);
    $api_key = isset($_POST['api_key']) ? trim((string) wp_unslash($_POST['api_key'])) : '';

    if ($should_clear) {
        delete_option(KOBUTSU_LEDGER_EXCHANGE_RATE_API_KEY_OPTION);
        return;
    }

    if ($api_key !== '') {
        update_option(KOBUTSU_LEDGER_EXCHANGE_RATE_API_KEY_OPTION, sanitize_text_field($api_key), false);
    }
}

function kobutsu_ledger_fetch_exchange_rates_for_date(string $rate_date): array
{
    $api_key = kobutsu_ledger_exchange_rate_api_key();
    if ($api_key === '') {
        return [
            'ok' => false,
            'date' => $rate_date,
            'message' => 'ExchangeRate-API key が未設定です。',
            'saved' => 0,
            'skipped' => 0,
        ];
    }

    $url = sprintf(
        'https://v6.exchangerate-api.com/v6/%s/latest/USD',
        rawurlencode($api_key)
    );
    $response = wp_remote_get($url, [
        'timeout' => 15,
        'headers' => [
            'Accept' => 'application/json',
        ],
    ]);

    if (is_wp_error($response)) {
        return [
            'ok' => false,
            'date' => $rate_date,
            'message' => $response->get_error_message(),
            'saved' => 0,
            'skipped' => 0,
        ];
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    if ($status_code !== 200 || !is_array($body) || ($body['result'] ?? '') !== 'success') {
        $error_type = is_array($body) ? (string) ($body['error-type'] ?? '') : '';
        return [
            'ok' => false,
            'date' => $rate_date,
            'message' => $error_type !== '' ? $error_type : '為替レートを取得できませんでした。',
            'saved' => 0,
            'skipped' => 0,
        ];
    }

    $rates = is_array($body['conversion_rates'] ?? null) ? $body['conversion_rates'] : [];
    $usd_jpy = (float) ($rates['JPY'] ?? 0);
    if ($usd_jpy <= 0) {
        return [
            'ok' => false,
            'date' => $rate_date,
            'message' => 'JPY レートがレスポンスに含まれていません。',
            'saved' => 0,
            'skipped' => 0,
        ];
    }

    $saved = 0;
    $skipped = 0;
    foreach (kobutsu_ledger_exchange_rate_target_currencies() as $currency) {
        $currency_per_usd = $currency === 'USD' ? 1.0 : (float) ($rates[$currency] ?? 0);
        if ($currency_per_usd <= 0) {
            $skipped++;
            continue;
        }

        $rate_jpy = $usd_jpy / $currency_per_usd;
        if (kobutsu_ledger_upsert_exchange_rate(
            $rate_date,
            $currency,
            'JPY',
            $rate_jpy,
            KOBUTSU_LEDGER_EXCHANGE_RATE_API_SOURCE,
            current_time('mysql'),
            false,
            ''
        )) {
            $saved++;
        } else {
            $skipped++;
        }
    }

    return [
        'ok' => true,
        'date' => $rate_date,
        'message' => '',
        'saved' => $saved,
        'skipped' => $skipped,
    ];
}

function kobutsu_ledger_upsert_exchange_rate(
    string $rate_date,
    string $base_currency,
    string $quote_currency,
    float $rate,
    string $source,
    string $fetched_at,
    bool $is_manual_override = false,
    string $notes = ''
): bool {
    global $wpdb;

    $table = kobutsu_ledger_table('exchange_rates');
    $base_currency = strtoupper(substr($base_currency, 0, 3));
    $quote_currency = strtoupper(substr($quote_currency, 0, 3));
    $source = sanitize_key($source);
    $now = current_time('mysql');

    $existing = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, is_manual_override FROM $table
            WHERE rate_date = %s AND base_currency = %s AND quote_currency = %s AND source = %s",
            $rate_date,
            $base_currency,
            $quote_currency,
            $source
        ),
        ARRAY_A
    );

    if ($existing && (int) $existing['is_manual_override'] === 1 && !$is_manual_override) {
        return false;
    }

    $data = [
        'rate_date' => $rate_date,
        'currency_code' => $base_currency,
        'rate_jpy' => $rate,
        'base_currency' => $base_currency,
        'quote_currency' => $quote_currency,
        'rate' => $rate,
        'source' => $source,
        'is_manual_override' => $is_manual_override ? 1 : 0,
        'fetched_at' => $fetched_at,
        'notes' => $notes,
        'updated_at' => $now,
    ];

    if ($existing) {
        return $wpdb->update(
            $table,
            $data,
            ['id' => (int) $existing['id']],
            ['%s', '%s', '%f', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%s'],
            ['%d']
        ) !== false;
    }

    $data['created_at'] = $now;

    return $wpdb->insert(
        $table,
        $data,
        ['%s', '%s', '%f', '%s', '%s', '%f', '%s', '%d', '%s', '%s', '%s', '%s']
    ) !== false;
}

function kobutsu_ledger_save_manual_exchange_rate(): array
{
    $rate_date = isset($_POST['rate_date']) ? sanitize_text_field((string) wp_unslash($_POST['rate_date'])) : '';
    $base_currency = isset($_POST['base_currency'])
        ? strtoupper(substr(sanitize_text_field((string) wp_unslash($_POST['base_currency'])), 0, 3))
        : '';
    $quote_currency = isset($_POST['quote_currency'])
        ? strtoupper(substr(sanitize_text_field((string) wp_unslash($_POST['quote_currency'])), 0, 3))
        : 'JPY';
    $rate = isset($_POST['rate']) ? (float) wp_unslash($_POST['rate']) : 0.0;
    $notes = isset($_POST['notes']) ? sanitize_textarea_field((string) wp_unslash($_POST['notes'])) : '';

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rate_date)) {
        return [
            'ok' => false,
            'message' => '日付は YYYY-MM-DD 形式で入力してください。',
            'saved' => 0,
            'skipped' => 0,
        ];
    }

    if (!preg_match('/^[A-Z]{3}$/', $base_currency) || !preg_match('/^[A-Z]{3}$/', $quote_currency)) {
        return [
            'ok' => false,
            'message' => '通貨コードは3文字で入力してください。',
            'saved' => 0,
            'skipped' => 0,
        ];
    }

    if ($rate <= 0) {
        return [
            'ok' => false,
            'message' => 'レートは0より大きい値を入力してください。',
            'saved' => 0,
            'skipped' => 0,
        ];
    }

    $saved = kobutsu_ledger_upsert_exchange_rate(
        $rate_date,
        $base_currency,
        $quote_currency,
        $rate,
        KOBUTSU_LEDGER_EXCHANGE_RATE_MANUAL_SOURCE,
        current_time('mysql'),
        true,
        $notes
    );

    return [
        'ok' => $saved,
        'date' => $rate_date,
        'message' => $saved ? '' : '手入力レートを保存できませんでした。',
        'saved' => $saved ? 1 : 0,
        'skipped' => $saved ? 0 : 1,
    ];
}

function kobutsu_ledger_exchange_rate_result_args(array $result): array
{
    if (!($result['ok'] ?? false)) {
        return [
            'kobutsu_message' => 'fetch_failed',
            'kobutsu_error' => sanitize_text_field((string) ($result['message'] ?? '')),
        ];
    }

    return [
        'kobutsu_message' => 'fetch_success',
        'rate_date' => sanitize_text_field((string) ($result['date'] ?? '')),
        'saved' => absint($result['saved'] ?? 0),
        'skipped' => absint($result['skipped'] ?? 0),
    ];
}

function kobutsu_ledger_exchange_rates_admin_redirect(array $args): void
{
    wp_safe_redirect(add_query_arg(array_merge(['page' => 'kobutsu-exchange-rates'], $args), admin_url('admin.php')));
    exit;
}

function kobutsu_ledger_admin_get_recent_exchange_rates(): array
{
    global $wpdb;

    return $wpdb->get_results(
        "SELECT rate_date, base_currency, quote_currency, rate, source, is_manual_override, fetched_at
        FROM " . kobutsu_ledger_table('exchange_rates') . "
        ORDER BY rate_date DESC, base_currency ASC, source ASC
        LIMIT 80",
        ARRAY_A
    ) ?: [];
}

function kobutsu_ledger_render_exchange_rates_admin_page(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('このページにアクセスする権限がありません。', 'kobutsu-ledger'));
    }

    $message = isset($_GET['kobutsu_message']) ? sanitize_key($_GET['kobutsu_message']) : '';
    $error = isset($_GET['kobutsu_error']) ? sanitize_text_field((string) wp_unslash($_GET['kobutsu_error'])) : '';
    $api_key_is_set = kobutsu_ledger_exchange_rate_api_key() !== '';
    $rows = kobutsu_ledger_admin_get_recent_exchange_rates();
    $fetch_button_attrs = $api_key_is_set ? [] : ['disabled' => 'disabled'];
    ?>
    <div class="wrap kobutsu-ledger-admin">
        <h1>為替レート</h1>
        <p>ExchangeRate-API から当日分を取得し、後続の損益計算で参照できる形で保存します。</p>

        <?php if ($message === 'settings_saved') : ?>
            <div class="notice notice-success is-dismissible"><p>設定を保存しました。</p></div>
        <?php elseif ($message === 'fetch_success') : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php echo esc_html((string) ($_GET['rate_date'] ?? '')); ?> の為替を保存しました。
                    保存 <?php echo esc_html((string) absint($_GET['saved'] ?? 0)); ?> 件、
                    スキップ <?php echo esc_html((string) absint($_GET['skipped'] ?? 0)); ?> 件。
                </p>
            </div>
        <?php elseif ($message === 'fetch_failed') : ?>
            <div class="notice notice-error is-dismissible"><p><?php echo esc_html($error ?: '為替レートを取得できませんでした。'); ?></p></div>
        <?php endif; ?>

        <h2>ExchangeRate-API 設定</h2>
        <form method="post" style="max-width: 760px; margin-bottom: 24px;">
            <?php wp_nonce_field('kobutsu_exchange_rate_settings'); ?>
            <input type="hidden" name="kobutsu_exchange_rate_action" value="save_settings">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="kobutsu_exchange_rate_api_key">API key</label></th>
                        <td>
                            <input
                                id="kobutsu_exchange_rate_api_key"
                                name="api_key"
                                type="password"
                                class="regular-text code"
                                autocomplete="off"
                                placeholder="<?php echo esc_attr($api_key_is_set ? '設定済み。変更時のみ入力' : '未設定'); ?>"
                            >
                            <p class="description">空欄のまま保存すると、現在の API key を維持します。</p>
                            <?php if ($api_key_is_set) : ?>
                                <label>
                                    <input type="checkbox" name="clear_api_key" value="1">
                                    API key を削除する
                                </label>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button('設定を保存'); ?>
        </form>

        <h2>手動取得</h2>
        <form method="post" style="margin-bottom: 24px;">
            <?php wp_nonce_field('kobutsu_exchange_rate_fetch_today'); ?>
            <input type="hidden" name="kobutsu_exchange_rate_action" value="fetch_today">
            <p>取得対象: <?php echo esc_html(implode(', ', kobutsu_ledger_exchange_rate_target_currencies())); ?> / JPY</p>
            <?php submit_button('今日の為替を取得', 'primary', 'submit', false, $fetch_button_attrs); ?>
        </form>

        <h2>手入力補完</h2>
        <form method="post" style="max-width: 960px; margin-bottom: 24px;">
            <?php wp_nonce_field('kobutsu_exchange_rate_manual'); ?>
            <input type="hidden" name="kobutsu_exchange_rate_action" value="save_manual_rate">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><label for="kobutsu_manual_rate_date">日付</label></th>
                        <td><input id="kobutsu_manual_rate_date" name="rate_date" type="date" value="<?php echo esc_attr(current_time('Y-m-d')); ?>" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kobutsu_manual_base_currency">通貨ペア</label></th>
                        <td>
                            <input id="kobutsu_manual_base_currency" name="base_currency" type="text" maxlength="3" size="4" value="USD" required>
                            /
                            <input name="quote_currency" type="text" maxlength="3" size="4" value="JPY" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kobutsu_manual_rate">レート</label></th>
                        <td><input id="kobutsu_manual_rate" name="rate" type="number" step="0.000001" min="0" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="kobutsu_manual_notes">メモ</label></th>
                        <td>
                            <textarea id="kobutsu_manual_notes" name="notes" rows="2" class="large-text" placeholder="例: みずほCSVから補完、月末確認など"></textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php submit_button('手入力レートを保存'); ?>
        </form>

        <h2>直近の保存レート</h2>
        <table class="widefat striped" style="max-width: 960px;">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>通貨ペア</th>
                    <th>レート</th>
                    <th>取得元</th>
                    <th>手入力固定</th>
                    <th>取得日時</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows) : ?>
                    <tr><td colspan="6">為替レートはまだ保存されていません。</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $row) : ?>
                    <tr>
                        <td><?php echo esc_html((string) $row['rate_date']); ?></td>
                        <td><?php echo esc_html((string) $row['base_currency'] . '/' . (string) $row['quote_currency']); ?></td>
                        <td><?php echo esc_html(number_format((float) $row['rate'], 6)); ?></td>
                        <td><?php echo esc_html((string) $row['source']); ?></td>
                        <td><?php echo (int) $row['is_manual_override'] === 1 ? 'はい' : 'いいえ'; ?></td>
                        <td><?php echo esc_html((string) $row['fetched_at']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
