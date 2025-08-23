<?php
/**
 * Eye-Book Security Hardening Tests
 *
 * @package EyeBook
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Eye_Book_Security_Hardening_Test Class
 *
 * Tests for security hardening functionality
 *
 * @class Eye_Book_Security_Hardening_Test
 * @extends Eye_Book_Test_Case
 * @since 1.0.0
 */
class Eye_Book_Security_Hardening_Test extends Eye_Book_Test_Case {

    /**
     * Security hardening instance
     *
     * @var Eye_Book_Security_Hardening
     * @since 1.0.0
     */
    private $security;

    /**
     * Set up before each test
     *
     * @since 1.0.0
     */
    public function setUp(): void {
        parent::setUp();
        $this->security = new Eye_Book_Security_Hardening();
    }

    /**
     * Test input sanitization
     *
     * @since 1.0.0
     */
    public function test_input_sanitization() {
        // Test basic text sanitization
        $dirty_input = '<script>alert("xss")</script>John Doe';
        $clean_input = $this->security->sanitize_input($dirty_input, 'text');
        
        $this->assertStringNotContainsString('<script>', $clean_input, 
            'Script tags should be removed');
        $this->assertStringContainsString('John Doe', $clean_input, 
            'Safe content should be preserved');
        
        // Test email sanitization
        $email_input = 'test@example.com<script>alert("xss")</script>';
        $clean_email = $this->security->sanitize_input($email_input, 'email');
        
        $this->assertEquals('test@example.com', $clean_email, 
            'Email should be properly sanitized');
        
        // Test phone sanitization
        $phone_input = '(555) 123-4567<script>alert("xss")</script>';
        $clean_phone = $this->security->sanitize_input($phone_input, 'phone');
        
        $this->assertMatchesRegularExpression('/^\(\d{3}\)\s\d{3}-\d{4}$/', $clean_phone, 
            'Phone should match expected format');
        $this->assertStringNotContainsString('<script>', $clean_phone, 
            'Script tags should be removed from phone');
        
        // Test array sanitization
        $array_input = array(
            'name' => '<script>alert("xss")</script>Test Name',
            'email' => 'test@example.com<script>',
            'notes' => '<p>Valid HTML</p><script>alert("bad")</script>'
        );
        
        $clean_array = $this->security->sanitize_input($array_input, 'text');
        
        $this->assertStringNotContainsString('<script>', $clean_array['name'], 
            'Script should be removed from array element');
        $this->assertStringContainsString('Test Name', $clean_array['name'], 
            'Safe content should be preserved in array');
    }

    /**
     * Test input validation
     *
     * @since 1.0.0
     */
    public function test_input_validation() {
        // Test email validation
        $valid_email = 'test@example.com';
        $result = $this->security->validate_input($valid_email, 'email');
        $this->assertTrue($result, 'Valid email should pass validation');
        
        $invalid_email = 'not-an-email';
        $result = $this->security->validate_input($invalid_email, 'email');
        $this->assertInstanceOf('WP_Error', $result, 'Invalid email should return WP_Error');
        
        // Test phone validation
        $valid_phone = '(555) 123-4567';
        $result = $this->security->validate_input($valid_phone, 'phone');
        $this->assertTrue($result, 'Valid phone should pass validation');
        
        $invalid_phone = '123';
        $result = $this->security->validate_input($invalid_phone, 'phone');
        $this->assertInstanceOf('WP_Error', $result, 'Invalid phone should return WP_Error');
        
        // Test required field validation
        $empty_input = '';
        $result = $this->security->validate_input($empty_input, 'text', array('required' => true));
        $this->assertInstanceOf('WP_Error', $result, 'Empty required field should return WP_Error');
        
        // Test length validation
        $short_input = 'ab';
        $result = $this->security->validate_input($short_input, 'text', array('min_length' => 5));
        $this->assertInstanceOf('WP_Error', $result, 'Input shorter than minimum should return WP_Error');
        
        $long_input = str_repeat('a', 300);
        $result = $this->security->validate_input($long_input, 'text', array('max_length' => 100));
        $this->assertInstanceOf('WP_Error', $result, 'Input longer than maximum should return WP_Error');
    }

    /**
     * Test SQL injection prevention
     *
     * @since 1.0.0
     */
    public function test_sql_injection_prevention() {
        // Simulate SQL injection attempts
        $injection_attempts = array(
            "' OR '1'='1",
            "'; DROP TABLE wp_users; --",
            "1' UNION SELECT * FROM wp_users --",
            "1' AND (SELECT COUNT(*) FROM wp_users) > 0 --"
        );
        
        foreach ($injection_attempts as $attempt) {
            $_REQUEST['test_input'] = $attempt;
            $_POST['test_input'] = $attempt;
            $_GET['test_input'] = $attempt;
            
            // Capture output for threat detection
            ob_start();
            
            try {
                // This should trigger threat detection
                $this->security->detect_threats();
                $output = ob_get_clean();
            } catch (Exception $e) {
                $output = ob_get_clean();
                // Expected behavior - threat detected and blocked
                $this->assertStringContainsString('Security violation', $e->getMessage(), 
                    'SQL injection should be detected and blocked');
            }
        }
        
        // Clean up
        $_REQUEST = array();
        $_POST = array();
        $_GET = array();
    }

