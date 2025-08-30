<?php
/**
 * Appointment model class for Eye-Book plugin
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
 * Eye_Book_Appointment Class
 *
 * Handles appointment data operations and business logic
 *
 * @class Eye_Book_Appointment
 * @since 1.0.0
 */
class Eye_Book_Appointment {

    /**
     * Appointment ID
     *
     * @var int
     * @since 1.0.0
     */
    public $id;

    /**
     * Appointment unique identifier
     *
     * @var string
     * @since 1.0.0
     */
    public $appointment_id;

    /**
     * Patient ID
     *
     * @var int
     * @since 1.0.0
     */
    public $patient_id;

    /**
     * Provider ID
     *
     * @var int
     * @since 1.0.0
     */
    public $provider_id;

    /**
     * Location ID
     *
     * @var int
     * @since 1.0.0
     */
    public $location_id;

    /**
     * Appointment type ID
     *
     * @var int
     * @since 1.0.0
     */
    public $appointment_type_id;

    /**
     * Start datetime
     *
     * @var string
     * @since 1.0.0
     */
    public $start_datetime;

    /**
     * End datetime
     *
     * @var string
     * @since 1.0.0
     */
    public $end_datetime;

    /**
     * Appointment status
     *
     * @var string
     * @since 1.0.0
     */
    public $status;

    /**
     * Constructor
     *
     * @param int|array $appointment Appointment ID or data array
     * @since 1.0.0
     */
    public function __construct($appointment = null) {
        if (is_numeric($appointment)) {
            $this->load($appointment);
        } elseif (is_array($appointment)) {
            $this->populate($appointment);
        }
    }

