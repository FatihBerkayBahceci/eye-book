<?php
/**
 * Appointment Type Model
 *
 * @package EyeBook
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eye_Book_Appointment_Type Class
 *
 * Handles appointment type operations
 *
 * @since 1.0.0
 */
class Eye_Book_Appointment_Type {

    /**
     * Table name
     *
     * @var string
     */
    private $table_name;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = EYE_BOOK_TABLE_APPOINTMENT_TYPES;
    }

    /**
     * Create appointment type
     *
     * @param array $data Appointment type data
     * @return int|WP_Error
     * @since 1.0.0
     */
    public function create($data) {
        $defaults = array(
            'name' => '',
            'description' => '',
            'duration' => 30,
            'color' => '#007cba',
            'price' => 0.00,
            'requires_forms' => 0,
            'is_active' => 1,
            'created_at' => current_time('mysql', true),
            'updated_at' => current_time('mysql', true)
        );

        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['name'])) {
            return new WP_Error('invalid_name', __('Appointment type name is required.', 'eye-book'));
        }

        // Sanitize data
        $data = $this->sanitize_data($data);

        global $wpdb;
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array('%s', '%s', '%d', '%s', '%f', '%d', '%d', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create appointment type.', 'eye-book'));
        }

        $appointment_type_id = $wpdb->insert_id;

        // Log creation
        if (class_exists('Eye_Book_Audit')) {
            Eye_Book_Audit::log('appointment_type_created', 'appointment_type', $appointment_type_id, array(
                'name' => $data['name'],
                'duration' => $data['duration']
            ));
        }

        return $appointment_type_id;
    }

    /**
     * Get appointment type by ID
     *
     * @param int $id Appointment type ID
     * @return object|false
     * @since 1.0.0
     */
    public function get($id) {
        global $wpdb;
        
        $id = intval($id);
        if ($id <= 0) {
            return false;
        }

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d AND is_active = 1",
            $id
        );

        return $wpdb->get_row($query);
    }

    /**
     * Get all appointment types
     *
     * @param array $args Query arguments
     * @return array
     * @since 1.0.0
     */
    public function get_all($args = array()) {
        global $wpdb;

        $defaults = array(
            'is_active' => 1,
            'orderby' => 'name',
            'order' => 'ASC',
            'limit' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $where_clause = "WHERE is_active = " . intval($args['is_active']);
        $order_clause = "ORDER BY {$args['orderby']} {$args['order']}";
        $limit_clause = $args['limit'] > 0 ? "LIMIT {$args['limit']}" : '';

        $query = "SELECT * FROM {$this->table_name} {$where_clause} {$order_clause} {$limit_clause}";

        return $wpdb->get_results($query);
    }

    /**
     * Update appointment type
     *
     * @param int $id Appointment type ID
     * @param array $data Update data
     * @return bool|WP_Error
     * @since 1.0.0
     */
    public function update($id, $data) {
        $id = intval($id);
        if ($id <= 0) {
            return new WP_Error('invalid_id', __('Invalid appointment type ID.', 'eye-book'));
        }

        // Get existing data
        $existing = $this->get($id);
        if (!$existing) {
            return new WP_Error('not_found', __('Appointment type not found.', 'eye-book'));
        }

        // Add update timestamp
        $data['updated_at'] = current_time('mysql', true);

        // Sanitize data
        $data = $this->sanitize_data($data);

        global $wpdb;
        $result = $wpdb->update(
            $this->table_name,
            $data,
            array('id' => $id),
            null,
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to update appointment type.', 'eye-book'));
        }

        // Log update
        if (class_exists('Eye_Book_Audit')) {
            Eye_Book_Audit::log('appointment_type_updated', 'appointment_type', $id, array(
                'changes' => $data
            ));
        }

        return true;
    }

    /**
     * Delete appointment type (soft delete)
     *
     * @param int $id Appointment type ID
     * @return bool|WP_Error
     * @since 1.0.0
     */
    public function delete($id) {
        $id = intval($id);
        if ($id <= 0) {
            return new WP_Error('invalid_id', __('Invalid appointment type ID.', 'eye-book'));
        }

        // Check if appointment type is in use
        if ($this->is_in_use($id)) {
            return new WP_Error('in_use', __('Cannot delete appointment type that is in use.', 'eye-book'));
        }

        global $wpdb;
        $result = $wpdb->update(
            $this->table_name,
            array('is_active' => 0, 'updated_at' => current_time('mysql', true)),
            array('id' => $id),
            array('%d', '%s'),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to delete appointment type.', 'eye-book'));
        }

        // Log deletion
        if (class_exists('Eye_Book_Audit')) {
            Eye_Book_Audit::log('appointment_type_deleted', 'appointment_type', $id);
        }

        return true;
    }

    /**
     * Check if appointment type is in use
     *
     * @param int $id Appointment type ID
     * @return bool
     * @since 1.0.0
     */
    private function is_in_use($id) {
        global $wpdb;
        
        $appointments_table = EYE_BOOK_TABLE_APPOINTMENTS;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$appointments_table} WHERE appointment_type_id = %d",
            $id
        ));

        return $count > 0;
    }

    /**
     * Get appointment types for provider
     *
     * @param int $provider_id Provider ID
     * @return array
     * @since 1.0.0
     */
    public function get_for_provider($provider_id) {
        global $wpdb;
        
        $provider_id = intval($provider_id);
        if ($provider_id <= 0) {
            return array();
        }

        // This would typically join with a provider_appointment_types table
        // For now, return all active appointment types
        return $this->get_all(array('is_active' => 1));
    }

    /**
     * Get appointment type statistics
     *
     * @param array $args Query arguments
     * @return array
     * @since 1.0.0
     */
    public function get_statistics($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'start_date' => date('Y-m-01'),
            'end_date' => date('Y-m-t'),
            'location_id' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $appointments_table = EYE_BOOK_TABLE_APPOINTMENTS;
        $where_clauses = array();
        $where_clauses[] = "a.appointment_date BETWEEN %s AND %s";
        $where_clauses[] = "a.status != 'cancelled'";

        if ($args['location_id'] > 0) {
            $where_clauses[] = "a.location_id = %d";
        }

        $where_clause = implode(' AND ', $where_clauses);

        $query = $wpdb->prepare(
            "SELECT 
                at.id,
                at.name,
                at.duration,
                COUNT(a.id) as total_appointments,
                SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN a.status = 'no_show' THEN 1 ELSE 0 END) as no_shows,
                AVG(CASE WHEN a.status = 'completed' THEN a.actual_duration ELSE NULL END) as avg_duration
            FROM {$this->table_name} at
            LEFT JOIN {$appointments_table} a ON at.id = a.appointment_type_id
            WHERE {$where_clause}
            GROUP BY at.id
            ORDER BY total_appointments DESC",
            $args['start_date'],
            $args['end_date'],
            $args['location_id']
        );

        return $wpdb->get_results($query);
    }

    /**
     * Sanitize appointment type data
     *
     * @param array $data Raw data
     * @return array Sanitized data
     * @since 1.0.0
     */
    private function sanitize_data($data) {
        $sanitized = array();

        if (isset($data['name'])) {
            $sanitized['name'] = sanitize_text_field($data['name']);
        }

        if (isset($data['description'])) {
            $sanitized['description'] = sanitize_textarea_field($data['description']);
        }

        if (isset($data['duration'])) {
            $sanitized['duration'] = intval($data['duration']);
            if ($sanitized['duration'] < 15) {
                $sanitized['duration'] = 15;
            }
        }

        if (isset($data['color'])) {
            $sanitized['color'] = sanitize_hex_color($data['color']);
        }

        if (isset($data['price'])) {
            $sanitized['price'] = floatval($data['price']);
        }

        if (isset($data['requires_forms'])) {
            $sanitized['requires_forms'] = intval($data['requires_forms']);
        }

        if (isset($data['is_active'])) {
            $sanitized['is_active'] = intval($data['is_active']);
        }

        if (isset($data['created_at'])) {
            $sanitized['created_at'] = sanitize_text_field($data['created_at']);
        }

        if (isset($data['updated_at'])) {
            $sanitized['updated_at'] = sanitize_text_field($data['updated_at']);
        }

        return $sanitized;
    }
}
