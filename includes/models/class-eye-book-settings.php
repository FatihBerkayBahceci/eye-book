<?php
/**
 * Settings Model
 *
 * @package EyeBook
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eye_Book_Settings Class
 *
 * Handles plugin settings operations
 *
 * @since 1.0.0
 */
class Eye_Book_Settings {

    /**
     * Table name
     *
     * @var string
     */
    private $table_name;

    /**
     * Settings cache
     *
     * @var array
     */
    private $settings_cache = array();

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = EYE_BOOK_TABLE_SETTINGS;
        $this->load_settings();
    }

    /**
     * Load all settings into cache
     *
     * @since 1.0.0
     */
    private function load_settings() {
        global $wpdb;
        
        $query = "SELECT setting_key, setting_value, setting_type FROM {$this->table_name}";
        $results = $wpdb->get_results($query);
        
        foreach ($results as $setting) {
            $this->settings_cache[$setting->setting_key] = $this->unserialize_setting($setting->setting_value, $setting->setting_type);
        }
    }

    /**
     * Get setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value if setting not found
     * @return mixed
     * @since 1.0.0
     */
    public function get($key, $default = null) {
        if (isset($this->settings_cache[$key])) {
            return $this->settings_cache[$key];
        }
        
        return $default;
    }

    /**
     * Set setting value
     *
     * @param string $key Setting key
     * @param mixed $value Setting value
     * @param string $type Setting type (string, int, float, bool, array, json)
     * @return bool|WP_Error
     * @since 1.0.0
     */
    public function set($key, $value, $type = 'string') {
        if (empty($key)) {
            return new WP_Error('invalid_key', __('Setting key is required.', 'eye-book'));
        }

        // Sanitize key
        $key = sanitize_key($key);
        
        // Determine type if not specified
        if ($type === 'auto') {
            $type = $this->determine_type($value);
        }

        // Serialize value based on type
        $serialized_value = $this->serialize_setting($value, $type);
        
        global $wpdb;
        
        // Check if setting exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} WHERE setting_key = %s",
            $key
        ));

        if ($existing) {
            // Update existing setting
            $result = $wpdb->update(
                $this->table_name,
                array(
                    'setting_value' => $serialized_value,
                    'setting_type' => $type,
                    'updated_at' => current_time('mysql', true)
                ),
                array('setting_key' => $key),
                array('%s', '%s', '%s'),
                array('%s')
            );
        } else {
            // Insert new setting
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'setting_key' => $key,
                    'setting_value' => $serialized_value,
                    'setting_type' => $type,
                    'created_at' => current_time('mysql', true),
                    'updated_at' => current_time('mysql', true)
                ),
                array('%s', '%s', '%s', '%s', '%s')
            );
        }

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to save setting.', 'eye-book'));
        }

        // Update cache
        $this->settings_cache[$key] = $value;

        // Log setting change
        if (class_exists('Eye_Book_Audit')) {
            Eye_Book_Audit::log('setting_updated', 'settings', 0, array(
                'key' => $key,
                'type' => $type
            ));
        }

        return true;
    }

    /**
     * Delete setting
     *
     * @param string $key Setting key
     * @return bool|WP_Error
     * @since 1.0.0
     */
    public function delete($key) {
        if (empty($key)) {
            return new WP_Error('invalid_key', __('Setting key is required.', 'eye-book'));
        }

        $key = sanitize_key($key);
        
        global $wpdb;
        $result = $wpdb->delete(
            $this->table_name,
            array('setting_key' => $key),
            array('%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to delete setting.', 'eye-book'));
        }

        // Remove from cache
        unset($this->settings_cache[$key]);

        // Log deletion
        if (class_exists('Eye_Book_Audit')) {
            Eye_Book_Audit::log('setting_deleted', 'settings', 0, array(
                'key' => $key
            ));
        }

        return true;
    }

    /**
     * Get multiple settings
     *
     * @param array $keys Array of setting keys
     * @return array
     * @since 1.0.0
     */
    public function get_multiple($keys) {
        $settings = array();
        
        foreach ($keys as $key) {
            $settings[$key] = $this->get($key);
        }
        
        return $settings;
    }

    /**
     * Set multiple settings
     *
     * @param array $settings Array of settings (key => value)
     * @return array Array of results
     * @since 1.0.0
     */
    public function set_multiple($settings) {
        $results = array();
        
        foreach ($settings as $key => $value) {
            $results[$key] = $this->set($key, $value);
        }
        
        return $results;
    }

    /**
     * Get all settings
     *
     * @return array
     * @since 1.0.0
     */
    public function get_all() {
        return $this->settings_cache;
    }

    /**
     * Get settings by prefix
     *
     * @param string $prefix Setting key prefix
     * @return array
     * @since 1.0.0
     */
    public function get_by_prefix($prefix) {
        $filtered = array();
        
        foreach ($this->settings_cache as $key => $value) {
            if (strpos($key, $prefix) === 0) {
                $filtered[$key] = $value;
            }
        }
        
        return $filtered;
    }

    /**
     * Check if setting exists
     *
     * @param string $key Setting key
     * @return bool
     * @since 1.0.0
     */
    public function exists($key) {
        return isset($this->settings_cache[$key]);
    }

    /**
     * Get default settings
     *
     * @return array
     * @since 1.0.0
     */
    public function get_defaults() {
        return array(
            // General settings
            'booking_enabled' => 1,
            'booking_advance_days' => 30,
            'booking_cancellation_hours' => 24,
            'appointment_duration' => 30,
            'timezone' => get_option('timezone_string', 'America/New_York'),
            'date_format' => get_option('date_format', 'F j, Y'),
            'time_format' => get_option('time_format', 'g:i a'),
            
            // Notification settings
            'reminder_email_hours' => 24,
            'reminder_sms_hours' => 2,
            'confirmation_email' => 1,
            'cancellation_email' => 1,
            'no_show_email' => 1,
            
            // HIPAA and security settings
            'hipaa_compliance_mode' => 1,
            'audit_retention_days' => 2555, // 7 years
            'encryption_enabled' => 1,
            'session_timeout_minutes' => 30,
            'max_login_attempts' => 5,
            'lockout_duration_minutes' => 30,
            
            // Payment settings
            'payment_enabled' => 0,
            'payment_gateway' => 'stripe',
            'stripe_publishable_key' => '',
            'stripe_secret_key' => '',
            'square_application_id' => '',
            'square_access_token' => '',
            
            // SMS settings
            'sms_enabled' => 0,
            'twilio_account_sid' => '',
            'twilio_auth_token' => '',
            'twilio_phone_number' => '',
            
            // Form settings
            'forms_enabled' => 1,
            'default_intake_form' => '',
            'forms_encryption' => 1,
            
            // API settings
            'api_enabled' => 1,
            'api_rate_limit' => 100,
            'api_key_expiry_days' => 365,
            
            // Backup settings
            'backup_enabled' => 1,
            'backup_frequency' => 'daily',
            'backup_retention_days' => 90,
            'backup_encryption' => 1
        );
    }

    /**
     * Reset settings to defaults
     *
     * @return bool|WP_Error
     * @since 1.0.0
     */
    public function reset_to_defaults() {
        $defaults = $this->get_defaults();
        
        foreach ($defaults as $key => $value) {
            $result = $this->set($key, $value);
            if (is_wp_error($result)) {
                return $result;
            }
        }

        // Log reset
        if (class_exists('Eye_Book_Audit')) {
            Eye_Book_Audit::log('settings_reset_to_defaults', 'settings', 0);
        }

        return true;
    }

    /**
     * Export settings
     *
     * @return array
     * @since 1.0.0
     */
    public function export() {
        $export_data = array();
        
        foreach ($this->settings_cache as $key => $value) {
            // Skip sensitive settings
            if ($this->is_sensitive_setting($key)) {
                continue;
            }
            
            $export_data[$key] = $value;
        }
        
        return $export_data;
    }

    /**
     * Import settings
     *
     * @param array $settings Settings to import
     * @param bool $overwrite Whether to overwrite existing settings
     * @return array Array of results
     * @since 1.0.0
     */
    public function import($settings, $overwrite = false) {
        if (!is_array($settings)) {
            return new WP_Error('invalid_data', __('Settings must be an array.', 'eye-book'));
        }

        $results = array();
        
        foreach ($settings as $key => $value) {
            if (!$overwrite && $this->exists($key)) {
                $results[$key] = new WP_Error('setting_exists', __('Setting already exists.', 'eye-book'));
                continue;
            }
            
            $results[$key] = $this->set($key, $value);
        }

        // Log import
        if (class_exists('Eye_Book_Audit')) {
            Eye_Book_Audit::log('settings_imported', 'settings', 0, array(
                'count' => count($settings),
                'overwrite' => $overwrite
            ));
        }

        return $results;
    }

    /**
     * Check if setting is sensitive
     *
     * @param string $key Setting key
     * @return bool
     * @since 1.0.0
     */
    private function is_sensitive_setting($key) {
        $sensitive_keys = array(
            'stripe_secret_key',
            'square_access_token',
            'twilio_auth_token',
            'encryption_key'
        );
        
        return in_array($key, $sensitive_keys);
    }

    /**
     * Determine setting type automatically
     *
     * @param mixed $value Setting value
     * @return string
     * @since 1.0.0
     */
    private function determine_type($value) {
        if (is_bool($value)) {
            return 'bool';
        } elseif (is_int($value)) {
            return 'int';
        } elseif (is_float($value)) {
            return 'float';
        } elseif (is_array($value)) {
            return 'array';
        } else {
            return 'string';
        }
    }

    /**
     * Serialize setting value
     *
     * @param mixed $value Setting value
     * @param string $type Setting type
     * @return string
     * @since 1.0.0
     */
    private function serialize_setting($value, $type) {
        switch ($type) {
            case 'bool':
                return $value ? '1' : '0';
            case 'int':
                return strval(intval($value));
            case 'float':
                return strval(floatval($value));
            case 'array':
            case 'json':
                return wp_json_encode($value);
            case 'string':
            default:
                return strval($value);
        }
    }

    /**
     * Unserialize setting value
     *
     * @param string $value Serialized value
     * @param string $type Setting type
     * @return mixed
     * @since 1.0.0
     */
    private function unserialize_setting($value, $type) {
        switch ($type) {
            case 'bool':
                return (bool) intval($value);
            case 'int':
                return intval($value);
            case 'float':
                return floatval($value);
            case 'array':
            case 'json':
                $decoded = json_decode($value, true);
                return $decoded !== null ? $decoded : $value;
            case 'string':
            default:
                return $value;
        }
    }
}
