<?php
/**
 * Patient model class for Eye-Book plugin
 *
 * @package EyeBook
 * @subpackage Models
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eye_Book_Patient Class
 *
 * Handles patient data operations and business logic
 *
 * @class Eye_Book_Patient
 * @since 1.0.0
 */
class Eye_Book_Patient {

    /**
     * Patient ID
     *
     * @var int
     * @since 1.0.0
     */
    public $id;

    /**
     * WordPress user ID
     *
     * @var int
     * @since 1.0.0
     */
    public $wp_user_id;

    /**
     * Unique patient identifier
     *
     * @var string
     * @since 1.0.0
     */
    public $patient_id;

    /**
     * First name
     *
     * @var string
     * @since 1.0.0
     */
    public $first_name;

    /**
     * Last name
     *
     * @var string
     * @since 1.0.0
     */
    public $last_name;

    /**
     * Date of birth
     *
     * @var string
     * @since 1.0.0
     */
    public $date_of_birth;

    /**
     * Gender
     *
     * @var string
     * @since 1.0.0
     */
    public $gender;

    /**
     * Phone number
     *
     * @var string
     * @since 1.0.0
     */
    public $phone;

    /**
     * Email address
     *
     * @var string
     * @since 1.0.0
     */
    public $email;

    /**
     * Status
     *
     * @var string
     * @since 1.0.0
     */
    public $status;

    /**
     * Constructor
     *
     * @param int|array $patient Patient ID or data array
     * @since 1.0.0
     */
    public function __construct($patient = null) {
        if (is_numeric($patient)) {
            $this->load($patient);
        } elseif (is_array($patient)) {
            $this->populate($patient);
        }
    }

