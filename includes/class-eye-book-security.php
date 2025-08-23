<?php
/**
 * Security management class for Eye-Book plugin
 *
 * @package EyeBook
 * @subpackage Security
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eye_Book_Security Class
 *
 * Handles security measures, HIPAA compliance, and access control
 *
 * @class Eye_Book_Security
 * @since 1.0.0
 */
class Eye_Book_Security {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize security hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        // Authentication and authorization
        add_action('wp_login', array($this, 'log_user_login'), 10, 2);
        add_action('wp_logout', array($this, 'log_user_logout'));
        add_action('wp_login_failed', array($this, 'log_failed_login'));
        
        // Session management
        add_action('init', array($this, 'init_session_security'));
        add_filter('auth_cookie_expiration', array($this, 'extend_auth_cookie'));
        
        // Data sanitization hooks
        add_filter('eye_book_sanitize_patient_data', array($this, 'sanitize_patient_data'));
        add_filter('eye_book_sanitize_appointment_data', array($this, 'sanitize_appointment_data'));
        
        // AJAX security
        add_action('wp_ajax_eye_book_*', array($this, 'verify_ajax_nonce'), 1);
        add_action('wp_ajax_nopriv_eye_book_*', array($this, 'verify_ajax_nonce'), 1);
        
        // Headers for security
        add_action('send_headers', array($this, 'add_security_headers'));
        
