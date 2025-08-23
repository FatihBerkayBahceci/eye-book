<?php
/**
 * Patient portal template
 *
 * @package EyeBook
 * @subpackage Public/Templates
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get patient appointments
$upcoming_appointments = $patient->get_upcoming_appointments(5);
$appointment_history = $patient->get_appointment_history(10);
$patient_forms = $patient->get_forms();
?>

<div class="eye-book-patient-portal">
    
    <!-- Portal Header -->
    <div class="portal-header">
        <div class="portal-welcome">
            <div>
                <h2><?php printf(__('Welcome, %s', 'eye-book'), esc_html($patient->first_name)); ?></h2>
                <p class="welcome-message"><?php _e('Manage your appointments and health information', 'eye-book'); ?></p>
            </div>
            <div class="portal-actions">
                <a href="<?php echo wp_logout_url(); ?>" class="btn btn-secondary portal-logout">
                    <?php _e('Logout', 'eye-book'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Portal Content Grid -->
    <div class="portal-grid">
        
        <!-- Upcoming Appointments -->
        <div class="portal-card">
            <h3><?php _e('Upcoming Appointments', 'eye-book'); ?></h3>
            
            <div class="card-content">
                <?php if (!empty($upcoming_appointments)): ?>
                    <div class="appointments-list">
                        <?php foreach ($upcoming_appointments as $appointment): 
                            $provider = $appointment->get_provider();
                            $location = $appointment->get_location();
                            $appointment_date = date_i18n(get_option('date_format'), strtotime($appointment->start_datetime));
                            $appointment_time = date_i18n(get_option('time_format'), strtotime($appointment->start_datetime));
                        ?>
                        <div class="appointment-item">
                            <div class="appointment-header">
                                <strong><?php echo esc_html($appointment_date); ?></strong>
                                <span class="appointment-time"><?php echo esc_html($appointment_time); ?></span>
                            </div>
                            <div class="appointment-details">
                                <div class="detail-item">
                                    <span class="label"><?php _e('Provider:', 'eye-book'); ?></span>
                                    <span class="value"><?php echo $provider ? esc_html($provider->get_display_name()) : __('Unknown', 'eye-book'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="label"><?php _e('Location:', 'eye-book'); ?></span>
                                    <span class="value"><?php echo $location ? esc_html($location->name) : __('Unknown', 'eye-book'); ?></span>
                                </div>
                                <div class="detail-item">
                                    <span class="label"><?php _e('Status:', 'eye-book'); ?></span>
                                    <span class="status-badge status-<?php echo esc_attr($appointment->status); ?>">
                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $appointment->status))); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="appointment-actions">
                                <?php if ($appointment->can_cancel()): ?>
                                <button type="button" class="btn btn-sm btn-secondary cancel-appointment" 
                                        data-appointment-id="<?php echo esc_attr($appointment->id); ?>">
                                    <?php _e('Cancel', 'eye-book'); ?>
                                </button>
                                <?php endif; ?>
                                
                                <button type="button" class="btn btn-sm btn-secondary reschedule-appointment" 
                                        data-appointment-id="<?php echo esc_attr($appointment->id); ?>">
                                    <?php _e('Reschedule', 'eye-book'); ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p><?php _e('You have no upcoming appointments.', 'eye-book'); ?></p>
                        <a href="#" class="btn btn-primary book-appointment"><?php _e('Book Appointment', 'eye-book'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card-actions">
                <a href="#" class="view-all-link"><?php _e('View All Appointments', 'eye-book'); ?></a>
            </div>
        </div>

        <!-- Patient Information -->
        <div class="portal-card">
            <h3><?php _e('My Information', 'eye-book'); ?></h3>
            
            <div class="card-content">
                <div class="patient-info">
                    <div class="info-item">
                        <span class="label"><?php _e('Name:', 'eye-book'); ?></span>
                        <span class="value"><?php echo esc_html($patient->get_full_name()); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label"><?php _e('Date of Birth:', 'eye-book'); ?></span>
                        <span class="value">
                            <?php echo $patient->date_of_birth ? date_i18n(get_option('date_format'), strtotime($patient->date_of_birth)) : __('Not provided', 'eye-book'); ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="label"><?php _e('Age:', 'eye-book'); ?></span>
                        <span class="value"><?php echo $patient->get_age(); ?> <?php _e('years old', 'eye-book'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label"><?php _e('Phone:', 'eye-book'); ?></span>
                        <span class="value"><?php echo esc_html($patient->phone ?: __('Not provided', 'eye-book')); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="label"><?php _e('Email:', 'eye-book'); ?></span>
                        <span class="value"><?php echo esc_html($patient->email ?: __('Not provided', 'eye-book')); ?></span>
                    </div>
                    <?php if ($patient->insurance_provider): ?>
                    <div class="info-item">
                        <span class="label"><?php _e('Insurance:', 'eye-book'); ?></span>
                        <span class="value"><?php echo esc_html($patient->insurance_provider); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card-actions">
                <button type="button" class="btn btn-primary edit-profile"><?php _e('Edit Profile', 'eye-book'); ?></button>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="portal-card">
            <h3><?php _e('Quick Actions', 'eye-book'); ?></h3>
            
            <div class="card-content">
                <div class="quick-actions-grid">
                    <a href="#" class="quick-action-item book-appointment">
                        <div class="action-icon">
                            <span class="dashicons dashicons-calendar-alt"></span>
                        </div>
                        <div class="action-content">
                            <h4><?php _e('Book Appointment', 'eye-book'); ?></h4>
                            <p><?php _e('Schedule a new appointment', 'eye-book'); ?></p>
                        </div>
                    </a>
                    
                    <a href="#" class="quick-action-item view-forms">
                        <div class="action-icon">
                            <span class="dashicons dashicons-media-document"></span>
                        </div>
                        <div class="action-content">
                            <h4><?php _e('Forms & Documents', 'eye-book'); ?></h4>
                            <p><?php _e('View and complete forms', 'eye-book'); ?></p>
                        </div>
                    </a>
                    
                    <a href="#" class="quick-action-item contact-clinic">
                        <div class="action-icon">
                            <span class="dashicons dashicons-phone"></span>
                        </div>
                        <div class="action-content">
                            <h4><?php _e('Contact Clinic', 'eye-book'); ?></h4>
                            <p><?php _e('Get in touch with us', 'eye-book'); ?></p>
                        </div>
                    </a>
                    
                    <a href="#" class="quick-action-item prescription-history">
                        <div class="action-icon">
                            <span class="dashicons dashicons-visibility"></span>
                        </div>
                        <div class="action-content">
                            <h4><?php _e('Prescription History', 'eye-book'); ?></h4>
                            <p><?php _e('View past prescriptions', 'eye-book'); ?></p>
                        </div>
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent History -->
        <div class="portal-card">
            <h3><?php _e('Recent Appointment History', 'eye-book'); ?></h3>
            
            <div class="card-content">
                <?php if (!empty($appointment_history)): ?>
                    <div class="history-list">
                        <?php foreach (array_slice($appointment_history, 0, 5) as $appointment): 
                            $provider = $appointment->get_provider();
                            $appointment_date = date_i18n(get_option('date_format'), strtotime($appointment->start_datetime));
                        ?>
                        <div class="history-item">
                            <div class="history-date">
                                <?php echo esc_html($appointment_date); ?>
                            </div>
                            <div class="history-details">
                                <strong><?php echo $provider ? esc_html($provider->get_display_name()) : __('Unknown Provider', 'eye-book'); ?></strong>
                                <span class="status-badge status-<?php echo esc_attr($appointment->status); ?>">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $appointment->status))); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p><?php _e('No appointment history available.', 'eye-book'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (count($appointment_history) > 5): ?>
            <div class="card-actions">
                <a href="#" class="view-all-link"><?php _e('View Complete History', 'eye-book'); ?></a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Forms & Documents -->
        <div class="portal-card">
            <h3><?php _e('Forms & Documents', 'eye-book'); ?></h3>
            
            <div class="card-content">
                <?php if (!empty($patient_forms)): ?>
                    <div class="forms-list">
                        <?php foreach (array_slice($patient_forms, 0, 3) as $form): ?>
                        <div class="form-item">
                            <div class="form-info">
                                <strong><?php echo esc_html(ucfirst(str_replace('_', ' ', $form->form_type))); ?></strong>
                                <span class="form-date">
                                    <?php echo date_i18n(get_option('date_format'), strtotime($form->completed_at)); ?>
                                </span>
                            </div>
                            <div class="form-actions">
                                <button type="button" class="btn btn-sm btn-secondary view-form" 
                                        data-form-id="<?php echo esc_attr($form->id); ?>">
                                    <?php _e('View', 'eye-book'); ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <p><?php _e('No forms completed yet.', 'eye-book'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card-actions">
                <button type="button" class="btn btn-primary complete-intake-form"><?php _e('Complete Intake Form', 'eye-book'); ?></button>
            </div>
        </div>

    </div>
</div>

<!-- Modals -->
<div id="cancel-appointment-modal" class="modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('Cancel Appointment', 'eye-book'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        
        <div class="modal-body">
            <form id="cancel-appointment-form">
                <input type="hidden" id="cancel-appointment-id" name="appointment_id">
                
                <div class="form-group">
                    <label for="cancellation-reason"><?php _e('Reason for cancellation:', 'eye-book'); ?></label>
                    <select id="cancellation-reason" name="cancellation_reason" required>
                        <option value=""><?php _e('Select a reason', 'eye-book'); ?></option>
                        <option value="schedule_conflict"><?php _e('Schedule Conflict', 'eye-book'); ?></option>
                        <option value="illness"><?php _e('Illness', 'eye-book'); ?></option>
                        <option value="emergency"><?php _e('Emergency', 'eye-book'); ?></option>
                        <option value="no_longer_needed"><?php _e('No Longer Needed', 'eye-book'); ?></option>
                        <option value="other"><?php _e('Other', 'eye-book'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="cancellation-notes"><?php _e('Additional notes (optional):', 'eye-book'); ?></label>
                    <textarea id="cancellation-notes" name="cancellation_notes" rows="3"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary modal-close"><?php _e('Keep Appointment', 'eye-book'); ?></button>
                    <button type="submit" class="btn btn-danger"><?php _e('Cancel Appointment', 'eye-book'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="edit-profile-modal" class="modal" style="display: none;">
    <div class="modal-overlay"></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('Edit Profile', 'eye-book'); ?></h3>
            <button type="button" class="modal-close">&times;</button>
        </div>
        
        <div class="modal-body">
            <form id="edit-profile-form">
                <input type="hidden" name="patient_id" value="<?php echo esc_attr($patient->id); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-first-name"><?php _e('First Name:', 'eye-book'); ?></label>
                        <input type="text" id="edit-first-name" name="first_name" 
                               value="<?php echo esc_attr($patient->first_name); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-last-name"><?php _e('Last Name:', 'eye-book'); ?></label>
                        <input type="text" id="edit-last-name" name="last_name" 
                               value="<?php echo esc_attr($patient->last_name); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit-phone"><?php _e('Phone:', 'eye-book'); ?></label>
                        <input type="tel" id="edit-phone" name="phone" class="phone-field"
                               value="<?php echo esc_attr($patient->phone); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-email"><?php _e('Email:', 'eye-book'); ?></label>
                        <input type="email" id="edit-email" name="email" 
                               value="<?php echo esc_attr($patient->email); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit-insurance-provider"><?php _e('Insurance Provider:', 'eye-book'); ?></label>
                    <input type="text" id="edit-insurance-provider" name="insurance_provider" 
                           value="<?php echo esc_attr($patient->insurance_provider ?? ''); ?>">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary modal-close"><?php _e('Cancel', 'eye-book'); ?></button>
                    <button type="submit" class="btn btn-primary"><?php _e('Save Changes', 'eye-book'); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Cancel appointment
    $('.cancel-appointment').on('click', function() {
        var appointmentId = $(this).data('appointment-id');
        $('#cancel-appointment-id').val(appointmentId);
        $('#cancel-appointment-modal').fadeIn();
    });
    
    // Submit cancellation
    $('#cancel-appointment-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.post(eyeBookPublic.ajax_url, formData + '&action=eye_book_cancel_appointment&nonce=' + eyeBookPublic.nonce)
            .done(function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'Failed to cancel appointment');
                }
            })
            .fail(function() {
                alert('Failed to cancel appointment');
            });
    });
    
    // Edit profile
    $('.edit-profile').on('click', function() {
        $('#edit-profile-modal').fadeIn();
    });
    
    // Submit profile edit
    $('#edit-profile-form').on('submit', function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.post(eyeBookPublic.ajax_url, formData + '&action=eye_book_update_profile&nonce=' + eyeBookPublic.nonce)
            .done(function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'Failed to update profile');
                }
            })
            .fail(function() {
                alert('Failed to update profile');
            });
    });
    
    // Modal close functionality
    $('.modal-close, .modal-overlay').on('click', function() {
        $('.modal').fadeOut();
    });
    
    // Book appointment redirect
    $('.book-appointment').on('click', function(e) {
        e.preventDefault();
        // Redirect to booking page or show booking form
        window.location.href = '/book-appointment/'; // This would be configured
    });
    
});
</script>