<?php
/**
 * Simple Eye-Book Performance Tests
 *
 * @package EyeBook
 * @subpackage Tests
 * @since 1.0.0
 */

use PHPUnit\Framework\TestCase;

/**
 * Eye_Book_Performance_Simple_Test Class
 *
 * Simple performance tests that don't require full WordPress
 *
 * @class Eye_Book_Performance_Simple_Test
 * @extends TestCase
 * @since 1.0.0
 */
class Eye_Book_Performance_Simple_Test extends TestCase {

    /**
     * Test memory usage for large datasets
     *
     * @since 1.0.0
     */
    public function test_memory_usage() {
        $initial_memory = memory_get_usage();
        
        // Create a large dataset
        $data = array();
        for ($i = 0; $i < 1000; $i++) {
            $data[] = array(
                'id' => $i,
                'first_name' => 'Patient' . $i,
                'last_name' => 'Test' . $i,
                'email' => "patient{$i}@example.com",
                'phone' => '(555) ' . str_pad($i, 3, '0', STR_PAD_LEFT) . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'date_of_birth' => '1980-01-01',
                'created_at' => date('Y-m-d H:i:s')
            );
        }
        
        $peak_memory = memory_get_peak_usage();
        $memory_used = $peak_memory - $initial_memory;
        
        // Memory usage should be reasonable (less than 10MB for 1000 records)
        $max_memory = 10 * 1024 * 1024; // 10MB
        $this->assertLessThan($max_memory, $memory_used, 
            'Memory usage should be under 10MB for 1000 patient records');
        
        // Clean up
        unset($data);
        
        $final_memory = memory_get_usage();
        $this->assertLessThan($peak_memory, $final_memory, 
            'Memory should be freed after cleanup');
    }

    /**
     * Test array processing performance
     *
     * @since 1.0.0
     */
    public function test_array_processing_performance() {
        // Create test dataset
        $patients = array();
        for ($i = 0; $i < 5000; $i++) {
            $patients[] = array(
                'id' => $i,
                'name' => 'Patient ' . $i,
                'email' => "patient{$i}@example.com",
                'status' => ($i % 3 === 0) ? 'active' : 'inactive'
            );
        }
        
        // Test filtering performance
        $start_time = microtime(true);
        
        $active_patients = array_filter($patients, function($patient) {
            return $patient['status'] === 'active';
        });
        
        $filter_time = microtime(true) - $start_time;
        
        $this->assertLessThan(0.1, $filter_time, 
            'Filtering 5000 records should take less than 0.1 seconds');
        $this->assertGreaterThan(0, count($active_patients), 
            'Should find active patients');
        
        // Test search performance
        $start_time = microtime(true);
        
        $search_results = array_filter($patients, function($patient) {
            return strpos($patient['name'], 'Patient 1') === 0;
        });
        
        $search_time = microtime(true) - $start_time;
        
        $this->assertLessThan(0.05, $search_time, 
            'Searching 5000 records should take less than 0.05 seconds');
    }

