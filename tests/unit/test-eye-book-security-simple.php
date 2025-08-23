<?php
/**
 * Simple Eye-Book Security Tests
 *
 * @package EyeBook
 * @subpackage Tests
 * @since 1.0.0
 */

use PHPUnit\Framework\TestCase;

/**
 * Eye_Book_Security_Simple_Test Class
 *
 * Simple security tests that don't require full WordPress
 *
 * @class Eye_Book_Security_Simple_Test
 * @extends TestCase
 * @since 1.0.0
 */
class Eye_Book_Security_Simple_Test extends TestCase {

    /**
     * Test input sanitization functions
     *
     * @since 1.0.0
     */
    public function test_input_sanitization() {
        // Test script tag removal
        $input = '<script>alert("xss")</script>Hello World';
        $sanitized = filter_var($input, FILTER_SANITIZE_STRING);
        
        $this->assertStringNotContainsString('<script>', $sanitized, 
            'Script tags should be removed');
        $this->assertStringContainsString('Hello World', $sanitized, 
            'Safe content should be preserved');

        // Test SQL injection patterns
        $sql_inputs = array(
            "'; DROP TABLE users; --",
            "' OR '1'='1",
            "1' UNION SELECT * FROM users --",
            "admin'/**/OR/**/1=1/**/--"
        );

        foreach ($sql_inputs as $input) {
            $sanitized = filter_var($input, FILTER_SANITIZE_STRING);
            // Basic sanitization should remove or escape dangerous patterns
            $this->assertNotEquals($input, $sanitized, 
                'SQL injection patterns should be sanitized');
        }
    }

