<?php
/**
 * Database management class for Eye-Book plugin
 *
 * @package EyeBook
 * @subpackage Database
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eye_Book_Database Class
 *
 * Handles database operations, table creation, and schema management
 *
 * @class Eye_Book_Database
 * @since 1.0.0
 */
class Eye_Book_Database {

    /**
     * Database version
     *
     * @var string
     * @since 1.0.0
     */
    private $db_version = '1.0.0';

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('plugins_loaded', array($this, 'check_database_version'));
    }

    /**
     * Check if database needs updating
     *
     * @since 1.0.0
     */
    public function check_database_version() {
        $installed_version = get_option('eye_book_db_version', '0.0.0');
        
        if (version_compare($installed_version, $this->db_version, '<')) {
            $this->create_tables();
            update_option('eye_book_db_version', $this->db_version);
        }
    }

    /**
     * Create all database tables
     *
     * @since 1.0.0
     */
    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Create tables in proper order to handle foreign key constraints
        $this->create_locations_table($charset_collate);
        $this->create_providers_table($charset_collate);
        $this->create_patients_table($charset_collate);
        $this->create_appointment_types_table($charset_collate);
        $this->create_appointments_table($charset_collate);
        $this->create_patient_forms_table($charset_collate);
        $this->create_audit_log_table($charset_collate);
        $this->create_settings_table($charset_collate);

        // Insert default data
        $this->insert_default_data();
    }

    /**
     * Create locations table
     *
     * @param string $charset_collate
     * @since 1.0.0
     */
    private function create_locations_table($charset_collate) {
        global $wpdb;

        $table_name = EYE_BOOK_TABLE_LOCATIONS;

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            address_line1 varchar(255) NOT NULL,
            address_line2 varchar(255) DEFAULT NULL,
            city varchar(100) NOT NULL,
            state varchar(2) NOT NULL,
            zip_code varchar(10) NOT NULL,
            phone varchar(20) NOT NULL,
            email varchar(255) DEFAULT NULL,
            timezone varchar(50) DEFAULT 'America/New_York',
            status enum('active','inactive') DEFAULT 'active',
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_status (status),
            KEY idx_state (state)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create providers table
     *
     * @param string $charset_collate
     * @since 1.0.0
     */
    private function create_providers_table($charset_collate) {
        global $wpdb;

        $table_name = EYE_BOOK_TABLE_PROVIDERS;

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            wp_user_id bigint(20) unsigned NOT NULL,
            license_number varchar(100) DEFAULT NULL,
            specialty enum('optometrist','ophthalmologist','optician','technician') NOT NULL,
            subspecialty varchar(255) DEFAULT NULL,
            title varchar(100) DEFAULT NULL,
            bio text,
            education text,
            certifications text,
            languages varchar(255) DEFAULT NULL,
            schedule_template longtext,
            location_ids varchar(255) DEFAULT NULL,
            hourly_rate decimal(10,2) DEFAULT NULL,
            status enum('active','inactive','on_leave') DEFAULT 'active',
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_wp_user_id (wp_user_id),
            KEY idx_specialty (specialty),
            KEY idx_status (status),
            KEY idx_license (license_number)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create patients table
     *
     * @param string $charset_collate
     * @since 1.0.0
     */
    private function create_patients_table($charset_collate) {
        global $wpdb;

        $table_name = EYE_BOOK_TABLE_PATIENTS;

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            wp_user_id bigint(20) unsigned DEFAULT NULL,
            patient_id varchar(50) UNIQUE NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            date_of_birth date NOT NULL,
            gender enum('male','female','other','prefer_not_to_say') DEFAULT NULL,
            phone varchar(20) NOT NULL,
            email varchar(255) DEFAULT NULL,
            address_line1 varchar(255) DEFAULT NULL,
            address_line2 varchar(255) DEFAULT NULL,
            city varchar(100) DEFAULT NULL,
            state varchar(2) DEFAULT NULL,
            zip_code varchar(10) DEFAULT NULL,
            emergency_contact_name varchar(255) DEFAULT NULL,
            emergency_contact_phone varchar(20) DEFAULT NULL,
            emergency_contact_relationship varchar(100) DEFAULT NULL,
            insurance_provider varchar(255) DEFAULT NULL,
            insurance_member_id varchar(100) DEFAULT NULL,
            insurance_group_number varchar(100) DEFAULT NULL,
            preferred_language varchar(50) DEFAULT 'en',
            medical_history longtext,
            allergies text,
            current_medications text,
            preferred_provider_id int(11) DEFAULT NULL,
            notes text,
            status enum('active','inactive','deceased') DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_patient_id (patient_id),
            KEY idx_wp_user_id (wp_user_id),
            KEY idx_last_name (last_name),
            KEY idx_phone (phone),
            KEY idx_email (email),
            KEY idx_status (status),
            KEY idx_preferred_provider (preferred_provider_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create appointment types table
     *
     * @param string $charset_collate
     * @since 1.0.0
     */
    private function create_appointment_types_table($charset_collate) {
        global $wpdb;

        $table_name = EYE_BOOK_TABLE_APPOINTMENT_TYPES;

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            slug varchar(100) UNIQUE NOT NULL,
            description text,
            duration int(11) NOT NULL DEFAULT 30,
            color varchar(7) DEFAULT '#3498db',
            icon varchar(50) DEFAULT NULL,
            requires_forms text,
            preparation_instructions text,
            price decimal(10,2) DEFAULT NULL,
            specialty enum('optometrist','ophthalmologist','both') DEFAULT 'both',
            is_active tinyint(1) DEFAULT 1,
            sort_order int(11) DEFAULT 0,
            settings longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_slug (slug),
            KEY idx_specialty (specialty),
            KEY idx_active (is_active),
            KEY idx_sort_order (sort_order)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create appointments table
     *
     * @param string $charset_collate
     * @since 1.0.0
     */
    private function create_appointments_table($charset_collate) {
        global $wpdb;

        $table_name = EYE_BOOK_TABLE_APPOINTMENTS;

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            appointment_id varchar(50) UNIQUE NOT NULL,
            patient_id int(11) NOT NULL,
            provider_id int(11) NOT NULL,
            location_id int(11) NOT NULL,
            appointment_type_id int(11) NOT NULL,
            start_datetime datetime NOT NULL,
            end_datetime datetime NOT NULL,
            status enum('scheduled','confirmed','checked_in','in_progress','completed','cancelled','no_show','rescheduled') DEFAULT 'scheduled',
            booking_source enum('online','phone','walk_in','staff') DEFAULT 'online',
            chief_complaint text,
            notes text,
            internal_notes text,
            reminder_sent_at datetime DEFAULT NULL,
            confirmation_sent_at datetime DEFAULT NULL,
            checked_in_at datetime DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            cancelled_at datetime DEFAULT NULL,
            cancellation_reason varchar(255) DEFAULT NULL,
            payment_status enum('pending','paid','partially_paid','refunded') DEFAULT 'pending',
            payment_amount decimal(10,2) DEFAULT NULL,
            insurance_authorized tinyint(1) DEFAULT 0,
            forms_completed tinyint(1) DEFAULT 0,
            created_by int(11) DEFAULT NULL,
            updated_by int(11) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_appointment_id (appointment_id),
            KEY idx_patient_id (patient_id),
            KEY idx_provider_id (provider_id),
            KEY idx_location_id (location_id),
            KEY idx_appointment_type_id (appointment_type_id),
            KEY idx_start_datetime (start_datetime),
            KEY idx_status (status),
            KEY idx_booking_source (booking_source),
            KEY idx_date_range (start_datetime, end_datetime),
            FOREIGN KEY (patient_id) REFERENCES $wpdb->prefix" . "eye_book_patients(id) ON DELETE CASCADE,
            FOREIGN KEY (provider_id) REFERENCES $wpdb->prefix" . "eye_book_providers(id) ON DELETE CASCADE,
            FOREIGN KEY (location_id) REFERENCES $wpdb->prefix" . "eye_book_locations(id) ON DELETE CASCADE,
            FOREIGN KEY (appointment_type_id) REFERENCES $wpdb->prefix" . "eye_book_appointment_types(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create patient forms table
     *
     * @param string $charset_collate
     * @since 1.0.0
     */
    private function create_patient_forms_table($charset_collate) {
        global $wpdb;

        $table_name = EYE_BOOK_TABLE_PATIENT_FORMS;

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            patient_id int(11) NOT NULL,
            appointment_id int(11) DEFAULT NULL,
            form_type varchar(100) NOT NULL,
            form_data longtext NOT NULL,
            completed_at datetime DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text,
            is_encrypted tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_patient_id (patient_id),
            KEY idx_appointment_id (appointment_id),
            KEY idx_form_type (form_type),
            KEY idx_completed_at (completed_at),
            FOREIGN KEY (patient_id) REFERENCES $wpdb->prefix" . "eye_book_patients(id) ON DELETE CASCADE
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create audit log table
     *
     * @param string $charset_collate
     * @since 1.0.0
     */
    private function create_audit_log_table($charset_collate) {
        global $wpdb;

        $table_name = EYE_BOOK_TABLE_AUDIT_LOG;

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            user_id int(11) DEFAULT NULL,
            action varchar(100) NOT NULL,
            object_type varchar(50) NOT NULL,
            object_id int(11) DEFAULT NULL,
            old_values longtext,
            new_values longtext,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text,
            session_id varchar(255) DEFAULT NULL,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_object_type (object_type),
            INDEX idx_object_id (object_id),
            INDEX idx_timestamp (timestamp),
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Create settings table
     *
     * @param string $charset_collate
     * @since 1.0.0
     */
    private function create_settings_table($charset_collate) {
        global $wpdb;

        $table_name = EYE_BOOK_TABLE_SETTINGS;

        $sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            setting_key varchar(255) NOT NULL,
            setting_value longtext,
            location_id int(11) DEFAULT NULL,
            is_encrypted tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY idx_key_location (setting_key, location_id),
            KEY idx_location_id (location_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Insert default data into tables
     *
     * @since 1.0.0
     */
    private function insert_default_data() {
        $this->insert_default_appointment_types();
        $this->insert_default_location();
    }

    /**
     * Insert default appointment types
     *
     * @since 1.0.0
     */
    private function insert_default_appointment_types() {
        global $wpdb;

        $table_name = EYE_BOOK_TABLE_APPOINTMENT_TYPES;

        // Check if data already exists
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }

        $appointment_types = array(
            array(
                'name' => __('Comprehensive Eye Exam', 'eye-book'),
                'slug' => 'comprehensive-eye-exam',
                'description' => __('Complete eye health examination including vision testing and eye disease screening', 'eye-book'),
                'duration' => 60,
                'color' => '#3498db',
                'specialty' => 'both',
                'sort_order' => 1
            ),
            array(
                'name' => __('Routine Eye Exam', 'eye-book'),
                'slug' => 'routine-eye-exam',
                'description' => __('Standard vision examination and prescription update', 'eye-book'),
                'duration' => 30,
                'color' => '#2ecc71',
                'specialty' => 'optometrist',
                'sort_order' => 2
            ),
            array(
                'name' => __('Contact Lens Fitting', 'eye-book'),
                'slug' => 'contact-lens-fitting',
                'description' => __('Contact lens consultation and fitting appointment', 'eye-book'),
                'duration' => 45,
                'color' => '#f39c12',
                'specialty' => 'optometrist',
                'sort_order' => 3
            ),
            array(
                'name' => __('Surgical Consultation', 'eye-book'),
                'slug' => 'surgical-consultation',
                'description' => __('Consultation for eye surgery procedures', 'eye-book'),
                'duration' => 45,
                'color' => '#e74c3c',
                'specialty' => 'ophthalmologist',
                'sort_order' => 4
            ),
            array(
                'name' => __('Follow-up Visit', 'eye-book'),
                'slug' => 'follow-up-visit',
                'description' => __('Post-treatment or post-surgery follow-up examination', 'eye-book'),
                'duration' => 20,
                'color' => '#9b59b6',
                'specialty' => 'both',
                'sort_order' => 5
            ),
            array(
                'name' => __('Emergency Visit', 'eye-book'),
                'slug' => 'emergency-visit',
                'description' => __('Urgent eye care appointment', 'eye-book'),
                'duration' => 30,
                'color' => '#e67e22',
                'specialty' => 'both',
                'sort_order' => 6
            )
        );

        foreach ($appointment_types as $type) {
            $wpdb->insert($table_name, $type);
        }
    }

    /**
     * Insert default location
     *
     * @since 1.0.0
     */
    private function insert_default_location() {
        global $wpdb;

        $table_name = EYE_BOOK_TABLE_LOCATIONS;

        // Check if data already exists
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }

        $default_location = array(
            'name' => __('Main Clinic', 'eye-book'),
            'address_line1' => '123 Main Street',
            'city' => 'New York',
            'state' => 'NY',
            'zip_code' => '10001',
            'phone' => '(555) 123-4567',
            'email' => 'info@eyeclinic.com',
            'timezone' => 'America/New_York',
            'status' => 'active'
        );

        $wpdb->insert($table_name, $default_location);
    }

    /**
     * Drop all plugin tables (used during uninstall)
     *
     * @since 1.0.0
     */
    public function drop_tables() {
        global $wpdb;

        $tables = array(
            EYE_BOOK_TABLE_APPOINTMENTS,
            EYE_BOOK_TABLE_PATIENT_FORMS,
            EYE_BOOK_TABLE_PATIENTS,
            EYE_BOOK_TABLE_PROVIDERS,
            EYE_BOOK_TABLE_APPOINTMENT_TYPES,
            EYE_BOOK_TABLE_LOCATIONS,
            EYE_BOOK_TABLE_AUDIT_LOG,
            EYE_BOOK_TABLE_SETTINGS
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    /**
     * Get database statistics
     *
     * @return array
     * @since 1.0.0
     */
    public function get_stats() {
        global $wpdb;

        $stats = array();

        $tables = array(
            'appointments' => EYE_BOOK_TABLE_APPOINTMENTS,
            'patients' => EYE_BOOK_TABLE_PATIENTS,
            'providers' => EYE_BOOK_TABLE_PROVIDERS,
            'locations' => EYE_BOOK_TABLE_LOCATIONS,
            'appointment_types' => EYE_BOOK_TABLE_APPOINTMENT_TYPES,
            'patient_forms' => EYE_BOOK_TABLE_PATIENT_FORMS,
            'audit_logs' => EYE_BOOK_TABLE_AUDIT_LOG
        );

        foreach ($tables as $key => $table) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table");
            $stats[$key] = intval($count);
        }

        return $stats;
    }
}