<?php
/**
 * Eye-Book Appointment Model Tests
 *
 * @package EyeBook
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Eye_Book_Appointment_Test Class
 *
 * Tests for the Eye_Book_Appointment model class
 *
 * @class Eye_Book_Appointment_Test
 * @extends Eye_Book_Test_Case
 * @since 1.0.0
 */
class Eye_Book_Appointment_Test extends Eye_Book_Test_Case {

    /**
     * Test appointment creation
     *
     * @since 1.0.0
     */
    public function test_appointment_creation() {
        $patient_id = $this->create_test_patient();
        $provider_id = $this->create_test_provider();
        $location_id = $this->create_test_location();
        
        $appointment = new Eye_Book_Appointment();
        $appointment->patient_id = $patient_id;
        $appointment->provider_id = $provider_id;
        $appointment->location_id = $location_id;
        $appointment->appointment_type_id = 1;
        $appointment->start_datetime = date('Y-m-d H:i:s', strtotime('+1 day 10:00'));
        $appointment->end_datetime = date('Y-m-d H:i:s', strtotime('+1 day 10:30'));
        $appointment->status = 'scheduled';
        $appointment->chief_complaint = 'Annual eye exam';
        $appointment->booking_source = 'test';
        
        $appointment_id = $appointment->save();
        
        $this->assertGreaterThan(0, $appointment_id, 'Appointment should be created with valid ID');
        $this->assertNotEmpty($appointment->appointment_id, 'Appointment should have unique appointment_id');
        
        // Verify appointment can be retrieved
        $retrieved_appointment = new Eye_Book_Appointment($appointment_id);
        $this->assertEquals($patient_id, $retrieved_appointment->patient_id, 
            'Retrieved appointment should have correct patient_id');
        $this->assertEquals($provider_id, $retrieved_appointment->provider_id, 
            'Retrieved appointment should have correct provider_id');
    }

    /**
     * Test appointment validation
     *
     * @since 1.0.0
     */
    public function test_appointment_validation() {
        $appointment = new Eye_Book_Appointment();
        
        // Test validation with missing required fields
        $result = $appointment->save();
        $this->assertFalse($result, 'Appointment save should fail without required fields');
        
        // Test validation with invalid datetime
        $appointment->patient_id = $this->create_test_patient();
        $appointment->provider_id = $this->create_test_provider();
        $appointment->location_id = $this->create_test_location();
        $appointment->start_datetime = 'invalid-date';
        
        $result = $appointment->save();
        $this->assertFalse($result, 'Appointment save should fail with invalid datetime');
        
        // Test validation with end time before start time
        $appointment->start_datetime = date('Y-m-d H:i:s', strtotime('+1 day 10:00'));
        $appointment->end_datetime = date('Y-m-d H:i:s', strtotime('+1 day 09:30'));
        
        $result = $appointment->save();
        $this->assertFalse($result, 'Appointment save should fail when end time is before start time');
    }

    /**
     * Test appointment status changes
     *
     * @since 1.0.0
     */
    public function test_appointment_status_changes() {
        $appointment_id = $this->create_test_appointment(array(
            'status' => 'scheduled'
        ));
        
        $appointment = new Eye_Book_Appointment($appointment_id);
        
        // Test valid status change
        $appointment->status = 'confirmed';
        $result = $appointment->save();
        $this->assertTrue($result, 'Status change to confirmed should succeed');
        
        // Verify status was changed
        $updated_appointment = new Eye_Book_Appointment($appointment_id);
        $this->assertEquals('confirmed', $updated_appointment->status, 
            'Status should be updated to confirmed');
        
        // Test invalid status
        $appointment->status = 'invalid_status';
        $result = $appointment->save();
        $this->assertFalse($result, 'Invalid status should not be allowed');
    }

