<?php
/**
 * Plugin Name: WP Calculator
 * Plugin URI: https://github.com/kdinya/wp-calculator
 * Description: Професійний калькулятор вартості виробів з довідником матеріалів, формуванням накладної, персоналізованим оформленням та надійним автооновленням.
 * Version: 1.0.0
 * Author: kdinya
 * Author URI: https://github.com/kdinya
 * Text Domain: wp-calculator
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WP_CALCULATOR_VERSION', '1.0.0');
define('WP_CALCULATOR_FILE', __FILE__);
define('WP_CALCULATOR_PATH', plugin_dir_path(__FILE__));
define('WP_CALCULATOR_URL', plugin_dir_url(__FILE__));

require_once WP_CALCULATOR_PATH . 'includes/class-wp-calculator-updater.php';
require_once WP_CALCULATOR_PATH . 'includes/class-wp-calculator-ajax.php';
require_once WP_CALCULATOR_PATH . 'includes/class-wp-calculator-admin.php';

function wp_calculator_init() {
    new WpCalculatorGitHubUpdater(WP_CALCULATOR_FILE, WP_CALCULATOR_VERSION);
    new WpCalculatorAdmin();
}
add_action('plugins_loaded', 'wp_calculator_init');
