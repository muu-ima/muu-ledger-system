<?php
/**
 * Plugin Name: Kobutsu Ledger API
 * Description: 古物台帳システム用の REST API とカスタムテーブルを追加します。
 * Version: 0.1.0
 * Author: Kobutsu Ledger
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/admin-supplier-sources.php';
require_once __DIR__ . '/admin-ec-sales.php';
require_once __DIR__ . '/admin-launch-settings.php';
require_once __DIR__ . '/admin-ledger.php';
require_once __DIR__ . '/includes/admin-menu.php';
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/ledger-rest-crud.php';
require_once __DIR__ . '/includes/rest.php';
require_once __DIR__ . '/includes/sync/supplier-sources.php';

const KOBUTSU_LEDGER_DB_VERSION = '0.3.3';

kobutsu_ledger_register_hooks(__FILE__);
