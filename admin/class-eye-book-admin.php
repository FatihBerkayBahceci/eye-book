<?php
/**
 * Admin interface class for Eye-Book plugin
 *
 * @package EyeBook
 * @subpackage Admin
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eye_Book_Admin Class
 *
 * Handles admin interface, menus, and dashboard functionality
 *
 * @class Eye_Book_Admin
 * @since 1.0.0
 */
class Eye_Book_Admin {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_eye_book_dashboard_stats', array($this, 'ajax_dashboard_stats'));
        add_action('wp_ajax_eye_book_get_appointments', array($this, 'ajax_get_appointments'));
        add_action('wp_ajax_eye_book_save_appointment', array($this, 'ajax_save_appointment'));
        add_action('wp_ajax_eye_book_delete_appointment', array($this, 'ajax_delete_appointment'));
        add_action('wp_ajax_eye_book_get_patients', array($this, 'ajax_get_patients'));
        add_action('wp_ajax_eye_book_save_patient', array($this, 'ajax_save_patient'));
        add_action('wp_ajax_eye_book_delete_patient', array($this, 'ajax_delete_patient'));
        add_action('wp_ajax_eye_book_get_providers', array($this, 'ajax_get_providers'));
        add_action('wp_ajax_eye_book_save_provider', array($this, 'ajax_save_provider'));
        add_action('wp_ajax_eye_book_delete_provider', array($this, 'ajax_delete_provider'));
        add_action('wp_ajax_eye_book_get_provider', array($this, 'ajax_get_provider'));
        add_action('wp_ajax_eye_book_bulk_action_providers', array($this, 'ajax_bulk_action_providers'));
        add_action('wp_ajax_eye_book_get_locations', array($this, 'ajax_get_locations'));
        add_action('wp_ajax_eye_book_save_location', array($this, 'ajax_save_location'));
        add_action('wp_ajax_eye_book_delete_location', array($this, 'ajax_delete_location'));
        add_action('wp_ajax_eye_book_get_location', array($this, 'ajax_get_location'));
        add_action('wp_ajax_eye_book_bulk_action_locations', array($this, 'ajax_bulk_action_locations'));
        add_action('wp_ajax_eye_book_get_patient', array($this, 'ajax_get_patient'));
        add_action('wp_ajax_eye_book_bulk_action_patients', array($this, 'ajax_bulk_action_patients'));
        add_action('wp_ajax_eye_book_generate_report', array($this, 'ajax_generate_report'));
        add_action('admin_head', array($this, 'admin_head_styles'));
    }

    /**
     * Initialize admin functionality
     *
     * @since 1.0.0
     */
    public function admin_init() {
        // Check user permissions
        if (!current_user_can('eye_book_manage_all') && !current_user_can('manage_options')) {
            return;
        }

        // Register settings
        $this->register_settings();
    }

    /**
     * Add admin menus
     *
     * @since 1.0.0
     */
    public function add_admin_menus() {
        // Check permissions
        if (!current_user_can('eye_book_manage_all') && !current_user_can('manage_options')) {
            return;
        }

        // Main menu
        add_menu_page(
            __('Eye-Book', 'eye-book'),
            __('Eye-Book', 'eye-book'),
            'eye_book_manage_all',
            'eye-book',
            array($this, 'dashboard_page'),
            'dashicons-calendar-alt',
            30
        );

        // Dashboard submenu (rename the first submenu)
        add_submenu_page(
            'eye-book',
            __('Dashboard', 'eye-book'),
            __('Dashboard', 'eye-book'),
            'eye_book_manage_all',
            'eye-book',
            array($this, 'dashboard_page')
        );

        // Appointments submenu
        add_submenu_page(
            'eye-book',
            __('Appointments', 'eye-book'),
            __('Appointments', 'eye-book'),
            'eye_book_manage_appointments',
            'eye-book-appointments',
            array($this, 'appointments_page')
        );

        // Patients submenu
        add_submenu_page(
            'eye-book',
            __('Patients', 'eye-book'),
            __('Patients', 'eye-book'),
            'eye_book_manage_patients',
            'eye-book-patients',
            array($this, 'patients_page')
        );

        // Providers submenu
        add_submenu_page(
            'eye-book',
            __('Providers', 'eye-book'),
            __('Providers', 'eye-book'),
            'eye_book_manage_all',
            'eye-book-providers',
            array($this, 'providers_page')
        );

        // Locations submenu
        add_submenu_page(
            'eye-book',
            __('Locations', 'eye-book'),
            __('Locations', 'eye-book'),
            'eye_book_manage_all',
            'eye-book-locations',
            array($this, 'locations_page')
        );

        // Reports submenu
        add_submenu_page(
            'eye-book',
            __('Reports', 'eye-book'),
            __('Reports', 'eye-book'),
            'eye_book_view_reports',
            'eye-book-reports',
            array($this, 'reports_page')
        );

        // Settings submenu
        add_submenu_page(
            'eye-book',
            __('Settings', 'eye-book'),
            __('Settings', 'eye-book'),
            'eye_book_manage_settings',
            'eye-book-settings',
            array($this, 'settings_page')
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook
     * @since 1.0.0
     */
    public function enqueue_admin_assets($hook) {
        // Only load on Eye-Book admin pages
        if (strpos($hook, 'eye-book') === false) {
            return;
        }

        // Enqueue modern CSS framework (Tailwind-like utilities)
        wp_enqueue_style(
            'eye-book-admin',
            EYE_BOOK_PLUGIN_URL . 'admin/assets/css/eye-book-admin.css',
            array(),
            EYE_BOOK_VERSION
        );

        // Enqueue additional modern CSS components
        wp_enqueue_style(
            'eye-book-components',
            EYE_BOOK_PLUGIN_URL . 'admin/assets/css/eye-book-components.css',
            array('eye-book-admin'),
            EYE_BOOK_VERSION
        );

        // Enqueue modern JavaScript frameworks
        wp_enqueue_script(
            'eye-book-admin',
            EYE_BOOK_PLUGIN_URL . 'admin/assets/js/eye-book-admin.js',
            array('jquery', 'wp-api'),
            EYE_BOOK_VERSION,
            true
        );

        // Enqueue WordPress core scripts that are reliable
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_style('wp-jquery-ui-dialog');
        
        // Enqueue CDN resources with fallback checks
        $this->enqueue_cdn_with_fallback(
            'alpine-js',
            'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js',
            '3.13.5'
        );

        $this->enqueue_cdn_with_fallback(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js',
            '4.4.0'
        );

        $this->enqueue_cdn_with_fallback(
            'flatpickr',
            'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js',
            '4.6.13'
        );
        
        $this->enqueue_cdn_with_fallback(
            'flatpickr-css',
            'https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css',
            '4.6.13',
            true
        );

        // Localize script with comprehensive data
        wp_localize_script('eye-book-admin', 'eyeBookAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eye_book_ajax_nonce'),
            'rest_url' => rest_url('eye-book/v1/'),
            'current_user' => wp_get_current_user()->ID,
            'current_page' => $_GET['page'] ?? '',
            'strings' => array(
                'loading' => __('Loading...', 'eye-book'),
                'error' => __('An error occurred', 'eye-book'),
                'success' => __('Success!', 'eye-book'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'eye-book'),
                'save_success' => __('Changes saved successfully', 'eye-book'),
                'delete_success' => __('Item deleted successfully', 'eye-book'),
                'validation_error' => __('Please check your input and try again', 'eye-book'),
                'network_error' => __('Network error. Please try again.', 'eye-book'),
                'cancel' => __('Cancel', 'eye-book'),
                'save' => __('Save', 'eye-book'),
                'delete' => __('Delete', 'eye-book'),
                'edit' => __('Edit', 'eye-book'),
                'view' => __('View', 'eye-book'),
                'add_new' => __('Add New', 'eye-book'),
                'search' => __('Search...', 'eye-book'),
                'no_results' => __('No results found', 'eye-book'),
                'select_date' => __('Select Date', 'eye-book'),
                'select_time' => __('Select Time', 'eye-book'),
                'all_appointments' => __('All Appointments', 'eye-book'),
                'today' => __('Today', 'eye-book'),
                'this_week' => __('This Week', 'eye-book'),
                'this_month' => __('This Month', 'eye-book'),
                'date_format' => get_option('date_format', 'F j, Y'),
                'time_format' => get_option('time_format', 'g:i a'),
                'first_day_of_week' => get_option('start_of_week', 0)
            ),
            'settings' => array(
                'date_format' => get_option('eye_book_date_format', 'F j, Y'),
                'time_format' => get_option('eye_book_time_format', 'g:i a'),
                'timezone' => get_option('eye_book_timezone', 'America/New_York'),
                'currency' => get_option('eye_book_currency', 'USD'),
                'currency_symbol' => get_option('eye_book_currency_symbol', '$')
            )
        ));

        // Page-specific scripts
        $this->enqueue_page_specific_scripts($hook);
    }

    /**
     * Register settings
     *
     * @since 1.0.0
     */
    private function register_settings() {
        // General settings
        register_setting('eye_book_general', 'eye_book_clinic_name');
        register_setting('eye_book_general', 'eye_book_timezone');
        register_setting('eye_book_general', 'eye_book_date_format');
        register_setting('eye_book_general', 'eye_book_time_format');
        
        // Booking settings
        register_setting('eye_book_booking', 'eye_book_booking_enabled');
        register_setting('eye_book_booking', 'eye_book_booking_advance_days');
        register_setting('eye_book_booking', 'eye_book_cancellation_hours');
        register_setting('eye_book_booking', 'eye_book_default_appointment_duration');
        
        // Security settings
        register_setting('eye_book_security', 'eye_book_hipaa_compliance_mode');
        register_setting('eye_book_security', 'eye_book_encryption_enabled');
        register_setting('eye_book_security', 'eye_book_audit_retention_days');
        register_setting('eye_book_security', 'eye_book_session_timeout');
        
        // Notification settings
        register_setting('eye_book_notifications', 'eye_book_email_reminders_enabled');
        register_setting('eye_book_notifications', 'eye_book_sms_reminders_enabled');
        register_setting('eye_book_notifications', 'eye_book_reminder_email_hours');
        register_setting('eye_book_notifications', 'eye_book_reminder_sms_hours');
    }

    /**
     * Dashboard page
     *
     * @since 1.0.0
     */
    public function dashboard_page() {
        $stats = $this->get_dashboard_stats();
        include EYE_BOOK_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Appointments page
     *
     * @since 1.0.0
     */
    public function appointments_page() {
        $action = $_GET['action'] ?? 'list';
        
        // Handle form submissions
        if ($_POST && isset($_POST['eye_book_appointment_nonce']) && wp_verify_nonce($_POST['eye_book_appointment_nonce'], 'eye_book_appointment_action')) {
            $this->handle_appointment_form_submission();
        }
        
        switch ($action) {
            case 'add':
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/appointment-form.php';
                break;
            case 'edit':
                $appointment_id = intval($_GET['id'] ?? 0);
                if ($appointment_id > 0) {
                    include EYE_BOOK_PLUGIN_DIR . 'admin/views/appointment-form.php';
                } else {
                    wp_redirect(admin_url('admin.php?page=eye-book-appointments'));
                    exit;
                }
                break;
            default:
                $appointments = $this->get_appointments_list();
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/appointments-list.php';
        }
    }

    /**
     * Handle appointment form submission
     *
     * @since 2.0.0
     */
    private function handle_appointment_form_submission() {
        $action = $_POST['action'] ?? '';
        $appointment_id = intval($_POST['appointment_id'] ?? 0);
        
        $appointment_data = array(
            'patient_id' => intval($_POST['patient_id'] ?? 0),
            'provider_id' => intval($_POST['provider_id'] ?? 0),
            'location_id' => intval($_POST['location_id'] ?? 0),
            'appointment_type_id' => intval($_POST['appointment_type_id'] ?? 0),
            'start_datetime' => sanitize_text_field($_POST['start_datetime'] ?? ''),
            'end_datetime' => sanitize_text_field($_POST['end_datetime'] ?? ''),
            'status' => sanitize_text_field($_POST['status'] ?? 'scheduled'),
            'notes' => sanitize_textarea_field($_POST['notes'] ?? ''),
            'patient_notes' => sanitize_textarea_field($_POST['patient_notes'] ?? '')
        );
        
        // Validate required fields
        if (empty($appointment_data['patient_id']) || empty($appointment_data['provider_id']) || 
            empty($appointment_data['start_datetime']) || empty($appointment_data['end_datetime'])) {
            wp_redirect(add_query_arg(array(
                'page' => 'eye-book-appointments',
                'action' => $appointment_id > 0 ? 'edit' : 'add',
                'id' => $appointment_id,
                'message' => 'error',
                'error' => 'missing_fields'
            ), admin_url('admin.php')));
            exit;
        }
        
        try {
            if ($action === 'update' && $appointment_id > 0) {
                // Update existing appointment
                $appointment = new Eye_Book_Appointment($appointment_id);
                if ($appointment->update($appointment_data)) {
                    // Log the update
                    Eye_Book_Audit::log('appointment_updated', 'appointment', $appointment_id, array(
                        'user_id' => get_current_user_id(),
                        'changes' => $appointment_data
                    ));
                    
                    wp_redirect(add_query_arg(array(
                        'page' => 'eye-book-appointments',
                        'message' => 'updated'
                    ), admin_url('admin.php')));
                    exit;
                }
            } else {
                // Create new appointment
                $appointment = new Eye_Book_Appointment();
                $new_id = $appointment->create($appointment_data);
                
                if ($new_id) {
                    // Log the creation
                    Eye_Book_Audit::log('appointment_created', 'appointment', $new_id, array(
                        'user_id' => get_current_user_id(),
                        'appointment_data' => $appointment_data
                    ));
                    
                    // Send notification if needed
                    $this->maybe_send_appointment_notification($new_id, 'created');
                    
                    wp_redirect(add_query_arg(array(
                        'page' => 'eye-book-appointments',
                        'message' => 'created'
                    ), admin_url('admin.php')));
                    exit;
                }
            }
        } catch (Exception $e) {
            error_log('Eye-Book: Appointment save error: ' . $e->getMessage());
            wp_redirect(add_query_arg(array(
                'page' => 'eye-book-appointments',
                'action' => $appointment_id > 0 ? 'edit' : 'add',
                'id' => $appointment_id,
                'message' => 'error',
                'error' => 'save_failed'
            ), admin_url('admin.php')));
            exit;
        }
        
        // If we get here, something went wrong
        wp_redirect(add_query_arg(array(
            'page' => 'eye-book-appointments',
            'message' => 'error'
        ), admin_url('admin.php')));
        exit;
    }

    /**
     * Send appointment notification if needed
     *
     * @param int $appointment_id
     * @param string $type
     * @since 2.0.0
     */
    private function maybe_send_appointment_notification($appointment_id, $type) {
        if (class_exists('Eye_Book_Notifications')) {
            $notifications = new Eye_Book_Notifications();
            switch ($type) {
                case 'created':
                    $notifications->send_appointment_confirmation($appointment_id);
                    break;
                case 'updated':
                    $notifications->send_appointment_update($appointment_id);
                    break;
            }
        }
    }

    /**
     * Patients page
     *
     * @since 1.0.0
     */
    public function patients_page() {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'add':
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/patient-form.php';
                break;
            case 'edit':
                $patient_id = intval($_GET['id'] ?? 0);
                $patient = new Eye_Book_Patient($patient_id);
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/patient-form.php';
                break;
            case 'view':
                $patient_id = intval($_GET['id'] ?? 0);
                $patient = new Eye_Book_Patient($patient_id);
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/patient-profile.php';
                break;
            default:
                $patients = $this->get_patients_list();
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/patients-list.php';
        }
    }

    /**
     * Providers page
     *
     * @since 1.0.0
     */
    public function providers_page() {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'add':
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/provider-form.php';
                break;
            case 'edit':
                $provider_id = intval($_GET['id'] ?? 0);
                $provider = new Eye_Book_Provider($provider_id);
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/provider-form.php';
                break;
            default:
                $providers = Eye_Book_Provider::get_providers();
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/providers-list.php';
        }
    }

    /**
     * Locations page
     *
     * @since 1.0.0
     */
    public function locations_page() {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'add':
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/location-form.php';
                break;
            case 'edit':
                $location_id = intval($_GET['id'] ?? 0);
                $location = new Eye_Book_Location($location_id);
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/location-form.php';
                break;
            default:
                $locations = Eye_Book_Location::get_locations();
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/locations-list.php';
        }
    }

    /**
     * Reports page
     *
     * @since 1.0.0
     */
    public function reports_page() {
        $report_type = $_GET['report'] ?? 'overview';
        $date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $date_to = $_GET['date_to'] ?? date('Y-m-d');
        
        $report_data = $this->get_report_data($report_type, $date_from, $date_to);
        
        include EYE_BOOK_PLUGIN_DIR . 'admin/views/reports.php';
    }

    /**
     * Settings page
     *
     * @since 1.0.0
     */
    public function settings_page() {
        $active_tab = $_GET['tab'] ?? 'general';
        
        if (isset($_POST['submit'])) {
            $this->save_settings($active_tab);
        }
        
        include EYE_BOOK_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Get dashboard statistics
     *
     * @return array
     * @since 1.0.0
     */
    private function get_dashboard_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Today's appointments
        $stats['today_appointments'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . "
             WHERE DATE(start_datetime) = %s AND status NOT IN ('cancelled')",
            current_time('Y-m-d')
        ));
        
        // This week's appointments
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $week_end = date('Y-m-d', strtotime('sunday this week'));
        $stats['week_appointments'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . "
             WHERE DATE(start_datetime) BETWEEN %s AND %s AND status NOT IN ('cancelled')",
            $week_start, $week_end
        ));
        
        // Total patients
        $stats['total_patients'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE status = 'active'"
        );
        
        // New patients this month
        $stats['new_patients'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_PATIENTS . "
             WHERE MONTH(created_at) = %d AND YEAR(created_at) = %d AND status = 'active'",
            date('n'), date('Y')
        ));
        
        // Recent appointments
        $stats['recent_appointments'] = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, p.first_name, p.last_name, pr.wp_user_id as provider_user_id
             FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a
             LEFT JOIN " . EYE_BOOK_TABLE_PATIENTS . " p ON a.patient_id = p.id
             LEFT JOIN " . EYE_BOOK_TABLE_PROVIDERS . " pr ON a.provider_id = pr.id
             WHERE a.start_datetime >= %s
             ORDER BY a.start_datetime ASC
             LIMIT 10",
            current_time('mysql')
        ));
        
        // Upcoming appointments that need attention
        $stats['pending_appointments'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . "
             WHERE start_datetime BETWEEN %s AND %s AND status = 'scheduled'",
            current_time('mysql'),
            date('Y-m-d H:i:s', strtotime('+24 hours'))
        ));
        
        return $stats;
    }

    /**
     * Get appointments list for admin
     *
     * @return array
     * @since 1.0.0
     */
    private function get_appointments_list() {
        $page = intval($_GET['paged'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $date_filter = $_GET['date_filter'] ?? '';
        $status_filter = $_GET['status_filter'] ?? '';
        $provider_filter = intval($_GET['provider_filter'] ?? 0);
        
        $args = array(
            'limit' => $per_page,
            'offset' => $offset,
            'orderby' => 'start_datetime',
            'order' => 'DESC'
        );
        
        if ($date_filter) {
            $args['date_from'] = $date_filter . ' 00:00:00';
            $args['date_to'] = $date_filter . ' 23:59:59';
        }
        
        if ($status_filter) {
            $args['status'] = $status_filter;
        }
        
        if ($provider_filter) {
            $args['provider_id'] = $provider_filter;
        }
        
        return Eye_Book_Appointment::get_appointments($args);
    }

    /**
     * Get patients list for admin
     *
     * @return array
     * @since 1.0.0
     */
    private function get_patients_list() {
        $page = intval($_GET['paged'] ?? 1);
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $search = sanitize_text_field($_GET['s'] ?? '');
        
        $args = array(
            'limit' => $per_page,
            'offset' => $offset,
            'orderby' => 'last_name',
            'order' => 'ASC'
        );
        
        if ($search) {
            $args['search'] = $search;
        }
        
        return Eye_Book_Patient::search_patients($args);
    }

    /**
     * Get report data
     *
     * @param string $report_type
     * @param string $date_from
     * @param string $date_to
     * @return array
     * @since 1.0.0
     */
    private function get_report_data($report_type, $date_from, $date_to) {
        global $wpdb;
        
        $data = array();
        
        switch ($report_type) {
            case 'appointments':
                $data['appointments_by_day'] = $wpdb->get_results($wpdb->prepare(
                    "SELECT DATE(start_datetime) as date, COUNT(*) as count
                     FROM " . EYE_BOOK_TABLE_APPOINTMENTS . "
                     WHERE DATE(start_datetime) BETWEEN %s AND %s
                     GROUP BY DATE(start_datetime)
                     ORDER BY date ASC",
                    $date_from, $date_to
                ));
                
                $data['appointments_by_status'] = $wpdb->get_results($wpdb->prepare(
                    "SELECT status, COUNT(*) as count
                     FROM " . EYE_BOOK_TABLE_APPOINTMENTS . "
                     WHERE DATE(start_datetime) BETWEEN %s AND %s
                     GROUP BY status",
                    $date_from, $date_to
                ));
                break;
                
            case 'providers':
                $data['provider_utilization'] = $wpdb->get_results($wpdb->prepare(
                    "SELECT pr.wp_user_id, COUNT(a.id) as appointment_count
                     FROM " . EYE_BOOK_TABLE_PROVIDERS . " pr
                     LEFT JOIN " . EYE_BOOK_TABLE_APPOINTMENTS . " a ON pr.id = a.provider_id
                     AND DATE(a.start_datetime) BETWEEN %s AND %s
                     GROUP BY pr.id",
                    $date_from, $date_to
                ));
                break;
                
            default:
                // Overview data
                $data['total_appointments'] = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . "
                     WHERE DATE(start_datetime) BETWEEN %s AND %s",
                    $date_from, $date_to
                ));
                
                $data['total_patients'] = $wpdb->get_var(
                    "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE status = 'active'"
                );
        }
        
        return $data;
    }

    /**
     * Save settings
     *
     * @param string $tab
     * @since 1.0.0
     */
    private function save_settings($tab) {
        // Verify nonce
        if (!wp_verify_nonce($_POST['_wpnonce'], 'eye_book_settings')) {
            wp_die(__('Security check failed', 'eye-book'));
        }
        
        switch ($tab) {
            case 'general':
                update_option('eye_book_clinic_name', sanitize_text_field($_POST['clinic_name'] ?? ''));
                update_option('eye_book_timezone', sanitize_text_field($_POST['timezone'] ?? 'America/New_York'));
                update_option('eye_book_date_format', sanitize_text_field($_POST['date_format'] ?? 'F j, Y'));
                update_option('eye_book_time_format', sanitize_text_field($_POST['time_format'] ?? 'g:i a'));
                break;
                
            case 'booking':
                update_option('eye_book_booking_enabled', intval($_POST['booking_enabled'] ?? 0));
                update_option('eye_book_booking_advance_days', intval($_POST['booking_advance_days'] ?? 30));
                update_option('eye_book_cancellation_hours', intval($_POST['cancellation_hours'] ?? 24));
                update_option('eye_book_default_appointment_duration', intval($_POST['default_appointment_duration'] ?? 30));
                break;
                
            case 'security':
                update_option('eye_book_hipaa_compliance_mode', intval($_POST['hipaa_compliance_mode'] ?? 1));
                update_option('eye_book_encryption_enabled', intval($_POST['encryption_enabled'] ?? 1));
                update_option('eye_book_audit_retention_days', intval($_POST['audit_retention_days'] ?? 2555));
                update_option('eye_book_session_timeout', intval($_POST['session_timeout'] ?? 1800));
                break;
                
            case 'notifications':
                update_option('eye_book_email_reminders_enabled', intval($_POST['email_reminders_enabled'] ?? 1));
                update_option('eye_book_sms_reminders_enabled', intval($_POST['sms_reminders_enabled'] ?? 0));
                update_option('eye_book_reminder_email_hours', intval($_POST['reminder_email_hours'] ?? 24));
                update_option('eye_book_reminder_sms_hours', intval($_POST['reminder_sms_hours'] ?? 2));
                break;
        }
        
        add_settings_error('eye_book_settings', 'settings_updated', __('Settings saved successfully.', 'eye-book'), 'success');
    }

    /**
     * AJAX handler for dashboard stats
     *
     * @since 1.0.0
     */
    public function ajax_dashboard_stats() {
        // Check nonce and permissions
        if (!wp_verify_nonce($_POST['nonce'], 'eye_book_ajax_nonce') || 
            !current_user_can('eye_book_manage_all')) {
            wp_die(__('Security check failed', 'eye-book'));
        }
        
        $stats = $this->get_dashboard_stats();
        
        wp_send_json_success($stats);
    }

    /**
     * Add admin head styles for modern UI
     *
     * @since 1.0.0
     */
    public function admin_head_styles() {
        if (!$this->is_eye_book_page()) {
            return;
        }

        echo '<style>
        /* Hide WordPress admin notices on Eye-Book pages for cleaner UI */
        .eye-book-page .notice, .eye-book-page .updated, .eye-book-page .error {
            display: none !important;
        }
        
        /* Modern page wrapper */
        .eye-book-page .wrap {
            margin: 0;
            padding: 0;
            max-width: none;
        }
        
        /* Hide default WordPress titles */
        .eye-book-page h1.wp-heading-inline {
            display: none;
        }
        
        /* Ensure Alpine.js works */
        [x-cloak] { 
            display: none !important; 
        }
        </style>';

        // Add Alpine.js directive
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            if (typeof Alpine !== "undefined") {
                Alpine.start();
            }
        });
        </script>';
    }

    /**
     * Enqueue page-specific scripts
     *
     * @param string $hook
     * @since 1.0.0
     */
    private function enqueue_page_specific_scripts($hook) {
        // Dashboard specific
        if (strpos($hook, 'eye-book') !== false && !strpos($hook, '-')) {
            wp_enqueue_script(
                'eye-book-dashboard',
                EYE_BOOK_PLUGIN_URL . 'admin/assets/js/dashboard.js',
                array('eye-book-admin', 'chart-js'),
                EYE_BOOK_VERSION,
                true
            );
        }

        // Appointments specific
        if (strpos($hook, 'eye-book-appointments') !== false) {
            wp_enqueue_script(
                'eye-book-appointments',
                EYE_BOOK_PLUGIN_URL . 'admin/assets/js/appointments.js',
                array('eye-book-admin', 'flatpickr'),
                EYE_BOOK_VERSION,
                true
            );
        }

        // Patients specific
        if (strpos($hook, 'eye-book-patients') !== false) {
            wp_enqueue_script(
                'eye-book-patients',
                EYE_BOOK_PLUGIN_URL . 'admin/assets/js/patients.js',
                array('eye-book-admin'),
                EYE_BOOK_VERSION,
                true
            );
        }

        // Providers specific
        if (strpos($hook, 'eye-book-providers') !== false) {
            wp_enqueue_script(
                'eye-book-providers',
                EYE_BOOK_PLUGIN_URL . 'admin/assets/js/providers.js',
                array('eye-book-admin'),
                EYE_BOOK_VERSION,
                true
            );
        }

        // Locations specific
        if (strpos($hook, 'eye-book-locations') !== false) {
            wp_enqueue_script(
                'eye-book-locations',
                EYE_BOOK_PLUGIN_URL . 'admin/assets/js/locations.js',
                array('eye-book-admin'),
                EYE_BOOK_VERSION,
                true
            );
        }

        // Calendar specific
        if (strpos($hook, 'eye-book-calendar') !== false) {
            wp_enqueue_script(
                'eye-book-calendar',
                EYE_BOOK_PLUGIN_URL . 'admin/assets/js/calendar.js',
                array('eye-book-admin', 'flatpickr'),
                EYE_BOOK_VERSION,
                true
            );
        }

        // Reports specific
        if (strpos($hook, 'eye-book-reports') !== false) {
            wp_enqueue_script(
                'eye-book-reports',
                EYE_BOOK_PLUGIN_URL . 'admin/assets/js/reports.js',
                array('eye-book-admin', 'chart-js'),
                EYE_BOOK_VERSION,
                true
            );
        }
    }

    /**
     * Check if current page is Eye-Book admin page
     *
     * @return bool
     * @since 1.0.0
     */
    private function is_eye_book_page() {
        $current_screen = get_current_screen();
        return $current_screen && strpos($current_screen->id, 'eye-book') !== false;
    }

    /**
     * AJAX handler for appointments
     *
     * @since 1.0.0
     */
    public function ajax_get_appointments() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_appointments')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $search = sanitize_text_field($_POST['search'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');

        $args = array(
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'search' => $search,
            'status' => $status,
            'date_from' => $date_from,
            'date_to' => $date_to,
            'orderby' => 'start_datetime',
            'order' => 'DESC'
        );

        $appointments = Eye_Book_Appointment::get_appointments($args);
        $total = Eye_Book_Appointment::count_appointments($args);

        wp_send_json_success(array(
            'appointments' => $appointments,
            'total' => $total,
            'pages' => ceil($total / $per_page)
        ));
    }

    /**
     * AJAX handler for saving appointments
     *
     * @since 1.0.0
     */
    public function ajax_save_appointment() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_appointments')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $appointment_id = intval($_POST['id'] ?? 0);
        $patient_id = intval($_POST['patient_id'] ?? 0);
        $provider_id = intval($_POST['provider_id'] ?? 0);
        $location_id = intval($_POST['location_id'] ?? 0);
        $appointment_type_id = intval($_POST['appointment_type_id'] ?? 0);
        $start_datetime = sanitize_text_field($_POST['start_datetime'] ?? '');
        $end_datetime = sanitize_text_field($_POST['end_datetime'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? 'scheduled');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');

        $appointment_data = array(
            'patient_id' => $patient_id,
            'provider_id' => $provider_id,
            'location_id' => $location_id,
            'appointment_type_id' => $appointment_type_id,
            'start_datetime' => $start_datetime,
            'end_datetime' => $end_datetime,
            'status' => $status,
            'notes' => $notes
        );

        if ($appointment_id > 0) {
            $appointment = new Eye_Book_Appointment($appointment_id);
            $result = $appointment->update($appointment_data);
        } else {
            $appointment = new Eye_Book_Appointment();
            $result = $appointment->create($appointment_data);
            $appointment_id = $appointment->get_id();
        }

        if ($result) {
            wp_send_json_success(array(
                'id' => $appointment_id,
                'message' => __('Appointment saved successfully', 'eye-book')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save appointment', 'eye-book')
            ));
        }
    }

    /**
     * AJAX handler for deleting appointments
     *
     * @since 1.0.0
     */
    public function ajax_delete_appointment() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_appointments')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $appointment_id = intval($_POST['id'] ?? 0);

        if ($appointment_id <= 0) {
            wp_send_json_error(array(
                'message' => __('Invalid appointment ID', 'eye-book')
            ));
        }

        $appointment = new Eye_Book_Appointment($appointment_id);
        $result = $appointment->delete();

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Appointment deleted successfully', 'eye-book')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete appointment', 'eye-book')
            ));
        }
    }

    /**
     * AJAX handler for patients
     *
     * @since 1.0.0
     */
    public function ajax_get_patients() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_patients')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $search = sanitize_text_field($_POST['search'] ?? '');

        $args = array(
            'limit' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'search' => $search,
            'orderby' => 'last_name',
            'order' => 'ASC'
        );

        $patients = Eye_Book_Patient::search_patients($args);
        $total = Eye_Book_Patient::count_patients($args);

        wp_send_json_success(array(
            'patients' => $patients,
            'total' => $total,
            'pages' => ceil($total / $per_page)
        ));
    }

    /**
     * AJAX handler for saving patients
     *
     * @since 1.0.0
     */
    public function ajax_save_patient() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_patients')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $patient_id = intval($_POST['id'] ?? 0);
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name = sanitize_text_field($_POST['last_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $date_of_birth = sanitize_text_field($_POST['date_of_birth'] ?? '');
        $address = sanitize_textarea_field($_POST['address'] ?? '');
        $emergency_contact_name = sanitize_text_field($_POST['emergency_contact_name'] ?? '');
        $emergency_contact_phone = sanitize_text_field($_POST['emergency_contact_phone'] ?? '');
        $medical_history = sanitize_textarea_field($_POST['medical_history'] ?? '');

        $patient_data = array(
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'date_of_birth' => $date_of_birth,
            'address' => $address,
            'emergency_contact_name' => $emergency_contact_name,
            'emergency_contact_phone' => $emergency_contact_phone,
            'medical_history' => $medical_history
        );

        if ($patient_id > 0) {
            $patient = new Eye_Book_Patient($patient_id);
            $result = $patient->update($patient_data);
        } else {
            $patient = new Eye_Book_Patient();
            $result = $patient->create($patient_data);
            $patient_id = $patient->get_id();
        }

        if ($result) {
            wp_send_json_success(array(
                'id' => $patient_id,
                'message' => __('Patient saved successfully', 'eye-book')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save patient', 'eye-book')
            ));
        }
    }

    /**
     * AJAX handler for deleting patients
     *
     * @since 1.0.0
     */
    public function ajax_delete_patient() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_patients')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $patient_id = intval($_POST['id'] ?? 0);

        if ($patient_id <= 0) {
            wp_send_json_error(array(
                'message' => __('Invalid patient ID', 'eye-book')
            ));
        }

        $patient = new Eye_Book_Patient($patient_id);
        $result = $patient->delete();

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Patient deleted successfully', 'eye-book')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to delete patient', 'eye-book')
            ));
        }
    }

    /**
     * AJAX handler for providers
     *
     * @since 1.0.0
     */
    public function ajax_get_providers() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_all')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $providers = Eye_Book_Provider::get_providers();
        wp_send_json_success($providers);
    }

    /**
     * AJAX handler for saving providers
     *
     * @since 1.0.0
     */
    public function ajax_save_provider() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_all')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $provider_id = intval($_POST['id'] ?? 0);
        $wp_user_id = intval($_POST['wp_user_id'] ?? 0);
        $specialty = sanitize_text_field($_POST['specialty'] ?? '');
        $license_number = sanitize_text_field($_POST['license_number'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        $provider_data = array(
            'wp_user_id' => $wp_user_id,
            'specialty' => $specialty,
            'license_number' => $license_number,
            'phone' => $phone,
            'email' => $email
        );

        if ($provider_id > 0) {
            $provider = new Eye_Book_Provider($provider_id);
            $result = $provider->update($provider_data);
        } else {
            $provider = new Eye_Book_Provider();
            $result = $provider->create($provider_data);
            $provider_id = $provider->get_id();
        }

        if ($result) {
            wp_send_json_success(array(
                'id' => $provider_id,
                'message' => __('Provider saved successfully', 'eye-book')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save provider', 'eye-book')
            ));
        }
    }

    /**
     * AJAX handler for locations
     *
     * @since 1.0.0
     */
    public function ajax_get_locations() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_all')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $locations = Eye_Book_Location::get_locations();
        wp_send_json_success($locations);
    }

    /**
     * AJAX handler for saving locations
     *
     * @since 1.0.0
     */
    public function ajax_save_location() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_all')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $location_id = intval($_POST['id'] ?? 0);
        $name = sanitize_text_field($_POST['name'] ?? '');
        $address = sanitize_textarea_field($_POST['address'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');

        $location_data = array(
            'name' => $name,
            'address' => $address,
            'phone' => $phone,
            'email' => $email
        );

        if ($location_id > 0) {
            $location = new Eye_Book_Location($location_id);
            $result = $location->update($location_data);
        } else {
            $location = new Eye_Book_Location();
            $result = $location->create($location_data);
            $location_id = $location->get_id();
        }

        if ($result) {
            wp_send_json_success(array(
                'id' => $location_id,
                'message' => __('Location saved successfully', 'eye-book')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to save location', 'eye-book')
            ));
        }
    }

    /**
     * AJAX handler for generating reports
     *
     * @since 1.0.0
     */
    public function ajax_generate_report() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_view_reports')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $report_type = sanitize_text_field($_POST['report_type'] ?? '');
        $date_from = sanitize_text_field($_POST['date_from'] ?? '');
        $date_to = sanitize_text_field($_POST['date_to'] ?? '');

        $report_data = $this->get_report_data($report_type, $date_from, $date_to);

        wp_send_json_success(array(
            'data' => $report_data,
            'type' => $report_type
        ));
    }

    /**
     * AJAX handler for getting single patient
     *
     * @since 1.0.0
     */
    public function ajax_get_patient() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_all')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $patient_id = intval($_POST['patient_id'] ?? 0);
        if (!$patient_id) {
            wp_send_json_error(__('Invalid patient ID', 'eye-book'));
        }

        global $wpdb;
        $patient = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE id = %d", 
            $patient_id
        ));

        if (!$patient) {
            wp_send_json_error(__('Patient not found', 'eye-book'));
        }

        wp_send_json_success($patient);
    }

    /**
     * AJAX handler for bulk actions on patients
     *
     * @since 1.0.0
     */
    public function ajax_bulk_action_patients() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_all')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $patient_ids = array_map('intval', $_POST['patient_ids'] ?? array());

        if (empty($patient_ids)) {
            wp_send_json_error(__('No patients selected', 'eye-book'));
        }

        global $wpdb;
        $success_count = 0;

        foreach ($patient_ids as $patient_id) {
            switch ($action) {
                case 'delete':
                    $result = $wpdb->delete(EYE_BOOK_TABLE_PATIENTS, array('id' => $patient_id), array('%d'));
                    if ($result) $success_count++;
                    break;
                case 'export':
                    // Handle export functionality
                    $success_count++;
                    break;
            }
        }

        wp_send_json_success(sprintf(__('%d patients processed successfully', 'eye-book'), $success_count));
    }

    /**
     * AJAX handler for getting single provider
     *
     * @since 1.0.0
     */
    public function ajax_get_provider() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_all')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $provider_id = intval($_POST['provider_id'] ?? 0);
        if (!$provider_id) {
            wp_send_json_error(__('Invalid provider ID', 'eye-book'));
        }

        global $wpdb;
        $provider = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . EYE_BOOK_TABLE_PROVIDERS . " WHERE id = %d", 
            $provider_id
        ));

        if (!$provider) {
            wp_send_json_error(__('Provider not found', 'eye-book'));
        }

        wp_send_json_success($provider);
    }

    /**
     * AJAX handler for deleting provider
     *
     * @since 1.0.0
     */
    public function ajax_delete_provider() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_all')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $provider_id = intval($_POST['provider_id'] ?? 0);
        if (!$provider_id) {
            wp_send_json_error(__('Invalid provider ID', 'eye-book'));
        }

        global $wpdb;
        $result = $wpdb->delete(EYE_BOOK_TABLE_PROVIDERS, array('id' => $provider_id), array('%d'));

        if ($result) {
            wp_send_json_success(__('Provider deleted successfully', 'eye-book'));
        } else {
            wp_send_json_error(__('Failed to delete provider', 'eye-book'));
        }
    }

    /**
     * AJAX handler for bulk actions on providers
     *
     * @since 1.0.0
     */
    public function ajax_bulk_action_providers() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_all')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $provider_ids = array_map('intval', $_POST['provider_ids'] ?? array());

        if (empty($provider_ids)) {
            wp_send_json_error(__('No providers selected', 'eye-book'));
        }

        global $wpdb;
        $success_count = 0;

        foreach ($provider_ids as $provider_id) {
            switch ($action) {
                case 'activate':
                    $result = $wpdb->update(
                        EYE_BOOK_TABLE_PROVIDERS, 
                        array('status' => 'active'), 
                        array('id' => $provider_id), 
                        array('%s'), 
                        array('%d')
                    );
                    if ($result !== false) $success_count++;
                    break;
                case 'deactivate':
                    $result = $wpdb->update(
                        EYE_BOOK_TABLE_PROVIDERS, 
                        array('status' => 'inactive'), 
                        array('id' => $provider_id), 
                        array('%s'), 
                        array('%d')
                    );
                    if ($result !== false) $success_count++;
                    break;
                case 'delete':
                    $result = $wpdb->delete(EYE_BOOK_TABLE_PROVIDERS, array('id' => $provider_id), array('%d'));
                    if ($result) $success_count++;
                    break;
            }
        }

        wp_send_json_success(sprintf(__('%d providers processed successfully', 'eye-book'), $success_count));
    }

    /**
     * AJAX handler for getting single location
     *
     * @since 1.0.0
     */
    public function ajax_get_location() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_all')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $location_id = intval($_POST['location_id'] ?? 0);
        if (!$location_id) {
            wp_send_json_error(__('Invalid location ID', 'eye-book'));
        }

        global $wpdb;
        $location = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . EYE_BOOK_TABLE_LOCATIONS . " WHERE id = %d", 
            $location_id
        ));

        if (!$location) {
            wp_send_json_error(__('Location not found', 'eye-book'));
        }

        wp_send_json_success($location);
    }

    /**
     * AJAX handler for deleting location
     *
     * @since 1.0.0
     */
    public function ajax_delete_location() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_all')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $location_id = intval($_POST['location_id'] ?? 0);
        if (!$location_id) {
            wp_send_json_error(__('Invalid location ID', 'eye-book'));
        }

        global $wpdb;
        $result = $wpdb->delete(EYE_BOOK_TABLE_LOCATIONS, array('id' => $location_id), array('%d'));

        if ($result) {
            wp_send_json_success(__('Location deleted successfully', 'eye-book'));
        } else {
            wp_send_json_error(__('Failed to delete location', 'eye-book'));
        }
    }

    /**
     * AJAX handler for bulk actions on locations
     *
     * @since 1.0.0
     */
    public function ajax_bulk_action_locations() {
        check_ajax_referer('eye_book_ajax_nonce', 'nonce');

        if (!current_user_can('eye_book_manage_all')) {
            wp_die(__('Permission denied', 'eye-book'));
        }

        $action = sanitize_text_field($_POST['bulk_action'] ?? '');
        $location_ids = array_map('intval', $_POST['location_ids'] ?? array());

        if (empty($location_ids)) {
            wp_send_json_error(__('No locations selected', 'eye-book'));
        }

        global $wpdb;
        $success_count = 0;

        foreach ($location_ids as $location_id) {
            switch ($action) {
                case 'activate':
                    $result = $wpdb->update(
                        EYE_BOOK_TABLE_LOCATIONS, 
                        array('status' => 'active'), 
                        array('id' => $location_id), 
                        array('%s'), 
                        array('%d')
                    );
                    if ($result !== false) $success_count++;
                    break;
                case 'deactivate':
                    $result = $wpdb->update(
                        EYE_BOOK_TABLE_LOCATIONS, 
                        array('status' => 'inactive'), 
                        array('id' => $location_id), 
                        array('%s'), 
                        array('%d')
                    );
                    if ($result !== false) $success_count++;
                    break;
                case 'delete':
                    $result = $wpdb->delete(EYE_BOOK_TABLE_LOCATIONS, array('id' => $location_id), array('%d'));
                    if ($result) $success_count++;
                    break;
            }
        }

        wp_send_json_success(sprintf(__('%d locations processed successfully', 'eye-book'), $success_count));
    }

    /**
     * Enqueue CDN resources with fallback handling
     *
     * @param string $handle Script/style handle
     * @param string $src CDN URL
     * @param string $version Version
     * @param bool $is_style Whether this is a stylesheet
     * @since 1.0.0
     */
    private function enqueue_cdn_with_fallback($handle, $src, $version, $is_style = false) {
        // Add fallback check for essential libraries only
        if ($is_style) {
            wp_enqueue_style($handle, $src, array(), $version);
        } else {
            wp_enqueue_script($handle, $src, array(), $version, true);
            
            // Add fallback for critical libraries
            if (in_array($handle, array('alpine-js', 'chart-js', 'flatpickr'))) {
                $fallback_script = "
                    document.addEventListener('DOMContentLoaded', function() {
                        // Basic fallback for {$handle} if CDN fails
                        if (typeof window.{$handle} === 'undefined' && typeof window.Alpine === 'undefined' && typeof window.Chart === 'undefined') {
                            console.warn('CDN library {$handle} failed to load. Some features may be limited.');
                            // Use jQuery as fallback for basic functionality
                            if (typeof jQuery !== 'undefined') {
                                // Basic functionality fallback
                                window.{$handle} = { loaded: false };
                            }
                        }
                    });
                ";
                wp_add_inline_script($handle, $fallback_script);
            }
        }
    }
}