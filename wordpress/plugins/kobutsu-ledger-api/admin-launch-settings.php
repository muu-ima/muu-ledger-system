<?php

if (!defined('ABSPATH')) {
    exit;
}

const KOBUTSU_LEDGER_FRONTEND_URL_OPTION = 'kobutsu_ledger_frontend_url';

add_action('admin_init', 'kobutsu_ledger_register_launch_settings');
add_filter('kobutsu_ledger_shell_frontend_url', 'kobutsu_ledger_filter_shell_frontend_url');

function kobutsu_ledger_default_frontend_url(): string
{
    if (defined('KOBUTSU_LEDGER_FRONTEND_URL')) {
        return (string) KOBUTSU_LEDGER_FRONTEND_URL;
    }

    return 'http://localhost:3000';
}

function kobutsu_ledger_frontend_url(): string
{
    $configured_url = (string) get_option(
        KOBUTSU_LEDGER_FRONTEND_URL_OPTION,
        kobutsu_ledger_default_frontend_url()
    );

    return esc_url_raw($configured_url);
}

function kobutsu_ledger_filter_shell_frontend_url(string $default_url): string
{
    $configured_url = kobutsu_ledger_frontend_url();

    return $configured_url !== '' ? $configured_url : $default_url;
}

function kobutsu_ledger_allowed_rest_origins(): array
{
    $origins = [
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:8081',
        'http://127.0.0.1:8081',
    ];
    $frontend_origin = wp_parse_url(kobutsu_ledger_frontend_url(), PHP_URL_SCHEME);
    $frontend_host = wp_parse_url(kobutsu_ledger_frontend_url(), PHP_URL_HOST);
    $frontend_port = wp_parse_url(kobutsu_ledger_frontend_url(), PHP_URL_PORT);

    if ($frontend_origin && $frontend_host) {
        $origins[] = $frontend_origin . '://' . $frontend_host . ($frontend_port ? ':' . $frontend_port : '');
    }

    $site_origin = wp_parse_url(site_url(), PHP_URL_SCHEME);
    $site_host = wp_parse_url(site_url(), PHP_URL_HOST);
    $site_port = wp_parse_url(site_url(), PHP_URL_PORT);

    if ($site_origin && $site_host) {
        $origins[] = $site_origin . '://' . $site_host . ($site_port ? ':' . $site_port : '');
    }

    return array_values(array_unique(array_filter($origins)));
}

function kobutsu_ledger_register_launch_settings(): void
{
    register_setting('kobutsu_ledger_launch', KOBUTSU_LEDGER_FRONTEND_URL_OPTION, [
        'default' => kobutsu_ledger_default_frontend_url(),
        'sanitize_callback' => 'esc_url_raw',
        'type' => 'string',
    ]);
}

function kobutsu_ledger_register_launch_settings_menu(): void
{
    add_submenu_page(
        'kobutsu-ledger',
        '起動設定',
        '起動設定',
        'edit_posts',
        'kobutsu-launch-settings',
        'kobutsu_ledger_render_launch_settings_page'
    );
}

function kobutsu_ledger_render_launch_settings_page(): void
{
    if (!current_user_can('edit_posts')) {
        wp_die(esc_html__('このページにアクセスする権限がありません。', 'kobutsu-ledger'));
    }

    $frontend_url = kobutsu_ledger_frontend_url();
    ?>
    <div class="wrap kobutsu-ledger-admin">
        <h1>起動設定</h1>
        <p>WordPress 側は起動入口を担い、実際の UI はフロントエンド URL へ委譲します。</p>

        <form method="post" action="options.php">
            <?php settings_fields('kobutsu_ledger_launch'); ?>

            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="kobutsu_ledger_frontend_url">Frontend URL</label>
                        </th>
                        <td>
                            <input
                                id="kobutsu_ledger_frontend_url"
                                name="<?php echo esc_attr(KOBUTSU_LEDGER_FRONTEND_URL_OPTION); ?>"
                                type="url"
                                class="regular-text code"
                                value="<?php echo esc_attr($frontend_url); ?>"
                            >
                            <p class="description">例: http://localhost:3000</p>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button('保存'); ?>
        </form>
    </div>
    <?php
}
