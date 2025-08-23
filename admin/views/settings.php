<?php
/**
 * Admin settings view
 *
 * @package EyeBook
 * @subpackage Admin/Views
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current tab
$active_tab = $_GET['tab'] ?? 'general';
$tabs = array(
    'general' => __('General', 'eye-book'),
    'booking' => __('Booking', 'eye-book'),
    'notifications' => __('Notifications', 'eye-book'),
    'security' => __('Security & HIPAA', 'eye-book'),
    'integrations' => __('Integrations', 'eye-book'),
    'advanced' => __('Advanced', 'eye-book')
);
?>

<div class="wrap eye-book-settings">
    <h1><?php _e('Eye-Book Settings', 'eye-book'); ?></h1>
    
    <?php settings_errors('eye_book_settings'); ?>

    <!-- Tabs Navigation -->
    <h2 class="nav-tab-wrapper">
        <?php foreach ($tabs as $tab_key => $tab_name): ?>
            <a href="<?php echo admin_url('admin.php?page=eye-book-settings&tab=' . $tab_key); ?>" 
               class="nav-tab <?php echo ($active_tab === $tab_key) ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($tab_name); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <div class="eye-book-settings-content">
        <form method="post" action="">
            <?php wp_nonce_field('eye_book_settings'); ?>
            
            <?php if ($active_tab === 'general'): ?>
                <!-- General Settings -->
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="clinic_name"><?php _e('Clinic Name', 'eye-book'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="clinic_name" name="clinic_name" 
                                       value="<?php echo esc_attr(get_option('eye_book_clinic_name', '')); ?>" 
                                       class="regular-text" />
                                <p class="description"><?php _e('The name of your practice/clinic.', 'eye-book'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="timezone"><?php _e('Timezone', 'eye-book'); ?></label>
                            </th>
                            <td>
                                <select id="timezone" name="timezone">
                                    <?php 
                                    $selected_timezone = get_option('eye_book_timezone', 'America/New_York');
                                    $timezones = array(
                                        'America/New_York' => 'Eastern Time (ET)',
                                        'America/Chicago' => 'Central Time (CT)', 
                                        'America/Denver' => 'Mountain Time (MT)',
                                        'America/Los_Angeles' => 'Pacific Time (PT)',
                                        'America/Anchorage' => 'Alaska Time (AKT)',
                                        'Pacific/Honolulu' => 'Hawaii Time (HT)'
                                    );
                                    
                                    foreach ($timezones as $tz => $label) {
                                        printf('<option value="%s" %s>%s</option>', 
                                            $tz, selected($selected_timezone, $tz, false), $label);
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e('Select your clinic\'s timezone for appointment scheduling.', 'eye-book'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="date_format"><?php _e('Date Format', 'eye-book'); ?></label>
                            </th>
                            <td>
                                <?php 
                                $date_formats = array(
                                    'F j, Y' => date('F j, Y'), // January 1, 2024
                                    'Y-m-d' => date('Y-m-d'),   // 2024-01-01
                                    'm/d/Y' => date('m/d/Y'),   // 01/01/2024
                                    'd/m/Y' => date('d/m/Y'),   // 01/01/2024
                                );
                                $selected_format = get_option('eye_book_date_format', 'F j, Y');
                                ?>
                                
                                <?php foreach ($date_formats as $format => $example): ?>
                                    <label>
                                        <input type="radio" name="date_format" value="<?php echo esc_attr($format); ?>" 
                                               <?php checked($selected_format, $format); ?> />
                                        <?php echo esc_html($example); ?>
                                    </label><br>
                                <?php endforeach; ?>
                                
                                <p class="description"><?php _e('Choose how dates appear throughout the system.', 'eye-book'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="time_format"><?php _e('Time Format', 'eye-book'); ?></label>
                            </th>
                            <td>
                                <?php 
                                $time_formats = array(
                                    'g:i a' => date('g:i a'), // 3:30 pm
                                    'H:i' => date('H:i'),     // 15:30
                                );
                                $selected_time_format = get_option('eye_book_time_format', 'g:i a');
                                ?>
                                
                                <?php foreach ($time_formats as $format => $example): ?>
                                    <label>
                                        <input type="radio" name="time_format" value="<?php echo esc_attr($format); ?>" 
                                               <?php checked($selected_time_format, $format); ?> />
                                        <?php echo esc_html($example); ?>
                                    </label><br>
                                <?php endforeach; ?>
                                
                                <p class="description"><?php _e('Choose how times appear throughout the system.', 'eye-book'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
            <?php elseif ($active_tab === 'booking'): ?>
                <!-- Booking Settings -->
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php _e('Online Booking', 'eye-book'); ?></th>
                            <td>
                                <fieldset>
                                    <label for="booking_enabled">
                                        <input type="checkbox" id="booking_enabled" name="booking_enabled" value="1" 
                                               <?php checked(get_option('eye_book_booking_enabled', 1)); ?> />
                                        <?php _e('Enable online appointment booking for patients', 'eye-book'); ?>
                                    </label>
                                    <p class="description"><?php _e('Allow patients to book appointments online through your website.', 'eye-book'); ?></p>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="booking_advance_days"><?php _e('Booking Window', 'eye-book'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="booking_advance_days" name="booking_advance_days" 
                                       value="<?php echo esc_attr(get_option('eye_book_booking_advance_days', 30)); ?>" 
                                       min="1" max="365" class="small-text" />
                                <span><?php _e('days in advance', 'eye-book'); ?></span>
                                <p class="description"><?php _e('How far in advance patients can book appointments online.', 'eye-book'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="cancellation_hours"><?php _e('Cancellation Policy', 'eye-book'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="cancellation_hours" name="cancellation_hours" 
                                       value="<?php echo esc_attr(get_option('eye_book_cancellation_hours', 24)); ?>" 
                                       min="1" max="168" class="small-text" />
                                <span><?php _e('hours notice required', 'eye-book'); ?></span>
                                <p class="description"><?php _e('Minimum notice required for appointment cancellations.', 'eye-book'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="default_appointment_duration"><?php _e('Default Duration', 'eye-book'); ?></label>
                            </th>
                            <td>
                                <select id="default_appointment_duration" name="default_appointment_duration">
                                    <?php 
                                    $durations = array(15, 30, 45, 60, 90, 120);
                                    $selected_duration = get_option('eye_book_default_appointment_duration', 30);
                                    
                                    foreach ($durations as $duration) {
                                        printf('<option value="%d" %s>%d minutes</option>', 
                                            $duration, selected($selected_duration, $duration, false), $duration);
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e('Default appointment duration when creating new appointments.', 'eye-book'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
            <?php elseif ($active_tab === 'notifications'): ?>
                <!-- Notifications Settings -->
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php _e('Email Reminders', 'eye-book'); ?></th>
                            <td>
                                <fieldset>
                                    <label for="email_reminders_enabled">
                                        <input type="checkbox" id="email_reminders_enabled" name="email_reminders_enabled" value="1" 
                                               <?php checked(get_option('eye_book_email_reminders_enabled', 1)); ?> />
                                        <?php _e('Send email appointment reminders', 'eye-book'); ?>
                                    </label>
                                    <p class="description"><?php _e('Automatically send email reminders to patients before appointments.', 'eye-book'); ?></p>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="reminder_email_hours"><?php _e('Email Reminder Timing', 'eye-book'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="reminder_email_hours" name="reminder_email_hours" 
                                       value="<?php echo esc_attr(get_option('eye_book_reminder_email_hours', 24)); ?>" 
                                       min="1" max="168" class="small-text" />
                                <span><?php _e('hours before appointment', 'eye-book'); ?></span>
                                <p class="description"><?php _e('When to send email reminders before the appointment.', 'eye-book'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('SMS Reminders', 'eye-book'); ?></th>
                            <td>
                                <fieldset>
                                    <label for="sms_reminders_enabled">
                                        <input type="checkbox" id="sms_reminders_enabled" name="sms_reminders_enabled" value="1" 
                                               <?php checked(get_option('eye_book_sms_reminders_enabled', 0)); ?> />
                                        <?php _e('Send SMS appointment reminders', 'eye-book'); ?>
                                    </label>
                                    <p class="description"><?php _e('Automatically send text message reminders (requires SMS provider setup).', 'eye-book'); ?></p>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="reminder_sms_hours"><?php _e('SMS Reminder Timing', 'eye-book'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="reminder_sms_hours" name="reminder_sms_hours" 
                                       value="<?php echo esc_attr(get_option('eye_book_reminder_sms_hours', 2)); ?>" 
                                       min="1" max="72" class="small-text" />
                                <span><?php _e('hours before appointment', 'eye-book'); ?></span>
                                <p class="description"><?php _e('When to send SMS reminders before the appointment.', 'eye-book'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
            <?php elseif ($active_tab === 'security'): ?>
                <!-- Security & HIPAA Settings -->
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row"><?php _e('HIPAA Compliance Mode', 'eye-book'); ?></th>
                            <td>
                                <fieldset>
                                    <label for="hipaa_compliance_mode">
                                        <input type="checkbox" id="hipaa_compliance_mode" name="hipaa_compliance_mode" value="1" 
                                               <?php checked(get_option('eye_book_hipaa_compliance_mode', 1)); ?> />
                                        <?php _e('Enable HIPAA compliance features', 'eye-book'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('Enables enhanced security measures, audit logging, and data encryption required for HIPAA compliance.', 'eye-book'); ?>
                                        <strong><?php _e('Recommended for all medical practices.', 'eye-book'); ?></strong>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Data Encryption', 'eye-book'); ?></th>
                            <td>
                                <fieldset>
                                    <label for="encryption_enabled">
                                        <input type="checkbox" id="encryption_enabled" name="encryption_enabled" value="1" 
                                               <?php checked(get_option('eye_book_encryption_enabled', 1)); ?> />
                                        <?php _e('Encrypt sensitive patient data', 'eye-book'); ?>
                                    </label>
                                    <p class="description"><?php _e('Encrypts PHI (Protected Health Information) in the database using AES-256 encryption.', 'eye-book'); ?></p>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="session_timeout"><?php _e('Session Timeout', 'eye-book'); ?></label>
                            </th>
                            <td>
                                <select id="session_timeout" name="session_timeout">
                                    <?php 
                                    $timeouts = array(
                                        900 => __('15 minutes', 'eye-book'),
                                        1800 => __('30 minutes', 'eye-book'),
                                        3600 => __('1 hour', 'eye-book'),
                                        7200 => __('2 hours', 'eye-book')
                                    );
                                    $selected_timeout = get_option('eye_book_session_timeout', 1800);
                                    
                                    foreach ($timeouts as $seconds => $label) {
                                        printf('<option value="%d" %s>%s</option>', 
                                            $seconds, selected($selected_timeout, $seconds, false), $label);
                                    }
                                    ?>
                                </select>
                                <p class="description"><?php _e('How long users can be inactive before being automatically logged out.', 'eye-book'); ?></p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="audit_retention_days"><?php _e('Audit Log Retention', 'eye-book'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="audit_retention_days" name="audit_retention_days" 
                                       value="<?php echo esc_attr(get_option('eye_book_audit_retention_days', 2555)); ?>" 
                                       min="365" max="3650" class="small-text" />
                                <span><?php _e('days', 'eye-book'); ?></span>
                                <p class="description">
                                    <?php _e('How long to keep audit logs. HIPAA requires 6 years minimum (2190 days).', 'eye-book'); ?>
                                    <strong><?php _e('Current setting: 7 years (2555 days)', 'eye-book'); ?></strong>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
            <?php elseif ($active_tab === 'integrations'): ?>
                <!-- Integrations Settings -->
                <div class="eye-book-integrations-grid">
                    <div class="integration-card">
                        <h3><?php _e('Payment Processing', 'eye-book'); ?></h3>
                        <p><?php _e('Connect payment gateways to collect copays and fees.', 'eye-book'); ?></p>
                        <div class="integration-status">
                            <span class="status-badge status-available"><?php _e('Available', 'eye-book'); ?></span>
                        </div>
                        <div class="integration-providers">
                            <span class="provider-badge">Stripe</span>
                            <span class="provider-badge">Square</span>
                            <span class="provider-badge">PayPal</span>
                        </div>
                        <a href="#" class="button"><?php _e('Configure', 'eye-book'); ?></a>
                    </div>
                    
                    <div class="integration-card">
                        <h3><?php _e('SMS Notifications', 'eye-book'); ?></h3>
                        <p><?php _e('Send appointment reminders via text message.', 'eye-book'); ?></p>
                        <div class="integration-status">
                            <span class="status-badge status-available"><?php _e('Available', 'eye-book'); ?></span>
                        </div>
                        <div class="integration-providers">
                            <span class="provider-badge">Twilio</span>
                        </div>
                        <a href="#" class="button"><?php _e('Configure', 'eye-book'); ?></a>
                    </div>
                    
                    <div class="integration-card">
                        <h3><?php _e('Insurance Verification', 'eye-book'); ?></h3>
                        <p><?php _e('Verify patient insurance eligibility in real-time.', 'eye-book'); ?></p>
                        <div class="integration-status">
                            <span class="status-badge status-coming-soon"><?php _e('Coming Soon', 'eye-book'); ?></span>
                        </div>
                        <div class="integration-providers">
                            <span class="provider-badge">Availity</span>
                            <span class="provider-badge">Change Healthcare</span>
                        </div>
                        <a href="#" class="button" disabled><?php _e('Coming Soon', 'eye-book'); ?></a>
                    </div>
                </div>
                
            <?php elseif ($active_tab === 'advanced'): ?>
                <!-- Advanced Settings -->
                <div class="eye-book-advanced-settings">
                    <div class="postbox">
                        <h3 class="hndle"><?php _e('Database Maintenance', 'eye-book'); ?></h3>
                        <div class="inside">
                            <p><?php _e('Perform database maintenance tasks to keep your system running smoothly.', 'eye-book'); ?></p>
                            
                            <table class="form-table">
                                <tbody>
                                    <tr>
                                        <th scope="row"><?php _e('Optimize Database', 'eye-book'); ?></th>
                                        <td>
                                            <button type="button" class="button" onclick="optimizeDatabase()">
                                                <?php _e('Optimize Tables', 'eye-book'); ?>
                                            </button>
                                            <p class="description"><?php _e('Optimize database tables for better performance.', 'eye-book'); ?></p>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th scope="row"><?php _e('Clean Up Data', 'eye-book'); ?></th>
                                        <td>
                                            <button type="button" class="button" onclick="cleanupOldData()">
                                                <?php _e('Clean Old Data', 'eye-book'); ?>
                                            </button>
                                            <p class="description"><?php _e('Remove old logs and temporary data (respects HIPAA retention requirements).', 'eye-book'); ?></p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <div class="postbox">
                        <h3 class="hndle"><?php _e('System Information', 'eye-book'); ?></h3>
                        <div class="inside">
                            <table class="widefat">
                                <tbody>
                                    <tr>
                                        <td><strong><?php _e('Plugin Version:', 'eye-book'); ?></strong></td>
                                        <td><?php echo EYE_BOOK_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php _e('Database Version:', 'eye-book'); ?></strong></td>
                                        <td><?php echo get_option('eye_book_db_version', '1.0.0'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php _e('WordPress Version:', 'eye-book'); ?></strong></td>
                                        <td><?php echo get_bloginfo('version'); ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php _e('PHP Version:', 'eye-book'); ?></strong></td>
                                        <td><?php echo PHP_VERSION; ?></td>
                                    </tr>
                                    <tr>
                                        <td><strong><?php _e('HIPAA Compliance:', 'eye-book'); ?></strong></td>
                                        <td>
                                            <?php if (get_option('eye_book_hipaa_compliance_mode', 1)): ?>
                                                <span class="status-enabled"><?php _e('Enabled', 'eye-book'); ?></span>
                                            <?php else: ?>
                                                <span class="status-disabled"><?php _e('Disabled', 'eye-book'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
            <?php endif; ?>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Changes', 'eye-book'); ?>" />
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Database optimization function
    window.optimizeDatabase = function() {
        if (!confirm('<?php _e("Are you sure you want to optimize the database? This may take a few minutes.", "eye-book"); ?>')) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'eye_book_optimize_database',
            nonce: eyeBookAdmin.nonce
        }, function(response) {
            if (response.success) {
                alert('<?php _e("Database optimization completed successfully.", "eye-book"); ?>');
            } else {
                alert('<?php _e("Database optimization failed. Please try again.", "eye-book"); ?>');
            }
        });
    };
    
    // Data cleanup function
    window.cleanupOldData = function() {
        if (!confirm('<?php _e("Are you sure you want to clean up old data? This action cannot be undone.", "eye-book"); ?>')) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'eye_book_cleanup_old_data',
            nonce: eyeBookAdmin.nonce
        }, function(response) {
            if (response.success) {
                alert('<?php _e("Data cleanup completed successfully.", "eye-book"); ?>');
            } else {
                alert('<?php _e("Data cleanup failed. Please try again.", "eye-book"); ?>');
            }
        });
    };
});
</script>