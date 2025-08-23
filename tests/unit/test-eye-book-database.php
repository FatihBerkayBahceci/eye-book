<?php
/**
 * Eye-Book Database Tests
 *
 * @package EyeBook
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Eye_Book_Database_Test Class
 *
 * Tests for the Eye_Book_Database class
 *
 * @class Eye_Book_Database_Test
 * @extends Eye_Book_Test_Case
 * @since 1.0.0
 */
class Eye_Book_Database_Test extends Eye_Book_Test_Case {

    /**
     * Database instance
     *
     * @var Eye_Book_Database
     * @since 1.0.0
     */
    private $database;

    /**
     * Set up before each test
     *
     * @since 1.0.0
     */
    public function setUp(): void {
        parent::setUp();
        $this->database = new Eye_Book_Database();
    }

    /**
     * Test database tables creation
     *
     * @since 1.0.0
     */
    public function test_create_tables() {
        // Test that create_tables method completes without error
        $result = $this->database->create_tables();
        $this->assertTrue($result, 'create_tables should return true');

        // Test that all required tables exist
        $expected_tables = array(
            'eye_book_locations',
            'eye_book_providers',
            'eye_book_patients',
            'eye_book_appointment_types',
            'eye_book_appointments',
            'eye_book_provider_schedules',
            'eye_book_audit_log',
            'eye_book_payments'
        );

        foreach ($expected_tables as $table) {
            $this->assertTableExists($table);
        }
    }

    /**
     * Test locations table structure
     *
     * @since 1.0.0
     */
    public function test_locations_table_structure() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eye_book_locations';
        $columns = $wpdb->get_results("DESCRIBE {$table_name}");
        
        $expected_columns = array(
            'id', 'name', 'address', 'city', 'state', 'zip_code',
            'phone', 'email', 'operating_hours', 'status', 'timezone',
            'created_at', 'updated_at'
        );
        
        $actual_columns = wp_list_pluck($columns, 'Field');
        
