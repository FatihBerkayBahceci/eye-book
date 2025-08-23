<?php
/**
 * Notifications management class for Eye-Book plugin
 *
 * @package EyeBook
 * @subpackage Notifications
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eye_Book_Notifications Class
 *
 * Handles email and SMS notifications for appointments, reminders, and confirmations
 *
 * @class Eye_Book_Notifications
 * @since 1.0.0
 */
class Eye_Book_Notifications {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('eye_book_send_reminders', array($this, 'send_appointment_reminders'));
        add_action('eye_book_appointment_created', array($this, 'send_booking_confirmation'));
        add_action('eye_book_appointment_cancelled', array($this, 'send_cancellation_notification'));
        add_action('eye_book_appointment_rescheduled', array($this, 'send_reschedule_notification'));
        
        // Schedule reminder cron job
        if (!wp_next_scheduled('eye_book_send_reminders')) {
            wp_schedule_event(time(), 'hourly', 'eye_book_send_reminders');
        }
    }

    /**
     * Send appointment reminders based on settings
     *
     * @since 1.0.0
     */
    public function send_appointment_reminders() {
        if (!get_option('eye_book_email_reminders_enabled', 1) && !get_option('eye_book_sms_reminders_enabled', 0)) {
            return;
        }

        // Get appointments that need reminders
        $email_appointments = $this->get_appointments_needing_email_reminders();
        $sms_appointments = $this->get_appointments_needing_sms_reminders();

        // Send email reminders
        if (get_option('eye_book_email_reminders_enabled', 1)) {
            foreach ($email_appointments as $appointment) {
                $this->send_email_reminder($appointment);
            }
        }

        // Send SMS reminders
        if (get_option('eye_book_sms_reminders_enabled', 0)) {
            foreach ($sms_appointments as $appointment) {
                $this->send_sms_reminder($appointment);
            }
        }

        // Log reminder activity
        if (!empty($email_appointments) || !empty($sms_appointments)) {
            Eye_Book_Audit::log('reminders_sent', 'system', null, array(
                'email_count' => count($email_appointments),
                'sms_count' => count($sms_appointments)
            ));
        }
    }

    /**
     * Get appointments that need email reminders
     *
     * @return array
     * @since 1.0.0
     */
    private function get_appointments_needing_email_reminders() {
        global $wpdb;

        $reminder_hours = get_option('eye_book_reminder_email_hours', 24);
        $reminder_time = date('Y-m-d H:i:s', time() + ($reminder_hours * HOUR_IN_SECONDS));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, p.email, p.first_name, p.last_name 
             FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a
             JOIN " . EYE_BOOK_TABLE_PATIENTS . " p ON a.patient_id = p.id
             WHERE a.start_datetime BETWEEN %s AND %s
             AND a.status IN ('scheduled', 'confirmed')
             AND a.reminder_sent_at IS NULL
             AND p.email IS NOT NULL AND p.email != ''
             ORDER BY a.start_datetime",
            date('Y-m-d H:i:s', time() + (($reminder_hours - 1) * HOUR_IN_SECONDS)),
            $reminder_time
        ));
    }

    /**
     * Get appointments that need SMS reminders
     *
     * @return array
     * @since 1.0.0
     */
    private function get_appointments_needing_sms_reminders() {
        global $wpdb;

        $reminder_hours = get_option('eye_book_reminder_sms_hours', 2);
        $reminder_time = date('Y-m-d H:i:s', time() + ($reminder_hours * HOUR_IN_SECONDS));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, p.phone, p.first_name, p.last_name 
             FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a
             JOIN " . EYE_BOOK_TABLE_PATIENTS . " p ON a.patient_id = p.id
             WHERE a.start_datetime BETWEEN %s AND %s
             AND a.status IN ('scheduled', 'confirmed')
             AND (a.reminder_sent_at IS NULL OR a.reminder_sent_at < DATE_SUB(NOW(), INTERVAL %d HOUR))
             AND p.phone IS NOT NULL AND p.phone != ''
             ORDER BY a.start_datetime",
            date('Y-m-d H:i:s', time() + (($reminder_hours - 1) * HOUR_IN_SECONDS)),
            $reminder_time,
            $reminder_hours + 1
        ));
    }

    /**
     * Send email reminder for appointment
     *
     * @param object $appointment_data
     * @return bool
     * @since 1.0.0
     */
    public function send_email_reminder($appointment_data) {
        if (empty($appointment_data->email)) {
            return false;
        }

        $appointment = new Eye_Book_Appointment($appointment_data->id);
        $provider = $appointment->get_provider();
        $location = $appointment->get_location();

        $appointment_date = date_i18n(get_option('date_format'), strtotime($appointment->start_datetime));
        $appointment_time = date_i18n(get_option('time_format'), strtotime($appointment->start_datetime));

        $subject = sprintf(__('Appointment Reminder - %s', 'eye-book'), get_bloginfo('name'));

        // Load email template
        $template = $this->get_email_template('appointment_reminder');
        
        $variables = array(
            '{patient_name}' => $appointment_data->first_name . ' ' . $appointment_data->last_name,
            '{appointment_date}' => $appointment_date,
            '{appointment_time}' => $appointment_time,
            '{provider_name}' => $provider ? $provider->get_display_name() : __('TBD', 'eye-book'),
            '{location_name}' => $location ? $location->name : __('TBD', 'eye-book'),
            '{location_address}' => $location ? $location->get_formatted_address() : '',
            '{location_phone}' => $location ? $location->phone : get_option('eye_book_clinic_phone', ''),
            '{clinic_name}' => get_bloginfo('name'),
            '{appointment_id}' => $appointment->appointment_id,
            '{portal_link}' => $this->generate_patient_portal_link($appointment->patient_id)
        );

        $message = str_replace(array_keys($variables), array_values($variables), $template);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        $sent = wp_mail($appointment_data->email, $subject, $message, $headers);

        if ($sent) {
            // Update reminder sent timestamp
            global $wpdb;
            $wpdb->update(
                EYE_BOOK_TABLE_APPOINTMENTS,
                array('reminder_sent_at' => current_time('mysql', true)),
                array('id' => $appointment->id)
            );

            // Log reminder sent
            Eye_Book_Audit::log('email_reminder_sent', 'appointment', $appointment->id, array(
                'patient_id' => $appointment->patient_id,
                'email' => $appointment_data->email
            ));
        }

        return $sent;
    }

    /**
     * Send SMS reminder for appointment
     *
     * @param object $appointment_data
     * @return bool
     * @since 1.0.0
     */
    public function send_sms_reminder($appointment_data) {
        if (empty($appointment_data->phone)) {
            return false;
        }

        $appointment = new Eye_Book_Appointment($appointment_data->id);
        $provider = $appointment->get_provider();
        $location = $appointment->get_location();

        $appointment_date = date_i18n('M j', strtotime($appointment->start_datetime));
        $appointment_time = date_i18n('g:i a', strtotime($appointment->start_datetime));

        $message = sprintf(
            __('Reminder: You have an appointment on %s at %s with %s. Reply STOP to opt out. - %s', 'eye-book'),
            $appointment_date,
            $appointment_time,
            $provider ? $provider->get_display_name() : __('TBD', 'eye-book'),
            get_bloginfo('name')
        );

        // Apply SMS character limit
        if (strlen($message) > 160) {
            $message = sprintf(
                __('Appointment reminder: %s at %s. Contact us for details. - %s', 'eye-book'),
                $appointment_date,
                $appointment_time,
                get_bloginfo('name')
            );
        }

        $sent = $this->send_sms($appointment_data->phone, $message);

        if ($sent) {
            // Log SMS sent
            Eye_Book_Audit::log('sms_reminder_sent', 'appointment', $appointment->id, array(
                'patient_id' => $appointment->patient_id,
                'phone' => $appointment_data->phone
            ));
        }

        return $sent;
    }

    /**
     * Send booking confirmation
     *
     * @param Eye_Book_Appointment $appointment
     * @since 1.0.0
     */
    public function send_booking_confirmation($appointment) {
        $patient = $appointment->get_patient();
        
        if (!$patient || !$patient->email) {
            return;
        }

        $provider = $appointment->get_provider();
        $location = $appointment->get_location();

        $appointment_date = date_i18n(get_option('date_format'), strtotime($appointment->start_datetime));
        $appointment_time = date_i18n(get_option('time_format'), strtotime($appointment->start_datetime));

        $subject = sprintf(__('Appointment Confirmed - %s', 'eye-book'), get_bloginfo('name'));

        // Load email template
        $template = $this->get_email_template('booking_confirmation');
        
        $variables = array(
            '{patient_name}' => $patient->get_full_name(),
            '{appointment_date}' => $appointment_date,
            '{appointment_time}' => $appointment_time,
            '{provider_name}' => $provider ? $provider->get_display_name() : __('TBD', 'eye-book'),
            '{location_name}' => $location ? $location->name : __('TBD', 'eye-book'),
            '{location_address}' => $location ? $location->get_formatted_address() : '',
            '{location_phone}' => $location ? $location->phone : get_option('eye_book_clinic_phone', ''),
            '{clinic_name}' => get_bloginfo('name'),
            '{appointment_id}' => $appointment->appointment_id,
            '{portal_link}' => $this->generate_patient_portal_link($appointment->patient_id),
            '{cancellation_policy}' => get_option('eye_book_cancellation_policy', __('Please contact us at least 24 hours in advance to reschedule or cancel.', 'eye-book'))
        );

        $message = str_replace(array_keys($variables), array_values($variables), $template);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        $sent = wp_mail($patient->email, $subject, $message, $headers);

        if ($sent) {
            // Update confirmation sent timestamp
            global $wpdb;
            $wpdb->update(
                EYE_BOOK_TABLE_APPOINTMENTS,
                array('confirmation_sent_at' => current_time('mysql', true)),
                array('id' => $appointment->id)
            );

            // Log confirmation sent
            Eye_Book_Audit::log('booking_confirmation_sent', 'appointment', $appointment->id, array(
                'patient_id' => $appointment->patient_id,
                'email' => $patient->email
            ));
        }
    }

    /**
     * Send cancellation notification
     *
     * @param Eye_Book_Appointment $appointment
     * @param string $reason
     * @since 1.0.0
     */
    public function send_cancellation_notification($appointment, $reason = '') {
        $patient = $appointment->get_patient();
        
        if (!$patient || !$patient->email) {
            return;
        }

        $appointment_date = date_i18n(get_option('date_format'), strtotime($appointment->start_datetime));
        $appointment_time = date_i18n(get_option('time_format'), strtotime($appointment->start_datetime));

        $subject = sprintf(__('Appointment Cancelled - %s', 'eye-book'), get_bloginfo('name'));

        $template = $this->get_email_template('appointment_cancelled');
        
        $variables = array(
            '{patient_name}' => $patient->get_full_name(),
            '{appointment_date}' => $appointment_date,
            '{appointment_time}' => $appointment_time,
            '{clinic_name}' => get_bloginfo('name'),
            '{appointment_id}' => $appointment->appointment_id,
            '{cancellation_reason}' => $reason ? $reason : __('No reason provided', 'eye-book'),
            '{rebooking_link}' => home_url('/book-appointment/'),
            '{contact_phone}' => get_option('eye_book_clinic_phone', '')
        );

        $message = str_replace(array_keys($variables), array_values($variables), $template);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        wp_mail($patient->email, $subject, $message, $headers);
    }

    /**
     * Send reschedule notification
     *
     * @param Eye_Book_Appointment $appointment
     * @param string $old_start
     * @param string $old_end
     * @since 1.0.0
     */
    public function send_reschedule_notification($appointment, $old_start, $old_end) {
        $patient = $appointment->get_patient();
        
        if (!$patient || !$patient->email) {
            return;
        }

        $old_date = date_i18n(get_option('date_format'), strtotime($old_start));
        $old_time = date_i18n(get_option('time_format'), strtotime($old_start));
        $new_date = date_i18n(get_option('date_format'), strtotime($appointment->start_datetime));
        $new_time = date_i18n(get_option('time_format'), strtotime($appointment->start_datetime));

        $subject = sprintf(__('Appointment Rescheduled - %s', 'eye-book'), get_bloginfo('name'));

        $template = $this->get_email_template('appointment_rescheduled');
        
        $variables = array(
            '{patient_name}' => $patient->get_full_name(),
            '{old_date}' => $old_date,
            '{old_time}' => $old_time,
            '{new_date}' => $new_date,
            '{new_time}' => $new_time,
            '{clinic_name}' => get_bloginfo('name'),
            '{appointment_id}' => $appointment->appointment_id,
            '{portal_link}' => $this->generate_patient_portal_link($appointment->patient_id)
        );

        $message = str_replace(array_keys($variables), array_values($variables), $template);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        wp_mail($patient->email, $subject, $message, $headers);
    }

    /**
     * Send SMS message
     *
     * @param string $phone
     * @param string $message
     * @return bool
     * @since 1.0.0
     */
    private function send_sms($phone, $message) {
        // This would integrate with SMS service provider (Twilio, etc.)
        // For now, return false to indicate SMS not implemented
        
        $sms_provider = get_option('eye_book_sms_provider', '');
        
        switch ($sms_provider) {
            case 'twilio':
                return $this->send_twilio_sms($phone, $message);
            case 'textmagic':
                return $this->send_textmagic_sms($phone, $message);
            default:
                // Use WordPress hook to allow third-party integration
                return apply_filters('eye_book_send_sms', false, $phone, $message);
        }
    }

    /**
     * Send SMS via Twilio
     *
     * @param string $phone
     * @param string $message
     * @return bool
     * @since 1.0.0
     */
    private function send_twilio_sms($phone, $message) {
        $account_sid = get_option('eye_book_twilio_account_sid', '');
        $auth_token = get_option('eye_book_twilio_auth_token', '');
        $from_number = get_option('eye_book_twilio_from_number', '');

        if (empty($account_sid) || empty($auth_token) || empty($from_number)) {
            return false;
        }

        // Format phone number
        $phone = $this->format_phone_for_sms($phone);

        $url = "https://api.twilio.com/2010-04-01/Accounts/$account_sid/Messages.json";
        
        $data = array(
            'From' => $from_number,
            'To' => $phone,
            'Body' => $message
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode("$account_sid:$auth_token")
            ),
            'body' => $data,
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            error_log('Twilio SMS Error: ' . $response->get_error_message());
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        return $response_code >= 200 && $response_code < 300;
    }

    /**
     * Format phone number for SMS
     *
     * @param string $phone
     * @return string
     * @since 1.0.0
     */
    private function format_phone_for_sms($phone) {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if missing (US)
        if (strlen($phone) === 10) {
            $phone = '1' . $phone;
        }
        
        return '+' . $phone;
    }

    /**
     * Get email template
     *
     * @param string $template_name
     * @return string
     * @since 1.0.0
     */
    private function get_email_template($template_name) {
        $templates = array(
            'appointment_reminder' => $this->get_reminder_template(),
            'booking_confirmation' => $this->get_confirmation_template(),
            'appointment_cancelled' => $this->get_cancellation_template(),
            'appointment_rescheduled' => $this->get_reschedule_template()
        );

        $template = $templates[$template_name] ?? '';
        
        // Allow customization via filter
        return apply_filters('eye_book_email_template_' . $template_name, $template);
    }

    /**
     * Get reminder email template
     *
     * @return string
     * @since 1.0.0
     */
    private function get_reminder_template() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Appointment Reminder</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #3498db; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .appointment-details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .button { background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Appointment Reminder</h1>
                </div>
                <div class="content">
                    <p>Dear {patient_name},</p>
                    <p>This is a friendly reminder about your upcoming appointment:</p>
                    
                    <div class="appointment-details">
                        <h3>Appointment Details</h3>
                        <p><strong>Date:</strong> {appointment_date}</p>
                        <p><strong>Time:</strong> {appointment_time}</p>
                        <p><strong>Provider:</strong> {provider_name}</p>
                        <p><strong>Location:</strong> {location_name}</p>
                        <p><strong>Address:</strong> {location_address}</p>
                        <p><strong>Phone:</strong> {location_phone}</p>
                    </div>
                    
                    <p><strong>Please arrive 15 minutes early</strong> for check-in and bring a valid ID and insurance card.</p>
                    
                    <p>You can access your patient portal to view appointment details or make changes:</p>
                    <p><a href="{portal_link}" class="button">Access Patient Portal</a></p>
                    
                    <p>If you need to reschedule or cancel, please contact us as soon as possible.</p>
                </div>
                <div class="footer">
                    <p>Thank you,<br>{clinic_name}</p>
                    <p>Appointment ID: {appointment_id}</p>
                </div>
            </div>
        </body>
        </html>';
    }

    /**
     * Get confirmation email template
     *
     * @return string
     * @since 1.0.0
     */
    private function get_confirmation_template() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Appointment Confirmed</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #27ae60; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .appointment-details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .button { background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>✓ Appointment Confirmed</h1>
                </div>
                <div class="content">
                    <p>Dear {patient_name},</p>
                    <p>Your appointment has been successfully booked and confirmed:</p>
                    
                    <div class="appointment-details">
                        <h3>Appointment Details</h3>
                        <p><strong>Date:</strong> {appointment_date}</p>
                        <p><strong>Time:</strong> {appointment_time}</p>
                        <p><strong>Provider:</strong> {provider_name}</p>
                        <p><strong>Location:</strong> {location_name}</p>
                        <p><strong>Address:</strong> {location_address}</p>
                        <p><strong>Phone:</strong> {location_phone}</p>
                        <p><strong>Appointment ID:</strong> {appointment_id}</p>
                    </div>
                    
                    <p><strong>Important Reminders:</strong></p>
                    <ul>
                        <li>Please arrive 15 minutes early for check-in</li>
                        <li>Bring a valid photo ID and insurance card</li>
                        <li>Bring a list of current medications</li>
                        <li>Wear comfortable clothing</li>
                    </ul>
                    
                    <p><strong>Cancellation Policy:</strong> {cancellation_policy}</p>
                    
                    <p>Access your patient portal to manage your appointments:</p>
                    <p><a href="{portal_link}" class="button">Access Patient Portal</a></p>
                </div>
                <div class="footer">
                    <p>Thank you for choosing {clinic_name}</p>
                    <p>We look forward to seeing you!</p>
                </div>
            </div>
        </body>
        </html>';
    }

    /**
     * Get cancellation email template
     *
     * @return string
     * @since 1.0.0
     */
    private function get_cancellation_template() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Appointment Cancelled</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #e74c3c; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .appointment-details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .button { background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Appointment Cancelled</h1>
                </div>
                <div class="content">
                    <p>Dear {patient_name},</p>
                    <p>Your appointment has been cancelled as requested:</p>
                    
                    <div class="appointment-details">
                        <h3>Cancelled Appointment</h3>
                        <p><strong>Date:</strong> {appointment_date}</p>
                        <p><strong>Time:</strong> {appointment_time}</p>
                        <p><strong>Appointment ID:</strong> {appointment_id}</p>
                        <p><strong>Reason:</strong> {cancellation_reason}</p>
                    </div>
                    
                    <p>We hope to see you soon. You can book a new appointment at any time:</p>
                    <p><a href="{rebooking_link}" class="button">Book New Appointment</a></p>
                    
                    <p>If you have any questions or need assistance, please contact us at {contact_phone}.</p>
                </div>
                <div class="footer">
                    <p>Thank you,<br>{clinic_name}</p>
                </div>
            </div>
        </body>
        </html>';
    }

    /**
     * Get reschedule email template
     *
     * @return string
     * @since 1.0.0
     */
    private function get_reschedule_template() {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Appointment Rescheduled</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f39c12; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .appointment-details { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .old-appointment { background: #ffebee; border-left: 4px solid #e74c3c; }
                .new-appointment { background: #e8f5e8; border-left: 4px solid #27ae60; }
                .button { background: #27ae60; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Appointment Rescheduled</h1>
                </div>
                <div class="content">
                    <p>Dear {patient_name},</p>
                    <p>Your appointment has been rescheduled. Please note the updated details below:</p>
                    
                    <div class="appointment-details old-appointment">
                        <h3>Previous Appointment (Cancelled)</h3>
                        <p><strong>Date:</strong> {old_date}</p>
                        <p><strong>Time:</strong> {old_time}</p>
                    </div>
                    
                    <div class="appointment-details new-appointment">
                        <h3>New Appointment (Confirmed)</h3>
                        <p><strong>Date:</strong> {new_date}</p>
                        <p><strong>Time:</strong> {new_time}</p>
                        <p><strong>Appointment ID:</strong> {appointment_id}</p>
                    </div>
                    
                    <p>Please save these new details and plan accordingly. We will send you a reminder closer to your appointment date.</p>
                    
                    <p>You can view your updated appointment in your patient portal:</p>
                    <p><a href="{portal_link}" class="button">Access Patient Portal</a></p>
                </div>
                <div class="footer">
                    <p>Thank you,<br>{clinic_name}</p>
                </div>
            </div>
        </body>
        </html>';
    }

    /**
     * Generate patient portal access link
     *
     * @param int $patient_id
     * @return string
     * @since 1.0.0
     */
    private function generate_patient_portal_link($patient_id) {
        $token = Eye_Book_Encryption::generate_patient_token($patient_id, 72); // 72 hour expiry
        return add_query_arg('patient_token', $token, home_url('/patient-portal/'));
    }

    /**
     * Send test email
     *
     * @param string $email
     * @param string $template_name
     * @return bool
     * @since 1.0.0
     */
    public function send_test_email($email, $template_name) {
        $subject = sprintf(__('Test Email - %s', 'eye-book'), get_bloginfo('name'));
        
        $template = $this->get_email_template($template_name);
        
        // Replace variables with test data
        $test_variables = array(
            '{patient_name}' => 'John Doe',
            '{appointment_date}' => date_i18n(get_option('date_format'), strtotime('+1 day')),
            '{appointment_time}' => '2:00 PM',
            '{provider_name}' => 'Dr. Smith',
            '{location_name}' => 'Main Clinic',
            '{location_address}' => '123 Main St, City, ST 12345',
            '{location_phone}' => '(555) 123-4567',
            '{clinic_name}' => get_bloginfo('name'),
            '{appointment_id}' => 'APT' . date('Ymd') . '1234',
            '{portal_link}' => home_url('/patient-portal/'),
            '{cancellation_policy}' => 'Please contact us 24 hours in advance.',
            '{old_date}' => date_i18n(get_option('date_format'), strtotime('+1 day')),
            '{old_time}' => '1:00 PM',
            '{new_date}' => date_i18n(get_option('date_format'), strtotime('+2 days')),
            '{new_time}' => '3:00 PM',
            '{cancellation_reason}' => 'Schedule conflict',
            '{rebooking_link}' => home_url('/book-appointment/'),
            '{contact_phone}' => '(555) 123-4567'
        );

        $message = str_replace(array_keys($test_variables), array_values($test_variables), $template);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        return wp_mail($email, $subject, $message, $headers);
    }
}