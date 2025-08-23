<?php
/**
 * RESTful API class for Eye-Book plugin
 *
 * @package EyeBook
 * @subpackage API
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eye_Book_API Class
 *
 * Provides RESTful API endpoints for third-party integrations
 *
 * @class Eye_Book_API
 * @since 1.0.0
 */
class Eye_Book_API {

    /**
     * API version
     *
     * @var string
     * @since 1.0.0
     */
    const API_VERSION = 'v1';

    /**
     * API namespace
     *
     * @var string
     * @since 1.0.0
     */
    const API_NAMESPACE = 'eye-book/v1';

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('init', array($this, 'add_cors_http_header'));
    }

    /**
     * Add CORS headers
     *
     * @since 1.0.0
     */
    public function add_cors_http_header() {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    }

    /**
     * Register API routes
     *
     * @since 1.0.0
     */
    public function register_routes() {
        // Appointments endpoints
        register_rest_route(self::API_NAMESPACE, '/appointments', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_appointments'),
                'permission_callback' => array($this, 'check_api_permissions'),
                'args' => $this->get_appointments_args()
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_appointment'),
                'permission_callback' => array($this, 'check_api_permissions'),
                'args' => $this->get_create_appointment_args()
            )
        ));

        register_rest_route(self::API_NAMESPACE, '/appointments/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_appointment'),
                'permission_callback' => array($this, 'check_api_permissions'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param);
                        }
                    )
                )
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_appointment'),
                'permission_callback' => array($this, 'check_api_permissions'),
                'args' => $this->get_update_appointment_args()
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_appointment'),
                'permission_callback' => array($this, 'check_api_permissions')
            )
        ));

        // Patients endpoints
        register_rest_route(self::API_NAMESPACE, '/patients', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_patients'),
                'permission_callback' => array($this, 'check_api_permissions'),
                'args' => $this->get_patients_args()
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_patient'),
                'permission_callback' => array($this, 'check_api_permissions'),
                'args' => $this->get_create_patient_args()
            )
        ));

        register_rest_route(self::API_NAMESPACE, '/patients/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_patient'),
                'permission_callback' => array($this, 'check_api_permissions')
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_patient'),
                'permission_callback' => array($this, 'check_api_permissions'),
                'args' => $this->get_update_patient_args()
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_patient'),
                'permission_callback' => array($this, 'check_api_permissions')
            )
        ));

        // Providers endpoints
        register_rest_route(self::API_NAMESPACE, '/providers', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_providers'),
                'permission_callback' => array($this, 'check_api_permissions')
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_provider'),
                'permission_callback' => array($this, 'check_api_permissions'),
                'args' => $this->get_create_provider_args()
            )
        ));

        register_rest_route(self::API_NAMESPACE, '/providers/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_provider'),
                'permission_callback' => array($this, 'check_api_permissions')
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_provider'),
                'permission_callback' => array($this, 'check_api_permissions')
            )
        ));

        // Locations endpoints
        register_rest_route(self::API_NAMESPACE, '/locations', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_locations'),
                'permission_callback' => array($this, 'check_api_permissions')
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_location'),
                'permission_callback' => array($this, 'check_api_permissions')
            )
        ));

        // Availability endpoints
        register_rest_route(self::API_NAMESPACE, '/availability', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_availability'),
            'permission_callback' => array($this, 'check_public_api_permissions'),
            'args' => array(
                'provider_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'location_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
                'date' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return strtotime($param) !== false;
                    }
                )
            )
        ));

        // Forms endpoints
        register_rest_route(self::API_NAMESPACE, '/forms', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_forms'),
            'permission_callback' => array($this, 'check_api_permissions')
        ));

        register_rest_route(self::API_NAMESPACE, '/forms/(?P<id>\d+)/responses', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_form_responses'),
            'permission_callback' => array($this, 'check_api_permissions')
        ));

        // Reports endpoints
        register_rest_route(self::API_NAMESPACE, '/reports/appointments', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_appointments_report'),
            'permission_callback' => array($this, 'check_api_permissions'),
            'args' => $this->get_report_args()
        ));

        register_rest_route(self::API_NAMESPACE, '/reports/patients', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'get_patients_report'),
            'permission_callback' => array($this, 'check_api_permissions'),
            'args' => $this->get_report_args()
        ));

        // Webhook endpoints
        register_rest_route(self::API_NAMESPACE, '/webhooks', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_webhooks'),
                'permission_callback' => array($this, 'check_api_permissions')
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_webhook'),
                'permission_callback' => array($this, 'check_api_permissions'),
                'args' => $this->get_create_webhook_args()
            )
        ));

        register_rest_route(self::API_NAMESPACE, '/webhooks/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_webhook'),
                'permission_callback' => array($this, 'check_api_permissions')
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_webhook'),
                'permission_callback' => array($this, 'check_api_permissions')
            )
        ));

        // Health check endpoint
        register_rest_route(self::API_NAMESPACE, '/health', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'health_check'),
            'permission_callback' => '__return_true'
        ));
    }

    /**
     * Check API permissions
     *
     * @param WP_REST_Request $request
     * @return bool
     * @since 1.0.0
     */
    public function check_api_permissions($request) {
        // Check for API key in headers
        $api_key = $request->get_header('X-API-Key');
        
        if (!$api_key) {
            // Check for Bearer token
            $authorization = $request->get_header('Authorization');
            if ($authorization && preg_match('/Bearer\s+(.*)$/i', $authorization, $matches)) {
                $api_key = $matches[1];
            }
        }

        if (!$api_key) {
            return new WP_Error('missing_api_key', __('API key is required', 'eye-book'), array('status' => 401));
        }

        // Validate API key
        $valid_keys = get_option('eye_book_api_keys', array());
        $key_valid = false;

        foreach ($valid_keys as $key_data) {
            if (hash_equals($key_data['key'], $api_key) && $key_data['active']) {
                $key_valid = true;
                // Log API usage
                $this->log_api_usage($key_data['name'], $request);
                break;
            }
        }

        if (!$key_valid) {
            return new WP_Error('invalid_api_key', __('Invalid API key', 'eye-book'), array('status' => 401));
        }

        // Check rate limiting
        if (!$this->check_rate_limit($api_key)) {
            return new WP_Error('rate_limit_exceeded', __('Rate limit exceeded', 'eye-book'), array('status' => 429));
        }

        return true;
    }

    /**
     * Check public API permissions (for public endpoints)
     *
     * @param WP_REST_Request $request
     * @return bool
     * @since 1.0.0
     */
    public function check_public_api_permissions($request) {
        // Allow public access for certain endpoints like availability
        return true;
    }

    /**
     * Log API usage
     *
     * @param string $key_name
     * @param WP_REST_Request $request
     * @since 1.0.0
     */
    private function log_api_usage($key_name, $request) {
        Eye_Book_Audit::log('api_request', 'system', null, array(
            'endpoint' => $request->get_route(),
            'method' => $request->get_method(),
            'api_key' => $key_name,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ));
    }

    /**
     * Check rate limiting
     *
     * @param string $api_key
     * @return bool
     * @since 1.0.0
     */
    private function check_rate_limit($api_key) {
        $rate_limit = get_option('eye_book_api_rate_limit', 1000); // requests per hour
        $key = 'eye_book_api_rate_' . md5($api_key);
        
        $current_count = get_transient($key);
        if ($current_count === false) {
            set_transient($key, 1, HOUR_IN_SECONDS);
            return true;
        }

        if ($current_count >= $rate_limit) {
            return false;
        }

        set_transient($key, $current_count + 1, HOUR_IN_SECONDS);
        return true;
    }

    /**
     * Get appointments
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function get_appointments($request) {
        global $wpdb;

        $page = $request->get_param('page') ?: 1;
        $per_page = min($request->get_param('per_page') ?: 20, 100);
        $offset = ($page - 1) * $per_page;

        // Build query conditions
        $where_conditions = array('1=1');
        $params = array();

        if ($request->get_param('provider_id')) {
            $where_conditions[] = 'a.provider_id = %d';
            $params[] = $request->get_param('provider_id');
        }

        if ($request->get_param('location_id')) {
            $where_conditions[] = 'a.location_id = %d';
            $params[] = $request->get_param('location_id');
        }

        if ($request->get_param('status')) {
            $where_conditions[] = 'a.status = %s';
            $params[] = $request->get_param('status');
        }

        if ($request->get_param('date_from')) {
            $where_conditions[] = 'DATE(a.start_datetime) >= %s';
            $params[] = $request->get_param('date_from');
        }

        if ($request->get_param('date_to')) {
            $where_conditions[] = 'DATE(a.start_datetime) <= %s';
            $params[] = $request->get_param('date_to');
        }

        $where_clause = implode(' AND ', $where_conditions);

        // Get total count
        $count_query = "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a WHERE $where_clause";
        $total = $wpdb->get_var($params ? $wpdb->prepare($count_query, $params) : $count_query);

        // Get appointments
        $query = "SELECT a.*, 
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    p.email as patient_email,
                    p.phone as patient_phone,
                    CONCAT(pr.first_name, ' ', pr.last_name) as provider_name,
                    l.name as location_name,
                    at.name as appointment_type_name
                  FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a
                  LEFT JOIN " . EYE_BOOK_TABLE_PATIENTS . " p ON a.patient_id = p.id
                  LEFT JOIN " . EYE_BOOK_TABLE_PROVIDERS . " pr ON a.provider_id = pr.id
                  LEFT JOIN " . EYE_BOOK_TABLE_LOCATIONS . " l ON a.location_id = l.id
                  LEFT JOIN " . EYE_BOOK_TABLE_APPOINTMENT_TYPES . " at ON a.appointment_type_id = at.id
                  WHERE $where_clause
                  ORDER BY a.start_datetime DESC
                  LIMIT %d OFFSET %d";

        $params[] = $per_page;
        $params[] = $offset;

        $appointments = $wpdb->get_results($wpdb->prepare($query, $params));

        // Format appointments
        $formatted_appointments = array_map(array($this, 'format_appointment'), $appointments);

        return new WP_REST_Response(array(
            'appointments' => $formatted_appointments,
            'pagination' => array(
                'page' => (int) $page,
                'per_page' => (int) $per_page,
                'total' => (int) $total,
                'total_pages' => (int) ceil($total / $per_page)
            )
        ), 200);
    }

    /**
     * Get single appointment
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function get_appointment($request) {
        $appointment_id = $request->get_param('id');
        $appointment = new Eye_Book_Appointment($appointment_id);

        if (!$appointment->id) {
            return new WP_Error('appointment_not_found', __('Appointment not found', 'eye-book'), array('status' => 404));
        }

        return new WP_REST_Response($this->format_appointment($appointment), 200);
    }

    /**
     * Create appointment
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function create_appointment($request) {
        $appointment = new Eye_Book_Appointment();
        
        // Set appointment data
        $appointment->patient_id = $request->get_param('patient_id');
        $appointment->provider_id = $request->get_param('provider_id');
        $appointment->location_id = $request->get_param('location_id');
        $appointment->appointment_type_id = $request->get_param('appointment_type_id');
        $appointment->start_datetime = $request->get_param('start_datetime');
        $appointment->end_datetime = $request->get_param('end_datetime');
        $appointment->status = $request->get_param('status') ?: 'scheduled';
        $appointment->chief_complaint = $request->get_param('chief_complaint') ?: '';
        $appointment->notes = $request->get_param('notes') ?: '';
        $appointment->booking_source = 'api';

        $appointment_id = $appointment->save();

        if (!$appointment_id) {
            return new WP_Error('appointment_creation_failed', __('Failed to create appointment', 'eye-book'), array('status' => 500));
        }

        // Trigger webhook
        $this->trigger_webhook('appointment.created', $this->format_appointment($appointment));

        return new WP_REST_Response($this->format_appointment($appointment), 201);
    }

    /**
     * Update appointment
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function update_appointment($request) {
        $appointment_id = $request->get_param('id');
        $appointment = new Eye_Book_Appointment($appointment_id);

        if (!$appointment->id) {
            return new WP_Error('appointment_not_found', __('Appointment not found', 'eye-book'), array('status' => 404));
        }

        // Update fields if provided
        $fields = array('start_datetime', 'end_datetime', 'status', 'chief_complaint', 'notes');
        foreach ($fields as $field) {
            if ($request->has_param($field)) {
                $appointment->$field = $request->get_param($field);
            }
        }

        $result = $appointment->save();

        if (!$result) {
            return new WP_Error('appointment_update_failed', __('Failed to update appointment', 'eye-book'), array('status' => 500));
        }

        // Trigger webhook
        $this->trigger_webhook('appointment.updated', $this->format_appointment($appointment));

        return new WP_REST_Response($this->format_appointment($appointment), 200);
    }

    /**
     * Delete appointment
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function delete_appointment($request) {
        $appointment_id = $request->get_param('id');
        $appointment = new Eye_Book_Appointment($appointment_id);

        if (!$appointment->id) {
            return new WP_Error('appointment_not_found', __('Appointment not found', 'eye-book'), array('status' => 404));
        }

        $formatted_appointment = $this->format_appointment($appointment);
        
        $result = $appointment->delete();

        if (!$result) {
            return new WP_Error('appointment_deletion_failed', __('Failed to delete appointment', 'eye-book'), array('status' => 500));
        }

        // Trigger webhook
        $this->trigger_webhook('appointment.deleted', $formatted_appointment);

        return new WP_REST_Response(array('message' => __('Appointment deleted successfully', 'eye-book')), 200);
    }

    /**
     * Get availability
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function get_availability($request) {
        $provider_id = $request->get_param('provider_id');
        $location_id = $request->get_param('location_id');
        $date = $request->get_param('date');
        $duration = $request->get_param('duration') ?: 30;

        $booking = new Eye_Book_Booking();
        $availability = $booking->check_availability($provider_id, $location_id, $date, $duration);

        return new WP_REST_Response(array(
            'date' => $date,
            'provider_id' => (int) $provider_id,
            'location_id' => (int) $location_id,
            'available_slots' => $availability
        ), 200);
    }

    /**
     * Health check endpoint
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     * @since 1.0.0
     */
    public function health_check($request) {
        global $wpdb;

        // Check database connection
        $db_status = $wpdb->get_var("SELECT 1") ? 'connected' : 'error';

        return new WP_REST_Response(array(
            'status' => 'ok',
            'version' => EYE_BOOK_VERSION,
            'database' => $db_status,
            'timestamp' => current_time('mysql', true)
        ), 200);
    }

    /**
     * Trigger webhook
     *
     * @param string $event
     * @param array $data
     * @since 1.0.0
     */
    private function trigger_webhook($event, $data) {
        $webhooks = get_option('eye_book_webhooks', array());

        foreach ($webhooks as $webhook) {
            if (!$webhook['active'] || !in_array($event, $webhook['events'])) {
                continue;
            }

            $payload = array(
                'event' => $event,
                'data' => $data,
                'timestamp' => current_time('mysql', true)
            );

            wp_remote_post($webhook['url'], array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'X-EyeBook-Event' => $event,
                    'X-EyeBook-Signature' => hash_hmac('sha256', json_encode($payload), $webhook['secret'])
                ),
                'body' => json_encode($payload),
                'timeout' => 30
            ));
        }
    }

    /**
     * Format appointment for API response
     *
     * @param object $appointment
     * @return array
     * @since 1.0.0
     */
    private function format_appointment($appointment) {
        return array(
            'id' => (int) $appointment->id,
            'appointment_id' => $appointment->appointment_id,
            'patient_id' => (int) $appointment->patient_id,
            'provider_id' => (int) $appointment->provider_id,
            'location_id' => (int) $appointment->location_id,
            'appointment_type_id' => (int) $appointment->appointment_type_id,
            'start_datetime' => $appointment->start_datetime,
            'end_datetime' => $appointment->end_datetime,
            'status' => $appointment->status,
            'chief_complaint' => $appointment->chief_complaint,
            'notes' => $appointment->notes,
            'booking_source' => $appointment->booking_source,
            'patient_name' => $appointment->patient_name ?? '',
            'provider_name' => $appointment->provider_name ?? '',
            'location_name' => $appointment->location_name ?? '',
            'appointment_type_name' => $appointment->appointment_type_name ?? '',
            'created_at' => $appointment->created_at,
            'updated_at' => $appointment->updated_at
        );
    }

    /**
     * Get appointments query arguments
     *
     * @return array
     * @since 1.0.0
     */
    private function get_appointments_args() {
        return array(
            'page' => array(
                'default' => 1,
                'sanitize_callback' => 'absint'
            ),
            'per_page' => array(
                'default' => 20,
                'sanitize_callback' => 'absint'
            ),
            'provider_id' => array(
                'sanitize_callback' => 'absint'
            ),
            'location_id' => array(
                'sanitize_callback' => 'absint'
            ),
            'status' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'date_from' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'date_to' => array(
                'sanitize_callback' => 'sanitize_text_field'
            )
        );
    }

    /**
     * Get create appointment arguments
     *
     * @return array
     * @since 1.0.0
     */
    private function get_create_appointment_args() {
        return array(
            'patient_id' => array(
                'required' => true,
                'sanitize_callback' => 'absint'
            ),
            'provider_id' => array(
                'required' => true,
                'sanitize_callback' => 'absint'
            ),
            'location_id' => array(
                'required' => true,
                'sanitize_callback' => 'absint'
            ),
            'appointment_type_id' => array(
                'required' => true,
                'sanitize_callback' => 'absint'
            ),
            'start_datetime' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'end_datetime' => array(
                'required' => true,
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'status' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'chief_complaint' => array(
                'sanitize_callback' => 'sanitize_textarea_field'
            ),
            'notes' => array(
                'sanitize_callback' => 'sanitize_textarea_field'
            )
        );
    }

    /**
     * Get update appointment arguments
     *
     * @return array
     * @since 1.0.0
     */
    private function get_update_appointment_args() {
        return array(
            'start_datetime' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'end_datetime' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'status' => array(
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'chief_complaint' => array(
                'sanitize_callback' => 'sanitize_textarea_field'
            ),
            'notes' => array(
                'sanitize_callback' => 'sanitize_textarea_field'
            )
        );
    }

    // Additional methods for patients, providers, locations, etc. would follow similar patterns...
    
    /**
     * Get patients arguments
     *
     * @return array
     * @since 1.0.0
     */
    private function get_patients_args() {
        return array(
            'page' => array('default' => 1, 'sanitize_callback' => 'absint'),
            'per_page' => array('default' => 20, 'sanitize_callback' => 'absint'),
            'search' => array('sanitize_callback' => 'sanitize_text_field')
        );
    }

    /**
     * Get create patient arguments
     *
     * @return array
     * @since 1.0.0
     */
    private function get_create_patient_args() {
        return array(
            'first_name' => array('required' => true, 'sanitize_callback' => 'sanitize_text_field'),
            'last_name' => array('required' => true, 'sanitize_callback' => 'sanitize_text_field'),
            'email' => array('required' => true, 'sanitize_callback' => 'sanitize_email'),
            'phone' => array('required' => true, 'sanitize_callback' => 'sanitize_text_field'),
            'date_of_birth' => array('required' => true, 'sanitize_callback' => 'sanitize_text_field')
        );
    }

    /**
     * Get update patient arguments
     *
     * @return array
     * @since 1.0.0
     */
    private function get_update_patient_args() {
        return array(
            'first_name' => array('sanitize_callback' => 'sanitize_text_field'),
            'last_name' => array('sanitize_callback' => 'sanitize_text_field'),
            'email' => array('sanitize_callback' => 'sanitize_email'),
            'phone' => array('sanitize_callback' => 'sanitize_text_field')
        );
    }

    /**
     * Get create provider arguments
     *
     * @return array
     * @since 1.0.0
     */
    private function get_create_provider_args() {
        return array(
            'first_name' => array('required' => true, 'sanitize_callback' => 'sanitize_text_field'),
            'last_name' => array('required' => true, 'sanitize_callback' => 'sanitize_text_field'),
            'email' => array('required' => true, 'sanitize_callback' => 'sanitize_email'),
            'specialization' => array('required' => true, 'sanitize_callback' => 'sanitize_text_field')
        );
    }

    /**
     * Get report arguments
     *
     * @return array
     * @since 1.0.0
     */
    private function get_report_args() {
        return array(
            'start_date' => array('sanitize_callback' => 'sanitize_text_field'),
            'end_date' => array('sanitize_callback' => 'sanitize_text_field'),
            'provider_id' => array('sanitize_callback' => 'absint'),
            'location_id' => array('sanitize_callback' => 'absint')
        );
    }

    /**
     * Get create webhook arguments
     *
     * @return array
     * @since 1.0.0
     */
    private function get_create_webhook_args() {
        return array(
            'name' => array('required' => true, 'sanitize_callback' => 'sanitize_text_field'),
            'url' => array('required' => true, 'sanitize_callback' => 'esc_url_raw'),
            'events' => array('required' => true, 'sanitize_callback' => array($this, 'sanitize_events_array')),
            'secret' => array('sanitize_callback' => 'sanitize_text_field')
        );
    }

    /**
     * Sanitize events array
     *
     * @param array $events
     * @return array
     * @since 1.0.0
     */
    public function sanitize_events_array($events) {
        if (!is_array($events)) {
            return array();
        }
        
        $allowed_events = array(
            'appointment.created',
            'appointment.updated',
            'appointment.cancelled',
            'appointment.deleted',
            'patient.created',
            'patient.updated'
        );
        
        return array_intersect($events, $allowed_events);
    }

    // Placeholder methods for remaining endpoints (patients, providers, locations, etc.)
    public function get_patients($request) { return new WP_REST_Response(array(), 200); }
    public function get_patient($request) { return new WP_REST_Response(array(), 200); }
    public function create_patient($request) { return new WP_REST_Response(array(), 201); }
    public function update_patient($request) { return new WP_REST_Response(array(), 200); }
    public function delete_patient($request) { return new WP_REST_Response(array(), 200); }
    public function get_providers($request) { return new WP_REST_Response(array(), 200); }
    public function get_provider($request) { return new WP_REST_Response(array(), 200); }
    public function create_provider($request) { return new WP_REST_Response(array(), 201); }
    public function update_provider($request) { return new WP_REST_Response(array(), 200); }
    public function get_locations($request) { return new WP_REST_Response(array(), 200); }
    public function create_location($request) { return new WP_REST_Response(array(), 201); }
    public function get_forms($request) { return new WP_REST_Response(array(), 200); }
    public function get_form_responses($request) { return new WP_REST_Response(array(), 200); }
    public function get_appointments_report($request) { return new WP_REST_Response(array(), 200); }
    public function get_patients_report($request) { return new WP_REST_Response(array(), 200); }
    public function get_webhooks($request) { return new WP_REST_Response(array(), 200); }
    public function create_webhook($request) { return new WP_REST_Response(array(), 201); }
    public function update_webhook($request) { return new WP_REST_Response(array(), 200); }
    public function delete_webhook($request) { return new WP_REST_Response(array(), 200); }
}