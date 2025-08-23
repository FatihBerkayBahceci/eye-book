<?php
/**
 * PHPUnit bootstrap file for Eye-Book plugin tests (Standalone)
 *
 * @package EyeBook
 * @subpackage Tests
 * @since 1.0.0
 */

// Define basic WordPress constants
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__, 4) . '/');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
}

// Define Eye-Book constants
define('EYE_BOOK_VERSION', '1.0.0');
define('EYE_BOOK_DB_VERSION', '1.0.0');
define('EYE_BOOK_PATH', dirname(__FILE__, 2) . '/');
define('EYE_BOOK_URL', 'http://localhost/wp-content/plugins/eye-book/');
define('EYE_BOOK_TEST_MODE', true);

// Include test helpers
require_once __DIR__ . '/includes/eye-book-test-helpers.php';

// Mock WordPress functions for testing
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        static $options = array();
        return isset($options[$option]) ? $options[$option] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        static $options = array();
        $options[$option] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option($option) {
        static $options = array();
        unset($options[$option]);
        return true;
    }
}

if (!function_exists('add_option')) {
    function add_option($option, $value) {
        return update_option($option, $value);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($str) {
        return filter_var($str, FILTER_SANITIZE_STRING);
    }
}

if (!function_exists('sanitize_email')) {
    function sanitize_email($email) {
        return filter_var($email, FILTER_SANITIZE_EMAIL);
    }
}

if (!function_exists('is_email')) {
    function is_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('wp_hash')) {
    function wp_hash($data, $scheme = 'auth') {
        return hash('sha256', $data . 'wp_salt');
    }
}

// Initialize global $wpdb
global $wpdb;
if (!isset($wpdb)) {
    $wpdb = new wpdb();
}

// Create test tables if needed
eye_book_create_test_tables();

echo "Eye-Book Standalone Test Bootstrap Loaded\n";