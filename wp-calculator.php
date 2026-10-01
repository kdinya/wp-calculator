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


