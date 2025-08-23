<?php
/**
 * Provider model class for Eye-Book plugin
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
 * Eye_Book_Provider Class
 *
 * Handles provider (doctor/staff) data operations and business logic
 *
 * @class Eye_Book_Provider
 * @since 1.0.0
 */
class Eye_Book_Provider {

    /**
     * Provider ID
     *
     * @var int
     * @since 1.0.0
     */
    public $id;

    /**
     * WordPress user ID
     *
     * @var int
     * @since 1.0.0
     */
    public $wp_user_id;

    /**
     * License number
     *
     * @var string
     * @since 1.0.0
     */
    public $license_number;

    /**
     * Provider specialty
     *
     * @var string
     * @since 1.0.0
     */
    public $specialty;

    /**
     * Provider status
     *
     * @var string
     * @since 1.0.0
     */
    public $status;

    /**
     * Constructor
     *
     * @param int|array $provider Provider ID or data array
     * @since 1.0.0
     */
    public function __construct($provider = null) {
        if (is_numeric($provider)) {
            $this->load($provider);
        } elseif (is_array($provider)) {
            $this->populate($provider);
        }
    }

    /**
     * Load provider by ID
     *
     * @param int $provider_id
     * @return bool Success status
     * @since 1.0.0
     */
    public function load($provider_id) {
        global $wpdb;

        $provider = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM " . EYE_BOOK_TABLE_PROVIDERS . " WHERE id = %d",
            $provider_id
        ), ARRAY_A);

        if ($provider) {
            $this->populate($provider);
            return true;
        }

        return false;
    }

    /**
     * Populate object properties from array
     *
     * @param array $data Provider data
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
     * Get provider display name
     *
     * @return string
     * @since 1.0.0
     */
    public function get_display_name() {
        if ($this->wp_user_id) {
            $user = get_user_by('ID', $this->wp_user_id);
            if ($user) {
                $title = $this->title ?? '';
                $name = $user->display_name ?: ($user->first_name . ' ' . $user->last_name);
                return trim($title . ' ' . $name);
            }
        }
        return __('Unknown Provider', 'eye-book');
    }

    /**
     * Get available time slots for a date
     *
     * @param string $date Date in Y-m-d format
     * @param int $location_id Location ID
     * @return array Available time slots
     * @since 1.0.0
     */
    public function get_available_slots($date, $location_id) {
        // Implementation for getting available slots
        // This would check the provider's schedule template and existing appointments
        return array();
    }

    /**
     * Save provider to database
     *
     * @return bool|int Provider ID on success, false on failure
     * @since 1.0.0
     */
    public function save() {
        global $wpdb;

        $data = $this->to_array();

        if ($this->id) {
            $result = $wpdb->update(
                EYE_BOOK_TABLE_PROVIDERS,
                $data,
                array('id' => $this->id)
            );

            return $result !== false ? $this->id : false;
        } else {
            $result = $wpdb->insert(EYE_BOOK_TABLE_PROVIDERS, $data);
            
            if ($result !== false) {
                $this->id = $wpdb->insert_id;
                return $this->id;
            }
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
            'wp_user_id' => $this->wp_user_id,
            'license_number' => $this->license_number ?? '',
            'specialty' => $this->specialty,
            'subspecialty' => $this->subspecialty ?? '',
            'title' => $this->title ?? '',
            'bio' => $this->bio ?? '',
            'education' => $this->education ?? '',
            'certifications' => $this->certifications ?? '',
            'languages' => $this->languages ?? '',
            'schedule_template' => $this->schedule_template ?? '',
            'location_ids' => $this->location_ids ?? '',
            'hourly_rate' => $this->hourly_rate ?? null,
            'status' => $this->status ?? 'active',
            'settings' => $this->settings ?? ''
        );
    }

    /**
     * Get all providers with filtering
     *
     * @param array $args Query arguments
     * @return array Providers
     * @since 1.0.0
     */
    public static function get_providers($args = array()) {
        global $wpdb;

        $defaults = array(
            'specialty' => null,
            'location_id' => null,
            'status' => 'active',
            'limit' => 50,
            'offset' => 0
        );

        $args = wp_parse_args($args, $defaults);

        $where_clauses = array('1=1');
        $where_values = array();

        if ($args['specialty']) {
            $where_clauses[] = 'specialty = %s';
            $where_values[] = $args['specialty'];
        }

        if ($args['status']) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        $where_clause = implode(' AND ', $where_clauses);

        $query = "SELECT * FROM " . EYE_BOOK_TABLE_PROVIDERS . "
                  WHERE $where_clause
                  ORDER BY id ASC
                  LIMIT %d OFFSET %d";

        $where_values[] = intval($args['limit']);
        $where_values[] = intval($args['offset']);

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        $results = $wpdb->get_results($query, ARRAY_A);
        $providers = array();

        foreach ($results as $result) {
            $providers[] = new self($result);
        }

        return $providers;
    }
}