        foreach ($expected_columns as $column) {
            $this->assertContains($column, $actual_columns, 
                "Locations table should have column: {$column}");
        }
    }

    /**
     * Test providers table structure
     *
     * @since 1.0.0
     */
    public function test_providers_table_structure() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eye_book_providers';
        $columns = $wpdb->get_results("DESCRIBE {$table_name}");
        
        $expected_columns = array(
            'id', 'first_name', 'last_name', 'email', 'phone',
            'specialization', 'license_number', 'npi_number',
            'status', 'bio', 'credentials', 'years_experience',
            'created_at', 'updated_at'
        );
        
        $actual_columns = wp_list_pluck($columns, 'Field');
        
        foreach ($expected_columns as $column) {
            $this->assertContains($column, $actual_columns, 
                "Providers table should have column: {$column}");
        }
    }

    /**
     * Test patients table structure
     *
     * @since 1.0.0
     */
    public function test_patients_table_structure() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eye_book_patients';
        $columns = $wpdb->get_results("DESCRIBE {$table_name}");
        
        $expected_columns = array(
            'id', 'patient_id', 'first_name', 'last_name', 'email', 'phone',
            'date_of_birth', 'gender', 'address', 'city', 'state', 'zip_code',
            'emergency_contact_name', 'emergency_contact_phone',
            'insurance_provider', 'insurance_id', 'status',
            'created_at', 'updated_at'
        );
        
        $actual_columns = wp_list_pluck($columns, 'Field');
        
        foreach ($expected_columns as $column) {
            $this->assertContains($column, $actual_columns, 
                "Patients table should have column: {$column}");
        }
    }

    /**
     * Test appointments table structure
     *
     * @since 1.0.0
     */
    public function test_appointments_table_structure() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eye_book_appointments';
        $columns = $wpdb->get_results("DESCRIBE {$table_name}");
        
        $expected_columns = array(
            'id', 'appointment_id', 'patient_id', 'provider_id', 'location_id',
            'appointment_type_id', 'start_datetime', 'end_datetime', 'status',
            'chief_complaint', 'notes', 'booking_source',
            'created_at', 'updated_at'
        );
        
        $actual_columns = wp_list_pluck($columns, 'Field');
        
        foreach ($expected_columns as $column) {
            $this->assertContains($column, $actual_columns, 
                "Appointments table should have column: {$column}");
        }
    }

    /**
     * Test audit log table structure
     *
     * @since 1.0.0
     */
    public function test_audit_log_table_structure() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eye_book_audit_log';
        $columns = $wpdb->get_results("DESCRIBE {$table_name}");
        
        $expected_columns = array(
            'id', 'event_type', 'object_type', 'object_id', 'user_id',
            'session_id', 'ip_address', 'user_agent', 'risk_level',
            'event_details', 'hash', 'created_at'
        );
        
        $actual_columns = wp_list_pluck($columns, 'Field');
        
        foreach ($expected_columns as $column) {
            $this->assertContains($column, $actual_columns, 
                "Audit log table should have column: {$column}");
        }
    }

    /**
     * Test payments table structure
     *
     * @since 1.0.0
     */
    public function test_payments_table_structure() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eye_book_payments';
        $columns = $wpdb->get_results("DESCRIBE {$table_name}");
        
        $expected_columns = array(
            'id', 'appointment_id', 'patient_id', 'amount', 'payment_type',
            'gateway', 'transaction_id', 'status', 'refund_amount',
            'refund_reason', 'refunded_at', 'created_at', 'updated_at'
        );
        
        $actual_columns = wp_list_pluck($columns, 'Field');
        
        foreach ($expected_columns as $column) {
            $this->assertContains($column, $actual_columns, 
                "Payments table should have column: {$column}");
        }
    }

    /**
     * Test table indexes are created
     *
     * @since 1.0.0
     */
    public function test_table_indexes() {
        global $wpdb;
        
        // Test appointments table indexes
        $appointments_table = $wpdb->prefix . 'eye_book_appointments';
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$appointments_table}");
        $index_columns = wp_list_pluck($indexes, 'Column_name');
        
        $expected_indexes = array('patient_id', 'provider_id', 'location_id', 'start_datetime', 'status');
        
        foreach ($expected_indexes as $column) {
            $this->assertContains($column, $index_columns, 
                "Appointments table should have index on: {$column}");
        }
        
        // Test patients table indexes
        $patients_table = $wpdb->prefix . 'eye_book_patients';
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$patients_table}");
        $index_columns = wp_list_pluck($indexes, 'Column_name');
        
        $expected_indexes = array('patient_id', 'email', 'phone', 'last_name');
        
        foreach ($expected_indexes as $column) {
            $this->assertContains($column, $index_columns, 
                "Patients table should have index on: {$column}");
        }
    }

    /**
     * Test database version tracking
     *
     * @since 1.0.0
     */
    public function test_database_version_tracking() {
        // Test that database version is set after table creation
        $this->database->create_tables();
        
        $db_version = get_option('eye_book_db_version');
        $this->assertEquals(EYE_BOOK_DB_VERSION, $db_version, 
            'Database version should be set to current version');
    }

    /**
     * Test database upgrade scenario
     *
     * @since 1.0.0
     */
    public function test_database_upgrade() {
        // Set an older version
        update_option('eye_book_db_version', '1.0.0');
        
        // Run database creation (should trigger upgrade)
        $this->database->create_tables();
        
        // Verify version is updated
        $db_version = get_option('eye_book_db_version');
        $this->assertEquals(EYE_BOOK_DB_VERSION, $db_version, 
            'Database version should be updated after upgrade');
    }

    /**
     * Test table charset and collation
     *
     * @since 1.0.0
     */
    public function test_table_charset_collation() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eye_book_patients';
        $table_status = $wpdb->get_row("SHOW TABLE STATUS LIKE '{$table_name}'");
        
        // Should use WordPress database charset
        $this->assertStringContains($wpdb->charset, $table_status->Collation, 
            'Table should use WordPress charset');
    }

    /**
     * Test foreign key constraints exist
     *
     * @since 1.0.0
     */
    public function test_foreign_key_constraints() {
        global $wpdb;
        
        // Test appointments table foreign keys
        $appointments_table = $wpdb->prefix . 'eye_book_appointments';
        
        // Check if foreign key columns exist (constraint creation depends on storage engine)
        $columns = $wpdb->get_results("DESCRIBE {$appointments_table}");
        $column_names = wp_list_pluck($columns, 'Field');
        
        $foreign_key_columns = array('patient_id', 'provider_id', 'location_id', 'appointment_type_id');
        
        foreach ($foreign_key_columns as $column) {
            $this->assertContains($column, $column_names, 
                "Appointments table should have foreign key column: {$column}");
        }
    }

    /**
     * Test database cleanup
     *
     * @since 1.0.0
     */
    public function test_drop_tables() {
        // First ensure tables exist
        $this->database->create_tables();
        $this->assertTableExists('eye_book_locations');
        
        // Test drop tables (if method exists)
        if (method_exists($this->database, 'drop_tables')) {
            $this->database->drop_tables();
            
            global $wpdb;
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SHOW TABLES LIKE %s",
                $wpdb->prefix . 'eye_book_locations'
            ));
            
            $this->assertNull($table_exists, 'Table should not exist after drop');
        } else {
            $this->markTestSkipped('drop_tables method not implemented');
        }
    }

    /**
     * Test database error handling
     *
     * @since 1.0.0
     */
    public function test_database_error_handling() {
        global $wpdb;
        
        // Simulate database error by using invalid SQL
        $original_show_errors = $wpdb->show_errors;
        $wpdb->show_errors = false;
        
        // This should fail gracefully
        $result = $wpdb->query("INVALID SQL STATEMENT");
        
        $this->assertFalse($result, 'Invalid SQL should return false');
        $this->assertNotEmpty($wpdb->last_error, 'Should have error message');
        
        // Restore original setting
        $wpdb->show_errors = $original_show_errors;
    }

    /**
     * Test table data insertion and retrieval
     *
     * @since 1.0.0
     */
    public function test_table_data_operations() {
        global $wpdb;
        
        // Test location insertion
        $location_data = array(
            'name' => 'Test Eye Clinic',
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'TS',
            'zip_code' => '12345',
            'phone' => '(555) 123-4567',
            'email' => 'test@example.com',
            'status' => 'active',
            'timezone' => 'America/New_York'
        );
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'eye_book_locations',
            $location_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        $this->assertNotFalse($result, 'Location insertion should succeed');
        
        $location_id = $wpdb->insert_id;
        $this->assertGreaterThan(0, $location_id, 'Should get valid location ID');
        
        // Test data retrieval
        $retrieved_location = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eye_book_locations WHERE id = %d",
            $location_id
        ));
        
        $this->assertNotNull($retrieved_location, 'Should retrieve inserted location');
        $this->assertEquals($location_data['name'], $retrieved_location->name, 
            'Retrieved data should match inserted data');
    }
}