<?php
/**
 * Test factory for creating Eye-Book test data
 *
 * @package EyeBook
 * @subpackage Tests
 * @since 1.0.0
 */

/**
 * Eye_Book_Test_Factory Class
 *
 * Factory for creating test data objects
 *
 * @class Eye_Book_Test_Factory
 * @since 1.0.0
 */
class Eye_Book_Test_Factory {

    /**
     * Appointment factory
     *
     * @var Eye_Book_Appointment_Factory
     * @since 1.0.0
     */
    public $appointment;

    /**
     * Patient factory
     *
     * @var Eye_Book_Patient_Factory
     * @since 1.0.0
     */
    public $patient;

    /**
     * Provider factory
     *
     * @var Eye_Book_Provider_Factory
     * @since 1.0.0
     */
    public $provider;

    /**
     * Location factory
     *
     * @var Eye_Book_Location_Factory
     * @since 1.0.0
     */
    public $location;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->appointment = new Eye_Book_Appointment_Factory();
        $this->patient = new Eye_Book_Patient_Factory();
        $this->provider = new Eye_Book_Provider_Factory();
        $this->location = new Eye_Book_Location_Factory();
    }
}

/**
 * Base factory class
 *
 * @class Eye_Book_Base_Factory
 * @since 1.0.0
 */
abstract class Eye_Book_Base_Factory {

    /**
     * Default values for object creation
     *
     * @var array
     * @since 1.0.0
     */
    protected $default_values = array();

    /**
     * Create object with given arguments
     *
     * @param array $args Arguments
     * @return int Object ID
     * @since 1.0.0
     */
    abstract public function create($args = array());

    /**
     * Merge arguments with defaults
     *
     * @param array $args Arguments
     * @return array Merged arguments
     * @since 1.0.0
     */
    protected function merge_with_defaults($args) {
        return wp_parse_args($args, $this->default_values);
    }

    /**
     * Generate fake data
     *
     * @param string $type Data type
     * @return string Generated data
     * @since 1.0.0
     */
    protected function fake($type) {
        switch ($type) {
            case 'first_name':
                $names = array('John', 'Jane', 'Michael', 'Sarah', 'David', 'Emily', 'James', 'Jessica');
                return $names[array_rand($names)];
                
            case 'last_name':
                $names = array('Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis');
                return $names[array_rand($names)];
                
            case 'email':
                return 'test' . wp_rand(1000, 9999) . '@example.com';
                
            case 'phone':
                return sprintf('(%03d) %03d-%04d', wp_rand(200, 999), wp_rand(200, 999), wp_rand(1000, 9999));
                
            case 'address':
                $streets = array('Main St', 'Oak Ave', 'First St', 'Second St', 'Park Ave', 'Cedar Ln');
                return wp_rand(100, 9999) . ' ' . $streets[array_rand($streets)];
                
            case 'city':
                $cities = array('Springfield', 'Franklin', 'Georgetown', 'Clinton', 'Greenville', 'Madison');
                return $cities[array_rand($cities)];
                
            case 'state':
                $states = array('CA', 'NY', 'TX', 'FL', 'IL', 'PA', 'OH', 'GA', 'NC', 'MI');
                return $states[array_rand($states)];
                
            case 'zip':
                return sprintf('%05d', wp_rand(10000, 99999));
                
            case 'ssn':
                return sprintf('%03d-%02d-%04d', wp_rand(100, 999), wp_rand(10, 99), wp_rand(1000, 9999));
                
            case 'date_of_birth':
                $start_date = strtotime('-80 years');
                $end_date = strtotime('-18 years');
                $random_timestamp = mt_rand($start_date, $end_date);
                return date('Y-m-d', $random_timestamp);
                
            case 'future_date':
                $start_date = time();
                $end_date = strtotime('+6 months');
                $random_timestamp = mt_rand($start_date, $end_date);
                return date('Y-m-d', $random_timestamp);
                
            case 'future_datetime':
                $start_date = time();
                $end_date = strtotime('+6 months');
                $random_timestamp = mt_rand($start_date, $end_date);
                // Round to nearest 15 minutes for appointments
                $random_timestamp = round($random_timestamp / 900) * 900;
                return date('Y-m-d H:i:s', $random_timestamp);
                
            case 'appointment_time':
                $hours = array('09', '10', '11', '13', '14', '15', '16');
                $minutes = array('00', '15', '30', '45');
                return $hours[array_rand($hours)] . ':' . $minutes[array_rand($minutes)] . ':00';
                
            default:
                return 'test_' . wp_rand(1000, 9999);
        }
    }
}