    /**
     * Test appointment conflict detection
     *
     * @since 1.0.0
     */
    public function test_appointment_conflicts() {
        $provider_id = $this->create_test_provider();
        $location_id = $this->create_test_location();
        
        // Create first appointment
        $start_time = date('Y-m-d H:i:s', strtotime('+1 day 10:00'));
        $end_time = date('Y-m-d H:i:s', strtotime('+1 day 10:30'));
        
        $appointment1_id = $this->create_test_appointment(array(
            'provider_id' => $provider_id,
            'location_id' => $location_id,
            'start_datetime' => $start_time,
            'end_datetime' => $end_time
        ));
        
        // Try to create overlapping appointment
        $appointment2 = new Eye_Book_Appointment();
        $appointment2->patient_id = $this->create_test_patient();
        $appointment2->provider_id = $provider_id;
        $appointment2->location_id = $location_id;
        $appointment2->start_datetime = date('Y-m-d H:i:s', strtotime('+1 day 10:15'));
        $appointment2->end_datetime = date('Y-m-d H:i:s', strtotime('+1 day 10:45'));
        
        // Check for conflicts
        if (method_exists($appointment2, 'check_conflicts')) {
            $conflicts = $appointment2->check_conflicts();
            $this->assertNotEmpty($conflicts, 'Should detect appointment conflict');
        }
        
        // Try to create non-overlapping appointment
        $appointment3 = new Eye_Book_Appointment();
        $appointment3->patient_id = $this->create_test_patient();
        $appointment3->provider_id = $provider_id;
        $appointment3->location_id = $location_id;
        $appointment3->start_datetime = date('Y-m-d H:i:s', strtotime('+1 day 11:00'));
        $appointment3->end_datetime = date('Y-m-d H:i:s', strtotime('+1 day 11:30'));
        
        $result = $appointment3->save();
        $this->assertNotFalse($result, 'Non-overlapping appointment should be created successfully');
    }

    /**
     * Test appointment search and filtering
     *
     * @since 1.0.0
     */
    public function test_appointment_search() {
        $provider1_id = $this->create_test_provider();
        $provider2_id = $this->create_test_provider();
        $location_id = $this->create_test_location();
        
        // Create multiple appointments
        $appointments = array();
        for ($i = 0; $i < 5; $i++) {
            $appointments[] = $this->create_test_appointment(array(
                'provider_id' => ($i % 2 === 0) ? $provider1_id : $provider2_id,
                'location_id' => $location_id,
                'start_datetime' => date('Y-m-d H:i:s', strtotime("+{$i} days 10:00")),
                'status' => ($i < 3) ? 'scheduled' : 'confirmed'
            ));
        }
        
        // Test search by provider
        if (method_exists('Eye_Book_Appointment', 'search')) {
            $provider1_appointments = Eye_Book_Appointment::search(array(
                'provider_id' => $provider1_id
            ));
            
            $this->assertEquals(3, count($provider1_appointments), 
                'Should find 3 appointments for provider 1');
            
            // Test search by status
            $scheduled_appointments = Eye_Book_Appointment::search(array(
                'status' => 'scheduled'
            ));
            
            $this->assertEquals(3, count($scheduled_appointments), 
                'Should find 3 scheduled appointments');
            
            // Test search by date range
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            $day_after = date('Y-m-d', strtotime('+2 days'));
            
            $date_range_appointments = Eye_Book_Appointment::search(array(
                'start_date' => $tomorrow,
                'end_date' => $day_after
            ));
            
            $this->assertGreaterThanOrEqual(1, count($date_range_appointments), 
                'Should find appointments in date range');
        }
    }

    /**
     * Test appointment duration calculation
     *
     * @since 1.0.0
     */
    public function test_appointment_duration() {
        $appointment_id = $this->create_test_appointment(array(
            'start_datetime' => '2024-01-15 10:00:00',
            'end_datetime' => '2024-01-15 10:30:00'
        ));
        
        $appointment = new Eye_Book_Appointment($appointment_id);
        
        if (method_exists($appointment, 'get_duration')) {
            $duration = $appointment->get_duration();
            $this->assertEquals(30, $duration, 'Duration should be 30 minutes');
        }
        
        if (method_exists($appointment, 'get_duration_formatted')) {
            $formatted_duration = $appointment->get_duration_formatted();
            $this->assertEquals('30 minutes', $formatted_duration, 
                'Formatted duration should be "30 minutes"');
        }
    }

    /**
     * Test appointment patient and provider relationships
     *
     * @since 1.0.0
     */
    public function test_appointment_relationships() {
        $patient_id = $this->create_test_patient(array(
            'first_name' => 'John',
            'last_name' => 'Doe'
        ));
        
        $provider_id = $this->create_test_provider(array(
            'first_name' => 'Dr. Jane',
            'last_name' => 'Smith'
        ));
        
        $appointment_id = $this->create_test_appointment(array(
            'patient_id' => $patient_id,
            'provider_id' => $provider_id
        ));
        
        $appointment = new Eye_Book_Appointment($appointment_id);
        
        // Test patient relationship
        if (method_exists($appointment, 'get_patient')) {
            $patient = $appointment->get_patient();
            $this->assertNotNull($patient, 'Should retrieve patient object');
            $this->assertEquals('John', $patient->first_name, 'Patient first name should match');
        }
        
        // Test provider relationship
        if (method_exists($appointment, 'get_provider')) {
            $provider = $appointment->get_provider();
            $this->assertNotNull($provider, 'Should retrieve provider object');
            $this->assertEquals('Dr. Jane', $provider->first_name, 'Provider first name should match');
        }
        
        // Test location relationship
        if (method_exists($appointment, 'get_location')) {
            $location = $appointment->get_location();
            $this->assertNotNull($location, 'Should retrieve location object');
        }
    }

