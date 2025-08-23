<?php
/**
 * Base test case class for Eye-Book tests
 *
 * @package EyeBook
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Eye_Book_Test_Case Class
 *
 * Extended WP_UnitTestCase with Eye-Book specific setup
 *
 * @class Eye_Book_Test_Case
 * @extends WP_UnitTestCase
 * @since 1.0.0
 */
class Eye_Book_Test_Case extends WP_UnitTestCase {

    /**
     * Eye-Book plugin instance
     *
     * @var EyeBook
     * @since 1.0.0
     */
    protected $plugin;

    /**
     * Test factory
     *
     * @var Eye_Book_Test_Factory
     * @since 1.0.0
     */
    protected $eye_book_factory;

    /**
     * Set up before each test
     *
     * @since 1.0.0
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialize plugin
        $this->plugin = EyeBook::instance();
        
        // Initialize test factory
        $this->eye_book_factory = new Eye_Book_Test_Factory();
        
        // Create Eye-Book database tables
        $this->create_eye_book_tables();
        
        // Set up test user roles and capabilities
        $this->setup_test_roles();
        
        // Clear any existing transients
        $this->clear_eye_book_transients();
        
        // Reset options to defaults
        $this->reset_eye_book_options();
    }

    /**
     * Tear down after each test
     *
     * @since 1.0.0
     */
    public function tearDown(): void {
        // Clean up test data
        $this->cleanup_test_data();
        
        // Remove test files
        $this->cleanup_test_files();
        
        // Clear transients
        $this->clear_eye_book_transients();
        
        parent::tearDown();
    }

    /**
     * Create Eye-Book database tables for testing
     *
     * @since 1.0.0
     */
    protected function create_eye_book_tables() {
        $database = new Eye_Book_Database();
        $database->create_tables();
    }

    /**
     * Set up test user roles and capabilities
     *
     * @since 1.0.0
     */
    protected function setup_test_roles() {
        // Add Eye-Book roles if they don't exist
        if (!get_role('eye_book_provider')) {
            add_role('eye_book_provider', 'Eye Care Provider', array(
                'read' => true,
                'eye_book_view_patients' => true,
                'eye_book_edit_patients' => true,
                'eye_book_view_appointments' => true,
                'eye_book_edit_appointments' => true
            ));
        }

        if (!get_role('eye_book_nurse')) {
            add_role('eye_book_nurse', 'Eye Care Nurse', array(
                'read' => true,
                'eye_book_view_patients' => true,
                'eye_book_view_appointments' => true,
                'eye_book_edit_appointments' => true
            ));
        }

        if (!get_role('eye_book_receptionist')) {
            add_role('eye_book_receptionist', 'Receptionist', array(
                'read' => true,
                'eye_book_view_appointments' => true,
                'eye_book_edit_appointments' => true
            ));
        }
    }

    /**
     * Clear Eye-Book transients
     *
     * @since 1.0.0
     */
    protected function clear_eye_book_transients() {
        global $wpdb;
        
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_eye_book_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_eye_book_%'");
    }

    /**
     * Reset Eye-Book options to defaults
     *
     * @since 1.0.0
     */
    protected function reset_eye_book_options() {
        $default_options = array(
            'eye_book_version' => EYE_BOOK_VERSION,
            'eye_book_db_version' => EYE_BOOK_DB_VERSION,
            'eye_book_security_level' => 'hipaa_compliant',
            'eye_book_encryption_enabled' => true,
            'eye_book_audit_enabled' => true,
            'eye_book_backup_enabled' => true
        );

        foreach ($default_options as $option => $value) {
            update_option($option, $value);
        }
    }