/**
 * Appointment factory
 *
 * @class Eye_Book_Appointment_Factory
 * @extends Eye_Book_Base_Factory
 * @since 1.0.0
 */
class Eye_Book_Appointment_Factory extends Eye_Book_Base_Factory {

    /**
     * Default values
     *
     * @var array
     * @since 1.0.0
     */
    protected $default_values = array(
        'patient_id' => null,
        'provider_id' => null,
        'location_id' => null,
        'appointment_type_id' => 1,
        'start_datetime' => null,
        'end_datetime' => null,
        'status' => 'scheduled',
        'chief_complaint' => 'Annual eye exam',
        'notes' => '',
        'booking_source' => 'test'
    );

    /**
     * Create appointment
     *
     * @param array $args Arguments
     * @return int Appointment ID
     * @since 1.0.0
     */
    public function create($args = array()) {
        global $wpdb;
        
        $args = $this->merge_with_defaults($args);
        
        // Auto-create dependencies if not provided
        if (empty($args['patient_id'])) {
            $patient_factory = new Eye_Book_Patient_Factory();
            $args['patient_id'] = $patient_factory->create();
        }
        
        if (empty($args['provider_id'])) {
            $provider_factory = new Eye_Book_Provider_Factory();
            $args['provider_id'] = $provider_factory->create();
        }
        
        if (empty($args['location_id'])) {
            $location_factory = new Eye_Book_Location_Factory();
            $args['location_id'] = $location_factory->create();
        }
        
        if (empty($args['start_datetime'])) {
            $args['start_datetime'] = $this->fake('future_datetime');
        }
        
        if (empty($args['end_datetime'])) {
            $start_time = strtotime($args['start_datetime']);
            $end_time = $start_time + (30 * 60); // 30 minutes default
            $args['end_datetime'] = date('Y-m-d H:i:s', $end_time);
        }
        
        // Generate appointment ID
        $args['appointment_id'] = 'APT-' . date('Y') . '-' . wp_rand(10000, 99999);
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'eye_book_appointments',
            $args,
            array('%d', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : 0;
    }
}

/**
 * Patient factory
 *
 * @class Eye_Book_Patient_Factory
 * @extends Eye_Book_Base_Factory
 * @since 1.0.0
 */
class Eye_Book_Patient_Factory extends Eye_Book_Base_Factory {

    /**
     * Default values
     *
     * @var array
     * @since 1.0.0
     */
    protected $default_values = array(
        'first_name' => null,
        'last_name' => null,
        'email' => null,
        'phone' => null,
        'date_of_birth' => null,
        'gender' => 'other',
        'address' => null,
        'city' => null,
        'state' => null,
        'zip_code' => null,
        'emergency_contact_name' => null,
        'emergency_contact_phone' => null,
        'insurance_provider' => 'Self Pay',
        'insurance_id' => '',
        'status' => 'active'
    );

    /**
     * Create patient
     *
     * @param array $args Arguments
     * @return int Patient ID
     * @since 1.0.0
     */
    public function create($args = array()) {
        global $wpdb;
        
        $args = $this->merge_with_defaults($args);
        
        // Generate fake data for null values
        if (empty($args['first_name'])) {
            $args['first_name'] = $this->fake('first_name');
        }
        
        if (empty($args['last_name'])) {
            $args['last_name'] = $this->fake('last_name');
        }
        
        if (empty($args['email'])) {
            $args['email'] = $this->fake('email');
        }
        
        if (empty($args['phone'])) {
            $args['phone'] = $this->fake('phone');
        }
        
        if (empty($args['date_of_birth'])) {
            $args['date_of_birth'] = $this->fake('date_of_birth');
        }
        
        if (empty($args['address'])) {
            $args['address'] = $this->fake('address');
        }
        
        if (empty($args['city'])) {
            $args['city'] = $this->fake('city');
        }
        
        if (empty($args['state'])) {
            $args['state'] = $this->fake('state');
        }
        
        if (empty($args['zip_code'])) {
            $args['zip_code'] = $this->fake('zip');
        }
        
        if (empty($args['emergency_contact_name'])) {
            $args['emergency_contact_name'] = $this->fake('first_name') . ' ' . $this->fake('last_name');
        }
        
        if (empty($args['emergency_contact_phone'])) {
            $args['emergency_contact_phone'] = $this->fake('phone');
        }
        
        // Generate patient ID
        $args['patient_id'] = 'PT-' . date('Y') . '-' . wp_rand(10000, 99999);
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'eye_book_patients',
            $args,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : 0;
    }
}

/**
 * Provider factory
 *
 * @class Eye_Book_Provider_Factory
 * @extends Eye_Book_Base_Factory
 * @since 1.0.0
 */
class Eye_Book_Provider_Factory extends Eye_Book_Base_Factory {

