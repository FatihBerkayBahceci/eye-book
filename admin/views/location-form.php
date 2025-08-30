<?php
/**
 * Location Form - Add/Edit Location
 *
 * @package EyeBook
 * @subpackage Admin/Views
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$action = $_GET['action'] ?? 'add';
$location_id = intval($_GET['id'] ?? 0);
$location = null;

if ($action === 'edit' && $location_id > 0) {
    $location = new Eye_Book_Location($location_id);
    if (!$location->id) {
        wp_die(__('Location not found', 'eye-book'));
    }
}

$page_title = ($action === 'edit') ? __('Edit Location', 'eye-book') : __('Add New Location', 'eye-book');

// Handle form submission
if ($_POST && isset($_POST['eye_book_location_nonce']) && wp_verify_nonce($_POST['eye_book_location_nonce'], 'eye_book_location_action')) {
    $location_data = array(
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'location_code' => sanitize_text_field($_POST['location_code'] ?? ''),
        'location_type' => sanitize_text_field($_POST['location_type'] ?? ''),
        'description' => sanitize_textarea_field($_POST['description'] ?? ''),
        'address_line1' => sanitize_text_field($_POST['address_line1'] ?? ''),
        'address_line2' => sanitize_text_field($_POST['address_line2'] ?? ''),
        'city' => sanitize_text_field($_POST['city'] ?? ''),
        'state' => sanitize_text_field($_POST['state'] ?? ''),
        'zip_code' => sanitize_text_field($_POST['zip_code'] ?? ''),
        'phone' => sanitize_text_field($_POST['phone'] ?? ''),
        'fax' => sanitize_text_field($_POST['fax'] ?? ''),
        'email' => sanitize_email($_POST['email'] ?? ''),
        'website' => sanitize_url($_POST['website'] ?? ''),
        'timezone' => sanitize_text_field($_POST['timezone'] ?? 'America/New_York'),
        'booking_enabled' => intval($_POST['booking_enabled'] ?? 0),
        'advance_booking_days' => intval($_POST['advance_booking_days'] ?? 30),
        'minimum_notice_hours' => intval($_POST['minimum_notice_hours'] ?? 2),
        'examination_rooms' => intval($_POST['examination_rooms'] ?? 1),
        'parking_available' => intval($_POST['parking_available'] ?? 0),
        'wheelchair_accessible' => intval($_POST['wheelchair_accessible'] ?? 0),
        'public_transportation' => sanitize_textarea_field($_POST['public_transportation'] ?? ''),
        'insurance_accepted' => sanitize_textarea_field($_POST['insurance_accepted'] ?? ''),
        'services_offered' => sanitize_textarea_field($_POST['services_offered'] ?? ''),
        'status' => sanitize_text_field($_POST['status'] ?? 'active'),
        'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
    );

    // Validate required fields
    $errors = array();
    if (empty($location_data['name'])) {
        $errors[] = __('Location name is required', 'eye-book');
    }
    if (empty($location_data['address_line1'])) {
        $errors[] = __('Address is required', 'eye-book');
    }
    if (empty($location_data['city'])) {
        $errors[] = __('City is required', 'eye-book');
    }

    if (empty($errors)) {
        try {
            if ($action === 'edit' && $location) {
                $result = $location->update($location_data);
                if ($result) {
                    wp_redirect(add_query_arg(array(
                        'page' => 'eye-book-locations',
                        'message' => 'updated'
                    ), admin_url('admin.php')));
                    exit;
                }
            } else {
                $new_location = new Eye_Book_Location();
                $result = $new_location->create($location_data);
                if ($result) {
                    wp_redirect(add_query_arg(array(
                        'page' => 'eye-book-locations',
                        'message' => 'created'
                    ), admin_url('admin.php')));
                    exit;
                }
            }
            $errors[] = __('Failed to save location. Please try again.', 'eye-book');
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// Get field values (either from POST data or existing location)
$field_values = array();
if ($_POST) {
    foreach ($_POST as $key => $value) {
        $field_values[$key] = $value;
    }
} elseif ($location) {
    $field_values = array(
        'name' => $location->name ?? '',
        'location_code' => $location->location_code ?? '',
        'location_type' => $location->location_type ?? '',
        'description' => $location->description ?? '',
        'address_line1' => $location->address_line1 ?? '',
        'address_line2' => $location->address_line2 ?? '',
        'city' => $location->city ?? '',
        'state' => $location->state ?? '',
        'zip_code' => $location->zip_code ?? '',
        'phone' => $location->phone ?? '',
        'fax' => $location->fax ?? '',
        'email' => $location->email ?? '',
        'website' => $location->website ?? '',
        'timezone' => $location->timezone ?? 'America/New_York',
        'booking_enabled' => $location->booking_enabled ?? 1,
        'advance_booking_days' => $location->advance_booking_days ?? 30,
        'minimum_notice_hours' => $location->minimum_notice_hours ?? 2,
        'examination_rooms' => $location->examination_rooms ?? 1,
        'parking_available' => $location->parking_available ?? 0,
        'wheelchair_accessible' => $location->wheelchair_accessible ?? 0,
        'public_transportation' => $location->public_transportation ?? '',
        'insurance_accepted' => $location->insurance_accepted ?? '',
        'services_offered' => $location->services_offered ?? '',
        'status' => $location->status ?? 'active',
        'notes' => $location->notes ?? ''
    );
}

// Common US states for dropdown
$states = array(
    'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California',
    'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'FL' => 'Florida', 'GA' => 'Georgia',
    'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
    'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
    'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri',
    'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey',
    'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
    'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
    'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont',
    'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming'
);

// Timezone options
$timezones = array(
    'America/New_York' => 'Eastern Time',
    'America/Chicago' => 'Central Time',
    'America/Denver' => 'Mountain Time',
    'America/Los_Angeles' => 'Pacific Time',
    'America/Phoenix' => 'Arizona Time',
    'America/Anchorage' => 'Alaska Time',
    'Pacific/Honolulu' => 'Hawaii Time'
);
?>

<div class="wrap eye-book-wrap">
    <h1><?php echo esc_html($page_title); ?>
        <a href="<?php echo admin_url('admin.php?page=eye-book-locations'); ?>" class="page-title-action"><?php _e('Back to Locations', 'eye-book'); ?></a>
    </h1>

    <?php if (!empty($errors)): ?>
        <div class="notice notice-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo esc_html($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="eye-book-location-form">
        <form method="post" action="">
            <?php wp_nonce_field('eye_book_location_action', 'eye_book_location_nonce'); ?>
            
            <div class="eye-book-form-container">
                <!-- Tab Navigation -->
                <div class="nav-tab-wrapper">
                    <a href="#basic-info" class="nav-tab nav-tab-active"><?php _e('Basic Information', 'eye-book'); ?></a>
                    <a href="#address-info" class="nav-tab"><?php _e('Address & Contact', 'eye-book'); ?></a>
                    <a href="#booking-settings" class="nav-tab"><?php _e('Booking Settings', 'eye-book'); ?></a>
                    <a href="#facility-info" class="nav-tab"><?php _e('Facility Details', 'eye-book'); ?></a>
                </div>

                <!-- Basic Information Tab -->
                <div id="basic-info" class="tab-content active">
                    <h3><?php _e('Location Details', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="name"><?php _e('Location Name', 'eye-book'); ?> <span class="required">*</span></label></th>
                            <td><input type="text" id="name" name="name" value="<?php echo esc_attr($field_values['name'] ?? ''); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="location_code"><?php _e('Location Code', 'eye-book'); ?></label></th>
                            <td>
                                <input type="text" id="location_code" name="location_code" value="<?php echo esc_attr($field_values['location_code'] ?? ''); ?>" class="regular-text" maxlength="10">
                                <p class="description"><?php _e('Short code for internal use (e.g., MAIN, NORTH, SOUTH)', 'eye-book'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="location_type"><?php _e('Location Type', 'eye-book'); ?></label></th>
                            <td>
                                <select id="location_type" name="location_type" class="regular-text">
                                    <option value="clinic" <?php selected($field_values['location_type'] ?? 'clinic', 'clinic'); ?>><?php _e('Clinic', 'eye-book'); ?></option>
                                    <option value="hospital" <?php selected($field_values['location_type'] ?? '', 'hospital'); ?>><?php _e('Hospital', 'eye-book'); ?></option>
                                    <option value="surgery_center" <?php selected($field_values['location_type'] ?? '', 'surgery_center'); ?>><?php _e('Surgery Center', 'eye-book'); ?></option>
                                    <option value="mobile_unit" <?php selected($field_values['location_type'] ?? '', 'mobile_unit'); ?>><?php _e('Mobile Unit', 'eye-book'); ?></option>
                                    <option value="home_visits" <?php selected($field_values['location_type'] ?? '', 'home_visits'); ?>><?php _e('Home Visits', 'eye-book'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="description"><?php _e('Description', 'eye-book'); ?></label></th>
                            <td><textarea id="description" name="description" class="large-text" rows="3"><?php echo esc_textarea($field_values['description'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="timezone"><?php _e('Timezone', 'eye-book'); ?></label></th>
                            <td>
                                <select id="timezone" name="timezone" class="regular-text">
                                    <?php foreach ($timezones as $tz => $label): ?>
                                        <option value="<?php echo esc_attr($tz); ?>" <?php selected($field_values['timezone'] ?? 'America/New_York', $tz); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="status"><?php _e('Status', 'eye-book'); ?></label></th>
                            <td>
                                <select id="status" name="status" class="regular-text">
                                    <option value="active" <?php selected($field_values['status'] ?? 'active', 'active'); ?>><?php _e('Active', 'eye-book'); ?></option>
                                    <option value="inactive" <?php selected($field_values['status'] ?? '', 'inactive'); ?>><?php _e('Inactive', 'eye-book'); ?></option>
                                    <option value="maintenance" <?php selected($field_values['status'] ?? '', 'maintenance'); ?>><?php _e('Under Maintenance', 'eye-book'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Address & Contact Information Tab -->
                <div id="address-info" class="tab-content">
                    <h3><?php _e('Address & Contact Information', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="address_line1"><?php _e('Address Line 1', 'eye-book'); ?> <span class="required">*</span></label></th>
                            <td><input type="text" id="address_line1" name="address_line1" value="<?php echo esc_attr($field_values['address_line1'] ?? ''); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="address_line2"><?php _e('Address Line 2', 'eye-book'); ?></label></th>
                            <td><input type="text" id="address_line2" name="address_line2" value="<?php echo esc_attr($field_values['address_line2'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="city"><?php _e('City', 'eye-book'); ?> <span class="required">*</span></label></th>
                            <td><input type="text" id="city" name="city" value="<?php echo esc_attr($field_values['city'] ?? ''); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="state"><?php _e('State', 'eye-book'); ?></label></th>
                            <td>
                                <select id="state" name="state" class="regular-text">
                                    <option value=""><?php _e('Select State', 'eye-book'); ?></option>
                                    <?php foreach ($states as $code => $name): ?>
                                        <option value="<?php echo esc_attr($code); ?>" <?php selected($field_values['state'] ?? '', $code); ?>>
                                            <?php echo esc_html($name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="zip_code"><?php _e('ZIP Code', 'eye-book'); ?></label></th>
                            <td><input type="text" id="zip_code" name="zip_code" value="<?php echo esc_attr($field_values['zip_code'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="phone"><?php _e('Phone Number', 'eye-book'); ?></label></th>
                            <td><input type="tel" id="phone" name="phone" value="<?php echo esc_attr($field_values['phone'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="fax"><?php _e('Fax Number', 'eye-book'); ?></label></th>
                            <td><input type="tel" id="fax" name="fax" value="<?php echo esc_attr($field_values['fax'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="email"><?php _e('Email', 'eye-book'); ?></label></th>
                            <td><input type="email" id="email" name="email" value="<?php echo esc_attr($field_values['email'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="website"><?php _e('Website', 'eye-book'); ?></label></th>
                            <td><input type="url" id="website" name="website" value="<?php echo esc_attr($field_values['website'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                    </table>
                </div>

                <!-- Booking Settings Tab -->
                <div id="booking-settings" class="tab-content">
                    <h3><?php _e('Appointment Booking Settings', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="booking_enabled"><?php _e('Online Booking Enabled', 'eye-book'); ?></label></th>
                            <td>
                                <input type="checkbox" id="booking_enabled" name="booking_enabled" value="1" <?php checked($field_values['booking_enabled'] ?? 1, 1); ?>>
                                <label for="booking_enabled"><?php _e('Allow patients to book appointments online at this location', 'eye-book'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="advance_booking_days"><?php _e('Advance Booking Days', 'eye-book'); ?></label></th>
                            <td>
                                <input type="number" id="advance_booking_days" name="advance_booking_days" value="<?php echo esc_attr($field_values['advance_booking_days'] ?? 30); ?>" class="regular-text" min="1" max="365">
                                <p class="description"><?php _e('How many days in advance can patients book appointments', 'eye-book'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="minimum_notice_hours"><?php _e('Minimum Notice Hours', 'eye-book'); ?></label></th>
                            <td>
                                <input type="number" id="minimum_notice_hours" name="minimum_notice_hours" value="<?php echo esc_attr($field_values['minimum_notice_hours'] ?? 2); ?>" class="regular-text" min="0" max="168">
                                <p class="description"><?php _e('Minimum hours notice required before appointment time', 'eye-book'); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Facility Details Tab -->
                <div id="facility-info" class="tab-content">
                    <h3><?php _e('Facility Information', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="examination_rooms"><?php _e('Number of Examination Rooms', 'eye-book'); ?></label></th>
                            <td><input type="number" id="examination_rooms" name="examination_rooms" value="<?php echo esc_attr($field_values['examination_rooms'] ?? 1); ?>" class="regular-text" min="1" max="50"></td>
                        </tr>
                        <tr>
                            <th><label for="parking_available"><?php _e('Parking Available', 'eye-book'); ?></label></th>
                            <td>
                                <input type="checkbox" id="parking_available" name="parking_available" value="1" <?php checked($field_values['parking_available'] ?? 0, 1); ?>>
                                <label for="parking_available"><?php _e('Parking is available at this location', 'eye-book'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="wheelchair_accessible"><?php _e('Wheelchair Accessible', 'eye-book'); ?></label></th>
                            <td>
                                <input type="checkbox" id="wheelchair_accessible" name="wheelchair_accessible" value="1" <?php checked($field_values['wheelchair_accessible'] ?? 0, 1); ?>>
                                <label for="wheelchair_accessible"><?php _e('This location is wheelchair accessible', 'eye-book'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="public_transportation"><?php _e('Public Transportation', 'eye-book'); ?></label></th>
                            <td><textarea id="public_transportation" name="public_transportation" class="large-text" rows="3"><?php echo esc_textarea($field_values['public_transportation'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="insurance_accepted"><?php _e('Insurance Accepted', 'eye-book'); ?></label></th>
                            <td><textarea id="insurance_accepted" name="insurance_accepted" class="large-text" rows="4"><?php echo esc_textarea($field_values['insurance_accepted'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="services_offered"><?php _e('Services Offered', 'eye-book'); ?></label></th>
                            <td><textarea id="services_offered" name="services_offered" class="large-text" rows="4"><?php echo esc_textarea($field_values['services_offered'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="notes"><?php _e('Internal Notes', 'eye-book'); ?></label></th>
                            <td><textarea id="notes" name="notes" class="large-text" rows="4"><?php echo esc_textarea($field_values['notes'] ?? ''); ?></textarea></td>
                        </tr>
                    </table>
                </div>
            </div>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $action === 'edit' ? __('Update Location', 'eye-book') : __('Add Location', 'eye-book'); ?>">
                <a href="<?php echo admin_url('admin.php?page=eye-book-locations'); ?>" class="button button-secondary"><?php _e('Cancel', 'eye-book'); ?></a>
            </p>
        </form>
    </div>
</div>

<style>
.eye-book-location-form .nav-tab-wrapper {
    margin-bottom: 20px;
}

.eye-book-location-form .tab-content {
    display: none;
    background: #fff;
    padding: 20px;
    border: 1px solid #c3c4c7;
    border-top: none;
    min-height: 400px;
}

.eye-book-location-form .tab-content.active {
    display: block;
}

.eye-book-location-form .required {
    color: #d63638;
}

.eye-book-location-form .form-table th {
    width: 200px;
}
</style>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var tabId = $(this).attr('href');
        
        // Remove active class from all tabs and content
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        
        // Add active class to clicked tab and corresponding content
        $(this).addClass('nav-tab-active');
        $(tabId).addClass('active');
    });
    
    // Phone number formatting
    $('input[type="tel"]').on('input', function() {
        var value = this.value.replace(/\D/g, '');
        var formattedValue = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
        if (value.length >= 10) {
            this.value = formattedValue;
        }
    });
    
    // Auto-generate location code from name
    $('#name').on('input', function() {
        if (!$('#location_code').val() || $('#location_code').data('auto-generated')) {
            var code = $(this).val().toUpperCase().replace(/[^A-Z0-9\s]/g, '').replace(/\s+/g, '').substring(0, 8);
            $('#location_code').val(code).data('auto-generated', true);
        }
    });
    
    $('#location_code').on('input', function() {
        $(this).removeData('auto-generated');
    });
});
</script>