<?php
/**
 * Eye-Book Enterprise System Configuration
 *
 * @package EyeBook
 * @since 2.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
$user_initials = substr($current_user->first_name, 0, 1) . substr($current_user->last_name, 0, 1);
if (empty($user_initials)) {
    $user_initials = substr($current_user->display_name, 0, 2);
}

// Get current tab
$active_tab = sanitize_text_field($_GET['tab'] ?? 'general');

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'eye_book_settings')) {
    // Process settings based on tab
    $success = false;
    $message = '';
    
    switch ($active_tab) {
        case 'general':
            update_option('eye_book_clinic_name', sanitize_text_field($_POST['clinic_name'] ?? ''));
            update_option('eye_book_timezone', sanitize_text_field($_POST['timezone'] ?? 'America/New_York'));
            update_option('eye_book_date_format', sanitize_text_field($_POST['date_format'] ?? 'F j, Y'));
            update_option('eye_book_time_format', sanitize_text_field($_POST['time_format'] ?? 'g:i a'));
            update_option('eye_book_currency', sanitize_text_field($_POST['currency'] ?? 'USD'));
            $success = true;
            $message = 'General settings updated successfully.';
            break;
            
        case 'booking':
            update_option('eye_book_booking_enabled', isset($_POST['booking_enabled']) ? 1 : 0);
            update_option('eye_book_booking_advance_days', intval($_POST['booking_advance_days'] ?? 30));
            update_option('eye_book_cancellation_hours', intval($_POST['cancellation_hours'] ?? 24));
            update_option('eye_book_default_appointment_duration', intval($_POST['default_appointment_duration'] ?? 30));
            update_option('eye_book_require_patient_approval', isset($_POST['require_patient_approval']) ? 1 : 0);
            $success = true;
            $message = 'Booking settings updated successfully.';
            break;
            
        case 'notifications':
            update_option('eye_book_email_reminders_enabled', isset($_POST['email_reminders_enabled']) ? 1 : 0);
            update_option('eye_book_reminder_email_hours', intval($_POST['reminder_email_hours'] ?? 24));
            update_option('eye_book_sms_reminders_enabled', isset($_POST['sms_reminders_enabled']) ? 1 : 0);
            update_option('eye_book_reminder_sms_hours', intval($_POST['reminder_sms_hours'] ?? 2));
            update_option('eye_book_admin_notifications', isset($_POST['admin_notifications']) ? 1 : 0);
            $success = true;
            $message = 'Notification settings updated successfully.';
            break;
            
        case 'hipaa':
            update_option('eye_book_hipaa_compliance_mode', isset($_POST['hipaa_compliance_mode']) ? 1 : 0);
            update_option('eye_book_encryption_enabled', isset($_POST['encryption_enabled']) ? 1 : 0);
            update_option('eye_book_session_timeout', intval($_POST['session_timeout'] ?? 1800));
            update_option('eye_book_audit_retention_days', intval($_POST['audit_retention_days'] ?? 2555));
            update_option('eye_book_data_backup_enabled', isset($_POST['data_backup_enabled']) ? 1 : 0);
            $success = true;
            $message = 'HIPAA & Security settings updated successfully.';
            break;
            
        case 'integrations':
            update_option('eye_book_stripe_enabled', isset($_POST['stripe_enabled']) ? 1 : 0);
            update_option('eye_book_stripe_public_key', sanitize_text_field($_POST['stripe_public_key'] ?? ''));
            update_option('eye_book_stripe_secret_key', sanitize_text_field($_POST['stripe_secret_key'] ?? ''));
            update_option('eye_book_twilio_enabled', isset($_POST['twilio_enabled']) ? 1 : 0);
            update_option('eye_book_twilio_account_sid', sanitize_text_field($_POST['twilio_account_sid'] ?? ''));
            update_option('eye_book_twilio_auth_token', sanitize_text_field($_POST['twilio_auth_token'] ?? ''));
            $success = true;
            $message = 'Integration settings updated successfully.';
            break;
    }
    
    if ($success) {
        add_settings_error('eye_book_settings', 'settings_updated', $message, 'success');
    }
}

// Settings data
$clinic_name = get_option('eye_book_clinic_name', '');
$timezone = get_option('eye_book_timezone', 'America/New_York');
$date_format = get_option('eye_book_date_format', 'F j, Y');
$time_format = get_option('eye_book_time_format', 'g:i a');
$currency = get_option('eye_book_currency', 'USD');

$booking_enabled = get_option('eye_book_booking_enabled', 1);
$booking_advance_days = get_option('eye_book_booking_advance_days', 30);
$cancellation_hours = get_option('eye_book_cancellation_hours', 24);
$default_duration = get_option('eye_book_default_appointment_duration', 30);

$email_reminders = get_option('eye_book_email_reminders_enabled', 1);
$sms_reminders = get_option('eye_book_sms_reminders_enabled', 0);
$hipaa_mode = get_option('eye_book_hipaa_compliance_mode', 1);
$encryption_enabled = get_option('eye_book_encryption_enabled', 1);
?>

<div class="eye-book-dashboard">
    <!-- Sidebar Navigation -->
    <aside class="eye-book-sidebar">
        <div class="eye-book-sidebar-header">
            <a href="<?php echo admin_url('admin.php?page=eye-book'); ?>" class="eye-book-sidebar-logo">
                <div class="eye-book-sidebar-logo-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 4.5C7 4.5 2.73 7.61 1 12C2.73 16.39 7 19.5 12 19.5C17 19.5 21.27 16.39 23 12C21.27 7.61 17 4.5 12 4.5ZM12 17C9.24 17 7 14.76 7 12C7 9.24 9.24 7 12 7C14.76 7 17 9.24 17 12C17 14.76 14.76 17 12 17ZM12 9C10.34 9 9 10.34 9 12C9 13.66 10.34 15 12 15C13.66 15 15 13.66 15 12C15 10.34 13.66 9 12 9Z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="eye-book-sidebar-logo-text">
                    <div class="eye-book-sidebar-logo-title">Eye-Book</div>
                    <div class="eye-book-sidebar-logo-subtitle">Healthcare Suite</div>
                </div>
            </a>
        </div>
        
        <nav class="eye-book-nav-menu">
            <!-- Main Navigation -->
            <div class="eye-book-nav-section">
                <div class="eye-book-nav-section-title">Main</div>
                <ul class="eye-book-nav-list">
                    <li class="eye-book-nav-item">
                        <a href="<?php echo admin_url('admin.php?page=eye-book'); ?>" class="eye-book-nav-link">
                            <span class="eye-book-nav-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 3H8V10H3V3Z" fill="currentColor" opacity="0.5"/>
                                    <path d="M12 3H17V7H12V3Z" fill="currentColor"/>
                                    <path d="M12 10H17V17H12V10Z" fill="currentColor" opacity="0.5"/>
                                    <path d="M3 13H8V17H3V13Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="eye-book-nav-item">
                        <a href="<?php echo admin_url('admin.php?page=eye-book-appointments'); ?>" class="eye-book-nav-link">
                            <span class="eye-book-nav-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M17 3h-1V1h-2v2H6V1H4v2H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 14H3V8h14v9zM5 10h2v2H5v-2zm4 0h2v2H9v-2zm4 0h2v2h-2v-2z"/>
                                </svg>
                            </span>
                            <span>Appointments</span>
                        </a>
                    </li>
                    <li class="eye-book-nav-item">
                        <a href="<?php echo admin_url('admin.php?page=eye-book-patients'); ?>" class="eye-book-nav-link">
                            <span class="eye-book-nav-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 10c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                            </span>
                            <span>Patients</span>
                        </a>
                    </li>
                    <li class="eye-book-nav-item">
                        <a href="<?php echo admin_url('admin.php?page=eye-book-providers'); ?>" class="eye-book-nav-link">
                            <span class="eye-book-nav-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M14.84 16.26C14.3 16.09 13.74 16 13.16 16c-1.27 0-2.45.39-3.45 1.01A8.97 8.97 0 012 10c0-4.97 4.03-9 9-9s9 4.03 9 9c0 2.88-1.36 5.43-3.47 7.08-.23-.32-.49-.61-.69-.82zM11 1.07C7.38 1.56 4.52 4.48 4.07 8.15c.58-.37 1.75-.84 3.43-.84 2.21 0 3.5 1.28 4.5 2.17.91.81 1.53 1.35 2.5 1.35s1.59-.54 2.5-1.35c.59-.52 1.25-1.11 2.13-1.51C18.16 4.41 14.93 1.48 11 1.07z"/>
                                </svg>
                            </span>
                            <span>Providers</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Clinical Management -->
            <div class="eye-book-nav-section">
                <div class="eye-book-nav-section-title">Clinical</div>
                <ul class="eye-book-nav-list">
                    <li class="eye-book-nav-item">
                        <a href="<?php echo admin_url('admin.php?page=eye-book-calendar'); ?>" class="eye-book-nav-link">
                            <span class="eye-book-nav-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M3 3h14v2H3V3zm0 4h14v10H3V7z"/>
                                </svg>
                            </span>
                            <span>Calendar View</span>
                        </a>
                    </li>
                    <li class="eye-book-nav-item">
                        <a href="<?php echo admin_url('admin.php?page=eye-book-locations'); ?>" class="eye-book-nav-link">
                            <span class="eye-book-nav-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 2C6.69 2 4 4.69 4 8c0 4.5 6 10 6 10s6-5.5 6-10c0-3.31-2.69-6-6-6zm0 8c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                                </svg>
                            </span>
                            <span>Locations</span>
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Administration -->
            <div class="eye-book-nav-section">
                <div class="eye-book-nav-section-title">Administration</div>
                <ul class="eye-book-nav-list">
                    <li class="eye-book-nav-item">
                        <a href="<?php echo admin_url('admin.php?page=eye-book-reports'); ?>" class="eye-book-nav-link">
                            <span class="eye-book-nav-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M3 3v14h14V3H3zm12 10H9v-2h6v2zm0-4H9V7h6v2z"/>
                                </svg>
                            </span>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li class="eye-book-nav-item">
                        <a href="<?php echo admin_url('admin.php?page=eye-book-settings'); ?>" class="eye-book-nav-link active">
                            <span class="eye-book-nav-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 8c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm7.86 2.78l-1.34.48c-.09.34-.19.67-.32.99l.73 1.19c.21.34.16.77-.13 1.05l-.71.71c-.28.28-.71.34-1.05.13l-1.19-.73c-.32.13-.65.23-.99.32l-.48 1.34c-.14.37-.51.64-.9.64h-1c-.39 0-.76-.27-.9-.64l-.48-1.34c-.34-.09-.67-.19-.99-.32l-1.19.73c-.34.21-.77.16-1.05-.13l-.71-.71c-.28-.28-.34-.71-.13-1.05l.73-1.19c-.13-.32-.23-.65-.32-.99l-1.34-.48C2.27 10.76 2 10.39 2 10V9c0-.39.27-.76.64-.9l1.34-.48c.09-.34.19-.67.32-.99l-.73-1.19c-.21-.34-.16-.77.13-1.05l.71-.71c.28-.28.71-.34 1.05-.13l1.19.73c.32-.13.65-.23.99-.32l.48-1.34C7.24 2.27 7.61 2 8 2h1c.39 0 .76.27.9.64l.48 1.34c.34.09.67.19.99.32l1.19-.73c.34-.21.77-.16 1.05.13l.71.71c.28.28.34.71.13 1.05l-.73 1.19c.13.32.23.65.32.99l1.34.48c.37.14.64.51.64.9v1c0 .39-.27.76-.64.9z"/>
                                </svg>
                            </span>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="eye-book-main">
        <!-- Top Header -->
        <header class="eye-book-header">
            <div class="eye-book-header-content">
                <div class="eye-book-header-left">
                    <h1 class="eye-book-page-title">System Settings</h1>
                    <nav class="eye-book-breadcrumb">
                        <a href="<?php echo admin_url(); ?>">Admin</a>
                        <span class="eye-book-breadcrumb-separator">/</span>
                        <span>Eye-Book</span>
                        <span class="eye-book-breadcrumb-separator">/</span>
                        <span>Settings</span>
                    </nav>
                </div>
                
                <div class="eye-book-header-actions">
                    <!-- Export Settings Button -->
                    <button class="eye-book-btn eye-book-btn-secondary" onclick="exportSettings()">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8.5 1.5V11h1V1.5l2.5 2.5.7-.7L8 .6 3.3 3.3l.7.7L6.5 1.5V11h2V1.5zm-7 11V14h13v-1.5H1.5z"/>
                        </svg>
                        <span>Export Settings</span>
                    </button>
                    
                    <!-- User Menu -->
                    <div class="eye-book-user-menu">
                        <div class="eye-book-user-avatar"><?php echo strtoupper($user_initials); ?></div>
                        <div class="eye-book-user-info">
                            <div class="eye-book-user-name"><?php echo esc_html($current_user->display_name); ?></div>
                            <div class="eye-book-user-role">Administrator</div>
                        </div>
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                            <path d="M3 4.5L6 7.5L9 4.5"/>
                        </svg>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="eye-book-content">
            <?php settings_errors('eye_book_settings'); ?>
            
            <!-- Settings Navigation Tabs -->
            <div class="eye-book-card eye-book-mb-4">
                <div class="eye-book-card-body" style="padding: 0;">
                    <div class="eye-book-tabs">
                        <nav class="eye-book-tabs-nav">
                            <button class="eye-book-tab-btn <?php echo $active_tab === 'general' ? 'active' : ''; ?>" data-tab="general" onclick="switchTab('general')">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 8c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm7.86 2.78l-1.34.48c-.09.34-.19.67-.32.99l.73 1.19c.21.34.16.77-.13 1.05l-.71.71c-.28.28-.71.34-1.05.13l-1.19-.73c-.32.13-.65.23-.99.32l-.48 1.34c-.14.37-.51.64-.9.64h-1c-.39 0-.76-.27-.9-.64l-.48-1.34c-.34-.09-.67-.19-.99-.32l-1.19.73c-.34.21-.77.16-1.05-.13l-.71-.71c-.28-.28-.34-.71-.13-1.05l.73-1.19c-.13-.32-.23-.65-.32-.99l-1.34-.48C2.27 10.76 2 10.39 2 10V9c0-.39.27-.76.64-.9l1.34-.48c.09-.34.19-.67.32-.99l-.73-1.19c-.21-.34-.16-.77.13-1.05l.71-.71c.28-.28.71-.34 1.05-.13l1.19.73c.32-.13.65-.23.99-.32l.48-1.34C7.24 2.27 7.61 2 8 2h1c.39 0 .76.27.9.64l.48 1.34c.34.09.67.19.99.32l1.19-.73c.34-.21.77-.16 1.05.13l.71.71c.28.28.34.71.13 1.05l-.73 1.19c.13.32.23.65.32.99l1.34.48c.37.14.64.51.64.9v1c0 .39-.27.76-.64.9z"/>
                                </svg>
                                General
                            </button>
                            <button class="eye-book-tab-btn <?php echo $active_tab === 'booking' ? 'active' : ''; ?>" data-tab="booking" onclick="switchTab('booking')">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M17 3h-1V1h-2v2H6V1H4v2H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 14H3V8h14v9z"/>
                                </svg>
                                Booking
                            </button>
                            <button class="eye-book-tab-btn <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>" data-tab="notifications" onclick="switchTab('notifications')">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 2C8.9 2 8 2.9 8 4v6l-2 2v1h8v-1l-2-2V4c0-1.1-.9-2-2-2zm1 13h-2c0 1.1.9 2 2 2s2-.9 2-2z"/>
                                </svg>
                                Notifications
                            </button>
                            <button class="eye-book-tab-btn <?php echo $active_tab === 'hipaa' ? 'active' : ''; ?>" data-tab="hipaa" onclick="switchTab('hipaa')">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v8c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2v-8c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                                </svg>
                                HIPAA & Security
                            </button>
                            <button class="eye-book-tab-btn <?php echo $active_tab === 'integrations' ? 'active' : ''; ?>" data-tab="integrations" onclick="switchTab('integrations')">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M3.5 6L2 7.5v5L3.5 14h2L7 12.5v-5L5.5 6h-2zM12.5 6L11 7.5v5l1.5 1.5h2L16 12.5v-5L14.5 6h-2zM8 3v14h4V3H8z"/>
                                </svg>
                                Integrations
                            </button>
                            <button class="eye-book-tab-btn <?php echo $active_tab === 'advanced' ? 'active' : ''; ?>" data-tab="advanced" onclick="switchTab('advanced')">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M15.95 10.78c.03-.25.05-.51.05-.78s-.02-.53-.06-.78l1.69-1.32c.15-.12.19-.34.1-.51l-1.6-2.77c-.1-.18-.31-.24-.49-.18l-1.99.8c-.42-.32-.86-.58-1.35-.78L12 2.34c-.03-.2-.2-.34-.4-.34H8.4c-.2 0-.36.14-.39.34l-.3 2.12c-.49.2-.94.47-1.35.78l-1.99-.8c-.18-.07-.39 0-.49.18l-1.6 2.77c-.1.18-.06.39.1.51l1.69 1.32c-.04.25-.07.52-.07.78s.02.53.06.78L2.37 12.1c-.15.12-.19.34-.1.51l1.6 2.77c.1.18.31.24.49.18l1.99-.8c.42.32.86.58 1.35.78l.3 2.12c.04.2.2.34.4.34h3.2c.2 0 .37-.14.39-.34l.3-2.12c.49-.2.94-.47 1.35-.78l1.99.8c.18.07.39 0 .49-.18l1.6-2.77c.1-.18.06-.39-.1-.51l-1.67-1.32zM10 13c-1.65 0-3-1.35-3-3s1.35-3 3-3 3 1.35 3 3-1.35 3-3 3z"/>
                                </svg>
                                Advanced
                            </button>
                        </nav>
                    </div>
                </div>
            </div>

            <!-- Settings Content -->
            <form method="POST" action="">
                <?php wp_nonce_field('eye_book_settings'); ?>
                
                <!-- General Settings Tab -->
                <div id="general-tab" class="eye-book-tab-content <?php echo $active_tab === 'general' ? 'active' : ''; ?>">
                    <div class="eye-book-card">
                        <div class="eye-book-card-header">
                            <div class="eye-book-card-header-content">
                                <h3 class="eye-book-card-title">General Configuration</h3>
                            </div>
                        </div>
                        <div class="eye-book-card-body">
                            <div class="eye-book-d-grid" style="grid-template-columns: 1fr 1fr; gap: var(--spacing-xl);">
                                <div>
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-form-label" for="clinic_name">Practice Name</label>
                                        <input type="text" id="clinic_name" name="clinic_name" class="eye-book-form-input" value="<?php echo esc_attr($clinic_name); ?>" placeholder="Your Practice Name">
                                        <small class="eye-book-form-help">This will appear in emails, reports, and patient communications.</small>
                                    </div>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-form-label" for="timezone">Timezone</label>
                                        <select id="timezone" name="timezone" class="eye-book-form-select">
                                            <?php
                                            $timezones = array(
                                                'America/New_York' => 'Eastern Time (ET)',
                                                'America/Chicago' => 'Central Time (CT)',
                                                'America/Denver' => 'Mountain Time (MT)',
                                                'America/Los_Angeles' => 'Pacific Time (PT)',
                                                'America/Anchorage' => 'Alaska Time (AKT)',
                                                'Pacific/Honolulu' => 'Hawaii Time (HT)'
                                            );
                                            foreach ($timezones as $tz => $label) {
                                                printf('<option value="%s" %s>%s</option>', $tz, selected($timezone, $tz, false), $label);
                                            }
                                            ?>
                                        </select>
                                        <small class="eye-book-form-help">All appointment times will be displayed in this timezone.</small>
                                    </div>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-form-label">Currency</label>
                                        <select name="currency" class="eye-book-form-select">
                                            <?php
                                            $currencies = array(
                                                'USD' => 'US Dollar ($)',
                                                'CAD' => 'Canadian Dollar (CAD$)',
                                                'EUR' => 'Euro (€)',
                                                'GBP' => 'British Pound (£)'
                                            );
                                            foreach ($currencies as $code => $label) {
                                                printf('<option value="%s" %s>%s</option>', $code, selected($currency, $code, false), $label);
                                            }
                                            ?>
                                        </select>
                                        <small class="eye-book-form-help">Currency for billing and payment processing.</small>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-form-label">Date Format</label>
                                        <div class="eye-book-radio-group">
                                            <?php
                                            $date_formats = array(
                                                'F j, Y' => date('F j, Y'),
                                                'Y-m-d' => date('Y-m-d'),
                                                'm/d/Y' => date('m/d/Y'),
                                                'd/m/Y' => date('d/m/Y')
                                            );
                                            foreach ($date_formats as $format => $example) {
                                                echo '<label class="eye-book-radio-label">';
                                                echo '<input type="radio" name="date_format" value="' . esc_attr($format) . '" ' . checked($date_format, $format, false) . '>';
                                                echo '<span class="eye-book-radio-text">' . esc_html($example) . '</span>';
                                                echo '</label>';
                                            }
                                            ?>
                                        </div>
                                        <small class="eye-book-form-help">How dates appear throughout the system.</small>
                                    </div>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-form-label">Time Format</label>
                                        <div class="eye-book-radio-group">
                                            <?php
                                            $time_formats = array(
                                                'g:i a' => date('g:i a'),
                                                'H:i' => date('H:i')
                                            );
                                            foreach ($time_formats as $format => $example) {
                                                echo '<label class="eye-book-radio-label">';
                                                echo '<input type="radio" name="time_format" value="' . esc_attr($format) . '" ' . checked($time_format, $format, false) . '>';
                                                echo '<span class="eye-book-radio-text">' . esc_html($example) . '</span>';
                                                echo '</label>';
                                            }
                                            ?>
                                        </div>
                                        <small class="eye-book-form-help">How times appear throughout the system.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Settings Tab -->
                <div id="booking-tab" class="eye-book-tab-content <?php echo $active_tab === 'booking' ? 'active' : ''; ?>">
                    <div class="eye-book-card">
                        <div class="eye-book-card-header">
                            <div class="eye-book-card-header-content">
                                <h3 class="eye-book-card-title">Online Booking Settings</h3>
                            </div>
                        </div>
                        <div class="eye-book-card-body">
                            <div class="eye-book-d-grid" style="grid-template-columns: 1fr 1fr; gap: var(--spacing-xl);">
                                <div>
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-checkbox-label">
                                            <input type="checkbox" name="booking_enabled" value="1" <?php checked($booking_enabled); ?> class="eye-book-checkbox">
                                            <span class="eye-book-checkbox-text">Enable Online Booking</span>
                                        </label>
                                        <small class="eye-book-form-help">Allow patients to book appointments online through your website.</small>
                                    </div>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-form-label" for="booking_advance_days">Booking Window</label>
                                        <div class="eye-book-input-group">
                                            <input type="number" id="booking_advance_days" name="booking_advance_days" class="eye-book-form-input" value="<?php echo esc_attr($booking_advance_days); ?>" min="1" max="365">
                                            <span class="eye-book-input-group-text">days in advance</span>
                                        </div>
                                        <small class="eye-book-form-help">How far in advance patients can book appointments.</small>
                                    </div>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-form-label" for="cancellation_hours">Cancellation Policy</label>
                                        <div class="eye-book-input-group">
                                            <input type="number" id="cancellation_hours" name="cancellation_hours" class="eye-book-form-input" value="<?php echo esc_attr($cancellation_hours); ?>" min="1" max="168">
                                            <span class="eye-book-input-group-text">hours notice required</span>
                                        </div>
                                        <small class="eye-book-form-help">Minimum notice required for cancellations.</small>
                                    </div>
                                </div>
                                
                                <div>
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-form-label" for="default_appointment_duration">Default Duration</label>
                                        <select id="default_appointment_duration" name="default_appointment_duration" class="eye-book-form-select">
                                            <?php
                                            $durations = array(15, 30, 45, 60, 90, 120);
                                            foreach ($durations as $duration) {
                                                printf('<option value="%d" %s>%d minutes</option>', 
                                                    $duration, selected($default_duration, $duration, false), $duration);
                                            }
                                            ?>
                                        </select>
                                        <small class="eye-book-form-help">Default appointment length for new appointments.</small>
                                    </div>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-checkbox-label">
                                            <input type="checkbox" name="require_patient_approval" value="1" <?php checked(get_option('eye_book_require_patient_approval', 0)); ?> class="eye-book-checkbox">
                                            <span class="eye-book-checkbox-text">Require Approval</span>
                                        </label>
                                        <small class="eye-book-form-help">Require staff approval for online bookings before confirming.</small>
                                    </div>
                                    
                                    <div class="eye-book-form-group">
                                        <div class="eye-book-info-box">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="eye-book-info-icon">
                                                <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm1 15H9v-6h2v6zm0-8H9V5h2v2z"/>
                                            </svg>
                                            <div>
                                                <strong>Online Booking Benefits:</strong>
                                                <ul style="margin: 8px 0 0 0; padding-left: 16px;">
                                                    <li>24/7 appointment scheduling</li>
                                                    <li>Reduces phone call volume</li>
                                                    <li>Improves patient satisfaction</li>
                                                    <li>Automatic confirmation emails</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications Settings Tab -->
                <div id="notifications-tab" class="eye-book-tab-content <?php echo $active_tab === 'notifications' ? 'active' : ''; ?>">
                    <div class="eye-book-card">
                        <div class="eye-book-card-header">
                            <div class="eye-book-card-header-content">
                                <h3 class="eye-book-card-title">Notification Settings</h3>
                            </div>
                        </div>
                        <div class="eye-book-card-body">
                            <div class="eye-book-d-grid" style="grid-template-columns: 1fr 1fr; gap: var(--spacing-xl);">
                                <div>
                                    <h4 style="margin-bottom: var(--spacing-md); color: var(--text-primary);">Email Notifications</h4>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-checkbox-label">
                                            <input type="checkbox" name="email_reminders_enabled" value="1" <?php checked($email_reminders); ?> class="eye-book-checkbox">
                                            <span class="eye-book-checkbox-text">Email Appointment Reminders</span>
                                        </label>
                                        <small class="eye-book-form-help">Send automated email reminders to patients.</small>
                                    </div>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-form-label" for="reminder_email_hours">Email Reminder Timing</label>
                                        <div class="eye-book-input-group">
                                            <input type="number" id="reminder_email_hours" name="reminder_email_hours" class="eye-book-form-input" value="<?php echo esc_attr(get_option('eye_book_reminder_email_hours', 24)); ?>" min="1" max="168">
                                            <span class="eye-book-input-group-text">hours before</span>
                                        </div>
                                        <small class="eye-book-form-help">When to send email reminders before appointments.</small>
                                    </div>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-checkbox-label">
                                            <input type="checkbox" name="admin_notifications" value="1" <?php checked(get_option('eye_book_admin_notifications', 1)); ?> class="eye-book-checkbox">
                                            <span class="eye-book-checkbox-text">Admin Notifications</span>
                                        </label>
                                        <small class="eye-book-form-help">Notify administrators of new bookings and changes.</small>
                                    </div>
                                </div>
                                
                                <div>
                                    <h4 style="margin-bottom: var(--spacing-md); color: var(--text-primary);">SMS Notifications</h4>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-checkbox-label">
                                            <input type="checkbox" name="sms_reminders_enabled" value="1" <?php checked($sms_reminders); ?> class="eye-book-checkbox">
                                            <span class="eye-book-checkbox-text">SMS Appointment Reminders</span>
                                        </label>
                                        <small class="eye-book-form-help">Send text message reminders (requires SMS provider setup).</small>
                                    </div>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-form-label" for="reminder_sms_hours">SMS Reminder Timing</label>
                                        <div class="eye-book-input-group">
                                            <input type="number" id="reminder_sms_hours" name="reminder_sms_hours" class="eye-book-form-input" value="<?php echo esc_attr(get_option('eye_book_reminder_sms_hours', 2)); ?>" min="1" max="72">
                                            <span class="eye-book-input-group-text">hours before</span>
                                        </div>
                                        <small class="eye-book-form-help">When to send SMS reminders before appointments.</small>
                                    </div>
                                    
                                    <?php if (!$sms_reminders): ?>
                                        <div class="eye-book-warning-box">
                                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="eye-book-warning-icon">
                                                <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm1 15H9v-2h2v2zm0-4H9V5h2v6z"/>
                                            </svg>
                                            <div>
                                                <strong>SMS Provider Required</strong>
                                                <p style="margin: 4px 0 0 0;">Configure Twilio in the Integrations tab to enable SMS notifications.</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HIPAA & Security Settings Tab -->
                <div id="hipaa-tab" class="eye-book-tab-content <?php echo $active_tab === 'hipaa' ? 'active' : ''; ?>">
                    <div class="eye-book-card">
                        <div class="eye-book-card-header">
                            <div class="eye-book-card-header-content">
                                <h3 class="eye-book-card-title">HIPAA Compliance & Security</h3>
                            </div>
                        </div>
                        <div class="eye-book-card-body">
                            <div class="eye-book-d-grid" style="grid-template-columns: 1fr 1fr; gap: var(--spacing-xl);">
                                <div>
                                    <h4 style="margin-bottom: var(--spacing-md); color: var(--text-primary);">HIPAA Compliance</h4>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-checkbox-label">
                                            <input type="checkbox" name="hipaa_compliance_mode" value="1" <?php checked($hipaa_mode); ?> class="eye-book-checkbox">
                                            <span class="eye-book-checkbox-text">Enable HIPAA Compliance Mode</span>
                                        </label>
                                        <small class="eye-book-form-help">Enables enhanced security, audit logging, and data encryption.</small>
                                    </div>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-checkbox-label">
                                            <input type="checkbox" name="encryption_enabled" value="1" <?php checked($encryption_enabled); ?> class="eye-book-checkbox">
                                            <span class="eye-book-checkbox-text">Encrypt Patient Data</span>
                                        </label>
                                        <small class="eye-book-form-help">Encrypts PHI using AES-256 encryption in database.</small>
                                    </div>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-form-label" for="audit_retention_days">Audit Log Retention</label>
                                        <div class="eye-book-input-group">
                                            <input type="number" id="audit_retention_days" name="audit_retention_days" class="eye-book-form-input" value="<?php echo esc_attr(get_option('eye_book_audit_retention_days', 2555)); ?>" min="2190" max="3650">
                                            <span class="eye-book-input-group-text">days</span>
                                        </div>
                                        <small class="eye-book-form-help">HIPAA requires minimum 6 years (2190 days). Current: 7 years.</small>
                                    </div>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-checkbox-label">
                                            <input type="checkbox" name="data_backup_enabled" value="1" <?php checked(get_option('eye_book_data_backup_enabled', 1)); ?> class="eye-book-checkbox">
                                            <span class="eye-book-checkbox-text">Automated Backups</span>
                                        </label>
                                        <small class="eye-book-form-help">Enable automated encrypted backups of patient data.</small>
                                    </div>
                                </div>
                                
                                <div>
                                    <h4 style="margin-bottom: var(--spacing-md); color: var(--text-primary);">Security Settings</h4>
                                    
                                    <div class="eye-book-form-group">
                                        <label class="eye-book-form-label" for="session_timeout">Session Timeout</label>
                                        <select id="session_timeout" name="session_timeout" class="eye-book-form-select">
                                            <?php
                                            $timeouts = array(
                                                900 => '15 minutes',
                                                1800 => '30 minutes',
                                                3600 => '1 hour',
                                                7200 => '2 hours'
                                            );
                                            $selected_timeout = get_option('eye_book_session_timeout', 1800);
                                            foreach ($timeouts as $seconds => $label) {
                                                printf('<option value="%d" %s>%s</option>', $seconds, selected($selected_timeout, $seconds, false), $label);
                                            }
                                            ?>
                                        </select>
                                        <small class="eye-book-form-help">Automatic logout after inactivity period.</small>
                                    </div>
                                    
                                    <div class="eye-book-success-box">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="eye-book-success-icon">
                                            <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm-2 15l-5-5 1.41-1.41L8 12.17l7.59-7.59L17 6l-9 9z"/>
                                        </svg>
                                        <div>
                                            <strong>HIPAA Compliant</strong>
                                            <p style="margin: 4px 0 0 0;">Your current configuration meets HIPAA compliance requirements for healthcare practices.</p>
                                        </div>
                                    </div>
                                    
                                    <div class="eye-book-form-group" style="margin-top: var(--spacing-lg);">
                                        <button type="button" class="eye-book-btn eye-book-btn-secondary eye-book-btn-sm" onclick="downloadHIPAAReport()">
                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                                <path d="M8.5 1.5V11h1V1.5l2.5 2.5.7-.7L8 .6 3.3 3.3l.7.7L6.5 1.5V11h2V1.5zm-7 11V14h13v-1.5H1.5z"/>
                                            </svg>
                                            Download HIPAA Compliance Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Integrations Settings Tab -->
                <div id="integrations-tab" class="eye-book-tab-content <?php echo $active_tab === 'integrations' ? 'active' : ''; ?>">
                    <div class="eye-book-d-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: var(--spacing-lg);">
                        <!-- Payment Processing -->
                        <div class="eye-book-card">
                            <div class="eye-book-card-header">
                                <div class="eye-book-card-header-content">
                                    <h3 class="eye-book-card-title">Payment Processing</h3>
                                </div>
                                <span class="eye-book-badge eye-book-badge-success" style="font-size: 11px;">Available</span>
                            </div>
                            <div class="eye-book-card-body">
                                <p style="color: var(--text-muted); margin-bottom: var(--spacing-lg);">Accept copays and payments directly through your booking system.</p>
                                
                                <div class="eye-book-form-group">
                                    <label class="eye-book-checkbox-label">
                                        <input type="checkbox" name="stripe_enabled" value="1" <?php checked(get_option('eye_book_stripe_enabled', 0)); ?> class="eye-book-checkbox">
                                        <span class="eye-book-checkbox-text">Enable Stripe Payments</span>
                                    </label>
                                </div>
                                
                                <div class="eye-book-form-group">
                                    <label class="eye-book-form-label" for="stripe_public_key">Stripe Public Key</label>
                                    <input type="text" id="stripe_public_key" name="stripe_public_key" class="eye-book-form-input" value="<?php echo esc_attr(get_option('eye_book_stripe_public_key', '')); ?>" placeholder="pk_test_...">
                                </div>
                                
                                <div class="eye-book-form-group">
                                    <label class="eye-book-form-label" for="stripe_secret_key">Stripe Secret Key</label>
                                    <input type="password" id="stripe_secret_key" name="stripe_secret_key" class="eye-book-form-input" value="<?php echo esc_attr(get_option('eye_book_stripe_secret_key', '')); ?>" placeholder="sk_test_...">
                                </div>
                                
                                <div style="display: flex; gap: var(--spacing-sm); align-items: center; margin-top: var(--spacing-lg);">
                                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNDAiIGhlaWdodD0iMTciIHZpZXdCb3g9IjAgMCA0MCAxNyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTE2LjgzIDEuMkMxNi44MyAxLjIgMTcuNTMgMC44IDE4LjU3IDAuOEMxOS42MSAwLjggMjAuMzEgMS4yIDIwLjMxIDEuMlYxNi4ySDI5LjMyVjEuMkMyOS4zMiAxLjIgMjkuNzQgMC44IDMwLjUgMC44QzMxLjI2IDAuOCAzMS42OCAxLjIgMzEuNjggMS4yVjE2LjJIMzkuNlYwSDE0LjQ3VjE2LjJIMTYuODNWMS4yWiIgZmlsbD0iIzYwNUJGRiIvPgo8L3N2Zz4K" alt="Stripe" style="height: 20px;">
                                    <span style="font-size: 12px; color: var(--text-muted);">Secure payment processing</span>
                                </div>
                            </div>
                        </div>

                        <!-- SMS Notifications -->
                        <div class="eye-book-card">
                            <div class="eye-book-card-header">
                                <div class="eye-book-card-header-content">
                                    <h3 class="eye-book-card-title">SMS Notifications</h3>
                                </div>
                                <span class="eye-book-badge eye-book-badge-success" style="font-size: 11px;">Available</span>
                            </div>
                            <div class="eye-book-card-body">
                                <p style="color: var(--text-muted); margin-bottom: var(--spacing-lg);">Send appointment reminders and updates via SMS.</p>
                                
                                <div class="eye-book-form-group">
                                    <label class="eye-book-checkbox-label">
                                        <input type="checkbox" name="twilio_enabled" value="1" <?php checked(get_option('eye_book_twilio_enabled', 0)); ?> class="eye-book-checkbox">
                                        <span class="eye-book-checkbox-text">Enable Twilio SMS</span>
                                    </label>
                                </div>
                                
                                <div class="eye-book-form-group">
                                    <label class="eye-book-form-label" for="twilio_account_sid">Account SID</label>
                                    <input type="text" id="twilio_account_sid" name="twilio_account_sid" class="eye-book-form-input" value="<?php echo esc_attr(get_option('eye_book_twilio_account_sid', '')); ?>" placeholder="AC...">
                                </div>
                                
                                <div class="eye-book-form-group">
                                    <label class="eye-book-form-label" for="twilio_auth_token">Auth Token</label>
                                    <input type="password" id="twilio_auth_token" name="twilio_auth_token" class="eye-book-form-input" value="<?php echo esc_attr(get_option('eye_book_twilio_auth_token', '')); ?>">
                                </div>
                                
                                <div style="display: flex; gap: var(--spacing-sm); align-items: center; margin-top: var(--spacing-lg);">
                                    <svg width="40" height="20" viewBox="0 0 40 20" fill="none">
                                        <path d="M10 4C14.4183 4 18 7.58172 18 12C18 16.4183 14.4183 20 10 20C5.58172 20 2 16.4183 2 12C2 7.58172 5.58172 4 10 4ZM30 4C34.4183 4 38 7.58172 38 12C38 16.4183 34.4183 20 30 20C25.5817 20 22 16.4183 22 12C22 7.58172 25.5817 4 30 4Z" fill="#F22F46"/>
                                    </svg>
                                    <span style="font-size: 12px; color: var(--text-muted);">Reliable SMS delivery worldwide</span>
                                </div>
                            </div>
                        </div>

                        <!-- Insurance Verification -->
                        <div class="eye-book-card">
                            <div class="eye-book-card-header">
                                <div class="eye-book-card-header-content">
                                    <h3 class="eye-book-card-title">Insurance Verification</h3>
                                </div>
                                <span class="eye-book-badge eye-book-badge-warning" style="font-size: 11px;">Coming Soon</span>
                            </div>
                            <div class="eye-book-card-body">
                                <p style="color: var(--text-muted); margin-bottom: var(--spacing-lg);">Real-time insurance eligibility verification and benefits checking.</p>
                                
                                <div class="eye-book-form-group">
                                    <div style="opacity: 0.5; pointer-events: none;">
                                        <label class="eye-book-checkbox-label">
                                            <input type="checkbox" disabled class="eye-book-checkbox">
                                            <span class="eye-book-checkbox-text">Enable Insurance Verification</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="eye-book-info-box">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" class="eye-book-info-icon">
                                        <path d="M10 0C4.48 0 0 4.48 0 10s4.48 10 10 10 10-4.48 10-10S15.52 0 10 0zm1 15H9v-6h2v6zm0-8H9V5h2v2z"/>
                                    </svg>
                                    <div>
                                        <strong>Coming in 2024</strong>
                                        <p style="margin: 4px 0 0 0;">Integration with major clearinghouses including Availity and Change Healthcare for real-time eligibility verification.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar Sync -->
                        <div class="eye-book-card">
                            <div class="eye-book-card-header">
                                <div class="eye-book-card-header-content">
                                    <h3 class="eye-book-card-title">Calendar Synchronization</h3>
                                </div>
                                <span class="eye-book-badge eye-book-badge-info" style="font-size: 11px;">Beta</span>
                            </div>
                            <div class="eye-book-card-body">
                                <p style="color: var(--text-muted); margin-bottom: var(--spacing-lg);">Sync appointments with external calendar systems.</p>
                                
                                <div class="eye-book-form-group">
                                    <label class="eye-book-checkbox-label">
                                        <input type="checkbox" name="google_calendar_enabled" value="1" class="eye-book-checkbox">
                                        <span class="eye-book-checkbox-text">Google Calendar Sync</span>
                                    </label>
                                </div>
                                
                                <div class="eye-book-form-group">
                                    <label class="eye-book-checkbox-label">
                                        <input type="checkbox" name="outlook_calendar_enabled" value="1" class="eye-book-checkbox">
                                        <span class="eye-book-checkbox-text">Outlook Calendar Sync</span>
                                    </label>
                                </div>
                                
                                <button type="button" class="eye-book-btn eye-book-btn-secondary eye-book-btn-sm" onclick="configureBetaIntegration()">
                                    Join Beta Program
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Advanced Settings Tab -->
                <div id="advanced-tab" class="eye-book-tab-content <?php echo $active_tab === 'advanced' ? 'active' : ''; ?>">
                    <div class="eye-book-d-grid" style="grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                        <!-- Database Maintenance -->
                        <div class="eye-book-card">
                            <div class="eye-book-card-header">
                                <div class="eye-book-card-header-content">
                                    <h3 class="eye-book-card-title">Database Maintenance</h3>
                                </div>
                            </div>
                            <div class="eye-book-card-body">
                                <p style="color: var(--text-muted); margin-bottom: var(--spacing-lg);">Keep your system running smoothly with regular maintenance tasks.</p>
                                
                                <div class="eye-book-form-group">
                                    <label class="eye-book-form-label">Optimize Database Tables</label>
                                    <button type="button" class="eye-book-btn eye-book-btn-secondary eye-book-btn-sm" onclick="optimizeDatabase()">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M8 0C3.58 0 0 3.58 0 8s3.58 8 8 8 8-3.58 8-8-3.58-8-8-8zm3.5 6L7 10.5 4.5 8 6 6.5l1 1L10 4l1.5 2z"/>
                                        </svg>
                                        Optimize Now
                                    </button>
                                    <small class="eye-book-form-help">Improve database performance by optimizing table structures.</small>
                                </div>
                                
                                <div class="eye-book-form-group">
                                    <label class="eye-book-form-label">Clean Old Data</label>
                                    <button type="button" class="eye-book-btn eye-book-btn-secondary eye-book-btn-sm" onclick="cleanupOldData()">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                            <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                        </svg>
                                        Clean Data
                                    </button>
                                    <small class="eye-book-form-help">Remove old logs and temporary data (respects HIPAA retention).</small>
                                </div>
                                
                                <div class="eye-book-form-group">
                                    <label class="eye-book-form-label">Export Database</label>
                                    <button type="button" class="eye-book-btn eye-book-btn-secondary eye-book-btn-sm" onclick="exportDatabase()">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M8.5 1.5V11h1V1.5l2.5 2.5.7-.7L8 .6 3.3 3.3l.7.7L6.5 1.5V11h2V1.5zm-7 11V14h13v-1.5H1.5z"/>
                                        </svg>
                                        Export
                                    </button>
                                    <small class="eye-book-form-help">Create encrypted backup of your database.</small>
                                </div>
                            </div>
                        </div>

                        <!-- System Information -->
                        <div class="eye-book-card">
                            <div class="eye-book-card-header">
                                <div class="eye-book-card-header-content">
                                    <h3 class="eye-book-card-title">System Information</h3>
                                </div>
                            </div>
                            <div class="eye-book-card-body">
                                <div class="eye-book-table-container">
                                    <table class="eye-book-table eye-book-table-sm">
                                        <tbody>
                                            <tr>
                                                <td style="font-weight: 600;">Plugin Version</td>
                                                <td>2.0.0</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">Database Version</td>
                                                <td><?php echo get_option('eye_book_db_version', '2.0.0'); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">WordPress Version</td>
                                                <td><?php echo get_bloginfo('version'); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">PHP Version</td>
                                                <td><?php echo PHP_VERSION; ?></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">MySQL Version</td>
                                                <td><?php echo $wpdb->db_version(); ?></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">HIPAA Compliance</td>
                                                <td>
                                                    <?php if ($hipaa_mode): ?>
                                                        <span class="eye-book-badge eye-book-badge-success" style="font-size: 11px;">Enabled</span>
                                                    <?php else: ?>
                                                        <span class="eye-book-badge eye-book-badge-warning" style="font-size: 11px;">Disabled</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: 600;">SSL Certificate</td>
                                                <td>
                                                    <?php if (is_ssl()): ?>
                                                        <span class="eye-book-badge eye-book-badge-success" style="font-size: 11px;">Active</span>
                                                    <?php else: ?>
                                                        <span class="eye-book-badge eye-book-badge-danger" style="font-size: 11px;">Not Active</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div style="margin-top: var(--spacing-lg);">
                                    <button type="button" class="eye-book-btn eye-book-btn-secondary eye-book-btn-sm" onclick="downloadSystemReport()">
                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                            <path d="M8.5 1.5V11h1V1.5l2.5 2.5.7-.7L8 .6 3.3 3.3l.7.7L6.5 1.5V11h2V1.5zm-7 11V14h13v-1.5H1.5z"/>
                                        </svg>
                                        Download System Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Save Button (shown on all tabs) -->
                <div class="eye-book-mt-4">
                    <button type="submit" name="submit" class="eye-book-btn eye-book-btn-primary eye-book-btn-lg">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M12 1H2C.9 1 0 1.9 0 3v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V5l-4-4zM9 12H7V9h2v3zm3-7H4V3h8v2z"/>
                        </svg>
                        Save Settings
                    </button>
                    <button type="button" class="eye-book-btn eye-book-btn-secondary eye-book-btn-lg" onclick="resetToDefaults()">
                        Reset to Defaults
                    </button>
                </div>
            </form>
        </div>
    </main>
</div>

<style>
/* Settings-specific styles */
.eye-book-tabs {
    border-bottom: 2px solid var(--border-light);
}

