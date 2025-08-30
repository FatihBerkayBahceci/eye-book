<?php
/**
 * Patient Profile View
 *
 * @package EyeBook
 * @subpackage Admin/Views
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$patient_id = intval($_GET['id'] ?? 0);
if (!$patient_id || !isset($patient)) {
    wp_die(__('Patient not found', 'eye-book'));
}

$patient_name = $patient->first_name . ' ' . $patient->last_name;

// Get patient appointments
global $wpdb;
$appointments = $wpdb->get_results($wpdb->prepare(
    "SELECT a.*, pr.wp_user_id as provider_user_id, l.name as location_name
     FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a
     LEFT JOIN " . EYE_BOOK_TABLE_PROVIDERS . " pr ON a.provider_id = pr.id
     LEFT JOIN " . EYE_BOOK_TABLE_LOCATIONS . " l ON a.location_id = l.id
     WHERE a.patient_id = %d
     ORDER BY a.start_datetime DESC
     LIMIT 20",
    $patient_id
));
?>

<div class="wrap eye-book-wrap">
    <h1><?php printf(__('Patient: %s', 'eye-book'), esc_html($patient_name)); ?>
        <a href="<?php echo admin_url('admin.php?page=eye-book-patients&action=edit&id=' . $patient_id); ?>" class="page-title-action"><?php _e('Edit Patient', 'eye-book'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=eye-book-patients'); ?>" class="page-title-action"><?php _e('Back to Patients', 'eye-book'); ?></a>
    </h1>

    <div class="eye-book-patient-profile">
        <div class="patient-info-grid">
            <!-- Basic Information -->
            <div class="info-section">
                <h3><?php _e('Patient Information', 'eye-book'); ?></h3>
                <table class="patient-info-table">
                    <tr>
                        <td><strong><?php _e('Full Name', 'eye-book'); ?>:</strong></td>
                        <td><?php echo esc_html($patient_name); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Email', 'eye-book'); ?>:</strong></td>
                        <td><?php echo esc_html($patient->email ?? ''); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Phone', 'eye-book'); ?>:</strong></td>
                        <td><?php echo esc_html($patient->phone ?? ''); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Date of Birth', 'eye-book'); ?>:</strong></td>
                        <td><?php echo esc_html($patient->date_of_birth ?? ''); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Gender', 'eye-book'); ?>:</strong></td>
                        <td><?php echo esc_html($patient->gender ?? ''); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Address', 'eye-book'); ?>:</strong></td>
                        <td><?php echo nl2br(esc_html($patient->address ?? '')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Status', 'eye-book'); ?>:</strong></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($patient->status ?? 'active'); ?>">
                                <?php echo esc_html(ucfirst($patient->status ?? 'active')); ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Emergency Contact -->
            <div class="info-section">
                <h3><?php _e('Emergency Contact', 'eye-book'); ?></h3>
                <table class="patient-info-table">
                    <tr>
                        <td><strong><?php _e('Name', 'eye-book'); ?>:</strong></td>
                        <td><?php echo esc_html($patient->emergency_contact_name ?? ''); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Phone', 'eye-book'); ?>:</strong></td>
                        <td><?php echo esc_html($patient->emergency_contact_phone ?? ''); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Relationship', 'eye-book'); ?>:</strong></td>
                        <td><?php echo esc_html($patient->emergency_contact_relationship ?? ''); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Insurance Information -->
            <div class="info-section">
                <h3><?php _e('Insurance Information', 'eye-book'); ?></h3>
                <table class="patient-info-table">
                    <tr>
                        <td><strong><?php _e('Primary Insurance', 'eye-book'); ?>:</strong></td>
                        <td><?php echo esc_html($patient->insurance_primary ?? ''); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Policy Number', 'eye-book'); ?>:</strong></td>
                        <td><?php echo esc_html($patient->insurance_policy_number ?? ''); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Group Number', 'eye-book'); ?>:</strong></td>
                        <td><?php echo esc_html($patient->insurance_group_number ?? ''); ?></td>
                    </tr>
                </table>
            </div>

            <!-- Medical History -->
            <div class="info-section full-width">
                <h3><?php _e('Medical History & Notes', 'eye-book'); ?></h3>
                <div class="medical-history">
                    <?php if (!empty($patient->medical_history)): ?>
                        <p><?php echo nl2br(esc_html($patient->medical_history)); ?></p>
                    <?php else: ?>
                        <p class="no-data"><?php _e('No medical history recorded.', 'eye-book'); ?></p>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($patient->notes)): ?>
                    <h4><?php _e('Additional Notes', 'eye-book'); ?></h4>
                    <div class="patient-notes">
                        <p><?php echo nl2br(esc_html($patient->notes)); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Appointment History -->
        <div class="appointment-history">
            <h3><?php _e('Appointment History', 'eye-book'); ?></h3>
            
            <div class="appointments-actions">
                <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&action=add&patient_id=' . $patient_id); ?>" class="button button-primary">
                    <?php _e('Schedule New Appointment', 'eye-book'); ?>
                </a>
            </div>

            <?php if (!empty($appointments)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'eye-book'); ?></th>
                            <th><?php _e('Time', 'eye-book'); ?></th>
                            <th><?php _e('Provider', 'eye-book'); ?></th>
                            <th><?php _e('Location', 'eye-book'); ?></th>
                            <th><?php _e('Status', 'eye-book'); ?></th>
                            <th><?php _e('Notes', 'eye-book'); ?></th>
                            <th><?php _e('Actions', 'eye-book'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <?php
                            $provider_name = '';
                            if ($appointment->provider_user_id) {
                                $provider_user = get_userdata($appointment->provider_user_id);
                                if ($provider_user) {
                                    $provider_name = $provider_user->display_name;
                                }
                            }
                            
                            $start_date = date('Y-m-d', strtotime($appointment->start_datetime));
                            $start_time = date('H:i', strtotime($appointment->start_datetime));
                            $status_class = 'status-' . $appointment->status;
                            ?>
                            <tr>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($appointment->start_datetime))); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($appointment->start_datetime))); ?></td>
                                <td><?php echo esc_html($provider_name); ?></td>
                                <td><?php echo esc_html($appointment->location_name ?? ''); ?></td>
                                <td>
                                    <span class="status-badge <?php echo esc_attr($status_class); ?>">
                                        <?php echo esc_html(ucfirst($appointment->status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(wp_trim_words($appointment->notes ?? '', 10)); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&action=edit&id=' . $appointment->id); ?>" class="button button-small">
                                        <?php _e('Edit', 'eye-book'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-appointments">
                    <p><?php _e('No appointments found for this patient.', 'eye-book'); ?></p>
                    <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&action=add&patient_id=' . $patient_id); ?>" class="button button-primary">
                        <?php _e('Schedule First Appointment', 'eye-book'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.eye-book-patient-profile {
    background: #fff;
    padding: 20px;
    border: 1px solid #c3c4c7;
    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
}

.patient-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

.info-section {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 4px;
    border: 1px solid #ddd;
}

.info-section.full-width {
    grid-column: 1 / -1;
}

.info-section h3 {
    margin: 0 0 15px 0;
    color: #1e293b;
    font-size: 16px;
    font-weight: 600;
    border-bottom: 2px solid #0073aa;
    padding-bottom: 5px;
}

.patient-info-table {
    width: 100%;
}

.patient-info-table td {
    padding: 8px 12px;
    border-bottom: 1px solid #eee;
}

.patient-info-table td:first-child {
    width: 40%;
    background: #f5f5f5;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    text-transform: capitalize;
}

.status-active {
    background: #d1fae5;
    color: #047857;
}

.status-inactive {
    background: #fee2e2;
    color: #dc2626;
}

.status-scheduled {
    background: #dbeafe;
    color: #1d4ed8;
}

.status-completed {
    background: #d1fae5;
    color: #047857;
}

.status-cancelled {
    background: #fee2e2;
    color: #dc2626;
}

.medical-history, .patient-notes {
    background: #fff;
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    line-height: 1.6;
}

.no-data {
    color: #666;
    font-style: italic;
}

.appointment-history {
    margin-top: 30px;
}

.appointment-history h3 {
    margin: 0 0 20px 0;
    color: #1e293b;
    font-size: 18px;
    font-weight: 600;
}

.appointments-actions {
    margin-bottom: 20px;
    padding: 15px;
    background: #f0f9ff;
    border: 1px solid #0ea5e9;
    border-radius: 4px;
}

.no-appointments {
    text-align: center;
    padding: 40px 20px;
    background: #f9f9f9;
    border: 2px dashed #ccc;
    border-radius: 4px;
}

.no-appointments p {
    font-size: 16px;
    color: #666;
    margin-bottom: 20px;
}

@media (max-width: 782px) {
    .patient-info-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .patient-info-table td:first-child {
        width: 50%;
    }
}
</style>