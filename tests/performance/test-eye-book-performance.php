<?php
/**
 * Eye-Book Performance Tests
 *
 * @package EyeBook
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Eye_Book_Performance_Test Class
 *
 * Tests for performance optimization and load handling
 *
 * @class Eye_Book_Performance_Test
 * @extends Eye_Book_Test_Case
 * @since 1.0.0
 */
class Eye_Book_Performance_Test extends Eye_Book_Test_Case {

    /**
     * Maximum acceptable query execution time (seconds)
     */
    const MAX_QUERY_TIME = 0.1;

    /**
     * Maximum acceptable page load time (seconds)
     */
    const MAX_PAGE_LOAD_TIME = 2.0;

    /**
     * Test database query performance
     *
     * @since 1.0.0
     */
    public function test_database_query_performance() {
        // Create test data
        $location_id = $this->create_test_location();
        $provider_id = $this->create_test_provider();
        
        $patient_ids = array();
        $appointment_ids = array();
        
        // Create 1000 test patients and appointments
        for ($i = 0; $i < 1000; $i++) {
            $patient_ids[] = $this->create_test_patient();
            
            if ($i < 500) { // Create 500 appointments
                $appointment_ids[] = $this->create_test_appointment(array(
                    'patient_id' => $patient_ids[$i],
                    'provider_id' => $provider_id,
                    'location_id' => $location_id,
                    'start_datetime' => date('Y-m-d H:i:s', strtotime("+{$i} hours"))
                ));
            }
        }

        global $wpdb;

        // Test appointment search performance
        $start_time = microtime(true);
        $appointments = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, 
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    CONCAT(pr.first_name, ' ', pr.last_name) as provider_name
             FROM {$wpdb->prefix}eye_book_appointments a
             LEFT JOIN {$wpdb->prefix}eye_book_patients p ON a.patient_id = p.id
             LEFT JOIN {$wpdb->prefix}eye_book_providers pr ON a.provider_id = pr.id
             WHERE a.provider_id = %d
             ORDER BY a.start_datetime DESC
             LIMIT 50",
            $provider_id
        ));
        $query_time = microtime(true) - $start_time;

        $this->assertLessThan(self::MAX_QUERY_TIME, $query_time, 
            'Appointment search query should complete within ' . self::MAX_QUERY_TIME . ' seconds');
        $this->assertGreaterThan(0, count($appointments), 
            'Query should return appointments');

        // Test patient search performance
        $start_time = microtime(true);
        $patients = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}eye_book_patients 
             WHERE status = 'active' 
             ORDER BY last_name, first_name 
             LIMIT 50"
        );
        $query_time = microtime(true) - $start_time;

        $this->assertLessThan(self::MAX_QUERY_TIME, $query_time, 
            'Patient search query should complete within ' . self::MAX_QUERY_TIME . ' seconds');

        // Test appointment availability query performance
        $start_time = microtime(true);
        $availability = $wpdb->get_results($wpdb->prepare(
            "SELECT start_datetime, end_datetime 
             FROM {$wpdb->prefix}eye_book_appointments 
             WHERE provider_id = %d 
             AND DATE(start_datetime) = %s
             AND status IN ('scheduled', 'confirmed')",
            $provider_id,
            date('Y-m-d', strtotime('+30 days'))
        ));
        $query_time = microtime(true) - $start_time;

        $this->assertLessThan(self::MAX_QUERY_TIME, $query_time, 
            'Availability query should complete within ' . self::MAX_QUERY_TIME . ' seconds');
    }

    /**
     * Test calendar loading performance
     *
     * @since 1.0.0
     */
    public function test_calendar_performance() {
        $provider_id = $this->create_test_provider();
        $location_id = $this->create_test_location();

        // Create appointments for a month
        for ($i = 0; $i < 30; $i++) {
            for ($j = 0; $j < 10; $j++) { // 10 appointments per day
                $this->create_test_appointment(array(
                    'provider_id' => $provider_id,
                    'location_id' => $location_id,
                    'start_datetime' => date('Y-m-d H:i:s', strtotime("+{$i} days +{$j} hours")),
                    'end_datetime' => date('Y-m-d H:i:s', strtotime("+{$i} days +{$j} hours +30 minutes"))
                ));
            }
        }

        // Test calendar data loading
        $start_time = microtime(true);
        
        $calendar_data = $this->get_calendar_data($provider_id, date('Y-m-01'), date('Y-m-t'));
        
        $load_time = microtime(true) - $start_time;

        $this->assertLessThan(0.5, $load_time, 
            'Calendar data should load within 0.5 seconds');
        $this->assertIsArray($calendar_data, 
            'Calendar should return data array');
        $this->assertGreaterThan(0, count($calendar_data), 
            'Calendar should contain appointments');
    }

    /**
     * Test search functionality performance
     *
     * @since 1.0.0
     */
    public function test_search_performance() {
        // Create diverse test data
        $test_patients = array();
        $search_terms = array('Smith', 'Johnson', 'Williams', 'Brown', 'Jones');
        
        for ($i = 0; $i < 500; $i++) {
            $last_name = $search_terms[$i % count($search_terms)];
            $test_patients[] = $this->create_test_patient(array(
                'first_name' => 'Patient' . $i,
                'last_name' => $last_name,
                'email' => "patient{$i}@example.com"
            ));
        }

        global $wpdb;

        // Test patient name search
        $start_time = microtime(true);
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eye_book_patients 
             WHERE (first_name LIKE %s OR last_name LIKE %s)
             ORDER BY last_name, first_name
             LIMIT 20",
            '%Smith%',
            '%Smith%'
        ));
        $search_time = microtime(true) - $start_time;

        $this->assertLessThan(0.05, $search_time, 
            'Patient search should complete within 0.05 seconds');
        $this->assertGreaterThan(0, count($results), 
            'Search should return results');

        // Test email search
        $start_time = microtime(true);
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eye_book_patients 
             WHERE email LIKE %s
             LIMIT 20",
            '%patient1%'
        ));
        $search_time = microtime(true) - $start_time;

        $this->assertLessThan(0.05, $search_time, 
            'Email search should complete within 0.05 seconds');
    }

    /**
     * Test audit log performance
     *
     * @since 1.0.0
     */
    public function test_audit_log_performance() {
        $user_id = $this->factory->user->create();
        wp_set_current_user($user_id);

        // Create many audit log entries
        global $wpdb;
        $audit_table = $wpdb->prefix . 'eye_book_audit_log';
        
        $start_time = microtime(true);
        
        for ($i = 0; $i < 100; $i++) {
            $wpdb->insert($audit_table, array(
                'event_type' => 'test_performance_event',
                'object_type' => 'test',
                'object_id' => $i,
                'user_id' => $user_id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'PHPUnit Test',
                'risk_level' => 'low',
                'event_details' => json_encode(array('test' => 'data')),
                'hash' => hash('sha256', 'test' . $i),
                'created_at' => current_time('mysql', true)
            ));
        }
        
        $insert_time = microtime(true) - $start_time;

        $this->assertLessThan(1.0, $insert_time, 
            'Audit log bulk insert should complete within 1 second');

        // Test audit log retrieval performance
        $start_time = microtime(true);
        
        $logs = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $audit_table 
             WHERE user_id = %d 
             ORDER BY created_at DESC 
             LIMIT 50",
            $user_id
        ));
        
        $query_time = microtime(true) - $start_time;

        $this->assertLessThan(0.05, $query_time, 
            'Audit log retrieval should complete within 0.05 seconds');
        $this->assertGreaterThan(0, count($logs), 
            'Should retrieve audit logs');
    }

    /**
     * Test API endpoint performance
     *
     * @since 1.0.0
     */
    public function test_api_performance() {
        if (!class_exists('Eye_Book_API')) {
            $this->markTestSkipped('API class not available');
            return;
        }

        // Create test data
        $provider_id = $this->create_test_provider();
        $location_id = $this->create_test_location();
        
        for ($i = 0; $i < 50; $i++) {
            $this->create_test_appointment(array(
                'provider_id' => $provider_id,
                'location_id' => $location_id
            ));
        }

        $api = new Eye_Book_API();
        
        // Mock API request
        $request = new WP_REST_Request('GET', '/eye-book/v1/appointments');
        $request->set_query_params(array(
            'provider_id' => $provider_id,
            'per_page' => 20
        ));

        // Test API response time
        $start_time = microtime(true);
        
        // Note: This would normally require proper API authentication
        // For testing, we'll mock the permission check
        $response = $api->get_appointments($request);
        
        $response_time = microtime(true) - $start_time;

        $this->assertLessThan(0.2, $response_time, 
            'API endpoint should respond within 0.2 seconds');
    }

    /**
     * Test memory usage
     *
     * @since 1.0.0
     */
    public function test_memory_usage() {
        $initial_memory = memory_get_usage();

        // Create substantial test data
        $data = array();
        for ($i = 0; $i < 1000; $i++) {
            $data[] = $this->create_test_patient();
        }

        $peak_memory = memory_get_peak_usage();
        $memory_used = $peak_memory - $initial_memory;

        // Should not use more than 50MB for 1000 patients
        $max_memory = 50 * 1024 * 1024; // 50MB
        
        $this->assertLessThan($max_memory, $memory_used, 
            'Memory usage should be under 50MB for 1000 patients');

        // Test memory cleanup
        unset($data);
        
        $final_memory = memory_get_usage();
        $this->assertLessThan($peak_memory, $final_memory, 
            'Memory should be cleaned up after unsetting variables');
    }

    /**
     * Test caching effectiveness
     *
     * @since 1.0.0
     */
    public function test_caching_performance() {
        $provider_id = $this->create_test_provider();
        
        // Test without cache (first load)
        delete_transient('eye_book_provider_' . $provider_id);
        
        $start_time = microtime(true);
        $provider_data = $this->get_provider_data($provider_id);
        $first_load_time = microtime(true) - $start_time;

        // Test with cache (second load)
        $start_time = microtime(true);
        $cached_provider_data = $this->get_provider_data($provider_id);
        $cached_load_time = microtime(true) - $start_time;

        $this->assertLessThan($first_load_time, $cached_load_time, 
            'Cached data should load faster than uncached');
        
        $this->assertEquals($provider_data, $cached_provider_data, 
            'Cached data should match original data');
    }

    /**
     * Test concurrent user simulation
     *
     * @since 1.0.0
     */
    public function test_concurrent_access_simulation() {
        // Create test data
        $provider_id = $this->create_test_provider();
        $location_id = $this->create_test_location();
        
        // Simulate multiple users trying to book the same time slot
        $target_datetime = date('Y-m-d H:i:s', strtotime('+1 day 10:00'));
        
        $booking_attempts = 0;
        $successful_bookings = 0;
        $failed_bookings = 0;

        for ($i = 0; $i < 10; $i++) {
            $patient_id = $this->create_test_patient();
            
            try {
                $appointment_id = $this->create_test_appointment(array(
                    'patient_id' => $patient_id,
                    'provider_id' => $provider_id,
                    'location_id' => $location_id,
                    'start_datetime' => $target_datetime,
                    'end_datetime' => date('Y-m-d H:i:s', strtotime('+1 day 10:30'))
                ));
                
                $booking_attempts++;
                
                if ($appointment_id > 0) {
                    $successful_bookings++;
                } else {
                    $failed_bookings++;
                }
                
            } catch (Exception $e) {
                $booking_attempts++;
                $failed_bookings++;
            }
        }

        // Only one booking should succeed for the same time slot
        $this->assertEquals(1, $successful_bookings, 
            'Only one appointment should be successful for the same time slot');
        $this->assertEquals(9, $failed_bookings, 
            '9 appointments should fail due to conflicts');
    }

    /**
     * Test large dataset handling
     *
     * @since 1.0.0
     */
    public function test_large_dataset_performance() {
        // Create a large number of records
        $start_time = microtime(true);
        
        $patient_ids = array();
        for ($i = 0; $i < 5000; $i++) {
            $patient_ids[] = $this->create_test_patient();
        }
        
        $creation_time = microtime(true) - $start_time;
        
        $this->assertLessThan(30, $creation_time, 
            'Creating 5000 patients should take less than 30 seconds');

        // Test pagination performance with large dataset
        global $wpdb;
        
        $start_time = microtime(true);
        $page1_results = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}eye_book_patients 
             ORDER BY id 
             LIMIT 50 OFFSET 0"
        );
        $page1_time = microtime(true) - $start_time;

        $start_time = microtime(true);
        $page100_results = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}eye_book_patients 
             ORDER BY id 
             LIMIT 50 OFFSET 4950"
        );
        $page100_time = microtime(true) - $start_time;

        $this->assertLessThan(0.1, $page1_time, 
            'First page should load quickly');
        $this->assertLessThan(0.2, $page100_time, 
            'Deep pagination should still perform reasonably');
        
        $this->assertEquals(50, count($page1_results), 
            'First page should return correct number of results');
        $this->assertEquals(50, count($page100_results), 
            'Deep page should return correct number of results');
    }

    /**
     * Helper method to get calendar data
     *
     * @param int $provider_id
     * @param string $start_date
     * @param string $end_date
     * @return array
     */
    private function get_calendar_data($provider_id, $start_date, $end_date) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, 
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name
             FROM {$wpdb->prefix}eye_book_appointments a
             LEFT JOIN {$wpdb->prefix}eye_book_patients p ON a.patient_id = p.id
             WHERE a.provider_id = %d 
             AND DATE(a.start_datetime) BETWEEN %s AND %s
             ORDER BY a.start_datetime",
            $provider_id,
            $start_date,
            $end_date
        ), ARRAY_A);
    }

    /**
     * Helper method to get provider data with caching
     *
     * @param int $provider_id
     * @return array
     */
    private function get_provider_data($provider_id) {
        $cache_key = 'eye_book_provider_' . $provider_id;
        $provider_data = get_transient($cache_key);
        
        if (false === $provider_data) {
            global $wpdb;
            $provider_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}eye_book_providers WHERE id = %d",
                $provider_id
            ), ARRAY_A);
            
            set_transient($cache_key, $provider_data, HOUR_IN_SECONDS);
        }
        
        return $provider_data;
    }
}