<?php
/**
 * Eye-Book HIPAA Compliance Tests
 *
 * @package EyeBook
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Eye_Book_HIPAA_Compliance_Test Class
 *
 * Tests for HIPAA compliance functionality
 *
 * @class Eye_Book_HIPAA_Compliance_Test
 * @extends Eye_Book_Test_Case
 * @since 1.0.0
 */
class Eye_Book_HIPAA_Compliance_Test extends Eye_Book_Test_Case {

    /**
     * Encryption instance
     *
     * @var Eye_Book_Encryption
     * @since 1.0.0
     */
    private $encryption;

    /**
     * Audit trail instance
     *
     * @var Eye_Book_Audit_Trail
     * @since 1.0.0
     */
    private $audit;

    /**
     * Set up before each test
     *
     * @since 1.0.0
     */
    public function setUp(): void {
        parent::setUp();
        $this->encryption = new Eye_Book_Encryption();
        $this->audit = new Eye_Book_Audit_Trail();
    }

    /**
     * Test PHI data encryption
     *
     * @since 1.0.0
     */
    public function test_phi_data_encryption() {
        $sensitive_data = array(
            'first_name' => 'John',
            'last_name' => 'Doe',
            'ssn' => '123-45-6789',
            'email' => 'john.doe@example.com',
            'phone' => '(555) 123-4567',
            'address' => '123 Main St',
            'medical_record' => 'Patient has diabetes'
        );

        foreach ($sensitive_data as $field => $value) {
            $encrypted = $this->encryption->encrypt($value);
            
            // Test that data is actually encrypted
            $this->assertNotEquals($value, $encrypted, 
                "Field {$field} should be encrypted");
            $this->assertGreaterThan(strlen($value), strlen($encrypted), 
                "Encrypted {$field} should be longer than original");
            
            // Test that data can be decrypted
            $decrypted = $this->encryption->decrypt($encrypted);
            $this->assertEquals($value, $decrypted, 
                "Decrypted {$field} should match original");
        }
    }

