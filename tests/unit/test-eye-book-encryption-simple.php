<?php
/**
 * Simple Eye-Book Encryption Tests
 *
 * @package EyeBook
 * @subpackage Tests
 * @since 1.0.0
 */

use PHPUnit\Framework\TestCase;

/**
 * Eye_Book_Encryption_Simple_Test Class
 *
 * Simple encryption tests that don't require full WordPress
 *
 * @class Eye_Book_Encryption_Simple_Test
 * @extends TestCase
 * @since 1.0.0
 */
class Eye_Book_Encryption_Simple_Test extends TestCase {

    /**
     * Test AES-256-CBC encryption/decryption
     *
     * @since 1.0.0
     */
    public function test_aes_encryption() {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension is not available');
            return;
        }

        $data = 'Sensitive patient information';
        $key = hash('sha256', 'test-encryption-key', true);
        $iv = openssl_random_pseudo_bytes(16);

        // Test encryption
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        $this->assertNotFalse($encrypted, 'Encryption should succeed');
        $this->assertNotEquals($data, $encrypted, 'Encrypted data should differ from original');

        // Test decryption
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        $this->assertEquals($data, $decrypted, 'Decrypted data should match original');
    }

    /**
     * Test key generation
     *
     * @since 1.0.0
     */
    public function test_key_generation() {
        // Test different key generation methods
        $key1 = hash('sha256', 'password1', true);
        $key2 = hash('sha256', 'password2', true);
        
        $this->assertEquals(32, strlen($key1), 'SHA256 key should be 32 bytes');
        $this->assertEquals(32, strlen($key2), 'SHA256 key should be 32 bytes');
        $this->assertNotEquals($key1, $key2, 'Different passwords should generate different keys');

        // Test random key generation
        $random_key1 = random_bytes(32);
        $random_key2 = random_bytes(32);
        
        $this->assertEquals(32, strlen($random_key1), 'Random key should be 32 bytes');
        $this->assertNotEquals($random_key1, $random_key2, 'Random keys should be different');
    }

    /**
     * Test IV generation and uniqueness
     *
     * @since 1.0.0
     */
    public function test_iv_generation() {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension is not available');
            return;
        }

        $iv1 = openssl_random_pseudo_bytes(16);
        $iv2 = openssl_random_pseudo_bytes(16);
        
        $this->assertEquals(16, strlen($iv1), 'IV should be 16 bytes for AES-256-CBC');
        $this->assertEquals(16, strlen($iv2), 'IV should be 16 bytes for AES-256-CBC');
        $this->assertNotEquals($iv1, $iv2, 'IVs should be unique');
    }

    /**
     * Test PHI field encryption
     *
     * @since 1.0.0
     */
    public function test_phi_field_encryption() {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension is not available');
            return;
        }

        $phi_data = array(
            'first_name' => 'John',
            'last_name' => 'Doe',
            'ssn' => '123-45-6789',
            'email' => 'john.doe@example.com',
            'phone' => '(555) 123-4567',
            'address' => '123 Main St, City, State 12345'
        );

        $key = hash('sha256', 'phi-encryption-key', true);

        foreach ($phi_data as $field => $value) {
            $iv = openssl_random_pseudo_bytes(16);
            $encrypted = openssl_encrypt($value, 'AES-256-CBC', $key, 0, $iv);
            
            $this->assertNotEquals($value, $encrypted, "PHI field {$field} should be encrypted");
            $this->assertNotEmpty($encrypted, "Encrypted {$field} should not be empty");
            
            // Verify decryption
            $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
            $this->assertEquals($value, $decrypted, "Decrypted {$field} should match original");
        }
    }

    /**
     * Test base64 encoding for storage
     *
     * @since 1.0.0
     */
    public function test_base64_encoding() {
        $data = 'Test data for base64 encoding';
        
        // Test encoding
        $encoded = base64_encode($data);
        $this->assertNotEquals($data, $encoded, 'Encoded data should differ from original');
        $this->assertTrue(base64_decode($encoded, true) !== false, 'Encoded data should be valid base64');

        // Test decoding
        $decoded = base64_decode($encoded);
        $this->assertEquals($data, $decoded, 'Decoded data should match original');
    }

    /**
     * Test encryption with IV prepending
     *
     * @since 1.0.0
     */
    public function test_encryption_with_iv_prepend() {
        if (!extension_loaded('openssl')) {
            $this->markTestSkipped('OpenSSL extension is not available');
            return;
        }

        $data = 'Test data for IV prepending';
        $key = hash('sha256', 'test-key', true);
        $iv = openssl_random_pseudo_bytes(16);

        // Encrypt and prepend IV
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        $encrypted_with_iv = base64_encode($iv . $encrypted);

        // Extract IV and decrypt
        $encrypted_data = base64_decode($encrypted_with_iv);
        $extracted_iv = substr($encrypted_data, 0, 16);
        $encrypted_content = substr($encrypted_data, 16);

        $this->assertEquals($iv, $extracted_iv, 'Extracted IV should match original');
        
        $decrypted = openssl_decrypt($encrypted_content, 'AES-256-CBC', $key, 0, $extracted_iv);
        $this->assertEquals($data, $decrypted, 'Decrypted data should match original');
    }

    /**
     * Test password hashing for user authentication
     *
     * @since 1.0.0
     */
    public function test_password_hashing() {
        $password = 'user-password-123';
        
        // Test PHP's password_hash
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->assertNotEquals($password, $hash, 'Hashed password should differ from original');
        $this->assertTrue(password_verify($password, $hash), 'Password should verify against hash');
        $this->assertFalse(password_verify('wrong-password', $hash), 'Wrong password should not verify');

        // Test that same password generates different hashes (due to salt)
        $hash2 = password_hash($password, PASSWORD_DEFAULT);
        $this->assertNotEquals($hash, $hash2, 'Same password should generate different hashes');
        $this->assertTrue(password_verify($password, $hash2), 'Password should verify against second hash');
    }

    /**
     * Test secure random token generation
     *
     * @since 1.0.0
     */
    public function test_token_generation() {
        // Test random_bytes
        $token1 = bin2hex(random_bytes(16));
        $token2 = bin2hex(random_bytes(16));
        
        $this->assertEquals(32, strlen($token1), 'Token should be 32 characters (16 bytes hex)');
        $this->assertEquals(32, strlen($token2), 'Token should be 32 characters (16 bytes hex)');
        $this->assertNotEquals($token1, $token2, 'Tokens should be unique');

        // Test different token lengths
        $short_token = bin2hex(random_bytes(8));
        $long_token = bin2hex(random_bytes(32));
        
        $this->assertEquals(16, strlen($short_token), 'Short token should be 16 characters');
        $this->assertEquals(64, strlen($long_token), 'Long token should be 64 characters');
    }

    /**
     * Test data integrity with HMAC
     *
     * @since 1.0.0
     */
    public function test_hmac_integrity() {
        $data = 'Important data that needs integrity verification';
        $key = 'hmac-secret-key';
        
        // Generate HMAC
        $hmac = hash_hmac('sha256', $data, $key);
        $this->assertEquals(64, strlen($hmac), 'SHA256 HMAC should be 64 characters');

        // Verify integrity
        $verify_hmac = hash_hmac('sha256', $data, $key);
        $this->assertEquals($hmac, $verify_hmac, 'HMAC should be reproducible with same data and key');

        // Test with modified data
        $modified_data = $data . ' modified';
        $modified_hmac = hash_hmac('sha256', $modified_data, $key);
        $this->assertNotEquals($hmac, $modified_hmac, 'HMAC should change with data modification');
    }

    /**
     * Test timing safe string comparison
     *
     * @since 1.0.0
     */
    public function test_timing_safe_comparison() {
        $string1 = 'secret-hash-value';
        $string2 = 'secret-hash-value';
        $string3 = 'different-value';

        // Test hash_equals for timing-safe comparison
        $this->assertTrue(hash_equals($string1, $string2), 'Identical strings should be equal');
        $this->assertFalse(hash_equals($string1, $string3), 'Different strings should not be equal');
        
        // Test with different length strings
        $short = 'short';
        $long = 'much-longer-string';
        $this->assertFalse(hash_equals($short, $long), 'Different length strings should not be equal');
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

        $data = str_repeat('Sample data for performance testing. ', 100); // ~3KB
        $key = hash('sha256', 'performance-test-key', true);
        $iv = openssl_random_pseudo_bytes(16);

        $start_time = microtime(true);
        
        // Encrypt 100 times
        for ($i = 0; $i < 100; $i++) {
            openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        }
        
        $encryption_time = microtime(true) - $start_time;
        
        // Should be able to encrypt 100 * 3KB in under 1 second
        $this->assertLessThan(1.0, $encryption_time, 'Encryption should be reasonably fast');
    }
}