    /**
     * Test XSS prevention
     *
     * @since 1.0.0
     */
    public function test_xss_prevention() {
        $xss_attempts = array(
            '<script>alert("xss")</script>',
            '<img src="x" onerror="alert(\'xss\')">',
            '<svg onload="alert(\'xss\')">',
            'javascript:alert("xss")',
            '<iframe src="javascript:alert(\'xss\')"></iframe>'
        );
        
        foreach ($xss_attempts as $attempt) {
            $sanitized = $this->security->sanitize_input($attempt, 'text');
            
            $this->assertStringNotContainsString('<script>', $sanitized, 
                'Script tags should be removed');
            $this->assertStringNotContainsString('javascript:', $sanitized, 
                'JavaScript protocol should be removed');
            $this->assertStringNotContainsString('onerror=', $sanitized, 
                'Event handlers should be removed');
        }
    }

    /**
     * Test brute force protection
     *
     * @since 1.0.0
     */
    public function test_brute_force_protection() {
        $test_ip = '192.168.1.100';
        $test_username = 'testuser';
        
        // Simulate multiple failed login attempts
        for ($i = 0; $i < 6; $i++) {
            $_SERVER['REMOTE_ADDR'] = $test_ip;
            $this->security->handle_failed_login($test_username);
        }
        
        // Check if IP is blocked
        $_SERVER['REMOTE_ADDR'] = $test_ip;
        
        // Create a mock user for authentication check
        $user = new WP_User();
        $result = $this->security->check_login_attempts($user, $test_username, 'password');
        
        $this->assertInstanceOf('WP_Error', $result, 
            'Login should be blocked after multiple failed attempts');
        $this->assertStringContainsString('Too many failed login attempts', $result->get_error_message(), 
            'Error message should indicate lockout');
    }

    /**
     * Test IP blacklisting
     *
     * @since 1.0.0
     */
    public function test_ip_blacklisting() {
        $test_ip = '192.168.1.101';
        
        // Add IP to blacklist
        update_option('eye_book_ip_blacklist', array($test_ip));
        
        // Set the test IP
        $_SERVER['REMOTE_ADDR'] = $test_ip;
        
        // Expect the access to be blocked
        $this->expectException('Exception');
        $this->expectExceptionMessage('Access denied');
        
        $this->security->check_ip_access();
    }

    /**
     * Test file upload security
     *
     * @since 1.0.0
     */
    public function test_file_upload_security() {
        // Test malicious file detection
        $malicious_file = array(
            'name' => 'malicious.php',
            'type' => 'application/php',
            'tmp_name' => '/tmp/test',
            'error' => 0,
            'size' => 1024
        );
        
        $result = $this->security->secure_file_upload($malicious_file);
        $this->assertNotEmpty($result['error'], 'PHP file should be rejected');
        
        // Test oversized file
        $large_file = array(
            'name' => 'large.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/test',
            'error' => 0,
            'size' => 50 * 1024 * 1024 // 50MB
        );
        
        $result = $this->security->secure_file_upload($large_file);
        $this->assertNotEmpty($result['error'], 'Oversized file should be rejected');
        
        // Test valid file
        $valid_file = array(
            'name' => 'photo.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/test',
            'error' => 0,
            'size' => 1024 * 1024 // 1MB
        );
        
        $result = $this->security->secure_file_upload($valid_file);
        $this->assertEmpty($result['error'], 'Valid file should be accepted');
    }

    /**
     * Test security headers
     *
     * @since 1.0.0
     */
    public function test_security_headers() {
        // Capture headers
        ob_start();
        $this->security->add_security_headers();
        $headers = xdebug_get_headers();
        ob_end_clean();
        
        $header_checks = array(
            'X-Frame-Options: SAMEORIGIN',
            'X-Content-Type-Options: nosniff',
            'X-XSS-Protection: 1; mode=block'
        );
        
        foreach ($header_checks as $expected_header) {
            $this->assertContains($expected_header, $headers, 
                "Security header should be set: {$expected_header}");
        }
    }