    /**
     * Load patient by ID
     *
     * @param int $patient_id
     * @return bool Success status
     * @since 1.0.0
     */
    public function load($patient_id) {
        global $wpdb;

        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE id = %d",
            $patient_id
        ), ARRAY_A);

        if ($patient) {
            $this->populate($patient);
            $this->decrypt_data();
            return true;
        }

        return false;
    }

    /**
     * Load patient by patient ID string
     *
     * @param string $patient_id
     * @return bool Success status
     * @since 1.0.0
     */
    public function load_by_patient_id($patient_id) {
        global $wpdb;

        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE patient_id = %s",
            $patient_id
        ), ARRAY_A);

        if ($patient) {
            $this->populate($patient);
            $this->decrypt_data();
            return true;
        }

        return false;
    }

    /**
     * Populate object properties from array
     *
     * @param array $data Patient data
     * @since 1.0.0
     */
    private function populate($data) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Decrypt sensitive patient data
     *
     * @since 1.0.0
     */
    private function decrypt_data() {
        if (get_option('eye_book_encryption_enabled', 1)) {
            $this->first_name = Eye_Book_Encryption::decrypt($this->first_name) ?: $this->first_name;
            $this->last_name = Eye_Book_Encryption::decrypt($this->last_name) ?: $this->last_name;
            $this->phone = Eye_Book_Encryption::decrypt($this->phone) ?: $this->phone;
            $this->email = Eye_Book_Encryption::decrypt($this->email) ?: $this->email;
            
            if (isset($this->address_line1)) {
                $this->address_line1 = Eye_Book_Encryption::decrypt($this->address_line1) ?: $this->address_line1;
            }
        }
    }

    /**
     * Save patient to database
     *
     * @return bool|int Patient ID on success, false on failure
     * @since 1.0.0
     */
    public function save() {
        global $wpdb;

        // Sanitize data
        $data = apply_filters('eye_book_sanitize_patient_data', $this->to_array());
        
        // Encrypt sensitive data if encryption is enabled
        if (get_option('eye_book_encryption_enabled', 1)) {
            $data = Eye_Book_Encryption::encrypt_patient_data($data);
        }

        $old_data = null;

        if ($this->id) {
            // Update existing patient
            $old_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE id = %d",
                $this->id
            ), ARRAY_A);

            $result = $wpdb->update(
                EYE_BOOK_TABLE_PATIENTS,
                $data,
                array('id' => $this->id)
            );

            if ($result !== false) {
                Eye_Book_Audit::log_patient_access($this->id, 'edit', array_keys($data));
                do_action('eye_book_patient_updated', $this, $old_data);
                return $this->id;
            }
        } else {
            // Create new patient
            if (empty($this->patient_id)) {
                $this->patient_id = $this->generate_patient_id();
                $data['patient_id'] = $this->patient_id;
            }

            $result = $wpdb->insert(
                EYE_BOOK_TABLE_PATIENTS,
                $data
            );

            if ($result !== false) {
                $this->id = $wpdb->insert_id;
                Eye_Book_Audit::log_patient_access($this->id, 'create', array_keys($data));
                do_action('eye_book_patient_created', $this);
                return $this->id;
            }
        }

        return false;
    }

    /**
     * Delete patient (soft delete by changing status)
     *
     * @return bool Success status
     * @since 1.0.0
     */
    public function delete() {
        global $wpdb;

        if (!$this->id) {
            return false;
        }

        // Soft delete by changing status to inactive
        $result = $wpdb->update(
            EYE_BOOK_TABLE_PATIENTS,
            array('status' => 'inactive'),
            array('id' => $this->id)
        );

        if ($result !== false) {
            $this->status = 'inactive';
            Eye_Book_Audit::log_patient_access($this->id, 'delete');
            do_action('eye_book_patient_deleted', $this);
            return true;
        }

        return false;
    }

    /**
     * Convert object to array
     *
     * @return array
     * @since 1.0.0
     */
    public function to_array() {
        return array(
            'wp_user_id' => $this->wp_user_id,
            'patient_id' => $this->patient_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth,
            'gender' => $this->gender,
            'phone' => $this->phone,
            'email' => $this->email,
            'address_line1' => $this->address_line1 ?? '',
            'address_line2' => $this->address_line2 ?? '',
            'city' => $this->city ?? '',
            'state' => $this->state ?? '',
            'zip_code' => $this->zip_code ?? '',
            'emergency_contact_name' => $this->emergency_contact_name ?? '',
            'emergency_contact_phone' => $this->emergency_contact_phone ?? '',
            'emergency_contact_relationship' => $this->emergency_contact_relationship ?? '',
            'insurance_provider' => $this->insurance_provider ?? '',
            'insurance_member_id' => $this->insurance_member_id ?? '',
            'insurance_group_number' => $this->insurance_group_number ?? '',
            'preferred_language' => $this->preferred_language ?? 'en',
            'medical_history' => $this->medical_history ?? '',
            'allergies' => $this->allergies ?? '',
            'current_medications' => $this->current_medications ?? '',
            'preferred_provider_id' => $this->preferred_provider_id ?? null,
            'notes' => $this->notes ?? '',
            'status' => $this->status ?? 'active'
        );
    }

    /**
     * Generate unique patient ID
     *
     * @return string
     * @since 1.0.0
     */
    private function generate_patient_id() {
        $prefix = get_option('eye_book_patient_id_prefix', 'PAT');
        $date = date('Y');
        
        global $wpdb;
        
        do {
            $random = wp_rand(100000, 999999);
            $patient_id = $prefix . $date . $random;
            
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE patient_id = %s",
                $patient_id
            ));
        } while ($exists > 0);
        
        return $patient_id;
    }

    /**
     * Get full name
     *
     * @return string
     * @since 1.0.0
     */
    public function get_full_name() {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get age
     *
     * @return int
     * @since 1.0.0
     */
    public function get_age() {
        if (empty($this->date_of_birth)) {
            return 0;
        }

        $birth_date = new DateTime($this->date_of_birth);
        $current_date = new DateTime();
        
        return $current_date->diff($birth_date)->y;
    }

    /**
     * Get formatted address
     *
     * @return string
     * @since 1.0.0
     */
    public function get_formatted_address() {
        $address_parts = array();
        
        if (!empty($this->address_line1)) {
            $address_parts[] = $this->address_line1;
        }
        
        if (!empty($this->address_line2)) {
            $address_parts[] = $this->address_line2;
        }
        
        $city_state_zip = array();
        if (!empty($this->city)) {
            $city_state_zip[] = $this->city;
        }
        
        if (!empty($this->state)) {
            $city_state_zip[] = $this->state;
        }
        
        if (!empty($this->zip_code)) {
            $city_state_zip[] = $this->zip_code;
        }
        
        if (!empty($city_state_zip)) {
            $address_parts[] = implode(', ', $city_state_zip);
        }
        
        return implode("\n", $address_parts);
    }

    /**
     * Get upcoming appointments
     *
     * @param int $limit Number of appointments to retrieve
     * @return array
     * @since 1.0.0
     */
    public function get_upcoming_appointments($limit = 5) {
        return Eye_Book_Appointment::get_appointments(array(
            'patient_id' => $this->id,
            'date_from' => current_time('mysql'),
            'status' => array('scheduled', 'confirmed'),
            'limit' => $limit,
            'orderby' => 'start_datetime',
            'order' => 'ASC'
        ));
    }

    /**
     * Get appointment history
     *
     * @param int $limit Number of appointments to retrieve
     * @return array
     * @since 1.0.0
     */
    public function get_appointment_history($limit = 10) {
        return Eye_Book_Appointment::get_appointments(array(
            'patient_id' => $this->id,
            'date_to' => current_time('mysql'),
            'limit' => $limit,
            'orderby' => 'start_datetime',
            'order' => 'DESC'
        ));
    }

    /**
     * Check if patient has any upcoming appointments
     *
     * @return bool
     * @since 1.0.0
     */
    public function has_upcoming_appointments() {
        global $wpdb;

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . "
             WHERE patient_id = %d 
             AND start_datetime > %s 
             AND status IN ('scheduled', 'confirmed')",
            $this->id,
            current_time('mysql')
        ));

        return intval($count) > 0;
    }

    /**
     * Get patient forms
     *
     * @param string $form_type Optional form type filter
     * @return array
     * @since 1.0.0
     */
    public function get_forms($form_type = null) {
        global $wpdb;

        $query = "SELECT * FROM " . EYE_BOOK_TABLE_PATIENT_FORMS . " WHERE patient_id = %d";
        $params = array($this->id);

        if ($form_type) {
            $query .= " AND form_type = %s";
            $params[] = $form_type;
        }

        $query .= " ORDER BY created_at DESC";

        return $wpdb->get_results($wpdb->prepare($query, $params));
    }

    /**
     * Add form data
     *
     * @param string $form_type Form type
     * @param array $form_data Form data
     * @param int $appointment_id Optional appointment ID
     * @return bool Success status
     * @since 1.0.0
     */
    public function add_form($form_type, $form_data, $appointment_id = null) {
        global $wpdb;

        $encrypted_data = Eye_Book_Encryption::encrypt_form_data($form_data);

        $result = $wpdb->insert(
            EYE_BOOK_TABLE_PATIENT_FORMS,
            array(
                'patient_id' => $this->id,
                'appointment_id' => $appointment_id,
                'form_type' => $form_type,
                'form_data' => $encrypted_data,
                'completed_at' => current_time('mysql'),
                'ip_address' => Eye_Book_Security::get_client_ip() ?? '0.0.0.0',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'is_encrypted' => 1
            )
        );

        if ($result !== false) {
            Eye_Book_Audit::log_patient_access($this->id, 'form_submitted', array($form_type));
            return true;
        }

        return false;
    }

    /**
     * Create WordPress user account for patient portal
     *
     * @param string $password Password for the account
     * @return int|WP_Error User ID on success, WP_Error on failure
     * @since 1.0.0
     */
    public function create_wp_user($password) {
        if ($this->wp_user_id) {
            return new WP_Error('user_exists', __('Patient already has a user account.', 'eye-book'));
        }

        if (empty($this->email)) {
            return new WP_Error('no_email', __('Patient email is required to create user account.', 'eye-book'));
        }

        $username = sanitize_user($this->email);
        $user_data = array(
            'user_login' => $username,
            'user_email' => $this->email,
            'user_pass' => $password,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->get_full_name(),
            'role' => 'subscriber'
        );

        $user_id = wp_insert_user($user_data);

        if (!is_wp_error($user_id)) {
            // Update patient record with WordPress user ID
            global $wpdb;
            $wpdb->update(
                EYE_BOOK_TABLE_PATIENTS,
                array('wp_user_id' => $user_id),
                array('id' => $this->id)
            );

            $this->wp_user_id = $user_id;

            // Log user creation
            Eye_Book_Audit::log_patient_access($this->id, 'user_created', array('wp_user_id' => $user_id));

            do_action('eye_book_patient_user_created', $this, $user_id);
        }

        return $user_id;
    }

    /**
     * Send patient portal invitation
     *
     * @return bool Success status
     * @since 1.0.0
     */
    public function send_portal_invitation() {
        if (empty($this->email)) {
            return false;
        }

        $token = Eye_Book_Encryption::generate_patient_token($this->id, 72); // 72 hour expiry
        $portal_url = add_query_arg('patient_token', $token, home_url('/patient-portal/'));

        $subject = sprintf(__('Patient Portal Access - %s', 'eye-book'), get_bloginfo('name'));
        
        $message = sprintf(
            __("Dear %s,\n\nYou now have access to our patient portal where you can:\n- View upcoming appointments\n- Access your medical history\n- Update your information\n- Complete forms online\n\nClick here to access your portal:\n%s\n\nThis link will expire in 72 hours.\n\nBest regards,\n%s", 'eye-book'),
            $this->get_full_name(),
            $portal_url,
            get_bloginfo('name')
        );

        $sent = wp_mail($this->email, $subject, $message);

        if ($sent) {
            Eye_Book_Audit::log_patient_access($this->id, 'portal_invitation_sent');
        }

        return $sent;
    }

    /**
     * Search patients with filtering
     *
     * @param array $args Search arguments
     * @return array Patients
     * @since 1.0.0
     */
    public static function search_patients($args = array()) {
        global $wpdb;

        $defaults = array(
            'search' => '',
            'status' => 'active',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'last_name',
            'order' => 'ASC'
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array('1=1');
        $where_values = array();

        // Status filter
        if ($args['status']) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        // Search functionality
        if (!empty($args['search'])) {
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_clauses[] = '(first_name LIKE %s OR last_name LIKE %s OR email LIKE %s OR patient_id LIKE %s OR phone LIKE %s)';
            $where_values = array_merge($where_values, array($search_term, $search_term, $search_term, $search_term, $search_term));
        }

        $where_clause = implode(' AND ', $where_clauses);

        // Order by
        $allowed_orderby = array('last_name', 'first_name', 'created_at', 'patient_id');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'last_name';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $query = "SELECT * FROM " . EYE_BOOK_TABLE_PATIENTS . "
                  WHERE $where_clause
                  ORDER BY $orderby $order
                  LIMIT %d OFFSET %d";

        $where_values[] = intval($args['limit']);
        $where_values[] = intval($args['offset']);

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $results = $wpdb->get_results($query, ARRAY_A);
        $patients = array();

        foreach ($results as $result) {
            $patients[] = new self($result);
        }

        return $patients;
    }

    /**
     * Get patient by email
     *
     * @param string $email
     * @return Eye_Book_Patient|null
     * @since 1.0.0
     */
    public static function get_by_email($email) {
        global $wpdb;

        // First try exact match
        $patient_data = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE email = %s",
            $email
        ), ARRAY_A);

        if ($patient_data) {
            return new self($patient_data);
        }

        // If encryption is enabled, we might need to search encrypted emails
        if (get_option('eye_book_encryption_enabled', 1)) {
            $encrypted_email = Eye_Book_Encryption::encrypt($email);
            
            $patient_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE email = %s",
                $encrypted_email
            ), ARRAY_A);

            if ($patient_data) {
                return new self($patient_data);
            }
        }

        return null;
    }
}