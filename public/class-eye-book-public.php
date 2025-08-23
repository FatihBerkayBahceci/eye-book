<?php
/**
 * Public-facing functionality class for Eye-Book plugin
 *
 * @package EyeBook
 * @subpackage Public
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eye_Book_Public Class
 *
 * Handles public-facing functionality including shortcodes, forms, and patient portal
 *
 * @class Eye_Book_Public
 * @since 1.0.0
 */
class Eye_Book_Public {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('init', array($this, 'init_shortcodes'));
        add_action('init', array($this, 'handle_form_submissions'));
        add_action('wp_ajax_eye_book_book_appointment', array($this, 'ajax_book_appointment'));
        add_action('wp_ajax_nopriv_eye_book_book_appointment', array($this, 'ajax_book_appointment'));
        add_action('wp_ajax_eye_book_get_available_slots', array($this, 'ajax_get_available_slots'));
        add_action('wp_ajax_nopriv_eye_book_get_available_slots', array($this, 'ajax_get_available_slots'));
    }

    /**
     * Enqueue public assets
     *
     * @since 1.0.0
     */
    public function enqueue_public_assets() {
        // Only enqueue on pages that use Eye-Book functionality
        if (!$this->should_enqueue_assets()) {
            return;
        }

        // Enqueue public styles
        wp_enqueue_style(
            'eye-book-public',
            EYE_BOOK_PLUGIN_URL . 'public/assets/css/eye-book-public.css',
            array(),
            EYE_BOOK_VERSION
        );

        // Enqueue public scripts
        wp_enqueue_script(
            'eye-book-public',
            EYE_BOOK_PLUGIN_URL . 'public/assets/js/eye-book-public.js',
            array('jquery'),
            EYE_BOOK_VERSION,
            true
        );

        // Localize script
        wp_localize_script('eye-book-public', 'eyeBookPublic', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eye_book_public_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'eye-book'),
                'error' => __('An error occurred', 'eye-book'),
                'book_success' => __('Appointment booked successfully!', 'eye-book'),
                'book_error' => __('Failed to book appointment. Please try again.', 'eye-book'),
                'select_date' => __('Please select a date', 'eye-book'),
                'select_time' => __('Please select a time', 'eye-book'),
                'fill_required' => __('Please fill in all required fields', 'eye-book')
            ),
            'settings' => array(
                'date_format' => get_option('date_format', 'F j, Y'),
                'time_format' => get_option('time_format', 'g:i a'),
                'booking_advance_days' => get_option('eye_book_booking_advance_days', 30)
            )
        ));
    }

    /**
     * Check if assets should be enqueued
     *
     * @return bool
     * @since 1.0.0
     */
    private function should_enqueue_assets() {
        global $post;

        // Check for shortcodes in content
        if ($post && has_shortcode($post->post_content, 'eye_book_booking')) {
            return true;
        }

        if ($post && has_shortcode($post->post_content, 'eye_book_patient_portal')) {
            return true;
        }

        // Check for Eye-Book query vars
        if (get_query_var('eye_book_page')) {
            return true;
        }

        return false;
    }

    /**
     * Initialize shortcodes
     *
     * @since 1.0.0
     */
    public function init_shortcodes() {
        add_shortcode('eye_book_booking', array($this, 'booking_form_shortcode'));
        add_shortcode('eye_book_patient_portal', array($this, 'patient_portal_shortcode'));
        add_shortcode('eye_book_appointment_calendar', array($this, 'appointment_calendar_shortcode'));
    }

    /**
     * Booking form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     * @since 1.0.0
     */
    public function booking_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'provider_id' => '',
            'location_id' => '',
            'appointment_type_id' => '',
            'show_provider_selection' => 'true',
            'show_location_selection' => 'true',
            'theme' => 'default'
        ), $atts, 'eye_book_booking');

        // Check if booking is enabled
        if (!get_option('eye_book_booking_enabled', 1)) {
            return '<div class="eye-book-notice">' . __('Online booking is currently disabled.', 'eye-book') . '</div>';
        }

        ob_start();
        include EYE_BOOK_PLUGIN_DIR . 'public/templates/booking-form.php';
        return ob_get_clean();
    }

    /**
     * Patient portal shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     * @since 1.0.0
     */
    public function patient_portal_shortcode($atts) {
        $atts = shortcode_atts(array(
            'redirect_after_login' => '',
            'show_registration' => 'true'
        ), $atts, 'eye_book_patient_portal');

        // Check if user is logged in or has valid token
        $patient = $this->get_current_patient();

        ob_start();
        if ($patient) {
            include EYE_BOOK_PLUGIN_DIR . 'public/templates/patient-portal.php';
        } else {
            include EYE_BOOK_PLUGIN_DIR . 'public/templates/patient-login.php';
        }
        return ob_get_clean();
    }

    /**
     * Appointment calendar shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string
     * @since 1.0.0
     */
    public function appointment_calendar_shortcode($atts) {
        $atts = shortcode_atts(array(
            'provider_id' => '',
            'location_id' => '',
            'view' => 'month', // month, week, day
            'show_available_only' => 'false'
        ), $atts, 'eye_book_appointment_calendar');

        ob_start();
        include EYE_BOOK_PLUGIN_DIR . 'public/templates/appointment-calendar.php';
        return ob_get_clean();
    }

    /**
     * Handle form submissions
     *
     * @since 1.0.0
     */
    public function handle_form_submissions() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // Handle appointment booking
        if (isset($_POST['eye_book_action']) && $_POST['eye_book_action'] === 'book_appointment') {
            $this->handle_appointment_booking();
        }

        // Handle patient registration
        if (isset($_POST['eye_book_action']) && $_POST['eye_book_action'] === 'patient_registration') {
            $this->handle_patient_registration();
        }

        // Handle patient login
        if (isset($_POST['eye_book_action']) && $_POST['eye_book_action'] === 'patient_login') {
            $this->handle_patient_login();
        }
    }

    /**
     * Handle appointment booking
     *
     * @since 1.0.0
     */
    private function handle_appointment_booking() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eye_book_booking_nonce')) {
            wp_die(__('Security check failed', 'eye-book'));
        }

        // Sanitize input data
        $appointment_data = array(
            'provider_id' => intval($_POST['provider_id']),
            'location_id' => intval($_POST['location_id']),
            'appointment_type_id' => intval($_POST['appointment_type_id']),
            'appointment_date' => sanitize_text_field($_POST['appointment_date']),
            'appointment_time' => sanitize_text_field($_POST['appointment_time']),
            'chief_complaint' => sanitize_textarea_field($_POST['chief_complaint'] ?? ''),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
        );

        $patient_data = array(
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
            'insurance_provider' => sanitize_text_field($_POST['insurance_provider'] ?? ''),
            'insurance_member_id' => sanitize_text_field($_POST['insurance_member_id'] ?? '')
        );

        try {
            // Create or find patient
            $patient = $this->create_or_find_patient($patient_data);
            
            if (!$patient) {
                throw new Exception(__('Failed to create patient record', 'eye-book'));
            }

            // Create appointment
            $appointment = new Eye_Book_Appointment();
            $appointment->patient_id = $patient->id;
            $appointment->provider_id = $appointment_data['provider_id'];
            $appointment->location_id = $appointment_data['location_id'];
            $appointment->appointment_type_id = $appointment_data['appointment_type_id'];
            $appointment->start_datetime = $appointment_data['appointment_date'] . ' ' . $appointment_data['appointment_time'];
            
            // Calculate end time based on appointment type
            $end_time = $this->calculate_appointment_end_time($appointment->start_datetime, $appointment_data['appointment_type_id']);
            $appointment->end_datetime = $end_time;
            
            $appointment->status = 'scheduled';
            $appointment->booking_source = 'online';
            $appointment->chief_complaint = $appointment_data['chief_complaint'];
            $appointment->notes = $appointment_data['notes'];

            // Check for conflicts
            $conflicts = $appointment->check_conflicts();
            if (!empty($conflicts)) {
                throw new Exception(__('The selected time slot is no longer available', 'eye-book'));
            }

            // Save appointment
            $appointment_id = $appointment->save();
            
            if (!$appointment_id) {
                throw new Exception(__('Failed to save appointment', 'eye-book'));
            }

            // Send confirmation email
            $this->send_booking_confirmation($appointment, $patient);

            // Redirect with success message
            $redirect_url = add_query_arg(array(
                'eye_book_booking' => 'success',
                'appointment_id' => $appointment->appointment_id
            ), wp_get_referer());

            wp_redirect($redirect_url);
            exit;

        } catch (Exception $e) {
            // Redirect with error message
            $redirect_url = add_query_arg(array(
                'eye_book_booking' => 'error',
                'message' => urlencode($e->getMessage())
            ), wp_get_referer());

            wp_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Create or find patient
     *
     * @param array $patient_data
     * @return Eye_Book_Patient|null
     * @since 1.0.0
     */
    private function create_or_find_patient($patient_data) {
        // Try to find existing patient by email
        $existing_patient = Eye_Book_Patient::get_by_email($patient_data['email']);
        
        if ($existing_patient) {
            // Update patient data if needed
            foreach ($patient_data as $key => $value) {
                if (property_exists($existing_patient, $key) && !empty($value)) {
                    $existing_patient->$key = $value;
                }
            }
            $existing_patient->save();
            return $existing_patient;
        }

        // Create new patient
        $patient = new Eye_Book_Patient();
        foreach ($patient_data as $key => $value) {
            if (property_exists($patient, $key)) {
                $patient->$key = $value;
            }
        }

        $patient_id = $patient->save();
        return $patient_id ? $patient : null;
    }

    /**
     * Calculate appointment end time
     *
     * @param string $start_datetime
     * @param int $appointment_type_id
     * @return string
     * @since 1.0.0
     */
    private function calculate_appointment_end_time($start_datetime, $appointment_type_id) {
        global $wpdb;

        $duration = $wpdb->get_var($wpdb->prepare(
            "SELECT duration FROM " . EYE_BOOK_TABLE_APPOINTMENT_TYPES . " WHERE id = %d",
            $appointment_type_id
        ));

        $duration = $duration ?: get_option('eye_book_default_appointment_duration', 30);
        
        return date('Y-m-d H:i:s', strtotime($start_datetime . ' +' . $duration . ' minutes'));
    }

    /**
     * Send booking confirmation
     *
     * @param Eye_Book_Appointment $appointment
     * @param Eye_Book_Patient $patient
     * @since 1.0.0
     */
    private function send_booking_confirmation($appointment, $patient) {
        if (!$patient->email) {
            return;
        }

        $provider = $appointment->get_provider();
        $location = $appointment->get_location();

        $subject = sprintf(__('Appointment Confirmation - %s', 'eye-book'), get_bloginfo('name'));
        
        $appointment_date = date_i18n(get_option('date_format'), strtotime($appointment->start_datetime));
        $appointment_time = date_i18n(get_option('time_format'), strtotime($appointment->start_datetime));

        $message = sprintf(
            __("Dear %s,\n\nYour appointment has been confirmed:\n\nAppointment ID: %s\nDate: %s\nTime: %s\nProvider: %s\nLocation: %s\n\nIf you need to reschedule or cancel, please contact us at least 24 hours in advance.\n\nThank you,\n%s", 'eye-book'),
            $patient->first_name . ' ' . $patient->last_name,
            $appointment->appointment_id,
            $appointment_date,
            $appointment_time,
            $provider ? $provider->get_display_name() : '',
            $location ? $location->name : '',
            get_bloginfo('name')
        );

        wp_mail($patient->email, $subject, $message);
    }

    /**
     * AJAX handler for booking appointment
     *
     * @since 1.0.0
     */
    public function ajax_book_appointment() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eye_book_public_nonce')) {
            wp_send_json_error(__('Security check failed', 'eye-book'));
        }

        // Process booking (similar to handle_appointment_booking but return JSON)
        try {
            // Sanitize input data
            $appointment_data = array(
                'provider_id' => intval($_POST['provider_id']),
                'location_id' => intval($_POST['location_id']),
                'appointment_type_id' => intval($_POST['appointment_type_id']),
                'appointment_date' => sanitize_text_field($_POST['appointment_date']),
                'appointment_time' => sanitize_text_field($_POST['appointment_time']),
                'chief_complaint' => sanitize_textarea_field($_POST['chief_complaint'] ?? ''),
                'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
            );

            $patient_data = array(
                'first_name' => sanitize_text_field($_POST['first_name']),
                'last_name' => sanitize_text_field($_POST['last_name']),
                'email' => sanitize_email($_POST['email']),
                'phone' => sanitize_text_field($_POST['phone']),
                'date_of_birth' => sanitize_text_field($_POST['date_of_birth']),
                'insurance_provider' => sanitize_text_field($_POST['insurance_provider'] ?? ''),
                'insurance_member_id' => sanitize_text_field($_POST['insurance_member_id'] ?? '')
            );

            // Create or find patient
            $patient = $this->create_or_find_patient($patient_data);
            
            if (!$patient) {
                throw new Exception(__('Failed to create patient record', 'eye-book'));
            }

            // Create appointment
            $appointment = new Eye_Book_Appointment();
            $appointment->patient_id = $patient->id;
            $appointment->provider_id = $appointment_data['provider_id'];
            $appointment->location_id = $appointment_data['location_id'];
            $appointment->appointment_type_id = $appointment_data['appointment_type_id'];
            $appointment->start_datetime = $appointment_data['appointment_date'] . ' ' . $appointment_data['appointment_time'];
            $appointment->end_datetime = $this->calculate_appointment_end_time($appointment->start_datetime, $appointment_data['appointment_type_id']);
            $appointment->status = 'scheduled';
            $appointment->booking_source = 'online';
            $appointment->chief_complaint = $appointment_data['chief_complaint'];
            $appointment->notes = $appointment_data['notes'];

            // Check for conflicts
            $conflicts = $appointment->check_conflicts();
            if (!empty($conflicts)) {
                throw new Exception(__('The selected time slot is no longer available', 'eye-book'));
            }

            // Save appointment
            $appointment_id = $appointment->save();
            
            if (!$appointment_id) {
                throw new Exception(__('Failed to save appointment', 'eye-book'));
            }

            // Send confirmation email
            $this->send_booking_confirmation($appointment, $patient);

            wp_send_json_success(array(
                'appointment_id' => $appointment->appointment_id,
                'message' => __('Appointment booked successfully!', 'eye-book')
            ));

        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * AJAX handler for getting available time slots
     *
     * @since 1.0.0
     */
    public function ajax_get_available_slots() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eye_book_public_nonce')) {
            wp_send_json_error(__('Security check failed', 'eye-book'));
        }

        $provider_id = intval($_POST['provider_id']);
        $location_id = intval($_POST['location_id']);
        $date = sanitize_text_field($_POST['date']);
        $appointment_type_id = intval($_POST['appointment_type_id']);

        try {
            $slots = $this->get_available_time_slots($provider_id, $location_id, $date, $appointment_type_id);
            wp_send_json_success($slots);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Get available time slots for a given date
     *
     * @param int $provider_id
     * @param int $location_id
     * @param string $date
     * @param int $appointment_type_id
     * @return array
     * @since 1.0.0
     */
    private function get_available_time_slots($provider_id, $location_id, $date, $appointment_type_id) {
        global $wpdb;

        // Get appointment type duration
        $duration = $wpdb->get_var($wpdb->prepare(
            "SELECT duration FROM " . EYE_BOOK_TABLE_APPOINTMENT_TYPES . " WHERE id = %d",
            $appointment_type_id
        ));
        
        $duration = $duration ?: get_option('eye_book_default_appointment_duration', 30);

        // Get existing appointments for the date
        $existing_appointments = $wpdb->get_results($wpdb->prepare(
            "SELECT start_datetime, end_datetime FROM " . EYE_BOOK_TABLE_APPOINTMENTS . "
             WHERE provider_id = %d AND location_id = %d 
             AND DATE(start_datetime) = %s 
             AND status NOT IN ('cancelled', 'no_show')
             ORDER BY start_datetime",
            $provider_id, $location_id, $date
        ));

        // Generate available slots (simplified - would need provider schedule integration)
        $available_slots = array();
        $start_hour = 8; // 8 AM
        $end_hour = 17; // 5 PM
        
        for ($hour = $start_hour; $hour < $end_hour; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 30) {
                $slot_time = sprintf('%02d:%02d:00', $hour, $minute);
                $slot_datetime = $date . ' ' . $slot_time;
                $slot_end = date('Y-m-d H:i:s', strtotime($slot_datetime . ' +' . $duration . ' minutes'));
                
                // Check if slot conflicts with existing appointments
                $has_conflict = false;
                foreach ($existing_appointments as $appointment) {
                    if (($slot_datetime >= $appointment->start_datetime && $slot_datetime < $appointment->end_datetime) ||
                        ($slot_end > $appointment->start_datetime && $slot_end <= $appointment->end_datetime) ||
                        ($slot_datetime <= $appointment->start_datetime && $slot_end >= $appointment->end_datetime)) {
                        $has_conflict = true;
                        break;
                    }
                }
                
                if (!$has_conflict) {
                    $available_slots[] = array(
                        'time' => $slot_time,
                        'display' => date_i18n(get_option('time_format'), strtotime($slot_time))
                    );
                }
            }
        }

        return $available_slots;
    }

    /**
     * Get current patient (from session or token)
     *
     * @return Eye_Book_Patient|null
     * @since 1.0.0
     */
    private function get_current_patient() {
        // Check for patient token in URL
        if (isset($_GET['patient_token'])) {
            $patient_id = Eye_Book_Encryption::verify_patient_token($_GET['patient_token']);
            if ($patient_id) {
                return new Eye_Book_Patient($patient_id);
            }
        }

        // Check if user is logged in and is a patient
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            // Find patient record for this WordPress user
            global $wpdb;
            $patient_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE wp_user_id = %d",
                $user->ID
            ), ARRAY_A);
            
            if ($patient_data) {
                return new Eye_Book_Patient($patient_data);
            }
        }

        return null;
    }

    /**
     * Handle patient registration
     *
     * @since 1.0.0
     */
    private function handle_patient_registration() {
        // Implementation for patient registration
        // This would create both WordPress user and patient record
    }

    /**
     * Handle patient login
     *
     * @since 1.0.0
     */
    private function handle_patient_login() {
        // Implementation for patient login
        // This would authenticate and create session
    }
}