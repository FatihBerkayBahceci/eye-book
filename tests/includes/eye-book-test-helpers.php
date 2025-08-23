<?php
/**
 * Eye-Book Test Helper Functions
 *
 * @package EyeBook
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Mock WordPress functions for testing
 *
 * @since 1.0.0
 */
if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        return array(
            'basedir' => '/tmp/wp-uploads',
            'baseurl' => 'http://example.com/wp-content/uploads',
            'path' => '/tmp/wp-uploads',
            'url' => 'http://example.com/wp-content/uploads',
            'subdir' => '',
            'error' => false
        );
    }
}

if (!function_exists('current_time')) {
    function current_time($type, $gmt = 0) {
        return $gmt ? gmdate('Y-m-d H:i:s') : date('Y-m-d H:i:s');
    }
}

if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action) {
        return 'test_nonce_' . md5($action);
    }
}

if (!function_exists('wp_verify_nonce')) {
    function wp_verify_nonce($nonce, $action) {
        return $nonce === 'test_nonce_' . md5($action);
    }
}

/**
 * Helper function to create test database tables
 *
 * @since 1.0.0
 */
function eye_book_create_test_tables() {
    global $wpdb;
    
    // Create simplified test tables
    $tables = array(
        'eye_book_locations' => "
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            address text,
            phone varchar(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ",
        'eye_book_providers' => "
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11),
            first_name varchar(100),
            last_name varchar(100),
            specialization varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ",
        'eye_book_patients' => "
            id int(11) NOT NULL AUTO_INCREMENT,
            first_name varchar(100),
            last_name varchar(100),
            email varchar(255),
            phone varchar(20),
            date_of_birth date,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ",
        'eye_book_appointments' => "
            id int(11) NOT NULL AUTO_INCREMENT,
            patient_id int(11),
            provider_id int(11),
            location_id int(11),
            start_datetime datetime,
            end_datetime datetime,
            status varchar(20) DEFAULT 'scheduled',
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ",
        'eye_book_audit_log' => "
            id int(11) NOT NULL AUTO_INCREMENT,
            event_type varchar(100),
            object_type varchar(50),
            object_id int(11),
            user_id int(11),
            ip_address varchar(45),
            user_agent text,
            risk_level varchar(20),
            event_details text,
            hash varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        "
    );
    
    foreach ($tables as $table_name => $schema) {
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}{$table_name} ({$schema}) {$wpdb->get_charset_collate()};";
        $wpdb->query($sql);
    }
}

/**
 * Helper function to generate test data
 *
 * @param string $type Data type
 * @param array $args Additional arguments
 * @return array
 * @since 1.0.0
 */
function eye_book_generate_test_data($type, $args = array()) {
    $defaults = array();
    
    switch ($type) {
        case 'patient':
            $defaults = array(
                'first_name' => 'Test',
                'last_name' => 'Patient',
                'email' => 'test@example.com',
                'phone' => '(555) 123-4567',
                'date_of_birth' => '1980-01-01',
                'status' => 'active'
            );
            break;
            
        case 'provider':
            $defaults = array(
                'first_name' => 'Dr. Test',
                'last_name' => 'Provider',
                'specialization' => 'Ophthalmology',
                'user_id' => 1
            );
            break;
            
        case 'location':
            $defaults = array(
                'name' => 'Test Eye Clinic',
                'address' => '123 Test Street, Test City, TC 12345',
                'phone' => '(555) 999-8888'
            );
            break;
            
        case 'appointment':
            $defaults = array(
                'patient_id' => 1,
                'provider_id' => 1,
                'location_id' => 1,
                'start_datetime' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'end_datetime' => date('Y-m-d H:i:s', strtotime('+1 day +30 minutes')),
                'status' => 'scheduled',
                'notes' => 'Test appointment'
            );
            break;
    }
    
    return array_merge($defaults, $args);
}

/**
 * Mock WordPress database functions
 *
 * @since 1.0.0
 */
if (!class_exists('wpdb')) {
    class wpdb {
        public $prefix = 'wp_';
        public $last_result = array();
        public $insert_id = 1;
        
        public function prepare($query, ...$args) {
            return vsprintf(str_replace('%s', "'%s'", $query), $args);
        }
        
        public function get_results($query, $output = OBJECT) {
            return array();
        }
        
        public function get_row($query, $output = OBJECT, $y = 0) {
            return null;
        }
        
        public function get_var($query = null, $x = 0, $y = 0) {
            return null;
        }
        
        public function query($query) {
            return true;
        }
        
        public function insert($table, $data, $format = null) {
            $this->insert_id++;
            return true;
        }
        
        public function update($table, $data, $where, $format = null, $where_format = null) {
            return true;
        }
        
        public function delete($table, $where, $where_format = null) {
            return true;
        }
        
        public function get_charset_collate() {
            return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        }
    }
}

/**
 * Mock WordPress constants and globals
 *
 * @since 1.0.0
 */
if (!defined('HOUR_IN_SECONDS')) {
    define('HOUR_IN_SECONDS', 3600);
}

if (!defined('DAY_IN_SECONDS')) {
    define('DAY_IN_SECONDS', 86400);
}

if (!defined('WEEK_IN_SECONDS')) {
    define('WEEK_IN_SECONDS', 604800);
}

if (!defined('MONTH_IN_SECONDS')) {
    define('MONTH_IN_SECONDS', 2592000);
}

if (!defined('YEAR_IN_SECONDS')) {
    define('YEAR_IN_SECONDS', 31536000);
}

// Mock global wpdb if not already set
if (!isset($GLOBALS['wpdb'])) {
    $GLOBALS['wpdb'] = new wpdb();
}

/**
 * Simple test assertion functions
 *
 * @since 1.0.0
 */
function eye_book_assert_equals($expected, $actual, $message = '') {
    if ($expected !== $actual) {
        throw new Exception($message ?: "Expected '{$expected}', got '{$actual}'");
    }
    return true;
}

function eye_book_assert_true($value, $message = '') {
    if ($value !== true) {
        throw new Exception($message ?: "Expected true, got " . var_export($value, true));
    }
    return true;
}

function eye_book_assert_false($value, $message = '') {
    if ($value !== false) {
        throw new Exception($message ?: "Expected false, got " . var_export($value, true));
    }
    return true;
}

function eye_book_assert_not_null($value, $message = '') {
    if ($value === null) {
        throw new Exception($message ?: "Expected non-null value, got null");
    }
    return true;
}