        // Prevent unauthorized access
        add_action('template_redirect', array($this, 'check_page_access'));
    }

    /**
     * Initialize session security measures
     *
     * @since 1.0.0
     */
    public function init_session_security() {
        if (!session_id() && !headers_sent()) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', is_ssl() ? 1 : 0);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
        }
        
        // Session timeout for HIPAA compliance (30 minutes)
        $timeout = apply_filters('eye_book_session_timeout', 1800);
        
        if (isset($_SESSION['eye_book_last_activity'])) {
            if (time() - $_SESSION['eye_book_last_activity'] > $timeout) {
                $this->destroy_session();
                wp_redirect(wp_login_url());
                exit;
            }
        }
        
        $_SESSION['eye_book_last_activity'] = time();
    }

    /**
     * Extend authentication cookie expiration for staff users
     *
     * @param int $expiration
     * @return int
     * @since 1.0.0
     */
    public function extend_auth_cookie($expiration) {
        $user = wp_get_current_user();
        
        if ($user && $this->is_eye_book_staff($user)) {
            // Extend to 8 hours for staff
            return 8 * HOUR_IN_SECONDS;
        }
        
        return $expiration;
    }

    /**
     * Log user login for audit trail
     *
     * @param string $user_login
     * @param WP_User $user
     * @since 1.0.0
     */
    public function log_user_login($user_login, $user) {
        if ($this->is_eye_book_staff($user)) {
            Eye_Book_Audit::log('user_login', 'authentication', $user->ID, array(
                'user_login' => $user_login,
                'user_role' => implode(',', $user->roles),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ));
        }
    }

    /**
     * Log user logout for audit trail
     *
     * @since 1.0.0
     */
    public function log_user_logout() {
        $user = wp_get_current_user();
        
        if ($user && $this->is_eye_book_staff($user)) {
            Eye_Book_Audit::log('user_logout', 'authentication', $user->ID, array(
                'user_login' => $user->user_login,
                'ip_address' => $this->get_client_ip()
            ));
        }
    }

    /**
     * Log failed login attempts
     *
     * @param string $username
     * @since 1.0.0
     */
    public function log_failed_login($username) {
        Eye_Book_Audit::log('login_failed', 'authentication', 0, array(
            'attempted_username' => $username,
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ));
        
        // Implement brute force protection
        $this->handle_failed_login_attempt();
    }

    /**
     * Handle failed login attempts and implement brute force protection
     *
     * @since 1.0.0
     */
    private function handle_failed_login_attempt() {
        $ip = $this->get_client_ip();
        $transient_key = 'eye_book_failed_login_' . md5($ip);
        
        $failed_attempts = get_transient($transient_key);
        $failed_attempts = $failed_attempts ? intval($failed_attempts) + 1 : 1;
        
        // Lock account after 5 failed attempts for 30 minutes
        $max_attempts = apply_filters('eye_book_max_login_attempts', 5);
        $lockout_duration = apply_filters('eye_book_lockout_duration', 1800);
        
        if ($failed_attempts >= $max_attempts) {
            set_transient('eye_book_lockout_' . md5($ip), true, $lockout_duration);
            
            Eye_Book_Audit::log('ip_locked', 'security', 0, array(
                'ip_address' => $ip,
                'failed_attempts' => $failed_attempts,
                'lockout_duration' => $lockout_duration
            ));
        }
        
        set_transient($transient_key, $failed_attempts, $lockout_duration);
    }

    /**
     * Check if IP is locked out
     *
     * @param string $ip
     * @return bool
     * @since 1.0.0
     */
    public function is_ip_locked($ip = null) {
        if (!$ip) {
            $ip = $this->get_client_ip();
        }
        
        return get_transient('eye_book_lockout_' . md5($ip)) !== false;
    }

    /**
     * Verify AJAX nonce for security
     *
     * @since 1.0.0
     */
    public function verify_ajax_nonce() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'eye_book_ajax_nonce')) {
            wp_die(__('Security check failed', 'eye-book'), 403);
        }
    }

    /**
     * Add security headers
     *
     * @since 1.0.0
     */
    public function add_security_headers() {
        if (strpos($_SERVER['REQUEST_URI'] ?? '', 'eye-book') !== false) {
            // HIPAA compliance headers
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            if (is_ssl()) {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
            
            // Cache control for sensitive data
            header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }

    /**
     * Check page access permissions
     *
     * @since 1.0.0
     */
    public function check_page_access() {
        global $wp;
        
        // Check if this is an Eye-Book page
        if (strpos($wp->request, 'eye-book') !== false) {
            if (!is_user_logged_in()) {
                wp_redirect(wp_login_url(home_url($wp->request)));
                exit;
            }
            
            // Check IP lockout
            if ($this->is_ip_locked()) {
                wp_die(__('Access denied. Too many failed login attempts.', 'eye-book'), 429);
            }
            
            // Check user permissions
            if (!$this->current_user_can_access()) {
                wp_die(__('Access denied. Insufficient permissions.', 'eye-book'), 403);
            }
        }
    }

    /**
     * Check if current user can access Eye-Book features
     *
     * @return bool
     * @since 1.0.0
     */
    public function current_user_can_access() {
        $user = wp_get_current_user();
        
        return $this->is_eye_book_staff($user) || current_user_can('manage_options');
    }

    /**
     * Check if user is Eye-Book staff
     *
     * @param WP_User $user
     * @return bool
     * @since 1.0.0
     */
    public function is_eye_book_staff($user) {
        if (!$user || !$user->ID) {
            return false;
        }
        
        $eye_book_roles = array(
            'eye_book_clinic_admin',
            'eye_book_doctor',
            'eye_book_nurse',
            'eye_book_receptionist'
        );
        
        return array_intersect($eye_book_roles, $user->roles) || in_array('administrator', $user->roles);
    }

    /**
     * Sanitize patient data for HIPAA compliance
     *
     * @param array $data
     * @return array
     * @since 1.0.0
     */
    public function sanitize_patient_data($data) {
        $sanitized = array();
        
        // Define field sanitization rules
        $sanitization_rules = array(
            'first_name' => 'sanitize_text_field',
            'last_name' => 'sanitize_text_field',
            'email' => 'sanitize_email',
            'phone' => array($this, 'sanitize_phone'),
            'date_of_birth' => array($this, 'sanitize_date'),
            'address_line1' => 'sanitize_text_field',
            'address_line2' => 'sanitize_text_field',
            'city' => 'sanitize_text_field',
            'state' => array($this, 'sanitize_state'),
            'zip_code' => array($this, 'sanitize_zip'),
            'insurance_member_id' => 'sanitize_text_field',
            'medical_history' => 'wp_kses_post',
            'allergies' => 'sanitize_textarea_field',
            'notes' => 'sanitize_textarea_field'
        );
        
        foreach ($data as $key => $value) {
            if (isset($sanitization_rules[$key])) {
                $sanitized[$key] = call_user_func($sanitization_rules[$key], $value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize appointment data
     *
     * @param array $data
     * @return array
     * @since 1.0.0
     */
    public function sanitize_appointment_data($data) {
        $sanitized = array();
        
        $sanitization_rules = array(
            'start_datetime' => array($this, 'sanitize_datetime'),
            'end_datetime' => array($this, 'sanitize_datetime'),
            'status' => array($this, 'sanitize_appointment_status'),
            'notes' => 'sanitize_textarea_field',
            'chief_complaint' => 'sanitize_textarea_field',
            'patient_id' => 'intval',
            'provider_id' => 'intval',
            'location_id' => 'intval',
            'appointment_type_id' => 'intval'
        );
        
        foreach ($data as $key => $value) {
            if (isset($sanitization_rules[$key])) {
                $sanitized[$key] = call_user_func($sanitization_rules[$key], $value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize phone number
     *
     * @param string $phone
     * @return string
     * @since 1.0.0
     */
    public function sanitize_phone($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Validate US phone number format
        if (strlen($phone) === 10) {
            return preg_replace('/(\d{3})(\d{3})(\d{4})/', '($1) $2-$3', $phone);
        } elseif (strlen($phone) === 11 && substr($phone, 0, 1) === '1') {
            return preg_replace('/1(\d{3})(\d{3})(\d{4})/', '1 ($1) $2-$3', $phone);
        }
        
        return $phone;
    }

    /**
     * Sanitize date input
     *
     * @param string $date
     * @return string|false
     * @since 1.0.0
     */
    public function sanitize_date($date) {
        $timestamp = strtotime($date);
        return $timestamp ? date('Y-m-d', $timestamp) : false;
    }

    /**
     * Sanitize datetime input
     *
     * @param string $datetime
     * @return string|false
     * @since 1.0.0
     */
    public function sanitize_datetime($datetime) {
        $timestamp = strtotime($datetime);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : false;
    }

    /**
     * Sanitize state abbreviation
     *
     * @param string $state
     * @return string
     * @since 1.0.0
     */
    public function sanitize_state($state) {
        $state = strtoupper(sanitize_text_field($state));
        
        $valid_states = array(
            'AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA',
            'HI', 'ID', 'IL', 'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD',
            'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV', 'NH', 'NJ',
            'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC',
            'SD', 'TN', 'TX', 'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY',
            'DC'
        );
        
        return in_array($state, $valid_states) ? $state : '';
    }

    /**
     * Sanitize ZIP code
     *
     * @param string $zip
     * @return string
     * @since 1.0.0
     */
    public function sanitize_zip($zip) {
        $zip = sanitize_text_field($zip);
        
        // US ZIP code format (12345 or 12345-6789)
        if (preg_match('/^\d{5}(-\d{4})?$/', $zip)) {
            return $zip;
        }
        
        return '';
    }

    /**
     * Sanitize appointment status
     *
     * @param string $status
     * @return string
     * @since 1.0.0
     */
    public function sanitize_appointment_status($status) {
        $valid_statuses = array(
            'scheduled', 'confirmed', 'checked_in', 'in_progress',
            'completed', 'cancelled', 'no_show', 'rescheduled'
        );
        
        $status = sanitize_text_field($status);
        return in_array($status, $valid_statuses) ? $status : 'scheduled';
    }

    /**
     * Get client IP address
     *
     * @return string
     * @since 1.0.0
     */
    public function get_client_ip() {
        $ip_fields = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_fields as $field) {
            if (!empty($_SERVER[$field])) {
                $ip = $_SERVER[$field];
                
                // Handle comma-separated IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Destroy user session
     *
     * @since 1.0.0
     */
    private function destroy_session() {
        if (session_id()) {
            session_destroy();
        }
        
        unset($_SESSION);
        
        // Clear WordPress auth cookies
        wp_clear_auth_cookie();
    }

    /**
     * Generate secure random token
     *
     * @param int $length
     * @return string
     * @since 1.0.0
     */
    public function generate_secure_token($length = 32) {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length / 2));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length / 2));
        } else {
            return wp_generate_password($length, true, true);
        }
    }

    /**
     * Hash sensitive data for storage
     *
     * @param string $data
     * @return string
     * @since 1.0.0
     */
    public function hash_data($data) {
        return hash('sha256', $data . wp_salt('logged_in'));
    }

    /**
     * Verify hashed data
     *
     * @param string $data
     * @param string $hash
     * @return bool
     * @since 1.0.0
     */
    public function verify_hash($data, $hash) {
        return hash_equals($hash, $this->hash_data($data));
    }
}