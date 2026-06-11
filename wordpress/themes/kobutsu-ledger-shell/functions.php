<?php
/**
 * Kobutsu Ledger Shell theme functions.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('after_setup_theme', function (): void {
    add_theme_support('title-tag');
});

function kobutsu_ledger_shell_default_frontend_url(): string
{
    if (defined('KOBUTSU_LEDGER_FRONTEND_URL')) {
        return (string) KOBUTSU_LEDGER_FRONTEND_URL;
    }

    return 'http://localhost:3000';
}

function kobutsu_ledger_shell_frontend_url(): string
{
    $url = apply_filters(
        'kobutsu_ledger_shell_frontend_url',
        kobutsu_ledger_shell_default_frontend_url()
    );

    return esc_url($url);
}

function kobutsu_ledger_shell_origin(string $url): string
{
    $scheme = wp_parse_url($url, PHP_URL_SCHEME);
    $host = wp_parse_url($url, PHP_URL_HOST);
    $port = wp_parse_url($url, PHP_URL_PORT);

    if (!$scheme || !$host) {
        return '';
    }

    return $scheme . '://' . $host . ($port ? ':' . $port : '');
}

function kobutsu_ledger_shell_auth_payload(): array
{
    return [
        'restBaseUrl' => esc_url_raw(home_url('/')),
        'restNonce' => is_user_logged_in() ? wp_create_nonce('wp_rest') : '',
        'wordpressOrigin' => kobutsu_ledger_shell_origin(home_url('/')),
        'canWrite' => current_user_can('edit_posts'),
    ];
}
