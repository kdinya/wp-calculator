<?php
/**
 * Plugin Name: WP Calculator
 * Plugin URI: https://github.com/kdinya/wp-calculator
 * Description: Професійний калькулятор вартості виробів з дерева з довідником порід, збереженням каталогу, формуванням накладних для клієнтів, адаптивним інтерфейсом та автооновленням з GitHub.
 * Version: 1.0.0
 * Author: kdinya
 * Author URI: https://tomchik.com.ua/
 * Text Domain: wp-calculator
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WP_CALCULATOR_VERSION', '1.0.0');
define('WP_CALCULATOR_FILE', __FILE__);
define('WP_CALCULATOR_DIR', plugin_dir_path(__FILE__));

/**
 * Клас автоматичної та ручної перевірки оновлень з GitHub Releases
 */
class WpCalculatorGitHubUpdater {
    private string $repo_owner;
    private string $repo_name;
    private string $plugin_file;
    private string $plugin_slug;
    private string $version;

    public function __construct(string $plugin_file, string $repo_owner = 'kdinya', string $repo_name = 'wp-calculator', string $version = WP_CALCULATOR_VERSION) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->repo_owner  = $repo_owner;
        $this->repo_name   = $repo_name;
        $this->version     = $version;
    }

    public function register(): void {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_api_info'), 20, 3);
        add_filter('upgrader_source_selection', array($this, 'fix_source_folder'), 10, 4);

        add_action('wp_ajax_wood_calc_check_update', array($this, 'ajax_check_update'));
        add_action('wp_ajax_wood_calc_run_update', array($this, 'ajax_run_update'));
    }

    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $release = $this->get_latest_release();
        if (!$release) {
            return $transient;
        }

        $latest_version = ltrim($release['tag_name'] ?? '', 'v');
        if (version_compare($latest_version, $this->version, '>')) {
            $package = $this->get_download_package($release);
            if (!empty($package)) {
                $obj = new \stdClass();
                $obj->slug        = dirname($this->plugin_slug);
                $obj->new_version = $latest_version;
                $obj->url         = "https://github.com/{$this->repo_owner}/{$this->repo_name}";
                $obj->package     = $package;
                $obj->plugin      = $this->plugin_slug;

                $transient->response[$this->plugin_slug] = $obj;
            }
        }

        return $transient;
    }

    public function plugin_api_info($res, $action, $args) {
        if ('plugin_information' !== $action) {
            return $res;
        }

        $slug = dirname($this->plugin_slug);
        if (!isset($args->slug) || ($args->slug !== $slug && $args->slug !== $this->plugin_slug)) {
            return $res;
        }

        $release = $this->get_latest_release();
        if (!$release) {
            return $res;
        }

        $latest_version = ltrim($release['tag_name'] ?? '', 'v');

        $res = new \stdClass();
        $res->name          = 'WP Calculator';
        $res->slug          = $slug;
        $res->version       = $latest_version;
        $res->author        = '<a href="https://github.com/' . esc_url($this->repo_owner) . '">' . esc_html($this->repo_owner) . '</a>';
        $res->homepage      = "https://github.com/{$this->repo_owner}/{$this->repo_name}";
        $res->download_link = $this->get_download_package($release);
        $res->tested        = '6.7';
        $res->requires      = '6.0';
        $res->requires_php  = '7.4';
        $res->last_updated  = $release['published_at'] ?? current_time('mysql');

        $res->sections = array(
            'description' => 'Професійний калькулятор вартості виробів з дерева з довідником порід, формуванням накладних та автооновленням з GitHub.',
            'changelog'   => !empty($release['body']) ? nl2br(esc_html($release['body'])) : 'Оновлення ' . esc_html($latest_version),
        );

        return $res;
    }

    public function fix_source_folder($source, $remote_source, $upgrader, $hook_extra = array()) {
        global $wp_filesystem;

        if (is_wp_error($source) || !($upgrader instanceof \Plugin_Upgrader) || !is_object($wp_filesystem)) {
            return $source;
        }

        $updated_plugin = (is_array($hook_extra) && !empty($hook_extra['plugin'])) ? $hook_extra['plugin'] : '';

        if ('' !== $updated_plugin && $updated_plugin !== $this->plugin_slug) {
            return $source;
        }

        $proper_folder = ('' !== $updated_plugin) ? dirname($this->plugin_slug) : basename($this->plugin_file, '.php');
        if ('.' === $proper_folder || '' === $proper_folder) {
            $proper_folder = 'wp-calculator';
        }

        $source_dir = untrailingslashit($source);
        if (basename($source_dir) === $proper_folder || $source_dir === untrailingslashit($remote_source)) {
            return $source;
        }

        $main_file = trailingslashit($source_dir) . basename($this->plugin_file);
        if (!$wp_filesystem->exists($main_file)) {
            return $source;
        }

        $new_source = trailingslashit($remote_source) . $proper_folder;
        if (!$wp_filesystem->move($source_dir, $new_source, true)) {
            return new \WP_Error('calc_rename_failed', sprintf('Не вдалося перейменувати папку плагіна в «%s».', $proper_folder));
        }

        return trailingslashit($new_source);
    }

    private function get_latest_release(bool $bypass_cache = false): ?array {
        $cache_key = 'wood_calc_github_latest_release';

        if (!$bypass_cache) {
            $cached = get_transient($cache_key);
            if (false !== $cached && is_array($cached)) {
                return $cached;
            }
        }

        $url = "https://api.github.com/repos/{$this->repo_owner}/{$this->repo_name}/releases/latest";
        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 10,
                'headers' => array(
                    'Accept'     => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/WP-Calculator-Updater',
                ),
            )
        );

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($body) || empty($body['tag_name'])) {
            return null;
        }

        set_transient($cache_key, $body, 6 * HOUR_IN_SECONDS);
        return $body;
    }

    private function get_download_package(array $release): string {
        if (empty($release['assets']) || !is_array($release['assets'])) {
            return '';
        }

        foreach ($release['assets'] as $asset) {
            if (!empty($asset['name']) && 'wp-calculator.zip' === $asset['name'] && !empty($asset['browser_download_url'])) {
                return (string) $asset['browser_download_url'];
            }
        }

        return '';
    }

    public function ajax_check_update(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Недостатньо прав.'), 403);
        }

        delete_transient('wood_calc_github_latest_release');
        delete_site_transient('update_plugins');

        $release = $this->get_latest_release(true);

        if (!$release || empty($release['tag_name'])) {
            wp_send_json_error(array('message' => 'Не вдалося зв’язатися з GitHub. Спробуйте пізніше.'));
        }

        $latest_version = ltrim((string) $release['tag_name'], 'v');
        $update_available = version_compare($latest_version, $this->version, '>');
        $package_url = $this->get_download_package($release);

        wp_send_json_success(array(
            'current_version'  => $this->version,
            'latest_version'   => $latest_version,
            'update_available' => $update_available,
            'can_reinstall'    => !empty($package_url),
            'has_package'      => !empty($package_url),
            'changelog'        => (string) ($release['body'] ?? ''),
            'html_url'         => (string) ($release['html_url'] ?? ''),
        ));
    }

    public function ajax_run_update(): void {
        if (!current_user_can('update_plugins')) {
            wp_send_json_error(array('message' => 'Недостатньо прав для оновлення плагінів.'), 403);
        }

        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        $release = $this->get_latest_release(true);
        if (!$release) {
            wp_send_json_error(array('message' => 'Не вдалося отримати реліз з GitHub.'));
        }

        $package = $this->get_download_package($release);
        if (empty($package)) {
            wp_send_json_error(array('message' => 'Архів wp-calculator.zip не знайдено в релізі.'));
        }

        $skin = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader($skin);
        $result = $upgrader->upgrade($this->plugin_slug, array('package' => $package));

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        if (is_wp_error($skin->result)) {
            wp_send_json_error(array('message' => $skin->result->get_error_message()));
        }

        if (false === $result) {
            wp_send_json_error(array('message' => 'Оновлення завершилося помилкою.'));
        }

        activate_plugin($this->plugin_slug);
        wp_send_json_success(array(
            'message' => 'Плагін успішно оновлено!',
            'version' => ltrim($release['tag_name'], 'v')
        ));
    }
}

// Ініціалізація Updater
$wood_calc_updater = new WpCalculatorGitHubUpdater(WP_CALCULATOR_FILE);
$wood_calc_updater->register();

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

