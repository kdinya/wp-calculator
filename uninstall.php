<?php
/**
 * Виконується лише при повному видаленні плагіна через адмінку WordPress (Uninstall).
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

// Перевіряємо налаштування в усіх збережених версіях сховища
$keys = array(
    'wood_calc_store_v3',
    'wood_calc_store_v4',
    'wood_calc_store_backup',
    'wood_calc_store_v2',
    'wood_calc_store'
);

$wipe = false;
foreach ($keys as $k) {
    $store = get_option($k, null);
    if (!empty($store) && is_array($store) && !empty($store['settings']) && !empty($store['settings']['wipe_on_uninstall'])) {
        $wipe = true;
        break;
    }
}

if ($wipe) {
    delete_option('wood_calc_store_v3');
    delete_option('wood_calc_store_v4');
    delete_option('wood_calc_store_backup');
    delete_option('wood_calc_store_v2');
    delete_option('wood_calc_store');
    delete_transient('wood_calc_github_latest_release');
}