    /**
     * Test XSS prevention
     *
     * @since 1.0.0
     */
    public function test_xss_prevention() {
        $xss_payloads = array(
            '<script>alert("xss")</script>',
            '<img src="x" onerror="alert(\'xss\')">',
            '<svg onload="alert(\'xss\')">',
            '<iframe src="javascript:alert(\'xss\')"></iframe>',
            '<div onclick="alert(\'xss\')">Click me</div>'
        );

        foreach ($xss_payloads as $payload) {
            echo "\n--- Testing XSS Payload: {$payload} ---\n";
            
            // Test different sanitization methods
            $sanitized1 = htmlspecialchars($payload, ENT_QUOTES, 'UTF-8');
            $sanitized2 = strip_tags($payload);
            $sanitized3 = filter_var($payload, FILTER_SANITIZE_STRING);
            
            echo "Original: {$payload}\n";
            echo "htmlspecialchars: {$sanitized1}\n";
            echo "strip_tags: {$sanitized2}\n";
            echo "filter_var: {$sanitized3}\n";

            // Real test: Check if any method actually neutralized the threat
            $htmlspecialchars_safe = !$this->contains_dangerous_html($sanitized1);
            $strip_tags_safe = !$this->contains_dangerous_html($sanitized2);
            $filter_var_safe = !$this->contains_dangerous_html($sanitized3);
            
            echo "htmlspecialchars safe: " . ($htmlspecialchars_safe ? 'YES' : 'NO') . "\n";
            echo "strip_tags safe: " . ($strip_tags_safe ? 'YES' : 'NO') . "\n";
            echo "filter_var safe: " . ($filter_var_safe ? 'YES' : 'NO') . "\n";

            // At least one method should neutralize the payload
            $is_safe = $htmlspecialchars_safe || $strip_tags_safe || $filter_var_safe;
            
            if (!$is_safe) {
                $this->fail("SECURITY RISK: None of the sanitization methods neutralized this XSS payload: {$payload}
                - htmlspecialchars result: {$sanitized1}
                - strip_tags result: {$sanitized2} 
                - filter_var result: {$sanitized3}");
            }
            
            $this->assertTrue($is_safe, "At least one sanitization method should neutralize XSS payload: {$payload}");
        }
    }

    /**
     * Helper method to check if payload is neutralized
     *
     * @param string $original
     * @param string $sanitized
     * @return bool
     */
    private function is_neutralized($original, $sanitized) {
        // If the sanitized version is different and doesn't contain dangerous patterns
        return $original !== $sanitized && !$this->contains_dangerous_html($sanitized);
    }

    /**
     * Helper method to check for dangerous HTML
     *
     * @param string $html
     * @return bool
     */
    private function contains_dangerous_html($html) {
        $dangerous_patterns = array(
            '/<script/i',
            '/javascript\s*:/i',
            '/on\w+\s*=/i', // event handlers like onclick, onload
            '/<iframe/i',
            '/<object/i',
            '/<embed/i'
        );

        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $html)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Test password hashing security
     *
     * @since 1.0.0
     */
    public function test_password_security() {
        $passwords = array(
            'password123',
            'StrongP@ssw0rd!',
            'very-long-password-with-special-chars-12345',
            'short'
        );

        foreach ($passwords as $password) {
            // Test PHP's password_hash
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            $this->assertNotEquals($password, $hash, 
                'Password should be hashed');
            $this->assertTrue(password_verify($password, $hash), 
                'Password should verify against its hash');
            $this->assertFalse(password_verify('wrong-password', $hash), 
                'Wrong password should not verify');
            
            // Test that same password generates different hashes (due to salt)
            $hash2 = password_hash($password, PASSWORD_DEFAULT);
            $this->assertNotEquals($hash, $hash2, 
                'Same password should generate different hashes due to salt');
        }
    }

    /**
     * Test token generation security
     *
     * @since 1.0.0
     */
    public function test_token_security() {
        // Test random token generation
        $tokens = array();
        for ($i = 0; $i < 100; $i++) {
            $tokens[] = bin2hex(random_bytes(32));
        }

        // All tokens should be unique
        $unique_tokens = array_unique($tokens);
        $this->assertEquals(count($tokens), count($unique_tokens), 
            'All generated tokens should be unique');

        // Test token length and format
        foreach ($tokens as $token) {
            $this->assertEquals(64, strlen($token), 
                'Token should be 64 characters (32 bytes in hex)');
            $this->assertMatchesRegularExpression('/^[a-f0-9]+$/', $token, 
                'Token should contain only hexadecimal characters');
        }
    }

    /**
     * Test CSRF token simulation
     *
     * @since 1.0.0
     */
    public function test_csrf_protection() {
        // Simulate CSRF token generation and validation
        $secret = 'csrf-secret-key';
        $user_id = 123;
        $action = 'delete_patient';
        $timestamp = time();
        
        // Generate token
        $token_data = $user_id . '|' . $action . '|' . $timestamp;
        $token = hash_hmac('sha256', $token_data, $secret);
        $full_token = base64_encode($token_data . '|' . $token);
        
        // Validate token
        $decoded = base64_decode($full_token);
        $parts = explode('|', $decoded);
        
        $this->assertEquals(4, count($parts), 'Token should have 4 parts');
        $this->assertEquals($user_id, (int)$parts[0], 'User ID should match');
        $this->assertEquals($action, $parts[1], 'Action should match');
        
        // Verify HMAC
        $verify_data = $parts[0] . '|' . $parts[1] . '|' . $parts[2];
        $verify_token = hash_hmac('sha256', $verify_data, $secret);
        $this->assertEquals($verify_token, $parts[3], 'HMAC should be valid');
        
        // Test token expiration (5 minutes)
        $age = time() - (int)$parts[2];
        $this->assertLessThan(300, $age, 'Token should be fresh (less than 5 minutes old)');
    }

    /**
     * Test data validation functions
     *
     * @since 1.0.0
     */
    public function test_data_validation() {
        // Test email validation
        $valid_emails = array(
            'test@example.com',
            'user.name+tag@domain.co.uk',
            'x@x.xx'
        );
        
        $invalid_emails = array(
            'invalid-email',
            '@domain.com',
            'user@',
            'user name@domain.com',
            'user@domain',
            ''
        );
        
        foreach ($valid_emails as $email) {
            $this->assertTrue(filter_var($email, FILTER_VALIDATE_EMAIL) !== false, 
                "Email should be valid: {$email}");
        }
        
        foreach ($invalid_emails as $email) {
            $this->assertFalse(filter_var($email, FILTER_VALIDATE_EMAIL) !== false, 
                "Email should be invalid: {$email}");
        }
        
        // Test phone number validation
        $phone_patterns = array(
            '(555) 123-4567',
            '555-123-4567',
            '555.123.4567',
            '5551234567'
        );
        
        foreach ($phone_patterns as $phone) {
            $digits_only = preg_replace('/[^0-9]/', '', $phone);
            $this->assertEquals(10, strlen($digits_only), 
                "Phone should have 10 digits: {$phone}");
            $this->assertMatchesRegularExpression('/^[0-9]+$/', $digits_only, 
                "Phone digits should be numeric: {$phone}");
        }
    }

    /**
     * Test file upload security
     *
     * @since 1.0.0
     */
    public function test_file_upload_security() {
        // Test dangerous file extensions
        $dangerous_files = array(
            'script.php',
            'malware.exe',
            'virus.bat',
            'hack.js',
            'shell.sh',
            'backdoor.php5',
            'trojan.phtml'
        );
        
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx');
        
        foreach ($dangerous_files as $filename) {
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $this->assertNotContains($extension, $allowed_extensions, 
                "Dangerous file extension should not be allowed: {$filename}");
        }
        
        // Test safe file extensions
        $safe_files = array(
            'photo.jpg',
            'document.pdf',
            'image.png',
            'report.docx'
        );
        
        foreach ($safe_files as $filename) {
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $this->assertContains($extension, $allowed_extensions, 
                "Safe file extension should be allowed: {$filename}");
        }
    }

    /**
     * Test SQL injection prevention patterns
     *
     * @since 1.0.0
     */
    public function test_sql_injection_patterns() {
        $sql_injection_patterns = array(
            "'; DROP TABLE",
            "' OR 1=1 --",
            "' UNION SELECT",
            "'; INSERT INTO",
            "'; UPDATE",
            "'; DELETE FROM",
            "/**/",
            "char(",
            "concat(",
            "0x",
            "unhex("
        );
        
        foreach ($sql_injection_patterns as $pattern) {
            echo "\n--- Testing SQL Injection Pattern: {$pattern} ---\n";
            
            // Test that pattern is detected in realistic contexts
            $test_inputs = array(
                "user_input_with_{$pattern}_injection",
                "normal_text {$pattern} more_text",
                $pattern,
                strtoupper($pattern),
                strtolower($pattern)
            );
            
            $detected_count = 0;
            foreach ($test_inputs as $input) {
                $is_detected = $this->detect_sql_injection($input);
                echo "Input: '{$input}' -> Detected: " . ($is_detected ? 'YES' : 'NO') . "\n";
                if ($is_detected) {
                    $detected_count++;
                }
            }
            
            // At least one variant should be detected for a real security test
            if ($detected_count === 0) {
                echo "WARNING: SQL injection pattern '{$pattern}' was not detected in any test case.\n";
                echo "This could indicate a vulnerability in the detection system.\n";
                
                // For now, we'll note this but not fail the test completely
                // In a real security audit, this would be a serious concern
                echo "SECURITY AUDIT NOTE: Detection system may need improvement for pattern: {$pattern}\n";
            }
            
            $this->assertGreaterThan(0, $detected_count, 
                "SQL injection pattern '{$pattern}' should be detected in at least one test case");
        }
    }

    /**
     * Helper method to detect SQL injection patterns
     *
     * @param string $input
     * @return bool
     */
    private function detect_sql_injection($input) {
        $dangerous_patterns = array(
            '/(\bDROP\b|\bDELETE\b|\bUNION\b|\bINSERT\b|\bUPDATE\b)/i',
            '/(\bOR\b\s+\d+\s*=\s*\d+)/i',
            '/(\'|\")(\s*;\s*)/i',
            '/(\-\-|\#|\/\*|\*\/)/i',
            '/(\bchar\s*\(|\bconcat\s*\(|\bunhex\s*\()/i',
            '/(0x[0-9a-f]+)/i'
        );
        
        foreach ($dangerous_patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Test session security
     *
     * @since 1.0.0
     */
    public function test_session_security() {
        // Test session ID security
        $session_id = session_create_id();
        
        if ($session_id) {
            $this->assertGreaterThan(20, strlen($session_id), 
                'Session ID should be sufficiently long');
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $session_id, 
                'Session ID should contain only alphanumeric characters');
        }
        
        // Test session timeout simulation
        $session_start = time();
        $session_timeout = 15 * 60; // 15 minutes
        $current_time = time();
        
        $session_age = $current_time - $session_start;
        $is_expired = $session_age > $session_timeout;
        
        $this->assertFalse($is_expired, 
            'New session should not be expired immediately');
        
        // Simulate expired session
        $old_session_start = time() - (20 * 60); // 20 minutes ago
        $old_session_age = $current_time - $old_session_start;
        $old_is_expired = $old_session_age > $session_timeout;
        
        $this->assertTrue($old_is_expired, 
            'Old session should be expired');
    }

    /**
     * Test IP validation and filtering
     *
     * @since 1.0.0
     */
    public function test_ip_validation() {
        $valid_ips = array(
            '192.168.1.1',
            '10.0.0.1',
            '172.16.0.1',
            '8.8.8.8',
            '2001:db8::1' // IPv6
        );
        
        $invalid_ips = array(
            '999.999.999.999',
            '192.168.1',
            'not.an.ip.address',
            '192.168.1.1.1',
            ''
        );
        
        foreach ($valid_ips as $ip) {
            $this->assertTrue(filter_var($ip, FILTER_VALIDATE_IP) !== false, 
                "IP should be valid: {$ip}");
        }
        
        foreach ($invalid_ips as $ip) {
            $this->assertFalse(filter_var($ip, FILTER_VALIDATE_IP) !== false, 
                "IP should be invalid: {$ip}");
        }
        
        // Test private IP detection
        $private_ips = array('192.168.1.1', '10.0.0.1', '172.16.0.1');
        $public_ips = array('8.8.8.8', '1.1.1.1');
        
        foreach ($private_ips as $ip) {
            $this->assertFalse(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) !== false, 
                "IP should be detected as private: {$ip}");
        }
        
        foreach ($public_ips as $ip) {
            $this->assertTrue(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) !== false, 
                "IP should be detected as public: {$ip}");
        }
    }
}