    /**
     * Clean up test data
     *
     * @since 1.0.0
     */
    protected function cleanup_test_data() {
        global $wpdb;
        
        // Clean up Eye-Book tables
        $tables = array(
            'eye_book_appointments',
            'eye_book_patients',
            'eye_book_providers',
            'eye_book_locations',
            'eye_book_appointment_types',
            'eye_book_provider_schedules',
            'eye_book_audit_log',
            'eye_book_payments'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}{$table}");
        }
    }

    /**
     * Clean up test files
     *
     * @since 1.0.0
     */
    protected function cleanup_test_files() {
        $upload_dir = wp_upload_dir();
        $test_dir = $upload_dir['basedir'] . '/eye-book-test/';
        
        if (file_exists($test_dir)) {
            $this->delete_directory($test_dir);
        }
    }

    /**
     * Recursively delete directory
     *
     * @param string $dir Directory path
     * @since 1.0.0
     */
    protected function delete_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->delete_directory($path) : unlink($path);
        }
        
        rmdir($dir);
    }

    /**
     * Create test appointment
     *
     * @param array $args Appointment arguments
     * @return int Appointment ID
     * @since 1.0.0
     */
    protected function create_test_appointment($args = array()) {
        return $this->eye_book_factory->appointment->create($args);
    }

    /**
     * Create test patient
     *
     * @param array $args Patient arguments
     * @return int Patient ID
     * @since 1.0.0
     */
    protected function create_test_patient($args = array()) {
        return $this->eye_book_factory->patient->create($args);
    }

    /**
     * Create test provider
     *
     * @param array $args Provider arguments
     * @return int Provider ID
     * @since 1.0.0
     */
    protected function create_test_provider($args = array()) {
        return $this->eye_book_factory->provider->create($args);
    }

    /**
     * Create test location
     *
     * @param array $args Location arguments
     * @return int Location ID
     * @since 1.0.0
     */
    protected function create_test_location($args = array()) {
        return $this->eye_book_factory->location->create($args);
    }

    /**
     * Assert that database table exists
     *
     * @param string $table_name Table name
     * @since 1.0.0
     */
    protected function assertTableExists($table_name) {
        global $wpdb;
        
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $wpdb->prefix . $table_name
        ));
        
        $this->assertNotNull($table_exists, "Table {$table_name} should exist");
    }

    /**
     * Assert that option has expected value
     *
     * @param string $option_name Option name
     * @param mixed $expected_value Expected value
     * @since 1.0.0
     */
    protected function assertOptionEquals($option_name, $expected_value) {
        $actual_value = get_option($option_name);
        $this->assertEquals($expected_value, $actual_value, 
            "Option {$option_name} should have expected value");
    }

    /**
     * Assert that capability exists for role
     *
     * @param string $role_name Role name
     * @param string $capability Capability name
     * @since 1.0.0
     */
    protected function assertRoleHasCapability($role_name, $capability) {
        $role = get_role($role_name);
        $this->assertNotNull($role, "Role {$role_name} should exist");
        $this->assertTrue($role->has_cap($capability), 
            "Role {$role_name} should have capability {$capability}");
    }

    /**
     * Assert that audit log entry exists
     *
     * @param string $event_type Event type
     * @param int $user_id User ID
     * @since 1.0.0
     */
    protected function assertAuditLogExists($event_type, $user_id = null) {
        global $wpdb;
        
        $where_clause = $wpdb->prepare("event_type = %s", $event_type);
        if ($user_id !== null) {
            $where_clause .= $wpdb->prepare(" AND user_id = %d", $user_id);
        }
        
        $log_exists = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}eye_book_audit_log WHERE {$where_clause}"
        );
        
        $this->assertGreaterThan(0, $log_exists, 
            "Audit log entry should exist for event type: {$event_type}");
    }

    /**
     * Assert that data is properly encrypted
     *
     * @param string $original_data Original data
     * @param string $encrypted_data Encrypted data
     * @since 1.0.0
     */
    protected function assertDataIsEncrypted($original_data, $encrypted_data) {
        $this->assertNotEquals($original_data, $encrypted_data, 
            'Data should be encrypted and different from original');
        $this->assertGreaterThan(strlen($original_data), strlen($encrypted_data), 
            'Encrypted data should typically be longer than original');
    }

    /**
     * Assert that PHI data is properly handled
     *
     * @param array $data Data array
     * @since 1.0.0
     */
    protected function assertPHICompliance($data) {
        $phi_fields = array('first_name', 'last_name', 'email', 'phone', 'address', 'ssn');
        
        foreach ($phi_fields as $field) {
            if (isset($data[$field]) && !empty($data[$field])) {
                // PHI data should be encrypted or marked for encryption
                $this->assertTrue(
                    $this->is_encrypted_data($data[$field]) || 
                    $this->is_marked_for_encryption($field),
                    "PHI field {$field} should be encrypted or marked for encryption"
                );
            }
        }
    }

    /**
     * Check if data appears to be encrypted
     *
     * @param string $data Data to check
     * @return bool
     * @since 1.0.0
     */
    private function is_encrypted_data($data) {
        // Basic check - encrypted data is typically base64 encoded
        return base64_encode(base64_decode($data, true)) === $data;
    }

    /**
     * Check if field is marked for encryption
     *
     * @param string $field Field name
     * @return bool
     * @since 1.0.0
     */
    private function is_marked_for_encryption($field) {
        $encrypted_fields = get_option('eye_book_encrypted_fields', array());
        return in_array($field, $encrypted_fields);
    }

    /**
     * Simulate AJAX request
     *
     * @param string $action Action name
     * @param array $data Request data
     * @param bool $is_admin Whether this is an admin AJAX request
     * @return string Response output
     * @since 1.0.0
     */
    protected function simulate_ajax_request($action, $data = array(), $is_admin = true) {
        $_POST = array_merge($_POST, $data);
        $_REQUEST = array_merge($_REQUEST, $data);
        
        // Set up AJAX environment
        if (!defined('DOING_AJAX')) {
            define('DOING_AJAX', true);
        }
        
        ob_start();
        
        try {
            if ($is_admin) {
                do_action('wp_ajax_' . $action);
            } else {
                do_action('wp_ajax_nopriv_' . $action);
            }
        } catch (WPAjaxDieContinueException $e) {
            // Expected behavior for AJAX requests
        }
        
        $response = ob_get_clean();
        
        // Clean up
        $_POST = array();
        $_REQUEST = array();
        
        return $response;
    }

    /**
     * Assert AJAX response is successful
     *
     * @param string $response AJAX response
     * @since 1.0.0
     */
    protected function assertAjaxSuccess($response) {
        $data = json_decode($response, true);
        $this->assertTrue($data['success'], 'AJAX response should be successful');
    }

    /**
     * Assert AJAX response is error
     *
     * @param string $response AJAX response
     * @since 1.0.0
     */
    protected function assertAjaxError($response) {
        $data = json_decode($response, true);
        $this->assertFalse($data['success'], 'AJAX response should be error');
    }
}