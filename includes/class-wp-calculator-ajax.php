<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Retrieves saved calculator data with in-memory caching and complete backward-compatible merging.
 */
if (!function_exists('wood_calc_get_stored_data')) {
function wood_calc_get_stored_data() {
    static $cached_data = null;

    if ($cached_data !== null) {
        return $cached_data;
    }

    // Read legacy records first and the canonical record last so current settings
    // and data always take precedence over historical backups.
    $storage_keys = array(
        'wood_calc_store_v3',
        'wood_calc_store_v4',
        'wood_calc_store_backup',
        'wood_calc_store_v2',
        'wood_calc_store',
    );

    $merged_materials = array();
    $merged_items = array();
    $saved_settings = array(
        'lang' => 'uk',
        'accent_color' => '#95b504',
        'wipe_on_uninstall' => false,
        'currency' => 'грн',
        'column_visibility' => array(
            'mat' => true,
            'dims' => true,
            'price' => true,
            'qty' => true,
            'sum' => true,
        ),
    );
    $found_storage = false;
    $primary_stored = null;

    foreach ($storage_keys as $key) {
        $stored = get_option($key, null);
        if (empty($stored) || !is_array($stored)) {
            continue;
        }

        $found_storage = true;
        if ($key === 'wood_calc_store_v3') {
            $primary_stored = $stored;
        }

        if (!empty($stored['settings']) && is_array($stored['settings'])) {
            foreach ($stored['settings'] as $setting_key => $setting_value) {
                if (array_key_exists($setting_key, $saved_settings)) {
                    if ($key === 'wood_calc_store_v3' || empty($primary_stored['settings']) || !array_key_exists($setting_key, $primary_stored['settings'])) {
                        $saved_settings[$setting_key] = $setting_value;
                    }
                }
            }
        }

        if (!empty($stored['materials']) && is_array($stored['materials'])) {
            foreach ($stored['materials'] as $material) {
                if (!is_array($material)) {
                    continue;
                }

                $material_id = isset($material['id'])
                    ? (string) $material['id']
                    : (isset($material['name']) ? sanitize_title($material['name']) : '');

                if ($material_id !== '' && !isset($merged_materials[$material_id])) {
                    $merged_materials[$material_id] = wood_calc_normalize_material($material, $material_id);
                }
            }
        }

        if (!empty($stored['items']) && is_array($stored['items'])) {
            foreach ($stored['items'] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $item_id = isset($item['id'])
                    ? (string) $item['id']
                    : 'legacy_' . md5(wp_json_encode($item));

                if ($item_id !== '' && !isset($merged_items[$item_id])) {
                    $item['id'] = $item_id;
                    $merged_items[$item_id] = $item;
                }
            }
        }
    }

    $cached_data = array(
        'materials' => array_values($merged_materials),
        'items' => array_values($merged_items),
        'settings' => $saved_settings,
        'initialized' => $found_storage && (!empty($merged_materials) || !empty($merged_items)),
    );

    // Only write on read if primary storage was completely missing and had to be migrated from legacy
    if ($found_storage && $primary_stored === null) {
        update_option('wood_calc_store_v3', $cached_data, false);
    }

    return $cached_data;
}
}

/**
 * Normalize historical material schemas to the current frontend schema.
 */
if (!function_exists('wood_calc_normalize_material')) {
function wood_calc_normalize_material($material, $fallback_id = '') {
    $name = isset($material['name']) ? sanitize_text_field($material['name']) : '';
    $price = 0.0;

    if (isset($material['price'])) {
        $price = (float) $material['price'];
    } elseif (isset($material['price_per_cm2'])) {
        $price = (float) $material['price_per_cm2'];
    } elseif (isset($material['rate'])) {
        $price = (float) $material['rate'];
    }

    $unit = (isset($material['unit']) && $material['unit'] === 'cm3') ? 'cm3' : 'cm2';

    $raw_id = isset($material['id']) && $material['id'] !== '' ? (string) $material['id'] : (string) $fallback_id;
    $safe_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw_id);
    if ($safe_id === '') {
        $safe_id = 'm_' . sanitize_title($name);
        $safe_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $safe_id);
    }
    if ($safe_id === '') {
        $safe_id = 'm_' . wp_rand(1000, 9999);
    }

    return array(
        'id' => $safe_id,
        'name' => function_exists('mb_substr') ? mb_substr($name, 0, 255) : substr($name, 0, 255),
        'price' => $price,
        'unit' => $unit,
    );
}
}

// AJAX: Fetch stored data.
add_action('wp_ajax_wood_calc_get', 'wood_calc_get_data');
if (!function_exists('wood_calc_get_data')) {
function wood_calc_get_data() {
    if (!check_ajax_referer('wood_calc_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Помилка безпеки: недійсний nonce.'), 403);
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Доступ заборонено.'), 403);
    }

    $data = wood_calc_get_stored_data();
    wp_send_json_success($data);
}
}

