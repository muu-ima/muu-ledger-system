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

if (!function_exists('kobutsu_ledger_shell_token_ttl')) {
    function kobutsu_ledger_shell_token_ttl(): int
    {
        return 10 * MINUTE_IN_SECONDS;
    }
}

if (!function_exists('kobutsu_ledger_shell_token_key')) {
    function kobutsu_ledger_shell_token_key(string $token): string
    {
        return 'kobutsu_shell_token_' . hash('sha256', $token);
    }
}

if (!function_exists('kobutsu_ledger_issue_shell_token')) {
    function kobutsu_ledger_issue_shell_token(): string
    {
        if (!is_user_logged_in() || !current_user_can('edit_posts')) {
            return '';
        }

        try {
            $token = bin2hex(random_bytes(32));
        } catch (Exception $exception) {
            return '';
        }

        set_transient(
            kobutsu_ledger_shell_token_key($token),
            [
                'issued_at' => time(),
                'user_id' => get_current_user_id(),
            ],
            kobutsu_ledger_shell_token_ttl()
        );

        return $token;
    }
}

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
        'restNonce' => kobutsu_ledger_issue_shell_token(),
        'wordpressOrigin' => kobutsu_ledger_shell_origin(home_url('/')),
        'canWrite' => current_user_can('edit_posts'),
    ];
}