    /**
     * Load appointment by ID
     *
     * @param int $appointment_id
     * @return bool Success status
     * @since 1.0.0
     */
    public function load($appointment_id) {
        global $wpdb;

        $appointment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " WHERE id = %d",
            $appointment_id
        ), ARRAY_A);

        if ($appointment) {
            $this->populate($appointment);
            return true;
        }

        return false;
    }

    /**
     * Populate object properties from array
     *
     * @param array $data Appointment data
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
     * Save appointment to database
     *
     * @return bool|int Appointment ID on success, false on failure
     * @since 1.0.0
     */
    public function save() {
        global $wpdb;

        $data = $this->to_array();
        $old_data = null;

        if ($this->id) {
            // Update existing appointment
            $old_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " WHERE id = %d",
                $this->id
            ), ARRAY_A);

            $result = $wpdb->update(
                EYE_BOOK_TABLE_APPOINTMENTS,
                $data,
                array('id' => $this->id)
            );

            if ($result !== false) {
                Eye_Book_Audit::log_appointment_action($this->id, 'updated', $old_data, $data);
                do_action('eye_book_appointment_updated', $this, $old_data);
                return $this->id;
            }
        } else {
            // Create new appointment
            if (empty($this->appointment_id)) {
                $this->appointment_id = $this->generate_appointment_id();
                $data['appointment_id'] = $this->appointment_id;
            }

            $result = $wpdb->insert(
                EYE_BOOK_TABLE_APPOINTMENTS,
                $data
            );

            if ($result !== false) {
                $this->id = $wpdb->insert_id;
                Eye_Book_Audit::log_appointment_action($this->id, 'created', null, $data);
                do_action('eye_book_appointment_created', $this);
                return $this->id;
            }
        }

        return false;
    }

    /**
     * Create new appointment
     *
     * @param array $data Appointment data
     * @return int|bool Appointment ID on success, false on failure
     * @since 1.0.0
     */
    public function create($data = array()) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        $this->id = null; // Ensure new record
        return $this->save();
    }

    /**
     * Update existing appointment
     *
     * @param array $data Appointment data
     * @return bool Success status
     * @since 1.0.0
     */
    public function update($data = array()) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        return $this->save();
    }

    /**
     * Get appointment ID
     *
     * @return int Appointment ID
     * @since 1.0.0
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Delete appointment
     *
     * @return bool Success status
     * @since 1.0.0
     */
    public function delete() {
        global $wpdb;

        if (!$this->id) {
            return false;
        }

        $old_data = $this->to_array();

        $result = $wpdb->delete(
            EYE_BOOK_TABLE_APPOINTMENTS,
            array('id' => $this->id)
        );

        if ($result !== false) {
            Eye_Book_Audit::log_appointment_action($this->id, 'deleted', $old_data);
            do_action('eye_book_appointment_deleted', $this);
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
            'patient_id' => $this->patient_id,
            'provider_id' => $this->provider_id,
            'location_id' => $this->location_id,
            'appointment_type_id' => $this->appointment_type_id,
            'start_datetime' => $this->start_datetime,
            'end_datetime' => $this->end_datetime,
            'status' => $this->status,
            'booking_source' => $this->booking_source ?? 'online',
            'chief_complaint' => $this->chief_complaint ?? '',
            'notes' => $this->notes ?? '',
            'internal_notes' => $this->internal_notes ?? ''
        );
    }

    /**
     * Generate unique appointment ID
     *
     * @return string
     * @since 1.0.0
     */
    private function generate_appointment_id() {
        $prefix = get_option('eye_book_appointment_id_prefix', 'APT');
        $date = date('Ymd');
        
        global $wpdb;
        
        do {
            $random = wp_rand(1000, 9999);
            $appointment_id = $prefix . $date . $random;
            
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " WHERE appointment_id = %s",
                $appointment_id
            ));
        } while ($exists > 0);
        
        return $appointment_id;
    }

    /**
     * Check for appointment conflicts
     *
     * @return array Conflicting appointments
     * @since 1.0.0
     */
    public function check_conflicts() {
        global $wpdb;

        $conflicts = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM " . EYE_BOOK_TABLE_APPOINTMENTS . "
            WHERE provider_id = %d
            AND location_id = %d
            AND status NOT IN ('cancelled', 'no_show')
            AND (
                (start_datetime < %s AND end_datetime > %s) OR
                (start_datetime < %s AND end_datetime > %s) OR
                (start_datetime >= %s AND end_datetime <= %s)
            )
            " . ($this->id ? "AND id != %d" : ""),
            $this->provider_id,
            $this->location_id,
            $this->end_datetime,
            $this->start_datetime,
            $this->start_datetime,
            $this->end_datetime,
            $this->start_datetime,
            $this->end_datetime,
            $this->id
        ));

        return $conflicts;
    }

    /**
     * Get appointment duration in minutes
     *
     * @return int Duration in minutes
     * @since 1.0.0
     */
    public function get_duration() {
        $start = strtotime($this->start_datetime);
        $end = strtotime($this->end_datetime);
        
        return ($end - $start) / 60;
    }

    /**
     * Check if appointment can be cancelled
     *
     * @return bool
     * @since 1.0.0
     */
    public function can_cancel() {
        if (in_array($this->status, array('cancelled', 'completed', 'no_show'))) {
            return false;
        }

        // Check if cancellation is within allowed timeframe
        $cancellation_hours = get_option('eye_book_cancellation_hours', 24);
        $appointment_time = strtotime($this->start_datetime);
        $cutoff_time = time() + ($cancellation_hours * HOUR_IN_SECONDS);

        return $appointment_time > $cutoff_time;
    }

    /**
     * Cancel appointment
     *
     * @param string $reason Cancellation reason
     * @param int $cancelled_by User ID who cancelled
     * @return bool Success status
     * @since 1.0.0
     */
    public function cancel($reason = '', $cancelled_by = null) {
        if (!$this->can_cancel()) {
            return false;
        }

        $old_status = $this->status;
        $this->status = 'cancelled';
        $this->cancellation_reason = $reason;
        $this->cancelled_at = current_time('mysql', true);

        if ($this->save()) {
            // Log cancellation
            Eye_Book_Audit::log_appointment_action($this->id, 'cancelled', 
                array('status' => $old_status),
                array('status' => 'cancelled', 'reason' => $reason, 'cancelled_by' => $cancelled_by)
            );

            // Trigger actions
            do_action('eye_book_appointment_cancelled', $this, $reason);

            return true;
        }

        return false;
    }

    /**
     * Reschedule appointment
     *
     * @param string $new_start_datetime New start datetime
     * @param string $new_end_datetime New end datetime
     * @return bool Success status
     * @since 1.0.0
     */
    public function reschedule($new_start_datetime, $new_end_datetime) {
        $old_start = $this->start_datetime;
        $old_end = $this->end_datetime;
        
        $this->start_datetime = $new_start_datetime;
        $this->end_datetime = $new_end_datetime;
        
        // Check for conflicts
        $conflicts = $this->check_conflicts();
        if (!empty($conflicts)) {
            // Restore original times
            $this->start_datetime = $old_start;
            $this->end_datetime = $old_end;
            return false;
        }
        
        if ($this->save()) {
            Eye_Book_Audit::log_appointment_action($this->id, 'rescheduled',
                array('start_datetime' => $old_start, 'end_datetime' => $old_end),
                array('start_datetime' => $new_start_datetime, 'end_datetime' => $new_end_datetime)
            );
            
            do_action('eye_book_appointment_rescheduled', $this, $old_start, $old_end);
            return true;
        }
        
        return false;
    }

    /**
     * Check in patient for appointment
     *
     * @return bool Success status
     * @since 1.0.0
     */
    public function check_in() {
        if ($this->status !== 'scheduled' && $this->status !== 'confirmed') {
            return false;
        }

        $this->status = 'checked_in';
        $this->checked_in_at = current_time('mysql', true);

        if ($this->save()) {
            do_action('eye_book_appointment_checked_in', $this);
            return true;
        }

        return false;
    }

    /**
     * Complete appointment
     *
     * @param string $notes Completion notes
     * @return bool Success status
     * @since 1.0.0
     */
    public function complete($notes = '') {
        if ($this->status !== 'in_progress' && $this->status !== 'checked_in') {
            return false;
        }

        $this->status = 'completed';
        $this->completed_at = current_time('mysql', true);
        
        if ($notes) {
            $this->notes = $notes;
        }

        if ($this->save()) {
            do_action('eye_book_appointment_completed', $this);
            return true;
        }

        return false;
    }

    /**
     * Get patient object
     *
     * @return Eye_Book_Patient|null
     * @since 1.0.0
     */
    public function get_patient() {
        if ($this->patient_id) {
            return new Eye_Book_Patient($this->patient_id);
        }
        return null;
    }

    /**
     * Get provider object
     *
     * @return Eye_Book_Provider|null
     * @since 1.0.0
     */
    public function get_provider() {
        if ($this->provider_id) {
            return new Eye_Book_Provider($this->provider_id);
        }
        return null;
    }

    /**
     * Get location object
     *
     * @return Eye_Book_Location|null
     * @since 1.0.0
     */
    public function get_location() {
        if ($this->location_id) {
            return new Eye_Book_Location($this->location_id);
        }
        return null;
    }

    /**
     * Send appointment reminder
     *
     * @param string $method Reminder method (email, sms, both)
     * @return bool Success status
     * @since 1.0.0
     */
    public function send_reminder($method = 'email') {
        $patient = $this->get_patient();
        $provider = $this->get_provider();
        $location = $this->get_location();

        if (!$patient || !$provider || !$location) {
            return false;
        }

        $sent = false;

        if ($method === 'email' || $method === 'both') {
            $sent = $this->send_email_reminder($patient, $provider, $location) || $sent;
        }

        if ($method === 'sms' || $method === 'both') {
            $sent = $this->send_sms_reminder($patient, $provider, $location) || $sent;
        }

        if ($sent) {
            global $wpdb;
            $wpdb->update(
                EYE_BOOK_TABLE_APPOINTMENTS,
                array('reminder_sent_at' => current_time('mysql', true)),
                array('id' => $this->id)
            );

            do_action('eye_book_appointment_reminder_sent', $this, $method);
        }

        return $sent;
    }

    /**
     * Send email reminder
     *
     * @param Eye_Book_Patient $patient
     * @param Eye_Book_Provider $provider
     * @param Eye_Book_Location $location
     * @return bool Success status
     * @since 1.0.0
     */
    private function send_email_reminder($patient, $provider, $location) {
        if (!$patient->email) {
            return false;
        }

        $subject = sprintf(__('Appointment Reminder - %s', 'eye-book'), get_bloginfo('name'));
        
        $appointment_date = date_i18n(get_option('date_format'), strtotime($this->start_datetime));
        $appointment_time = date_i18n(get_option('time_format'), strtotime($this->start_datetime));

        $message = sprintf(
            __("Dear %s,\n\nThis is a reminder that you have an appointment scheduled:\n\nDate: %s\nTime: %s\nProvider: %s\nLocation: %s\n\nIf you need to reschedule or cancel, please contact us as soon as possible.\n\nThank you,\n%s", 'eye-book'),
            $patient->first_name . ' ' . $patient->last_name,
            $appointment_date,
            $appointment_time,
            $provider->get_display_name(),
            $location->name,
            get_bloginfo('name')
        );

        return wp_mail($patient->email, $subject, $message);
    }

    /**
     * Send SMS reminder
     *
     * @param Eye_Book_Patient $patient
     * @param Eye_Book_Provider $provider
     * @param Eye_Book_Location $location
     * @return bool Success status
     * @since 1.0.0
     */
    private function send_sms_reminder($patient, $provider, $location) {
        if (!$patient->phone) {
            return false;
        }

        $appointment_date = date_i18n('M j', strtotime($this->start_datetime));
        $appointment_time = date_i18n('g:i a', strtotime($this->start_datetime));

        $message = sprintf(
            __('Appointment reminder: %s at %s with %s. Contact us to reschedule/cancel.', 'eye-book'),
            $appointment_date,
            $appointment_time,
            $provider->get_display_name()
        );

        // This would integrate with SMS service provider
        return apply_filters('eye_book_send_sms', false, $patient->phone, $message);
    }

    /**
     * Get all appointments with filtering
     *
     * @param array $args Query arguments
     * @return array Appointments
     * @since 1.0.0
     */
    public static function get_appointments($args = array()) {
        global $wpdb;

        $defaults = array(
            'patient_id' => null,
            'provider_id' => null,
            'location_id' => null,
            'status' => null,
            'date_from' => null,
            'date_to' => null,
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'start_datetime',
            'order' => 'ASC'
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array('1=1');
        $where_values = array();

        if ($args['patient_id']) {
            $where_clauses[] = 'patient_id = %d';
            $where_values[] = intval($args['patient_id']);
        }

        if ($args['provider_id']) {
            $where_clauses[] = 'provider_id = %d';
            $where_values[] = intval($args['provider_id']);
        }

        if ($args['location_id']) {
            $where_clauses[] = 'location_id = %d';
            $where_values[] = intval($args['location_id']);
        }

        if ($args['status']) {
            if (is_array($args['status'])) {
                $placeholders = implode(',', array_fill(0, count($args['status']), '%s'));
                $where_clauses[] = "status IN ($placeholders)";
                $where_values = array_merge($where_values, $args['status']);
            } else {
                $where_clauses[] = 'status = %s';
                $where_values[] = $args['status'];
            }
        }

        if ($args['date_from']) {
            $where_clauses[] = 'start_datetime >= %s';
            $where_values[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where_clauses[] = 'start_datetime <= %s';
            $where_values[] = $args['date_to'];
        }

        $where_clause = implode(' AND ', $where_clauses);

        $allowed_orderby = array('start_datetime', 'created_at', 'patient_id', 'provider_id');
        $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'start_datetime';
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';

        $query = "SELECT * FROM " . EYE_BOOK_TABLE_APPOINTMENTS . "
                  WHERE $where_clause
                  ORDER BY $orderby $order
                  LIMIT %d OFFSET %d";

        $where_values[] = intval($args['limit']);
        $where_values[] = intval($args['offset']);

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $results = $wpdb->get_results($query, ARRAY_A);
        $appointments = array();

        foreach ($results as $result) {
            $appointments[] = new self($result);
        }

        return $appointments;
    }

    /**
     * Count appointments with optional filters
     *
     * @param array $args Query arguments
     * @return int Appointment count
     * @since 1.0.0
     */
    public static function count_appointments($args = array()) {
        global $wpdb;

        $defaults = array(
            'patient_id' => '',
            'provider_id' => '',
            'location_id' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'search' => ''
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array('1=1'); // Base condition
        $where_values = array();

        if ($args['patient_id']) {
            $where_clauses[] = 'patient_id = %d';
            $where_values[] = intval($args['patient_id']);
        }

        if ($args['provider_id']) {
            $where_clauses[] = 'provider_id = %d';
            $where_values[] = intval($args['provider_id']);
        }

        if ($args['location_id']) {
            $where_clauses[] = 'location_id = %d';
            $where_values[] = intval($args['location_id']);
        }

        if ($args['status']) {
            if (is_array($args['status'])) {
                $placeholders = implode(',', array_fill(0, count($args['status']), '%s'));
                $where_clauses[] = "status IN ($placeholders)";
                $where_values = array_merge($where_values, $args['status']);
            } else {
                $where_clauses[] = 'status = %s';
                $where_values[] = $args['status'];
            }
        }

        if ($args['date_from']) {
            $where_clauses[] = 'start_datetime >= %s';
            $where_values[] = $args['date_from'];
        }

        if ($args['date_to']) {
            $where_clauses[] = 'start_datetime <= %s';
            $where_values[] = $args['date_to'];
        }

        if ($args['search']) {
            $where_clauses[] = '(notes LIKE %s OR chief_complaint LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }

        $where_clause = implode(' AND ', $where_clauses);

        $query = "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " WHERE $where_clause";

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return $wpdb->get_var($query);
    }
}