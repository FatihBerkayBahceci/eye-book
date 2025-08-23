<?php
/**
 * Patient Form Model
 *
 * @package EyeBook
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eye_Book_Patient_Form Class
 *
 * Handles patient form operations
 *
 * @since 1.0.0
 */
class Eye_Book_Patient_Form {

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
        $this->table_name = EYE_BOOK_TABLE_PATIENT_FORMS;
    }

    /**
     * Create patient form
     *
     * @param array $data Form data
     * @return int|WP_Error
     * @since 1.0.0
     */
    public function create($data) {
        $defaults = array(
            'patient_id' => 0,
            'form_type' => '',
            'form_data' => '',
            'status' => 'pending',
            'submitted_at' => current_time('mysql', true),
            'reviewed_at' => null,
            'reviewed_by' => 0,
            'notes' => '',
            'is_encrypted' => 1,
            'created_at' => current_time('mysql', true),
            'updated_at' => current_time('mysql', true)
        );

        $data = wp_parse_args($data, $defaults);
        
        // Validate required fields
        if (empty($data['patient_id']) || empty($data['form_type'])) {
            return new WP_Error('invalid_data', __('Patient ID and form type are required.', 'eye-book'));
        }

        // Encrypt form data if encryption is enabled
        if ($data['is_encrypted'] && !empty($data['form_data'])) {
            if (class_exists('Eye_Book_Encryption')) {
                $encryption = new Eye_Book_Encryption();
                $data['form_data'] = $encryption->encrypt($data['form_data']);
            }
        }

        // Sanitize data
        $data = $this->sanitize_data($data);

        global $wpdb;
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to create patient form.', 'eye-book'));
        }

        $form_id = $wpdb->insert_id;

        // Log creation
        if (class_exists('Eye_Book_Audit')) {
            Eye_Book_Audit::log('patient_form_created', 'patient_form', $form_id, array(
                'patient_id' => $data['patient_id'],
                'form_type' => $data['form_type']
            ));
        }

        return $form_id;
    }

    /**
     * Get patient form by ID
     *
     * @param int $id Form ID
     * @param bool $decrypt Whether to decrypt form data
     * @return object|false
     * @since 1.0.0
     */
    public function get($id, $decrypt = true) {
        global $wpdb;
        
        $id = intval($id);
        if ($id <= 0) {
            return false;
        }

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $id
        );

        $form = $wpdb->get_row($query);
        
        if ($form && $decrypt && $form->is_encrypted && !empty($form->form_data)) {
            $form->form_data = $this->decrypt_form_data($form->form_data);
        }

        return $form;
    }

    /**
     * Get forms by patient ID
     *
     * @param int $patient_id Patient ID
     * @param array $args Query arguments
     * @return array
     * @since 1.0.0
     */
    public function get_by_patient($patient_id, $args = array()) {
        global $wpdb;
        
        $patient_id = intval($patient_id);
        if ($patient_id <= 0) {
            return array();
        }

        $defaults = array(
            'form_type' => '',
            'status' => '',
            'orderby' => 'submitted_at',
            'order' => 'DESC',
            'limit' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array("patient_id = %d");
        $where_values = array($patient_id);

        if (!empty($args['form_type'])) {
            $where_clauses[] = "form_type = %s";
            $where_values[] = $args['form_type'];
        }

        if (!empty($args['status'])) {
            $where_clauses[] = "status = %s";
            $where_values[] = $args['status'];
        }

        $where_clause = implode(' AND ', $where_clauses);
        $order_clause = "ORDER BY {$args['orderby']} {$args['order']}";
        $limit_clause = $args['limit'] > 0 ? "LIMIT {$args['limit']}" : '';

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$where_clause} {$order_clause} {$limit_clause}",
            ...$where_values
        );

        $forms = $wpdb->get_results($query);
        
        // Decrypt form data if needed
        if (!empty($forms)) {
            foreach ($forms as $form) {
                if ($form->is_encrypted && !empty($form->form_data)) {
                    $form->form_data = $this->decrypt_form_data($form->form_data);
                }
            }
        }

        return $forms;
    }

    /**
     * Get forms by type
     *
     * @param string $form_type Form type
     * @param array $args Query arguments
     * @return array
     * @since 1.0.0
     */
    public function get_by_type($form_type, $args = array()) {
        global $wpdb;
        
        if (empty($form_type)) {
            return array();
        }

        $defaults = array(
            'status' => '',
            'start_date' => '',
            'end_date' => '',
            'orderby' => 'submitted_at',
            'order' => 'DESC',
            'limit' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array("form_type = %s");
        $where_values = array($form_type);

        if (!empty($args['status'])) {
            $where_clauses[] = "status = %s";
            $where_values[] = $args['status'];
        }

        if (!empty($args['start_date'])) {
            $where_clauses[] = "submitted_at >= %s";
            $where_values[] = $args['start_date'];
        }

        if (!empty($args['end_date'])) {
            $where_clauses[] = "submitted_at <= %s";
            $where_values[] = $args['end_date'];
        }

        $where_clause = implode(' AND ', $where_clauses);
        $order_clause = "ORDER BY {$args['orderby']} {$args['order']}";
        $limit_clause = $args['limit'] > 0 ? "LIMIT {$args['limit']}" : '';

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$where_clause} {$order_clause} {$limit_clause}",
            ...$where_values
        );

        $forms = $wpdb->get_results($query);
        
        // Decrypt form data if needed
        if (!empty($forms)) {
            foreach ($forms as $form) {
                if ($form->is_encrypted && !empty($form->form_data)) {
                    $form->form_data = $this->decrypt_form_data($form->form_data);
                }
            }
        }

        return $forms;
    }

    /**
     * Update patient form
     *
     * @param int $id Form ID
     * @param array $data Update data
     * @return bool|WP_Error
     * @since 1.0.0
     */
    public function update($id, $data) {
        $id = intval($id);
        if ($id <= 0) {
            return new WP_Error('invalid_id', __('Invalid form ID.', 'eye-book'));
        }

        // Get existing data
        $existing = $this->get($id, false);
        if (!$existing) {
            return new WP_Error('not_found', __('Patient form not found.', 'eye-book'));
        }

        // Encrypt form data if it's being updated and encryption is enabled
        if (isset($data['form_data']) && $existing->is_encrypted && !empty($data['form_data'])) {
            if (class_exists('Eye_Book_Encryption')) {
                $encryption = new Eye_Book_Encryption();
                $data['form_data'] = $encryption->encrypt($data['form_data']);
            }
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
            return new WP_Error('db_error', __('Failed to update patient form.', 'eye-book'));
        }

        // Log update
        if (class_exists('Eye_Book_Audit')) {
            Eye_Book_Audit::log('patient_form_updated', 'patient_form', $id, array(
                'changes' => array_keys($data)
            ));
        }

        return true;
    }

    /**
     * Mark form as reviewed
     *
     * @param int $id Form ID
     * @param int $reviewer_id Reviewer user ID
     * @param string $notes Review notes
     * @return bool|WP_Error
     * @since 1.0.0
     */
    public function mark_reviewed($id, $reviewer_id, $notes = '') {
        $data = array(
            'status' => 'reviewed',
            'reviewed_at' => current_time('mysql', true),
            'reviewed_by' => intval($reviewer_id),
            'notes' => sanitize_textarea_field($notes)
        );

        return $this->update($id, $data);
    }

    /**
     * Delete patient form
     *
     * @param int $id Form ID
     * @return bool|WP_Error
     * @since 1.0.0
     */
    public function delete($id) {
        $id = intval($id);
        if ($id <= 0) {
            return new WP_Error('invalid_id', __('Invalid form ID.', 'eye-book'));
        }

        global $wpdb;
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );

        if ($result === false) {
            return new WP_Error('db_error', __('Failed to delete patient form.', 'eye-book'));
        }

        // Log deletion
        if (class_exists('Eye_Book_Audit')) {
            Eye_Book_Audit::log('patient_form_deleted', 'patient_form', $id);
        }

        return true;
    }

    /**
     * Get form statistics
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
            'form_type' => '',
            'location_id' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array("pf.submitted_at BETWEEN %s AND %s");
        $where_values = array($args['start_date'], $args['end_date']);

        if (!empty($args['form_type'])) {
            $where_clauses[] = "pf.form_type = %s";
            $where_values[] = $args['form_type'];
        }

        if ($args['location_id'] > 0) {
            $where_clauses[] = "p.location_id = %d";
            $where_values[] = $args['location_id'];
        }

        $where_clause = implode(' AND ', $where_clauses);

        $query = $wpdb->prepare(
            "SELECT 
                pf.form_type,
                COUNT(pf.id) as total_forms,
                SUM(CASE WHEN pf.status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN pf.status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
                AVG(CASE WHEN pf.status = 'reviewed' 
                    THEN TIMESTAMPDIFF(MINUTE, pf.submitted_at, pf.reviewed_at) 
                    ELSE NULL END) as avg_review_time
            FROM {$this->table_name} pf
            LEFT JOIN " . EYE_BOOK_TABLE_PATIENTS . " p ON pf.patient_id = p.id
            WHERE {$where_clause}
            GROUP BY pf.form_type
            ORDER BY total_forms DESC",
            ...$where_values
        );

        return $wpdb->get_results($query);
    }

    /**
     * Decrypt form data
     *
     * @param string $encrypted_data Encrypted form data
     * @return string Decrypted form data
     * @since 1.0.0
     */
    private function decrypt_form_data($encrypted_data) {
        if (class_exists('Eye_Book_Encryption')) {
            $encryption = new Eye_Book_Encryption();
            return $encryption->decrypt($encrypted_data);
        }
        
        return $encrypted_data;
    }

    /**
     * Sanitize form data
     *
     * @param array $data Raw data
     * @return array Sanitized data
     * @since 1.0.0
     */
    private function sanitize_data($data) {
        $sanitized = array();

        if (isset($data['patient_id'])) {
            $sanitized['patient_id'] = intval($data['patient_id']);
        }

        if (isset($data['form_type'])) {
            $sanitized['form_type'] = sanitize_text_field($data['form_type']);
        }

        if (isset($data['form_data'])) {
            $sanitized['form_data'] = $data['form_data']; // Already sanitized/encrypted
        }

        if (isset($data['status'])) {
            $sanitized['status'] = sanitize_text_field($data['status']);
        }

        if (isset($data['submitted_at'])) {
            $sanitized['submitted_at'] = sanitize_text_field($data['submitted_at']);
        }

        if (isset($data['reviewed_at'])) {
            $sanitized['reviewed_at'] = $data['reviewed_at'] ? sanitize_text_field($data['reviewed_at']) : null;
        }

        if (isset($data['reviewed_by'])) {
            $sanitized['reviewed_by'] = intval($data['reviewed_by']);
        }

        if (isset($data['notes'])) {
            $sanitized['notes'] = sanitize_textarea_field($data['notes']);
        }

        if (isset($data['is_encrypted'])) {
            $sanitized['is_encrypted'] = intval($data['is_encrypted']);
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
