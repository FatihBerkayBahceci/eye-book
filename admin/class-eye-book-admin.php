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

        // Enqueue admin styles
        wp_enqueue_style(
            'eye-book-admin',
            EYE_BOOK_PLUGIN_URL . 'admin/assets/css/eye-book-admin.css',
            array(),
            EYE_BOOK_VERSION
        );

        // Enqueue admin scripts
        wp_enqueue_script(
            'eye-book-admin',
            EYE_BOOK_PLUGIN_URL . 'admin/assets/js/eye-book-admin.js',
            array('jquery', 'wp-api'),
            EYE_BOOK_VERSION,
            true
        );

        // Localize script
        wp_localize_script('eye-book-admin', 'eyeBookAdmin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('eye_book_ajax_nonce'),
            'strings' => array(
                'loading' => __('Loading...', 'eye-book'),
                'error' => __('An error occurred', 'eye-book'),
                'confirm_delete' => __('Are you sure you want to delete this item?', 'eye-book'),
                'save_success' => __('Changes saved successfully', 'eye-book')
            )
        ));

        // Enqueue additional scripts for specific pages
        if (strpos($hook, 'eye-book-appointments') !== false) {
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_style('jquery-ui-datepicker');
        }

        if (strpos($hook, 'eye-book-reports') !== false) {
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1');
        }
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
        
        switch ($action) {
            case 'add':
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/appointment-form.php';
                break;
            case 'edit':
                $appointment_id = intval($_GET['id'] ?? 0);
                $appointment = new Eye_Book_Appointment($appointment_id);
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/appointment-form.php';
                break;
            default:
                $appointments = $this->get_appointments_list();
                include EYE_BOOK_PLUGIN_DIR . 'admin/views/appointments-list.php';
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
}