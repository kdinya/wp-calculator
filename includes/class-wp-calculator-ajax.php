<?php
if (!defined("ABSPATH")) exit;

/**
 * Retrieves saved calculator data with in-memory caching and complete backward-compatibility merge.
 */
function wood_calc_get_stored_data() {
    static  = null;
    if ( !== null) {
        return ;
    }

     = array(
        'wood_calc_store_v3',
        'wood_calc_store_v4',
        'wood_calc_store_backup',
        'wood_calc_store_v2',
        'wood_calc_store'
    );

     = array();
     = array();
     = array(
        'lang' => 'uk',
        'accent_color' => '#95b504',
        'wipe_on_uninstall' => false,
        'column_visibility' => array('mat' => true, 'dims' => true, 'price' => true, 'qty' => true, 'sum' => true)
    );
     = false;

    // Iterate through all historical storage keys to merge items, materials, and settings without dropping anything
    foreach ( as ) {
         = get_option(, null);
        if (!empty() && is_array()) {
             = true;

            if (!empty(['settings']) && is_array(['settings'])) {
                foreach (['settings'] as  => ) {
                    if (!isset([])) {
                        [] = ;
                    }
                }
            }

            if (!empty(['materials']) && is_array(['materials'])) {
                foreach (['materials'] as ) {
                     = isset(['id']) ? strval(['id']) : (isset(['name']) ? sanitize_title(['name']) : '');
                    if ( !== '' && !isset([])) {
                        [] = ;
                    }
                }
            }

            if (!empty(['items']) && is_array(['items'])) {
                foreach (['items'] as ) {
                     = isset(['id']) ? strval(['id']) : '';
                    if ( !== '' && !isset([])) {
                        [] = ;
                    }
                }
            }
        }
    }

     = array(
        'materials'   => array_values(),
        'items'       => array_values(),
        'settings'    => ,
        'initialized' =>  && (!empty() || !empty())
    );

    if () {
        update_option('wood_calc_store_v3', , false);
    }

     = ;
    return ;
}

// 2. AJAX: Fetch stored data
add_action('wp_ajax_wood_calc_get', 'wood_calc_get_data');
function wood_calc_get_data() {
    if (!check_ajax_referer('wood_calc_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Помилка безпеки: недійсний nonce.'), 403);
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Доступ заборонено.'), 403);
    }

     = wood_calc_get_stored_data();
    wp_send_json_success();
}

// 3. AJAX: Save data to database with validation and sanitization
add_action('wp_ajax_wood_calc_save', 'wood_calc_save_data');
function wood_calc_save_data() {
    if (!check_ajax_referer('wood_calc_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Помилка безпеки: недійсний nonce.'), 403);
    }
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Доступ заборонено.'), 403);
    }

     = file_get_contents('php://input');
    if (empty()) {
        wp_send_json_error(array('message' => 'Порожні дані запиту.'), 400);
    }

     = json_decode(, true);
    if (!is_array() || !isset(['materials']) || !isset(['items'])) {
        wp_send_json_error(array('message' => 'Невірна структура даних.'), 400);
    }

    // Sanitize materials
     = array();
    if (is_array(['materials'])) {
        foreach (['materials'] as ) {
            if (!is_array()) continue;
             = isset(['name']) ? sanitize_text_field(['name']) : '';
             = isset(['price_per_cm2']) ? floatval(['price_per_cm2']) : (isset(['rate']) ? floatval(['rate']) : 0.0);
            if ( === '' ||  <= 0) continue;

             = isset(['id']) ? sanitize_text_field(strval(['id'])) : sanitize_title();
            [] = array(
                'id'            => ,
                'name'          => ,
                'price_per_cm2' => 
            );
        }
    }

    // Sanitize items
     = array();
    if (is_array(['items'])) {
        foreach (['items'] as ) {
            if (!is_array()) continue;
             = isset(['name']) ? sanitize_text_field(mb_substr(['name'], 0, 255)) : '';
            if ( === '') continue;

             = isset(['len']) ? floatval(['len']) : 0.0;
             = isset(['width']) ? floatval(['width']) : 0.0;
             = isset(['qty']) ? max(1, intval(['qty'])) : 1;
             = isset(['price']) ? max(0.0, floatval(['price'])) : 0.0;
             = isset(['area_cm2']) ? floatval(['area_cm2']) : (( * ) / 100);

             = isset(['id']) ? sanitize_text_field(strval(['id'])) : ('item_' . time() . '_' . wp_rand(100, 999));
             = isset(['material']) ? sanitize_text_field(['material']) : '';
             = isset(['material_id']) ? sanitize_text_field(strval(['material_id'])) : '';
             = !isset(['in_invoice']) || !empty(['in_invoice']);
             = isset(['selected']) ? !empty(['selected']) : true;

            [] = array(
                'id'          => ,
                'name'        => ,
                'material'    => ,
                'material_id' => ,
                'len'         => ,
                'width'       => ,
                'area_cm2'    => ,
                'price'       => ,
                'qty'         => ,
                'in_invoice'  => ,
                'selected'    => 
            );
        }
    }

    // Sanitize settings
     = (isset(['settings']) && is_array(['settings'])) ? ['settings'] : array();
     = (isset(['lang']) && ['lang'] === 'en') ? 'en' : 'uk';
     = (isset(['accent_color']) && preg_match('/^#[0-9a-fA-F]{6}$/', ['accent_color'])) ? ['accent_color'] : '#95b504';
     = !empty(['wipe_on_uninstall']);

     = array('mat' => true, 'dims' => true, 'price' => true, 'qty' => true, 'sum' => true);
    if (isset(['column_visibility']) && is_array(['column_visibility'])) {
        foreach ( as  => ) {
            if (isset(['column_visibility'][])) {
                [] = !empty(['column_visibility'][]);
            }
        }
    }

     = array(
        'materials'   => ,
        'items'       => ,
        'settings'    => array(
            'lang'              => ,
            'accent_color'      => ,
            'wipe_on_uninstall' => ,
            'column_visibility' => 
        ),
        'initialized' => true,
        'updated_at'  => current_time('mysql')
    );

    update_option('wood_calc_store_v3', , false);
    update_option('wood_calc_store_v4', , false);
    update_option('wood_calc_store_backup', , false);

    wp_send_json_success(array('message' => 'Дані успішно збережено.'));
}