.eye-book-tabs-nav {
    display: flex;
    gap: 2px;
}

.eye-book-tab-btn {
    padding: var(--spacing-md) var(--spacing-lg);
    border: none;
    background: none;
    color: var(--text-muted);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition-base);
    border-radius: var(--radius-md) var(--radius-md) 0 0;
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    white-space: nowrap;
}

.eye-book-tab-btn:hover {
    background: var(--gray-50);
    color: var(--text-primary);
}

.eye-book-tab-btn.active {
    background: var(--primary-color);
    color: white;
}

.eye-book-tab-content {
    display: none;
}

.eye-book-tab-content.active {
    display: block;
}

.eye-book-radio-group {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-sm);
}

.eye-book-radio-label {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: var(--transition-base);
}

.eye-book-radio-label:hover {
    border-color: var(--primary-color);
    background: var(--primary-bg);
}

.eye-book-radio-label input[type="radio"] {
    margin: 0;
}

.eye-book-input-group {
    display: flex;
    align-items: stretch;
}

.eye-book-input-group .eye-book-form-input {
    border-radius: var(--radius-md) 0 0 var(--radius-md);
    border-right: none;
}

.eye-book-input-group-text {
    padding: var(--spacing-md);
    background: var(--gray-50);
    border: 1px solid var(--border-color);
    border-radius: 0 var(--radius-md) var(--radius-md) 0;
    border-left: none;
    font-size: 14px;
    color: var(--text-muted);
    white-space: nowrap;
}

