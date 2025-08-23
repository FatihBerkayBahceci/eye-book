<?php
/**
 * Comprehensive Audit Trail system for Eye-Book plugin
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
 * Eye_Book_Audit_Trail Class
 *
 * Enhanced audit trail system for HIPAA compliance
 *
 * @class Eye_Book_Audit_Trail
 * @since 1.0.0
 */
class Eye_Book_Audit_Trail {

    /**
     * Audit event types
     *
     * @var array
     * @since 1.0.0
     */
    const EVENT_TYPES = array(
        'user_login' => 'User Login',
        'user_logout' => 'User Logout',
        'user_failed_login' => 'Failed Login Attempt',
        'user_created' => 'User Created',
        'user_updated' => 'User Updated',
        'user_deleted' => 'User Deleted',
        'patient_viewed' => 'Patient Record Viewed',
        'patient_created' => 'Patient Created',
        'patient_updated' => 'Patient Updated',
        'patient_deleted' => 'Patient Deleted',
        'patient_phi_accessed' => 'PHI Data Accessed',
        'appointment_created' => 'Appointment Created',
        'appointment_updated' => 'Appointment Updated',
        'appointment_cancelled' => 'Appointment Cancelled',
        'appointment_deleted' => 'Appointment Deleted',
        'appointment_checked_in' => 'Patient Checked In',
        'provider_schedule_viewed' => 'Provider Schedule Viewed',
        'provider_updated' => 'Provider Updated',
        'payment_processed' => 'Payment Processed',
        'payment_refunded' => 'Payment Refunded',
        'form_submitted' => 'Form Submitted',
        'form_viewed' => 'Form Viewed',
        'report_generated' => 'Report Generated',
        'data_exported' => 'Data Exported',
        'settings_updated' => 'Settings Updated',
        'database_backup' => 'Database Backup',
        'data_breach_detected' => 'Potential Data Breach',
        'unauthorized_access' => 'Unauthorized Access Attempt',
        'api_request' => 'API Request',
        'bulk_operation' => 'Bulk Operation',
        'system_error' => 'System Error'
    );

    /**
     * Risk levels
     *
     * @var array
     * @since 1.0.0
     */
    const RISK_LEVELS = array(
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
        'critical' => 'Critical'
    );

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('init', array($this, 'init_hooks'));
        add_action('wp_login', array($this, 'log_user_login'), 10, 2);
        add_action('wp_logout', array($this, 'log_user_logout'));
        add_action('wp_login_failed', array($this, 'log_failed_login'));
        add_action('user_register', array($this, 'log_user_registration'));
        add_action('profile_update', array($this, 'log_user_update'));
        add_action('delete_user', array($this, 'log_user_deletion'));
        
        // Schedule cleanup task
        if (!wp_next_scheduled('eye_book_audit_cleanup')) {
            wp_schedule_event(time(), 'daily', 'eye_book_audit_cleanup');
        }
        add_action('eye_book_audit_cleanup', array($this, 'cleanup_old_logs'));
        
