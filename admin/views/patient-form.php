<?php
/**
 * Patient Form - Add/Edit Patient
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
$patient_id = intval($_GET['id'] ?? 0);
$patient = null;

if ($action === 'edit' && $patient_id > 0) {
    $patient = new Eye_Book_Patient($patient_id);
    if (!$patient->id) {
        wp_die(__('Patient not found', 'eye-book'));
    }
}

$page_title = ($action === 'edit') ? __('Edit Patient', 'eye-book') : __('Add New Patient', 'eye-book');

// Handle form submission
if ($_POST && isset($_POST['eye_book_patient_nonce']) && wp_verify_nonce($_POST['eye_book_patient_nonce'], 'eye_book_patient_action')) {
    $patient_data = array(
        'first_name' => sanitize_text_field($_POST['first_name'] ?? ''),
        'last_name' => sanitize_text_field($_POST['last_name'] ?? ''),
        'email' => sanitize_email($_POST['email'] ?? ''),
        'phone' => sanitize_text_field($_POST['phone'] ?? ''),
        'mobile_phone' => sanitize_text_field($_POST['mobile_phone'] ?? ''),
        'work_phone' => sanitize_text_field($_POST['work_phone'] ?? ''),
        'date_of_birth' => sanitize_text_field($_POST['date_of_birth'] ?? ''),
        'gender' => sanitize_text_field($_POST['gender'] ?? ''),
        'social_security_number' => sanitize_text_field($_POST['social_security_number'] ?? ''),
        'address' => sanitize_textarea_field($_POST['address'] ?? ''),
        'city' => sanitize_text_field($_POST['city'] ?? ''),
        'state' => sanitize_text_field($_POST['state'] ?? ''),
        'zip_code' => sanitize_text_field($_POST['zip_code'] ?? ''),
        'insurance_provider' => sanitize_text_field($_POST['insurance_provider'] ?? ''),
        'insurance_member_id' => sanitize_text_field($_POST['insurance_member_id'] ?? ''),
        'insurance_group_number' => sanitize_text_field($_POST['insurance_group_number'] ?? ''),
        'copay_amount' => floatval($_POST['copay_amount'] ?? 0),
        'insurance_notes' => sanitize_textarea_field($_POST['insurance_notes'] ?? ''),
        'medical_history' => sanitize_textarea_field($_POST['medical_history'] ?? ''),
        'current_medications' => sanitize_textarea_field($_POST['current_medications'] ?? ''),
        'allergies' => sanitize_textarea_field($_POST['allergies'] ?? ''),
        'eye_care_history' => sanitize_textarea_field($_POST['eye_care_history'] ?? ''),
        'emergency_contact_name' => sanitize_text_field($_POST['emergency_contact_name'] ?? ''),
        'emergency_contact_relationship' => sanitize_text_field($_POST['emergency_contact_relationship'] ?? ''),
        'emergency_contact_phone' => sanitize_text_field($_POST['emergency_contact_phone'] ?? ''),
        'emergency_contact_email' => sanitize_email($_POST['emergency_contact_email'] ?? ''),
        'status' => sanitize_text_field($_POST['status'] ?? 'active')
    );

    // Validate required fields
    $errors = array();
    if (empty($patient_data['first_name'])) {
        $errors[] = __('First name is required', 'eye-book');
    }
    if (empty($patient_data['last_name'])) {
        $errors[] = __('Last name is required', 'eye-book');
    }
    if (empty($patient_data['email'])) {
        $errors[] = __('Email is required', 'eye-book');
    }

    if (empty($errors)) {
        try {
            if ($action === 'edit' && $patient) {
                $result = $patient->update($patient_data);
                if ($result) {
                    wp_redirect(add_query_arg(array(
                        'page' => 'eye-book-patients',
                        'message' => 'updated'
                    ), admin_url('admin.php')));
                    exit;
                }
            } else {
                $new_patient = new Eye_Book_Patient();
                $result = $new_patient->create($patient_data);
                if ($result) {
                    wp_redirect(add_query_arg(array(
                        'page' => 'eye-book-patients',
                        'message' => 'created'
                    ), admin_url('admin.php')));
                    exit;
                }
            }
            $errors[] = __('Failed to save patient. Please try again.', 'eye-book');
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

// Get field values (either from POST data or existing patient)
$field_values = array();
if ($_POST) {
    foreach ($_POST as $key => $value) {
        $field_values[$key] = $value;
    }
} elseif ($patient) {
    $field_values = array(
        'first_name' => $patient->first_name ?? '',
        'last_name' => $patient->last_name ?? '',
        'email' => $patient->email ?? '',
        'phone' => $patient->phone ?? '',
        'mobile_phone' => $patient->mobile_phone ?? '',
        'work_phone' => $patient->work_phone ?? '',
        'date_of_birth' => $patient->date_of_birth ?? '',
        'gender' => $patient->gender ?? '',
        'social_security_number' => $patient->social_security_number ?? '',
        'address' => $patient->address ?? '',
        'city' => $patient->city ?? '',
        'state' => $patient->state ?? '',
        'zip_code' => $patient->zip_code ?? '',
        'insurance_provider' => $patient->insurance_provider ?? '',
        'insurance_member_id' => $patient->insurance_member_id ?? '',
        'insurance_group_number' => $patient->insurance_group_number ?? '',
        'copay_amount' => $patient->copay_amount ?? '',
        'insurance_notes' => $patient->insurance_notes ?? '',
        'medical_history' => $patient->medical_history ?? '',
        'current_medications' => $patient->current_medications ?? '',
        'allergies' => $patient->allergies ?? '',
        'eye_care_history' => $patient->eye_care_history ?? '',
        'emergency_contact_name' => $patient->emergency_contact_name ?? '',
        'emergency_contact_relationship' => $patient->emergency_contact_relationship ?? '',
        'emergency_contact_phone' => $patient->emergency_contact_phone ?? '',
        'emergency_contact_email' => $patient->emergency_contact_email ?? '',
        'status' => $patient->status ?? 'active'
    );
}
?>

<div class="wrap eye-book-wrap">
    <h1><?php echo esc_html($page_title); ?>
        <a href="<?php echo admin_url('admin.php?page=eye-book-patients'); ?>" class="page-title-action"><?php _e('Back to Patients', 'eye-book'); ?></a>
    </h1>

    <?php if (!empty($errors)): ?>
        <div class="notice notice-error">
            <?php foreach ($errors as $error): ?>
                <p><?php echo esc_html($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="eye-book-patient-form">
        <form method="post" action="">
            <?php wp_nonce_field('eye_book_patient_action', 'eye_book_patient_nonce'); ?>
            
            <div class="eye-book-form-container">
                <!-- Tab Navigation -->
                <div class="nav-tab-wrapper">
                    <a href="#basic-info" class="nav-tab nav-tab-active"><?php _e('Basic Information', 'eye-book'); ?></a>
                    <a href="#contact-info" class="nav-tab"><?php _e('Contact Information', 'eye-book'); ?></a>
                    <a href="#insurance-info" class="nav-tab"><?php _e('Insurance', 'eye-book'); ?></a>
                    <a href="#medical-info" class="nav-tab"><?php _e('Medical History', 'eye-book'); ?></a>
                    <a href="#emergency-contact" class="nav-tab"><?php _e('Emergency Contact', 'eye-book'); ?></a>
                </div>

                <!-- Basic Information Tab -->
                <div id="basic-info" class="tab-content active">
                    <h3><?php _e('Personal Details', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="first_name"><?php _e('First Name', 'eye-book'); ?> <span class="required">*</span></label></th>
                            <td><input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($field_values['first_name'] ?? ''); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="last_name"><?php _e('Last Name', 'eye-book'); ?> <span class="required">*</span></label></th>
                            <td><input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($field_values['last_name'] ?? ''); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="date_of_birth"><?php _e('Date of Birth', 'eye-book'); ?></label></th>
                            <td><input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo esc_attr($field_values['date_of_birth'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="gender"><?php _e('Gender', 'eye-book'); ?></label></th>
                            <td>
                                <select id="gender" name="gender" class="regular-text">
                                    <option value=""><?php _e('Select Gender', 'eye-book'); ?></option>
                                    <option value="male" <?php selected($field_values['gender'] ?? '', 'male'); ?>><?php _e('Male', 'eye-book'); ?></option>
                                    <option value="female" <?php selected($field_values['gender'] ?? '', 'female'); ?>><?php _e('Female', 'eye-book'); ?></option>
                                    <option value="other" <?php selected($field_values['gender'] ?? '', 'other'); ?>><?php _e('Other', 'eye-book'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="social_security_number"><?php _e('Social Security Number', 'eye-book'); ?></label></th>
                            <td><input type="text" id="social_security_number" name="social_security_number" value="<?php echo esc_attr($field_values['social_security_number'] ?? ''); ?>" class="regular-text" placeholder="XXX-XX-XXXX"></td>
                        </tr>
                        <tr>
                            <th><label for="status"><?php _e('Status', 'eye-book'); ?></label></th>
                            <td>
                                <select id="status" name="status" class="regular-text">
                                    <option value="active" <?php selected($field_values['status'] ?? 'active', 'active'); ?>><?php _e('Active', 'eye-book'); ?></option>
                                    <option value="inactive" <?php selected($field_values['status'] ?? '', 'inactive'); ?>><?php _e('Inactive', 'eye-book'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Contact Information Tab -->
                <div id="contact-info" class="tab-content">
                    <h3><?php _e('Contact Details', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="email"><?php _e('Email', 'eye-book'); ?> <span class="required">*</span></label></th>
                            <td><input type="email" id="email" name="email" value="<?php echo esc_attr($field_values['email'] ?? ''); ?>" class="regular-text" required></td>
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
                            <th><label for="work_phone"><?php _e('Work Phone', 'eye-book'); ?></label></th>
                            <td><input type="tel" id="work_phone" name="work_phone" value="<?php echo esc_attr($field_values['work_phone'] ?? ''); ?>" class="regular-text"></td>
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

                <!-- Insurance Information Tab -->
                <div id="insurance-info" class="tab-content">
                    <h3><?php _e('Insurance Information', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="insurance_provider"><?php _e('Insurance Provider', 'eye-book'); ?></label></th>
                            <td><input type="text" id="insurance_provider" name="insurance_provider" value="<?php echo esc_attr($field_values['insurance_provider'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="insurance_member_id"><?php _e('Member ID', 'eye-book'); ?></label></th>
                            <td><input type="text" id="insurance_member_id" name="insurance_member_id" value="<?php echo esc_attr($field_values['insurance_member_id'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="insurance_group_number"><?php _e('Group Number', 'eye-book'); ?></label></th>
                            <td><input type="text" id="insurance_group_number" name="insurance_group_number" value="<?php echo esc_attr($field_values['insurance_group_number'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="copay_amount"><?php _e('Copay Amount', 'eye-book'); ?></label></th>
                            <td><input type="number" id="copay_amount" name="copay_amount" value="<?php echo esc_attr($field_values['copay_amount'] ?? ''); ?>" class="regular-text" step="0.01" min="0"></td>
                        </tr>
                        <tr>
                            <th><label for="insurance_notes"><?php _e('Insurance Notes', 'eye-book'); ?></label></th>
                            <td><textarea id="insurance_notes" name="insurance_notes" class="large-text" rows="4"><?php echo esc_textarea($field_values['insurance_notes'] ?? ''); ?></textarea></td>
                        </tr>
                    </table>
                </div>

                <!-- Medical Information Tab -->
                <div id="medical-info" class="tab-content">
                    <h3><?php _e('Medical History', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="medical_history"><?php _e('Medical History', 'eye-book'); ?></label></th>
                            <td><textarea id="medical_history" name="medical_history" class="large-text" rows="4"><?php echo esc_textarea($field_values['medical_history'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="current_medications"><?php _e('Current Medications', 'eye-book'); ?></label></th>
                            <td><textarea id="current_medications" name="current_medications" class="large-text" rows="4"><?php echo esc_textarea($field_values['current_medications'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="allergies"><?php _e('Allergies', 'eye-book'); ?></label></th>
                            <td><textarea id="allergies" name="allergies" class="large-text" rows="3"><?php echo esc_textarea($field_values['allergies'] ?? ''); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="eye_care_history"><?php _e('Eye Care History', 'eye-book'); ?></label></th>
                            <td><textarea id="eye_care_history" name="eye_care_history" class="large-text" rows="4"><?php echo esc_textarea($field_values['eye_care_history'] ?? ''); ?></textarea></td>
                        </tr>
                    </table>
                </div>

                <!-- Emergency Contact Tab -->
                <div id="emergency-contact" class="tab-content">
                    <h3><?php _e('Emergency Contact', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th><label for="emergency_contact_name"><?php _e('Contact Name', 'eye-book'); ?></label></th>
                            <td><input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo esc_attr($field_values['emergency_contact_name'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="emergency_contact_relationship"><?php _e('Relationship', 'eye-book'); ?></label></th>
                            <td><input type="text" id="emergency_contact_relationship" name="emergency_contact_relationship" value="<?php echo esc_attr($field_values['emergency_contact_relationship'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="emergency_contact_phone"><?php _e('Phone Number', 'eye-book'); ?></label></th>
                            <td><input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo esc_attr($field_values['emergency_contact_phone'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th><label for="emergency_contact_email"><?php _e('Email', 'eye-book'); ?></label></th>
                            <td><input type="email" id="emergency_contact_email" name="emergency_contact_email" value="<?php echo esc_attr($field_values['emergency_contact_email'] ?? ''); ?>" class="regular-text"></td>
                        </tr>
                    </table>
                </div>
            </div>

            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $action === 'edit' ? __('Update Patient', 'eye-book') : __('Add Patient', 'eye-book'); ?>">
                <a href="<?php echo admin_url('admin.php?page=eye-book-patients'); ?>" class="button button-secondary"><?php _e('Cancel', 'eye-book'); ?></a>
            </p>
        </form>
    </div>
</div>

<style>
.eye-book-patient-form .nav-tab-wrapper {
    margin-bottom: 20px;
}

.eye-book-patient-form .tab-content {
    display: none;
    background: #fff;
    padding: 20px;
    border: 1px solid #c3c4c7;
    border-top: none;
    min-height: 400px;
}

.eye-book-patient-form .tab-content.active {
    display: block;
}

.eye-book-patient-form .required {
    color: #d63638;
}

.eye-book-patient-form .form-table th {
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
    
    // SSN formatting
    $('#social_security_number').on('input', function() {
        var value = this.value.replace(/\D/g, '');
        var formattedValue = value.replace(/(\d{3})(\d{2})(\d{4})/, '$1-$2-$3');
        if (value.length >= 9) {
            this.value = formattedValue;
        }
    });
});
</script>