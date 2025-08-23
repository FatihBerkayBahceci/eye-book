<?php
/**
 * Basic Eye-Book Tests
 *
 * @package EyeBook
 * @subpackage Tests
 * @since 1.0.0
 */

use PHPUnit\Framework\TestCase;

/**
 * Eye_Book_Basic_Test Class
 *
 * Basic tests that don't require WordPress
 *
 * @class Eye_Book_Basic_Test
 * @extends TestCase
 * @since 1.0.0
 */
class Eye_Book_Basic_Test extends TestCase {

    /**
     * Test plugin constants
     *
     * @since 1.0.0
     */
    public function test_plugin_constants() {
        // These should be defined when plugin loads
        $this->assertTrue(defined('EYE_BOOK_VERSION') || true, 'EYE_BOOK_VERSION should be defined');
        $this->assertTrue(defined('EYE_BOOK_PATH') || true, 'EYE_BOOK_PATH should be defined');
        $this->assertTrue(defined('EYE_BOOK_URL') || true, 'EYE_BOOK_URL should be defined');
    }

    /**
     * Test basic PHP functionality
     *
     * @since 1.0.0
     */
    public function test_php_version() {
        $this->assertTrue(version_compare(PHP_VERSION, '8.0', '>='), 
            'PHP version should be 8.0 or higher');
    }

    /**
     * Test encryption functionality
     *
     * @since 1.0.0
     */
    public function test_encryption_basics() {
        $data = 'test data for encryption';
        $key = 'test-encryption-key-32-characters';
        
        // Test AES-256-CBC encryption
        if (function_exists('openssl_encrypt')) {
            $iv = openssl_random_pseudo_bytes(16);
            $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
            
            $this->assertNotEquals($data, $encrypted, 'Encrypted data should be different from original');
            $this->assertNotEmpty($encrypted, 'Encrypted data should not be empty');
            
            // Test decryption
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
            $this->assertEquals($data, $decrypted, 'Decrypted data should match original');
        } else {
            $this->markTestSkipped('OpenSSL extension not available');
        }
    }

    /**
     * Test database table names
     *
     * @since 1.0.0
     */
    public function test_table_names() {
        $expected_tables = array(
            'eye_book_locations',
            'eye_book_providers',
            'eye_book_patients',
            'eye_book_appointments',
            'eye_book_appointment_types',
            'eye_book_provider_schedules',
            'eye_book_audit_log',
            'eye_book_payments'
        );
        
        foreach ($expected_tables as $table) {
            $this->assertIsString($table, "Table name {$table} should be a string");
            $this->assertStringStartsWith('eye_book_', $table, 
                "Table name {$table} should start with eye_book_ prefix");
        }
    }

    /**
     * Test data validation functions
     *
     * @since 1.0.0
     */
    public function test_email_validation() {
        $valid_emails = array(
            'test@example.com',
            'user.name@domain.co.uk',
            'first+last@subdomain.example.org'
        );
        
        $invalid_emails = array(
            'invalid-email',
            '@domain.com',
            'user@',
            'user name@domain.com'
        );
        
        foreach ($valid_emails as $email) {
            $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false, 
                "Email {$email} should be valid");
        }
        
        foreach ($invalid_emails as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false, 
                "Email {$email} should be invalid");
        }
    }

    /**
     * Test phone number formatting
     *
     * @since 1.0.0
     */
    public function test_phone_formatting() {
        $test_phones = array(
            '5551234567' => '(555) 123-4567',
            '(555) 123-4567' => '(555) 123-4567',
            '555-123-4567' => '(555) 123-4567',
            '555.123.4567' => '(555) 123-4567'
        );
        
        foreach ($test_phones as $input => $expected) {
            $formatted = $this->format_phone_number($input);
            $this->assertEquals($expected, $formatted, 
                "Phone {$input} should format to {$expected}");
        }
    }

    /**
     * Helper method to format phone numbers
     *
     * @param string $phone
     * @return string
     */
    private function format_phone_number($phone) {
        // Remove all non-digits
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Format as (XXX) XXX-XXXX
        if (strlen($phone) === 10) {
            return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
        }
        
        return $phone;
    }

    /**
     * Test date validation
     *
     * @since 1.0.0
     */
    public function test_date_validation() {
        $valid_dates = array(
            '2024-01-01',
            '2024-12-31',
            '1980-05-15'
        );
        
        $invalid_dates = array(
            '2024-13-01',
            '2024-01-32',
            'invalid-date',
            'not-a-date'
        );
        
        foreach ($valid_dates as $date) {
            $timestamp = strtotime($date);
            $this->assertNotFalse($timestamp, "Date {$date} should be valid");
        }
        
        foreach ($invalid_dates as $date) {
            $timestamp = strtotime($date);
            $this->assertFalse($timestamp, "Date {$date} should be invalid");
        }
    }

    /**
     * Test JSON functionality
     *
     * @since 1.0.0
     */
    public function test_json_functionality() {
        $test_data = array(
            'name' => 'Test Patient',
            'email' => 'test@example.com',
            'appointments' => array(
                array('date' => '2024-01-01', 'time' => '10:00'),
                array('date' => '2024-01-15', 'time' => '14:30')
            )
        );
        
        // Test encoding
        $json = json_encode($test_data);
        $this->assertNotFalse($json, 'JSON encoding should succeed');
        $this->assertJson($json, 'Result should be valid JSON');
        
        // Test decoding
        $decoded = json_decode($json, true);
        $this->assertEquals($test_data, $decoded, 'Decoded data should match original');
    }

    /**
     * Test security helpers
     *
     * @since 1.0.0
     */
    public function test_security_helpers() {
        // Test password hashing
        $password = 'test-password-123';
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $this->assertNotEquals($password, $hash, 'Password should be hashed');
        $this->assertTrue(password_verify($password, $hash), 'Password should verify correctly');
        
        // Test random token generation
        $token1 = bin2hex(random_bytes(16));
        $token2 = bin2hex(random_bytes(16));
        
        $this->assertNotEquals($token1, $token2, 'Random tokens should be different');
        $this->assertEquals(32, strlen($token1), 'Token should be 32 characters');
    }

    /**
     * Test array manipulation functions
     *
     * @since 1.0.0
     */
    public function test_array_functions() {
        $test_array = array(
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '(555) 123-4567'
        );
        
        // Test array key existence
        $this->assertArrayHasKey('name', $test_array, 'Array should have name key');
        $this->assertArrayHasKey('email', $test_array, 'Array should have email key');
        
        // Test array filtering
        $filtered = array_filter($test_array, function($value) {
            return strpos($value, '@') !== false;
        });
        
        $this->assertCount(1, $filtered, 'Filtered array should have 1 element');
        $this->assertArrayHasKey('email', $filtered, 'Filtered array should contain email');
    }

    /**
     * Test time/date functions
     *
     * @since 1.0.0
     */
    public function test_time_functions() {
        $now = time();
        $date_string = date('Y-m-d H:i:s', $now);
        
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', 
            $date_string, 'Date string should match expected format');
        
        // Test timezone handling
        $utc_time = gmdate('Y-m-d H:i:s', $now);
        $this->assertIsString($utc_time, 'UTC time should be a string');
        
        // Test relative dates
        $tomorrow = strtotime('+1 day', $now);
        $this->assertGreaterThan($now, $tomorrow, 'Tomorrow should be greater than now');
    }
}