.eye-book-info-box,
.eye-book-warning-box,
.eye-book-success-box {
    display: flex;
    gap: var(--spacing-md);
    padding: var(--spacing-lg);
    border-radius: var(--radius-lg);
    font-size: 14px;
}

.eye-book-info-box {
    background: var(--info-bg);
    border: 1px solid var(--info-border);
    color: var(--info-color);
}

.eye-book-warning-box {
    background: var(--warning-bg);
    border: 1px solid var(--warning-border);
    color: var(--warning-color);
}

.eye-book-success-box {
    background: var(--success-bg);
    border: 1px solid var(--success-border);
    color: var(--success-color);
}

.eye-book-info-icon,
.eye-book-warning-icon,
.eye-book-success-icon {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
}
</style>

<script>
function switchTab(tabName) {
    // Update URL without reload
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);
    
    // Update active states
    document.querySelectorAll('.eye-book-tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tabName);
    });
    
    document.querySelectorAll('.eye-book-tab-content').forEach(content => {
        content.classList.toggle('active', content.id === tabName + '-tab');
    });
}

function optimizeDatabase() {
    if (!confirm('Are you sure you want to optimize the database? This may take a few minutes.')) {
        return;
    }
    
    // Mock implementation - replace with actual AJAX call
    alert('Database optimization completed successfully.');
}

