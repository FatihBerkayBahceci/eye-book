<?php
/**
 * Audit logging class for Eye-Book plugin
 *
 * @package EyeBook
 * @subpackage Audit
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eye_Book_Audit Class
 *
 * Handles audit logging for HIPAA compliance
 *
 * @class Eye_Book_Audit
 * @since 1.0.0
 */
class Eye_Book_Audit {

    /**
     * Log an audit event
     *
     * @param string $action The action performed
     * @param string $object_type Type of object (patient, appointment, etc.)
     * @param int $object_id ID of the object
     * @param array $details Additional details about the action
     * @param int $user_id User ID (defaults to current user)
     * @return bool Success status
     * @since 1.0.0
     */
    public static function log($action, $object_type, $object_id = null, $details = array(), $user_id = null) {
        global $wpdb;

        // Get current user if not specified
        if ($user_id === null) {
            $user_id = get_current_user_id();
        }

        // Prepare audit data
        $audit_data = array(
            'user_id' => $user_id,
            'action' => sanitize_text_field($action),
            'object_type' => sanitize_text_field($object_type),
            'object_id' => $object_id ? intval($object_id) : null,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => self::get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'session_id' => session_id() ?: null,
            'timestamp' => current_time('mysql', true)
        );

        // Handle different types of details
        if (isset($details['old_values'])) {
            $audit_data['old_values'] = wp_json_encode(self::sanitize_audit_data($details['old_values']));
        }

        if (isset($details['new_values'])) {
            $audit_data['new_values'] = wp_json_encode(self::sanitize_audit_data($details['new_values']));
        }

        // Add custom fields from details
        $allowed_fields = array('ip_address', 'user_agent', 'session_id');
        foreach ($allowed_fields as $field) {
            if (isset($details[$field])) {
                $audit_data[$field] = $details[$field];
            }
        }

        // Insert audit log
        $result = $wpdb->insert(
            EYE_BOOK_TABLE_AUDIT_LOG,
            $audit_data,
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        if ($result === false) {
            error_log('Eye-Book Audit Log Error: ' . $wpdb->last_error);
            return false;
        }

        // Trigger audit log action for external integrations
        do_action('eye_book_audit_logged', $audit_data, $wpdb->insert_id);

        return true;
    }

    /**
     * Log patient data access
     *
     * @param int $patient_id
     * @param string $access_type (view, edit, create, delete)
     * @param array $accessed_fields
     * @since 1.0.0
     */
    public static function log_patient_access($patient_id, $access_type, $accessed_fields = array()) {
        self::log(
            'patient_' . $access_type,
            'patient',
            $patient_id,
            array(
                'accessed_fields' => $accessed_fields,
                'reason' => 'Clinical care'
            )
        );
    }

    /**
     * Log appointment actions
     *
     * @param int $appointment_id
     * @param string $action
     * @param array $old_data
     * @param array $new_data
     * @since 1.0.0
     */
    public static function log_appointment_action($appointment_id, $action, $old_data = array(), $new_data = array()) {
        $details = array();
        
        if (!empty($old_data)) {
            $details['old_values'] = $old_data;
        }
        
        if (!empty($new_data)) {
            $details['new_values'] = $new_data;
        }

        self::log(
            'appointment_' . $action,
            'appointment',
            $appointment_id,
            $details
        );
    }

    /**
     * Log data export events
     *
     * @param string $export_type
     * @param array $exported_data_info
     * @since 1.0.0
     */
    public static function log_data_export($export_type, $exported_data_info = array()) {
        self::log(
            'data_export',
            'system',
            null,
            array(
                'export_type' => $export_type,
                'data_info' => $exported_data_info,
                'compliance_reason' => 'Patient request or business requirement'
            )
        );
    }

    /**
     * Log security events
     *
     * @param string $event_type
     * @param array $event_details
     * @since 1.0.0
     */
    public static function log_security_event($event_type, $event_details = array()) {
        self::log(
            'security_' . $event_type,
            'security',
            null,
            $event_details
        );
    }

    /**
     * Log HIPAA breach incidents
     *
     * @param string $breach_type
     * @param array $breach_details
     * @since 1.0.0
     */
    public static function log_hipaa_breach($breach_type, $breach_details = array()) {
        // Log the breach
        self::log(
            'hipaa_breach',
            'security',
            null,
            array_merge($breach_details, array(
                'breach_type' => $breach_type,
                'severity' => $breach_details['severity'] ?? 'high',
                'requires_notification' => true
            ))
        );

        // Trigger breach notification workflow
        do_action('eye_book_hipaa_breach_detected', $breach_type, $breach_details);

        // Send immediate notification to admin
        self::notify_breach_detected($breach_type, $breach_details);
    }

    /**
     * Get audit logs with filtering
     *
     * @param array $args Query arguments
     * @return array Audit logs
     * @since 1.0.0
     */
    public static function get_audit_logs($args = array()) {
        global $wpdb;

        $defaults = array(
            'user_id' => null,
            'action' => null,
            'object_type' => null,
            'object_id' => null,
            'date_from' => null,
            'date_to' => null,
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'timestamp',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array('1=1');
        $where_values = array();

        // Build WHERE clause
        if ($args['user_id']) {
            $where_clauses[] = 'user_id = %d';
            $where_values[] = intval($args['user_id']);
        }

        if ($args['action']) {
            $where_clauses[] = 'action = %s';
            $where_values[] = $args['action'];
        }

        if ($args['object_type']) {
            $where_clauses[] = 'object_type = %s';
            $where_values[] = $args['object_type'];
        }

        if ($args['object_id']) {
            $where_clauses[] = 'object_id = %d';
            $where_values[] = intval($args['object_id']);
        }

        if ($args['date_from']) {
            $where_clauses[] = 'timestamp >= %s';
            $where_values[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where_clauses[] = 'timestamp <= %s';
            $where_values[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where_clauses);

        // Build ORDER BY clause
        $allowed_orderby = array('timestamp', 'user_id', 'action', 'object_type');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'timestamp';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';

        // Build query
        $query = "SELECT * FROM " . EYE_BOOK_TABLE_AUDIT_LOG . " 
                  WHERE $where_clause 
                  ORDER BY $orderby $order 
                  LIMIT %d OFFSET %d";

        $where_values[] = intval($args['limit']);
        $where_values[] = intval($args['offset']);

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return $wpdb->get_results($query);
    }

    /**
     * Get audit log statistics
     *
     * @param array $args Query arguments
     * @return array Statistics
     * @since 1.0.0
     */
    public static function get_audit_stats($args = array()) {
        global $wpdb;

        $defaults = array(
            'date_from' => date('Y-m-d', strtotime('-30 days')),
            'date_to' => date('Y-m-d H:i:s')
        );

        $args = wp_parse_args($args, $defaults);

        $stats = array();

        // Total logs count
        $stats['total_logs'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_AUDIT_LOG . " 
             WHERE timestamp BETWEEN %s AND %s",
            $args['date_from'],
            $args['date_to']
        ));

        // Actions breakdown
        $stats['actions'] = $wpdb->get_results($wpdb->prepare(
            "SELECT action, COUNT(*) as count 
             FROM " . EYE_BOOK_TABLE_AUDIT_LOG . " 
             WHERE timestamp BETWEEN %s AND %s 
             GROUP BY action 
             ORDER BY count DESC",
            $args['date_from'],
            $args['date_to']
        ));

        // User activity
        $stats['user_activity'] = $wpdb->get_results($wpdb->prepare(
            "SELECT u.display_name, COUNT(*) as count 
             FROM " . EYE_BOOK_TABLE_AUDIT_LOG . " a 
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
             WHERE a.timestamp BETWEEN %s AND %s 
             GROUP BY a.user_id 
             ORDER BY count DESC 
             LIMIT 10",
            $args['date_from'],
            $args['date_to']
        ));

        // Object type breakdown
        $stats['object_types'] = $wpdb->get_results($wpdb->prepare(
            "SELECT object_type, COUNT(*) as count 
             FROM " . EYE_BOOK_TABLE_AUDIT_LOG . " 
             WHERE timestamp BETWEEN %s AND %s 
             GROUP BY object_type 
             ORDER BY count DESC",
            $args['date_from'],
            $args['date_to']
        ));

        return $stats;
    }

    /**
     * Clean old audit logs based on retention policy
     *
     * @since 1.0.0
     */
    public static function cleanup_old_logs() {
        global $wpdb;

        $retention_days = get_option('eye_book_audit_retention_days', 2555); // 7 years default
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-$retention_days days"));

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM " . EYE_BOOK_TABLE_AUDIT_LOG . " WHERE timestamp < %s",
            $cutoff_date
        ));

        if ($deleted > 0) {
            self::log('audit_cleanup', 'system', null, array(
                'deleted_records' => $deleted,
                'cutoff_date' => $cutoff_date,
                'retention_days' => $retention_days
            ));
        }

        return $deleted;
    }

    /**
     * Export audit logs for compliance reporting
     *
     * @param array $args Export arguments
     * @return string File path of exported data
     * @since 1.0.0
     */
    public static function export_audit_logs($args = array()) {
        $logs = self::get_audit_logs($args);
        
        // Create CSV content
        $csv_content = "Timestamp,User,Action,Object Type,Object ID,IP Address,Details\n";
        
        foreach ($logs as $log) {
            $user = get_user_by('ID', $log->user_id);
            $user_name = $user ? $user->display_name : 'System';
            
            $details = '';
            if ($log->old_values || $log->new_values) {
                $details = 'Changed: ' . ($log->old_values ? 'From ' . $log->old_values : '') . 
                          ($log->new_values ? ' To ' . $log->new_values : '');
            }
            
            $csv_content .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $log->timestamp,
                $user_name,
                $log->action,
                $log->object_type,
                $log->object_id ?: '',
                $log->ip_address,
                str_replace('"', '""', $details)
            );
        }
        
        // Save to file
        $upload_dir = wp_upload_dir();
        $filename = 'audit-log-' . date('Y-m-d-H-i-s') . '.csv';
        $filepath = $upload_dir['path'] . '/' . $filename;
        
        file_put_contents($filepath, $csv_content);
        
        // Log the export
        self::log_data_export('audit_logs', array(
            'filename' => $filename,
            'record_count' => count($logs),
            'date_range' => array(
                'from' => $args['date_from'] ?? 'beginning',
                'to' => $args['date_to'] ?? 'now'
            )
        ));
        
        return $filepath;
    }

