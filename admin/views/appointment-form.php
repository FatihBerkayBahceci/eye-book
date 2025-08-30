<?php
/**
 * Eye-Book Modern Appointment Form
 * Add/Edit appointment with modern UI
 *
 * @package EyeBook
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get appointment data if editing
$appointment_id = intval($_GET['id'] ?? 0);
$appointment = null;
$is_editing = false;

if ($appointment_id > 0) {
    $appointment = new Eye_Book_Appointment($appointment_id);
    $is_editing = true;
}

// Get required data for form
global $wpdb;

// Get patients
$patients = $wpdb->get_results("SELECT id, first_name, last_name, email, phone FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE status = 'active' ORDER BY last_name, first_name");

// Get providers  
$providers = $wpdb->get_results("SELECT p.id, p.wp_user_id, u.display_name, p.specialty FROM " . EYE_BOOK_TABLE_PROVIDERS . " p LEFT JOIN {$wpdb->users} u ON p.wp_user_id = u.ID ORDER BY u.display_name");

// Get locations
$locations = $wpdb->get_results("SELECT id, name, address FROM " . EYE_BOOK_TABLE_LOCATIONS . " ORDER BY name");

// Get appointment types
$appointment_types = $wpdb->get_results("SELECT id, name, duration, color FROM " . EYE_BOOK_TABLE_APPOINTMENT_TYPES . " WHERE is_active = 1 ORDER BY name");

// Get URL parameters for pre-selection
$preset_patient_id = intval($_GET['patient_id'] ?? 0);
$preset_provider_id = intval($_GET['provider_id'] ?? 0);

// Form values
$form_data = array(
    'patient_id' => $appointment ? $appointment->patient_id : ($preset_patient_id ?: ''),
    'provider_id' => $appointment ? $appointment->provider_id : ($preset_provider_id ?: ''),
    'location_id' => $appointment ? $appointment->location_id : '',
    'appointment_type_id' => $appointment ? $appointment->appointment_type_id : '',
    'start_datetime' => $appointment ? date('Y-m-d\TH:i', strtotime($appointment->start_datetime)) : '',
    'end_datetime' => $appointment ? date('Y-m-d\TH:i', strtotime($appointment->end_datetime)) : '',
    'status' => $appointment ? $appointment->status : 'scheduled',
    'notes' => $appointment ? $appointment->notes : '',
    'patient_notes' => $appointment ? $appointment->patient_notes : ''
);
?>

<!-- Modern Appointment Form -->
<div class="eye-book-page" x-data="appointmentForm()" x-init="init()">
    <!-- Header -->
    <header class="eye-book-header">
        <div class="eye-book-header-content">
            <div class="eye-book-header-left">
                <div>
                    <h1 class="eye-book-page-title">
                        <?php echo $is_editing ? __('Edit Appointment', 'eye-book') : __('New Appointment', 'eye-book'); ?>
                    </h1>
                    <div class="eye-book-header-breadcrumb">
                        <a href="<?php echo admin_url('admin.php?page=eye-book'); ?>"><?php _e('Dashboard', 'eye-book'); ?></a>
                        <span class="eye-book-header-breadcrumb-separator">•</span>
                        <a href="<?php echo admin_url('admin.php?page=eye-book-appointments'); ?>"><?php _e('Appointments', 'eye-book'); ?></a>
                        <span class="eye-book-header-breadcrumb-separator">•</span>
                        <span><?php echo $is_editing ? __('Edit', 'eye-book') : __('New', 'eye-book'); ?></span>
                    </div>
                </div>
            </div>
            <div class="eye-book-header-actions">
                <a href="<?php echo admin_url('admin.php?page=eye-book-appointments'); ?>" class="eye-book-btn eye-book-btn-secondary">
                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"></path>
                    </svg>
                    <?php _e('Back to List', 'eye-book'); ?>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="eye-book-content">
        <form @submit.prevent="submitForm" class="eye-book-appointment-form">
            <!-- Patient Selection -->
            <div class="eye-book-form-section">
                <div class="eye-book-form-section-header">
                    <svg class="eye-book-form-section-icon" width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                    </svg>
                    <div>
                        <h3 class="eye-book-form-section-title"><?php _e('Patient Information', 'eye-book'); ?></h3>
                        <p class="eye-book-form-section-subtitle"><?php _e('Select or add a new patient for this appointment', 'eye-book'); ?></p>
                    </div>
                </div>

                <div class="eye-book-form-grid cols-2">
                    <!-- Patient Selection -->
                    <div class="eye-book-form-group">
                        <label class="eye-book-form-label" for="patient_id">
                            <?php _e('Patient', 'eye-book'); ?> <span class="eye-book-form-label-required">*</span>
                        </label>
                        <div class="eye-book-select-enhanced">
                            <select id="patient_id" name="patient_id" class="eye-book-form-select" x-model="form.patient_id" required>
                                <option value=""><?php _e('Select Patient', 'eye-book'); ?></option>
                                <?php foreach ($patients as $patient): ?>
                                    <option value="<?php echo $patient->id; ?>" 
                                            data-email="<?php echo esc_attr($patient->email); ?>"
                                            data-phone="<?php echo esc_attr($patient->phone); ?>"
                                            <?php selected($form_data['patient_id'], $patient->id); ?>>
                                        <?php echo esc_html($patient->first_name . ' ' . $patient->last_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="eye-book-form-helper">
                            <?php _e('Choose an existing patient or create a new one', 'eye-book'); ?>
                        </div>
                    </div>

                    <!-- Quick Add Patient Button -->
                    <div class="eye-book-form-group" style="display: flex; align-items: end;">
                        <button type="button" class="eye-book-btn eye-book-btn-secondary" @click="showNewPatientModal = true">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"></path>
                            </svg>
                            <?php _e('New Patient', 'eye-book'); ?>
                        </button>
                    </div>
                </div>

                <!-- Selected Patient Info -->
                <div x-show="selectedPatientInfo" class="eye-book-alert eye-book-alert-info" style="margin-top: 1rem;">
                    <svg class="eye-book-alert-icon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"></path>
                    </svg>
                    <div class="eye-book-alert-content">
                        <div class="eye-book-alert-title"><?php _e('Selected Patient', 'eye-book'); ?></div>
                        <div class="eye-book-alert-message" x-text="selectedPatientInfo"></div>
                    </div>
                </div>
            </div>

            <!-- Appointment Details -->
            <div class="eye-book-form-section">
                <div class="eye-book-form-section-header">
                    <svg class="eye-book-form-section-icon" width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"></path>
                    </svg>
                    <div>
                        <h3 class="eye-book-form-section-title"><?php _e('Appointment Details', 'eye-book'); ?></h3>
                        <p class="eye-book-form-section-subtitle"><?php _e('Set the appointment type, date, time and provider', 'eye-book'); ?></p>
                    </div>
                </div>

                <div class="eye-book-form-grid cols-2">
                    <!-- Provider -->
                    <div class="eye-book-form-group">
                        <label class="eye-book-form-label" for="provider_id">
                            <?php _e('Provider', 'eye-book'); ?> <span class="eye-book-form-label-required">*</span>
                        </label>
                        <div class="eye-book-select-enhanced">
                            <select id="provider_id" name="provider_id" class="eye-book-form-select" x-model="form.provider_id" required>
                                <option value=""><?php _e('Select Provider', 'eye-book'); ?></option>
                                <?php foreach ($providers as $provider): ?>
                                    <option value="<?php echo $provider->id; ?>" <?php selected($form_data['provider_id'], $provider->id); ?>>
                                        <?php echo esc_html($provider->display_name); ?>
                                        <?php if ($provider->specialty): ?>
                                            (<?php echo esc_html($provider->specialty); ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Location -->
                    <div class="eye-book-form-group">
                        <label class="eye-book-form-label" for="location_id">
                            <?php _e('Location', 'eye-book'); ?> <span class="eye-book-form-label-required">*</span>
                        </label>
                        <div class="eye-book-select-enhanced">
                            <select id="location_id" name="location_id" class="eye-book-form-select" x-model="form.location_id" required>
                                <option value=""><?php _e('Select Location', 'eye-book'); ?></option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo $location->id; ?>" <?php selected($form_data['location_id'], $location->id); ?>>
                                        <?php echo esc_html($location->name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Appointment Type -->
                    <div class="eye-book-form-group">
                        <label class="eye-book-form-label" for="appointment_type_id">
                            <?php _e('Appointment Type', 'eye-book'); ?> <span class="eye-book-form-label-required">*</span>
                        </label>
                        <div class="eye-book-select-enhanced">
                            <select id="appointment_type_id" name="appointment_type_id" class="eye-book-form-select" x-model="form.appointment_type_id" @change="updateDuration" required>
                                <option value=""><?php _e('Select Type', 'eye-book'); ?></option>
                                <?php foreach ($appointment_types as $type): ?>
                                    <option value="<?php echo $type->id; ?>" 
                                            data-duration="<?php echo $type->duration; ?>"
                                            data-color="<?php echo esc_attr($type->color); ?>"
                                            <?php selected($form_data['appointment_type_id'], $type->id); ?>>
                                        <?php echo esc_html($type->name); ?> (<?php echo $type->duration; ?> min)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="eye-book-form-group">
                        <label class="eye-book-form-label" for="status">
                            <?php _e('Status', 'eye-book'); ?>
                        </label>
                        <div class="eye-book-select-enhanced">
                            <select id="status" name="status" class="eye-book-form-select" x-model="form.status">
                                <option value="scheduled" <?php selected($form_data['status'], 'scheduled'); ?>><?php _e('Scheduled', 'eye-book'); ?></option>
                                <option value="confirmed" <?php selected($form_data['status'], 'confirmed'); ?>><?php _e('Confirmed', 'eye-book'); ?></option>
                                <option value="in_progress" <?php selected($form_data['status'], 'in_progress'); ?>><?php _e('In Progress', 'eye-book'); ?></option>
                                <option value="completed" <?php selected($form_data['status'], 'completed'); ?>><?php _e('Completed', 'eye-book'); ?></option>
                                <option value="cancelled" <?php selected($form_data['status'], 'cancelled'); ?>><?php _e('Cancelled', 'eye-book'); ?></option>
                                <option value="no_show" <?php selected($form_data['status'], 'no_show'); ?>><?php _e('No Show', 'eye-book'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Date & Time -->
            <div class="eye-book-form-section">
                <div class="eye-book-form-section-header">
                    <svg class="eye-book-form-section-icon" width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"></path>
                    </svg>
                    <div>
                        <h3 class="eye-book-form-section-title"><?php _e('Date & Time', 'eye-book'); ?></h3>
                        <p class="eye-book-form-section-subtitle"><?php _e('Schedule the appointment date and time', 'eye-book'); ?></p>
                    </div>
                </div>

                <div class="eye-book-form-grid cols-2">
                    <!-- Start Date & Time -->
                    <div class="eye-book-form-group">
                        <label class="eye-book-form-label" for="start_datetime">
                            <?php _e('Start Date & Time', 'eye-book'); ?> <span class="eye-book-form-label-required">*</span>
                        </label>
                        <input type="datetime-local" 
                               id="start_datetime" 
                               name="start_datetime" 
                               class="eye-book-form-input" 
                               x-model="form.start_datetime"
                               @change="calculateEndTime"
                               value="<?php echo esc_attr($form_data['start_datetime']); ?>" 
                               required>
                        <div class="eye-book-form-helper">
                            <?php _e('Select the appointment start date and time', 'eye-book'); ?>
                        </div>
                    </div>

                    <!-- End Date & Time -->
                    <div class="eye-book-form-group">
                        <label class="eye-book-form-label" for="end_datetime">
                            <?php _e('End Date & Time', 'eye-book'); ?> <span class="eye-book-form-label-required">*</span>
                        </label>
                        <input type="datetime-local" 
                               id="end_datetime" 
                               name="end_datetime" 
                               class="eye-book-form-input" 
                               x-model="form.end_datetime"
                               value="<?php echo esc_attr($form_data['end_datetime']); ?>" 
                               required>
                        <div class="eye-book-form-helper" x-text="durationText">
                            <?php _e('End time will be calculated automatically', 'eye-book'); ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Time Slots -->
                <div class="eye-book-form-group" x-show="availableSlots.length > 0">
                    <label class="eye-book-form-label"><?php _e('Available Time Slots', 'eye-book'); ?></label>
                    <div class="eye-book-time-slots">
                        <template x-for="slot in availableSlots" :key="slot.time">
                            <button type="button" 
                                    class="eye-book-time-slot" 
                                    :class="{ 'active': form.start_datetime === slot.datetime }"
                                    @click="selectTimeSlot(slot)"
                                    x-text="slot.time">
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="eye-book-form-section">
                <div class="eye-book-form-section-header">
                    <svg class="eye-book-form-section-icon" width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                    </svg>
                    <div>
                        <h3 class="eye-book-form-section-title"><?php _e('Notes', 'eye-book'); ?></h3>
                        <p class="eye-book-form-section-subtitle"><?php _e('Add any additional information or notes', 'eye-book'); ?></p>
                    </div>
                </div>

                <div class="eye-book-form-grid cols-2">
                    <!-- Internal Notes -->
                    <div class="eye-book-form-group">
                        <label class="eye-book-form-label" for="notes">
                            <?php _e('Internal Notes', 'eye-book'); ?>
                        </label>
                        <textarea id="notes" 
                                  name="notes" 
                                  class="eye-book-form-textarea" 
                                  rows="4"
                                  x-model="form.notes"
                                  placeholder="<?php _e('Internal notes (not visible to patient)', 'eye-book'); ?>"><?php echo esc_textarea($form_data['notes']); ?></textarea>
                        <div class="eye-book-form-helper">
                            <?php _e('These notes are only visible to staff members', 'eye-book'); ?>
                        </div>
                    </div>

                    <!-- Patient Notes -->
                    <div class="eye-book-form-group">
                        <label class="eye-book-form-label" for="patient_notes">
                            <?php _e('Patient Notes', 'eye-book'); ?>
                        </label>
                        <textarea id="patient_notes" 
                                  name="patient_notes" 
                                  class="eye-book-form-textarea" 
                                  rows="4"
                                  x-model="form.patient_notes"
                                  placeholder="<?php _e('Patient instructions or information', 'eye-book'); ?>"><?php echo esc_textarea($form_data['patient_notes']); ?></textarea>
                        <div class="eye-book-form-helper">
                            <?php _e('These notes will be visible to the patient', 'eye-book'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="eye-book-form-section">
                <div class="eye-book-card-footer">
                    <button type="button" 
                            class="eye-book-btn eye-book-btn-secondary" 
                            @click="window.location.href = '<?php echo admin_url('admin.php?page=eye-book-appointments'); ?>'">
                        <?php _e('Cancel', 'eye-book'); ?>
                    </button>
                    <button type="submit" 
                            class="eye-book-btn eye-book-btn-primary" 
                            :disabled="isSubmitting">
                        <span x-show="!isSubmitting">
                            <?php echo $is_editing ? __('Update Appointment', 'eye-book') : __('Create Appointment', 'eye-book'); ?>
                        </span>
                        <span x-show="isSubmitting" class="eye-book-spinner">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2L9 2V6L10 6V2ZM10 18L9 18V14L10 14V18ZM18 10L18 9H14L14 10L18 10ZM2 10L2 9L6 9V10L2 10Z"></path>
                            </svg>
                            <?php _e('Saving...', 'eye-book'); ?>
                        </span>
                    </button>
                </div>
            </div>

            <!-- Hidden Fields -->
            <input type="hidden" name="action" value="<?php echo $is_editing ? 'update' : 'create'; ?>">
            <input type="hidden" name="appointment_id" value="<?php echo $appointment_id; ?>">
            <?php wp_nonce_field('eye_book_appointment_action', 'eye_book_appointment_nonce'); ?>
        </form>
    </div>
</div>

<!-- New Patient Modal -->
<div x-show="showNewPatientModal" 
     x-cloak
     class="eye-book-modal-overlay" 
     @click.self="showNewPatientModal = false">
    <div class="eye-book-modal">
        <div class="eye-book-modal-header">
            <h3 class="eye-book-modal-title"><?php _e('Add New Patient', 'eye-book'); ?></h3>
            <button type="button" class="eye-book-modal-close" @click="showNewPatientModal = false">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"></path>
                </svg>
            </button>
        </div>
        <div class="eye-book-modal-body">
            <form @submit.prevent="createNewPatient">
                <div class="eye-book-form-grid cols-2">
                    <div class="eye-book-form-group">
                        <label class="eye-book-form-label"><?php _e('First Name', 'eye-book'); ?> *</label>
                        <input type="text" x-model="newPatient.first_name" class="eye-book-form-input" required>
                    </div>
                    <div class="eye-book-form-group">
                        <label class="eye-book-form-label"><?php _e('Last Name', 'eye-book'); ?> *</label>
                        <input type="text" x-model="newPatient.last_name" class="eye-book-form-input" required>
                    </div>
                    <div class="eye-book-form-group">
                        <label class="eye-book-form-label"><?php _e('Email', 'eye-book'); ?> *</label>
                        <input type="email" x-model="newPatient.email" class="eye-book-form-input" required>
                    </div>
                    <div class="eye-book-form-group">
                        <label class="eye-book-form-label"><?php _e('Phone', 'eye-book'); ?> *</label>
                        <input type="tel" x-model="newPatient.phone" class="eye-book-form-input" required>
                    </div>
                    <div class="eye-book-form-group" style="grid-column: 1 / -1;">
                        <label class="eye-book-form-label"><?php _e('Date of Birth', 'eye-book'); ?></label>
                        <input type="date" x-model="newPatient.date_of_birth" class="eye-book-form-input">
                    </div>
                </div>
            </form>
        </div>
        <div class="eye-book-modal-footer">
            <button type="button" class="eye-book-btn eye-book-btn-secondary" @click="showNewPatientModal = false">
                <?php _e('Cancel', 'eye-book'); ?>
            </button>
            <button type="button" class="eye-book-btn eye-book-btn-primary" @click="createNewPatient" :disabled="isCreatingPatient">
                <span x-show="!isCreatingPatient"><?php _e('Add Patient', 'eye-book'); ?></span>
                <span x-show="isCreatingPatient"><?php _e('Creating...', 'eye-book'); ?></span>
            </button>
        </div>
    </div>
</div>

<script>
function appointmentForm() {
    return {
        form: {
            patient_id: '<?php echo esc_js($form_data['patient_id']); ?>',
            provider_id: '<?php echo esc_js($form_data['provider_id']); ?>',
            location_id: '<?php echo esc_js($form_data['location_id']); ?>',
            appointment_type_id: '<?php echo esc_js($form_data['appointment_type_id']); ?>',
            start_datetime: '<?php echo esc_js($form_data['start_datetime']); ?>',
            end_datetime: '<?php echo esc_js($form_data['end_datetime']); ?>',
            status: '<?php echo esc_js($form_data['status']); ?>',
            notes: '<?php echo esc_js($form_data['notes']); ?>',
            patient_notes: '<?php echo esc_js($form_data['patient_notes']); ?>'
        },
        
        newPatient: {
            first_name: '',
            last_name: '',
            email: '',
            phone: '',
            date_of_birth: ''
        },
        
        isSubmitting: false,
        isCreatingPatient: false,
        showNewPatientModal: false,
        selectedPatientInfo: '',
        availableSlots: [],
        durationText: '',
        
        init() {
            this.updatePatientInfo();
            this.calculateEndTime();
            this.loadAvailableSlots();
        },
        
        updatePatientInfo() {
            const select = document.getElementById('patient_id');
            if (select && select.selectedOptions.length > 0) {
                const option = select.selectedOptions[0];
                const email = option.dataset.email;
                const phone = option.dataset.phone;
                if (email || phone) {
                    this.selectedPatientInfo = `${email} • ${phone}`;
                }
            }
        },
        
        updateDuration() {
            const select = document.getElementById('appointment_type_id');
            if (select && select.selectedOptions.length > 0) {
                const duration = parseInt(select.selectedOptions[0].dataset.duration) || 30;
                this.calculateEndTime();
            }
        },
        
        calculateEndTime() {
            if (this.form.start_datetime) {
                const startDate = new Date(this.form.start_datetime);
                const select = document.getElementById('appointment_type_id');
                const duration = select && select.selectedOptions.length > 0 
                    ? parseInt(select.selectedOptions[0].dataset.duration) || 30 
                    : 30;
                    
                const endDate = new Date(startDate.getTime() + (duration * 60 * 1000));
                this.form.end_datetime = endDate.toISOString().slice(0, 16);
                this.durationText = `Duration: ${duration} minutes`;
            }
        },
        
        loadAvailableSlots() {
            if (this.form.provider_id && this.form.start_datetime) {
                const date = this.form.start_datetime.split('T')[0];
                // Mock available slots - in real implementation, fetch from server
                this.availableSlots = [
                    { time: '09:00 AM', datetime: `${date}T09:00` },
                    { time: '10:00 AM', datetime: `${date}T10:00` },
                    { time: '11:00 AM', datetime: `${date}T11:00` },
                    { time: '02:00 PM', datetime: `${date}T14:00` },
                    { time: '03:00 PM', datetime: `${date}T15:00` },
                    { time: '04:00 PM', datetime: `${date}T16:00` }
                ];
            }
        },
        
        selectTimeSlot(slot) {
            this.form.start_datetime = slot.datetime;
            this.calculateEndTime();
        },
        
        async submitForm() {
            this.isSubmitting = true;
            
            try {
                const formData = new FormData();
                Object.keys(this.form).forEach(key => {
                    formData.append(key, this.form[key]);
                });
                formData.append('action', 'eye_book_save_appointment');
                formData.append('appointment_id', <?php echo intval($appointment_id); ?>);
                formData.append('nonce', eyeBookAdmin.nonce);
                
                const response = await fetch(eyeBookAdmin.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    window.location.href = '<?php echo admin_url('admin.php?page=eye-book-appointments'); ?>?message=saved';
                } else {
                    alert(result.data.message || 'Error saving appointment');
                }
            } catch (error) {
                console.error('Form submission error:', error);
                alert('Network error occurred');
            } finally {
                this.isSubmitting = false;
            }
        },
        
        async createNewPatient() {
            this.isCreatingPatient = true;
            
            try {
                const formData = new FormData();
                Object.keys(this.newPatient).forEach(key => {
                    formData.append(key, this.newPatient[key]);
                });
                formData.append('action', 'eye_book_save_patient');
                formData.append('nonce', eyeBookAdmin.nonce);
                
                const response = await fetch(eyeBookAdmin.ajax_url, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Add new patient to select
                    const select = document.getElementById('patient_id');
                    const option = new Option(
                        `${this.newPatient.first_name} ${this.newPatient.last_name}`,
                        result.data.id
                    );
                    option.setAttribute('data-email', this.newPatient.email);
                    option.setAttribute('data-phone', this.newPatient.phone);
                    select.add(option);
                    
                    // Select the new patient
                    this.form.patient_id = result.data.id;
                    select.value = result.data.id;
                    
                    // Close modal and reset form
                    this.showNewPatientModal = false;
                    this.newPatient = { first_name: '', last_name: '', email: '', phone: '', date_of_birth: '' };
                    this.updatePatientInfo();
                } else {
                    alert(result.data.message || 'Error creating patient');
                }
            } catch (error) {
                console.error('Patient creation error:', error);
                alert('Network error occurred');
            } finally {
                this.isCreatingPatient = false;
            }
        }
    }
}
</script>

<style>
.eye-book-time-slots {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.eye-book-time-slot {
    padding: 0.5rem 1rem;
    background: var(--bg-primary);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all var(--transition-base);
    font-size: 14px;
    font-weight: 500;
}

.eye-book-time-slot:hover {
    border-color: var(--primary-color);
    background: var(--primary-50);
}

.eye-book-time-slot.active {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.eye-book-spinner {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.eye-book-spinner svg {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

[x-cloak] { 
    display: none !important; 
}
</style>