    /**
     * Test session security
     *
     * @since 1.0.0
     */
    public function test_session_security() {
        // Create test user
        $user_id = $this->factory->user->create(array(
            'user_login' => 'testuser',
            'user_pass' => 'testpass'
        ));
        
        // Simulate login
        wp_set_current_user($user_id);
        $user = get_user_by('ID', $user_id);
        
        // Test session regeneration
        $old_session_id = session_id();
        $this->security->regenerate_session_id();
        $new_session_id = session_id();
        
        $this->assertNotEquals($old_session_id, $new_session_id, 
            'Session ID should be regenerated');
        
        // Test session timeout enforcement
        // Set last activity to 2 hours ago
        update_user_meta($user_id, 'last_activity', time() - (2 * 60 * 60));
        
        // Session should be considered expired
        $this->security->enforce_session_timeout();
        
        // User should be logged out
        $this->assertEquals(0, get_current_user_id(), 
            'User should be logged out after session timeout');
    }

    /**
     * Test vulnerability scanning
     *
     * @since 1.0.0
     */
    public function test_vulnerability_scanning() {
        $scan_results = $this->security->run_vulnerability_scan();
        
        $this->assertIsArray($scan_results, 'Scan results should be array');
        $this->assertArrayHasKey('plugins', $scan_results, 
            'Scan should check plugins');
        $this->assertArrayHasKey('passwords', $scan_results, 
            'Scan should check passwords');
        $this->assertArrayHasKey('permissions', $scan_results, 
            'Scan should check file permissions');
        $this->assertArrayHasKey('database', $scan_results, 
            'Scan should check database security');
        
        // Verify scan results structure
        foreach ($scan_results as $category => $issues) {
            $this->assertIsArray($issues, "Scan results for {$category} should be array");
        }
    }

    /**
     * Test threat detection patterns
     *
     * @since 1.0.0
     */
    public function test_threat_detection_patterns() {
        // Test path traversal detection
        $_REQUEST['file'] = '../../../etc/passwd';
        
        ob_start();
        try {
            $this->security->detect_threats();
        } catch (Exception $e) {
            $this->assertStringContainsString('Security violation', $e->getMessage(), 
                'Path traversal should be detected');
        }
        ob_end_clean();
        
        // Test malicious user agent detection
        $_SERVER['HTTP_USER_AGENT'] = 'sqlmap/1.0';
        
        ob_start();
        try {
            $this->security->detect_threats();
        } catch (Exception $e) {
            $this->assertStringContainsString('Security violation', $e->getMessage(), 
                'Malicious user agent should be detected');
        }
        ob_end_clean();
        
        // Clean up
        $_REQUEST = array();
        $_SERVER['HTTP_USER_AGENT'] = 'PHPUnit Test';
    }

    /**
     * Test security audit logging
     *
     * @since 1.0.0
     */
    public function test_security_audit_logging() {
        // Create test user
        $user_id = $this->factory->user->create();
        wp_set_current_user($user_id);
        
        // Test failed login logging
        $this->security->handle_failed_login('testuser');
        $this->assertAuditLogExists('user_login_failed', null);
        
        // Test successful login logging
        $user = get_user_by('ID', $user_id);
        $this->security->handle_successful_login($user->user_login, $user);
        $this->assertAuditLogExists('user_login_success', $user_id);
    }

    /**
     * Test security configuration validation
     *
     * @since 1.0.0
     */
    public function test_security_configuration() {
        // Test security level setting
        update_option('eye_book_security_level', 'hipaa_compliant');
        
        $security_checks = $this->security->run_security_checks();
        
        $this->assertIsArray($security_checks, 'Security checks should return array');
        $this->assertArrayHasKey('wp_version', $security_checks, 
            'Should check WordPress version');
        $this->assertArrayHasKey('ssl_config', $security_checks, 
            'Should check SSL configuration');
        $this->assertArrayHasKey('database_security', $security_checks, 
            'Should check database security');
        
        // Verify each check has proper structure
        foreach ($security_checks as $check_name => $check_result) {
            $this->assertArrayHasKey('status', $check_result, 
                "Check {$check_name} should have status");
            $this->assertArrayHasKey('message', $check_result, 
                "Check {$check_name} should have message");
            $this->assertContains($check_result['status'], array('pass', 'fail', 'warning'), 
                "Check {$check_name} status should be valid");
        }
    }

    /**
     * Test CSRF protection
     *
     * @since 1.0.0
     */
    public function test_csrf_protection() {
        // Test without nonce
        $_POST['action'] = 'eye_book_test_action';
        
        $result = wp_verify_nonce('', 'eye_book_test_nonce');
        $this->assertFalse($result, 'Request without nonce should fail');
        
        // Test with valid nonce
        $nonce = wp_create_nonce('eye_book_test_nonce');
        $result = wp_verify_nonce($nonce, 'eye_book_test_nonce');
        $this->assertNotFalse($result, 'Request with valid nonce should succeed');
        
        // Test with invalid nonce
        $result = wp_verify_nonce('invalid_nonce', 'eye_book_test_nonce');
        $this->assertFalse($result, 'Request with invalid nonce should fail');
    }
}