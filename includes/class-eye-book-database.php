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
    private $db_version = '1.0.2';

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
            mobile_phone varchar(20) DEFAULT NULL,
            work_phone varchar(20) DEFAULT NULL,
            email varchar(255) DEFAULT NULL,
            social_security_number varchar(11) DEFAULT NULL,
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
            copay_amount decimal(10,2) DEFAULT NULL,
            preferred_language varchar(50) DEFAULT 'en',
            medical_history longtext,
            eye_care_history longtext,
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
            KEY idx_date_range (start_datetime, end_datetime)
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
            KEY idx_completed_at (completed_at)
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
        $this->insert_default_locations();
        $this->insert_default_providers();
        $this->insert_default_patients();
        $this->insert_default_appointments();
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
     * Insert default locations
     *
     * @since 1.0.0
     */
    private function insert_default_locations() {
        global $wpdb;

        $table_name = EYE_BOOK_TABLE_LOCATIONS;

        // Check if data already exists
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }

        $locations = array(
            array(
                'name' => 'Manhattan Eye Center',
                'address_line1' => '123 Fifth Avenue',
                'address_line2' => 'Suite 200',
                'city' => 'New York',
                'state' => 'NY',
                'zip_code' => '10001',
                'phone' => '(212) 555-0101',
                'email' => 'manhattan@eyecare.com',
                'timezone' => 'America/New_York',
                'status' => 'active'
            ),
            array(
                'name' => 'Brooklyn Vision Clinic',
                'address_line1' => '456 Atlantic Avenue',
                'city' => 'Brooklyn',
                'state' => 'NY', 
                'zip_code' => '11217',
                'phone' => '(718) 555-0202',
                'email' => 'brooklyn@eyecare.com',
                'timezone' => 'America/New_York',
                'status' => 'active'
            ),
            array(
                'name' => 'Queens Family Eye Care',
                'address_line1' => '789 Northern Boulevard',
                'city' => 'Queens',
                'state' => 'NY',
                'zip_code' => '11354',
                'phone' => '(718) 555-0303',
                'email' => 'queens@eyecare.com',
                'timezone' => 'America/New_York',
                'status' => 'active'
            ),
            array(
                'name' => 'Westchester Eye Institute',
                'address_line1' => '321 Main Street',
                'city' => 'White Plains',
                'state' => 'NY',
                'zip_code' => '10601',
                'phone' => '(914) 555-0404',
                'email' => 'westchester@eyecare.com',
                'timezone' => 'America/New_York',
                'status' => 'active'
            )
        );

        foreach ($locations as $location) {
            $wpdb->insert($table_name, $location);
        }
    }

    /**
     * Insert default providers
     *
     * @since 1.0.0
     */
    private function insert_default_providers() {
        global $wpdb;

        $table_name = EYE_BOOK_TABLE_PROVIDERS;

        // Check if data already exists
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }

        $providers = array(
            array(
                'provider_id' => 'DR001',
                'first_name' => 'Sarah',
                'last_name' => 'Johnson',
                'credentials' => 'MD, Ophthalmologist',
                'specialty' => 'Ophthalmologist',
                'license_number' => 'NY12345',
                'phone' => '(212) 555-1001',
                'email' => 'dr.johnson@eyecare.com',
                'biography' => 'Dr. Johnson specializes in retinal diseases and has over 15 years of experience.',
                'education' => 'Harvard Medical School, Johns Hopkins Residency',
                'languages' => 'English, Spanish',
                'status' => 'active'
            ),
            array(
                'provider_id' => 'DR002',
                'first_name' => 'Michael',
                'last_name' => 'Chen',
                'credentials' => 'OD, Optometrist',
                'specialty' => 'Optometrist',
                'license_number' => 'NY12346',
                'phone' => '(212) 555-1002',
                'email' => 'dr.chen@eyecare.com',
                'biography' => 'Dr. Chen focuses on comprehensive eye exams and contact lens fittings.',
                'education' => 'SUNY Optometry',
                'languages' => 'English, Mandarin',
                'status' => 'active'
            ),
            array(
                'provider_id' => 'DR003',
                'first_name' => 'Emily',
                'last_name' => 'Rodriguez',
                'credentials' => 'MD, Ophthalmologist',
                'specialty' => 'Ophthalmologist',
                'license_number' => 'NY12347',
                'phone' => '(718) 555-1003',
                'email' => 'dr.rodriguez@eyecare.com',
                'biography' => 'Dr. Rodriguez specializes in cataract and glaucoma surgery.',
                'education' => 'Cornell Medical School, Mount Sinai Residency',
                'languages' => 'English, Spanish, Portuguese',
                'status' => 'active'
            ),
            array(
                'provider_id' => 'DR004',
                'first_name' => 'David',
                'last_name' => 'Kim',
                'credentials' => 'OD, Optometrist',
                'specialty' => 'Optometrist',
                'license_number' => 'NY12348',
                'phone' => '(718) 555-1004',
                'email' => 'dr.kim@eyecare.com',
                'biography' => 'Dr. Kim has expertise in pediatric optometry and low vision rehabilitation.',
                'education' => 'Pennsylvania College of Optometry',
                'languages' => 'English, Korean',
                'status' => 'active'
            ),
            array(
                'provider_id' => 'DR005',
                'first_name' => 'Lisa',
                'last_name' => 'Thompson',
                'credentials' => 'MD, Ophthalmologist',
                'specialty' => 'Ophthalmologist',
                'license_number' => 'NY12349',
                'phone' => '(914) 555-1005',
                'email' => 'dr.thompson@eyecare.com',
                'biography' => 'Dr. Thompson is a corneal specialist with fellowship training in corneal transplantation.',
                'education' => 'Columbia Medical School, Wills Eye Hospital Fellowship',
                'languages' => 'English, French',
                'status' => 'active'
            ),
            array(
                'provider_id' => 'DR006',
                'first_name' => 'James',
                'last_name' => 'Wilson',
                'credentials' => 'OD, Optometrist',
                'specialty' => 'Optometrist',
                'license_number' => 'NY12350',
                'phone' => '(914) 555-1006',
                'email' => 'dr.wilson@eyecare.com',
                'biography' => 'Dr. Wilson specializes in sports vision and contact lens specialty fittings.',
                'education' => 'Illinois College of Optometry',
                'languages' => 'English',
                'status' => 'active'
            )
        );

        foreach ($providers as $provider) {
            $wpdb->insert($table_name, $provider);
        }
    }

    /**
     * Insert default patients
     *
     * @since 1.0.0
     */
    private function insert_default_patients() {
        global $wpdb;

        $table_name = EYE_BOOK_TABLE_PATIENTS;

        // Check if data already exists
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }

        // Generate 60 realistic patients
        $patients = array(
            // First batch - realistic patient data
            array(
                'patient_id' => 'PAT001',
                'first_name' => 'John',
                'last_name' => 'Anderson',
                'date_of_birth' => '1980-03-15',
                'gender' => 'male',
                'phone' => '(212) 555-2001',
                'mobile_phone' => '(917) 555-2001',
                'email' => 'john.anderson@email.com',
                'social_security_number' => '123-45-6789',
                'address_line1' => '123 Park Avenue',
                'city' => 'New York',
                'state' => 'NY',
                'zip_code' => '10016',
                'emergency_contact_name' => 'Jane Anderson',
                'emergency_contact_phone' => '(212) 555-2002',
                'emergency_contact_relationship' => 'Spouse',
                'insurance_provider' => 'Blue Cross Blue Shield',
                'insurance_member_id' => 'BC123456789',
                'copay_amount' => 25.00,
                'medical_history' => 'Hypertension, controlled',
                'eye_care_history' => 'Wears glasses for myopia',
                'allergies' => 'None known',
                'status' => 'active'
            ),
            array(
                'patient_id' => 'PAT002',
                'first_name' => 'Maria',
                'last_name' => 'Garcia',
                'date_of_birth' => '1975-07-22',
                'gender' => 'female',
                'phone' => '(718) 555-2003',
                'mobile_phone' => '(917) 555-2003',
                'email' => 'maria.garcia@email.com',
                'social_security_number' => '234-56-7890',
                'address_line1' => '456 Brooklyn Heights',
                'city' => 'Brooklyn',
                'state' => 'NY',
                'zip_code' => '11201',
                'emergency_contact_name' => 'Carlos Garcia',
                'emergency_contact_phone' => '(718) 555-2004',
                'emergency_contact_relationship' => 'Husband',
                'insurance_provider' => 'Aetna',
                'insurance_member_id' => 'AE987654321',
                'copay_amount' => 30.00,
                'medical_history' => 'Diabetes Type 2',
                'eye_care_history' => 'Recent diabetic eye screening',
                'allergies' => 'Penicillin',
                'status' => 'active'
            ),
            array(
                'patient_id' => 'PAT003',
                'first_name' => 'William',
                'last_name' => 'Johnson',
                'date_of_birth' => '1962-11-08',
                'gender' => 'male',
                'phone' => '(718) 555-2005',
                'mobile_phone' => '(347) 555-2005',
                'email' => 'william.johnson@email.com',
                'social_security_number' => '345-67-8901',
                'address_line1' => '789 Queens Boulevard',
                'city' => 'Queens',
                'state' => 'NY',
                'zip_code' => '11375',
                'emergency_contact_name' => 'Mary Johnson',
                'emergency_contact_phone' => '(718) 555-2006',
                'emergency_contact_relationship' => 'Wife',
                'insurance_provider' => 'Humana',
                'insurance_member_id' => 'HU456123789',
                'copay_amount' => 20.00,
                'medical_history' => 'High cholesterol',
                'eye_care_history' => 'Cataract surgery candidate',
                'allergies' => 'Sulfa drugs',
                'status' => 'active'
            )
        );

        // Generate additional 57 patients with varied realistic data
        $first_names = array('Emma', 'Noah', 'Olivia', 'Liam', 'Ava', 'Isabella', 'Sophia', 'Jackson', 'Mia', 'Lucas', 'Harper', 'Evelyn', 'Alexander', 'Abigail', 'Ethan', 'Emily', 'Jacob', 'Elizabeth', 'Michael', 'Mila', 'Daniel', 'Ella', 'Henry', 'Avery', 'James', 'Sofia', 'Benjamin', 'Camila', 'Logan', 'Aria', 'Matthew', 'Scarlett', 'Lucas', 'Victoria', 'David', 'Madison', 'Joseph', 'Luna', 'Samuel', 'Grace', 'Robert', 'Chloe', 'Christopher', 'Penelope', 'William', 'Layla', 'Anthony', 'Riley', 'Thomas', 'Zoey', 'Charles', 'Nora', 'Andrew', 'Lily', 'Joshua', 'Eleanor', 'Nathan');
        
        $last_names = array('Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez', 'Hernandez', 'Lopez', 'Gonzalez', 'Wilson', 'Anderson', 'Thomas', 'Taylor', 'Moore', 'Jackson', 'Martin', 'Lee', 'Perez', 'Thompson', 'White', 'Harris', 'Sanchez', 'Clark', 'Ramirez', 'Lewis', 'Robinson', 'Walker', 'Young', 'Allen', 'King', 'Wright', 'Scott', 'Torres', 'Nguyen', 'Hill', 'Flores', 'Green', 'Adams', 'Nelson', 'Baker', 'Hall', 'Rivera', 'Campbell', 'Mitchell', 'Carter', 'Roberts');

        $cities = array('New York', 'Brooklyn', 'Queens', 'Bronx', 'Staten Island', 'White Plains', 'Yonkers');
        $zip_codes = array('10001', '10016', '11201', '11375', '10451', '10301', '10601', '10701');
        
        for ($i = 4; $i <= 60; $i++) {
            $first_name = $first_names[array_rand($first_names)];
            $last_name = $last_names[array_rand($last_names)];
            $city = $cities[array_rand($cities)];
            $zip = $zip_codes[array_rand($zip_codes)];
            $birth_year = rand(1950, 2005);
            $birth_month = rand(1, 12);
            $birth_day = rand(1, 28);
            
            $patients[] = array(
                'patient_id' => sprintf('PAT%03d', $i),
                'first_name' => $first_name,
                'last_name' => $last_name,
                'date_of_birth' => sprintf('%04d-%02d-%02d', $birth_year, $birth_month, $birth_day),
                'gender' => rand(0, 1) ? 'male' : 'female',
                'phone' => sprintf('(%s) 555-%04d', rand(0, 1) ? '212' : '718', rand(2000, 9999)),
                'mobile_phone' => sprintf('(%s) 555-%04d', rand(0, 1) ? '917' : '347', rand(2000, 9999)),
                'email' => strtolower($first_name . '.' . $last_name . '@email.com'),
                'social_security_number' => sprintf('%03d-%02d-%04d', rand(100, 999), rand(10, 99), rand(1000, 9999)),
                'address_line1' => rand(100, 999) . ' ' . $last_names[array_rand($last_names)] . ' ' . (rand(0, 1) ? 'Street' : 'Avenue'),
                'city' => $city,
                'state' => 'NY',
                'zip_code' => $zip,
                'emergency_contact_name' => $first_names[array_rand($first_names)] . ' ' . $last_name,
                'emergency_contact_phone' => sprintf('(%s) 555-%04d', rand(0, 1) ? '212' : '718', rand(2000, 9999)),
                'emergency_contact_relationship' => array('Spouse', 'Parent', 'Child', 'Sibling', 'Friend')[array_rand(array('Spouse', 'Parent', 'Child', 'Sibling', 'Friend'))],
                'insurance_provider' => array('Blue Cross Blue Shield', 'Aetna', 'Humana', 'UnitedHealth', 'Cigna')[array_rand(array('Blue Cross Blue Shield', 'Aetna', 'Humana', 'UnitedHealth', 'Cigna'))],
                'insurance_member_id' => strtoupper(substr(md5(uniqid()), 0, 10)),
                'copay_amount' => array(15.00, 20.00, 25.00, 30.00, 35.00)[array_rand(array(15.00, 20.00, 25.00, 30.00, 35.00))],
                'medical_history' => array('None', 'Hypertension', 'Diabetes', 'High cholesterol', 'Allergies')[array_rand(array('None', 'Hypertension', 'Diabetes', 'High cholesterol', 'Allergies'))],
                'eye_care_history' => array('First visit', 'Regular checkups', 'Previous surgery', 'Wears contacts', 'Wears glasses')[array_rand(array('First visit', 'Regular checkups', 'Previous surgery', 'Wears contacts', 'Wears glasses'))],
                'allergies' => array('None known', 'Penicillin', 'Sulfa drugs', 'Latex', 'Environmental')[array_rand(array('None known', 'Penicillin', 'Sulfa drugs', 'Latex', 'Environmental'))],
                'status' => 'active'
            );
        }

        foreach ($patients as $patient) {
            $wpdb->insert($table_name, $patient);
        }
    }

    /**
     * Insert default appointments
     *
     * @since 1.0.0
     */
    private function insert_default_appointments() {
        global $wpdb;

        $table_name = EYE_BOOK_TABLE_APPOINTMENTS;

        // Check if data already exists
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($count > 0) {
            return;
        }

        // Get IDs from related tables
        $patient_ids = $wpdb->get_col("SELECT id FROM " . EYE_BOOK_TABLE_PATIENTS . " LIMIT 60");
        $provider_ids = $wpdb->get_col("SELECT id FROM " . EYE_BOOK_TABLE_PROVIDERS);
        $location_ids = $wpdb->get_col("SELECT id FROM " . EYE_BOOK_TABLE_LOCATIONS);
        $appointment_type_ids = $wpdb->get_col("SELECT id FROM " . EYE_BOOK_TABLE_APPOINTMENT_TYPES);

        if (empty($patient_ids) || empty($provider_ids) || empty($location_ids) || empty($appointment_type_ids)) {
            return; // Dependencies not met
        }

        $appointments = array();
        $statuses = array('scheduled', 'completed', 'cancelled', 'no_show');
        
        // Generate 100+ appointments across different dates
        for ($i = 0; $i < 120; $i++) {
            // Generate dates from 3 months ago to 2 months future
            $days_offset = rand(-90, 60);
            $hour = rand(8, 17); // Business hours 8 AM to 5 PM
            $minute = array(0, 15, 30, 45)[array_rand(array(0, 15, 30, 45))]; // 15-minute intervals
            
            $start_datetime = date('Y-m-d H:i:s', strtotime("$days_offset days $hour:$minute"));
            $end_datetime = date('Y-m-d H:i:s', strtotime("$days_offset days " . ($hour + 1) . ":$minute")); // 1 hour appointment
            
            // Past appointments are more likely to be completed
            if ($days_offset < 0) {
                $status = array('completed', 'completed', 'completed', 'cancelled', 'no_show')[array_rand(array('completed', 'completed', 'completed', 'cancelled', 'no_show'))];
            } else {
                $status = array('scheduled', 'scheduled', 'scheduled', 'cancelled')[array_rand(array('scheduled', 'scheduled', 'scheduled', 'cancelled'))];
            }
            
            $appointments[] = array(
                'appointment_id' => 'APT' . date('Ymd') . sprintf('%04d', $i + 1),
                'patient_id' => $patient_ids[array_rand($patient_ids)],
                'provider_id' => $provider_ids[array_rand($provider_ids)],
                'location_id' => $location_ids[array_rand($location_ids)],
                'appointment_type_id' => $appointment_type_ids[array_rand($appointment_type_ids)],
                'start_datetime' => $start_datetime,
                'end_datetime' => $end_datetime,
                'status' => $status,
                'booking_source' => array('online', 'phone', 'walk_in', 'referral')[array_rand(array('online', 'phone', 'walk_in', 'referral'))],
                'chief_complaint' => array('Routine exam', 'Eye pain', 'Blurred vision', 'Follow-up', 'New glasses', 'Contact lens check')[array_rand(array('Routine exam', 'Eye pain', 'Blurred vision', 'Follow-up', 'New glasses', 'Contact lens check'))],
                'notes' => $status === 'completed' ? 'Exam completed successfully. Follow-up recommended in 6 months.' : '',
                'patient_notes' => 'Patient requests morning appointments when possible.'
            );
        }

        foreach ($appointments as $appointment) {
            $wpdb->insert($table_name, $appointment);
        }
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