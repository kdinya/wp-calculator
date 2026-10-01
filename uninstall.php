<?php
/**
 * Виконується лише при повному видаленні плагіна через адмінку WordPress (Uninstall).
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

// Перевіряємо налаштування: за замовчуванням дані НЕ видаляються
$store = get_option('wood_calc_store_v3', null);
if (empty($store)) {
    $store = get_option('wood_calc_store_v4', null);
}

$wipe = false;
if (!empty($store) && is_array($store) && !empty($store['settings']) && !empty($store['settings']['wipe_on_uninstall'])) {
    $wipe = true;
}

if ($wipe) {
    delete_option('wood_calc_store_v3');
    delete_option('wood_calc_store_v4');
    delete_option('wood_calc_store_backup');
    delete_option('wood_calc_store_v2');
    delete_option('wood_calc_store');
    delete_transient('wood_calc_github_latest_release');
}
