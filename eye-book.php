<?php
/**
 * Plugin Name: Eye-Book
 * Plugin URI: https://github.com/fatihberkaybahceci/eye-book
 * Description: Enterprise-level appointment scheduling plugin for eye care practices in the US. HIPAA compliant with comprehensive patient management, provider scheduling, and clinical workflow integration.
 * Version: 1.0.0
 * Author: Fatih Berkay Bahçeci
 * Author URI: https://fatihberkaybahceci.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: eye-book
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * Network: false
 *
 * @package EyeBook
 * @author Fatih Berkay Bahçeci
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EYE_BOOK_VERSION', '1.0.0');
define('EYE_BOOK_PLUGIN_FILE', __FILE__);
define('EYE_BOOK_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EYE_BOOK_PLUGIN_URL', plugin_dir_url(__FILE__));
define('EYE_BOOK_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('EYE_BOOK_TEXTDOMAIN', 'eye-book');

// Minimum WordPress version required
define('EYE_BOOK_MIN_WP_VERSION', '5.0');

// Minimum PHP version required
define('EYE_BOOK_MIN_PHP_VERSION', '7.4');

/**
 * Main EyeBook Class
 *
 * @class EyeBook
 * @since 1.0.0
 */
final class EyeBook {

    /**
     * Plugin instance
     *
     * @var EyeBook
     * @since 1.0.0
     */
    private static $instance = null;

    /**
     * Plugin version
     *
     * @var string
     * @since 1.0.0
     */
    public $version = EYE_BOOK_VERSION;

    /**
     * Get EyeBook instance
     *
     * @return EyeBook
     * @since 1.0.0
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * EyeBook Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->define_constants();
        $this->check_requirements();
        $this->init_hooks();
        $this->includes();
        $this->init();
    }

    /**
     * Define additional constants if needed
     *
     * @since 1.0.0
     */
    private function define_constants() {
        // Database table prefixes
        global $wpdb;
        define('EYE_BOOK_TABLE_PREFIX', $wpdb->prefix . 'eye_book_');
        
        // Define table names
        define('EYE_BOOK_TABLE_APPOINTMENTS', EYE_BOOK_TABLE_PREFIX . 'appointments');
        define('EYE_BOOK_TABLE_PATIENTS', EYE_BOOK_TABLE_PREFIX . 'patients');
        define('EYE_BOOK_TABLE_PROVIDERS', EYE_BOOK_TABLE_PREFIX . 'providers');
        define('EYE_BOOK_TABLE_LOCATIONS', EYE_BOOK_TABLE_PREFIX . 'locations');
        define('EYE_BOOK_TABLE_APPOINTMENT_TYPES', EYE_BOOK_TABLE_PREFIX . 'appointment_types');
        define('EYE_BOOK_TABLE_PATIENT_FORMS', EYE_BOOK_TABLE_PREFIX . 'patient_forms');
        define('EYE_BOOK_TABLE_AUDIT_LOG', EYE_BOOK_TABLE_PREFIX . 'audit_log');
        define('EYE_BOOK_TABLE_SETTINGS', EYE_BOOK_TABLE_PREFIX . 'settings');
    }

