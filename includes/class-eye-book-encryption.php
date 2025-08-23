<?php
/**
 * Encryption and data protection class for Eye-Book plugin
 *
 * @package EyeBook
 * @subpackage Encryption
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eye_Book_Encryption Class
 *
 * Handles encryption and decryption of sensitive data for HIPAA compliance
 *
 * @class Eye_Book_Encryption
 * @since 1.0.0
 */
class Eye_Book_Encryption {

    /**
     * Encryption method
     *
     * @var string
     * @since 1.0.0
     */
    private static $method = 'AES-256-CBC';

    /**
     * Initialize encryption system
     *
     * @since 1.0.0
     */
    public static function init() {
        // Ensure encryption key exists
        self::get_encryption_key();
    }

    /**
     * Get or create encryption key
     *
     * @return string
     * @since 1.0.0
     */
    private static function get_encryption_key() {
        $key = get_option('eye_book_encryption_key');
        
        if (empty($key)) {
            $key = self::generate_key();
            update_option('eye_book_encryption_key', $key, false); // Don't autoload
            
            // Log key generation
            Eye_Book_Audit::log('encryption_key_generated', 'security', null, array(
                'key_length' => strlen($key),
                'method' => self::$method
            ));
        }
        
        return $key;
    }

    /**
     * Generate a secure encryption key
     *
     * @return string
     * @since 1.0.0
     */
    private static function generate_key() {
        if (function_exists('random_bytes')) {
            return base64_encode(random_bytes(32));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return base64_encode(openssl_random_pseudo_bytes(32));
        } else {
            // Fallback using WordPress salts
            return base64_encode(hash('sha256', wp_salt('auth') . wp_salt('secure_auth') . wp_salt('logged_in') . wp_salt('nonce'), true));
        }
    }

    /**
     * Encrypt sensitive data
     *
     * @param string $data Data to encrypt
     * @return string|false Encrypted data or false on failure
     * @since 1.0.0
     */
    public static function encrypt($data) {
        if (empty($data)) {
            return $data;
        }

        try {
            $key = base64_decode(self::get_encryption_key());
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::$method));
            
            $encrypted = openssl_encrypt($data, self::$method, $key, OPENSSL_RAW_DATA, $iv);
            
            if ($encrypted === false) {
                error_log('Eye-Book Encryption Error: ' . openssl_error_string());
                return false;
            }
            
