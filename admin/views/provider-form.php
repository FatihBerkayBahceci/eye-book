<?php
/**
 * Provider Form - Add/Edit Provider
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
$provider_id = intval($_GET['id'] ?? 0);
$provider = null;

if ($action === 'edit' && $provider_id > 0) {
    $provider = new Eye_Book_Provider($provider_id);
    if (!$provider->id) {
        wp_die(__('Provider not found', 'eye-book'));
    }
}

$page_title = ($action === 'edit') ? __('Edit Provider', 'eye-book') : __('Add New Provider', 'eye-book');

// Handle form submission
if ($_POST && isset($_POST['eye_book_provider_nonce']) && wp_verify_nonce($_POST['eye_book_provider_nonce'], 'eye_book_provider_action')) {
    $provider_data = array(
        'wp_user_id' => intval($_POST['wp_user_id'] ?? 0),
        'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
        'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
        'title' => sanitize_text_field($_POST['title'] ?? ''),
        'suffix' => sanitize_text_field($_POST['suffix'] ?? ''),
        'display_name' => sanitize_text_field($_POST['display_name'] ?? ''),
        'specialization' => sanitize_text_field($_POST['specialization'] ?? ''),
        'subspecialty' => sanitize_text_field($_POST['subspecialty'] ?? ''),
        'license_number' => sanitize_text_field($_POST['license_number'] ?? ''),
        'npi_number' => sanitize_text_field($_POST['npi_number'] ?? ''),
        'dea_number' => sanitize_text_field($_POST['dea_number'] ?? ''),
        'credentials' => sanitize_text_field($_POST['credentials'] ?? ''),
        'bio' => sanitize_textarea_field($_POST['bio'] ?? ''),
        'education' => sanitize_textarea_field($_POST['education'] ?? ''),
        'certifications' => sanitize_textarea_field($_POST['certifications'] ?? ''),
        'languages' => sanitize_text_field($_POST['languages'] ?? ''),
        'phone' => sanitize_text_field($_POST['phone'] ?? ''),
        'mobile_phone' => sanitize_text_field($_POST['mobile_phone'] ?? ''),
        'email' => sanitize_email($_POST['email'] ?? ''),
        'address' => sanitize_textarea_field($_POST['address'] ?? ''),
        'city' => sanitize_text_field($_POST['city'] ?? ''),
        'state' => sanitize_text_field($_POST['state'] ?? ''),
        'zip_code' => sanitize_text_field($_POST['zip_code'] ?? ''),
        'hourly_rate' => floatval($_POST['hourly_rate'] ?? 0),
        'default_appointment_duration' => intval($_POST['default_appointment_duration'] ?? 30),
        'buffer_time' => intval($_POST['buffer_time'] ?? 0),
        'status' => sanitize_text_field($_POST['status'] ?? 'active'),
        'notes' => sanitize_textarea_field($_POST['notes'] ?? '')
    );

    // Validate required fields
    $errors = array();
    if (empty($provider_data['first_name'])) {
        $errors[] = __('First name is required', 'eye-book');
    }
    if (empty($provider_data['last_name'])) {
        $errors[] = __('Last name is required', 'eye-book');
    }
    if (empty($provider_data['specialization'])) {
        $errors[] = __('Specialization is required', 'eye-book');
    }

    if (empty($errors)) {
        try {
            if ($action === 'edit' && $provider) {
                $result = $provider->update($provider_data);
                if ($result) {
                    wp_redirect(add_query_arg(array(
                        'page' => 'eye-book-providers',
                        'message' => 'updated'
                    ), admin_url('admin.php')));
                    exit;
                }
            } else {
                $new_provider = new Eye_Book_Provider();
                $result = $new_provider->create($provider_data);
                if ($result) {
                    wp_redirect(add_query_arg(array(
                        'page' => 'eye-book-providers',
                        'message' => 'created'
                    ), admin_url('admin.php')));
                    exit;
                }
            }
            $errors[] = __('Failed to save provider. Please try again.', 'eye-book');
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// Get field values (either from POST data or existing provider)
$field_values = array();
if ($_POST) {
    foreach ($_POST as $key => $value) {
        $field_values[$key] = $value;
    }
} elseif ($provider) {
    $field_values = array(
        'wp_user_id' => $provider->wp_user_id ?? 0,
        'first_name' => $provider->first_name ?? '',
        'last_name' => $provider->last_name ?? '',
        'title' => $provider->title ?? '',
        'suffix' => $provider->suffix ?? '',
        'display_name' => $provider->display_name ?? '',
        'specialization' => $provider->specialization ?? '',
        'subspecialty' => $provider->subspecialty ?? '',
        'license_number' => $provider->license_number ?? '',
        'npi_number' => $provider->npi_number ?? '',
        'dea_number' => $provider->dea_number ?? '',
        'credentials' => $provider->credentials ?? '',
        'bio' => $provider->bio ?? '',
        'education' => $provider->education ?? '',
        'certifications' => $provider->certifications ?? '',
        'languages' => $provider->languages ?? '',
        'phone' => $provider->phone ?? '',
        'mobile_phone' => $provider->mobile_phone ?? '',
        'email' => $provider->email ?? '',
        'address' => $provider->address ?? '',
        'city' => $provider->city ?? '',
        'state' => $provider->state ?? '',
        'zip_code' => $provider->zip_code ?? '',
        'hourly_rate' => $provider->hourly_rate ?? '',
        'default_appointment_duration' => $provider->default_appointment_duration ?? 30,
        'buffer_time' => $provider->buffer_time ?? 0,
        'status' => $provider->status ?? 'active',
        'notes' => $provider->notes ?? ''
    );
}

// Get WordPress users for linking
$users = get_users(array('role__not_in' => array('subscriber')));
?>

<div class="wrap eye-book-wrap">
    <h1><?php echo esc_html($page_title); ?>
        <a href="<?php echo admin_url('admin.php?page=eye-book-providers'); ?>" class="page-title-action"><?php _e('Back to Providers', 'eye-book'); ?></a>
    </h1>

    <?php if (!empty($errors)): ?>
        <div class="notice notice-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo esc_html($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="eye-book-provider-form">
        <form method="post" action="">
            <?php wp_nonce_field('eye_book_provider_action', 'eye_book_provider_nonce'); ?>
            
            <div class="eye-book-form-container">
                <!-- Tab Navigation -->
                <div class="nav-tab-wrapper">
                    <a href="#basic-info" class="nav-tab nav-tab-active"><?php _e('Basic Information', 'eye-book'); ?></a>
                    <a href="#professional-info" class="nav-tab"><?php _e('Professional Details', 'eye-book'); ?></a>
                    <a href="#contact-info" class="nav-tab"><?php _e('Contact Information', 'eye-book'); ?></a>
                    <a href="#schedule-settings" class="nav-tab"><?php _e('Schedule Settings', 'eye-book'); ?></a>
                </div>

                <!-- Basic Information Tab -->
                <div id="basic-info" class="tab-content active">
                    <h3><?php _e('Personal Details', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="wp_user_id"><?php _e('WordPress User', 'eye-book'); ?></label></th>
                            <td>
                                <select id="wp_user_id" name="wp_user_id" class="regular-text">
                                    <option value="0"><?php _e('Select User (Optional)', 'eye-book'); ?></option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user->ID; ?>" <?php selected($field_values['wp_user_id'] ?? 0, $user->ID); ?>>
                                            <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e('Link this provider to a WordPress user account for login access.', 'eye-book'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="title"><?php _e('Title', 'eye-book'); ?></label></th>
                            <td>
                                <select id="title" name="title" class="regular-text">
                                    <option value=""><?php _e('Select Title', 'eye-book'); ?></option>
                                    <option value="Dr." <?php selected($field_values['title'] ?? '', 'Dr.'); ?>><?php _e('Dr.', 'eye-book'); ?></option>
                                    <option value="Prof." <?php selected($field_values['title'] ?? '', 'Prof.'); ?>><?php _e('Prof.', 'eye-book'); ?></option>
                                    <option value="Mr." <?php selected($field_values['title'] ?? '', 'Mr.'); ?>><?php _e('Mr.', 'eye-book'); ?></option>
                                    <option value="Ms." <?php selected($field_values['title'] ?? '', 'Ms.'); ?>><?php _e('Ms.', 'eye-book'); ?></option>
                                    <option value="Mrs." <?php selected($field_values['title'] ?? '', 'Mrs.'); ?>><?php _e('Mrs.', 'eye-book'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="first_name"><?php _e('First Name', 'eye-book'); ?> <span class="required">*</span></label></th>
                            <td><input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($field_values['first_name'] ?? ''); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="last_name"><?php _e('Last Name', 'eye-book'); ?> <span class="required">*</span></label></th>
                            <td><input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($field_values['last_name'] ?? ''); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="suffix"><?php _e('Suffix', 'eye-book'); ?></label></th>
                            <td>
                                <input type="text" id="suffix" name="suffix" value="<?php echo esc_attr($field_values['suffix'] ?? ''); ?>" class="regular-text" placeholder="Jr., Sr., III, etc.">
                            </td>
                        </tr>
                        <tr>
                            <th><label for="display_name"><?php _e('Display Name', 'eye-book'); ?></label></th>
                            <td>
                                <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($field_values['display_name'] ?? ''); ?>" class="regular-text" placeholder="How name appears to patients">
                                <p class="description"><?php _e('Leave blank to auto-generate from first and last name', 'eye-book'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="status"><?php _e('Status', 'eye-book'); ?></label></th>
                            <td>
                                <select id="status" name="status" class="regular-text">
                                    <option value="active" <?php selected($field_values['status'] ?? 'active', 'active'); ?>><?php _e('Active', 'eye-book'); ?></option>
                                    <option value="inactive" <?php selected($field_values['status'] ?? '', 'inactive'); ?>><?php _e('Inactive', 'eye-book'); ?></option>
                                    <option value="on_leave" <?php selected($field_values['status'] ?? '', 'on_leave'); ?>><?php _e('On Leave', 'eye-book'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Professional Information Tab -->
                <div id="professional-info" class="tab-content">
                    <h3><?php _e('Professional Details', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="specialization"><?php _e('Specialization', 'eye-book'); ?> <span class="required">*</span></label></th>
                            <td>
                                <select id="specialization" name="specialization" class="regular-text" required>
                                    <option value=""><?php _e('Select Specialization', 'eye-book'); ?></option>
                                    <option value="ophthalmologist" <?php selected($field_values['specialization'] ?? '', 'ophthalmologist'); ?>><?php _e('Ophthalmologist', 'eye-book'); ?></option>
                                    <option value="optometrist" <?php selected($field_values['specialization'] ?? '', 'optometrist'); ?>><?php _e('Optometrist', 'eye-book'); ?></option>
                                    <option value="optician" <?php selected($field_values['specialization'] ?? '', 'optician'); ?>><?php _e('Optician', 'eye-book'); ?></option>
                                    <option value="technician" <?php selected($field_values['specialization'] ?? '', 'technician'); ?>><?php _e('Eye Care Technician', 'eye-book'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="subspecialty"><?php _e('Subspecialty', 'eye-book'); ?></label></th>
                            <td><input type="text" id="subspecialty" name="subspecialty" value="<?php echo esc_attr($field_values['subspecialty'] ?? ''); ?>" class="regular-text" placeholder="e.g., Retinal Specialist, Glaucoma Specialist"></td>
                        </tr>
                        <tr>
                            <th><label for="license_number"><?php _e('License Number', 'eye-book'); ?></label></th>
                            <td><input type="text" id="license_number" name="license_number" value="<?php echo esc_attr($field_values['license_number'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="npi_number"><?php _e('NPI Number', 'eye-book'); ?></label></th>
                            <td><input type="text" id="npi_number" name="npi_number" value="<?php echo esc_attr($field_values['npi_number'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="dea_number"><?php _e('DEA Number', 'eye-book'); ?></label></th>
                            <td><input type="text" id="dea_number" name="dea_number" value="<?php echo esc_attr($field_values['dea_number'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="credentials"><?php _e('Credentials', 'eye-book'); ?></label></th>
                            <td><input type="text" id="credentials" name="credentials" value="<?php echo esc_attr($field_values['credentials'] ?? ''); ?>" class="regular-text" placeholder="MD, OD, PhD, etc."></td>
                        </tr>
                        <tr>
                            <th><label for="education"><?php _e('Education', 'eye-book'); ?></label></th>
                            <td><textarea id="education" name="education" class="large-text" rows="4"><?php echo esc_textarea($field_values['education'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="certifications"><?php _e('Certifications', 'eye-book'); ?></label></th>
                            <td><textarea id="certifications" name="certifications" class="large-text" rows="3"><?php echo esc_textarea($field_values['certifications'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="languages"><?php _e('Languages Spoken', 'eye-book'); ?></label></th>
                            <td><input type="text" id="languages" name="languages" value="<?php echo esc_attr($field_values['languages'] ?? ''); ?>" class="regular-text" placeholder="English, Spanish, etc."></td>
                        </tr>
                        <tr>
                            <th><label for="bio"><?php _e('Biography', 'eye-book'); ?></label></th>
                            <td><textarea id="bio" name="bio" class="large-text" rows="5"><?php echo esc_textarea($field_values['bio'] ?? ''); ?></textarea></td>
                        </tr>
                    </table>
                </div>

                <!-- Contact Information Tab -->
                <div id="contact-info" class="tab-content">
                    <h3><?php _e('Contact Details', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="email"><?php _e('Email', 'eye-book'); ?></label></th>
                            <td><input type="email" id="email" name="email" value="<?php echo esc_attr($field_values['email'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="phone"><?php _e('Primary Phone', 'eye-book'); ?></label></th>
                            <td><input type="tel" id="phone" name="phone" value="<?php echo esc_attr($field_values['phone'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="mobile_phone"><?php _e('Mobile Phone', 'eye-book'); ?></label></th>
                            <td><input type="tel" id="mobile_phone" name="mobile_phone" value="<?php echo esc_attr($field_values['mobile_phone'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="address"><?php _e('Address', 'eye-book'); ?></label></th>
                            <td><textarea id="address" name="address" class="large-text" rows="3"><?php echo esc_textarea($field_values['address'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="city"><?php _e('City', 'eye-book'); ?></label></th>
                            <td><input type="text" id="city" name="city" value="<?php echo esc_attr($field_values['city'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="state"><?php _e('State', 'eye-book'); ?></label></th>
                            <td><input type="text" id="state" name="state" value="<?php echo esc_attr($field_values['state'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="zip_code"><?php _e('ZIP Code', 'eye-book'); ?></label></th>
                            <td><input type="text" id="zip_code" name="zip_code" value="<?php echo esc_attr($field_values['zip_code'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                    </table>
                </div>

                <!-- Schedule Settings Tab -->
                <div id="schedule-settings" class="tab-content">
                    <h3><?php _e('Schedule & Billing Settings', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="default_appointment_duration"><?php _e('Default Appointment Duration', 'eye-book'); ?></label></th>
                            <td>
                                <select id="default_appointment_duration" name="default_appointment_duration" class="regular-text">
                                    <option value="15" <?php selected($field_values['default_appointment_duration'] ?? 30, 15); ?>>15 <?php _e('minutes', 'eye-book'); ?></option>
                                    <option value="30" <?php selected($field_values['default_appointment_duration'] ?? 30, 30); ?>>30 <?php _e('minutes', 'eye-book'); ?></option>
                                    <option value="45" <?php selected($field_values['default_appointment_duration'] ?? 30, 45); ?>>45 <?php _e('minutes', 'eye-book'); ?></option>
                                    <option value="60" <?php selected($field_values['default_appointment_duration'] ?? 30, 60); ?>>60 <?php _e('minutes', 'eye-book'); ?></option>
                                    <option value="90" <?php selected($field_values['default_appointment_duration'] ?? 30, 90); ?>>90 <?php _e('minutes', 'eye-book'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="buffer_time"><?php _e('Buffer Time Between Appointments', 'eye-book'); ?></label></th>
                            <td>
                                <select id="buffer_time" name="buffer_time" class="regular-text">
                                    <option value="0" <?php selected($field_values['buffer_time'] ?? 0, 0); ?>>0 <?php _e('minutes', 'eye-book'); ?></option>
                                    <option value="5" <?php selected($field_values['buffer_time'] ?? 0, 5); ?>>5 <?php _e('minutes', 'eye-book'); ?></option>
                                    <option value="10" <?php selected($field_values['buffer_time'] ?? 0, 10); ?>>10 <?php _e('minutes', 'eye-book'); ?></option>
                                    <option value="15" <?php selected($field_values['buffer_time'] ?? 0, 15); ?>>15 <?php _e('minutes', 'eye-book'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="hourly_rate"><?php _e('Hourly Rate', 'eye-book'); ?></label></th>
                            <td>
                                <input type="number" id="hourly_rate" name="hourly_rate" value="<?php echo esc_attr($field_values['hourly_rate'] ?? ''); ?>" class="regular-text" step="0.01" min="0">
                                <p class="description"><?php _e('Optional: Used for scheduling and billing calculations', 'eye-book'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="notes"><?php _e('Internal Notes', 'eye-book'); ?></label></th>
                            <td><textarea id="notes" name="notes" class="large-text" rows="4"><?php echo esc_textarea($field_values['notes'] ?? ''); ?></textarea></td>
                        </tr>
                    </table>
                </div>
            </div>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $action === 'edit' ? __('Update Provider', 'eye-book') : __('Add Provider', 'eye-book'); ?>">
                <a href="<?php echo admin_url('admin.php?page=eye-book-providers'); ?>" class="button button-secondary"><?php _e('Cancel', 'eye-book'); ?></a>
            </p>
        </form>
    </div>
</div>

<style>
.eye-book-provider-form .nav-tab-wrapper {
    margin-bottom: 20px;
}

.eye-book-provider-form .tab-content {
    display: none;
    background: #fff;
    padding: 20px;
    border: 1px solid #c3c4c7;
    border-top: none;
    min-height: 400px;
}

.eye-book-provider-form .tab-content.active {
    display: block;
}

.eye-book-provider-form .required {
    color: #d63638;
}

.eye-book-provider-form .form-table th {
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
    
    // Auto-generate display name
    $('#first_name, #last_name, #title, #suffix').on('input', function() {
        if (!$('#display_name').val() || $('#display_name').data('auto-generated')) {
            var displayName = '';
            var title = $('#title').val();
            var firstName = $('#first_name').val();
            var lastName = $('#last_name').val();
            var suffix = $('#suffix').val();
            
            if (title) displayName += title + ' ';
            if (firstName) displayName += firstName + ' ';
            if (lastName) displayName += lastName;
            if (suffix) displayName += ', ' + suffix;
            
            $('#display_name').val(displayName.trim()).data('auto-generated', true);
        }
    });
    
    $('#display_name').on('input', function() {
        $(this).removeData('auto-generated');
    });
});
</script>