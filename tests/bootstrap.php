<?php
/**
 * PHPUnit bootstrap file for Eye-Book plugin tests
 *
 * @package EyeBook
 * @subpackage Tests
 * @since 1.0.0
 */

// Set up the WordPress testing environment
$_tests_dir = getenv('WP_TESTS_DIR');

if ( ! $_tests_dir ) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the Eye-Book plugin
 *
 * @since 1.0.0
 */
function _manually_load_eye_book_plugin() {
    require dirname(__FILE__) . '/../eye-book.php';
}

tests_add_filter('muplugins_loaded', '_manually_load_eye_book_plugin');

require $_tests_dir . '/includes/bootstrap.php';

// Additional test setup
require_once dirname(__FILE__) . '/includes/class-eye-book-test-case.php';
require_once dirname(__FILE__) . '/includes/class-eye-book-test-factory.php';
require_once dirname(__FILE__) . '/includes/eye-book-test-helpers.php';