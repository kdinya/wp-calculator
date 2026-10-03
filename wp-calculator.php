<?php
/**
 * Plugin Name: WP Calculator
 * Plugin URI: https://github.com/kdinya/wp-calculator
 * Description: Universal product and material cost calculator with materials directory, invoice generation, multiple export options, customizable styling, and reliable GitHub auto-updates.
 * Version: 1.0.1
 * Author: kdinya
 * Author URI: https://github.com/kdinya
 * Text Domain: wp-calculator
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WP_CALCULATOR_VERSION', '1.0.1');
define('WP_CALCULATOR_FILE', __FILE__);
define('WP_CALCULATOR_PATH', plugin_dir_path(__FILE__));
define('WP_CALCULATOR_URL', plugin_dir_url(__FILE__));

/**
 * Bootstrap the plugin with strict zero-footprint on public frontend requests.
 * Only loads admin controllers, templates, and updater when in admin context,
 * handling an AJAX action, or running WP-Cron.
 */
function wp_calculator_bootstrap() {
    $is_admin   = is_admin();
    $doing_ajax = (defined('DOING_AJAX') && DOING_AJAX) || (function_exists('wp_doing_ajax') && wp_doing_ajax());
    $doing_cron = (defined('DOING_CRON') && DOING_CRON) || (function_exists('wp_doing_cron') && wp_doing_cron());

    // Zero-load guard: for regular public visitors, do not parse admin classes or touch the database
    if (!$is_admin && !$doing_ajax && !$doing_cron) {
        return;
    }

    // Load data storage and AJAX handlers whenever in admin or processing AJAX
    if ($doing_ajax || $is_admin) {
        require_once WP_CALCULATOR_PATH . 'includes/class-wp-calculator-ajax.php';
    }

    // Load admin UI components exclusively in WordPress admin dashboard
    if ($is_admin) {
        require_once WP_CALCULATOR_PATH . 'includes/class-wp-calculator-admin.php';
        new WpCalculatorAdmin();
    }

    // Load updater hooks for admin dashboard, AJAX, or WP-Cron update routines
    if ($is_admin || $doing_ajax || $doing_cron) {
        require_once WP_CALCULATOR_PATH . 'includes/class-wp-calculator-updater.php';
        $updater = new WpCalculatorGitHubUpdater(WP_CALCULATOR_FILE, 'kdinya', 'wp-calculator', WP_CALCULATOR_VERSION);
        $updater->register();
    }
}
add_action('plugins_loaded', 'wp_calculator_bootstrap');
