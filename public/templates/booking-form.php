<?php
/**
 * Appointment booking form template
 *
 * @package EyeBook
 * @subpackage Public/Templates
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get form data
$providers = Eye_Book_Provider::get_providers(array('status' => 'active'));
$locations = Eye_Book_Location::get_locations(array('status' => 'active'));

// Get appointment types
global $wpdb;
$appointment_types = $wpdb->get_results(
    "SELECT * FROM " . EYE_BOOK_TABLE_APPOINTMENT_TYPES . " WHERE is_active = 1 ORDER BY sort_order"
);

// Handle success/error messages
$booking_status = $_GET['eye_book_booking'] ?? '';
$message = '';

if ($booking_status === 'success') {
    $appointment_id = sanitize_text_field($_GET['appointment_id'] ?? '');
    $message = '<div class="eye-book-success">' . 
               sprintf(__('Your appointment has been booked successfully! Appointment ID: %s', 'eye-book'), $appointment_id) . 
               '</div>';
} elseif ($booking_status === 'error') {
    $error_message = sanitize_text_field($_GET['message'] ?? __('An error occurred', 'eye-book'));
    $message = '<div class="eye-book-error">' . $error_message . '</div>';
}
?>

<div class="eye-book-booking-form" data-theme="<?php echo esc_attr($atts['theme']); ?>">
    <?php echo $message; ?>
    
    <form id="eye-book-booking-form" class="eye-book-form" method="post" action="">
        <?php wp_nonce_field('eye_book_booking_nonce', 'nonce'); ?>
        <input type="hidden" name="eye_book_action" value="book_appointment">
        
        <!-- Step 1: Appointment Details -->
        <div class="booking-step" id="step-1">
            <h3><?php _e('Appointment Details', 'eye-book'); ?></h3>
            
            <div class="form-row">
                <?php if ($atts['show_location_selection'] === 'true' && count($locations) > 1): ?>
                <div class="form-group">
                    <label for="location_id" class="required"><?php _e('Location', 'eye-book'); ?></label>
                    <select id="location_id" name="location_id" required>
                        <option value=""><?php _e('Select Location', 'eye-book'); ?></option>
                        <?php foreach ($locations as $location): ?>
                            <option value="<?php echo $location->id; ?>" 
                                    <?php selected($atts['location_id'], $location->id); ?>>
                                <?php echo esc_html($location->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="location_id" value="<?php echo $atts['location_id'] ?: ($locations[0]->id ?? ''); ?>">
                <?php endif; ?>
                
                <?php if ($atts['show_provider_selection'] === 'true' && count($providers) > 1): ?>
                <div class="form-group">
                    <label for="provider_id" class="required"><?php _e('Provider', 'eye-book'); ?></label>
                    <select id="provider_id" name="provider_id" required>
                        <option value=""><?php _e('Select Provider', 'eye-book'); ?></option>
                        <?php foreach ($providers as $provider): ?>
                            <option value="<?php echo $provider->id; ?>" 
                                    <?php selected($atts['provider_id'], $provider->id); ?>>
                                <?php echo esc_html($provider->get_display_name()); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="provider_id" value="<?php echo $atts['provider_id'] ?: ($providers[0]->id ?? ''); ?>">
                <?php endif; ?>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="appointment_type_id" class="required"><?php _e('Appointment Type', 'eye-book'); ?></label>
                    <select id="appointment_type_id" name="appointment_type_id" required>
                        <option value=""><?php _e('Select Appointment Type', 'eye-book'); ?></option>
                        <?php foreach ($appointment_types as $type): ?>
                            <option value="<?php echo $type->id; ?>" 
                                    data-duration="<?php echo $type->duration; ?>"
                                    <?php selected($atts['appointment_type_id'], $type->id); ?>>
                                <?php echo esc_html($type->name); ?> (<?php echo $type->duration; ?> <?php _e('min', 'eye-book'); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="appointment-type-description"></div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="appointment_date" class="required"><?php _e('Preferred Date', 'eye-book'); ?></label>
                    <input type="date" id="appointment_date" name="appointment_date" required 
                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                           max="<?php echo date('Y-m-d', strtotime('+' . get_option('eye_book_booking_advance_days', 30) . ' days')); ?>">
                </div>
                
                <div class="form-group">
                    <label for="appointment_time" class="required"><?php _e('Preferred Time', 'eye-book'); ?></label>
                    <select id="appointment_time" name="appointment_time" required disabled>
                        <option value=""><?php _e('Select date first', 'eye-book'); ?></option>
                    </select>
                    <div class="loading-slots" style="display: none;"><?php _e('Loading available times...', 'eye-book'); ?></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="chief_complaint"><?php _e('Reason for Visit', 'eye-book'); ?></label>
                <textarea id="chief_complaint" name="chief_complaint" rows="3" 
                          placeholder="<?php _e('Please describe the reason for your visit', 'eye-book'); ?>"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-primary next-step"><?php _e('Next Step', 'eye-book'); ?></button>
            </div>
        </div>
        
        <!-- Step 2: Patient Information -->
        <div class="booking-step" id="step-2" style="display: none;">
            <h3><?php _e('Patient Information', 'eye-book'); ?></h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name" class="required"><?php _e('First Name', 'eye-book'); ?></label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                
                <div class="form-group">
                    <label for="last_name" class="required"><?php _e('Last Name', 'eye-book'); ?></label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email" class="required"><?php _e('Email Address', 'eye-book'); ?></label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone" class="required"><?php _e('Phone Number', 'eye-book'); ?></label>
                    <input type="tel" id="phone" name="phone" required 
                           placeholder="(555) 123-4567">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="date_of_birth" class="required"><?php _e('Date of Birth', 'eye-book'); ?></label>
                    <input type="date" id="date_of_birth" name="date_of_birth" required>
                </div>
                
                <div class="form-group">
                    <label for="gender"><?php _e('Gender', 'eye-book'); ?></label>
                    <select id="gender" name="gender">
                        <option value=""><?php _e('Select Gender', 'eye-book'); ?></option>
                        <option value="male"><?php _e('Male', 'eye-book'); ?></option>
                        <option value="female"><?php _e('Female', 'eye-book'); ?></option>
                        <option value="other"><?php _e('Other', 'eye-book'); ?></option>
                        <option value="prefer_not_to_say"><?php _e('Prefer not to say', 'eye-book'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="insurance_provider"><?php _e('Insurance Provider', 'eye-book'); ?></label>
                    <input type="text" id="insurance_provider" name="insurance_provider" 
                           placeholder="<?php _e('e.g., Blue Cross Blue Shield', 'eye-book'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="insurance_member_id"><?php _e('Member ID', 'eye-book'); ?></label>
                    <input type="text" id="insurance_member_id" name="insurance_member_id">
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes"><?php _e('Additional Notes', 'eye-book'); ?></label>
                <textarea id="notes" name="notes" rows="3" 
                          placeholder="<?php _e('Any additional information you would like us to know', 'eye-book'); ?>"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary prev-step"><?php _e('Previous', 'eye-book'); ?></button>
                <button type="button" class="btn btn-primary next-step"><?php _e('Review & Confirm', 'eye-book'); ?></button>
            </div>
        </div>
        
        <!-- Step 3: Confirmation -->
        <div class="booking-step" id="step-3" style="display: none;">
            <h3><?php _e('Confirm Your Appointment', 'eye-book'); ?></h3>
            
            <div class="appointment-summary">
                <div class="summary-section">
                    <h4><?php _e('Appointment Details', 'eye-book'); ?></h4>
                    <div class="summary-item">
                        <span class="label"><?php _e('Date:', 'eye-book'); ?></span>
                        <span class="value" id="summary-date"></span>
                    </div>
                    <div class="summary-item">
                        <span class="label"><?php _e('Time:', 'eye-book'); ?></span>
                        <span class="value" id="summary-time"></span>
                    </div>
                    <div class="summary-item">
                        <span class="label"><?php _e('Provider:', 'eye-book'); ?></span>
                        <span class="value" id="summary-provider"></span>
                    </div>
                    <div class="summary-item">
                        <span class="label"><?php _e('Location:', 'eye-book'); ?></span>
                        <span class="value" id="summary-location"></span>
                    </div>
                    <div class="summary-item">
                        <span class="label"><?php _e('Type:', 'eye-book'); ?></span>
                        <span class="value" id="summary-type"></span>
                    </div>
                </div>
                
                <div class="summary-section">
                    <h4><?php _e('Patient Information', 'eye-book'); ?></h4>
                    <div class="summary-item">
                        <span class="label"><?php _e('Name:', 'eye-book'); ?></span>
                        <span class="value" id="summary-name"></span>
                    </div>
                    <div class="summary-item">
                        <span class="label"><?php _e('Email:', 'eye-book'); ?></span>
                        <span class="value" id="summary-email"></span>
                    </div>
                    <div class="summary-item">
                        <span class="label"><?php _e('Phone:', 'eye-book'); ?></span>
                        <span class="value" id="summary-phone"></span>
                    </div>
                </div>
            </div>
            
            <div class="terms-section">
                <label class="checkbox-label">
                    <input type="checkbox" id="agree_terms" required>
                    <?php _e('I agree to the', 'eye-book'); ?> 
                    <a href="#" target="_blank"><?php _e('Terms of Service', 'eye-book'); ?></a> 
                    <?php _e('and', 'eye-book'); ?> 
                    <a href="#" target="_blank"><?php _e('Privacy Policy', 'eye-book'); ?></a>
                </label>
                
                <label class="checkbox-label">
                    <input type="checkbox" id="confirm_info">
                    <?php _e('I confirm that the information provided is accurate', 'eye-book'); ?>
                </label>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary prev-step"><?php _e('Previous', 'eye-book'); ?></button>
                <button type="submit" class="btn btn-primary submit-booking" disabled>
                    <?php _e('Book Appointment', 'eye-book'); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    var currentStep = 1;
    var maxSteps = 3;
    
    // Step navigation
    $('.next-step').on('click', function() {
        if (validateCurrentStep()) {
            if (currentStep < maxSteps) {
                showStep(currentStep + 1);
            }
        }
    });
    
    $('.prev-step').on('click', function() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    });
    
    function showStep(step) {
        $('.booking-step').hide();
        $('#step-' + step).show();
        currentStep = step;
        
        if (step === 3) {
            updateSummary();
        }
    }
    
    function validateCurrentStep() {
        var isValid = true;
        var currentStepElement = $('#step-' + currentStep);
        
        currentStepElement.find('[required]').each(function() {
            if (!$(this).val()) {
                $(this).addClass('error');
                isValid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        
        return isValid;
    }
    
    function updateSummary() {
        // Update appointment summary
        var date = $('#appointment_date').val();
        var time = $('#appointment_time option:selected').text();
        var provider = $('#provider_id option:selected').text();
        var location = $('#location_id option:selected').text();
        var type = $('#appointment_type_id option:selected').text();
        
        $('#summary-date').text(date);
        $('#summary-time').text(time);
        $('#summary-provider').text(provider);
        $('#summary-location').text(location);
        $('#summary-type').text(type);
        
        // Update patient summary
        var name = $('#first_name').val() + ' ' + $('#last_name').val();
        var email = $('#email').val();
        var phone = $('#phone').val();
        
        $('#summary-name').text(name);
        $('#summary-email').text(email);
        $('#summary-phone').text(phone);
    }
    
    // Load available time slots when date changes
    $('#appointment_date').on('change', function() {
        loadAvailableSlots();
    });
    
    $('#provider_id, #location_id, #appointment_type_id').on('change', function() {
        if ($('#appointment_date').val()) {
            loadAvailableSlots();
        }
    });
    
    function loadAvailableSlots() {
        var providerID = $('#provider_id').val();
        var locationID = $('#location_id').val();
        var appointmentTypeID = $('#appointment_type_id').val();
        var date = $('#appointment_date').val();
        
        if (!providerID || !locationID || !appointmentTypeID || !date) {
            return;
        }
        
        $('.loading-slots').show();
        $('#appointment_time').prop('disabled', true).html('<option value="">' + eyeBookPublic.strings.loading + '</option>');
        
        $.post(eyeBookPublic.ajax_url, {
            action: 'eye_book_get_available_slots',
            nonce: eyeBookPublic.nonce,
            provider_id: providerID,
            location_id: locationID,
            appointment_type_id: appointmentTypeID,
            date: date
        }, function(response) {
            $('.loading-slots').hide();
            
            if (response.success) {
                var options = '<option value="">' + eyeBookPublic.strings.select_time + '</option>';
                
                if (response.data.length > 0) {
                    $.each(response.data, function(index, slot) {
                        options += '<option value="' + slot.time + '">' + slot.display + '</option>';
                    });
                } else {
                    options = '<option value="">' + 'No available times' + '</option>';
                }
                
                $('#appointment_time').html(options).prop('disabled', false);
            } else {
                $('#appointment_time').html('<option value="">' + response.data + '</option>');
            }
        }).fail(function() {
            $('.loading-slots').hide();
            $('#appointment_time').html('<option value="">' + eyeBookPublic.strings.error + '</option>');
        });
    }
    
    // Enable submit button when terms are agreed
    $('#agree_terms, #confirm_info').on('change', function() {
        var termsAgreed = $('#agree_terms').is(':checked');
        var infoConfirmed = $('#confirm_info').is(':checked');
        
        $('.submit-booking').prop('disabled', !(termsAgreed && infoConfirmed));
    });
    
    // Phone number formatting
    $('#phone').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        var formattedValue = '';
        
        if (value.length >= 6) {
            formattedValue = '(' + value.substring(0, 3) + ') ' + value.substring(3, 6) + '-' + value.substring(6, 10);
        } else if (value.length >= 3) {
            formattedValue = '(' + value.substring(0, 3) + ') ' + value.substring(3);
        } else {
            formattedValue = value;
        }
        
        $(this).val(formattedValue);
    });
    
    // Show appointment type description
    $('#appointment_type_id').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var description = selectedOption.data('description') || '';
        $('.appointment-type-description').text(description);
    });
});
</script>