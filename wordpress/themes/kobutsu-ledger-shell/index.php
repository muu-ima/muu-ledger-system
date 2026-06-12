<?php
/**
 * Shell view for the Kobutsu Ledger frontend.
 */

if (!defined('ABSPATH')) {
    exit;
}

$frontend_url = kobutsu_ledger_shell_frontend_url();
$frontend_origin = kobutsu_ledger_shell_origin($frontend_url);
$auth_payload = kobutsu_ledger_shell_auth_payload();
$auth_refresh_url = admin_url('admin-ajax.php?action=kobutsu_ledger_shell_auth');
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
            id="kobutsu-shell-frame"
            class="kobutsu-shell"
            src="<?php echo $frontend_url; ?>"
            title="<?php echo esc_attr__('Kobutsu Ledger', 'kobutsu-ledger-shell'); ?>"
            allow="clipboard-read; clipboard-write"
        ></iframe>
        <script>
            (() => {
                const frame = document.getElementById("kobutsu-shell-frame");
                const targetOrigin = <?php echo wp_json_encode($frontend_origin); ?>;
                let authPayload = <?php echo wp_json_encode($auth_payload); ?>;
                const authRefreshUrl = <?php echo wp_json_encode($auth_refresh_url); ?>;

                if (!frame || !targetOrigin) {
                    return;
                }

                const postAuthPayload = () => {
                    if (!frame.contentWindow) {
                        return;
                    }

                    frame.contentWindow.postMessage(
                        {
                            type: "kobutsu-ledger-shell-auth",
                            payload: authPayload,
                        },
                        targetOrigin,
                    );
                };

                const refreshAuthPayload = async () => {
                    try {
                        const response = await fetch(authRefreshUrl, {
                            credentials: "same-origin",
                        });
                        const data = await response.json();

                        if (response.ok && data?.success && data?.data) {
                            authPayload = data.data;
                        }
                    } catch (error) {
                        // Fall back to the current payload; the iframe handles a second 401.
                    }
                };

                frame.addEventListener("load", postAuthPayload);

                window.addEventListener("message", (event) => {
                    if (event.origin !== targetOrigin) {
                        return;
                    }

                    if (event.data?.type === "kobutsu-ledger-auth-request") {
                        refreshAuthPayload().finally(postAuthPayload);
                    }
                });
            })();
        </script>
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
