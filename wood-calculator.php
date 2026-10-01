<?php
/**
 * Plugin Name: Wood Calculator (Калькулятор виробів з дерева)
 * Plugin URI: https://github.com/kdinya/wp-calculator
 * Description: Професійний калькулятор вартості виробів з дерева з довідником порід, збереженням каталогу, формуванням накладних для клієнтів та адаптивним інтерфейсом у стилі сайту.
 * Version: 1.0.0
 * Author: Wood Calculator Team
 * Author URI: https://tomchik.com.ua/
 * Text Domain: wood-calculator
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Захист від прямого доступу
}

// 1. Меню в адмін-панелі WordPress
add_action('admin_menu', 'wood_calc_add_admin_menu');
function wood_calc_add_admin_menu() {
    add_menu_page(
        'Калькулятор виробів',
        'Калькулятор виробів',
        'manage_options',
        'wood-calculator',
        'wood_calc_admin_page_render',
        'dashicons-calculator',
        30
    );
}

function wood_calc_admin_page_render() {
    echo '<div class="wrap" style="max-width:1300px; margin:20px auto 40px auto;">';
    echo wood_calc_render_shortcode();
    echo '</div>';
}

// 2. AJAX: Отримання збережених даних (Багаторівневий захист збереження)
add_action('wp_ajax_wood_calc_get', 'wood_calc_get_data');
function wood_calc_get_data() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Доступ заборонено');
    }

    $keys = array('wood_calc_store_v3', 'wood_calc_store_v4', 'wood_calc_store_v2', 'wood_calc_store');
    $merged_materials = array();
    $merged_items = array();

    foreach ($keys as $k) {
        $val = get_option($k, null);
        if (!empty($val) && is_array($val)) {
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

    $data = array(
        'materials' => array_values($merged_materials),
        'items'     => array_values($merged_items)
    );

    wp_send_json_success($data);
}

// 3. AJAX: Збереження даних у базу WordPress
add_action('wp_ajax_wood_calc_save', 'wood_calc_save_data');
function wood_calc_save_data() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Доступ заборонено');
    }

    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);

    if (isset($data['materials']) && isset($data['items'])) {
        $save_payload = array(
            'materials' => $data['materials'],
            'items'     => $data['items']
        );
        // Зберігаємо в основний та резервний ключі
        update_option('wood_calc_store_v3', $save_payload, false);
        update_option('wood_calc_store_v4', $save_payload, false);
        update_option('wood_calc_store_backup', $save_payload, false);
        
        wp_send_json_success(array('message' => 'Дані успішно збережено'));
    } else {
        wp_send_json_error('Помилка структури даних');
    }
}

// 4. Шорткод [wood_calculator]
add_shortcode('wood_calculator', 'wood_calc_render_shortcode');
function wood_calc_render_shortcode() {
    if (!current_user_can('manage_options')) {
        return '<p style="padding:20px; color:#c8232c; background:#fff1f2; border-radius:8px; font-weight:600; text-align:center;">🔒 Цей калькулятор доступний тільки адміністратору сайту.</p>';
    }

    $ajax_url = admin_url('admin-ajax.php');
    ob_start();
    ?>
    <div id="wood-calc-app">
        <style>
            :root {
                --tc-green: #95b504;
                --tc-green-dark: #7e9c02;
                --tc-green-light: #f4f8e7;
                --tc-dark: #24272a;
                --tc-border: #e2e8f0;
                --tc-text: #1e293b;
            }
            #wood-calc-app {
                max-width: 1250px;
                margin: 20px auto;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                color: var(--tc-text);
                line-height: 1.45;
                font-size: 14px;
            }
            #wood-calc-app * { box-sizing: border-box; }

            /* Зелені чекбокси */
            #wood-calc-app input[type="checkbox"] {
                accent-color: var(--tc-green);
                width: 18px;
                height: 18px;
                cursor: pointer;
                vertical-align: middle;
            }

            /* Шапка */
            .tc-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: var(--tc-dark);
                color: #fff;
                padding: 14px 20px;
                border-radius: 10px;
                margin-bottom: 20px;
                border-bottom: 4px solid var(--tc-green);
            }
            .tc-header h1 {
                font-size: 18px;
                margin: 0;
                color: #fff;
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .tc-header-actions {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            /* Сітка: 2 стовпчики на ПК */
            .tc-layout {
                display: grid;
                grid-template-columns: 1fr 360px;
                gap: 20px;
                align-items: start;
            }

            /* Мобільні та планшети: довідник стрибає вгору */
            @media (max-width: 900px) {
                .tc-layout {
                    display: flex;
                    flex-direction: column;
                }
                .tc-side-col {
                    order: -1;
                    width: 100%;
                }
                .tc-main-col {
                    order: 1;
                    width: 100%;
                }
            }

            .tc-card {
                background: #fff;
                padding: 20px;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.04);
                border: 1px solid var(--tc-border);
                margin-bottom: 20px;
            }
            .tc-card h2 {
                font-size: 16px;
                margin: 0 0 16px 0;
                color: var(--tc-dark);
                font-weight: 700;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            label {
                font-size: 12px;
                font-weight: 600;
                color: #64748b;
                display: block;
                margin-bottom: 5px;
            }
            input, select {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                font-size: 14px;
                background: #fff;
                color: var(--tc-text);
                height: 40px;
            }
            input:focus, select:focus {
                outline: 2px solid var(--tc-green);
                border-color: transparent;
            }

            /* Кнопки */
            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                padding: 8px 14px;
                border-radius: 6px;
                font-weight: 600;
                font-size: 13px;
                cursor: pointer;
                border: none;
                transition: all 0.15s ease;
                height: 40px;
                text-decoration: none;
            }
            .btn-green { background: var(--tc-green); color: #fff; }
            .btn-green:hover { background: var(--tc-green-dark); color: #fff; }
            .btn-dark { background: var(--tc-dark); color: #fff; }
            .btn-dark:hover { background: #3a3f45; color: #fff; }
            .btn-light { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
            .btn-light:hover { background: #e2e8f0; }
            .btn-active { background: var(--tc-green) !important; color: #fff !important; }
            .btn-danger { background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5; }
            .btn-danger:hover { background: #fecaca; }

            .btn-icon {
                padding: 5px 8px;
                font-size: 13px;
                border-radius: 4px;
                border: none;
                cursor: pointer;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                height: 30px;
                width: 30px;
            }
            .btn-edit { background: #e0f2fe; color: #0369a1; }
            .btn-edit:hover { background: #bae6fd; }
            .btn-dup { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
            .btn-dup:hover { background: #e2e8f0; }
            .btn-del { background: #fee2e2; color: #dc2626; }
            .btn-del:hover { background: #fecaca; }

            /* Сітки форм */
            .grid-calc {
                display: grid;
                grid-template-columns: 2fr 1fr 1fr auto;
                gap: 12px;
                align-items: end;
            }
            @media (max-width: 650px) {
                .grid-calc { grid-template-columns: 1fr 1fr; }
                .grid-calc .full-mobile { grid-column: span 2; }
            }

            .calc-result-box {
                margin-top: 16px;
                padding: 14px 18px;
                background: var(--tc-green-light);
                border: 1px dashed var(--tc-green);
                border-radius: 8px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            /* Панель колонок накладної */
            .column-toggles-bar {
                background: #f8fafc;
                border: 1px solid var(--tc-border);
                border-radius: 8px;
                padding: 10px 14px;
                margin: 12px 0 16px 0;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 14px;
                font-size: 13px;
            }
            .column-toggles-bar label {
                margin: 0;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                cursor: pointer;
                color: #334155;
            }

            /* Таблиця */
            .table-wrap { overflow-x: auto; }
            table { width: 100%; border-collapse: collapse; margin-top: 6px; min-width: 600px; }
            th, td {
                padding: 12px 10px;
                text-align: left;
                border-bottom: 1px solid var(--tc-border);
                font-size: 13px;
                vertical-align: middle;
            }
            th { color: #64748b; font-weight: 600; font-size: 12px; }

            /* Drag & Drop */
            .wc-drag-handle {
                cursor: grab;
                color: #94a3b8;
                font-size: 18px;
                padding: 4px 6px;
                user-select: none;
                display: inline-block;
            }
            .wc-drag-handle:active { cursor: grabbing; color: var(--tc-green); }
            tr.row-dragging { opacity: 0.35; background: #f0fdf4; }
            tr.drop-above { border-top: 3px solid var(--tc-green) !important; }
            tr.drop-below { border-bottom: 3px solid var(--tc-green) !important; }

            /* Матеріали */
            .mat-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 10px 12px;
                background: #f8fafc;
                border: 1px solid var(--tc-border);
                border-radius: 8px;
                margin-bottom: 8px;
                font-size: 13px;
            }

            /* Підсумок */
            .total-banner {
                margin-top: 18px;
                padding: 16px 20px;
                background: var(--tc-dark);
                color: #fff;
                border-radius: 8px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                font-size: 20px;
                font-weight: 700;
            }
            .total-banner span.val { color: #b7e31b; }
            .bottom-actions-bar {
                margin-top: 16px;
                display: flex;
                justify-content: flex-end;
            }

            /* Накладна */
            #invoice-print-card { background: #fff; }
            .invoice-header-box { display: none; }

            #wood-calc-app.invoice-mode .no-invoice { display: none !important; }
            #wood-calc-app.invoice-mode .tc-layout { grid-template-columns: 1fr; }
            #wood-calc-app.invoice-mode #invoice-print-card {
                padding: 28px;
                border: 2px solid var(--tc-green);
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            }
            #wood-calc-app.invoice-mode .invoice-header-box {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding-bottom: 16px;
                border-bottom: 2px solid var(--tc-green);
                margin-bottom: 20px;
            }
            #invoice-exit-wrapper {
                display: none;
                margin-top: 24px;
                text-align: center;
            }
            #wood-calc-app.invoice-mode #invoice-exit-wrapper { display: block; }

            /* Модальні вікна в дизайні сайту */
            .modal-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(36, 39, 42, 0.7);
                backdrop-filter: blur(2px);
                z-index: 999999;
                align-items: center;
                justify-content: center;
                padding: 16px;
            }
            .modal-box {
                background: #fff;
                padding: 24px;
                border-radius: 12px;
                max-width: 440px;
                width: 100%;
                box-shadow: 0 15px 35px rgba(0,0,0,0.25);
                border-top: 4px solid var(--tc-green);
            }
            .modal-box h3 {
                margin: 0 0 14px 0;
                color: var(--tc-dark);
                font-size: 17px;
                font-weight: 700;
            }
            .modal-actions {
                display: flex;
                justify-content: flex-end;
                gap: 10px;
                margin-top: 20px;
            }
        </style>

        <!-- Шапка -->
        <div class="tc-header no-invoice">
            <h1>🛠️ Розрахунок вартості виробів</h1>
            <div class="tc-header-actions">
                <span id="wc-status" style="font-size:12px; color:#cbd5e1;">● Синхронізація...</span>
                <button type="button" class="btn btn-dark" style="height:32px; font-size:12px; padding:4px 10px;" onclick="downloadDataBackup()" title="Зберегти резервну копію всіх даних на комп'ютер">💾 Бекап</button>
            </div>
        </div>

        <div class="tc-layout">
            <!-- ЛІВИЙ СТОВПЧИК: Калькулятор та Вироби -->
            <div class="tc-main-col">
                
                <!-- 1. ШВИДКИЙ КАЛЬКУЛЯТОР -->
                <div class="tc-card no-invoice">
                    <h2>1. Швидкий калькулятор вартості</h2>
                    <div class="grid-calc">
                        <div class="full-mobile">
                            <label>Матеріал (тариф за 1 см²)</label>
                            <select id="calc-mat" onchange="runQuickCalc()"></select>
                        </div>
                        <div>
                            <label>Довжина (мм)</label>
                            <input type="number" id="calc-len" placeholder="напр. 500" oninput="runQuickCalc()">
                        </div>
                        <div>
                            <label>Ширина (мм)</label>
                            <input type="number" id="calc-width" placeholder="напр. 300" oninput="runQuickCalc()">
                        </div>
                        <div class="full-mobile">
                            <button type="button" class="btn btn-green" style="width:100%;" onclick="sendToSaveForm()">Внести у виріб ↓</button>
                        </div>
                    </div>
                    <div class="calc-result-box">
                        <div>
                            <span style="font-size:12px; color:#64748b;">Розрахована площа:</span>
                            <strong id="res-area" style="font-size:15px; margin-left:4px;">0 см²</strong>
                        </div>
                        <div>
                            <span style="font-size:12px; color:#64748b;">Ціна за 1 шт:</span>
                            <strong id="res-price" style="font-size:18px; color:var(--tc-dark); margin-left:6px;">0.00 грн</strong>
                        </div>
                    </div>
                </div>

                <!-- 2. ДОДАТИ ВИРІБ У СПИСОК -->
                <div class="tc-card no-invoice">
                    <h2>2. Додати виріб у список</h2>
                    <div style="display:grid; grid-template-columns: 2fr 1.5fr 1fr 1fr 1.2fr auto; gap:10px; align-items:end;">
                        <div>
                            <label>Назва виробу</label>
                            <input type="text" id="add-name" placeholder="напр. Дошка дубова">
                        </div>
                        <div>
                            <label>Оберіть матеріал</label>
                            <select id="add-mat"></select>
                        </div>
                        <div>
                            <label>Довжина (мм)</label>
                            <input type="number" id="add-len" placeholder="мм">
                        </div>
                        <div>
                            <label>Ширина (мм)</label>
                            <input type="number" id="add-width" placeholder="мм">
                        </div>
                        <div>
                            <label>Ціна/шт (грн)</label>
                            <input type="number" step="0.01" id="add-price" placeholder="0.00">
                        </div>
                        <div>
                            <button type="button" class="btn btn-dark" onclick="addCustomProduct()">+ Зберегти</button>
                        </div>
                    </div>
                </div>

                <!-- 3. СПИСОК ВИРОБІВ / БЛОК НАКЛАДНОЇ -->
                <div id="invoice-print-card" class="tc-card" style="margin-bottom:0;">
                    
                    <!-- Шапка для накладної (без назви сайту, тільки замовлення і дата) -->
                    <div class="invoice-header-box">
                        <div>
                            <h2 style="margin:0; font-size:22px; color:var(--tc-dark); letter-spacing:0.5px;">РОЗРАХУНОК ЗАМОВЛЕННЯ</h2>
                            <div style="font-size:13px; color:#64748b; margin-top:4px;" id="inv-date"></div>
                        </div>
                    </div>

                    <h2 class="no-invoice">
                        <span>Список виробів</span>
                        <div style="display:flex; gap:8px;">
                            <button type="button" id="btn-toggle-filter" class="btn btn-light" onclick="toggleHideUnselected()">👁️ Сховати невиділені</button>
                        </div>
                    </h2>

                    <!-- Вибір колонок для накладної -->
                    <div class="column-toggles-bar no-invoice">
                        <span style="font-weight:700; color:#334155;">Стовпці для накладної:</span>
                        <label><input type="checkbox" id="col-inv-mat" checked onchange="renderProducts()"> Матеріал</label>
                        <label><input type="checkbox" id="col-inv-dim" checked onchange="renderProducts()"> Розміри</label>
                        <label><input type="checkbox" id="col-inv-price" checked onchange="renderProducts()"> Ціна/шт</label>
                        <label><input type="checkbox" id="col-inv-qty" checked onchange="renderProducts()"> К-сть</label>
                        <label><input type="checkbox" id="col-inv-sum" checked onchange="renderProducts()"> Сума</label>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width:30px;" class="no-invoice"></th>
                                    <th style="width:34px;" class="no-invoice">
                                        <input type="checkbox" id="chk-all" checked onchange="toggleSelectAll()">
                                    </th>
                                    <th>Виріб</th>
                                    <th class="col-mat">Матеріал</th>
                                    <th class="col-dim">Розміри</th>
                                    <th class="col-price">Ціна / 1 шт</th>
                                    <th class="col-qty" style="width:85px;">К-сть</th>
                                    <th class="col-sum">Сума</th>
                                    <th style="width:115px;" class="no-invoice">Дії</th>
                                </tr>
                            </thead>
                            <tbody id="products-tbody"></tbody>
                        </table>
                    </div>

                    <!-- Підсумок -->
                    <div class="total-banner">
                        <span>Загальна сума:</span>
                        <span class="val" id="grand-total">0.00 грн</span>
                    </div>

                    <!-- Кнопка режиму накладної -->
                    <div class="bottom-actions-bar no-invoice">
                        <button type="button" class="btn btn-green" onclick="toggleInvoiceMode()">📸 Накладна для скріна</button>
                    </div>
                </div>

                <!-- КНОПКА ВИХОДУ З РЕЖИМУ НАКЛАДНОЇ (ОКРЕМО ПІД БЛОКОМ ДЛЯ СКРІНУ) -->
                <div id="invoice-exit-wrapper">
                    <button type="button" class="btn btn-light" style="padding:10px 24px; font-size:14px; border:1px solid #94a3b8;" onclick="toggleInvoiceMode()">← Вийти з режиму накладної</button>
                </div>
            </div>

            <!-- ПРАВИЙ СТОВПЧИК: Довідник матеріалів (на мобільному переміщується вгору) -->
            <div class="tc-side-col no-invoice">
                <div class="tc-card">
                    <h2>Довідник матеріалів</h2>
                    
                    <div style="margin-bottom:14px;">
                        <div style="margin-bottom:8px;">
                            <label>Назва породи / матеріалу</label>
                            <input type="text" id="new-mat-name" placeholder="напр. Дуб, Ясен">
                        </div>
                        <div style="margin-bottom:10px;">
                            <label>Вартість за 1 см² (грн, точна)</label>
                            <input type="number" step="any" id="new-mat-rate" placeholder="напр. 0.20123">
                        </div>
                        <button type="button" class="btn btn-green" style="width:100%;" onclick="createMaterial()">+ Додати матеріал</button>
                    </div>

                    <hr style="border:none; border-top:1px solid var(--tc-border); margin:16px 0;">
                    <label style="margin-bottom:10px;">Створені матеріали:</label>
                    <div id="materials-list"></div>
                </div>
            </div>
        </div>

        <!-- МОДАЛЬНЕ ВІКНО РЕДАГУВАННЯ ВИРОБУ -->
        <div id="edit-prod-modal" class="modal-overlay">
            <div class="modal-box">
                <h3>✏️ Редагування виробу</h3>
                <input type="hidden" id="edit-prod-id">
                <div style="margin-bottom:10px;">
                    <label>Назва виробу</label>
                    <input type="text" id="edit-prod-name">
                </div>
                <div style="margin-bottom:10px;">
                    <label>Матеріал</label>
                    <select id="edit-prod-mat"></select>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
                    <div>
                        <label>Довжина (мм)</label>
                        <input type="number" id="edit-prod-len">
                    </div>
                    <div>
                        <label>Ширина (мм)</label>
                        <input type="number" id="edit-prod-width">
                    </div>
                </div>
                <div style="margin-bottom:14px;">
                    <label>Фіксована ціна за 1 шт (грн)</label>
                    <input type="number" step="0.01" id="edit-prod-price">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-light" onclick="closeEditModal()">Скасувати</button>
                    <button type="button" class="btn btn-green" onclick="saveEditedProduct()">Зберегти зміни</button>
                </div>
            </div>
        </div>

        <!-- МОДАЛЬНЕ ВІКНО РЕДАГУВАННЯ МАТЕРІАЛУ -->
        <div id="edit-mat-modal" class="modal-overlay">
            <div class="modal-box">
                <h3>✏️ Редагування матеріалу</h3>
                <input type="hidden" id="edit-mat-id">
                <div style="margin-bottom:10px;">
                    <label>Назва матеріалу</label>
                    <input type="text" id="edit-mat-name">
                </div>
                <div style="margin-bottom:14px;">
                    <label>Ціна за 1 см² (грн, без округлення)</label>
                    <input type="number" step="any" id="edit-mat-rate">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-light" onclick="closeMatModal()">Скасувати</button>
                    <button type="button" class="btn btn-green" onclick="saveEditedMaterial()">Зберегти</button>
                </div>
            </div>
        </div>

        <!-- МОДАЛЬНЕ ВІКНО ПІДТВЕРДЖЕННЯ ВИДАЛЕННЯ (В СТИЛІ САЙТУ) -->
        <div id="delete-confirm-modal" class="modal-overlay">
            <div class="modal-box" style="border-top-color:#dc2626;">
                <h3 id="del-modal-title">Підтвердження видалення</h3>
                <p id="del-modal-desc" style="color:#64748b; font-size:14px; margin:10px 0 20px 0;">Ви впевнені, що бажаєте видалити цей елемент?</p>
                <div class="modal-actions">
                    <button type="button" class="btn btn-light" onclick="closeDeleteModal()">Скасувати</button>
                    <button type="button" class="btn btn-danger" id="del-modal-confirm-btn">Так, видалити</button>
                </div>
            </div>
        </div>

    </div>

    <script>
    (function() {
        const WC_AJAX = "<?php echo esc_url($ajax_url); ?>";
        const LOCAL_MIRROR_KEY = 'wood_calc_local_mirror_v2';

        let appMaterials = [];
        let appItems = [];
        let hideUnselected = false;
        let deleteActionCallback = null;

        // 1. ЗАВАНТАЖЕННЯ ДАНИХ З БАЗИ WORDPRESS ТА ЛОКАЛЬНОГО ДЗЕРКАЛА
        async function appLoad() {
            showStatus('● Завантаження...', '#64748b');
            let loadedFromDb = false;
            
            try {
                const res = await fetch(WC_AJAX + '?action=wood_calc_get');
                const json = await res.json();
                if (json.success && json.data) {
                    appMaterials = json.data.materials || [];
                    appItems = json.data.items || [];
                    loadedFromDb = true;
                    showStatus('● Синхронізовано з базою', '#95b504');
                    // Оновлюємо локальне дзеркало
                    saveLocalMirror();
                }
            } catch(e) {
                console.warn('DB Load warning:', e);
            }

            // Якщо база була порожньою або виник збій, перевіряємо локальне дзеркало
            if (!loadedFromDb || (appMaterials.length === 0 && appItems.length === 0)) {
                try {
                    const localData = localStorage.getItem(LOCAL_MIRROR_KEY);
                    if (localData) {
                        const parsed = JSON.parse(localData);
                        if (parsed.materials && parsed.materials.length > 0) appMaterials = parsed.materials;
                        if (parsed.items && parsed.items.length > 0) appItems = parsed.items;
                        showStatus('● Відновлено з локального сховища', '#95b504');
                        // Зберігаємо назад у базу
                        appSave();
                    }
                } catch(e) {}
            }

            renderMaterials();
            renderProducts();
            runQuickCalc();
        }

        // 2. ЗБЕРЕЖЕННЯ ДАНИХ (БАЗА WORDPRESS + LOCALSTORAGE)
        async function appSave() {
            showStatus('Збереження...', '#d97706');
            saveLocalMirror();

            try {
                const res = await fetch(WC_AJAX + '?action=wood_calc_save', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ materials: appMaterials, items: appItems })
                });
                const json = await res.json();
                if (json.success) {
                    showStatus('● Збережено в базі', '#95b504');
                } else {
                    showStatus('⚠ Помилка бази', '#dc2626');
                }
            } catch(e) {
                showStatus('⚠ Збережено локально (офлайн)', '#d97706');
            }
        }

        function saveLocalMirror() {
            try {
                localStorage.setItem(LOCAL_MIRROR_KEY, JSON.stringify({
                    materials: appMaterials,
                    items: appItems,
                    savedAt: new Date().toISOString()
                }));
            } catch(e) {}
        }

        function showStatus(text, color) {
            const el = document.getElementById('wc-status');
            if (el) {
                el.textContent = text;
                el.style.color = color;
            }
        }

        // 3. РЕЗЕРВНЕ КОПІЮВАННЯ (БЕКАП)
        window.downloadDataBackup = function() {
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify({
                version: "1.0",
                exportDate: new Date().toISOString(),
                materials: appMaterials,
                items: appItems
            }, null, 2));
            const dlAnchor = document.createElement('a');
            dlAnchor.setAttribute("href", dataStr);
            dlAnchor.setAttribute("download", "wood_calculator_backup_" + new Date().toISOString().slice(0,10) + ".json");
            document.body.appendChild(dlAnchor);
            dlAnchor.click();
            dlAnchor.remove();
        };

        // 4. ШВИДКИЙ КАЛЬКУЛЯТОР
        window.runQuickCalc = function() {
            const matSelect = document.getElementById('calc-mat');
            const rate = parseFloat(matSelect ? matSelect.value : 0) || 0;
            const len = parseFloat(document.getElementById('calc-len').value) || 0;
            const width = parseFloat(document.getElementById('calc-width').value) || 0;

            const areaCm2 = (len * width) / 100;
            const price = areaCm2 * rate;

            document.getElementById('res-area').textContent = areaCm2.toFixed(1) + ' см²';
            document.getElementById('res-price').textContent = price.toFixed(2) + ' грн';
        };

        window.sendToSaveForm = function() {
            const matSelect = document.getElementById('calc-mat');
            const selectedOpt = matSelect.options[matSelect.selectedIndex];
            const matName = selectedOpt ? selectedOpt.getAttribute('data-name') : '';
            const len = document.getElementById('calc-len').value;
            const width = document.getElementById('calc-width').value;
            const priceText = document.getElementById('res-price').textContent.replace(' грн', '');

            if (!len || !width || parseFloat(priceText) <= 0) {
                alert('Спочатку введіть розміри у калькуляторі!');
                return;
            }

            const addMatSelect = document.getElementById('add-mat');
            if (matName) {
                for (let i = 0; i < addMatSelect.options.length; i++) {
                    if (addMatSelect.options[i].text === matName) {
                        addMatSelect.selectedIndex = i;
                        break;
                    }
                }
            }

            document.getElementById('add-len').value = len;
            document.getElementById('add-width').value = width;
            document.getElementById('add-price').value = priceText;
            document.getElementById('add-name').focus();
        };

        // 5. ДОВІДНИК МАТЕРІАЛІВ
        window.createMaterial = function() {
            const name = document.getElementById('new-mat-name').value.trim();
            const rate = parseFloat(document.getElementById('new-mat-rate').value);

            if (!name || isNaN(rate) || rate <= 0) {
                alert('Вкажіть назву та коректну точну вартість за 1 см²!');
                return;
            }

            appMaterials.push({ id: Date.now(), name: name, rate: rate });
            document.getElementById('new-mat-name').value = '';
            document.getElementById('new-mat-rate').value = '';
            
            appSave();
            renderMaterials();
            runQuickCalc();
        };

        window.openEditMaterial = function(id) {
            const mat = appMaterials.find(m => m.id === id);
            if (!mat) return;
            document.getElementById('edit-mat-id').value = mat.id;
            document.getElementById('edit-mat-name').value = mat.name;
            document.getElementById('edit-mat-rate').value = mat.rate;
            document.getElementById('edit-mat-modal').style.display = 'flex';
        };

        window.closeMatModal = function() {
            document.getElementById('edit-mat-modal').style.display = 'none';
        };

        window.saveEditedMaterial = function() {
            const id = parseInt(document.getElementById('edit-mat-id').value);
            const name = document.getElementById('edit-mat-name').value.trim();
            const rate = parseFloat(document.getElementById('edit-mat-rate').value);
            const mat = appMaterials.find(m => m.id === id);

            if (mat && name && !isNaN(rate) && rate > 0) {
                mat.name = name;
                mat.rate = rate;
                appSave();
                renderMaterials();
                runQuickCalc();
                closeMatModal();
            }
        };

        window.deleteMaterial = function(id) {
            const mat = appMaterials.find(m => m.id === id);
            openDeleteModal(
                'Видалити матеріал?',
                'Ви впевнені, що хочете видалити матеріал "' + (mat ? mat.name : '') + '" з довідника?',
                function() {
                    appMaterials = appMaterials.filter(m => m.id !== id);
                    appSave();
                    renderMaterials();
                    runQuickCalc();
                }
            );
        };

        function renderMaterials() {
            const container = document.getElementById('materials-list');
            const selectCalc = document.getElementById('calc-mat');
            const selectAdd = document.getElementById('add-mat');
            const selectEdit = document.getElementById('edit-prod-mat');

            container.innerHTML = '';
            selectCalc.innerHTML = '';
            selectAdd.innerHTML = '';
            if (selectEdit) selectEdit.innerHTML = '';

            if (appMaterials.length === 0) {
                container.innerHTML = '<div style="color:#94a3b8; font-size:13px; text-align:center; padding:12px;">Матеріалів немає. Додайте перший вище.</div>';
                selectCalc.innerHTML = '<option value="0">-- Немає матеріалів --</option>';
                selectAdd.innerHTML = '<option value="">-- Немає матеріалів --</option>';
                return;
            }

            appMaterials.forEach(m => {
                const div = document.createElement('div');
                div.className = 'mat-item';
                div.innerHTML = `
                    <div>
                        <strong>${m.name}</strong>
                        <div style="font-size:12px; color:#64748b;">${m.rate} грн / см²</div>
                    </div>
                    <div style="display:flex; gap:4px;">
                        <button type="button" class="btn-icon btn-edit" onclick="openEditMaterial(${m.id})" title="Редагувати">✏️</button>
                        <button type="button" class="btn-icon btn-del" onclick="deleteMaterial(${m.id})" title="Видалити">✕</button>
                    </div>
                `;
                container.appendChild(div);

                const optCalc = document.createElement('option');
                optCalc.value = m.rate;
                optCalc.setAttribute('data-name', m.name);
                optCalc.textContent = `${m.name} (${m.rate} грн/см²)`;
                selectCalc.appendChild(optCalc);

                const optAdd = document.createElement('option');
                optAdd.value = m.name;
                optAdd.textContent = m.name;
                selectAdd.appendChild(optAdd);

                if (selectEdit) {
                    const optEdit = document.createElement('option');
                    optEdit.value = m.name;
                    optEdit.textContent = m.name;
                    selectEdit.appendChild(optEdit);
                }
            });
        }

        // 6. СПИСОК ВИРОБІВ
        window.addCustomProduct = function() {
            const name = document.getElementById('add-name').value.trim() || 'Виріб';
            const mat = document.getElementById('add-mat').value || '—';
            const len = parseFloat(document.getElementById('add-len').value) || 0;
            const width = parseFloat(document.getElementById('add-width').value) || 0;
            const unitPrice = parseFloat(document.getElementById('add-price').value) || 0;

            if (len <= 0 || width <= 0) {
                alert('Вкажіть довжину і ширину в мм!');
                return;
            }

            appItems.push({
                id: Date.now(),
                name: name,
                mat: mat,
                len: len,
                width: width,
                unitPrice: unitPrice,
                qty: 1,
                selected: true
            });

            document.getElementById('add-name').value = '';
            document.getElementById('add-len').value = '';
            document.getElementById('add-width').value = '';
            document.getElementById('add-price').value = '';

            appSave();
            renderProducts();
        };

        window.duplicateProduct = function(id) {
            const orig = appItems.find(i => i.id === id);
            if (!orig) return;
            const copy = Object.assign({}, orig, {
                id: Date.now(),
                name: orig.name + ' (копія)',
                selected: true
            });
            const idx = appItems.findIndex(i => i.id === id);
            appItems.splice(idx + 1, 0, copy);
            appSave();
            renderProducts();
        };

        window.openEditProduct = function(id) {
            const it = appItems.find(i => i.id === id);
            if (!it) return;
            document.getElementById('edit-prod-id').value = it.id;
            document.getElementById('edit-prod-name').value = it.name;
            const editSelect = document.getElementById('edit-prod-mat');
            if (editSelect) editSelect.value = it.mat;
            document.getElementById('edit-prod-len').value = it.len;
            document.getElementById('edit-prod-width').value = it.width;
            document.getElementById('edit-prod-price').value = it.unitPrice;
            document.getElementById('edit-prod-modal').style.display = 'flex';
        };

        window.closeEditModal = function() {
            document.getElementById('edit-prod-modal').style.display = 'none';
        };

        window.saveEditedProduct = function() {
            const id = parseInt(document.getElementById('edit-prod-id').value);
            const it = appItems.find(i => i.id === id);
            if (it) {
                it.name = document.getElementById('edit-prod-name').value.trim() || 'Виріб';
                it.mat = document.getElementById('edit-prod-mat').value || '—';
                it.len = parseFloat(document.getElementById('edit-prod-len').value) || 0;
                it.width = parseFloat(document.getElementById('edit-prod-width').value) || 0;
                it.unitPrice = parseFloat(document.getElementById('edit-prod-price').value) || 0;
                appSave();
                renderProducts();
                closeEditModal();
            }
        };

        window.deleteProduct = function(id) {
            const it = appItems.find(i => i.id === id);
            openDeleteModal(
                'Видалити виріб?',
                'Ви впевнені, що хочете видалити виріб "' + (it ? it.name : '') + '"?',
                function() {
                    appItems = appItems.filter(i => i.id !== id);
                    appSave();
                    renderProducts();
                }
            );
        };

        window.updateProductQty = function(id, val) {
            const it = appItems.find(i => i.id === id);
            if (it) {
                it.qty = Math.max(1, parseInt(val) || 1);
                appSave();
                renderProducts();
            }
        };

        window.toggleProductSelect = function(id) {
            const it = appItems.find(i => i.id === id);
            if (it) {
                it.selected = !it.selected;
                appSave();
                renderProducts();
            }
        };

        window.toggleSelectAll = function() {
            const chk = document.getElementById('chk-all').checked;
            appItems.forEach(i => i.selected = chk);
            appSave();
            renderProducts();
        };

        window.toggleHideUnselected = function() {
            hideUnselected = !hideUnselected;
            const btn = document.getElementById('btn-toggle-filter');
            if (hideUnselected) {
                btn.classList.add('btn-active');
                btn.textContent = '✓ Показати всі';
            } else {
                btn.classList.remove('btn-active');
                btn.textContent = '👁️ Сховати невиділені';
            }
            renderProducts();
        };

        window.toggleInvoiceMode = function() {
            const app = document.getElementById('wood-calc-app');
            app.classList.toggle('invoice-mode');
            document.getElementById('inv-date').textContent = 'Дата: ' + new Date().toLocaleDateString('uk-UA');
            if (app.classList.contains('invoice-mode')) {
                hideUnselected = true;
            } else {
                hideUnselected = false;
            }
            renderProducts();
            window.scrollTo({top: 0, behavior: 'smooth'});
        };

        // 7. МОДАЛЬНЕ ВІКНО ПІДТВЕРДЖЕННЯ ВИДАЛЕННЯ (В СТИЛІ САЙТУ)
        function openDeleteModal(title, desc, onConfirm) {
            document.getElementById('del-modal-title').textContent = title;
            document.getElementById('del-modal-desc').textContent = desc;
            deleteActionCallback = onConfirm;
            document.getElementById('delete-confirm-modal').style.display = 'flex';
        }

        window.closeDeleteModal = function() {
            document.getElementById('delete-confirm-modal').style.display = 'none';
            deleteActionCallback = null;
        };

        document.getElementById('del-modal-confirm-btn').addEventListener('click', function() {
            if (typeof deleteActionCallback === 'function') {
                deleteActionCallback();
            }
            closeDeleteModal();
        });

        // 8. ВІДОБРАЖЕННЯ ВИРОБІВ
        window.renderProducts = function() {
            const app = document.getElementById('wood-calc-app');
            const isInvoice = app && app.classList.contains('invoice-mode');

            const showMat = !isInvoice || document.getElementById('col-inv-mat').checked;
            const showDim = !isInvoice || document.getElementById('col-inv-dim').checked;
            const showPrice = !isInvoice || document.getElementById('col-inv-price').checked;
            const showQty = !isInvoice || document.getElementById('col-inv-qty').checked;
            const showSum = !isInvoice || document.getElementById('col-inv-sum').checked;

            const thMat = document.querySelector('th.col-mat');
            const thDim = document.querySelector('th.col-dim');
            const thPrice = document.querySelector('th.col-price');
            const thQty = document.querySelector('th.col-qty');
            const thSum = document.querySelector('th.col-sum');

            if (thMat) thMat.style.display = showMat ? '' : 'none';
            if (thDim) thDim.style.display = showDim ? '' : 'none';
            if (thPrice) thPrice.style.display = showPrice ? '' : 'none';
            if (thQty) thQty.style.display = showQty ? '' : 'none';
            if (thSum) thSum.style.display = showSum ? '' : 'none';

            const tbody = document.getElementById('products-tbody');
            tbody.innerHTML = '';
            let total = 0;

            const visibleItems = hideUnselected ? appItems.filter(i => i.selected) : appItems;

            if (visibleItems.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align:center; color:#94a3b8; padding:28px;">Немає виробів для відображення</td></tr>';
                document.getElementById('grand-total').textContent = '0.00 грн';
                return;
            }

            visibleItems.forEach((it) => {
                const rowTotal = it.unitPrice * it.qty;
                if (it.selected) total += rowTotal;

                const tr = document.createElement('tr');
                tr.dataset.id = it.id;

                let html = `
                    <td class="no-invoice">
                        <span class="wc-drag-handle" title="Затисніть для зміни порядку" draggable="true">⠿</span>
                    </td>
                    <td class="no-invoice">
                        <input type="checkbox" ${it.selected ? 'checked' : ''} onchange="toggleProductSelect(${it.id})">
                    </td>
                    <td><strong>${it.name}</strong></td>
                `;

                if (showMat) html += `<td class="col-mat"><span style="color:#475569;">${it.mat}</span></td>`;
                if (showDim) html += `<td class="col-dim"><strong>${it.len} × ${it.width} мм</strong></td>`;
                if (showPrice) html += `<td class="col-price">${it.unitPrice.toFixed(2)} грн</td>`;

                if (showQty) {
                    if (isInvoice) {
                        html += `<td class="col-qty" style="text-align:center;">${it.qty} шт</td>`;
                    } else {
                        html += `
                            <td class="col-qty">
                                <input type="number" min="1" value="${it.qty}" style="width:62px; height:34px; text-align:center; font-weight:600;" onchange="updateProductQty(${it.id}, this.value)">
                            </td>
                        `;
                    }
                }

                if (showSum) html += `<td class="col-sum"><strong style="color:var(--tc-dark);">${rowTotal.toFixed(2)} грн</strong></td>`;

                html += `
                    <td class="no-invoice">
                        <div style="display:flex; gap:4px;">
                            <button type="button" class="btn-icon btn-edit" onclick="openEditProduct(${it.id})" title="Редагувати">✏️</button>
                            <button type="button" class="btn-icon btn-dup" onclick="duplicateProduct(${it.id})" title="Дублювати">📋</button>
                            <button type="button" class="btn-icon btn-del" onclick="deleteProduct(${it.id})" title="Видалити">✕</button>
                        </div>
                    </td>
                `;

                tr.innerHTML = html;
                tbody.appendChild(tr);
            });

            document.getElementById('grand-total').textContent = total.toFixed(2) + ' грн';
            setupDragAndDrop();
        };

        // 9. ПРОФЕСІЙНИЙ DRAG & DROP ЧЕРЕЗ РУЧКУ (⠿) З ПІДСВІЧУВАННЯМ
        let draggedRow = null;

        function setupDragAndDrop() {
            const handles = document.querySelectorAll('.wc-drag-handle');
            const rows = document.querySelectorAll('#products-tbody tr');

            handles.forEach(handle => {
                handle.addEventListener('dragstart', function(e) {
                    draggedRow = this.closest('tr');
                    draggedRow.classList.add('row-dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', draggedRow.dataset.id);
                });

                handle.addEventListener('dragend', function() {
                    if (draggedRow) draggedRow.classList.remove('row-dragging');
                    rows.forEach(r => r.classList.remove('drop-above', 'drop-below'));
                    draggedRow = null;
                });
            });

            rows.forEach(row => {
                row.addEventListener('dragover', function(e) {
                    if (!draggedRow || draggedRow === this) return;
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';

                    const rect = this.getBoundingClientRect();
                    const midY = rect.top + rect.height / 2;

                    rows.forEach(r => r.classList.remove('drop-above', 'drop-below'));
                    if (e.clientY < midY) {
                        this.classList.add('drop-above');
                    } else {
                        this.classList.add('drop-below');
                    }
                });

                row.addEventListener('dragleave', function() {
                    this.classList.remove('drop-above', 'drop-below');
                });

                row.addEventListener('drop', function(e) {
                    if (!draggedRow || draggedRow === this) return;
                    e.preventDefault();

                    const rect = this.getBoundingClientRect();
                    const midY = rect.top + rect.height / 2;
                    const isAbove = e.clientY < midY;

                    const fromId = parseInt(draggedRow.dataset.id);
                    const toId = parseInt(this.dataset.id);

                    const fromIndex = appItems.findIndex(i => i.id === fromId);
                    let toIndex = appItems.findIndex(i => i.id === toId);

                    if (fromIndex > -1 && toIndex > -1) {
                        const [movedItem] = appItems.splice(fromIndex, 1);
                        // Якщо перетягували згори вниз, коригуємо індекс
                        if (fromIndex < toIndex) {
                            toIndex = appItems.findIndex(i => i.id === toId);
                        }
                        const insertIndex = isAbove ? toIndex : toIndex + 1;
                        appItems.splice(insertIndex, 0, movedItem);

                        appSave();
                        renderProducts();
                    }

                    rows.forEach(r => r.classList.remove('drop-above', 'drop-below'));
                });
            });
        }

        appLoad();
    })();
    </script>
    <?php
    return ob_get_clean();
}
