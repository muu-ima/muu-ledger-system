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