// 2. AJAX: Отримання збережених даних
add_action('wp_ajax_wood_calc_get', 'wood_calc_get_data');
function wood_calc_get_data() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Доступ заборонено');
    }

    $keys = array('wood_calc_store_v3', 'wood_calc_store_v4', 'wood_calc_store_v2', 'wood_calc_store');
    $merged_materials = array();
    $merged_items = array();
    $saved_settings = array('lang' => 'uk');

    foreach ($keys as $k) {
        $val = get_option($k, null);
        if (!empty($val) && is_array($val)) {
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

    $data = array(
        'materials' => array_values($merged_materials),
        'items'     => array_values($merged_items),
        'settings'  => $saved_settings
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

// 4. Шорткод [wood_calculator]
add_shortcode('wood_calculator', 'wood_calc_render_shortcode');
function wood_calc_render_shortcode() {
    if (!current_user_can('manage_options')) {
        return '<div style="padding:20px; background:#fff; border-left:4px solid #dc2626; border-radius:4px; font-family:sans-serif; color:#333;">🔒 Доступ до калькулятора дозволено лише адміністраторам сайту.</div>';
    }

    $ajax_url = admin_url('admin-ajax.php');

    ob_start();
    ?>
    <div id="wood-calculator-app" class="tc-app-wrapper">
        <style>
            :root {
                --tc-dark: #24272a;
                --tc-green: #95b504;
                --tc-green-dark: #7e9c02;
                --tc-green-light: #f4f8e7;
                --tc-danger: #dc2626;
                --tc-gray-bg: #f8fafc;
                --tc-border: #e2e8f0;
                --tc-text: #1e293b;
            }

            #wood-calculator-app {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                color: var(--tc-text);
                background-color: var(--tc-gray-bg);
                padding: 24px;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
                box-sizing: border-box;
                line-height: 1.5;
            }

            #wood-calculator-app * {
                box-sizing: border-box;
            }

            #wood-calculator-app input[type="checkbox"] {
                accent-color: var(--tc-green) !important;
                width: 18px !important;
                height: 18px !important;
                cursor: pointer !important;
                vertical-align: middle;
            }

            #wood-calculator-app input[type="radio"] {
                accent-color: var(--tc-green) !important;
                width: 18px !important;
                height: 18px !important;
                cursor: pointer !important;
            }

            .tc-tab-bar {
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
                border-bottom: 2px solid var(--tc-border);
                padding-bottom: 10px;
            }
            .tc-tab-btn {
                background: #ffffff;
                border: 1px solid var(--tc-border);
                padding: 10px 20px;
                font-size: 14px;
                font-weight: 700;
                color: #64748b;
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.2s ease;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            .tc-tab-btn:hover {
                background: #f1f5f9;
                color: var(--tc-dark);
            }
            .tc-tab-btn.active {
                background: var(--tc-dark);
                color: #ffffff;
                border-color: var(--tc-dark);
            }

            .tc-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                padding-bottom: 15px;
                border-bottom: 2px solid var(--tc-border);
            }
            .tc-header h1 {
                margin: 0;
                font-size: 24px;
                color: var(--tc-dark);
                font-weight: 800;
            }
            .tc-header-actions {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .tc-layout {
                display: grid;
                grid-template-columns: 1fr 360px;
                gap: 24px;
                align-items: start;
            }
            .tc-main-col {
                display: flex;
                flex-direction: column;
                gap: 24px;
            }
            .tc-side-col {
                display: flex;
                flex-direction: column;
                gap: 24px;
            }

            .tc-card {
                background: #ffffff;
                border-radius: 10px;
                padding: 20px;
                border: 1px solid var(--tc-border);
                box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            }
            .tc-card h2 {
                margin-top: 0;
                margin-bottom: 16px;
                font-size: 17px;
                color: var(--tc-dark);
                font-weight: 700;
                border-bottom: 1px solid #f1f5f9;
                padding-bottom: 8px;
            }

            label {
                display: block;
                font-size: 12px;
                font-weight: 600;
                color: #64748b;
                margin-bottom: 4px;
            }
            input[type="text"], input[type="number"], select {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #cbd5e1;
                border-radius: 6px;
                font-size: 14px;
                color: var(--tc-dark);
                background: #fff;
                outline: none;
                transition: border-color 0.2s;
            }
            input:focus, select:focus {
                border-color: var(--tc-green);
                box-shadow: 0 0 0 2px rgba(149, 181, 4, 0.15);
            }

            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 13px;
                font-weight: 600;
                cursor: pointer;
                border: none;
                transition: all 0.2s;
                text-decoration: none;
                gap: 6px;
            }
            .btn-green {
                background: var(--tc-green);
                color: #ffffff;
            }
            .btn-green:hover {
                background: var(--tc-green-dark);
            }
            .btn-dark {
                background: var(--tc-dark);
                color: #ffffff;
            }
            .btn-dark:hover {
                background: #383e44;
            }
            .btn-danger {
                background: var(--tc-danger);
                color: #ffffff;
            }
            .btn-danger:hover {
                background: #b91c1c;
            }
            .btn-outline {
                background: transparent;
                border: 1px solid #cbd5e1;
                color: #475569;
            }
            .btn-outline:hover {
                background: #f1f5f9;
                color: var(--tc-dark);
            }
            .btn-sm {
                padding: 4px 8px;
                font-size: 12px;
            }

            .grid-calc {
                display: grid;
                grid-template-columns: 2fr 1fr 1fr 1.2fr;
                gap: 12px;
                align-items: end;
            }
            .calc-result-box {
                margin-top: 14px;
                background: var(--tc-green-light);
                border: 1px dashed var(--tc-green);
                border-radius: 8px;
                padding: 12px 16px;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            .tc-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 12px;
                font-size: 13px;
            }
            .tc-table th {
                background: #f8fafc;
                text-align: left;
                padding: 10px;
                font-weight: 600;
                color: #64748b;
                border-bottom: 2px solid var(--tc-border);
            }
            .tc-table td {
                padding: 10px;
                border-bottom: 1px solid var(--tc-border);
                vertical-align: middle;
            }
            .tc-table tr:hover {
                background: #fcfdfd;
            }

            .wc-drag-handle {
                cursor: grab;
                user-select: none;
                color: #94a3b8;
                font-size: 16px;
                padding: 0 4px;
                display: inline-block;
            }
            .wc-drag-handle:active {
                cursor: grabbing;
            }
            tr.dragging {
                opacity: 0.45;
                background: #f1f5f9 !important;
            }
            tr.drop-above td {
                border-top: 2px solid var(--tc-green) !important;
            }
            tr.drop-below td {
                border-bottom: 2px solid var(--tc-green) !important;
            }

            .summary-bar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #ffffff;
                padding: 16px 20px;
                border-radius: 10px;
                border: 2px solid var(--tc-green);
                margin-top: 16px;
            }
            .summary-total {
                font-size: 20px;
                font-weight: 800;
                color: var(--tc-dark);
            }

            .invoice-mode #wood-calculator-app {
                background: #ffffff !important;
                padding: 20px !important;
                box-shadow: none !important;
            }
            .invoice-mode .no-invoice {
                display: none !important;
            }
            .invoice-mode .tc-layout {
                grid-template-columns: 1fr !important;
            }
            .invoice-mode .tc-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            .invoice-mode .tc-table th, 
            .invoice-mode .tc-table td {
                padding: 8px 10px !important;
            }
            .invoice-header-box {
                display: none;
                border-bottom: 2px solid var(--tc-dark);
                padding-bottom: 10px;
                margin-bottom: 15px;
            }
            .invoice-mode .invoice-header-box {
                display: flex;
                justify-content: space-between;
                align-items: flex-end;
            }

            .modal-backdrop {
                display: none;
                position: fixed;
                top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(15, 23, 42, 0.6);
                z-index: 999999;
                align-items: center;
                justify-content: center;
            }
            .modal-content {
                background: #ffffff;
                border-radius: 12px;
                width: 90%;
                max-width: 480px;
                padding: 24px;
                box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            }

            @media (max-width: 900px) {
                .tc-layout {
                    grid-template-columns: 1fr;
                }
                .tc-side-col {
                    order: -1;
                }
                .grid-calc {
                    grid-template-columns: 1fr 1fr;
                }
                .grid-calc .full-mobile {
                    grid-column: span 2;
                }
            }
        </style>

        <div class="tc-tab-bar no-invoice">
            <button type="button" class="tc-tab-btn active" id="tab-nav-calc" onclick="switchWcTab('calc')">
                <span>🧮</span> <span data-i18n="tab_calc">Калькулятор</span>
            </button>
            <button type="button" class="tc-tab-btn" id="tab-nav-appearance" onclick="switchWcTab('appearance')">
                <span>🎨</span> <span data-i18n="tab_appearance">Оформлення</span>
            </button>
            <button type="button" class="tc-tab-btn" id="tab-nav-settings" onclick="switchWcTab('settings')">
                <span>⚙️</span> <span data-i18n="tab_settings">Налаштування</span>
            </button>
        </div>

        <!-- ВКЛАДКА 1: КАЛЬКУЛЯТОР -->
        <div id="wc-tab-pane-calc" class="wc-tab-pane">
            <div class="tc-header no-invoice">
                <h1 data-i18n="header_title">🛠️ Розрахунок вартості виробів</h1>
                <div class="tc-header-actions">
                    <span id="wc-status" style="font-size:12px; color:#64748b;" data-i18n="syncing">● Синхронізація...</span>
                    <button type="button" class="btn btn-dark" style="height:32px; font-size:12px; padding:4px 10px;" onclick="downloadDataBackup()" data-i18n="btn_backup">💾 Бекап</button>
                </div>
            </div>

            <div class="tc-layout">
                <div class="tc-main-col">
                    
                    <!-- 1. ШВИДКИЙ КАЛЬКУЛЯТОР -->
                    <div class="tc-card no-invoice">
                        <h2 data-i18n="sec1_title">1. Швидкий калькулятор вартості</h2>
                        <div class="grid-calc">
                            <div class="full-mobile">
                                <label data-i18n="lbl_calc_mat">Матеріал (тариф за 1 см²)</label>
                                <select id="calc-mat" onchange="runQuickCalc()"></select>
                            </div>
                            <div>
                                <label data-i18n="lbl_calc_len">Довжина (мм)</label>
                                <input type="number" id="calc-len" placeholder="500" oninput="runQuickCalc()">
                            </div>
                            <div>
                                <label data-i18n="lbl_calc_width">Ширина (мм)</label>
                                <input type="number" id="calc-width" placeholder="300" oninput="runQuickCalc()">
                            </div>
                            <div class="full-mobile">
                                <button type="button" class="btn btn-green" style="width:100%;" onclick="sendToSaveForm()" data-i18n="btn_send_to_form">Внести у виріб ↓</button>
                            </div>
                        </div>
                        <div class="calc-result-box">
                            <div>
                                <span style="font-size:12px; color:#64748b;" data-i18n="res_area">Розрахована площа:</span>
                                <strong id="res-area" style="font-size:15px; margin-left:4px;">0 см²</strong>
                            </div>
                            <div>
                                <span style="font-size:12px; color:#64748b;" data-i18n="res_price">Ціна за 1 шт:</span>
                                <strong id="res-price" style="font-size:18px; color:var(--tc-dark); margin-left:6px;">0.00 грн</strong>
                            </div>
                        </div>
                    </div>

                    <!-- 2. ДОДАТИ ВИРІБ У СПИСОК -->
                    <div class="tc-card no-invoice">
                        <h2 data-i18n="sec2_title">2. Додати виріб у список</h2>
                        <div style="display:grid; grid-template-columns: 2fr 1.5fr 1fr 1fr 1.2fr auto; gap:10px; align-items:end;">
                            <div>
                                <label data-i18n="lbl_add_name">Назва виробу</label>
                                <input type="text" id="add-name" placeholder="напр. Дошка дубова">
                            </div>
                            <div>
                                <label data-i18n="lbl_add_mat">Оберіть матеріал</label>
                                <select id="add-mat"></select>
                            </div>
                            <div>
                                <label data-i18n="lbl_calc_len">Довжина (мм)</label>
                                <input type="number" id="add-len" placeholder="мм">
                            </div>
                            <div>
                                <label data-i18n="lbl_calc_width">Ширина (мм)</label>
                                <input type="number" id="add-width" placeholder="мм">
                            </div>
                            <div>
                                <label data-i18n="lbl_add_price">Ціна/шт (грн)</label>
                                <input type="number" step="0.01" id="add-price" placeholder="0.00">
                            </div>
                            <div>
                                <button type="button" class="btn btn-dark" onclick="addCustomProduct()" data-i18n="btn_add_save">+ Зберегти</button>
                            </div>
                        </div>
                    </div>

                    <!-- 3. СПИСОК ВИРОБІВ / БЛОК НАКЛАДНОЇ -->
                    <div id="invoice-print-card" class="tc-card" style="margin-bottom:0;">
                        <div class="invoice-header-box">
                            <div>
                                <h2 style="margin:0; font-size:22px; color:var(--tc-dark); letter-spacing:0.5px;" data-i18n="invoice_title">РОЗРАХУНОК ЗАМОВЛЕННЯ</h2>
                                <div style="font-size:13px; color:#64748b; margin-top:4px;" id="inv-date"></div>
                            </div>
                        </div>

                        <h2 class="no-invoice" style="display:flex; justify-content:space-between; align-items:center;">
                            <span data-i18n="sec3_title">3. Список виробів</span>
                            <div style="font-size:13px; font-weight:normal; display:flex; gap:12px; align-items:center;">
                                <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer; margin:0;">
                                    <input type="checkbox" id="filter-selected" onchange="renderItems()">
                                    <span data-i18n="chk_hide_unselected">Сховати невиділені</span>
                                </label>
                                <button type="button" class="btn btn-outline btn-sm" onclick="toggleSelectAll(true)" data-i18n="btn_select_all">Виділити всі</button>
                                <button type="button" class="btn btn-outline btn-sm" onclick="toggleSelectAll(false)" data-i18n="btn_deselect_all">Зняти всі</button>
                            </div>
                        </h2>

                        <!-- Вибір колонок для накладної -->
                        <div class="no-invoice" style="background:#f8fafc; padding:10px 14px; border-radius:6px; margin-bottom:12px; border:1px solid #e2e8f0; display:flex; gap:16px; align-items:center; flex-wrap:wrap;">
                            <strong style="font-size:12px; color:#475569;" data-i18n="inv_cols_title">Колонки для накладної:</strong>
                            <label style="display:inline-flex; align-items:center; gap:5px; margin:0; cursor:pointer; font-size:12px;">
                                <input type="checkbox" id="col-toggle-photo" checked onchange="toggleColumnVisibility('photo', this.checked)"> <span data-i18n="col_photo">Фото</span>
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:5px; margin:0; cursor:pointer; font-size:12px;">
                                <input type="checkbox" id="col-toggle-mat" checked onchange="toggleColumnVisibility('mat', this.checked)"> <span data-i18n="col_material">Матеріал</span>
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:5px; margin:0; cursor:pointer; font-size:12px;">
                                <input type="checkbox" id="col-toggle-dims" checked onchange="toggleColumnVisibility('dims', this.checked)"> <span data-i18n="col_dims">Розміри</span>
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:5px; margin:0; cursor:pointer; font-size:12px;">
                                <input type="checkbox" id="col-toggle-price" checked onchange="toggleColumnVisibility('price', this.checked)"> <span data-i18n="col_price_pc">Ціна / 1 шт</span>
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:5px; margin:0; cursor:pointer; font-size:12px;">
                                <input type="checkbox" id="col-toggle-qty" checked onchange="toggleColumnVisibility('qty', this.checked)"> <span data-i18n="col_qty">К-сть</span>
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:5px; margin:0; cursor:pointer; font-size:12px;">
                                <input type="checkbox" id="col-toggle-sum" checked onchange="toggleColumnVisibility('sum', this.checked)"> <span data-i18n="col_sum">Сума</span>
                            </label>
                        </div>

                        <!-- Таблиця виробів -->
                        <div style="overflow-x:auto;">
                            <table class="tc-table" id="items-table">
                                <thead>
                                    <tr>
                                        <th style="width:40px;" class="no-invoice"></th>
                                        <th style="width:30px;"><input type="checkbox" id="select-all-top" onchange="toggleSelectAll(this.checked)"></th>
                                        <th class="col-photo-header" style="width:50px;" data-i18n="col_photo">Фото</th>
                                        <th data-i18n="lbl_add_name">Назва виробу</th>
                                        <th class="col-mat-header" data-i18n="col_material">Матеріал</th>
                                        <th class="col-dims-header" data-i18n="col_dims">Розміри</th>
                                        <th class="col-price-header" data-i18n="col_price_pc">Ціна / 1 шт</th>
                                        <th class="col-qty-header" style="width:90px;" data-i18n="col_qty">К-сть</th>
                                        <th class="col-sum-header" style="text-align:right;" data-i18n="col_sum">Сума</th>
                                        <th style="width:130px; text-align:right;" class="no-invoice" data-i18n="col_actions">Дії</th>
                                    </tr>
                                </thead>
                                <tbody id="items-tbody"></tbody>
                            </table>
                        </div>

                        <div class="summary-bar">
                            <div style="font-size:13px; color:#475569;">
                                <span data-i18n="summary_selected">Обрано виробів:</span> <strong id="sum-items-count">0</strong> | 
                                <span data-i18n="summary_qty">Загальна к-сть:</span> <strong id="sum-total-qty">0</strong> <span data-i18n="pcs">шт</span>
                            </div>
                            <div class="summary-total">
                                <span data-i18n="summary_sum">Загальна сума:</span> <span id="sum-grand-total">0.00</span> <span data-i18n="curr">грн</span>
                            </div>
                        </div>

                        <div class="no-invoice" style="margin-top:16px; text-align:right;">
                            <button type="button" class="btn btn-green" onclick="enterInvoiceMode()" data-i18n="btn_invoice_mode">📸 Накладна для скріна</button>
                        </div>
                    </div>

                    <div id="exit-invoice-container" style="display:none; margin-top:20px; text-align:center;">
                        <button type="button" class="btn btn-dark" onclick="exitInvoiceMode()" style="padding:10px 24px; font-size:14px;" data-i18n="btn_exit_invoice">← Вийти з режиму накладної</button>
                    </div>

                </div>

                <!-- ПРАВИЙ СТОВПЧИК: Довідник матеріалів -->
                <div class="tc-side-col no-invoice">
                    <div class="tc-card">
                        <h2 data-i18n="sec_materials_title">🪵 Довідник порід</h2>
                        <div style="display:flex; flex-direction:column; gap:10px;">
                            <div>
                                <label data-i18n="lbl_mat_name">Назва породи</label>
                                <input type="text" id="mat-name" placeholder="напр. Дуб селект">
                            </div>
                            <div>
                                <label data-i18n="lbl_mat_rate">Тариф за 1 см² (грн)</label>
                                <input type="number" step="0.00001" id="mat-price" placeholder="0.20123">
                            </div>
                            <button type="button" class="btn btn-dark" onclick="addNewMaterial()" data-i18n="btn_mat_add">+ Додати</button>
                        </div>

                        <table class="tc-table" style="margin-top:16px;">
                            <thead>
                                <tr>
                                    <th data-i18n="col_material">Порода</th>
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

        <!-- ВКЛАДКА: ОФОРМЛЕННЯ -->
        <div id="wc-tab-pane-appearance" class="wc-tab-pane" style="display:none;">
            <div class="tc-card">
                <h2 data-i18n="appearance_title">🎨 Зовнішній вигляд</h2>
                <div style="max-width:650px;">
                    <label style="font-weight:700; font-size:14px; margin-bottom:8px; display:block;" data-i18n="lbl_accent_color">Акцентний колір кнопок та активних елементів</label>
                    <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:10px;" id="wc-color-presets"></div>
                    <div style="display:flex; gap:12px; align-items:center; margin-top:16px;">
                        <label style="display:inline-flex; align-items:center; gap:8px; font-size:13px; font-weight:600; margin:0;">
                            <span data-i18n="lbl_custom_color">Довільний колір:</span>
                            <input type="color" id="wc-custom-color" style="width:48px; height:36px; padding:2px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer;" onchange="setAccentColor(this.value)">
                        </label>
                    </div>
                    <p style="font-size:13px; color:#64748b; margin-top:14px; line-height:1.6;" data-i18n="appearance_desc">
                        Обраний колір застосовується до кнопок, чекбоксів, підсвітки та акцентних рамок інтерфейсу. Вибір зберігається автоматично.
                    </p>
                </div>
            </div>
        </div>

        <!-- ВКЛАДКА 2: НАЛАШТУВАННЯ ТА ОНОВЛЕННЯ -->
        <div id="wc-tab-pane-settings" class="wc-tab-pane" style="display:none;">
            <div style="display:flex; flex-direction:column; gap:20px;">
                <!-- Блок мови -->
                <div class="tc-card">
                    <h2 data-i18n="settings_title">⚙️ Налаштування калькулятора</h2>
                    <div style="max-width:550px; padding:10px 0;">
                        <label style="font-weight:700; font-size:14px; margin-bottom:8px; display:block;" data-i18n="settings_lang">Мова інтерфейсу</label>
                        <div style="display:flex; gap:16px; align-items:center; flex-wrap:wrap; margin-top:10px;">
                            <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; background:#fff; border:1px solid #cbd5e1; padding:10px 16px; border-radius:8px; font-weight:600;">
                                <input type="radio" name="wc_lang_choice" value="uk" id="wc-lang-uk" onchange="setWcLanguage('uk')">
                                <span>🇺🇦 Українська</span>
                            </label>
                            <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; background:#fff; border:1px solid #cbd5e1; padding:10px 16px; border-radius:8px; font-weight:600;">
                                <input type="radio" name="wc_lang_choice" value="en" id="wc-lang-en" onchange="setWcLanguage('en')">
                                <span>🇬🇧 English</span>
                            </label>
                        </div>
                        <p style="font-size:13px; color:#64748b; margin-top:14px; line-height:1.6;" data-i18n="settings_lang_desc">
                            Обрана мова зберігається автоматично та використовується для калькулятора, каталогу і накладної.
                        </p>

                        <div style="margin-top:24px; padding-top:16px; border-top:1px solid #e2e8f0;">
                            <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:13px; margin:0;">
                                <input type="checkbox" id="wc-wipe-on-uninstall" onchange="toggleWipeOnUninstall(this.checked)">
                                <span data-i18n="lbl_wipe_uninstall">Видаляти всі дані та налаштування при повному видаленні плагіна</span>
                            </label>
                            <p style="font-size:12px; color:#94a3b8; margin:6px 0 0 26px;" data-i18n="desc_wipe_uninstall">
                                Якщо вимкнено — ваші створені матеріали, каталог виробів та налаштування збережуться навіть після деінсталяції плагіна.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Блок оновлень з GitHub -->
                <div class="tc-card">
                    <h2 data-i18n="updater_title">🚀 Оновлення плагіна з GitHub</h2>
                    <div style="max-width:650px;">
                        <div style="display:flex; align-items:center; gap:16px; margin-bottom:14px;">
                            <span style="font-size:14px; color:#475569;">
                                <strong data-i18n="lbl_current_ver">Поточна версія:</strong> <code>v<?php echo esc_html(WP_CALCULATOR_VERSION); ?></code>
                            </span>
                            <span style="font-size:14px; color:#475569;" id="wc-latest-ver-box"></span>
                        </div>

                        <div style="display:flex; gap:12px; align-items:center; flex-wrap:wrap;">
                            <button type="button" class="btn btn-dark" id="btn-check-update" onclick="checkGitHubUpdate()">
                                🔍 <span data-i18n="btn_check_update">Перевірити оновлення</span>
                            </button>
                            <button type="button" class="btn btn-green" id="btn-run-update" style="display:none;" onclick="runGitHubUpdate()">
                                ⚡ <span data-i18n="btn_apply_update">Оновити плагін зараз</span>
                            </button>
                            <span id="update-status-msg" style="font-size:13px; color:#64748b;"></span>
                        </div>

                        <div id="update-changelog-box" style="display:none; margin-top:16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px;">
                            <strong style="font-size:13px; color:var(--tc-dark);" data-i18n="lbl_changelog">Зміни в релізі:</strong>
                            <div id="update-changelog-text" style="font-size:13px; color:#475569; margin-top:6px; line-height:1.5;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- МОДАЛЬНЕ ВІКНО РЕДАГУВАННЯ ВИРОБУ -->
        <div id="modal-edit-product" class="modal-backdrop">
            <div class="modal-content">
                <h3 style="margin-top:0; font-size:18px; color:var(--tc-dark);" data-i18n="modal_edit_prod_title">Редагувати виріб</h3>
                <input type="hidden" id="edit-prod-id">
                <div style="display:flex; flex-direction:column; gap:12px; margin-top:14px;">
                    <div>
                        <label data-i18n="lbl_add_name">Назва виробу</label>
                        <input type="text" id="edit-prod-name">
                    </div>
                    <div>
                        <label data-i18n="lbl_add_mat">Матеріал</label>
                        <select id="edit-prod-mat"></select>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <label data-i18n="lbl_calc_len">Довжина (мм)</label>
                            <input type="number" id="edit-prod-len">
                        </div>
                        <div>
                            <label data-i18n="lbl_calc_width">Ширина (мм)</label>
                            <input type="number" id="edit-prod-width">
                        </div>
                    </div>
                    <div>
                        <label data-i18n="lbl_add_price">Ціна за 1 шт (грн)</label>
                        <input type="number" step="0.01" id="edit-prod-price">
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px;">
                        <button type="button" class="btn btn-outline" onclick="closeEditProductModal()" data-i18n="btn_cancel">Скасувати</button>
                        <button type="button" class="btn btn-green" onclick="saveEditedProduct()" data-i18n="btn_save">Зберегти зміни</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- МОДАЛЬНЕ ВІКНО РЕДАГУВАННЯ МАТЕРІАЛУ -->
        <div id="modal-edit-material" class="modal-backdrop">
            <div class="modal-content">
                <h3 style="margin-top:0; font-size:18px; color:var(--tc-dark);" data-i18n="modal_edit_mat_title">Редагувати матеріал</h3>
                <input type="hidden" id="edit-mat-id">
                <div style="display:flex; flex-direction:column; gap:12px; margin-top:14px;">
                    <div>
                        <label data-i18n="lbl_mat_name">Назва породи</label>
                        <input type="text" id="edit-mat-name">
                    </div>
                    <div>
                        <label data-i18n="lbl_mat_rate">Тариф за 1 см² (грн)</label>
                        <input type="number" step="0.00001" id="edit-mat-price">
                    </div>
                    <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:10px;">
                        <button type="button" class="btn btn-outline" onclick="closeEditMaterialModal()" data-i18n="btn_cancel">Скасувати</button>
                        <button type="button" class="btn btn-green" onclick="saveEditedMaterial()" data-i18n="btn_save">Зберегти зміни</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- МОДАЛЬНЕ ВІКНО ПІДТВЕРДЖЕННЯ ВИДАЛЕННЯ -->
        <div id="modal-confirm-delete" class="modal-backdrop">
            <div class="modal-content" style="max-width:400px; text-align:center;">
                <div style="font-size:40px; margin-bottom:10px;">⚠️</div>
                <h3 style="margin:0 0 10px 0; font-size:18px; color:var(--tc-dark);" data-i18n="modal_del_title">Підтвердження видалення</h3>
                <p style="font-size:13px; color:#64748b; margin-bottom:20px;" data-i18n="modal_del_text">
                    Ви дійсно бажаєте видалити цей елемент? Цю дію неможливо буде скасувати.
                </p>
                <div style="display:flex; justify-content:center; gap:12px;">
                    <button type="button" class="btn btn-outline" onclick="closeConfirmModal()" data-i18n="btn_cancel">Скасувати</button>
                    <button type="button" class="btn btn-danger" id="confirm-del-btn" data-i18n="btn_delete">Видалити</button>
                </div>
            </div>
        </div>

    </div>

    <script>
    (function() {
        const AJAX_URL = '<?php echo esc_url($ajax_url); ?>';
        const LOCAL_STORAGE_KEY = 'wood_calc_local_mirror_v2';
        const LANG_STORAGE_KEY = 'wood_calc_lang';

        const I18N = {
            uk: {
                tab_calc: "Калькулятор",
                tab_settings: "Налаштування",
                header_title: "🛠️ Розрахунок вартості виробів",
                btn_backup: "💾 Бекап",
                syncing: "● Синхронізація...",
                saved: "● Збережено в WordPress",
                offline: "● Локальний режим",
                sec1_title: "1. Швидкий калькулятор вартості",
                lbl_calc_mat: "Матеріал (тариф за 1 см²)",
                lbl_calc_len: "Довжина (мм)",
                lbl_calc_width: "Ширина (мм)",
                btn_send_to_form: "Внести у виріб ↓",
                res_area: "Розрахована площа:",
                res_price: "Ціна за 1 шт:",
                sec2_title: "2. Додати виріб у список",
                lbl_add_name: "Назва виробу",
                lbl_add_mat: "Оберіть матеріал",
                lbl_add_price: "Ціна/шт (грн)",
                btn_add_save: "+ Зберегти",
                sec3_title: "3. Список виробів",
                invoice_title: "РОЗРАХУНОК ЗАМОВЛЕННЯ",
                chk_hide_unselected: "Сховати невиділені",
                btn_select_all: "Виділити всі",
                btn_deselect_all: "Зняти всі",
                inv_cols_title: "Колонки для накладної:",
                col_photo: "Фото",
                col_material: "Матеріал",
                col_dims: "Розміри",
                col_price_pc: "Ціна / 1 шт",
                col_qty: "К-сть",
                col_sum: "Сума",
                col_actions: "Дії",
                summary_selected: "Обрано виробів:",
                summary_qty: "Загальна к-сть:",
                summary_sum: "Загальна сума:",
                btn_invoice_mode: "📸 Накладна для скріна",
                btn_exit_invoice: "← Вийти з режиму накладної",
                sec_materials_title: "🪵 Довідник порід",
                lbl_mat_name: "Назва породи",
                lbl_mat_rate: "Тариф за 1 см² (грн)",
                btn_mat_add: "+ Додати",
                settings_title: "⚙️ Налаштування калькулятора",
                settings_lang: "Мова інтерфейсу",
                settings_lang_desc: "Обрана мова зберігається автоматично та використовується для калькулятора, каталогу і накладної.",
                updater_title: "🚀 Оновлення плагіна з GitHub",
                lbl_wipe_uninstall: "Видаляти всі дані та налаштування при повному видаленні плагіна",
                desc_wipe_uninstall: "Якщо вимкнено — ваші створені матеріали, каталог виробів та налаштування збережуться навіть після деінсталяції плагіна.",
                tab_appearance: "Оформлення",
                appearance_title: "🎨 Зовнішній вигляд",
                lbl_accent_color: "Акцентний колір кнопок та активних елементів",
                lbl_custom_color: "Довільний колір:",
                appearance_desc: "Обраний колір застосовується до кнопок, чекбоксів, підсвітки та акцентних рамок інтерфейсу. Вибір зберігається автоматично.",
                lbl_current_ver: "Поточна версія:",
                lbl_latest_ver: "Остання в GitHub:",
                btn_check_update: "Перевірити оновлення",
                btn_apply_update: "Оновити плагін зараз",
                lbl_changelog: "Зміни в релізі:",
                checking_update: "Перевіряємо релізи GitHub...",
                update_latest: "У вас встановлено найновішу версію!",
                update_found: "Знайдено нову версію: ",
                update_reinstall_ok: "Версія актуальна. Можна перевстановити за потреби.",
                updating_in_progress: "Завантаження та встановлення оновлення...",
                update_success: "Плагін успішно оновлено! Перезавантажте сторінку.",
                modal_edit_prod_title: "Редагувати виріб",
                modal_edit_mat_title: "Редагувати матеріал",
                modal_del_title: "Підтвердження видалення",
                modal_del_text: "Ви дійсно бажаєте видалити цей елемент? Цю дію неможливо буде скасувати.",
                btn_cancel: "Скасувати",
                btn_save: "Зберегти зміни",
                btn_delete: "Видалити",
                curr: "грн",
                sq_cm: "см²",
                pcs: "шт",
                no_items: "Немає створених виробів",
                no_materials: "Матеріали відсутні. Додайте перший матеріал у довіднику.",
                enter_valid_name: "Будь ласка, введіть коректну назву та розміри",
                enter_valid_mat: "Будь ласка, введіть назву породи та коректний тариф"
            },
            en: {
                tab_calc: "Calculator",
                tab_settings: "Settings",
                header_title: "🛠️ Wood Product Cost Calculator",
                btn_backup: "💾 Backup",
                syncing: "● Syncing...",
                saved: "● Saved to WordPress",
                offline: "● Offline mode",
                sec1_title: "1. Quick Cost Calculator",
                lbl_calc_mat: "Material (rate per 1 cm²)",
                lbl_calc_len: "Length (mm)",
                lbl_calc_width: "Width (mm)",
                btn_send_to_form: "Send to product ↓",
                res_area: "Calculated area:",
                res_price: "Price per 1 pc:",
                sec2_title: "2. Add Product to List",
                lbl_add_name: "Product name",
                lbl_add_mat: "Choose material",
                lbl_add_price: "Price/pc (UAH)",
                btn_add_save: "+ Save",
                sec3_title: "3. Product Catalog",
                invoice_title: "ORDER CALCULATION",
                chk_hide_unselected: "Hide unselected",
                btn_select_all: "Select all",
                btn_deselect_all: "Deselect all",
                inv_cols_title: "Invoice columns:",
                col_photo: "Photo",
                col_material: "Material",
                col_dims: "Dimensions",
                col_price_pc: "Price / 1 pc",
                col_qty: "Qty",
                col_sum: "Sum",
                col_actions: "Actions",
                summary_selected: "Selected items:",
                summary_qty: "Total qty:",
                summary_sum: "Total sum:",
                btn_invoice_mode: "📸 Invoice View for Screenshot",
                btn_exit_invoice: "← Exit Invoice View",
                sec_materials_title: "🪵 Material Directory",
                lbl_mat_name: "Wood type name",
                lbl_mat_rate: "Rate per 1 cm² (UAH)",
                btn_mat_add: "+ Add",
                settings_title: "⚙️ Calculator Settings",
                settings_lang: "Interface Language",
                settings_lang_desc: "Selected language is saved automatically and used for calculator, catalog, and invoice.",
                updater_title: "🚀 GitHub Plugin Updates",
                lbl_wipe_uninstall: "Delete all data and settings on plugin uninstallation",
                desc_wipe_uninstall: "If disabled, your created materials, products catalog, and settings are preserved even after plugin uninstall.",
                tab_appearance: "Appearance",
                appearance_title: "🎨 Appearance",
                lbl_accent_color: "Accent color for buttons and active elements",
                lbl_custom_color: "Custom color:",
                appearance_desc: "The selected color is applied to buttons, checkboxes, highlights and accent borders. Your choice is saved automatically.",
                lbl_current_ver: "Current version:",
                lbl_latest_ver: "Latest on GitHub:",
                btn_check_update: "Check for Updates",
                btn_apply_update: "Update Plugin Now",
                lbl_changelog: "Release Changelog:",
                checking_update: "Checking GitHub releases...",
                update_latest: "You have the latest version installed!",
                update_found: "New version available: ",
                update_reinstall_ok: "Version is up to date. Reinstallation available if needed.",
                updating_in_progress: "Downloading and updating plugin...",
                update_success: "Plugin successfully updated! Please refresh the page.",
                modal_edit_prod_title: "Edit Product",
                modal_edit_mat_title: "Edit Material",
                modal_del_title: "Confirm Deletion",
                modal_del_text: "Are you sure you want to delete this item? This action cannot be undone.",
                btn_cancel: "Cancel",
                btn_save: "Save Changes",
                btn_delete: "Delete",
                curr: "UAH",
                sq_cm: "cm²",
                pcs: "pcs",
                no_items: "No products created yet",
                no_materials: "No materials added yet. Please add a material in directory.",
                enter_valid_name: "Please enter valid name and dimensions",
                enter_valid_mat: "Please enter wood name and valid rate"
            }
        };

        let currentLang = 'uk';
        let materials = [];
        let items = [];
        let columnVisibility = {
            photo: true,
            mat: true,
            dims: true,
            price: true,
            qty: true,
            sum: true
        };

        function t(key) {
            if (I18N[currentLang] && I18N[currentLang][key]) {
                return I18N[currentLang][key];
            }
            if (I18N['uk'] && I18N['uk'][key]) {
                return I18N['uk'][key];
            }
            return key;
        }

        function applyLanguageToDom() {
            document.querySelectorAll('[data-i18n]').forEach(el => {
                const k = el.getAttribute('data-i18n');
                if (k && I18N[currentLang] && I18N[currentLang][k]) {
                    el.textContent = I18N[currentLang][k];
                }
            });

            const radioUk = document.getElementById('wc-lang-uk');
            const radioEn = document.getElementById('wc-lang-en');
            if (radioUk && radioEn) {
                radioUk.checked = (currentLang === 'uk');
                radioEn.checked = (currentLang === 'en');
            }

            const addName = document.getElementById('add-name');
            if (addName) addName.placeholder = currentLang === 'uk' ? 'напр. Дошка дубова' : 'e.g. Oak Board';
            const matName = document.getElementById('mat-name');
            if (matName) matName.placeholder = currentLang === 'uk' ? 'напр. Дуб селект' : 'e.g. Premium Oak';

            renderMaterials();
            renderItems();
            updateCalculations();
        }

        const COLOR_PRESETS = [
            { name: 'tomchik', labelUk: 'Фірмовий tomchik', labelEn: 'Tomchik brand', color: '#95b504' },
            { name: 'graphite', labelUk: 'Темний графіт', labelEn: 'Dark graphite', color: '#24272a' },
            { name: 'red', labelUk: 'Червоний', labelEn: 'Red', color: '#dc2626' },
            { name: 'blue', labelUk: 'Класичний синій', labelEn: 'Classic blue', color: '#2563eb' },
            { name: 'indigo', labelUk: 'Фіолетовий / Індиго', labelEn: 'Purple / Indigo', color: '#7c3aed' },
            { name: 'orange', labelUk: 'Бурштиновий', labelEn: 'Amber / Orange', color: '#ea580c' }
        ];

        let accentColor = '#95b504';
        let wipeOnUninstall = false;

        window.toggleWipeOnUninstall = function(enabled) {
            wipeOnUninstall = enabled;
            saveData(true);
        };

        function applyAccentColor() {
            document.documentElement.style.setProperty('--tc-green', accentColor);
            document.documentElement.style.setProperty('--tc-green-dark', shadeColor(accentColor, -15));
            document.documentElement.style.setProperty('--tc-green-light', tintLightColor(accentColor));
        }

        function shadeColor(hex, percent) {
            const num = parseInt(hex.slice(1), 16);
            const r = (num >> 16) + percent;
            const g = ((num >> 8) & 0x00FF) + percent;
            const b = (num & 0x0000FF) + percent;
            const clamp = (v) => Math.max(0, Math.min(255, v));
            return '#' + ((clamp(r) << 16) | (clamp(g) << 8) | clamp(b)).toString(16).padStart(6, '0');
        }

        function tintLightColor(hex) {
            const num = parseInt(hex.slice(1), 16);
            const r = (num >> 16) & 0x00FF;
            const g = (num >> 8) & 0x00FF;
            const b = num & 0x0000FF;
            const lighten = (v) => Math.round(v + (255 - v) * 0.9);
            return '#' + ((lighten(r) << 16) | (lighten(g) << 8) | lighten(b)).toString(16).padStart(6, '0');
        }

        function renderColorPresets() {
            const container = document.getElementById('wc-color-presets');
            if (!container) return;
            container.innerHTML = COLOR_PRESETS.map(p => {
                const isActive = p.color.toLowerCase() === accentColor.toLowerCase();
                return `<button type="button" class="btn ${isActive ? 'btn-dark' : 'btn-outline'} btn-sm" style="gap:6px;" onclick="setAccentColor('${p.color}')">
                            <span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:${p.color};"></span>
                            <span>${currentLang === 'uk' ? p.labelUk : p.labelEn}</span>
                        </button>`;
            }).join('');
            const customInput = document.getElementById('wc-custom-color');
            if (customInput) customInput.value = accentColor;
        }

        window.setAccentColor = function(color) {
            if (!/^#[0-9a-fA-F]{6}$/.test(color)) return;
            accentColor = color;
            applyAccentColor();
            renderColorPresets();
            saveData(true);
        };

        window.setWcLanguage = function(lang) {
            if (lang !== 'uk' && lang !== 'en') return;
            currentLang = lang;
            localStorage.setItem(LANG_STORAGE_KEY, lang);
            applyLanguageToDom();
            saveData(true);
        };

        window.switchWcTab = function(tabName) {
            const panes = {
                calc: document.getElementById('wc-tab-pane-calc'),
                appearance: document.getElementById('wc-tab-pane-appearance'),
                settings: document.getElementById('wc-tab-pane-settings')
            };
            const buttons = {
                calc: document.getElementById('tab-nav-calc'),
                appearance: document.getElementById('tab-nav-appearance'),
                settings: document.getElementById('tab-nav-settings')
            };

            Object.keys(panes).forEach(key => {
                if (panes[key]) panes[key].style.display = (key === tabName) ? 'block' : 'none';
                if (buttons[key]) {
                    if (key === tabName) buttons[key].classList.add('active');
                    else buttons[key].classList.remove('active');
                }
            });

            if (tabName === 'appearance') {
                renderColorPresets();
            }
        };

        // GitHub Updater JS
        window.checkGitHubUpdate = function() {
            const statusEl = document.getElementById('update-status-msg');
            const btnRun = document.getElementById('btn-run-update');
            const latestBox = document.getElementById('wc-latest-ver-box');
            const changelogBox = document.getElementById('update-changelog-box');
            const changelogText = document.getElementById('update-changelog-text');

            if (statusEl) statusEl.textContent = t('checking_update');
            if (btnRun) btnRun.style.display = 'none';

            fetch(AJAX_URL + '?action=wood_calc_check_update')
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data) {
                        const d = res.data;
                        if (latestBox) {
                            latestBox.innerHTML = `<strong>${t('lbl_latest_ver')}</strong> <code>v${escapeHtml(d.latest_version)}</code>`;
                        }

                        if (d.update_available) {
                            if (statusEl) statusEl.textContent = t('update_found') + 'v' + d.latest_version;
                            if (btnRun) btnRun.style.display = 'inline-flex';
                        } else {
                            if (statusEl) statusEl.textContent = d.can_reinstall ? t('update_reinstall_ok') : t('update_latest');
                            if (d.can_reinstall && btnRun) {
                                btnRun.style.display = 'inline-flex';
                                btnRun.textContent = '⚡ ' + (currentLang === 'uk' ? 'Перевстановити v' : 'Reinstall v') + d.latest_version;
                            }
                        }

                        if (d.changelog && changelogBox && changelogText) {
                            changelogText.innerHTML = escapeHtml(d.changelog).replace(/\n/g, '<br>');
                            changelogBox.style.display = 'block';
                        }
                    } else {
                        if (statusEl) statusEl.textContent = (res.data && res.data.message) ? res.data.message : 'Помилка перевірки оновлення';
                    }
                })
                .catch(err => {
                    if (statusEl) statusEl.textContent = 'Помилка запиту до GitHub';
                });
        };

        window.runGitHubUpdate = function() {
            const statusEl = document.getElementById('update-status-msg');
            const btnRun = document.getElementById('btn-run-update');
            if (btnRun) btnRun.disabled = true;
            if (statusEl) statusEl.textContent = t('updating_in_progress');

            fetch(AJAX_URL + '?action=wood_calc_run_update', { method: 'POST' })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        if (statusEl) statusEl.textContent = t('update_success');
                        setTimeout(() => { location.reload(); }, 1500);
                    } else {
                        if (btnRun) btnRun.disabled = false;
                        if (statusEl) statusEl.textContent = (res.data && res.data.message) ? res.data.message : 'Помилка оновлення';
                    }
                })
                .catch(() => {
                    if (btnRun) btnRun.disabled = false;
                    if (statusEl) statusEl.textContent = 'Помилка оновлення плагіна';
                });
        };

        // Завантаження даних
        function loadData() {
            const statusEl = document.getElementById('wc-status');
            if (statusEl) statusEl.textContent = t('syncing');

            const savedLocalLang = localStorage.getItem(LANG_STORAGE_KEY);
            if (savedLocalLang === 'uk' || savedLocalLang === 'en') {
                currentLang = savedLocalLang;
            }

            fetch(AJAX_URL + '?action=wood_calc_get')
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data) {
                        materials = Array.isArray(res.data.materials) ? res.data.materials : [];
                        items = Array.isArray(res.data.items) ? res.data.items : [];
                        if (res.data.settings && res.data.settings.lang) {
                            currentLang = res.data.settings.lang;
                            localStorage.setItem(LANG_STORAGE_KEY, currentLang);
                        }
                        if (res.data.settings && res.data.settings.accent_color && /^#[0-9a-fA-F]{6}$/.test(res.data.settings.accent_color)) {
                            accentColor = res.data.settings.accent_color;
                        }
                        if (res.data.settings && typeof res.data.settings.wipe_on_uninstall !== 'undefined') {
                            wipeOnUninstall = !!res.data.settings.wipe_on_uninstall;
                            const wipeCb = document.getElementById('wc-wipe-on-uninstall');
                            if (wipeCb) wipeCb.checked = wipeOnUninstall;
                        }
                        applyAccentColor();
                        
                        localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify({
                            materials: materials,
                            items: items,
                            settings: { lang: currentLang, accent_color: accentColor, wipe_on_uninstall: wipeOnUninstall }
                        }));
                        if (statusEl) statusEl.textContent = t('saved');
                    } else {
                        fallbackToLocalStorage();
                    }
                    applyLanguageToDom();
                })
                .catch(() => {
                    fallbackToLocalStorage();
                    applyLanguageToDom();
                });
        }

        function fallbackToLocalStorage() {
            const statusEl = document.getElementById('wc-status');
            const local = localStorage.getItem(LOCAL_STORAGE_KEY);
            if (local) {
                try {
                    const parsed = JSON.parse(local);
                    if (parsed.materials && Array.isArray(parsed.materials)) materials = parsed.materials;
                    if (parsed.items && Array.isArray(parsed.items)) items = parsed.items;
                    if (parsed.settings && parsed.settings.lang) {
                        currentLang = parsed.settings.lang;
                    }
                    if (parsed.settings && parsed.settings.accent_color) {
                        accentColor = parsed.settings.accent_color;
                    }
                    if (parsed.settings && typeof parsed.settings.wipe_on_uninstall !== 'undefined') {
                        wipeOnUninstall = !!parsed.settings.wipe_on_uninstall;
                        const wipeCb = document.getElementById('wc-wipe-on-uninstall');
                        if (wipeCb) wipeCb.checked = wipeOnUninstall;
                    }
                    applyAccentColor();
                    if (statusEl) statusEl.textContent = t('offline');
                } catch(e) {}
            }
        }

        function saveData(silent) {
            const statusEl = document.getElementById('wc-status');
            if (!silent && statusEl) statusEl.textContent = t('syncing');

            const payload = {
                materials: materials,
                items: items,
                settings: { lang: currentLang, accent_color: accentColor, wipe_on_uninstall: wipeOnUninstall }
            };

            localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(payload));
            localStorage.setItem(LANG_STORAGE_KEY, currentLang);

            fetch(AJAX_URL + '?action=wood_calc_save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success && statusEl) {
                    statusEl.textContent = t('saved');
                }
            })
            .catch(() => {
                if (statusEl) statusEl.textContent = t('offline');
            });
        }

        window.downloadDataBackup = function() {
            const data = {
                materials: materials,
                items: items,
                settings: { lang: currentLang },
                export_date: new Date().toISOString()
            };
            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'wood-calculator-backup-' + new Date().toISOString().slice(0, 10) + '.json';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        };

        window.runQuickCalc = function() {
            const matSel = document.getElementById('calc-mat');
            const len = parseFloat(document.getElementById('calc-len').value) || 0;
            const width = parseFloat(document.getElementById('calc-width').value) || 0;
            const rate = parseFloat(matSel ? matSel.value : 0) || 0;

            const areaCm2 = (len * width) / 100;
            const price = areaCm2 * rate;

            const resArea = document.getElementById('res-area');
            const resPrice = document.getElementById('res-price');
            if (resArea) resArea.textContent = areaCm2.toFixed(1) + ' ' + t('sq_cm');
            if (resPrice) resPrice.textContent = price.toFixed(2) + ' ' + t('curr');
        };

        window.sendToSaveForm = function() {
            const matSel = document.getElementById('calc-mat');
            const lenVal = document.getElementById('calc-len').value;
            const widthVal = document.getElementById('calc-width').value;
            const selectedOpt = matSel ? matSel.options[matSel.selectedIndex] : null;
            const matName = selectedOpt ? selectedOpt.getAttribute('data-name') : '';

            const len = parseFloat(lenVal) || 0;
            const width = parseFloat(widthVal) || 0;
            const rate = parseFloat(matSel ? matSel.value : 0) || 0;
            const price = ((len * width) / 100) * rate;

            const addName = document.getElementById('add-name');
            const addMat = document.getElementById('add-mat');
            const addLen = document.getElementById('add-len');
            const addWidth = document.getElementById('add-width');
            const addPrice = document.getElementById('add-price');

            if (addName) addName.value = (currentLang === 'uk' ? 'Виріб #' : 'Product #') + (items.length + 1);
            if (addLen) addLen.value = lenVal;
            if (addWidth) addWidth.value = widthVal;
            if (addPrice) addPrice.value = price.toFixed(2);

            if (addMat && matName) {
                for (let i = 0; i < addMat.options.length; i++) {
                    if (addMat.options[i].text === matName) {
                        addMat.selectedIndex = i;
                        break;
                    }
                }
            }
        };

        function renderMaterials() {
            const tbody = document.getElementById('materials-tbody');
            const calcMat = document.getElementById('calc-mat');
            const addMat = document.getElementById('add-mat');
            const editProdMat = document.getElementById('edit-prod-mat');

            if (tbody) {
                if (materials.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#94a3b8; padding:16px;">' + t('no_materials') + '</td></tr>';
                } else {
                    tbody.innerHTML = materials.map((m, idx) => `
                        <tr>
                            <td><strong>${escapeHtml(m.name)}</strong></td>
                            <td>${parseFloat(m.price)}</td>
                            <td style="text-align:right;">
                                <button type="button" class="btn btn-outline btn-sm" onclick="openEditMaterialModal(${idx})" title="${t('modal_edit_mat_title')}">✏️</button>
                                <button type="button" class="btn btn-danger btn-sm" onclick="askDeleteMaterial(${idx})" title="${t('btn_delete')}">🗑️</button>
                            </td>
                        </tr>
                    `).join('');
                }
            }

            const optionsHtml = materials.length === 0 
                ? `<option value="0">${t('no_materials')}</option>`
                : materials.map(m => `<option value="${m.price}" data-name="${escapeHtml(m.name)}">${escapeHtml(m.name)} (${parseFloat(m.price)} ${t('curr')}/${t('sq_cm')})</option>`).join('');

            if (calcMat) calcMat.innerHTML = optionsHtml;
            if (addMat) addMat.innerHTML = optionsHtml;
            if (editProdMat) editProdMat.innerHTML = optionsHtml;

            runQuickCalc();
        }

        window.addNewMaterial = function() {
            const nameInput = document.getElementById('mat-name');
            const priceInput = document.getElementById('mat-price');
            const name = (nameInput ? nameInput.value : '').trim();
            const price = parseFloat(priceInput ? priceInput.value : 0);

            if (!name || isNaN(price) || price <= 0) {
                alert(t('enter_valid_mat'));
                return;
            }

            materials.push({
                id: 'm_' + Date.now(),
                name: name,
                price: price
            });

            if (nameInput) nameInput.value = '';
            if (priceInput) priceInput.value = '';

            renderMaterials();
            saveData();
        };

        window.openEditMaterialModal = function(idx) {
            const m = materials[idx];
            if (!m) return;
            document.getElementById('edit-mat-id').value = idx;
            document.getElementById('edit-mat-name').value = m.name;
            document.getElementById('edit-mat-price').value = m.price;
            document.getElementById('modal-edit-material').style.display = 'flex';
        };

        window.closeEditMaterialModal = function() {
            document.getElementById('modal-edit-material').style.display = 'none';
        };

        window.saveEditedMaterial = function() {
            const idx = parseInt(document.getElementById('edit-mat-id').value, 10);
            const name = document.getElementById('edit-mat-name').value.trim();
            const price = parseFloat(document.getElementById('edit-mat-price').value);

            if (!name || isNaN(price) || price <= 0) {
                alert(t('enter_valid_mat'));
                return;
            }

            if (materials[idx]) {
                materials[idx].name = name;
                materials[idx].price = price;
            }

            closeEditMaterialModal();
            renderMaterials();
            saveData();
        };

        window.askDeleteMaterial = function(idx) {
            showConfirmModal(() => {
                materials.splice(idx, 1);
                renderMaterials();
                saveData();
            });
        };

        window.addCustomProduct = function() {
            const nameEl = document.getElementById('add-name');
            const matEl = document.getElementById('add-mat');
            const lenEl = document.getElementById('add-len');
            const widthEl = document.getElementById('add-width');
            const priceEl = document.getElementById('add-price');

            const name = (nameEl ? nameEl.value : '').trim();
            const selectedOpt = matEl ? matEl.options[matEl.selectedIndex] : null;
            const matName = selectedOpt ? selectedOpt.getAttribute('data-name') : '';
            const len = parseFloat(lenEl ? lenEl.value : 0) || 0;
            const width = parseFloat(widthEl ? widthEl.value : 0) || 0;
            const price = parseFloat(priceEl ? priceEl.value : 0) || 0;

            if (!name) {
                alert(t('enter_valid_name'));
                return;
            }

            const newItem = {
                id: 'p_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
                name: name,
                material: matName || '-',
                len: len,
                width: width,
                price: price,
                qty: 1,
                selected: true,
                photo: ''
            };

            items.push(newItem);

            if (nameEl) nameEl.value = '';
            if (lenEl) lenEl.value = '';
            if (widthEl) widthEl.value = '';
            if (priceEl) priceEl.value = '';

            renderItems();
            saveData();
        };

        window.openEditProductModal = function(id) {
            const item = items.find(it => it.id === id);
            if (!item) return;

            document.getElementById('edit-prod-id').value = item.id;
            document.getElementById('edit-prod-name').value = item.name;
            document.getElementById('edit-prod-len').value = item.len;
            document.getElementById('edit-prod-width').value = item.width;
            document.getElementById('edit-prod-price').value = item.price;

            const editMatSel = document.getElementById('edit-prod-mat');
            if (editMatSel) {
                for (let i = 0; i < editMatSel.options.length; i++) {
                    if (editMatSel.options[i].getAttribute('data-name') === item.material) {
                        editMatSel.selectedIndex = i;
                        break;
                    }
                }
            }

            document.getElementById('modal-edit-product').style.display = 'flex';
        };

        window.closeEditProductModal = function() {
            document.getElementById('modal-edit-product').style.display = 'none';
        };

        window.saveEditedProduct = function() {
            const id = document.getElementById('edit-prod-id').value;
            const item = items.find(it => it.id === id);
            if (!item) return;

            const name = document.getElementById('edit-prod-name').value.trim();
            const editMatSel = document.getElementById('edit-prod-mat');
            const selectedOpt = editMatSel ? editMatSel.options[editMatSel.selectedIndex] : null;
            const matName = selectedOpt ? selectedOpt.getAttribute('data-name') : item.material;
            const len = parseFloat(document.getElementById('edit-prod-len').value) || 0;
            const width = parseFloat(document.getElementById('edit-prod-width').value) || 0;
            const price = parseFloat(document.getElementById('edit-prod-price').value) || 0;

            if (!name) {
                alert(t('enter_valid_name'));
                return;
            }

            item.name = name;
            item.material = matName;
            item.len = len;
            item.width = width;
            item.price = price;

            closeEditProductModal();
            renderItems();
            saveData();
        };

        window.duplicateItem = function(id) {
            const item = items.find(it => it.id === id);
            if (!item) return;

            const copy = JSON.parse(JSON.stringify(item));
            copy.id = 'p_' + Date.now() + '_' + Math.floor(Math.random() * 1000);
            copy.name = copy.name + (currentLang === 'uk' ? ' (копія)' : ' (copy)');

            const idx = items.findIndex(it => it.id === id);
            items.splice(idx + 1, 0, copy);

            renderItems();
            saveData();
        };

        window.askDeleteItem = function(id) {
            showConfirmModal(() => {
                items = items.filter(it => it.id !== id);
                renderItems();
                saveData();
            });
        };

        let confirmCallback = null;
        function showConfirmModal(callback) {
            confirmCallback = callback;
            document.getElementById('modal-confirm-delete').style.display = 'flex';
        }
        window.closeConfirmModal = function() {
            document.getElementById('modal-confirm-delete').style.display = 'none';
            confirmCallback = null;
        };
        const confirmBtn = document.getElementById('confirm-del-btn');
        if (confirmBtn) {
            confirmBtn.onclick = function() {
                if (confirmCallback) confirmCallback();
                closeConfirmModal();
            };
        }

        function renderItems() {
            const tbody = document.getElementById('items-tbody');
            if (!tbody) return;

            const hideUnselected = document.getElementById('filter-selected')?.checked || false;
            let displayItems = items;
            if (hideUnselected) {
                displayItems = items.filter(it => it.selected);
            }

            if (displayItems.length === 0) {
                tbody.innerHTML = `<tr><td colspan="10" style="text-align:center; color:#94a3b8; padding:20px;">${t('no_items')}</td></tr>`;
                updateCalculations();
                return;
            }

            tbody.innerHTML = displayItems.map((item) => {
                const isSelected = item.selected !== false;
                const qty = item.qty || 1;
                const sum = (item.price * qty).toFixed(2);
                const dimsText = (item.len && item.width) ? `${item.len} × ${item.width} мм` : '-';

                return `
                    <tr id="row-${item.id}" data-id="${item.id}">
                        <td class="no-invoice" style="text-align:center;">
                            <span class="wc-drag-handle" title="Перетягнути для зміни порядку" draggable="true">⠿</span>
                        </td>
                        <td>
                            <input type="checkbox" ${isSelected ? 'checked' : ''} onchange="setItemSelected('${item.id}', this.checked)">
                        </td>
                        <td class="col-photo-cell">
                            ${item.photo ? `<img src="${escapeHtml(item.photo)}" style="width:36px; height:36px; object-fit:cover; border-radius:4px;">` : `<span style="color:#cbd5e1; font-size:16px;">🖼️</span>`}
                        </td>
                        <td>
                            <strong>${escapeHtml(item.name)}</strong>
                        </td>
                        <td class="col-mat-cell">${escapeHtml(item.material || '-')}</td>
                        <td class="col-dims-cell">${dimsText}</td>
                        <td class="col-price-cell">${parseFloat(item.price).toFixed(2)} ${t('curr')}</td>
                        <td class="col-qty-cell">
                            <input type="number" min="1" value="${qty}" style="width:65px; padding:4px 6px;" onchange="setItemQty('${item.id}', this.value)">
                        </td>
                        <td class="col-sum-cell" style="text-align:right; font-weight:700;">
                            ${sum} ${t('curr')}
                        </td>
                        <td class="no-invoice" style="text-align:right; white-space:nowrap;">
                            <button type="button" class="btn btn-outline btn-sm" onclick="duplicateItem('${item.id}')" title="Дублювати">📋</button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="openEditProductModal('${item.id}')" title="Редагувати">✏️</button>
                            <button type="button" class="btn btn-danger btn-sm" onclick="askDeleteItem('${item.id}')" title="Видалити">🗑️</button>
                        </td>
                    </tr>
                `;
            }).join('');

            applyColumnVisibility();
            setupDragAndDrop();
            updateCalculations();
        }

        window.setItemSelected = function(id, selected) {
            const item = items.find(it => it.id === id);
            if (item) {
                item.selected = selected;
                saveData(true);
                updateCalculations();
            }
        };

        window.setItemQty = function(id, val) {
            const item = items.find(it => it.id === id);
            if (item) {
                item.qty = Math.max(1, parseInt(val, 10) || 1);
                saveData(true);
                renderItems();
            }
        };

        window.toggleSelectAll = function(selectAll) {
            items.forEach(it => it.selected = selectAll);
            const topCb = document.getElementById('select-all-top');
            if (topCb) topCb.checked = selectAll;
            saveData(true);
            renderItems();
        };

        function updateCalculations() {
            let selectedCount = 0;
            let totalQty = 0;
            let grandTotal = 0;

            items.forEach(item => {
                if (item.selected) {
                    selectedCount++;
                    const qty = item.qty || 1;
                    totalQty += qty;
                    grandTotal += (item.price * qty);
                }
            });

            const countEl = document.getElementById('sum-items-count');
            const qtyEl = document.getElementById('sum-total-qty');
            const sumEl = document.getElementById('sum-grand-total');

            if (countEl) countEl.textContent = selectedCount;
            if (qtyEl) qtyEl.textContent = totalQty;
            if (sumEl) sumEl.textContent = grandTotal.toFixed(2);
        }

        window.toggleColumnVisibility = function(colName, isVisible) {
            columnVisibility[colName] = isVisible;
            applyColumnVisibility();
        };

        function applyColumnVisibility() {
            const setDisplay = (selector, visible) => {
                document.querySelectorAll(selector).forEach(el => {
                    el.style.display = visible ? '' : 'none';
                });
            };

            setDisplay('.col-photo-header, .col-photo-cell', columnVisibility.photo);
            setDisplay('.col-mat-header, .col-mat-cell', columnVisibility.mat);
            setDisplay('.col-dims-header, .col-dims-cell', columnVisibility.dims);
            setDisplay('.col-price-header, .col-price-cell', columnVisibility.price);
            setDisplay('.col-qty-header, .col-qty-cell', columnVisibility.qty);
            setDisplay('.col-sum-header, .col-sum-cell', columnVisibility.sum);
        }

        window.enterInvoiceMode = function() {
            document.body.classList.add('invoice-mode');
            const dateEl = document.getElementById('inv-date');
            if (dateEl) {
                const now = new Date();
                dateEl.textContent = now.toLocaleDateString(currentLang === 'uk' ? 'uk-UA' : 'en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }
            const exitBtn = document.getElementById('exit-invoice-container');
            if (exitBtn) exitBtn.style.display = 'block';
        };

        window.exitInvoiceMode = function() {
            document.body.classList.remove('invoice-mode');
            const exitBtn = document.getElementById('exit-invoice-container');
            if (exitBtn) exitBtn.style.display = 'none';
        };

        let draggedRow = null;
        function setupDragAndDrop() {
            const handles = document.querySelectorAll('.wc-drag-handle');
            handles.forEach(handle => {
                const row = handle.closest('tr');
                if (!row) return;

                handle.onmousedown = () => { row.draggable = true; };
                handle.onmouseup = () => { row.draggable = false; };

                row.ondragstart = (e) => {
                    draggedRow = row;
                    row.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                };

                row.ondragend = () => {
                    row.draggable = false;
                    row.classList.remove('dragging');
                    document.querySelectorAll('tr').forEach(r => {
                        r.classList.remove('drop-above');
                        r.classList.remove('drop-below');
                    });
                    draggedRow = null;
                };

                row.ondragover = (e) => {
                    e.preventDefault();
                    if (!draggedRow || draggedRow === row) return;
                    const rect = row.getBoundingClientRect();
                    const mid = rect.top + rect.height / 2;
                    row.classList.remove('drop-above', 'drop-below');
                    if (e.clientY < mid) {
                        row.classList.add('drop-above');
                    } else {
                        row.classList.add('drop-below');
                    }
                };

                row.ondrop = (e) => {
                    e.preventDefault();
                    if (!draggedRow || draggedRow === row) return;

                    const srcId = draggedRow.getAttribute('data-id');
                    const targetId = row.getAttribute('data-id');

                    const srcIdx = items.findIndex(it => it.id === srcId);
                    const targetIdx = items.findIndex(it => it.id === targetId);

                    if (srcIdx !== -1 && targetIdx !== -1) {
                        const [moved] = items.splice(srcIdx, 1);
                        const isBelow = row.classList.contains('drop-below');
                        const newIdx = isBelow ? (srcIdx < targetIdx ? targetIdx : targetIdx + 1) : (srcIdx < targetIdx ? targetIdx - 1 : targetIdx);
                        items.splice(newIdx, 0, moved);
                        renderItems();
                        saveData();
                    }
                };
            });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        document.addEventListener('DOMContentLoaded', loadData);
        loadData();
    })();
    </script>
    <?php
    return ob_get_clean();
}