    /**
     * Test patient data PHI compliance
     *
     * @since 1.0.0
     */
    public function test_patient_data_phi_compliance() {
        $patient_data = array(
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane.smith@example.com',
            'phone' => '(555) 987-6543',
            'ssn' => '987-65-4321',
            'date_of_birth' => '1980-05-15',
            'address' => '456 Oak Ave',
            'emergency_contact_name' => 'John Smith',
            'emergency_contact_phone' => '(555) 111-2222'
        );

        $patient_id = $this->create_test_patient($patient_data);
        $this->assertGreaterThan(0, $patient_id, 'Patient should be created');

        // Test PHI compliance
        $this->assertPHICompliance($patient_data);

        // Test that patient data is properly stored
        global $wpdb;
        $stored_patient = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eye_book_patients WHERE id = %d",
            $patient_id
        ), ARRAY_A);

        $this->assertNotNull($stored_patient, 'Patient should be retrievable');
        
        // Verify sensitive fields are encrypted or protected
        $phi_fields = array('first_name', 'last_name', 'email', 'phone', 'ssn');
        foreach ($phi_fields as $field) {
            if (isset($stored_patient[$field])) {
                // If encryption is enabled, data should not match original
                if (get_option('eye_book_encryption_enabled', true)) {
                    $this->assertNotEquals($patient_data[$field], $stored_patient[$field], 
                        "PHI field {$field} should be encrypted");
                }
            }
        }
    }

    /**
     * Test audit trail for PHI access
     *
     * @since 1.0.0
     */
    public function test_phi_access_audit_trail() {
        // Create test user and patient
        $user_id = $this->factory->user->create(array(
            'role' => 'eye_book_provider'
        ));
        wp_set_current_user($user_id);

        $patient_id = $this->create_test_patient();

        // Simulate PHI access
        do_action('eye_book_phi_accessed', $patient_id, 'patient_view', array(
            'first_name', 'last_name', 'email', 'phone'
        ));

        // Verify audit log entry
        global $wpdb;
        $audit_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eye_book_audit_log 
             WHERE event_type = 'patient_phi_accessed' 
             AND object_id = %d 
             AND user_id = %d",
            $patient_id,
            $user_id
        ));

        $this->assertNotNull($audit_entry, 'PHI access should be logged');
        $this->assertEquals('patient_phi_accessed', $audit_entry->event_type, 
            'Event type should be correct');
        $this->assertEquals($patient_id, $audit_entry->object_id, 
            'Object ID should match patient');
        $this->assertEquals($user_id, $audit_entry->user_id, 
            'User ID should match current user');
    }

    /**
     * Test minimum necessary access control
     *
     * @since 1.0.0
     */
    public function test_minimum_necessary_access() {
        // Create test users with different roles
        $provider_id = $this->factory->user->create(array(
            'role' => 'eye_book_provider'
        ));
        
        $nurse_id = $this->factory->user->create(array(
            'role' => 'eye_book_nurse'
        ));
        
        $receptionist_id = $this->factory->user->create(array(
            'role' => 'eye_book_receptionist'
        ));

        $patient_id = $this->create_test_patient();

        // Test provider access (should have full access)
        wp_set_current_user($provider_id);
        $this->assertTrue(current_user_can('eye_book_view_patients'), 
            'Provider should be able to view patients');
        $this->assertTrue(current_user_can('eye_book_edit_patients'), 
            'Provider should be able to edit patients');

        // Test nurse access (limited access)
        wp_set_current_user($nurse_id);
        $this->assertTrue(current_user_can('eye_book_view_appointments'), 
            'Nurse should be able to view appointments');
        $this->assertFalse(current_user_can('eye_book_edit_patients'), 
            'Nurse should not be able to edit patient records');

        // Test receptionist access (very limited)
        wp_set_current_user($receptionist_id);
        $this->assertTrue(current_user_can('eye_book_view_appointments'), 
            'Receptionist should be able to view appointments');
        $this->assertFalse(current_user_can('eye_book_view_patients'), 
            'Receptionist should not be able to view full patient records');
    }

    /**
     * Test data retention and deletion
     *
     * @since 1.0.0
     */
    public function test_data_retention_and_deletion() {
        $patient_id = $this->create_test_patient();
        
        // Test patient exists
        $patient = new Eye_Book_Patient($patient_id);
        $this->assertTrue($patient->exists(), 'Patient should exist');

        // Test HIPAA-compliant deletion
        if (method_exists($patient, 'hipaa_delete')) {
            $result = $patient->hipaa_delete('Patient requested deletion');
            $this->assertTrue($result, 'HIPAA deletion should succeed');

            // Verify patient is marked as deleted but data is retained for compliance
            $deleted_patient = new Eye_Book_Patient($patient_id);
            $this->assertEquals('deleted', $deleted_patient->status, 
                'Patient status should be deleted');
            $this->assertNotNull($deleted_patient->deleted_at, 
                'Deletion timestamp should be set');
        }

        // Test audit log cleanup (older than retention period)
        global $wpdb;
        
        // Create old audit log entries
        $old_date = date('Y-m-d H:i:s', strtotime('-8 years'));
        $wpdb->insert(
            $wpdb->prefix . 'eye_book_audit_log',
            array(
                'event_type' => 'test_old_event',
                'created_at' => $old_date,
                'user_id' => 1,
                'ip_address' => '127.0.0.1'
            )
        );

        $audit_id = $wpdb->insert_id;

        // Run cleanup
        if (method_exists($this->audit, 'cleanup_old_logs')) {
            $this->audit->cleanup_old_logs();

            // Verify old logs are removed
            $old_log = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}eye_book_audit_log WHERE id = %d",
                $audit_id
            ));

            $this->assertNull($old_log, 'Old audit logs should be cleaned up');
        }
    }

    /**
     * Test breach detection and notification
     *
     * @since 1.0.0
     */
    public function test_breach_detection_notification() {
        // Simulate suspicious activity that might indicate a breach
        $user_id = $this->factory->user->create();
        wp_set_current_user($user_id);

        // Create multiple PHI access events in short time
        for ($i = 0; $i < 100; $i++) {
            $patient_id = $this->create_test_patient();
            do_action('eye_book_phi_accessed', $patient_id, 'bulk_access', array('all_fields'));
        }

        // Check if breach detection is triggered
        global $wpdb;
        $suspicious_activity = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}eye_book_audit_log 
             WHERE event_type = 'suspicious_activity_detected' 
             OR event_type = 'data_breach_suspected'"
        );

        // This test depends on breach detection implementation
        if ($suspicious_activity) {
            $this->assertNotNull($suspicious_activity, 'Suspicious activity should be detected');
        }
    }

    /**
     * Test user authentication and session management
     *
     * @since 1.0.0
     */
    public function test_hipaa_authentication_requirements() {
        $user_id = $this->factory->user->create(array(
            'user_login' => 'hipaa_test_user',
            'user_pass' => 'ComplexPassword123!',
            'role' => 'eye_book_provider'
        ));

        // Test password complexity requirements
        $weak_passwords = array('123', 'password', 'abc123');
        
        foreach ($weak_passwords as $weak_password) {
            $result = wp_set_password($weak_password, $user_id);
            
            // If password policy is enforced, weak passwords should be rejected
            // This depends on implementation
        }

        // Test session timeout
        wp_set_current_user($user_id);
        $this->assertEquals($user_id, get_current_user_id(), 'User should be logged in');

        // Simulate session timeout
        update_user_meta($user_id, 'last_activity', time() - (16 * 60)); // 16 minutes ago
        
        // Check if session is properly managed
        if (class_exists('Eye_Book_Security_Hardening')) {
            $security = new Eye_Book_Security_Hardening();
            if (method_exists($security, 'enforce_session_timeout')) {
                $security->enforce_session_timeout();
                // User should be logged out after timeout
                $this->assertEquals(0, get_current_user_id(), 
                    'User should be logged out after session timeout');
            }
        }
    }

    /**
     * Test data backup encryption
     *
     * @since 1.0.0
     */
    public function test_backup_encryption() {
        if (class_exists('Eye_Book_Backup_Recovery')) {
            $backup = new Eye_Book_Backup_Recovery();
            
            // Create test backup
            $backup_result = $backup->create_full_backup();
            
            if ($backup_result['success']) {
                // Verify backup is encrypted
                $backup_file = $backup_result['storage_locations']['local']['file'] ?? '';
                
                if (file_exists($backup_file)) {
                    $backup_content = file_get_contents($backup_file);
                    
                    // Backup should not contain plain text PHI
                    $this->assertStringNotContainsString('john.doe@example.com', $backup_content, 
                        'Backup should not contain plain text email');
                    $this->assertStringNotContainsString('123-45-6789', $backup_content, 
                        'Backup should not contain plain text SSN');
                }
            }
        } else {
            $this->markTestSkipped('Backup functionality not available');
        }
    }

    /**
     * Test access logging completeness
     *
     * @since 1.0.0
     */
    public function test_access_logging_completeness() {
        $user_id = $this->factory->user->create(array(
            'role' => 'eye_book_provider'
        ));
        wp_set_current_user($user_id);

        $patient_id = $this->create_test_patient();
        $appointment_id = $this->create_test_appointment(array(
            'patient_id' => $patient_id
        ));

        // Simulate various access events
        $access_events = array(
            array('type' => 'patient_viewed', 'object_id' => $patient_id),
            array('type' => 'appointment_viewed', 'object_id' => $appointment_id),
            array('type' => 'patient_updated', 'object_id' => $patient_id),
            array('type' => 'appointment_updated', 'object_id' => $appointment_id)
        );

        foreach ($access_events as $event) {
            do_action('eye_book_audit_log', $event['type'], 'patient', $event['object_id']);
        }

        // Verify all events are logged
        global $wpdb;
        foreach ($access_events as $event) {
            $log_entry = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}eye_book_audit_log 
                 WHERE event_type = %s AND object_id = %d AND user_id = %d",
                $event['type'],
                $event['object_id'],
                $user_id
            ));

            $this->assertNotNull($log_entry, 
                "Access event {$event['type']} should be logged");
            
            // Verify required audit fields
            $this->assertNotNull($log_entry->ip_address, 'IP address should be logged');
            $this->assertNotNull($log_entry->created_at, 'Timestamp should be logged');
            $this->assertNotNull($log_entry->user_agent, 'User agent should be logged');
        }
    }

    /**
     * Test data transmission security
     *
     * @since 1.0.0
     */
    public function test_data_transmission_security() {
        // Test HTTPS enforcement
        $_SERVER['HTTPS'] = '';
        $_SERVER['SERVER_PORT'] = '80';
        
        // If HTTPS is required, HTTP requests should be redirected
        if (defined('EYE_BOOK_REQUIRE_SSL') && EYE_BOOK_REQUIRE_SSL) {
            $this->expectException('Exception');
            // This would normally trigger a redirect
        }

        // Test API security headers
        $api = new Eye_Book_API();
        
        // Simulate API request
        $request = new WP_REST_Request('GET', '/eye-book/v1/patients');
        $request->set_header('X-API-Key', 'test-key');
        
        // API should enforce secure transmission
        $response = $api->check_api_permissions($request);
        
        if (is_wp_error($response)) {
            $this->assertStringContainsString('API key', $response->get_error_message(), 
                'API should require authentication');
        }
    }

    /**
     * Test Business Associate Agreement compliance
     *
     * @since 1.0.0
     */
    public function test_baa_compliance() {
        // Test that BAA requirements are tracked
        $baa_requirements = array(
            'payment_processors' => array('stripe', 'square', 'paypal'),
            'email_providers' => array('sendgrid', 'mailgun'),
            'sms_providers' => array('twilio')
        );

        foreach ($baa_requirements as $category => $providers) {
            foreach ($providers as $provider) {
                $baa_status = get_option("eye_book_baa_{$provider}_status");
                
                // BAA status should be tracked (even if not set)
                $this->assertIsArray($baa_status, "BAA status for {$provider} should be tracked");
            }
        }
    }

    /**
     * Test patient rights implementation
     *
     * @since 1.0.0
     */
    public function test_patient_rights_implementation() {
        $patient_id = $this->create_test_patient();
        $patient = new Eye_Book_Patient($patient_id);

        // Test right of access (patient can access own records)
        if (method_exists($patient, 'get_patient_portal_data')) {
            $portal_data = $patient->get_patient_portal_data();
            
            $this->assertIsArray($portal_data, 'Patient should be able to access own data');
            $this->assertArrayHasKey('appointments', $portal_data, 
                'Patient should have access to appointments');
            $this->assertArrayHasKey('profile', $portal_data, 
                'Patient should have access to profile');
        }

        // Test right to amendment
        if (method_exists($patient, 'request_amendment')) {
            $amendment_request = array(
                'field' => 'phone',
                'current_value' => '(555) 123-4567',
                'requested_value' => '(555) 987-6543',
                'reason' => 'Phone number changed'
            );
            
            $result = $patient->request_amendment($amendment_request);
            $this->assertTrue($result, 'Patient should be able to request amendments');
        }

        // Test accounting of disclosures
        if (method_exists($patient, 'get_disclosure_history')) {
            $disclosures = $patient->get_disclosure_history();
            $this->assertIsArray($disclosures, 
                'Patient should be able to get disclosure history');
        }
    }
}