        // Real-time security monitoring
        add_action('init', array($this, 'monitor_suspicious_activity'));
    }

    /**
     * Initialize hooks
     *
     * @since 1.0.0
     */
    public function init_hooks() {
        // Create audit table if not exists
        $this->create_audit_table();
        
        // Monitor PHI access
        add_action('eye_book_phi_accessed', array($this, 'log_phi_access'), 10, 3);
        
        // Monitor appointment activities
        add_action('eye_book_appointment_created', array($this, 'log_appointment_created'), 10, 2);
        add_action('eye_book_appointment_updated', array($this, 'log_appointment_updated'), 10, 3);
        add_action('eye_book_appointment_cancelled', array($this, 'log_appointment_cancelled'), 10, 2);
        
        // Monitor payment activities
        add_action('eye_book_payment_processed', array($this, 'log_payment_processed'), 10, 2);
        add_action('eye_book_payment_refunded', array($this, 'log_payment_refunded'), 10, 3);
        
        // Monitor data exports
        add_action('eye_book_data_exported', array($this, 'log_data_export'), 10, 3);
        
        // Monitor setting changes
        add_action('updated_option', array($this, 'monitor_setting_changes'), 10, 3);
    }

    /**
     * Create comprehensive audit log entry
     *
     * @param string $event_type
     * @param string $object_type
     * @param int $object_id
     * @param array $details
     * @param string $risk_level
     * @return int|false Audit log ID on success, false on failure
     * @since 1.0.0
     */
    public function log_event($event_type, $object_type = '', $object_id = null, $details = array(), $risk_level = 'low') {
        global $wpdb;

        $user_id = get_current_user_id();
        $ip_address = $this->get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $session_id = $this->get_session_id();
        
        // Enhanced details
        $enhanced_details = array_merge($details, array(
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'http_method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'referer' => $_SERVER['HTTP_REFERER'] ?? '',
            'server_name' => $_SERVER['SERVER_NAME'] ?? '',
            'php_session_id' => session_id(),
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => EYE_BOOK_VERSION
        ));

        $table_name = $wpdb->prefix . 'eye_book_audit_log';
        
        $result = $wpdb->insert($table_name, array(
            'event_type' => $event_type,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'user_id' => $user_id,
            'session_id' => $session_id,
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'risk_level' => $risk_level,
            'event_details' => wp_json_encode($enhanced_details),
            'created_at' => current_time('mysql', true),
            'hash' => $this->generate_integrity_hash($event_type, $object_type, $object_id, $enhanced_details)
        ), array(
            '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
        ));

        if ($result === false) {
            error_log('Eye-Book Audit: Failed to log event - ' . $wpdb->last_error);
            return false;
        }

        $audit_id = $wpdb->insert_id;

        // Check for suspicious patterns
        $this->analyze_event_patterns($event_type, $user_id, $ip_address);
        
        // Send real-time alerts for high-risk events
        if (in_array($risk_level, array('high', 'critical'))) {
            $this->send_security_alert($event_type, $enhanced_details, $risk_level);
        }

        return $audit_id;
    }

    /**
     * Log user login
     *
     * @param string $user_login
     * @param WP_User $user
     * @since 1.0.0
     */
    public function log_user_login($user_login, $user) {
        $this->log_event('user_login', 'user', $user->ID, array(
            'username' => $user_login,
            'user_email' => $user->user_email,
            'user_roles' => $user->roles,
            'login_time' => current_time('mysql', true)
        ), 'low');
    }

    /**
     * Log user logout
     *
     * @since 1.0.0
     */
    public function log_user_logout() {
        $user = wp_get_current_user();
        if ($user->ID) {
            $this->log_event('user_logout', 'user', $user->ID, array(
                'username' => $user->user_login,
                'logout_time' => current_time('mysql', true)
            ), 'low');
        }
    }

    /**
     * Log failed login attempts
     *
     * @param string $username
     * @since 1.0.0
     */
    public function log_failed_login($username) {
        $this->log_event('user_failed_login', 'user', null, array(
            'attempted_username' => $username,
            'failure_reason' => 'Invalid credentials',
            'attempt_time' => current_time('mysql', true)
        ), 'medium');
        
        // Check for brute force patterns
        $this->check_brute_force_attempts($username);
    }

    /**
     * Log PHI data access
     *
     * @param int $patient_id
     * @param string $data_type
     * @param array $accessed_fields
     * @since 1.0.0
     */
    public function log_phi_access($patient_id, $data_type, $accessed_fields = array()) {
        $patient = new Eye_Book_Patient($patient_id);
        
        $this->log_event('patient_phi_accessed', 'patient', $patient_id, array(
            'patient_name' => $patient->get_full_name(),
            'data_type' => $data_type,
            'accessed_fields' => $accessed_fields,
            'access_time' => current_time('mysql', true),
            'legitimate_purpose' => $this->determine_access_purpose()
        ), 'medium');
    }

    /**
     * Log appointment creation
     *
     * @param int $appointment_id
     * @param array $appointment_data
     * @since 1.0.0
     */
    public function log_appointment_created($appointment_id, $appointment_data) {
        $this->log_event('appointment_created', 'appointment', $appointment_id, array(
            'patient_id' => $appointment_data['patient_id'],
            'provider_id' => $appointment_data['provider_id'],
            'appointment_date' => $appointment_data['start_datetime'],
            'booking_source' => $appointment_data['booking_source'] ?? 'admin'
        ), 'low');
    }

    /**
     * Log data export activities
     *
     * @param string $export_type
     * @param array $export_params
     * @param int $record_count
     * @since 1.0.0
     */
    public function log_data_export($export_type, $export_params, $record_count) {
        $this->log_event('data_exported', 'system', null, array(
            'export_type' => $export_type,
            'parameters' => $export_params,
            'record_count' => $record_count,
            'export_format' => $export_params['format'] ?? 'csv',
            'date_range' => array(
                'start' => $export_params['start_date'] ?? '',
                'end' => $export_params['end_date'] ?? ''
            )
        ), 'high');
    }

    /**
     * Monitor suspicious activity patterns
     *
     * @since 1.0.0
     */
    public function monitor_suspicious_activity() {
        // Multiple failed login attempts
        $this->detect_brute_force();
        
        // Unusual data access patterns
        $this->detect_unusual_access_patterns();
        
        // After-hours access
        $this->detect_after_hours_access();
        
        // Mass data operations
        $this->detect_mass_operations();
    }

    /**
     * Analyze event patterns for anomalies
     *
     * @param string $event_type
     * @param int $user_id
     * @param string $ip_address
     * @since 1.0.0
     */
    private function analyze_event_patterns($event_type, $user_id, $ip_address) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eye_book_audit_log';
        $time_window = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        // Check for rapid successive events
        $rapid_events = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE event_type = %s AND user_id = %d AND created_at > %s",
            $event_type, $user_id, $time_window
        ));
        
        if ($rapid_events > 50) {
            $this->log_event('suspicious_activity_detected', 'security', null, array(
                'pattern_type' => 'rapid_events',
                'event_type' => $event_type,
                'event_count' => $rapid_events,
                'time_window' => '1 hour',
                'user_id' => $user_id
            ), 'high');
        }
        
        // Check for unusual IP patterns
        $ip_events = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT ip_address) FROM $table_name 
             WHERE user_id = %d AND created_at > %s",
            $user_id, date('Y-m-d H:i:s', strtotime('-24 hours'))
        ));
        
        if ($ip_events > 5) {
            $this->log_event('suspicious_activity_detected', 'security', null, array(
                'pattern_type' => 'multiple_ip_addresses',
                'ip_count' => $ip_events,
                'time_window' => '24 hours',
                'user_id' => $user_id
            ), 'medium');
        }
    }

    /**
     * Send security alerts
     *
     * @param string $event_type
     * @param array $details
     * @param string $risk_level
     * @since 1.0.0
     */
    private function send_security_alert($event_type, $details, $risk_level) {
        $admin_emails = $this->get_security_admin_emails();
        
        if (empty($admin_emails)) {
            return;
        }
        
        $subject = sprintf('[EYE-BOOK SECURITY ALERT] %s - %s Risk', 
            self::EVENT_TYPES[$event_type] ?? $event_type,
            strtoupper($risk_level)
        );
        
        $message = $this->build_security_alert_message($event_type, $details, $risk_level);
        
        foreach ($admin_emails as $email) {
            wp_mail($email, $subject, $message, array(
                'Content-Type: text/html; charset=UTF-8',
                'X-Priority: 1'
            ));
        }
        
        // Also log the alert sending
        $this->log_event('security_alert_sent', 'system', null, array(
            'alert_type' => $event_type,
            'risk_level' => $risk_level,
            'recipients' => count($admin_emails),
            'alert_details' => $details
        ), 'low');
    }

    /**
     * Get comprehensive audit trail report
     *
     * @param array $filters
     * @return array
     * @since 1.0.0
     */
    public function get_audit_report($filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eye_book_audit_log';
        $where_conditions = array('1=1');
        $params = array();
        
        // Apply filters
        if (!empty($filters['start_date'])) {
            $where_conditions[] = 'created_at >= %s';
            $params[] = $filters['start_date'] . ' 00:00:00';
        }
        
        if (!empty($filters['end_date'])) {
            $where_conditions[] = 'created_at <= %s';
            $params[] = $filters['end_date'] . ' 23:59:59';
        }
        
        if (!empty($filters['event_type'])) {
            $where_conditions[] = 'event_type = %s';
            $params[] = $filters['event_type'];
        }
        
        if (!empty($filters['user_id'])) {
            $where_conditions[] = 'user_id = %d';
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['risk_level'])) {
            $where_conditions[] = 'risk_level = %s';
            $params[] = $filters['risk_level'];
        }
        
        if (!empty($filters['ip_address'])) {
            $where_conditions[] = 'ip_address = %s';
            $params[] = $filters['ip_address'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        $limit = isset($filters['limit']) ? intval($filters['limit']) : 1000;
        $offset = isset($filters['offset']) ? intval($filters['offset']) : 0;
        
        // Get audit logs
        $query = "SELECT al.*, u.user_login, u.display_name 
                  FROM $table_name al 
                  LEFT JOIN {$wpdb->users} u ON al.user_id = u.ID 
                  WHERE $where_clause 
                  ORDER BY al.created_at DESC 
                  LIMIT %d OFFSET %d";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $logs = $wpdb->get_results($wpdb->prepare($query, $params));
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
        $total_count = $wpdb->get_var($params ? $wpdb->prepare($count_query, array_slice($params, 0, -2)) : $count_query);
        
        return array(
            'logs' => $logs,
            'total_count' => $total_count,
            'filters_applied' => $filters
        );
    }

    /**
     * Verify audit log integrity
     *
     * @param int $log_id
     * @return bool
     * @since 1.0.0
     */
    public function verify_log_integrity($log_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eye_book_audit_log';
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d", $log_id
        ));
        
        if (!$log) {
            return false;
        }
        
        $details = json_decode($log->event_details, true);
        $calculated_hash = $this->generate_integrity_hash(
            $log->event_type, $log->object_type, $log->object_id, $details
        );
        
        return hash_equals($log->hash, $calculated_hash);
    }

    /**
     * Create audit table
     *
     * @since 1.0.0
     */
    private function create_audit_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eye_book_audit_log';
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            object_type varchar(50) DEFAULT NULL,
            object_id bigint(20) DEFAULT NULL,
            user_id bigint(20) DEFAULT NULL,
            session_id varchar(255) DEFAULT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text DEFAULT NULL,
            risk_level varchar(20) NOT NULL DEFAULT 'low',
            event_details longtext DEFAULT NULL,
            hash varchar(64) NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY object_type (object_type),
            KEY object_id (object_id),
            KEY user_id (user_id),
            KEY ip_address (ip_address),
            KEY risk_level (risk_level),
            KEY created_at (created_at),
            KEY hash (hash)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Generate integrity hash
     *
     * @param string $event_type
     * @param string $object_type
     * @param int $object_id
     * @param array $details
     * @return string
     * @since 1.0.0
     */
    private function generate_integrity_hash($event_type, $object_type, $object_id, $details) {
        $data = array(
            'event_type' => $event_type,
            'object_type' => $object_type,
            'object_id' => $object_id,
            'details' => $details,
            'timestamp' => microtime(true),
            'salt' => wp_salt('auth')
        );
        
        return hash('sha256', serialize($data));
    }

    /**
     * Get client IP address
     *
     * @return string
     * @since 1.0.0
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, 
                        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Get session ID
     *
     * @return string
     * @since 1.0.0
     */
    private function get_session_id() {
        if (!session_id()) {
            session_start();
        }
        return session_id();
    }

    /**
     * Get security admin emails
     *
     * @return array
     * @since 1.0.0
     */
    private function get_security_admin_emails() {
        $emails = get_option('eye_book_security_admin_emails', array());
        
        if (empty($emails)) {
            // Fallback to admin email
            $emails = array(get_option('admin_email'));
        }
        
        return array_filter($emails);
    }

    /**
     * Build security alert message
     *
     * @param string $event_type
     * @param array $details
     * @param string $risk_level
     * @return string
     * @since 1.0.0
     */
    private function build_security_alert_message($event_type, $details, $risk_level) {
        $message = '<html><body>';
        $message .= '<h2>Security Alert - Eye-Book Plugin</h2>';
        $message .= '<p><strong>Event:</strong> ' . (self::EVENT_TYPES[$event_type] ?? $event_type) . '</p>';
        $message .= '<p><strong>Risk Level:</strong> ' . strtoupper($risk_level) . '</p>';
        $message .= '<p><strong>Time:</strong> ' . current_time('mysql') . '</p>';
        $message .= '<p><strong>IP Address:</strong> ' . $this->get_client_ip() . '</p>';
        $message .= '<p><strong>User Agent:</strong> ' . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . '</p>';
        
        if (!empty($details)) {
            $message .= '<h3>Event Details:</h3>';
            $message .= '<ul>';
            foreach ($details as $key => $value) {
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                }
                $message .= '<li><strong>' . ucfirst(str_replace('_', ' ', $key)) . ':</strong> ' . esc_html($value) . '</li>';
            }
            $message .= '</ul>';
        }
        
        $message .= '<p><em>This is an automated security alert from the Eye-Book plugin.</em></p>';
        $message .= '</body></html>';
        
        return $message;
    }

    /**
     * Cleanup old audit logs
     *
     * @since 1.0.0
     */
    public function cleanup_old_logs() {
        global $wpdb;
        
        $retention_days = get_option('eye_book_audit_retention_days', 2555); // 7 years default
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$retention_days days"));
        
        $table_name = $wpdb->prefix . 'eye_book_audit_log';
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name WHERE created_at < %s",
            $cutoff_date
        ));
        
        if ($deleted > 0) {
            $this->log_event('audit_logs_cleaned', 'system', null, array(
                'deleted_count' => $deleted,
                'cutoff_date' => $cutoff_date,
                'retention_days' => $retention_days
            ), 'low');
        }
    }

    /**
     * Check for brute force attempts
     *
     * @param string $username
     * @since 1.0.0
     */
    private function check_brute_force_attempts($username) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'eye_book_audit_log';
        $ip_address = $this->get_client_ip();
        $time_window = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        
        $failed_attempts = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE event_type = 'user_failed_login' 
             AND ip_address = %s AND created_at > %s",
            $ip_address, $time_window
        ));
        
        if ($failed_attempts >= 5) {
            $this->log_event('brute_force_detected', 'security', null, array(
                'attempted_username' => $username,
                'failed_attempts' => $failed_attempts,
                'time_window' => '15 minutes',
                'ip_address' => $ip_address
            ), 'critical');
        }
    }

    /**
     * Detect unusual access patterns
     *
     * @since 1.0.0
     */
    private function detect_unusual_access_patterns() {
        // Implementation for detecting unusual PHI access patterns
    }

    /**
     * Detect after-hours access
     *
     * @since 1.0.0
     */
    private function detect_after_hours_access() {
        $current_hour = date('H');
        $business_start = get_option('eye_book_business_hours_start', '08');
        $business_end = get_option('eye_book_business_hours_end', '18');
        
        if ($current_hour < $business_start || $current_hour > $business_end) {
            $user_id = get_current_user_id();
            if ($user_id && !current_user_can('administrator')) {
                $this->log_event('after_hours_access', 'security', null, array(
                    'access_time' => current_time('mysql'),
                    'user_id' => $user_id,
                    'business_hours' => "$business_start:00 - $business_end:00"
                ), 'medium');
            }
        }
    }

    /**
     * Detect mass operations
     *
     * @since 1.0.0
     */
    private function detect_mass_operations() {
        // Implementation for detecting mass data operations
    }

    /**
     * Determine access purpose
     *
     * @return string
     * @since 1.0.0
     */
    private function determine_access_purpose() {
        // Analyze request context to determine legitimate business purpose
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        
        if (strpos($uri, 'appointment') !== false) {
            return 'appointment_management';
        } elseif (strpos($uri, 'patient') !== false) {
            return 'patient_care';
        } elseif (strpos($uri, 'report') !== false) {
            return 'reporting';
        }
        
        return 'general_access';
    }

    /**
     * Monitor setting changes
     *
     * @param string $option
     * @param mixed $old_value
     * @param mixed $value
     * @since 1.0.0
     */
    public function monitor_setting_changes($option, $old_value, $value) {
        if (strpos($option, 'eye_book_') === 0) {
            $this->log_event('settings_updated', 'settings', null, array(
                'option_name' => $option,
                'old_value' => $this->sanitize_setting_value($old_value),
                'new_value' => $this->sanitize_setting_value($value)
            ), 'medium');
        }
    }

    /**
     * Sanitize setting value for logging
     *
     * @param mixed $value
     * @return mixed
     * @since 1.0.0
     */
    private function sanitize_setting_value($value) {
        if (is_string($value) && strlen($value) > 100) {
            return substr($value, 0, 100) . '...';
        }
        
        // Don't log sensitive values
        if (is_string($value) && (
            strpos($value, 'password') !== false ||
            strpos($value, 'secret') !== false ||
            strpos($value, 'key') !== false
        )) {
            return '[REDACTED]';
        }
        
        return $value;
    }

    /**
     * Detect brute force attacks
     *
     * @since 1.0.0
     */
    private function detect_brute_force() {
        // Implementation moved to check_brute_force_attempts
    }
}