            // Combine IV and encrypted data
            $result = base64_encode($iv . $encrypted);
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Eye-Book Encryption Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Decrypt sensitive data
     *
     * @param string $encrypted_data Encrypted data to decrypt
     * @return string|false Decrypted data or false on failure
     * @since 1.0.0
     */
    public static function decrypt($encrypted_data) {
        if (empty($encrypted_data)) {
            return $encrypted_data;
        }

        try {
            $key = base64_decode(self::get_encryption_key());
            $data = base64_decode($encrypted_data);
            
            $iv_length = openssl_cipher_iv_length(self::$method);
            $iv = substr($data, 0, $iv_length);
            $encrypted = substr($data, $iv_length);
            
            $decrypted = openssl_decrypt($encrypted, self::$method, $key, OPENSSL_RAW_DATA, $iv);
            
            if ($decrypted === false) {
                error_log('Eye-Book Decryption Error: ' . openssl_error_string());
                return false;
            }
            
            return $decrypted;
            
        } catch (Exception $e) {
            error_log('Eye-Book Decryption Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Encrypt patient PHI fields
     *
     * @param array $patient_data Patient data array
     * @return array Patient data with encrypted PHI fields
     * @since 1.0.0
     */
    public static function encrypt_patient_data($patient_data) {
        $phi_fields = self::get_phi_fields();
        
        foreach ($phi_fields as $field) {
            if (isset($patient_data[$field]) && !empty($patient_data[$field])) {
                $encrypted = self::encrypt($patient_data[$field]);
                if ($encrypted !== false) {
                    $patient_data[$field] = $encrypted;
                }
            }
        }
        
        return $patient_data;
    }

    /**
     * Decrypt patient PHI fields
     *
     * @param array $patient_data Patient data array
     * @return array Patient data with decrypted PHI fields
     * @since 1.0.0
     */
    public static function decrypt_patient_data($patient_data) {
        $phi_fields = self::get_phi_fields();
        
        foreach ($phi_fields as $field) {
            if (isset($patient_data[$field]) && !empty($patient_data[$field])) {
                $decrypted = self::decrypt($patient_data[$field]);
                if ($decrypted !== false) {
                    $patient_data[$field] = $decrypted;
                }
            }
        }
        
        return $patient_data;
    }

    /**
     * Get list of PHI (Protected Health Information) fields
     *
     * @return array
     * @since 1.0.0
     */
    private static function get_phi_fields() {
        return apply_filters('eye_book_phi_fields', array(
            'first_name',
            'last_name',
            'date_of_birth',
            'phone',
            'email',
            'address_line1',
            'address_line2',
            'emergency_contact_name',
            'emergency_contact_phone',
            'insurance_member_id',
            'medical_history',
            'allergies',
            'current_medications',
            'notes'
        ));
    }

    /**
     * Hash data for indexing (one-way)
     *
     * @param string $data Data to hash
     * @return string Hashed data
     * @since 1.0.0
     */
    public static function hash_for_index($data) {
        return hash('sha256', $data . wp_salt('logged_in'));
    }

    /**
     * Create searchable hash for patient lookup
     *
     * @param string $email Patient email
     * @param string $phone Patient phone
     * @return string Search hash
     * @since 1.0.0
     */
    public static function create_patient_search_hash($email, $phone) {
        $search_string = strtolower($email) . '|' . preg_replace('/[^0-9]/', '', $phone);
        return self::hash_for_index($search_string);
    }

    /**
     * Encrypt form data
     *
     * @param array $form_data Form data to encrypt
     * @return array Encrypted form data
     * @since 1.0.0
     */
    public static function encrypt_form_data($form_data) {
        if (!is_array($form_data)) {
            return $form_data;
        }

        $encrypted_data = array();
        
        foreach ($form_data as $key => $value) {
            if (is_array($value)) {
                $encrypted_data[$key] = self::encrypt_form_data($value);
            } else {
                $encrypted_data[$key] = $value;
            }
        }

        // Encrypt the entire form data as JSON
        $json_data = wp_json_encode($encrypted_data);
        return self::encrypt($json_data);
    }

    /**
     * Decrypt form data
     *
     * @param string $encrypted_form_data Encrypted form data
     * @return array Decrypted form data
     * @since 1.0.0
     */
    public static function decrypt_form_data($encrypted_form_data) {
        if (empty($encrypted_form_data)) {
            return array();
        }

        $decrypted_json = self::decrypt($encrypted_form_data);
        
        if ($decrypted_json === false) {
            return array();
        }

        $form_data = json_decode($decrypted_json, true);
        
        return is_array($form_data) ? $form_data : array();
    }

    /**
     * Secure data deletion
     *
     * @param string $data Data to securely delete from memory
     * @since 1.0.0
     */
    public static function secure_zero($data) {
        if (is_string($data)) {
            $length = strlen($data);
            for ($i = 0; $i < $length; $i++) {
                $data[$i] = "\0";
            }
        }
    }

    /**
     * Generate secure token for patient portal access
     *
     * @param int $patient_id Patient ID
     * @param int $expiry_hours Hours until token expires
     * @return string Secure access token
     * @since 1.0.0
     */
    public static function generate_patient_token($patient_id, $expiry_hours = 24) {
        $expiry = time() + ($expiry_hours * HOUR_IN_SECONDS);
        
        $token_data = array(
            'patient_id' => $patient_id,
            'expiry' => $expiry,
            'random' => wp_generate_password(16, true, true)
        );
        
        $token = base64_encode(wp_json_encode($token_data));
        $signed_token = hash_hmac('sha256', $token, wp_salt('auth')) . '.' . $token;
        
        return $signed_token;
    }

    /**
     * Verify patient access token
     *
     * @param string $token Patient access token
     * @return int|false Patient ID if valid, false otherwise
     * @since 1.0.0
     */
    public static function verify_patient_token($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 2) {
            return false;
        }
        
        list($signature, $payload) = $parts;
        
        // Verify signature
        $expected_signature = hash_hmac('sha256', $payload, wp_salt('auth'));
        if (!hash_equals($signature, $expected_signature)) {
            return false;
        }
        
        // Decode payload
        $token_data = json_decode(base64_decode($payload), true);
        
        if (!$token_data || !isset($token_data['patient_id'], $token_data['expiry'])) {
            return false;
        }
        
        // Check expiry
        if (time() > $token_data['expiry']) {
            return false;
        }
        
        return intval($token_data['patient_id']);
    }

    /**
     * Encrypt database field value
     *
     * @param string $value Value to encrypt
     * @param string $table Table name
     * @param string $field Field name
     * @return string Encrypted value
     * @since 1.0.0
     */
    public static function encrypt_field($value, $table, $field) {
        // Check if this field should be encrypted
        $encrypted_fields = self::get_encrypted_fields();
        
        if (!isset($encrypted_fields[$table]) || !in_array($field, $encrypted_fields[$table])) {
            return $value;
        }
        
        return self::encrypt($value);
    }

    /**
     * Decrypt database field value
     *
     * @param string $encrypted_value Encrypted value
     * @param string $table Table name
     * @param string $field Field name
     * @return string Decrypted value
     * @since 1.0.0
     */
    public static function decrypt_field($encrypted_value, $table, $field) {
        // Check if this field should be decrypted
        $encrypted_fields = self::get_encrypted_fields();
        
        if (!isset($encrypted_fields[$table]) || !in_array($field, $encrypted_fields[$table])) {
            return $encrypted_value;
        }
        
        return self::decrypt($encrypted_value);
    }

    /**
     * Get configuration of which fields should be encrypted
     *
     * @return array
     * @since 1.0.0
     */
    private static function get_encrypted_fields() {
        return apply_filters('eye_book_encrypted_fields', array(
            'patients' => array(
                'first_name',
                'last_name',
                'date_of_birth',
                'phone',
                'email',
                'address_line1',
                'address_line2',
                'emergency_contact_name',
                'emergency_contact_phone',
                'insurance_member_id',
                'medical_history',
                'allergies',
                'current_medications',
                'notes'
            ),
            'patient_forms' => array(
                'form_data'
            ),
            'appointments' => array(
                'notes',
                'internal_notes',
                'chief_complaint'
            )
        ));
    }

    /**
     * Key rotation - generate new encryption key and re-encrypt data
     *
     * @return bool Success status
     * @since 1.0.0
     */
    public static function rotate_encryption_key() {
        global $wpdb;
        
        // Get current key
        $old_key = get_option('eye_book_encryption_key');
        
        if (empty($old_key)) {
            return false;
        }
        
        // Generate new key
        $new_key = self::generate_key();
        
        // Begin transaction
        $wpdb->query('START TRANSACTION');
        
        try {
            // Re-encrypt all encrypted data with new key
            $encrypted_fields = self::get_encrypted_fields();
            
            foreach ($encrypted_fields as $table => $fields) {
                $full_table_name = $wpdb->prefix . 'eye_book_' . $table;
                
                foreach ($fields as $field) {
                    // Get all records with encrypted data in this field
                    $records = $wpdb->get_results($wpdb->prepare(
                        "SELECT id, $field FROM $full_table_name WHERE $field IS NOT NULL AND $field != ''"
                    ));
                    
                    foreach ($records as $record) {
                        // Decrypt with old key
                        $old_encryption_key = $old_key;
                        update_option('eye_book_encryption_key', $old_encryption_key, false);
                        $decrypted = self::decrypt($record->$field);
                        
                        // Encrypt with new key
                        update_option('eye_book_encryption_key', $new_key, false);
                        $re_encrypted = self::encrypt($decrypted);
                        
                        // Update record
                        $wpdb->update(
                            $full_table_name,
                            array($field => $re_encrypted),
                            array('id' => $record->id)
                        );
                        
                        // Secure delete decrypted data
                        self::secure_zero($decrypted);
                    }
                }
            }
            
            // Commit transaction
            $wpdb->query('COMMIT');
            
            // Log key rotation
            Eye_Book_Audit::log('encryption_key_rotated', 'security', null, array(
                'old_key_hash' => hash('sha256', $old_key),
                'new_key_hash' => hash('sha256', $new_key)
            ));
            
            return true;
            
        } catch (Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            
            // Restore old key
            update_option('eye_book_encryption_key', $old_key, false);
            
            error_log('Eye-Book Key Rotation Failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check encryption status and integrity
     *
     * @return array Status report
     * @since 1.0.0
     */
    public static function check_encryption_status() {
        $status = array(
            'key_exists' => false,
            'openssl_available' => extension_loaded('openssl'),
            'method_available' => false,
            'test_encrypt_decrypt' => false,
            'encrypted_records_count' => 0
        );
        
        // Check if encryption key exists
        $key = get_option('eye_book_encryption_key');
        $status['key_exists'] = !empty($key);
        
        // Check if encryption method is available
        if ($status['openssl_available']) {
            $status['method_available'] = in_array(self::$method, openssl_get_cipher_methods());
        }
        
        // Test encryption/decryption
        if ($status['key_exists'] && $status['method_available']) {
            $test_data = 'Test encryption data: ' . wp_generate_password(20);
            $encrypted = self::encrypt($test_data);
            $decrypted = self::decrypt($encrypted);
            
            $status['test_encrypt_decrypt'] = ($test_data === $decrypted);
        }
        
        // Count encrypted records
        global $wpdb;
        $encrypted_fields = self::get_encrypted_fields();
        
        foreach ($encrypted_fields as $table => $fields) {
            $full_table_name = $wpdb->prefix . 'eye_book_' . $table;
            
            foreach ($fields as $field) {
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $full_table_name WHERE $field IS NOT NULL AND $field != ''"
                ));
                
                $status['encrypted_records_count'] += intval($count);
            }
        }
        
        return $status;
    }
}