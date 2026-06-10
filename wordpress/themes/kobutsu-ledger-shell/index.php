<?php
/**
 * Shell view for the Kobutsu Ledger frontend.
 */

if (!defined('ABSPATH')) {
    exit;
}

$frontend_url = kobutsu_ledger_shell_frontend_url();
$launch_settings_url = current_user_can('edit_posts')
    ? admin_url('admin.php?page=kobutsu-launch-settings')
    : '';
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
    <style>
        body {
            overflow: hidden;
        }

        .kobutsu-shell {
            width: 100vw;
            height: 100vh;
            border: 0;
            display: block;
            background: #fff;
        }

        .kobutsu-shell-fallback {
            padding: 24px;
            font-family: system-ui, sans-serif;
        }
    </style>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
    <?php if ($frontend_url) : ?>
        <iframe
            class="kobutsu-shell"
            src="<?php echo $frontend_url; ?>"
            title="<?php echo esc_attr__('Kobutsu Ledger', 'kobutsu-ledger-shell'); ?>"
            allow="clipboard-read; clipboard-write"
        ></iframe>
    <?php else : ?>
        <main class="kobutsu-shell-fallback">
            <h1><?php echo esc_html__('Kobutsu Ledger', 'kobutsu-ledger-shell'); ?></h1>
            <p><?php echo esc_html__('Frontend URL is not configured.', 'kobutsu-ledger-shell'); ?></p>
            <?php if ($launch_settings_url) : ?>
                <p>
                    <a href="<?php echo esc_url($launch_settings_url); ?>">
                        <?php echo esc_html__('起動設定を開く', 'kobutsu-ledger-shell'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </main>
    <?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