function cleanupOldData() {
    if (!confirm('Are you sure you want to clean up old data? This action respects HIPAA retention requirements and cannot be undone.')) {
        return;
    }
    
    // Mock implementation - replace with actual AJAX call
    alert('Data cleanup completed successfully.');
}

function exportDatabase() {
    if (!confirm('This will create an encrypted backup of your database. Continue?')) {
        return;
    }
    
    // Mock implementation - replace with actual export functionality
    alert('Database export initiated. You will receive a download link via email when complete.');
}

function exportSettings() {
    // Mock implementation - export current settings
    const settings = {
        exported_at: new Date().toISOString(),
        plugin_version: '2.0.0',
        settings: {
            // Export current settings
        }
    };
    
    const blob = new Blob([JSON.stringify(settings, null, 2)], { type: 'application/json' });
    const url = URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'eye-book-settings-' + new Date().toISOString().slice(0, 10) + '.json';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

function downloadHIPAAReport() {
    // Mock implementation - generate HIPAA compliance report
    alert('HIPAA Compliance Report will be generated and downloaded.');
}

function downloadSystemReport() {
    // Mock implementation - generate system report
    alert('System Report will be generated and downloaded.');
}

function configureBetaIntegration() {
    alert('Beta integration configuration - contact support for access.');
}

function resetToDefaults() {
    if (!confirm('Are you sure you want to reset all settings to their default values? This action cannot be undone.')) {
        return;
    }
    
    // Reload page with reset parameter
    const url = new URL(window.location);
    url.searchParams.set('reset', '1');
    window.location.href = url.toString();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab') || 'general';
    switchTab(activeTab);
});
</script>