    /**
     * Test appointment deletion
     *
     * @since 1.0.0
     */
    public function test_appointment_deletion() {
        $appointment_id = $this->create_test_appointment();
        
        $appointment = new Eye_Book_Appointment($appointment_id);
        $this->assertTrue($appointment->exists(), 'Appointment should exist before deletion');
        
        // Test soft delete (status change)
        if (method_exists($appointment, 'cancel')) {
            $result = $appointment->cancel('Patient requested cancellation');
            $this->assertTrue($result, 'Appointment cancellation should succeed');
            
            $updated_appointment = new Eye_Book_Appointment($appointment_id);
            $this->assertEquals('cancelled', $updated_appointment->status, 
                'Appointment status should be cancelled');
        }
        
        // Test hard delete
        if (method_exists($appointment, 'delete')) {
            $result = $appointment->delete();
            $this->assertTrue($result, 'Appointment deletion should succeed');
            
            $deleted_appointment = new Eye_Book_Appointment($appointment_id);
            $this->assertFalse($deleted_appointment->exists(), 'Appointment should not exist after deletion');
        }
    }

    /**
     * Test appointment audit logging
     *
     * @since 1.0.0
     */
    public function test_appointment_audit_logging() {
        // Create user for audit logging
        $user_id = $this->factory->user->create(array(
            'role' => 'administrator'
        ));
        wp_set_current_user($user_id);
        
        // Test appointment creation logging
        $appointment_id = $this->create_test_appointment();
        
        $this->assertAuditLogExists('appointment_created', $user_id);
        
        // Test appointment update logging
        $appointment = new Eye_Book_Appointment($appointment_id);
        $appointment->notes = 'Updated notes for testing';
        $appointment->save();
        
        $this->assertAuditLogExists('appointment_updated', $user_id);
    }

    /**
     * Test appointment data sanitization
     *
     * @since 1.0.0
     */
    public function test_appointment_data_sanitization() {
        $patient_id = $this->create_test_patient();
        $provider_id = $this->create_test_provider();
        $location_id = $this->create_test_location();
        
        $appointment = new Eye_Book_Appointment();
        $appointment->patient_id = $patient_id;
        $appointment->provider_id = $provider_id;
        $appointment->location_id = $location_id;
        $appointment->start_datetime = date('Y-m-d H:i:s', strtotime('+1 day 10:00'));
        $appointment->end_datetime = date('Y-m-d H:i:s', strtotime('+1 day 10:30'));
        
        // Test script injection prevention
        $appointment->chief_complaint = '<script>alert("xss")</script>Eye pain';
        $appointment->notes = '<script>alert("xss")</script>Patient notes';
        
        $appointment_id = $appointment->save();
        $this->assertGreaterThan(0, $appointment_id, 'Appointment should be saved');
        
        // Verify data is sanitized
        $retrieved_appointment = new Eye_Book_Appointment($appointment_id);
        $this->assertStringNotContainsString('<script>', $retrieved_appointment->chief_complaint, 
            'Script tags should be removed from chief complaint');
        $this->assertStringNotContainsString('<script>', $retrieved_appointment->notes, 
            'Script tags should be removed from notes');
    }

    /**
     * Test appointment reminders
     *
     * @since 1.0.0
     */
    public function test_appointment_reminders() {
        $appointment_id = $this->create_test_appointment(array(
            'start_datetime' => date('Y-m-d H:i:s', strtotime('+1 day 10:00'))
        ));
        
        $appointment = new Eye_Book_Appointment($appointment_id);
        
        // Test reminder scheduling
        if (method_exists($appointment, 'schedule_reminders')) {
            $result = $appointment->schedule_reminders();
            $this->assertTrue($result, 'Reminder scheduling should succeed');
        }
        
        // Test reminder status
        if (method_exists($appointment, 'get_reminder_status')) {
            $status = $appointment->get_reminder_status();
            $this->assertIsArray($status, 'Reminder status should be array');
        }
    }
}