    /**
     * Default values
     *
     * @var array
     * @since 1.0.0
     */
    protected $default_values = array(
        'first_name' => null,
        'last_name' => null,
        'email' => null,
        'phone' => null,
        'specialization' => 'General Ophthalmology',
        'license_number' => null,
        'npi_number' => null,
        'status' => 'active',
        'bio' => 'Experienced eye care professional',
        'credentials' => 'MD',
        'years_experience' => 10
    );

    /**
     * Create provider
     *
     * @param array $args Arguments
     * @return int Provider ID
     * @since 1.0.0
     */
    public function create($args = array()) {
        global $wpdb;
        
        $args = $this->merge_with_defaults($args);
        
        // Generate fake data for null values
        if (empty($args['first_name'])) {
            $args['first_name'] = 'Dr. ' . $this->fake('first_name');
        }
        
        if (empty($args['last_name'])) {
            $args['last_name'] = $this->fake('last_name');
        }
        
        if (empty($args['email'])) {
            $args['email'] = $this->fake('email');
        }
        
        if (empty($args['phone'])) {
            $args['phone'] = $this->fake('phone');
        }
        
        if (empty($args['license_number'])) {
            $args['license_number'] = 'LIC-' . wp_rand(100000, 999999);
        }
        
        if (empty($args['npi_number'])) {
            $args['npi_number'] = wp_rand(1000000000, 9999999999);
        }
        
        $specializations = array(
            'General Ophthalmology',
            'Retinal Diseases',
            'Corneal Diseases',
            'Glaucoma',
            'Pediatric Ophthalmology',
            'Oculoplastic Surgery',
            'Neuro-Ophthalmology'
        );
        
        if ($args['specialization'] === 'General Ophthalmology') {
            $args['specialization'] = $specializations[array_rand($specializations)];
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'eye_book_providers',
            $args,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );
        
        return $result ? $wpdb->insert_id : 0;
    }
}

/**
 * Location factory
 *
 * @class Eye_Book_Location_Factory
 * @extends Eye_Book_Base_Factory
 * @since 1.0.0
 */
class Eye_Book_Location_Factory extends Eye_Book_Base_Factory {

    /**
     * Default values
     *
     * @var array
     * @since 1.0.0
     */
    protected $default_values = array(
        'name' => null,
        'address' => null,
        'city' => null,
        'state' => null,
        'zip_code' => null,
        'phone' => null,
        'email' => null,
        'operating_hours' => '{"monday":{"open":"09:00","close":"17:00","closed":false},"tuesday":{"open":"09:00","close":"17:00","closed":false},"wednesday":{"open":"09:00","close":"17:00","closed":false},"thursday":{"open":"09:00","close":"17:00","closed":false},"friday":{"open":"09:00","close":"17:00","closed":false},"saturday":{"open":"09:00","close":"13:00","closed":false},"sunday":{"open":"","close":"","closed":true}}',
        'status' => 'active',
        'timezone' => 'America/New_York'
    );

    /**
     * Create location
     *
     * @param array $args Arguments
     * @return int Location ID
     * @since 1.0.0
     */
    public function create($args = array()) {
        global $wpdb;
        
        $args = $this->merge_with_defaults($args);
        
        // Generate fake data for null values
        if (empty($args['name'])) {
            $args['name'] = $this->fake('city') . ' Eye Care Center';
        }
        
        if (empty($args['address'])) {
            $args['address'] = $this->fake('address');
        }
        
        if (empty($args['city'])) {
            $args['city'] = $this->fake('city');
        }
        
        if (empty($args['state'])) {
            $args['state'] = $this->fake('state');
        }
        
        if (empty($args['zip_code'])) {
            $args['zip_code'] = $this->fake('zip');
        }
        
        if (empty($args['phone'])) {
            $args['phone'] = $this->fake('phone');
        }
        
        if (empty($args['email'])) {
            $args['email'] = $this->fake('email');
        }
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'eye_book_locations',
            $args,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : 0;
    }
}