// AJAX: Save data to database with validation and sanitization.
add_action('wp_ajax_wood_calc_save', 'wood_calc_save_data');
if (!function_exists('wood_calc_save_data')) {
function wood_calc_save_data() {
    if (!check_ajax_referer('wood_calc_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Помилка безпеки: недійсний nonce.'), 403);
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Доступ заборонено.'), 403);
    }

    $raw = file_get_contents('php://input');
    if (empty($raw)) {
        wp_send_json_error(array('message' => 'Порожні дані запиту.'), 400);
    }

    $data = json_decode($raw, true);
    if (!is_array($data) || !isset($data['materials']) || !isset($data['items'])) {
        wp_send_json_error(array('message' => 'Невірна структура даних.'), 400);
    }

    $materials = array();
    if (is_array($data['materials'])) {
        $mat_count = 0;
        foreach ($data['materials'] as $material) {
            if (!is_array($material) || ++$mat_count > 500) {
                continue;
            }

            $normalized = wood_calc_normalize_material($material);
            if ($normalized['name'] === '' || !is_finite($normalized['price']) || $normalized['price'] <= 0) {
                continue;
            }

            if ($normalized['id'] === '') {
                $normalized['id'] = sanitize_title($normalized['name']);
            }
            $materials[] = $normalized;
        }
    }

    $items = array();
    if (is_array($data['items'])) {
        $item_count = 0;
        foreach ($data['items'] as $item) {
            if (!is_array($item) || ++$item_count > 5000) {
                continue;
            }

            $name = isset($item['name']) ? sanitize_text_field((string) $item['name']) : '';
            if (function_exists('mb_substr')) {
                $name = mb_substr($name, 0, 255);
            } else {
                $name = substr($name, 0, 255);
            }
            if ($name === '') {
                continue;
            }

            $len = isset($item['len']) ? (float) $item['len'] : 0.0;
            $width = isset($item['width']) ? (float) $item['width'] : 0.0;
            $height = (isset($item['height']) && is_numeric($item['height']) && (float) $item['height'] > 0) ? (float) $item['height'] : 0.0;
            $price = isset($item['price']) ? (float) $item['price'] : 0.0;
            $qty = isset($item['qty']) ? (int) $item['qty'] : 1;

            if (!is_finite($len) || !is_finite($width) || $len <= 0 || $width <= 0) {
                continue;
            }
            if (!is_finite($price) || $price < 0) {
                continue;
            }

            $qty = max(1, $qty);
            $raw_item_id = isset($item['id']) && $item['id'] !== '' ? (string) $item['id'] : '';
            $item_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $raw_item_id);
            if ($item_id === '') {
                $item_id = 'item_' . time() . '_' . wp_rand(100, 999);
            }

            $items[] = array(
                'id' => $item_id,
                'name' => $name,
                'material' => isset($item['material']) ? sanitize_text_field((string) $item['material']) : '',
                'material_id' => isset($item['material_id']) ? sanitize_text_field((string) $item['material_id']) : '',
                'len' => $len,
                'width' => $width,
                'height' => $height > 0 ? $height : null,
                'area_cm2' => ($len * $width) / 100,
                'volume_cm3' => $height > 0 ? (($len * $width * $height) / 1000) : null,
                'price' => $price,
                'qty' => $qty,
                'in_invoice' => !isset($item['in_invoice']) || !empty($item['in_invoice']),
                'selected' => isset($item['selected']) ? !empty($item['selected']) : true,
                'photo' => isset($item['photo']) ? esc_url_raw((string) $item['photo']) : '',
            );
        }
    }

    $input_settings = (isset($data['settings']) && is_array($data['settings'])) ? $data['settings'] : array();
    $lang = (isset($input_settings['lang']) && $input_settings['lang'] === 'en') ? 'en' : 'uk';
    $accent_color = (isset($input_settings['accent_color']) && preg_match('/^#[0-9a-fA-F]{6}$/', (string) $input_settings['accent_color']))
        ? (string) $input_settings['accent_color']
        : '#95b504';
    $wipe_on_uninstall = !empty($input_settings['wipe_on_uninstall']);
    $currency = (isset($input_settings['currency']) && is_string($input_settings['currency'])) ? sanitize_text_field(trim($input_settings['currency'])) : 'грн';
    if ($currency === '') {
        $currency = 'грн';
    }
    $column_visibility = array('mat' => true, 'dims' => true, 'price' => true, 'qty' => true, 'sum' => true);

    if (isset($input_settings['column_visibility']) && is_array($input_settings['column_visibility'])) {
        foreach ($column_visibility as $column => $default) {
            if (array_key_exists($column, $input_settings['column_visibility'])) {
                $column_visibility[$column] = !empty($input_settings['column_visibility'][$column]);
            }
        }
    }

    $save_payload = array(
        'materials' => $materials,
        'items' => $items,
        'settings' => array(
            'lang' => $lang,
            'accent_color' => $accent_color,
            'wipe_on_uninstall' => $wipe_on_uninstall,
            'currency' => $currency,
            'column_visibility' => $column_visibility,
        ),
        'initialized' => true,
        'updated_at' => current_time('mysql'),
    );

    $current_stored = get_option('wood_calc_store_v3', null);
    if (is_array($current_stored)) {
        $keys_to_compare = array('materials', 'items', 'settings', 'initialized');
        $cmp_current = array_intersect_key($current_stored, array_flip($keys_to_compare));
        $cmp_new = array_intersect_key($save_payload, array_flip($keys_to_compare));
        if ($cmp_current === $cmp_new) {
            wp_send_json_success(array(
                'message' => 'Дані актуальні (без змін).',
                'unchanged' => true,
            ));
        }
    }

    update_option('wood_calc_store_v3', $save_payload, false);

    wp_send_json_success(array('message' => 'Дані успішно збережено.'));
}
}