    /**
     * Check system requirements
     *
     * @since 1.0.0
     */
    private function check_requirements() {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), EYE_BOOK_MIN_WP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'wp_version_notice'));
            return false;
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, EYE_BOOK_MIN_PHP_VERSION, '<')) {
            add_action('admin_notices', array($this, 'php_version_notice'));
            return false;
        }

        // Check for required PHP extensions
        $required_extensions = array('mysqli', 'openssl', 'json', 'mbstring');
        foreach ($required_extensions as $extension) {
            if (!extension_loaded($extension)) {
                add_action('admin_notices', array($this, 'php_extension_notice'));
                return false;
            }
        }

        return true;
    }

    /**
     * Initialize hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        add_action('init', array($this, 'load_plugin_textdomain'));
        
        // Plugin activation/deactivation hooks
        register_activation_hook(EYE_BOOK_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(EYE_BOOK_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Admin hooks will be handled by Eye_Book_Admin class
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
    }

    /**
     * Include required files
     *
     * @since 1.0.0
     */
    private function includes() {
        // Core includes
        include_once EYE_BOOK_PLUGIN_DIR . 'includes/class-eye-book-database.php';
        include_once EYE_BOOK_PLUGIN_DIR . 'includes/class-eye-book-security.php';
        include_once EYE_BOOK_PLUGIN_DIR . 'includes/class-eye-book-audit.php';
        include_once EYE_BOOK_PLUGIN_DIR . 'includes/class-eye-book-encryption.php';
        include_once EYE_BOOK_PLUGIN_DIR . 'includes/class-eye-book-notifications.php';
        
        // Admin includes
        if (is_admin()) {
            include_once EYE_BOOK_PLUGIN_DIR . 'admin/class-eye-book-admin.php';
            include_once EYE_BOOK_PLUGIN_DIR . 'admin/class-eye-book-settings.php';
        }
        
        // Public includes
        include_once EYE_BOOK_PLUGIN_DIR . 'public/class-eye-book-public.php';
        include_once EYE_BOOK_PLUGIN_DIR . 'public/class-eye-book-booking.php';
        
        // Model includes
        include_once EYE_BOOK_PLUGIN_DIR . 'includes/models/class-eye-book-appointment.php';
        include_once EYE_BOOK_PLUGIN_DIR . 'includes/models/class-eye-book-patient.php';
        include_once EYE_BOOK_PLUGIN_DIR . 'includes/models/class-eye-book-provider.php';
        include_once EYE_BOOK_PLUGIN_DIR . 'includes/models/class-eye-book-location.php';
        include_once EYE_BOOK_PLUGIN_DIR . 'includes/models/class-eye-book-appointment-type.php';
        include_once EYE_BOOK_PLUGIN_DIR . 'includes/models/class-eye-book-patient-form.php';
        include_once EYE_BOOK_PLUGIN_DIR . 'includes/models/class-eye-book-settings.php';
    }

    /**
     * Initialize plugin components
     *
     * @since 1.0.0
     */
    private function init() {
        // Initialize core components
        new Eye_Book_Security();
        new Eye_Book_Audit();
        new Eye_Book_Notifications();
        
        if (is_admin()) {
            new Eye_Book_Admin();
        }
        
        new Eye_Book_Public();
        new Eye_Book_Booking();
    }

    /**
     * Load plugin textdomain for internationalization
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            EYE_BOOK_TEXTDOMAIN,
            false,
            dirname(EYE_BOOK_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Plugin activation
     *
     * @since 1.0.0
     */
    public function activate() {
        // Create database tables
        $database = new Eye_Book_Database();
        $database->create_tables();
        
        // Create default roles and capabilities
        $this->create_roles_and_caps();
        
        // Set default settings
        $this->set_default_settings();
        
        // Schedule cron events
        $this->schedule_events();
        
        // Log activation
        if (class_exists('Eye_Book_Audit')) {
            Eye_Book_Audit::log('plugin_activated', 'system', 0, array(
                'version' => EYE_BOOK_VERSION,
                'user_id' => get_current_user_id()
            ));
        }
        
        // Set activation flag
        update_option('eye_book_version', EYE_BOOK_VERSION);
        update_option('eye_book_activation_time', current_time('timestamp'));
    }

    /**
     * Plugin deactivation
     *
     * @since 1.0.0
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('eye_book_daily_cleanup');
        wp_clear_scheduled_hook('eye_book_send_reminders');
        
        // Log deactivation
        if (class_exists('Eye_Book_Audit')) {
            Eye_Book_Audit::log('plugin_deactivated', 'system', 0, array(
                'version' => EYE_BOOK_VERSION,
                'user_id' => get_current_user_id()
            ));
        }
    }

    /**
     * Create custom roles and capabilities
     *
     * @since 1.0.0
     */
    private function create_roles_and_caps() {
        // Define capabilities
        $clinic_admin_caps = array(
            'eye_book_manage_all',
            'eye_book_manage_appointments',
            'eye_book_manage_patients',
            'eye_book_manage_providers',
            'eye_book_manage_settings',
            'eye_book_view_reports',
            'read'
        );
        
        $doctor_caps = array(
            'eye_book_manage_own_appointments',
            'eye_book_view_own_patients',
            'eye_book_manage_own_schedule',
            'eye_book_view_own_reports',
            'read'
        );
        
        $receptionist_caps = array(
            'eye_book_manage_appointments',
            'eye_book_view_patients',
            'eye_book_checkin_patients',
            'read'
        );
        
        // Create roles
        add_role('eye_book_clinic_admin', __('Clinic Administrator', 'eye-book'), $clinic_admin_caps);
        add_role('eye_book_doctor', __('Doctor', 'eye-book'), $doctor_caps);
        add_role('eye_book_nurse', __('Nurse', 'eye-book'), $receptionist_caps);
        add_role('eye_book_receptionist', __('Receptionist', 'eye-book'), $receptionist_caps);
        
        // Add capabilities to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($clinic_admin_caps as $cap) {
                $admin_role->add_cap($cap);
            }
        }
    }

    /**
     * Set default plugin settings
     *
     * @since 1.0.0
     */
    private function set_default_settings() {
        $default_settings = array(
            'booking_enabled' => 1,
            'booking_advance_days' => 30,
            'appointment_duration' => 30,
            'reminder_email_hours' => 24,
            'reminder_sms_hours' => 2,
            'timezone' => get_option('timezone_string', 'America/New_York'),
            'date_format' => get_option('date_format', 'F j, Y'),
            'time_format' => get_option('time_format', 'g:i a'),
            'hipaa_compliance_mode' => 1,
            'audit_retention_days' => 2555, // 7 years
            'encryption_enabled' => 1
        );
        
        foreach ($default_settings as $key => $value) {
            add_option('eye_book_' . $key, $value);
        }
    }

    /**
     * Schedule cron events
     *
     * @since 1.0.0
     */
    private function schedule_events() {
        // Daily cleanup
        if (!wp_next_scheduled('eye_book_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'eye_book_daily_cleanup');
        }
        
        // Send reminders every hour
        if (!wp_next_scheduled('eye_book_send_reminders')) {
            wp_schedule_event(time(), 'hourly', 'eye_book_send_reminders');
        }
    }

    /**
     * Add admin menu
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        if (current_user_can('eye_book_manage_all') || current_user_can('manage_options')) {
            add_menu_page(
                __('Eye-Book', 'eye-book'),
                __('Eye-Book', 'eye-book'),
                'eye_book_manage_all',
                'eye-book',
                array($this, 'admin_dashboard'),
                'dashicons-calendar-alt',
                30
            );
        }
    }

    /**
     * Admin dashboard callback
     *
     * @since 1.0.0
     */
    public function admin_dashboard() {
        echo '<div class="wrap"><h1>' . __('Eye-Book Dashboard', 'eye-book') . '</h1></div>';
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook
     * @since 1.0.0
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'eye-book') !== false) {
            wp_enqueue_script(
                'eye-book-admin',
                EYE_BOOK_PLUGIN_URL . 'admin/assets/js/eye-book-admin.js',
                array('jquery'),
                EYE_BOOK_VERSION,
                true
            );
            
            wp_enqueue_style(
                'eye-book-admin',
                EYE_BOOK_PLUGIN_URL . 'admin/assets/css/eye-book-admin.css',
                array(),
                EYE_BOOK_VERSION
            );
        }
    }

    /**
     * Enqueue frontend scripts and styles
     *
     * @since 1.0.0
     */
    public function frontend_enqueue_scripts() {
        wp_enqueue_script(
            'eye-book-public',
            EYE_BOOK_PLUGIN_URL . 'public/assets/js/eye-book-public.js',
            array('jquery'),
            EYE_BOOK_VERSION,
            true
        );
        
        wp_enqueue_style(
            'eye-book-public',
            EYE_BOOK_PLUGIN_URL . 'public/assets/css/eye-book-public.css',
            array(),
            EYE_BOOK_VERSION
        );
    }

    /**
     * WordPress version notice
     *
     * @since 1.0.0
     */
    public function wp_version_notice() {
        echo '<div class="notice notice-error"><p>';
        printf(
            __('Eye-Book requires WordPress version %s or higher. You are running version %s.', 'eye-book'),
            EYE_BOOK_MIN_WP_VERSION,
            get_bloginfo('version')
        );
        echo '</p></div>';
    }

    /**
     * PHP version notice
     *
     * @since 1.0.0
     */
    public function php_version_notice() {
        echo '<div class="notice notice-error"><p>';
        printf(
            __('Eye-Book requires PHP version %s or higher. You are running version %s.', 'eye-book'),
            EYE_BOOK_MIN_PHP_VERSION,
            PHP_VERSION
        );
        echo '</p></div>';
    }

    /**
     * PHP extension notice
     *
     * @since 1.0.0
     */
    public function php_extension_notice() {
        echo '<div class="notice notice-error"><p>';
        _e('Eye-Book requires certain PHP extensions to be installed. Please contact your hosting provider.', 'eye-book');
        echo '</p></div>';
    }
}

/**
 * Initialize EyeBook plugin
 *
 * @return EyeBook
 * @since 1.0.0
 */
function eye_book() {
    return EyeBook::instance();
}

// Start the plugin
eye_book();