    /**
     * Sanitize audit data to remove sensitive information
     *
     * @param array $data
     * @return array
     * @since 1.0.0
     */
    private static function sanitize_audit_data($data) {
        if (!is_array($data)) {
            return $data;
        }

        $sensitive_fields = array(
            'password',
            'ssn',
            'social_security_number',
            'credit_card',
            'bank_account'
        );

        $sanitized = array();

        foreach ($data as $key => $value) {
            $key_lower = strtolower($key);
            
            if (in_array($key_lower, $sensitive_fields) || 
                strpos($key_lower, 'password') !== false ||
                strpos($key_lower, 'ssn') !== false) {
                $sanitized[$key] = '[REDACTED]';
            } else if (is_array($value)) {
                $sanitized[$key] = self::sanitize_audit_data($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Get client IP address
     *
     * @return string
     * @since 1.0.0
     */
    private static function get_client_ip() {
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
                
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Notify administrators of detected breach
     *
     * @param string $breach_type
     * @param array $breach_details
     * @since 1.0.0
     */
    private static function notify_breach_detected($breach_type, $breach_details) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');

        $subject = sprintf(__('[URGENT] HIPAA Breach Detected - %s', 'eye-book'), $site_name);
        
        $message = sprintf(
            __("A potential HIPAA breach has been detected on %s.\n\nBreach Type: %s\nTimestamp: %s\nDetails: %s\n\nImmediate action may be required. Please review the audit logs and contact your compliance officer.", 'eye-book'),
            $site_name,
            $breach_type,
            current_time('Y-m-d H:i:s'),
            wp_json_encode($breach_details)
        );

        wp_mail($admin_email, $subject, $message, array(
            'X-Priority: 1',
            'X-MSMail-Priority: High',
            'Importance: High'
        ));
    }
}