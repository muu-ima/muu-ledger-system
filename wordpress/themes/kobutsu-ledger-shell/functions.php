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
    $url = get_theme_mod('kobutsu_ledger_frontend_url', kobutsu_ledger_shell_default_frontend_url());

    return esc_url($url);
}

add_action('customize_register', function (WP_Customize_Manager $wp_customize): void {
    $wp_customize->add_section('kobutsu_ledger_shell', [
        'title' => __('Kobutsu Ledger', 'kobutsu-ledger-shell'),
        'priority' => 30,
    ]);

    $wp_customize->add_setting('kobutsu_ledger_frontend_url', [
        'default' => kobutsu_ledger_shell_default_frontend_url(),
        'sanitize_callback' => 'esc_url_raw',
    ]);

    $wp_customize->add_control('kobutsu_ledger_frontend_url', [
        'label' => __('Frontend URL', 'kobutsu-ledger-shell'),
        'section' => 'kobutsu_ledger_shell',
        'type' => 'url',
    ]);
});
