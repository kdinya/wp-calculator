<?php
if (!defined('ABSPATH')) {
    exit;
}

class WpCalculatorAdmin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Калькулятор виробів',
            'Калькулятор виробів',
            'manage_options',
            'wp-calculator',
            array($this, 'render_admin_page'),
            'dashicons-calculator',
            30
        );
    }

    public static function get_translations($lang = 'uk') {
        $dict = array(
            'uk' => array(
                'header_title' => '🛠️ Розрахунок вартості виробів',
                'tab_calc' => 'Калькулятор',
                'tab_appearance' => 'Оформлення',
                'tab_settings' => 'Налаштування',
                'syncing' => '● Синхронізація...',
                'btn_backup' => 'Завантажити бекап (JSON)',
                'backup_title' => '💾 Резервне копіювання даних',
                'backup_desc' => 'Збережіть повну резервну копію ваших матеріалів, виробів та налаштувань у файлі JSON для безпеки або перенесення.',
                'sec1_title' => '1. Швидкий калькулятор вартості',
                'lbl_calc_mat' => 'Матеріал (тариф за 1 см²)',
                'lbl_calc_len' => 'Довжина (мм)',
                'lbl_calc_width' => 'Ширина (мм)',
                'btn_send_to_form' => 'Внести у виріб ↓',
                'res_area' => 'Розрахована площа:',
                'res_price' => 'Ціна за 1 шт:',
                'sec2_title' => 'Додати виріб у список',
                'btn_add_product' => 'Додати виріб',
                'lbl_add_name' => 'Назва виробу',
                'lbl_add_mat' => 'Оберіть матеріал',
                'lbl_add_price' => 'Ціна/шт (грн)',
                'btn_add_save' => '+ Зберегти',
                'sec3_title' => '3. Список виробів',
                'btn_select_all' => 'Виділити всі',
                'btn_deselect_all' => 'Зняти всі',
                'inv_cols_title' => 'Колонки для накладної:',
                'col_material' => 'Матеріал',
                'col_dims' => 'Розміри',
                'col_price_pc' => 'Ціна / 1 шт',
                'col_qty' => 'К-сть',
                'col_sum' => 'Сума',
                'col_actions' => 'Дії',
                'summary_selected' => 'Обрано виробів:',
                'summary_qty' => 'Загальна к-сть:',
                'summary_sum' => 'Загальна сума:',
                'pcs' => 'шт',
                'curr' => 'грн',
                'btn_invoice_mode' => '📄 Переглянути накладну',
                'modal_copy_prod_title' => 'Копіювання виробу',
                'btn_back_to_calc' => '← Повернутися до калькулятора',
                'btn_exit_invoice' => '← Повернутися до калькулятора',
                'invoice_title' => 'РОЗРАХУНОК ЗАМОВЛЕННЯ',
                'sec_materials_title' => '📦 Довідник матеріалів',
                'lbl_mat_name' => 'Назва матеріалу',
                'lbl_mat_rate' => 'Тариф за 1 см² (грн)',
                'btn_mat_add' => '+ Додати',
                'appearance_title' => '🎨 Зовнішній вигляд',
                'lbl_accent_color' => 'Акцентний колір кнопок та активних елементів',
                'lbl_custom_color' => 'Довільний колір:',
                'appearance_desc' => 'Обраний колір миттєво застосовується до кнопок, чекбоксів, підсвітки та рамок інтерфейсу без перезавантаження сторінки.',
                'settings_title' => '⚙️ Налаштування калькулятора',
                'settings_lang' => 'Мова інтерфейсу',
                'settings_lang_desc' => 'Обрана мова зберігається автоматично та використовується для калькулятора, каталогу і накладної.',
                'lbl_wipe_uninstall' => 'Видаляти всі дані та налаштування при повному видаленні плагіна',
                'desc_wipe_uninstall' => 'Якщо вимкнено — ваші створені матеріали, каталог виробів та налаштування збережуться навіть після деінсталяції плагіна.',
                'updater_title' => '🚀 Оновлення плагіна з GitHub',
                'lbl_current_ver' => 'Поточна версія:',
                'btn_check_update' => 'Перевірити оновлення',
                'btn_apply_update' => 'Оновити плагін зараз',
                'lbl_changelog' => 'Зміни в релізі:',
                'modal_edit_prod_title' => 'Редагувати виріб',
                'modal_edit_mat_title' => 'Редагувати матеріал',
                'tab_catalog' => 'Всі вироби',
                'catalog_title' => '📋 Каталог усіх створених виробів',
                'catalog_desc' => 'Тут зберігаються всі ваші створені вироби. Ви можете додавати їх у накладну, редагувати, дублювати або впорядковувати перетягуванням.',
                'chk_add_to_invoice' => 'Додати в накладну',
                'btn_add_from_catalog' => '+ Додати з каталогу',
                'btn_add_to_inv' => '+ В накладну',
                'in_invoice_badge' => 'В накладній',
                'btn_remove_from_inv' => 'Прибрати з накладної',
                'modal_catalog_title' => 'Додати вироби з каталогу в накладну',
                'no_catalog_items' => 'Немає створених виробів у каталозі',
                'no_invoice_items' => 'У накладній ще немає виробів. Додайте створений виріб або оберіть з вкладки «Всі вироби».',
                'btn_download_png' => 'Завантажити PNG',
                'btn_copy_png' => 'Скопіювати картинку',
                'btn_download_pdf' => 'Завантажити PDF',
                'btn_download_excel' => 'Завантажити Excel',
                'btn_share_invoice' => 'Поділитися',
                'btn_email_invoice' => 'Надіслати на Email',
                'copied_image_success' => 'Зображення скопійовано в буфер обміну!',
                'copied_image_failed' => 'Не вдалося скопіювати зображення.',
                'all_items_in_invoice' => 'Усі вироби з каталогу вже є в накладній!',
                'catalog_status_in_inv' => 'В накладній',
                'catalog_status_not_in_inv' => 'Не в накладній',
                'modal_del_title' => 'Підтвердження видалення',
                'modal_del_text' => 'Ви дійсно бажаєте видалити цей елемент? Цю дію неможливо буде скасувати.',
                'btn_cancel' => 'Скасувати',
                'btn_close' => 'Закрити',
                'btn_save' => 'Зберегти зміни',
                'modal_add_catalog_title' => 'Додати новий виріб у каталог',
                'btn_create_in_catalog' => 'Зберегти в каталог',
                'col_status' => 'Статус',
                'err_invalid_dims' => 'Будь ласка, вкажіть коректні розміри (довжина та ширина мають бути більше 0).',
                'err_invalid_price' => 'Ціна повинна бути 0 або більше.',
                'err_enter_name' => 'Будь ласка, введіть назву виробу.',
                'data_recovered_toast' => 'Дані успішно відновлено з локального дзеркала!',
                'chk_modal_add_to_inv' => 'Додати також у поточну накладну',
                'catalog_item_added' => 'Виріб успішно додано до каталогу!',
                'btn_delete' => 'Видалити'
            ),
            'en' => array(
                'header_title' => '🛠️ Product Cost Calculator',
                'tab_calc' => 'Calculator',
                'tab_appearance' => 'Appearance',
                'tab_settings' => 'Settings',
                'syncing' => '● Syncing...',
                'btn_backup' => 'Download Backup (JSON)',
                'backup_title' => '💾 Data Backup',
                'backup_desc' => 'Save a complete backup of your materials, items, and settings in JSON format for security or migration.',
                'sec1_title' => '1. Quick Cost Calculator',
                'lbl_calc_mat' => 'Material (rate per 1 cm²)',
                'lbl_calc_len' => 'Length (mm)',
                'lbl_calc_width' => 'Width (mm)',
                'btn_send_to_form' => 'Add to Product Form ↓',
                'res_area' => 'Calculated Area:',
                'res_price' => 'Unit Price:',
                'sec2_title' => 'Add Product to List',
                'btn_add_product' => 'Add Product',
                'lbl_add_name' => 'Product Name',
                'lbl_add_mat' => 'Select Material',
                'lbl_add_price' => 'Price/unit (UAH)',
                'btn_add_save' => '+ Save',
                'sec3_title' => '3. Product List',
                'btn_select_all' => 'Select All',
                'btn_deselect_all' => 'Deselect All',
                'inv_cols_title' => 'Columns for Invoice:',
                'col_material' => 'Material',
                'col_dims' => 'Dimensions',
                'col_price_pc' => 'Price / 1 pc',
                'col_qty' => 'Qty',
                'col_sum' => 'Total',
                'col_actions' => 'Actions',
                'summary_selected' => 'Selected products:',
                'summary_qty' => 'Total quantity:',
                'summary_sum' => 'Grand total:',
                'pcs' => 'pcs',
                'curr' => 'UAH',
                'btn_invoice_mode' => '📄 View Invoice',
                'modal_copy_prod_title' => 'Copy Product',
                'btn_back_to_calc' => '← Back to Calculator',
                'btn_exit_invoice' => '← Back to Calculator',
                'invoice_title' => 'ORDER ESTIMATE',
                'sec_materials_title' => '📦 Materials Directory',
                'lbl_mat_name' => 'Material Name',
                'lbl_mat_rate' => 'Rate per 1 cm² (UAH)',
                'btn_mat_add' => '+ Add',
                'appearance_title' => '🎨 Appearance',
                'lbl_accent_color' => 'Accent color for buttons and active elements',
                'lbl_custom_color' => 'Custom color:',
                'appearance_desc' => 'The selected color is instantly applied to buttons, checkboxes, highlights, and borders in real time without refreshing.',
                'settings_title' => '⚙️ Calculator Settings',
                'settings_lang' => 'Interface Language',
                'settings_lang_desc' => 'The chosen language is saved automatically and applies to the calculator, catalog, and invoice.',
                'lbl_wipe_uninstall' => 'Delete all data and settings on full plugin uninstall',
                'desc_wipe_uninstall' => 'When unchecked, your created materials, catalog items, and settings are preserved even after uninstalling the plugin.',
                'updater_title' => '🚀 GitHub Plugin Updater',
                'lbl_current_ver' => 'Current version:',
                'btn_check_update' => 'Check for Updates',
                'btn_apply_update' => 'Update Plugin Now',
                'lbl_changelog' => 'Release Changelog:',
                'modal_edit_prod_title' => 'Edit Product',
                'modal_edit_mat_title' => 'Edit Material',
                'tab_catalog' => 'All Products',
                'catalog_title' => '📋 All Created Products Catalog',
                'catalog_desc' => 'All your created products are stored here. You can add them to the invoice, edit, duplicate or reorder by dragging.',
                'chk_add_to_invoice' => 'Add to invoice',
                'btn_add_from_catalog' => '+ Add from catalog',
                'btn_add_to_inv' => '+ To invoice',
                'in_invoice_badge' => 'In invoice',
                'btn_remove_from_inv' => 'Remove from invoice',
                'modal_catalog_title' => 'Add Products from Catalog to Invoice',
                'no_catalog_items' => 'No products created in catalog yet',
                'no_invoice_items' => 'No items in the invoice yet. Add a created product or choose from the All Products tab.',
                'btn_download_png' => 'Download PNG',
                'btn_copy_png' => 'Copy Image',
                'btn_download_pdf' => 'Download PDF',
                'btn_download_excel' => 'Download Excel',
                'btn_share_invoice' => 'Share',
                'btn_email_invoice' => 'Send via Email',
                'copied_image_success' => 'Image copied to clipboard!',
                'copied_image_failed' => 'Failed to copy image.',
                'all_items_in_invoice' => 'All products from catalog are already in the invoice!',
                'catalog_status_in_inv' => 'In invoice',
                'catalog_status_not_in_inv' => 'Not in invoice',

                'modal_del_title' => 'Confirm Deletion',
                'modal_del_text' => 'Are you sure you want to delete this item? This action cannot be undone.',
                'btn_cancel' => 'Cancel',
                'btn_close' => 'Close',
                'btn_save' => 'Save Changes',
                'modal_add_catalog_title' => 'Add New Product to Catalog',
                'btn_create_in_catalog' => 'Save to Catalog',
                'col_status' => 'Status',
                'err_invalid_dims' => 'Please enter valid dimensions (length and width must be greater than 0).',
                'err_invalid_price' => 'Price must be 0 or greater.',
                'err_enter_name' => 'Please enter product name.',
                'data_recovered_toast' => 'Data successfully recovered from local mirror!',
                'chk_modal_add_to_inv' => 'Also add to current invoice',
                'catalog_item_added' => 'Product added to catalog successfully!',
                'btn_delete' => 'Delete'
            )
        );

        $selected = ($lang === 'en') ? 'en' : 'uk';
        return $dict[$selected];
    }

    public function enqueue_assets($hook) {
        if (empty($hook) || strpos($hook, 'wp-calculator') === false) {
            return;
        }

        wp_enqueue_style(
            'wp-calculator-admin',
            WP_CALCULATOR_URL . 'assets/css/admin.css',
            array(),
            WP_CALCULATOR_VERSION
        );

        wp_enqueue_script(
            'wp-calculator-admin',
            WP_CALCULATOR_URL . 'assets/js/admin.js',
            array(),
            WP_CALCULATOR_VERSION,
            true
        );

        $stored_data = wood_calc_get_stored_data();
        $saved_settings = isset($stored_data['settings']) && is_array($stored_data['settings']) ? $stored_data['settings'] : array();
        $current_lang = isset($saved_settings['lang']) && in_array($saved_settings['lang'], array('uk', 'en'), true) ? $saved_settings['lang'] : 'uk';
        $accent_color = isset($saved_settings['accent_color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $saved_settings['accent_color']) ? $saved_settings['accent_color'] : '#95b504';
        $wipe_on_uninstall = !empty($saved_settings['wipe_on_uninstall']);

        $bootstrap_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wood_calc_nonce'),
            'data'     => $stored_data,
            'settings' => array(
                'lang' => $current_lang,
                'accent_color' => $accent_color,
                'wipe_on_uninstall' => $wipe_on_uninstall
            ),
            'version'  => WP_CALCULATOR_VERSION
        );

        wp_add_inline_script(
            'wp-calculator-admin',
            'window.WOOD_CALC_BOOTSTRAP = ' . wp_json_encode($bootstrap_data) . ';',
            'before'
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Недостатньо прав для доступу до цієї сторінки.');
        }

        $stored_data = wood_calc_get_stored_data();
        $saved_settings = isset($stored_data['settings']) && is_array($stored_data['settings']) ? $stored_data['settings'] : array();
        $current_lang = isset($saved_settings['lang']) && in_array($saved_settings['lang'], array('uk', 'en'), true) ? $saved_settings['lang'] : 'uk';
        $accent_color = isset($saved_settings['accent_color']) && preg_match('/^#[0-9a-fA-F]{6}$/', $saved_settings['accent_color']) ? $saved_settings['accent_color'] : '#95b504';
        $t = self::get_translations($current_lang);
        $nonce = wp_create_nonce('wood_calc_nonce');
        ?>
        <div class="wrap">
            <input type="hidden" id="wood-calc-nonce" value="<?php echo esc_attr($nonce); ?>">
            <div id="wood-calculator-app" class="tc-app-wrapper" style="--tc-green: <?php echo esc_attr($accent_color); ?>;">

                <div class="tc-tab-bar no-invoice">
                    <button type="button" class="tc-tab-btn active" id="tab-nav-calc" onclick="switchWcTab('calc')">
                        <span>🧮</span> <span data-i18n="tab_calc"><?php echo esc_html($t['tab_calc']); ?></span>
                    </button>
                    <button type="button" class="tc-tab-btn" id="tab-nav-catalog" onclick="switchWcTab('catalog')">
                        <span>📋</span> <span data-i18n="tab_catalog"><?php echo esc_html($t['tab_catalog']); ?></span>
                    </button>
                    <button type="button" class="tc-tab-btn" id="tab-nav-appearance" onclick="switchWcTab('appearance')">
                        <span>🎨</span> <span data-i18n="tab_appearance"><?php echo esc_html($t['tab_appearance']); ?></span>
                    </button>
                    <button type="button" class="tc-tab-btn" id="tab-nav-settings" onclick="switchWcTab('settings')">
                        <span>⚙️</span> <span data-i18n="tab_settings"><?php echo esc_html($t['tab_settings']); ?></span>
                    </button>
                </div>

                <!-- ВКЛАДКА 1: КАЛЬКУЛЯТОР -->
                <div id="wc-tab-pane-calc" class="wc-tab-pane">
                    <div class="tc-header no-invoice">
                        <h1 data-i18n="header_title"><?php echo esc_html($t['header_title']); ?></h1>
                        <div class="tc-header-actions">
                            <span id="wc-status" style="font-size:12px; color:#64748b;" data-i18n="syncing"><?php echo esc_html($t['syncing']); ?></span>
                        </div>
                    </div>

                    <div class="tc-layout">
                        <div class="tc-main-col">
                            
                            <!-- 1. ШВИДКИЙ КАЛЬКУЛЯТОР -->
                            <div class="tc-card no-invoice">
                                <h2 data-i18n="sec1_title"><?php echo esc_html($t['sec1_title']); ?></h2>
                                <div class="grid-calc">
                                    <div class="full-mobile">
                                        <label data-i18n="lbl_calc_mat"><?php echo esc_html($t['lbl_calc_mat']); ?></label>
                                        <select id="calc-mat" onchange="runQuickCalc()"></select>
                                    </div>
                                    <div>
                                        <label data-i18n="lbl_calc_len"><?php echo esc_html($t['lbl_calc_len']); ?></label>
                                        <input type="number" id="calc-len" placeholder="500" oninput="runQuickCalc()">
                                    </div>
                                    <div>
                                        <label data-i18n="lbl_calc_width"><?php echo esc_html($t['lbl_calc_width']); ?></label>
                                        <input type="number" id="calc-width" placeholder="300" oninput="runQuickCalc()">
                                    </div>
                                    <div class="full-mobile">
                                        <button type="button" class="btn btn-green" style="width:100%;" onclick="sendToSaveForm()" data-i18n="btn_send_to_form"><?php echo esc_html($t['btn_send_to_form']); ?></button>
                                    </div>
                                </div>
                                <div class="calc-result-box">
                                    <div>
                                        <span style="font-size:12px; color:#64748b;" data-i18n="res_area"><?php echo esc_html($t['res_area']); ?></span>
                                        <strong id="res-area" style="font-size:15px; margin-left:4px;">0 см²</strong>
                                    </div>
                                    <div>
                                        <span style="font-size:12px; color:#64748b;" data-i18n="res_price"><?php echo esc_html($t['res_price']); ?></span>
                                        <strong id="res-price" style="font-size:18px; color:var(--tc-dark); margin-left:6px;">0.00 грн</strong>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. ДОДАТИ ВИРІБ У СПИСОК -->
                            <div class="tc-card no-invoice">
                                <h2 data-i18n="sec2_title"><?php echo esc_html($t['sec2_title']); ?></h2>
                                <div class="grid-add-product">
                                    <div>
                                        <label data-i18n="lbl_add_name"><?php echo esc_html($t['lbl_add_name']); ?></label>
                                        <input type="text" id="add-name" placeholder="<?php echo esc_attr($current_lang === 'en' ? 'e.g. Oak Board' : 'напр. Дошка дубова'); ?>">
                                    </div>
                                    <div>
                                        <label data-i18n="lbl_add_mat"><?php echo esc_html($t['lbl_add_mat']); ?></label>
                                        <select id="add-mat"></select>
                                    </div>
                                    <div>
                                        <label data-i18n="lbl_calc_len"><?php echo esc_html($t['lbl_calc_len']); ?></label>
                                        <input type="number" id="add-len" placeholder="мм">
                                    </div>
                                    <div>
                                        <label data-i18n="lbl_calc_width"><?php echo esc_html($t['lbl_calc_width']); ?></label>
                                        <input type="number" id="add-width" placeholder="мм">
                                    </div>
                                    <div>
                                        <label data-i18n="lbl_add_price"><?php echo esc_html($t['lbl_add_price']); ?></label>
                                        <input type="number" step="any" id="add-price" placeholder="0.00">
                                    </div>
                                    <div>
                                        <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                                            <button type="button" class="btn btn-dark" onclick="addCustomProduct()" data-i18n="btn_add_save"><?php echo esc_html($t['btn_add_save']); ?></button>
                                            <label class="tc-checkbox-label" style="display:inline-flex; align-items:center; gap:6px; font-size:13px; cursor:pointer; margin:0; user-select:none;">
                                                <input type="checkbox" id="add-to-invoice-chk" checked>
                                                <span data-i18n="chk_add_to_invoice"><?php echo esc_html($t['chk_add_to_invoice']); ?></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            
                            <!-- Панель дій та експорту в режимі накладної -->
                            <div class="invoice-actions-bar">
                                <button type="button" class="btn btn-outline btn-sm" onclick="downloadInvoicePng()" title="Завантажити накладну як картинку PNG" data-i18n="btn_download_png">
                                    <span>🖼️</span> <?php echo esc_html($t['btn_download_png']); ?>
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="copyInvoicePng()" title="Скопіювати зображення в буфер для вставки в чат" data-i18n="btn_copy_png">
                                    <span>📋</span> <?php echo esc_html($t['btn_copy_png']); ?>
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="downloadInvoicePdf()" title="Завантажити накладну у форматі PDF" data-i18n="btn_download_pdf">
                                    <span>📄</span> <?php echo esc_html($t['btn_download_pdf']); ?>
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="downloadInvoiceExcel()" title="Експортувати замовлення в таблицю Excel" data-i18n="btn_download_excel">
                                    <span>📊</span> <?php echo esc_html($t['btn_download_excel']); ?>
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" id="btn-share-wc" onclick="shareInvoice()" title="Поділитися накладною через будь-який додаток" data-i18n="btn_share_invoice">
                                    <span>📲</span> <?php echo esc_html($t['btn_share_invoice']); ?>
                                </button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="emailInvoice()" title="Надіслати замовлення по email" data-i18n="btn_email_invoice">
                                    <span>✉️</span> <?php echo esc_html($t['btn_email_invoice']); ?>
                                </button>
                                <button type="button" class="btn btn-dark btn-sm" onclick="exitInvoiceMode()" data-i18n="btn_exit_invoice" style="margin-left:auto;">
                                    <?php echo esc_html($t['btn_exit_invoice']); ?>
                                </button>
                            </div>

                            <!-- 3. СПИСОК ВИРОБІВ / БЛОК НАКЛАДНОЇ -->
                            <div id="invoice-print-card" class="tc-card" style="margin-bottom:0;">
                                <div class="invoice-header-box">
                                    <div>
                                        <h2 style="margin:0; font-size:22px; color:var(--tc-dark); letter-spacing:0.5px;" data-i18n="invoice_title"><?php echo esc_html($t['invoice_title']); ?></h2>
                                        <div style="font-size:13px; color:#64748b; margin-top:4px;" id="inv-date"></div>
                                    </div>
                                </div>

                                <h2 class="no-invoice tc-sec3-header">
                                    <span data-i18n="sec3_title"><?php echo esc_html($t['sec3_title']); ?></span>
                                    <div class="tc-sec3-actions">

                                        <button type="button" class="btn btn-outline btn-sm" onclick="openAddFromCatalogModal()" data-i18n="btn_add_from_catalog"><?php echo esc_html($t['btn_add_from_catalog']); ?></button>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="toggleSelectAll(true)" data-i18n="btn_select_all"><?php echo esc_html($t['btn_select_all']); ?></button>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="toggleSelectAll(false)" data-i18n="btn_deselect_all"><?php echo esc_html($t['btn_deselect_all']); ?></button>
                                    </div>
                                </h2>

                                <!-- Вибір колонок для накладної -->
                                <div class="no-invoice" style="background:#f8fafc; padding:10px 14px; border-radius:6px; margin-bottom:12px; border:1px solid #e2e8f0; display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
                                    <strong style="font-size:12px; color:#475569;" data-i18n="inv_cols_title"><?php echo esc_html($t['inv_cols_title']); ?></strong>
                                    <label style="display:inline-flex; align-items:center; gap:5px; margin:0; cursor:pointer; font-size:12px;">
                                        <input type="checkbox" id="col-toggle-mat" checked onchange="toggleColumnVisibility('mat', this.checked)"> <span data-i18n="col_material"><?php echo esc_html($t['col_material']); ?></span>
                                    </label>
                                    <label style="display:inline-flex; align-items:center; gap:5px; margin:0; cursor:pointer; font-size:12px;">
                                        <input type="checkbox" id="col-toggle-dims" checked onchange="toggleColumnVisibility('dims', this.checked)"> <span data-i18n="col_dims"><?php echo esc_html($t['col_dims']); ?></span>
                                    </label>
                                    <label style="display:inline-flex; align-items:center; gap:5px; margin:0; cursor:pointer; font-size:12px;">
                                        <input type="checkbox" id="col-toggle-price" checked onchange="toggleColumnVisibility('price', this.checked)"> <span data-i18n="col_price_pc"><?php echo esc_html($t['col_price_pc']); ?></span>
                                    </label>
                                    <label style="display:inline-flex; align-items:center; gap:5px; margin:0; cursor:pointer; font-size:12px;">
                                        <input type="checkbox" id="col-toggle-qty" checked onchange="toggleColumnVisibility('qty', this.checked)"> <span data-i18n="col_qty"><?php echo esc_html($t['col_qty']); ?></span>
                                    </label>
                                    <label style="display:inline-flex; align-items:center; gap:5px; margin:0; cursor:pointer; font-size:12px;">
                                        <input type="checkbox" id="col-toggle-sum" checked onchange="toggleColumnVisibility('sum', this.checked)"> <span data-i18n="col_sum"><?php echo esc_html($t['col_sum']); ?></span>
                                    </label>
                                </div>

                                <!-- Таблиця виробів -->
                                <div style="overflow-x:auto;">
                                    <div class="tc-table-responsive">
                                        <table class="tc-table" id="items-table">
                                            <thead>
                                                <tr>
                                                    <th style="width:40px;" class="no-invoice"></th>
                                                    <th style="width:30px;" class="no-invoice"><input type="checkbox" id="select-all-top" onchange="toggleSelectAll(this.checked)"></th>
                                                    <th data-i18n="lbl_add_name"><?php echo esc_html($t['lbl_add_name']); ?></th>
                                                    <th class="col-mat-header" data-i18n="col_material"><?php echo esc_html($t['col_material']); ?></th>
                                                    <th class="col-dims-header" data-i18n="col_dims"><?php echo esc_html($t['col_dims']); ?></th>
                                                    <th class="col-price-header" data-i18n="col_price_pc"><?php echo esc_html($t['col_price_pc']); ?></th>
                                                    <th class="col-qty-header" style="width:90px;" data-i18n="col_qty"><?php echo esc_html($t['col_qty']); ?></th>
                                                    <th class="col-sum-header" style="text-align:right;" data-i18n="col_sum"><?php echo esc_html($t['col_sum']); ?></th>
                                                    <th style="width:130px; text-align:right;" class="no-invoice" data-i18n="col_actions"><?php echo esc_html($t['col_actions']); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody id="items-tbody"></tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="summary-bar">
                                    <div style="font-size:13px; color:#475569;">
                                        <span data-i18n="summary_selected"><?php echo esc_html($t['summary_selected']); ?></span> <strong id="sum-items-count">0</strong> | 
                                        <span data-i18n="summary_qty"><?php echo esc_html($t['summary_qty']); ?></span> <strong id="sum-total-qty">0</strong> <span data-i18n="pcs"><?php echo esc_html($t['pcs']); ?></span>
                                    </div>
                                    <div class="summary-total">
                                        <span data-i18n="summary_sum"><?php echo esc_html($t['summary_sum']); ?></span> <span id="sum-grand-total">0.00</span> <span data-i18n="curr"><?php echo esc_html($t['curr']); ?></span>
                                    </div>
                                </div>

                                <!-- Кнопка переходу до накладної на сторінці калькулятора -->
                                <div style="margin-top:18px; display:flex; justify-content:flex-end; align-items:center;" class="no-invoice">
                                    <button type="button" class="btn btn-green" id="btn-invoice-mode" onclick="enterInvoiceMode()" data-i18n="btn_invoice_mode" style="padding:10px 22px; font-size:14px; font-weight:700; display:inline-flex; align-items:center; gap:8px;">
                                        <span>📄</span> <?php echo esc_html($t['btn_invoice_mode']); ?>
                                    </button>
                                </div>
                            </div>

                            <div id="exit-invoice-container" style="display:none; margin-top:20px; text-align:center;">
                                <button type="button" class="btn btn-dark" onclick="exitInvoiceMode()" style="padding:10px 24px; font-size:14px;" data-i18n="btn_exit_invoice"><?php echo esc_html($t['btn_exit_invoice']); ?></button>
                            </div>

                        </div>

                        <!-- ПРАВИЙ СТОВПЧИК: Довідник матеріалів -->
                        <div class="tc-side-col no-invoice">
                            <div class="tc-card">
                                <h2 data-i18n="sec_materials_title"><?php echo esc_html($t['sec_materials_title']); ?></h2>
                                <div style="display:flex; flex-direction:column; gap:10px;">
                                    <div>
                                        <label data-i18n="lbl_mat_name"><?php echo esc_html($t['lbl_mat_name']); ?></label>
                                        <input type="text" id="mat-name" placeholder="<?php echo esc_attr($current_lang === 'en' ? 'e.g. Premium Oak' : 'напр. Дуб селект'); ?>">
                                    </div>
                                    <div>
                                        <label data-i18n="lbl_mat_rate"><?php echo esc_html($t['lbl_mat_rate']); ?></label>
                                        <input type="number" step="0.00001" id="mat-price" placeholder="0.20123">
                                    </div>
                                    <button type="button" class="btn btn-dark" onclick="addNewMaterial()" data-i18n="btn_mat_add"><?php echo esc_html($t['btn_mat_add']); ?></button>
                                </div>

                                <div class="tc-table-responsive">
                                    <table class="tc-table" style="margin-top:16px;">
                                        <thead>
                                            <tr>
                                                <th data-i18n="col_material"><?php echo esc_html($t['col_material']); ?></th>
                                                <th>грн/см²</th>
                                                <th style="width:70px; text-align:right;"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="materials-tbody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                                <!-- ВКЛАДКА 2: ВСІ ВИРОБИ (КАТАЛОГ) -->
                <div id="wc-tab-pane-catalog" class="wc-tab-pane" style="display:none;">
                    <div class="tc-card">
                        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:16px;">
                            <div>
                                <h2 style="margin:0; font-size:20px; color:var(--tc-dark);" data-i18n="catalog_title"><?php echo esc_html($t['catalog_title']); ?></h2>
                                <p style="margin:4px 0 0 0; font-size:13px; color:#64748b;" data-i18n="catalog_desc"><?php echo esc_html($t['catalog_desc']); ?></p>
                            </div>
                            <button type="button" class="btn btn-green" onclick="openAddCatalogItemModal()">
                                <span>➕</span> <span data-i18n="btn_add_product"><?php echo esc_html($t['btn_add_product']); ?></span>
                            </button>
                        </div>
                        <div class="tc-table-responsive">
                            <table class="tc-table" id="catalog-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px;"></th>
                                        <th data-i18n="lbl_add_name"><?php echo esc_html($t['lbl_add_name']); ?></th>
                                        <th data-i18n="col_material"><?php echo esc_html($t['col_material']); ?></th>
                                        <th data-i18n="col_dims"><?php echo esc_html($t['col_dims']); ?></th>
                                        <th data-i18n="col_price_pc"><?php echo esc_html($t['col_price_pc']); ?></th>
                                        <th style="width:160px; text-align:center;" data-i18n="col_status"><?php echo esc_html($t['col_status']); ?></th>
                                        <th style="width:130px; text-align:right;" data-i18n="col_actions"><?php echo esc_html($t['col_actions']); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="catalog-tab-tbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

<!-- ВКЛАДКА: ОФОРМЛЕННЯ -->
                <div id="wc-tab-pane-appearance" class="wc-tab-pane" style="display:none;">
                    <div class="tc-card">
                        <h2 data-i18n="appearance_title"><?php echo esc_html($t['appearance_title']); ?></h2>
                        <div style="max-width:650px;">
                            <label style="font-weight:700; font-size:14px; margin-bottom:8px; display:block;" data-i18n="lbl_accent_color"><?php echo esc_html($t['lbl_accent_color']); ?></label>
                            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;" id="wc-color-presets"></div>
                            <div style="display:flex; gap:12px; align-items:center; margin-top:16px;">
                                <label style="display:inline-flex; align-items:center; gap:8px; font-size:13px; font-weight:600; margin:0;">
                                    <span data-i18n="lbl_custom_color"><?php echo esc_html($t['lbl_custom_color']); ?></span>
                                    <input type="color" id="wc-custom-color" style="width:48px; height:36px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" onchange="setAccentColor(this.value)">
                                </label>
                            </div>
                            <p style="font-size:13px; color:#64748b; margin-top:14px; line-height:1.6;" data-i18n="appearance_desc">
                                <?php echo esc_html($t['appearance_desc']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ВКЛАДКА 2: НАЛАШТУВАННЯ ТА ОНОВЛЕННЯ -->
                <div id="wc-tab-pane-settings" class="wc-tab-pane" style="display:none;">
                    <div style="display:flex; flex-direction:column; gap:20px;">
                        
                        <!-- Блок мови та параметрів -->
                        <div class="tc-card">
                            <h2 data-i18n="settings_title"><?php echo esc_html($t['settings_title']); ?></h2>
                            <div style="max-width:550px; padding:10px 0;">
                                <label style="font-weight:700; font-size:14px; margin-bottom:8px; display:block;" data-i18n="settings_lang"><?php echo esc_html($t['settings_lang']); ?></label>
                                <div style="display:flex; gap:16px; align-items:center; flex-wrap:wrap; margin-top:10px;">
                                    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; background:#fff; border:1px solid #cbd5e1; padding:10px 16px; border-radius:8px; font-weight:600;">
                                        <input type="radio" name="wc_lang_choice" value="uk" id="wc-lang-uk" <?php checked($current_lang, 'uk'); ?> onchange="setWcLanguage('uk')">
                                        <span>🇺🇦 Українська</span>
                                    </label>
                                    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; background:#fff; border:1px solid #cbd5e1; padding:10px 16px; border-radius:8px; font-weight:600;">
                                        <input type="radio" name="wc_lang_choice" value="en" id="wc-lang-en" <?php checked($current_lang, 'en'); ?> onchange="setWcLanguage('en')">
                                        <span>🇬🇧 English</span>
                                    </label>
                                </div>
                                <p style="font-size:13px; color:#64748b; margin-top:14px; line-height:1.6;" data-i18n="settings_lang_desc">
                                    <?php echo esc_html($t['settings_lang_desc']); ?>
                                </p>

                                <div style="margin-top:24px; padding-top:16px; border-top:1px solid #e2e8f0;">
                                    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:13px; margin:0;">
                                        <input type="checkbox" id="wc-wipe-on-uninstall" <?php checked(!empty($saved_settings['wipe_on_uninstall'])); ?> onchange="toggleWipeOnUninstall(this.checked)">
                                        <span data-i18n="lbl_wipe_uninstall"><?php echo esc_html($t['lbl_wipe_uninstall']); ?></span>
                                    </label>
                                    <p style="font-size:12px; color:#94a3b8; margin:6px 0 0 26px;" data-i18n="desc_wipe_uninstall">
                                        <?php echo esc_html($t['desc_wipe_uninstall']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Блок резервного копіювання (Бекап перенесено в налаштування) -->
                        <div class="tc-card">
                            <h2 data-i18n="backup_title"><?php echo esc_html($t['backup_title']); ?></h2>
                            <div style="max-width:650px;">
                                <p style="font-size:13px; color:#64748b; margin-top:4px; line-height:1.6;" data-i18n="backup_desc">
                                    <?php echo esc_html($t['backup_desc']); ?>
                                </p>
                                <div style="margin-top:16px;">
                                    <button type="button" class="btn btn-dark" onclick="downloadDataBackup()">
                                        💾 <span data-i18n="btn_backup"><?php echo esc_html($t['btn_backup']); ?></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Блок оновлень з GitHub -->
                        <div class="tc-card">
                            <h2 data-i18n="updater_title"><?php echo esc_html($t['updater_title']); ?></h2>
                            <div style="max-width:650px;">
                                <div style="display:flex; align-items:center; gap:16px; margin-bottom:14px;">
                                    <span style="font-size:14px; color:#475569;">
                                        <strong data-i18n="lbl_current_ver"><?php echo esc_html($t['lbl_current_ver']); ?></strong> <code>v<?php echo esc_html(WP_CALCULATOR_VERSION); ?></code>
                                    </span>
                                    <span style="font-size:14px; color:#475569;" id="wc-latest-ver-box"></span>
                                </div>

                                <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                                    <button type="button" class="btn btn-dark" id="btn-check-update" onclick="checkGitHubUpdate()">
                                        🔍 <span data-i18n="btn_check_update"><?php echo esc_html($t['btn_check_update']); ?></span>
                                    </button>
                                    <button type="button" class="btn btn-green" id="btn-run-update" style="display:none;" onclick="runGitHubUpdate()">
                                        ⚡ <span data-i18n="btn_apply_update"><?php echo esc_html($t['btn_apply_update']); ?></span>
                                    </button>
                                    <span id="update-status-msg" style="font-size:13px; color:#64748b;"></span>
                                </div>

                                <div id="update-changelog-box" style="display:none; margin-top:16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px;">
                                    <strong style="font-size:13px; color:var(--tc-dark);" data-i18n="lbl_changelog"><?php echo esc_html($t['lbl_changelog']); ?></strong>
                                    <div id="update-changelog-text" style="font-size:13px; color:#475569; margin-top:6px; line-height:1.5;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- МОДАЛЬНЕ ВІКНО РЕДАГУВАННЯ ВИРОБУ -->
                <div id="modal-edit-product" class="modal-backdrop">
                    <div class="modal-content">
                        <h3 id="modal-edit-prod-title" style="margin-top:0; font-size:18px; color:var(--tc-dark);" data-i18n="modal_edit_prod_title"><?php echo esc_html($t['modal_edit_prod_title']); ?></h3>
                        <input type="hidden" id="edit-prod-id">
                        <input type="hidden" id="edit-prod-source-id">
                        <input type="hidden" id="edit-prod-action" value="edit">
                        <input type="hidden" id="edit-prod-target" value="invoice">
                        <div style="display:flex; flex-direction:column; gap:12px; margin-top:14px;">
                            <div>
                                <label data-i18n="lbl_add_name"><?php echo esc_html($t['lbl_add_name']); ?></label>
                                <input type="text" id="edit-prod-name">
                            </div>
                            <div>
                                <label data-i18n="lbl_add_mat"><?php echo esc_html($t['lbl_add_mat']); ?></label>
                                <select id="edit-prod-mat"></select>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                <div>
                                    <label data-i18n="lbl_calc_len"><?php echo esc_html($t['lbl_calc_len']); ?></label>
                                    <input type="number" id="edit-prod-len">
                                </div>
                                <div>
                                    <label data-i18n="lbl_calc_width"><?php echo esc_html($t['lbl_calc_width']); ?></label>
                                    <input type="number" id="edit-prod-width">
                                </div>
                            </div>
                            <div>
                                <label data-i18n="lbl_add_price"><?php echo esc_html($t['lbl_add_price']); ?></label>
                                <input type="number" step="any" id="edit-prod-price">
                            </div>
                            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px;">
                                <button type="button" class="btn btn-outline" onclick="closeEditProductModal()" data-i18n="btn_cancel"><?php echo esc_html($t['btn_cancel']); ?></button>
                                <button type="button" class="btn btn-green" onclick="saveEditedProduct()" data-i18n="btn_save"><?php echo esc_html($t['btn_save']); ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- МОДАЛЬНЕ ВІКНО РЕДАГУВАННЯ МАТЕРІАЛУ -->
                <div id="modal-edit-material" class="modal-backdrop">
                    <div class="modal-content">
                        <h3 style="margin-top:0; font-size:18px; color:var(--tc-dark);" data-i18n="modal_edit_mat_title"><?php echo esc_html($t['modal_edit_mat_title']); ?></h3>
                        <input type="hidden" id="edit-mat-id">
                        <div style="display:flex; flex-direction:column; gap:12px; margin-top:14px;">
                            <div>
                                <label data-i18n="lbl_mat_name"><?php echo esc_html($t['lbl_mat_name']); ?></label>
                                <input type="text" id="edit-mat-name">
                            </div>
                            <div>
                                <label data-i18n="lbl_mat_rate"><?php echo esc_html($t['lbl_mat_rate']); ?></label>
                                <input type="number" step="0.00001" id="edit-mat-price">
                            </div>
                            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px;">
                                <button type="button" class="btn btn-outline" onclick="closeEditMaterialModal()" data-i18n="btn_cancel"><?php echo esc_html($t['btn_cancel']); ?></button>
                                <button type="button" class="btn btn-green" onclick="saveEditedMaterial()" data-i18n="btn_save"><?php echo esc_html($t['btn_save']); ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- МОДАЛЬНЕ ВІКНО ПІДТВЕРДЖЕННЯ ВИДАЛЕННЯ -->
                <div id="modal-confirm-delete" class="modal-backdrop">
                    <div class="modal-content" style="max-width:400px; text-align:center;">
                        <div style="font-size:40px; margin-bottom:10px;">⚠️</div>
                        <h3 style="margin:0 0 10px 0; font-size:18px; color:var(--tc-dark);" data-i18n="modal_del_title"><?php echo esc_html($t['modal_del_title']); ?></h3>
                        <p style="font-size:13px; color:#64748b; margin-bottom:20px;" data-i18n="modal_del_text">
                            <?php echo esc_html($t['modal_del_text']); ?>
                        </p>
                        <div style="display:flex; justify-content:center; gap:12px;">
                            <button type="button" class="btn btn-outline" onclick="closeConfirmModal()" data-i18n="btn_cancel"><?php echo esc_html($t['btn_cancel']); ?></button>
                            <button type="button" class="btn btn-danger" id="confirm-del-btn" data-i18n="btn_delete"><?php echo esc_html($t['btn_delete']); ?></button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
                <!-- МОДАЛЬНЕ ВІКНО: ДОДАТИ З КАТАЛОГУ В НАКЛАДНУ -->
                
                <!-- МОДАЛЬНЕ ВІКНО: СТВОРЕННЯ ВИРОБУ В КАТАЛОЗІ -->
                <div id="modal-add-catalog-item" class="modal-backdrop" style="display:none;" onclick="if(event.target===this)closeAddCatalogItemModal();">
                    <div class="modal-content" style="max-width:520px;">
                        <h3 style="margin-top:0; font-size:18px; color:var(--tc-dark);" data-i18n="modal_add_catalog_title"><?php echo esc_html($t['modal_add_catalog_title']); ?></h3>
                        <div style="display:flex; flex-direction:column; gap:12px; margin-top:14px;">
                            <div>
                                <label data-i18n="lbl_add_name"><?php echo esc_html($t['lbl_add_name']); ?></label>
                                <input type="text" id="cat-add-name" placeholder="Наприклад: Стільниця дубова">
                            </div>
                            <div>
                                <label data-i18n="lbl_add_mat"><?php echo esc_html($t['lbl_add_mat']); ?></label>
                                <select id="cat-add-mat" onchange="recalcCatalogModalPrice()"></select>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                <div>
                                    <label data-i18n="lbl_calc_len"><?php echo esc_html($t['lbl_calc_len']); ?></label>
                                    <input type="number" step="any" id="cat-add-len" value="1000" oninput="recalcCatalogModalPrice()">
                                </div>
                                <div>
                                    <label data-i18n="lbl_calc_width"><?php echo esc_html($t['lbl_calc_width']); ?></label>
                                    <input type="number" step="any" id="cat-add-width" value="500" oninput="recalcCatalogModalPrice()">
                                </div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                                <div>
                                    <label data-i18n="col_qty"><?php echo esc_html($t['col_qty']); ?></label>
                                    <input type="number" min="1" id="cat-add-qty" value="1">
                                </div>
                                <div>
                                    <label data-i18n="lbl_add_price"><?php echo esc_html($t['lbl_add_price']); ?></label>
                                    <input type="number" step="any" id="cat-add-price">
                                </div>
                            </div>
                            <div style="margin-top:4px;">
                                <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:13px; font-weight:500;">
                                    <input type="checkbox" id="cat-add-to-inv">
                                    <span data-i18n="chk_modal_add_to_inv"><?php echo esc_html($t['chk_modal_add_to_inv']); ?></span>
                                </label>
                            </div>
                            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:14px;">
                                <button type="button" class="btn btn-outline" onclick="closeAddCatalogItemModal()" data-i18n="btn_close"><?php echo esc_html($t['btn_close']); ?></button>
                                <button type="button" class="btn btn-green" onclick="saveCatalogItemFromModal()" data-i18n="btn_create_in_catalog"><?php echo esc_html($t['btn_create_in_catalog']); ?></button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="modal-catalog-picker" class="modal-backdrop" style="display:none;" onclick="if(event.target===this)closeAddFromCatalogModal();">
                    <div class="modal-content" style="max-width:650px;">
                        <h3 data-i18n="modal_catalog_title"><?php echo esc_html($t['modal_catalog_title']); ?></h3>
                        <div id="catalog-picker-list" style="max-height:360px; overflow-y:auto; margin:16px 0;">
                            <!-- Список для вибору -->
                        </div>
                        <div class="modal-actions">
                            <button type="button" class="btn btn-outline" onclick="closeAddFromCatalogModal()" data-i18n="btn_close"><?php echo esc_html($t['btn_close']); ?></button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?php
    }
}
