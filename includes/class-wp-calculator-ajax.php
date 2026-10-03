<?php
if (!defined("ABSPATH")) exit;

/**
 * Retrieves saved calculator data with in-memory caching and backward compatibility.
 */
function wood_calc_get_stored_data() {
    static $cached_data = null;
    if ($cached_data !== null) {
        return $cached_data;
    }

    $main = get_option('wood_calc_store_v3', null);
    if (!empty($main) && is_array($main) && isset($main['materials'])) {
        $cached_data = $main;
        return $cached_data;
    }

    $backup = get_option('wood_calc_store_backup', null);
    if (!empty($backup) && is_array($backup) && isset($backup['materials'])) {
        $cached_data = $backup;
        return $cached_data;
    }

    $legacy_keys = array('wood_calc_store_v4', 'wood_calc_store_v2', 'wood_calc_store');
    $merged_materials = array();
    $merged_items = array();
    $saved_settings = array('lang' => 'uk', 'accent_color' => '#95b504', 'wipe_on_uninstall' => false);
    $found_legacy = false;

    foreach ($legacy_keys as $k) {
        $val = get_option($k, null);
        if (!empty($val) && is_array($val)) {
            $found_legacy = true;
            if (!empty($val['settings']) && is_array($val['settings'])) {
                $saved_settings = array_merge($saved_settings, $val['settings']);
            }
            if (!empty($val['materials']) && is_array($val['materials'])) {
                foreach ($val['materials'] as $m) {
                    $m_id = isset($m['id']) ? $m['id'] : (isset($m['name']) ? $m['name'] : rand(1000, 999999));
                    if (!isset($merged_materials[$m_id])) {
                        $merged_materials[$m_id] = $m;
                    }
                }
            }
            if (!empty($val['items']) && is_array($val['items'])) {
                foreach ($val['items'] as $item) {
                    $item_id = isset($item['id']) ? $item['id'] : rand(1000, 999999);
                    if (!isset($merged_items[$item_id])) {
                        $merged_items[$item_id] = $item;
                    }
                }
            }
        }
    }

    $initial = array(
        'materials' => array_values($merged_materials),
        'items'     => array_values($merged_items),
        'settings'  => $saved_settings
    );

    if ($found_legacy) {
        update_option('wood_calc_store_v3', $initial, false);
    }

    $cached_data = $initial;
    return $cached_data;
}

// 2. AJAX: Fetch stored data
add_action('wp_ajax_wood_calc_get', 'wood_calc_get_data');
function wood_calc_get_data() {
    if (!check_ajax_referer('wood_calc_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Помилка безпеки: недійсний nonce.'), 403);
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Доступ заборонено', 403);
    }

    $data = wood_calc_get_stored_data();
    wp_send_json_success($data);
}

// 3. AJAX: Save data to database
add_action('wp_ajax_wood_calc_save', 'wood_calc_save_data');
function wood_calc_save_data() {
    if (!check_ajax_referer('wood_calc_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Помилка безпеки: недійсний nonce.'), 403);
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Доступ заборонено', 403);
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (isset($data['materials']) && isset($data['items'])) {
        $save_payload = array(
            'materials' => $data['materials'],
            'items'     => $data['items'],
            'settings'  => isset($data['settings']) && is_array($data['settings']) ? $data['settings'] : array('lang' => 'uk')
        );
        update_option('wood_calc_store_v3', $save_payload, false);
        update_option('wood_calc_store_v4', $save_payload, false);
        update_option('wood_calc_store_backup', $save_payload, false);

        wp_send_json_success(array('message' => 'Дані успішно збережено'));
    } else {
        wp_send_json_error('Помилка структури даних');
    }
}
