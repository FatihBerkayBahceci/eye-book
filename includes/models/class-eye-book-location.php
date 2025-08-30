<?php
/**
 * Location model class for Eye-Book plugin
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
 * Eye_Book_Location Class
 *
 * Handles location data operations and business logic
 *
 * @class Eye_Book_Location
 * @since 1.0.0
 */
class Eye_Book_Location {

    /**
     * Location ID
     *
     * @var int
     * @since 1.0.0
     */
    public $id;

    /**
     * Location name
     *
     * @var string
     * @since 1.0.0
     */
    public $name;

    /**
     * Address line 1
     *
     * @var string
     * @since 1.0.0
     */
    public $address_line1;

    /**
     * City
     *
     * @var string
     * @since 1.0.0
     */
    public $city;

    /**
     * State
     *
     * @var string
     * @since 1.0.0
     */
    public $state;

    /**
     * ZIP code
     *
     * @var string
     * @since 1.0.0
     */
    public $zip_code;

    /**
     * Phone number
     *
     * @var string
     * @since 1.0.0
     */
    public $phone;

    /**
     * Email address
     *
     * @var string
     * @since 1.0.0
     */
    public $email;

    /**
     * Timezone
     *
     * @var string
     * @since 1.0.0
     */
    public $timezone;

    /**
     * Status
     *
     * @var string
     * @since 1.0.0
     */
    public $status;

    /**
     * Constructor
     *
     * @param int|array $location Location ID or data array
     * @since 1.0.0
     */
    public function __construct($location = null) {
        if (is_numeric($location)) {
            $this->load($location);
        } elseif (is_array($location)) {
            $this->populate($location);
        }
    }

    /**
     * Load location by ID
     *
     * @param int $location_id
     * @return bool Success status
     * @since 1.0.0
     */
    public function load($location_id) {
        global $wpdb;

        $location = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . EYE_BOOK_TABLE_LOCATIONS . " WHERE id = %d",
            $location_id
        ), ARRAY_A);

        if ($location) {
            $this->populate($location);
            return true;
        }

        return false;
    }

    /**
     * Populate object properties from array
     *
     * @param array $data Location data
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
     * Get formatted address
     *
     * @return string
     * @since 1.0.0
     */
    public function get_formatted_address() {
        $address_parts = array();
        
        if (!empty($this->address_line1)) {
            $address_parts[] = $this->address_line1;
        }
        
        if (!empty($this->address_line2)) {
            $address_parts[] = $this->address_line2;
        }
        
        $city_state_zip = array();
        if (!empty($this->city)) {
            $city_state_zip[] = $this->city;
        }
        
        if (!empty($this->state)) {
            $city_state_zip[] = $this->state;
        }
        
        if (!empty($this->zip_code)) {
            $city_state_zip[] = $this->zip_code;
        }
        
        if (!empty($city_state_zip)) {
            $address_parts[] = implode(', ', $city_state_zip);
        }
        
        return implode("\n", $address_parts);
    }

    /**
     * Save location to database
     *
     * @return bool|int Location ID on success, false on failure
     * @since 1.0.0
     */
    public function save() {
        global $wpdb;

        $data = $this->to_array();

        if ($this->id) {
            $result = $wpdb->update(
                EYE_BOOK_TABLE_LOCATIONS,
                $data,
                array('id' => $this->id)
            );

            return $result !== false ? $this->id : false;
        } else {
            $result = $wpdb->insert(EYE_BOOK_TABLE_LOCATIONS, $data);
            
            if ($result !== false) {
                $this->id = $wpdb->insert_id;
                return $this->id;
            }
        }

        return false;
    }

    /**
     * Create location
     *
     * @param array $data Location data
     * @return bool|int Location ID on success, false on failure
     * @since 1.0.0
     */
    public function create($data = array()) {
        // Set properties from data
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        // Clear the ID to ensure new record
        $this->id = null;
        
        return $this->save();
    }

    /**
     * Update location
     *
     * @param array $data Location data
     * @return bool|int Location ID on success, false on failure
     * @since 1.0.0
     */
    public function update($data = array()) {
        // Set properties from data
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        
        return $this->save();
    }

    /**
     * Get location ID
     *
     * @return int|null Location ID
     * @since 1.0.0
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * Convert object to array
     *
     * @return array
     * @since 1.0.0
     */
    public function to_array() {
        return array(
            'name' => $this->name,
            'address_line1' => $this->address_line1,
            'address_line2' => $this->address_line2 ?? '',
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'phone' => $this->phone,
            'email' => $this->email ?? '',
            'timezone' => $this->timezone ?? 'America/New_York',
            'status' => $this->status ?? 'active',
            'settings' => $this->settings ?? ''
        );
    }

    /**
     * Get all locations with filtering
     *
     * @param array $args Query arguments
     * @return array Locations
     * @since 1.0.0
     */
    public static function get_locations($args = array()) {
        global $wpdb;

        $defaults = array(
            'status' => 'active',
            'limit' => 50,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array('1=1');
        $where_values = array();

        if ($args['status']) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        $where_clause = implode(' AND ', $where_clauses);

        $query = "SELECT * FROM " . EYE_BOOK_TABLE_LOCATIONS . "
                  WHERE $where_clause
                  ORDER BY name ASC
                  LIMIT %d OFFSET %d";

        $where_values[] = intval($args['limit']);
        $where_values[] = intval($args['offset']);

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $results = $wpdb->get_results($query, ARRAY_A);
        $locations = array();

        foreach ($results as $result) {
            $locations[] = new self($result);
        }

        return $locations;
    }
}