    /**
     * Test string processing performance
     *
     * @since 1.0.0
     */
    public function test_string_processing_performance() {
        $test_data = array();
        
        // Generate test strings
        for ($i = 0; $i < 1000; $i++) {
            $test_data[] = "Patient Name {$i} <script>alert('xss')</script> patient{$i}@example.com (555) {$i}-{$i}";
        }
        
        $start_time = microtime(true);
        
        // Process all strings (simulate sanitization)
        $processed = array_map(function($string) {
            // Remove script tags
            $clean = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $string);
            // Extract email
            preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $clean, $email_matches);
            // Extract phone
            preg_match('/\(\d{3}\)\s\d{3}-\d{4}/', $clean, $phone_matches);
            
            return array(
                'clean_text' => $clean,
                'email' => isset($email_matches[0]) ? $email_matches[0] : '',
                'phone' => isset($phone_matches[0]) ? $phone_matches[0] : ''
            );
        }, $test_data);
        
        $processing_time = microtime(true) - $start_time;
        
        $this->assertLessThan(0.5, $processing_time, 
            'Processing 1000 strings should take less than 0.5 seconds');
        $this->assertEquals(1000, count($processed), 
            'Should process all strings');
        
        // Verify processing worked
        $this->assertStringNotContainsString('<script>', $processed[0]['clean_text'], 
            'Script tags should be removed');
        $this->assertStringContainsString('@example.com', $processed[0]['email'], 
            'Email should be extracted');
    }

    /**
     * Test encryption performance
     *
     * @since 1.0.0
     */
    public function test_encryption_performance() {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension is not available');
            return;
        }
        
        $test_data = array();
        
        // Generate PHI data to encrypt
        for ($i = 0; $i < 100; $i++) {
            $test_data[] = array(
                'name' => "Patient Name {$i}",
                'ssn' => sprintf('%03d-%02d-%04d', $i, $i % 100, $i * 10),
                'email' => "patient{$i}@example.com",
                'phone' => sprintf('(555) %03d-%04d', $i % 1000, $i % 10000),
                'address' => "{$i} Main St, City, State {$i}"
            );
        }
        
        $key = hash('sha256', 'performance-test-key', true);
        $start_time = microtime(true);
        
        // Encrypt all PHI data
        $encrypted_data = array();
        foreach ($test_data as $record) {
            $encrypted_record = array();
            foreach ($record as $field => $value) {
                $iv = openssl_random_pseudo_bytes(16);
                $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);
                $encrypted_record[$field] = base64_encode($iv . $encrypted);
            }
            $encrypted_data[] = $encrypted_record;
        }
        
        $encryption_time = microtime(true) - $start_time;
        
        $this->assertLessThan(1.0, $encryption_time, 
            'Encrypting 100 PHI records should take less than 1 second');
        $this->assertEquals(100, count($encrypted_data), 
            'Should encrypt all records');
        
        // Test decryption performance
        $start_time = microtime(true);
        
        $decrypted_data = array();
        foreach ($encrypted_data as $record) {
            $decrypted_record = array();
            foreach ($record as $field => $encrypted_value) {
                $data = base64_decode($encrypted_value);
                $iv = substr($data, 0, 16);
                $encrypted = substr($data, 16);
                $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
                $decrypted_record[$field] = $decrypted;
            }
            $decrypted_data[] = $decrypted_record;
        }
        
        $decryption_time = microtime(true) - $start_time;
        
        $this->assertLessThan(1.0, $decryption_time, 
            'Decrypting 100 PHI records should take less than 1 second');
        $this->assertEquals($test_data, $decrypted_data, 
            'Decrypted data should match original');
    }

    /**
     * Test hash generation performance
     *
     * @since 1.0.0
     */
    public function test_hash_performance() {
        $test_data = array();
        
        // Generate test data for hashing
        for ($i = 0; $i < 1000; $i++) {
            $test_data[] = "audit_log_entry_{$i}_" . date('Y-m-d H:i:s') . "_sensitive_data";
        }
        
        $start_time = microtime(true);
        
        // Generate SHA256 hashes
        $hashes = array_map(function($data) {
            return hash('sha256', $data);
        }, $test_data);
        
        $hash_time = microtime(true) - $start_time;
        
        $this->assertLessThan(0.1, $hash_time, 
            'Generating 1000 SHA256 hashes should take less than 0.1 seconds');
        $this->assertEquals(1000, count($hashes), 
            'Should generate all hashes');
        
        // Verify hash uniqueness
        $unique_hashes = array_unique($hashes);
        $this->assertEquals(count($hashes), count($unique_hashes), 
            'All hashes should be unique');
    }

    /**
     * Test JSON processing performance
     *
     * @since 1.0.0
     */
    public function test_json_performance() {
        $test_data = array();
        
        // Generate complex nested data
        for ($i = 0; $i < 500; $i++) {
            $test_data[] = array(
                'patient' => array(
                    'id' => $i,
                    'name' => "Patient {$i}",
                    'contact' => array(
                        'email' => "patient{$i}@example.com",
                        'phone' => "(555) {$i}-{$i}",
                        'address' => array(
                            'street' => "{$i} Main St",
                            'city' => 'Test City',
                            'state' => 'TS',
                            'zip' => str_pad($i, 5, '0', STR_PAD_LEFT)
                        )
                    )
                ),
                'appointments' => array(
                    array(
                        'date' => date('Y-m-d', strtotime("+{$i} days")),
                        'time' => sprintf('%02d:00', ($i % 12) + 8),
                        'type' => 'checkup',
                        'provider' => "Dr. Provider " . ($i % 5)
                    )
                ),
                'metadata' => array(
                    'created_at' => date('c'),
                    'updated_at' => date('c'),
                    'version' => '1.0.0'
                )
            );
        }
        
        // Test JSON encoding performance
        $start_time = microtime(true);
        
        $json_strings = array_map('json_encode', $test_data);
        
        $encoding_time = microtime(true) - $start_time;
        
        $this->assertLessThan(0.2, $encoding_time, 
            'Encoding 500 complex records to JSON should take less than 0.2 seconds');
        
        // Test JSON decoding performance
        $start_time = microtime(true);
        
        $decoded_data = array_map(function($json) {
            return json_decode($json, true);
        }, $json_strings);
        
        $decoding_time = microtime(true) - $start_time;
        
        $this->assertLessThan(0.2, $decoding_time, 
            'Decoding 500 JSON strings should take less than 0.2 seconds');
        $this->assertEquals($test_data, $decoded_data, 
            'Decoded data should match original');
    }

    /**
     * Test date/time processing performance
     *
     * @since 1.0.0
     */
    public function test_datetime_performance() {
        $start_time = microtime(true);
        
        // Generate 1000 date calculations
        $dates = array();
        $base_date = strtotime('2024-01-01 08:00:00');
        
        for ($i = 0; $i < 1000; $i++) {
            $appointment_time = $base_date + ($i * 86400) + ($i % 24 * 3600); // Various dates and times
            $dates[] = array(
                'timestamp' => $appointment_time,
                'formatted' => date('Y-m-d H:i:s', $appointment_time),
                'relative' => date('Y-m-d H:i:s', $appointment_time + 1800), // +30 minutes
                'day_of_week' => date('l', $appointment_time),
                'is_weekend' => in_array(date('w', $appointment_time), array(0, 6))
            );
        }
        
        $processing_time = microtime(true) - $start_time;
        
        $this->assertLessThan(0.1, $processing_time, 
            'Processing 1000 date calculations should take less than 0.1 seconds');
        $this->assertEquals(1000, count($dates), 
            'Should process all dates');
        
        // Verify some results
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', 
            $dates[0]['formatted'], 'Date should be properly formatted');
        $this->assertIsBool($dates[0]['is_weekend'], 
            'Weekend flag should be boolean');
    }

    /**
     * Test pagination performance simulation
     *
     * @since 1.0.0
     */
    public function test_pagination_performance() {
        // Simulate a large dataset
        $total_records = 10000;
        $page_size = 50;
        $total_pages = ceil($total_records / $page_size);
        
        $start_time = microtime(true);
        
        // Simulate fetching different pages
        for ($page = 1; $page <= min(20, $total_pages); $page++) { // Test first 20 pages
            $offset = ($page - 1) * $page_size;
            $page_data = array();
            
            // Simulate record creation for this page
            for ($i = 0; $i < $page_size && ($offset + $i) < $total_records; $i++) {
                $record_id = $offset + $i;
                $page_data[] = array(
                    'id' => $record_id,
                    'name' => "Record {$record_id}",
                    'created_at' => date('Y-m-d H:i:s', time() - $record_id * 3600)
                );
            }
            
            // Simulate basic processing
            $processed_page = array_map(function($record) {
                return array(
                    'id' => $record['id'],
                    'display_name' => $record['name'],
                    'age_hours' => (time() - strtotime($record['created_at'])) / 3600
                );
            }, $page_data);
        }
        
        $pagination_time = microtime(true) - $start_time;
        
        $this->assertLessThan(0.5, $pagination_time, 
            'Processing 20 pages of pagination should take less than 0.5 seconds');
    }
}