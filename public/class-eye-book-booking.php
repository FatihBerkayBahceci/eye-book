<?php
/**
 * Booking management class for Eye-Book plugin
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
 * Eye_Book_Booking Class
 *
 * Handles appointment booking logic, availability checking, and booking flow
 *
 * @class Eye_Book_Booking
 * @since 1.0.0
 */
class Eye_Book_Booking {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('wp_ajax_eye_book_check_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_nopriv_eye_book_check_availability', array($this, 'ajax_check_availability'));
        add_action('wp_ajax_eye_book_book_appointment', array($this, 'ajax_book_appointment'));
        add_action('wp_ajax_nopriv_eye_book_book_appointment', array($this, 'ajax_book_appointment'));
        add_action('wp_ajax_eye_book_cancel_appointment', array($this, 'ajax_cancel_appointment'));
        add_action('wp_ajax_eye_book_reschedule_appointment', array($this, 'ajax_reschedule_appointment'));
        add_action('wp_ajax_eye_book_update_profile', array($this, 'ajax_update_profile'));
    }

    /**
     * Check appointment availability
     *
     * @param int $provider_id
     * @param int $location_id
     * @param string $date
     * @param int $duration Duration in minutes
     * @return array Available time slots
     * @since 1.0.0
     */
    public function check_availability($provider_id, $location_id, $date, $duration = 30) {
        global $wpdb;

        // Get provider's schedule for the date
        $schedule = $this->get_provider_schedule($provider_id, $date);
        
        if (empty($schedule)) {
            return array();
        }

        // Get existing appointments for the date
        $existing_appointments = $wpdb->get_results($wpdb->prepare(
            "SELECT start_datetime, end_datetime FROM " . EYE_BOOK_TABLE_APPOINTMENTS . "
             WHERE provider_id = %d AND location_id = %d 
             AND DATE(start_datetime) = %s 
             AND status NOT IN ('cancelled', 'no_show')
             ORDER BY start_datetime",
            $provider_id, $location_id, $date
        ));

        // Generate available slots based on schedule
        $available_slots = array();
        
        foreach ($schedule as $work_period) {
            $period_slots = $this->generate_time_slots(
                $date . ' ' . $work_period['start_time'],
                $date . ' ' . $work_period['end_time'],
                $duration
            );
            
            // Remove slots that conflict with existing appointments
            foreach ($period_slots as $slot) {
                $slot_start = $slot['datetime'];
                $slot_end = date('Y-m-d H:i:s', strtotime($slot_start . ' +' . $duration . ' minutes'));
                
                $has_conflict = false;
                foreach ($existing_appointments as $appointment) {
                    if ($this->times_overlap($slot_start, $slot_end, $appointment->start_datetime, $appointment->end_datetime)) {
                        $has_conflict = true;
                        break;
                    }
                }
                
                if (!$has_conflict) {
                    $available_slots[] = $slot;
                }
            }
        }

        // Apply business rules (buffer times, etc.)
        $available_slots = $this->apply_booking_rules($available_slots, $date);

        return $available_slots;
    }

    /**
     * Get provider's schedule for a specific date
     *
     * @param int $provider_id
     * @param string $date
     * @return array Schedule periods
     * @since 1.0.0
     */
    private function get_provider_schedule($provider_id, $date) {
        // Get day of week (0 = Sunday, 6 = Saturday)
        $day_of_week = date('w', strtotime($date));
        
        // Default schedule (this would be stored in provider settings)
        $default_schedule = array(
            1 => array(array('start_time' => '08:00:00', 'end_time' => '17:00:00')), // Monday
            2 => array(array('start_time' => '08:00:00', 'end_time' => '17:00:00')), // Tuesday
            3 => array(array('start_time' => '08:00:00', 'end_time' => '17:00:00')), // Wednesday
            4 => array(array('start_time' => '08:00:00', 'end_time' => '17:00:00')), // Thursday
            5 => array(array('start_time' => '08:00:00', 'end_time' => '17:00:00')), // Friday
            0 => array(), // Sunday - closed
            6 => array()  // Saturday - closed
        );

        // TODO: Get actual schedule from provider settings
        // For now, return default schedule
        return $default_schedule[$day_of_week] ?? array();
    }

    /**
     * Generate time slots for a period
     *
     * @param string $start_datetime
     * @param string $end_datetime
     * @param int $interval_minutes
     * @return array Time slots
     * @since 1.0.0
     */
    private function generate_time_slots($start_datetime, $end_datetime, $interval_minutes = 30) {
        $slots = array();
        $current = strtotime($start_datetime);
        $end = strtotime($end_datetime);
        
        while ($current < $end) {
            $slot_datetime = date('Y-m-d H:i:s', $current);
            $slot_time = date('H:i:s', $current);
            $display_time = date_i18n(get_option('time_format'), $current);
            
            $slots[] = array(
                'datetime' => $slot_datetime,
                'time' => $slot_time,
                'display' => $display_time
            );
            
            $current += ($interval_minutes * 60);
        }
        
        return $slots;
    }

    /**
     * Check if two time periods overlap
     *
     * @param string $start1
     * @param string $end1
     * @param string $start2
     * @param string $end2
     * @return bool
     * @since 1.0.0
     */
    private function times_overlap($start1, $end1, $start2, $end2) {
        return (strtotime($start1) < strtotime($end2)) && (strtotime($end1) > strtotime($start2));
    }

    /**
     * Apply booking business rules
     *
     * @param array $slots
     * @param string $date
     * @return array Filtered slots
     * @since 1.0.0
     */
    private function apply_booking_rules($slots, $date) {
        $filtered_slots = array();
        $now = current_time('timestamp');
        $booking_cutoff = $now + (2 * HOUR_IN_SECONDS); // 2 hours advance booking required
        
        foreach ($slots as $slot) {
            $slot_timestamp = strtotime($slot['datetime']);
            
            // Skip slots that are too soon
            if ($slot_timestamp < $booking_cutoff) {
                continue;
            }
            
            // Skip lunch hour (12:00 PM - 1:00 PM)
            $slot_hour = date('H', $slot_timestamp);
            if ($slot_hour == 12) {
                continue;
            }
            
            $filtered_slots[] = $slot;
        }
        
        return $filtered_slots;
    }

    /**
     * Book an appointment
     *
     * @param array $appointment_data
     * @param array $patient_data
     * @return array Result with success status and data
     * @since 1.0.0
     */
    public function book_appointment($appointment_data, $patient_data) {
        try {
            // Validate required data
            $this->validate_booking_data($appointment_data, $patient_data);
            
            // Check availability one more time
            $duration = $this->get_appointment_duration($appointment_data['appointment_type_id']);
            $available_slots = $this->check_availability(
                $appointment_data['provider_id'],
                $appointment_data['location_id'],
                $appointment_data['date'],
                $duration
            );
            
            $requested_time = $appointment_data['date'] . ' ' . $appointment_data['time'];
            $time_available = false;
            
            foreach ($available_slots as $slot) {
                if ($slot['datetime'] === $requested_time) {
                    $time_available = true;
                    break;
                }
            }
            
            if (!$time_available) {
                throw new Exception(__('The selected time slot is no longer available.', 'eye-book'));
            }
            
            // Create or find patient
            $patient = $this->create_or_find_patient($patient_data);
            if (!$patient) {
                throw new Exception(__('Failed to create patient record.', 'eye-book'));
            }
            
            // Create appointment
            $appointment = new Eye_Book_Appointment();
            $appointment->patient_id = $patient->id;
            $appointment->provider_id = $appointment_data['provider_id'];
            $appointment->location_id = $appointment_data['location_id'];
            $appointment->appointment_type_id = $appointment_data['appointment_type_id'];
            $appointment->start_datetime = $requested_time;
            $appointment->end_datetime = date('Y-m-d H:i:s', strtotime($requested_time . ' +' . $duration . ' minutes'));
            $appointment->status = 'scheduled';
            $appointment->booking_source = 'online';
            $appointment->chief_complaint = $appointment_data['chief_complaint'] ?? '';
            $appointment->notes = $appointment_data['notes'] ?? '';
            
            // Save appointment
            $appointment_id = $appointment->save();
            if (!$appointment_id) {
                throw new Exception(__('Failed to save appointment.', 'eye-book'));
            }
            
            // Send confirmation
            $this->send_booking_confirmation($appointment, $patient);
            
            // Log successful booking
            Eye_Book_Audit::log('appointment_booked_online', 'appointment', $appointment_id, array(
                'patient_id' => $patient->id,
                'provider_id' => $appointment_data['provider_id'],
                'booking_source' => 'online'
            ));
            
            return array(
                'success' => true,
                'appointment_id' => $appointment->appointment_id,
                'message' => __('Appointment booked successfully!', 'eye-book')
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Validate booking data
     *
     * @param array $appointment_data
     * @param array $patient_data
     * @throws Exception
     * @since 1.0.0
     */
    private function validate_booking_data($appointment_data, $patient_data) {
        // Required appointment fields
        $required_appointment_fields = array('provider_id', 'location_id', 'appointment_type_id', 'date', 'time');
        foreach ($required_appointment_fields as $field) {
            if (empty($appointment_data[$field])) {
                throw new Exception(sprintf(__('Required field missing: %s', 'eye-book'), $field));
            }
        }
        
        // Required patient fields
        $required_patient_fields = array('first_name', 'last_name', 'email', 'phone', 'date_of_birth');
        foreach ($required_patient_fields as $field) {
            if (empty($patient_data[$field])) {
                throw new Exception(sprintf(__('Required patient field missing: %s', 'eye-book'), $field));
            }
        }
        
        // Validate email
        if (!is_email($patient_data['email'])) {
            throw new Exception(__('Invalid email address.', 'eye-book'));
        }
        
        // Validate date
        if (!strtotime($appointment_data['date'])) {
            throw new Exception(__('Invalid appointment date.', 'eye-book'));
        }
        
        // Check if date is in the future
        if (strtotime($appointment_data['date']) < strtotime('today')) {
            throw new Exception(__('Appointment date must be in the future.', 'eye-book'));
        }
        
        // Check booking advance limit
        $advance_days = get_option('eye_book_booking_advance_days', 30);
        $max_date = strtotime('+' . $advance_days . ' days');
        if (strtotime($appointment_data['date']) > $max_date) {
            throw new Exception(sprintf(__('Appointments can only be booked up to %d days in advance.', 'eye-book'), $advance_days));
        }
    }

    /**
     * Get appointment duration by type
     *
     * @param int $appointment_type_id
     * @return int Duration in minutes
     * @since 1.0.0
     */
    private function get_appointment_duration($appointment_type_id) {
        global $wpdb;
        
        $duration = $wpdb->get_var($wpdb->prepare(
            "SELECT duration FROM " . EYE_BOOK_TABLE_APPOINTMENT_TYPES . " WHERE id = %d",
            $appointment_type_id
        ));
        
        return $duration ?: get_option('eye_book_default_appointment_duration', 30);
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
            $needs_update = false;
            foreach ($patient_data as $key => $value) {
                if (property_exists($existing_patient, $key) && $existing_patient->$key !== $value) {
                    $existing_patient->$key = $value;
                    $needs_update = true;
                }
            }
            
            if ($needs_update) {
                $existing_patient->save();
            }
            
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
            __("Dear %s,\n\nYour appointment has been confirmed:\n\nAppointment ID: %s\nDate: %s\nTime: %s\nProvider: %s\nLocation: %s\n\nPlease arrive 15 minutes early for check-in. If you need to reschedule or cancel, please contact us at least 24 hours in advance.\n\nYou can access your patient portal using this secure link:\n%s\n\nThank you,\n%s", 'eye-book'),
            $patient->first_name . ' ' . $patient->last_name,
            $appointment->appointment_id,
            $appointment_date,
            $appointment_time,
            $provider ? $provider->get_display_name() : __('TBD', 'eye-book'),
            $location ? $location->name : __('TBD', 'eye-book'),
            $this->generate_patient_portal_link($patient),
            get_bloginfo('name')
        );
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        wp_mail($patient->email, $subject, $message, $headers);
    }

    /**
     * Generate patient portal access link
     *
     * @param Eye_Book_Patient $patient
     * @return string Portal URL
     * @since 1.0.0
     */
    private function generate_patient_portal_link($patient) {
        $token = Eye_Book_Encryption::generate_patient_token($patient->id, 72); // 72 hour expiry
        $portal_url = add_query_arg('patient_token', $token, home_url('/patient-portal/'));
        
        return $portal_url;
    }

    /**
     * AJAX handler for checking availability
     *
     * @since 1.0.0
     */
    public function ajax_check_availability() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eye_book_public_nonce')) {
            wp_send_json_error(__('Security check failed', 'eye-book'));
        }
        
        $provider_id = intval($_POST['provider_id']);
        $location_id = intval($_POST['location_id']);
        $date = sanitize_text_field($_POST['date']);
        $appointment_type_id = intval($_POST['appointment_type_id']);
        
        $duration = $this->get_appointment_duration($appointment_type_id);
        $available_slots = $this->check_availability($provider_id, $location_id, $date, $duration);
        
        wp_send_json_success($available_slots);
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
        
        $appointment_data = array(
            'provider_id' => intval($_POST['provider_id']),
            'location_id' => intval($_POST['location_id']),
            'appointment_type_id' => intval($_POST['appointment_type_id']),
            'date' => sanitize_text_field($_POST['appointment_date']),
            'time' => sanitize_text_field($_POST['appointment_time']),
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
        
        $result = $this->book_appointment($appointment_data, $patient_data);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX handler for canceling appointment
     *
     * @since 1.0.0
     */
    public function ajax_cancel_appointment() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eye_book_public_nonce')) {
            wp_send_json_error(__('Security check failed', 'eye-book'));
        }
        
        $appointment_id = intval($_POST['appointment_id']);
        $cancellation_reason = sanitize_text_field($_POST['cancellation_reason']);
        
        $appointment = new Eye_Book_Appointment($appointment_id);
        
        if (!$appointment->id) {
            wp_send_json_error(__('Appointment not found', 'eye-book'));
        }
        
        if ($appointment->cancel($cancellation_reason)) {
            wp_send_json_success(__('Appointment cancelled successfully', 'eye-book'));
        } else {
            wp_send_json_error(__('Failed to cancel appointment', 'eye-book'));
        }
    }

    /**
     * AJAX handler for updating patient profile
     *
     * @since 1.0.0
     */
    public function ajax_update_profile() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'eye_book_public_nonce')) {
            wp_send_json_error(__('Security check failed', 'eye-book'));
        }
        
        $patient_id = intval($_POST['patient_id']);
        $patient = new Eye_Book_Patient($patient_id);
        
        if (!$patient->id) {
            wp_send_json_error(__('Patient not found', 'eye-book'));
        }
        
        // Update patient data
        $patient->first_name = sanitize_text_field($_POST['first_name']);
        $patient->last_name = sanitize_text_field($_POST['last_name']);
        $patient->phone = sanitize_text_field($_POST['phone']);
        $patient->email = sanitize_email($_POST['email']);
        $patient->insurance_provider = sanitize_text_field($_POST['insurance_provider']);
        
        if ($patient->save()) {
            wp_send_json_success(__('Profile updated successfully', 'eye-book'));
        } else {
            wp_send_json_error(__('Failed to update profile', 'eye-book'));
        }
    }
}