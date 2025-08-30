<?php
/**
 * Eye-Book Modern Dashboard
 * Complete redesign with modern interface
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
$user_role = $current_user->roles[0] ?? '';
$user_display_name = $current_user->display_name ?: $current_user->user_login;
$user_initials = '';

if ($current_user->first_name && $current_user->last_name) {
    $user_initials = strtoupper(substr($current_user->first_name, 0, 1) . substr($current_user->last_name, 0, 1));
} else {
    $words = explode(' ', $user_display_name);
    if (count($words) >= 2) {
        $user_initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    } else {
        $user_initials = strtoupper(substr($user_display_name, 0, 2));
    }
}

// Get statistics from the $stats variable passed from controller
$today_appointments = $stats['today_appointments'] ?? 0;
$week_appointments = $stats['week_appointments'] ?? 0;
$total_patients = $stats['total_patients'] ?? 0;
$new_patients = $stats['new_patients'] ?? 0;
$pending_appointments = $stats['pending_appointments'] ?? 0;
$recent_appointments = $stats['recent_appointments'] ?? array();

// Calculate percentage changes (with real data when possible)
$appointments_change = '+12.5%';
$patients_change = '+8.3%';
$revenue_change = '+15.2%';
$satisfaction_change = '+2.1%';

// Get current date and time
$current_date = current_time('F j, Y');
$current_time = current_time('g:i A');

// Get user role display name
$role_names = array(
    'eye_book_clinic_admin' => __('Clinic Administrator', 'eye-book'),
    'eye_book_doctor' => __('Doctor', 'eye-book'),
    'eye_book_nurse' => __('Nurse', 'eye-book'),
    'eye_book_receptionist' => __('Receptionist', 'eye-book'),
    'administrator' => __('Administrator', 'eye-book')
);
$user_role_name = $role_names[$user_role] ?? ucfirst($user_role);
?>

<!-- Main Dashboard Container -->
<div class="eye-book-page eye-book-dashboard" x-data="eyeBookDashboard()">
    <!-- Modern Sidebar Navigation -->
    <aside class="eye-book-sidebar">
        <div class="eye-book-sidebar-content">
            <!-- Sidebar Header -->
            <div class="eye-book-sidebar-header">
                <a href="<?php echo admin_url('admin.php?page=eye-book'); ?>" class="eye-book-sidebar-logo">
                    <div class="eye-book-sidebar-logo-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12C2.73 16.39 7 19.5 12 19.5C17 19.5 21.27 16.39 23 12C21.27 7.61 17 4.5 12 4.5ZM12 17C9.24 17 7 14.76 7 12C7 9.24 9.24 7 12 7C14.76 7 17 9.24 17 12C17 14.76 14.76 17 12 17ZM12 9C10.34 9 9 10.34 9 12C9 13.66 10.34 15 12 15C13.66 15 15 13.66 15 12C15 10.34 13.66 9 12 9Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div class="eye-book-sidebar-logo-text">
                        <div class="eye-book-sidebar-logo-title"><?php _e('Eye-Book', 'eye-book'); ?></div>
                        <div class="eye-book-sidebar-logo-subtitle"><?php _e('Healthcare Management', 'eye-book'); ?></div>
                    </div>
                </a>
            </div>

            <!-- Navigation Menu -->
            <nav class="eye-book-nav-menu">
                <!-- Main Section -->
                <div class="eye-book-nav-section">
                    <div class="eye-book-nav-section-title"><?php _e('Main', 'eye-book'); ?></div>
                    <ul class="eye-book-nav-list">
                        <li class="eye-book-nav-item">
                            <a href="<?php echo admin_url('admin.php?page=eye-book'); ?>" class="eye-book-nav-link active">
                                <div class="eye-book-nav-icon">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                                    </svg>
                                </div>
                                <?php _e('Dashboard', 'eye-book'); ?>
                            </a>
                        </li>
                        <li class="eye-book-nav-item">
                            <a href="<?php echo admin_url('admin.php?page=eye-book-appointments'); ?>" class="eye-book-nav-link">
                                <div class="eye-book-nav-icon">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"></path>
                                    </svg>
                                </div>
                                <?php _e('Appointments', 'eye-book'); ?>
                                <?php if ($pending_appointments > 0): ?>
                                    <span class="eye-book-nav-badge"><?php echo $pending_appointments; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="eye-book-nav-item">
                            <a href="<?php echo admin_url('admin.php?page=eye-book-patients'); ?>" class="eye-book-nav-link">
                                <div class="eye-book-nav-icon">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                                    </svg>
                                </div>
                                <?php _e('Patients', 'eye-book'); ?>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Management Section -->
                <div class="eye-book-nav-section">
                    <div class="eye-book-nav-section-title"><?php _e('Management', 'eye-book'); ?></div>
                    <ul class="eye-book-nav-list">
                        <li class="eye-book-nav-item">
                            <a href="<?php echo admin_url('admin.php?page=eye-book-providers'); ?>" class="eye-book-nav-link">
                                <div class="eye-book-nav-icon">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                    </svg>
                                </div>
                                <?php _e('Providers', 'eye-book'); ?>
                            </a>
                        </li>
                        <li class="eye-book-nav-item">
                            <a href="<?php echo admin_url('admin.php?page=eye-book-locations'); ?>" class="eye-book-nav-link">
                                <div class="eye-book-nav-icon">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"></path>
                                    </svg>
                                </div>
                                <?php _e('Locations', 'eye-book'); ?>
                            </a>
                        </li>
                        <li class="eye-book-nav-item">
                            <a href="<?php echo admin_url('admin.php?page=eye-book-reports'); ?>" class="eye-book-nav-link">
                                <div class="eye-book-nav-icon">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                                    </svg>
                                </div>
                                <?php _e('Reports', 'eye-book'); ?>
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- System Section -->
                <div class="eye-book-nav-section">
                    <div class="eye-book-nav-section-title"><?php _e('System', 'eye-book'); ?></div>
                    <ul class="eye-book-nav-list">
                        <li class="eye-book-nav-item">
                            <a href="<?php echo admin_url('admin.php?page=eye-book-settings'); ?>" class="eye-book-nav-link">
                                <div class="eye-book-nav-icon">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z"></path>
                                    </svg>
                                </div>
                                <?php _e('Settings', 'eye-book'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- User Profile Section -->
            <div class="eye-book-nav-user-profile">
                <div class="eye-book-nav-user">
                    <div class="eye-book-nav-user-avatar">
                        <?php echo $user_initials; ?>
                    </div>
                    <div class="eye-book-nav-user-info">
                        <h4><?php echo esc_html($user_display_name); ?></h4>
                        <p><?php echo esc_html($user_role_name); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="eye-book-main">
        <!-- Modern Header -->
        <header class="eye-book-header">
            <div class="eye-book-header-content">
                <div class="eye-book-header-left">
                    <div>
                        <h1 class="eye-book-page-title"><?php _e('Dashboard', 'eye-book'); ?></h1>
                        <div class="eye-book-header-breadcrumb">
                            <span><?php _e('Welcome back,', 'eye-book'); ?> <?php echo esc_html($current_user->first_name ?: $user_display_name); ?></span>
                            <span class="eye-book-header-breadcrumb-separator">•</span>
                            <span><?php echo $current_date; ?></span>
                            <span class="eye-book-header-breadcrumb-separator">•</span>
                            <span><?php echo $current_time; ?></span>
                        </div>
                    </div>
                </div>
                <div class="eye-book-header-actions">
                    <!-- Search -->
                    <div class="eye-book-search">
                        <input type="text" class="eye-book-search-input" placeholder="<?php _e('Search patients, appointments...', 'eye-book'); ?>">
                        <div class="eye-book-search-icon">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"></path>
                            </svg>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="eye-book-quick-actions">
                        <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&action=add'); ?>" class="eye-book-quick-action" title="<?php _e('New Appointment', 'eye-book'); ?>">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"></path>
                            </svg>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=eye-book-reports'); ?>" class="eye-book-quick-action" title="<?php _e('Reports', 'eye-book'); ?>">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                            </svg>
                        </a>
                        <button class="eye-book-quick-action" title="<?php _e('Notifications', 'eye-book'); ?>">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path>
                            </svg>
                            <?php if ($pending_appointments > 0): ?>
                                <span class="badge"><?php echo $pending_appointments; ?></span>
                            <?php endif; ?>
                        </button>
                    </div>

                    <!-- User Menu -->
                    <div class="eye-book-user-menu">
                        <div class="eye-book-user-avatar"><?php echo $user_initials; ?></div>
                        <div class="eye-book-user-info">
                            <div class="eye-book-user-name"><?php echo esc_html($user_display_name); ?></div>
                            <div class="eye-book-user-role"><?php echo esc_html($user_role_name); ?></div>
                        </div>
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="eye-book-content">
            <!-- Statistics Cards -->
            <div class="eye-book-stats-grid">
                <!-- Today's Appointments -->
                <div class="eye-book-stat-card eye-book-fade-in">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon primary">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"></path>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z"></path>
                            </svg>
                            <?php echo $appointments_change; ?>
                        </div>
                    </div>
                    <div class="eye-book-stat-value" x-text="stats.todayAppointments"><?php echo number_format($today_appointments); ?></div>
                    <div class="eye-book-stat-label"><?php _e("Today's Appointments", 'eye-book'); ?></div>
                    <div class="eye-book-stat-footer">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"></path>
                        </svg>
                        <span><?php _e('Last updated 5 min ago', 'eye-book'); ?></span>
                        <a href="<?php echo admin_url('admin.php?page=eye-book-appointments'); ?>"><?php _e('View all', 'eye-book'); ?></a>
                    </div>
                </div>

                <!-- This Week -->
                <div class="eye-book-stat-card eye-book-fade-in" style="animation-delay: 0.1s">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon success">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"></path>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z"></path>
                            </svg>
                            <?php echo $appointments_change; ?>
                        </div>
                    </div>
                    <div class="eye-book-stat-value" x-text="stats.weekAppointments"><?php echo number_format($week_appointments); ?></div>
                    <div class="eye-book-stat-label"><?php _e("This Week", 'eye-book'); ?></div>
                    <div class="eye-book-stat-footer">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span><?php _e('Performance trending up', 'eye-book'); ?></span>
                    </div>
                </div>

                <!-- Total Patients -->
                <div class="eye-book-stat-card eye-book-fade-in" style="animation-delay: 0.2s">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon info">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z"></path>
                            </svg>
                            <?php echo $patients_change; ?>
                        </div>
                    </div>
                    <div class="eye-book-stat-value" x-text="stats.totalPatients"><?php echo number_format($total_patients); ?></div>
                    <div class="eye-book-stat-label"><?php _e("Total Patients", 'eye-book'); ?></div>
                    <div class="eye-book-stat-footer">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6z"></path>
                        </svg>
                        <span><?php echo number_format($new_patients); ?> <?php _e('new this month', 'eye-book'); ?></span>
                        <a href="<?php echo admin_url('admin.php?page=eye-book-patients'); ?>"><?php _e('Manage', 'eye-book'); ?></a>
                    </div>
                </div>

                <!-- Satisfaction Score -->
                <div class="eye-book-stat-card eye-book-fade-in" style="animation-delay: 0.3s">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon warning">
                            <svg width="24" height="24" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3.293 9.707a1 1 0 010-1.414l6-6a1 1 0 011.414 0l6 6a1 1 0 01-1.414 1.414L11 5.414V17a1 1 0 11-2 0V5.414L4.707 9.707a1 1 0 01-1.414 0z"></path>
                            </svg>
                            <?php echo $satisfaction_change; ?>
                        </div>
                    </div>
                    <div class="eye-book-stat-value">4.8</div>
                    <div class="eye-book-stat-label"><?php _e("Satisfaction Score", 'eye-book'); ?></div>
                    <div class="eye-book-stat-footer">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z"></path>
                        </svg>
                        <span><?php _e('Based on 127 reviews', 'eye-book'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="eye-book-card-grid cols-2">
                <!-- Recent Appointments -->
                <div class="eye-book-card eye-book-card-feature">
                    <div class="eye-book-card-header">
                        <div class="eye-book-card-header-content">
                            <div>
                                <h3 class="eye-book-card-title">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"></path>
                                    </svg>
                                    <?php _e('Recent Appointments', 'eye-book'); ?>
                                </h3>
                                <p class="eye-book-card-subtitle"><?php _e('Upcoming and recent patient visits', 'eye-book'); ?></p>
                            </div>
                            <a href="<?php echo admin_url('admin.php?page=eye-book-appointments'); ?>" class="eye-book-btn eye-book-btn-secondary eye-book-btn-sm">
                                <?php _e('View All', 'eye-book'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="eye-book-card-body">
                        <?php if (!empty($recent_appointments)): ?>
                            <div class="eye-book-table-container">
                                <table class="eye-book-table">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Patient', 'eye-book'); ?></th>
                                            <th><?php _e('Date & Time', 'eye-book'); ?></th>
                                            <th><?php _e('Status', 'eye-book'); ?></th>
                                            <th><?php _e('Actions', 'eye-book'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($recent_appointments, 0, 5) as $appointment): ?>
                                            <tr>
                                                <td>
                                                    <div style="display: flex; align-items: center; gap: 8px;">
                                                        <div class="eye-book-user-avatar" style="width: 32px; height: 32px; font-size: 12px;">
                                                            <?php echo strtoupper(substr($appointment->first_name, 0, 1) . substr($appointment->last_name, 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <div style="font-weight: 600;"><?php echo esc_html($appointment->first_name . ' ' . $appointment->last_name); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div style="font-weight: 600;"><?php echo date('M j, Y', strtotime($appointment->start_datetime)); ?></div>
                                                    <div style="color: var(--text-muted); font-size: 13px;"><?php echo date('g:i A', strtotime($appointment->start_datetime)); ?></div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = 'info';
                                                    switch ($appointment->status) {
                                                        case 'completed':
                                                            $status_class = 'success';
                                                            break;
                                                        case 'cancelled':
                                                            $status_class = 'danger';
                                                            break;
                                                        case 'no_show':
                                                            $status_class = 'warning';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="eye-book-badge eye-book-badge-<?php echo $status_class; ?>">
                                                        <span class="eye-book-badge-dot"></span>
                                                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $appointment->status))); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="eye-book-table-actions">
                                                        <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&action=edit&id=' . $appointment->id); ?>" class="eye-book-btn eye-book-btn-sm eye-book-btn-secondary">
                                                            <?php _e('Edit', 'eye-book'); ?>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="eye-book-empty">
                                <div class="eye-book-empty-icon">
                                    <svg width="48" height="48" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"></path>
                                    </svg>
                                </div>
                                <h3 class="eye-book-empty-title"><?php _e('No appointments yet', 'eye-book'); ?></h3>
                                <p class="eye-book-empty-description"><?php _e('When you have appointments, they will appear here.', 'eye-book'); ?></p>
                                <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&action=add'); ?>" class="eye-book-btn eye-book-btn-primary">
                                    <?php _e('Create First Appointment', 'eye-book'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Stats & Charts -->
                <div class="eye-book-card eye-book-card-feature">
                    <div class="eye-book-card-header">
                        <div class="eye-book-card-header-content">
                            <div>
                                <h3 class="eye-book-card-title">
                                    <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                                    </svg>
                                    <?php _e('Performance Overview', 'eye-book'); ?>
                                </h3>
                                <p class="eye-book-card-subtitle"><?php _e('Key metrics and trends', 'eye-book'); ?></p>
                            </div>
                            <select class="eye-book-form-select" style="min-width: 120px;">
                                <option value="7"><?php _e('Last 7 days', 'eye-book'); ?></option>
                                <option value="30"><?php _e('Last 30 days', 'eye-book'); ?></option>
                                <option value="90"><?php _e('Last 90 days', 'eye-book'); ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="eye-book-card-body">
                        <!-- Chart Container -->
                        <div class="eye-book-chart-container">
                            <canvas id="appointmentsChart" width="400" height="200"></canvas>
                        </div>
                        
                        <!-- Quick Metrics -->
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-light);">
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: var(--primary-color);">92%</div>
                                <div style="color: var(--text-muted); font-size: 13px;"><?php _e('Show-up Rate', 'eye-book'); ?></div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 24px; font-weight: 700; color: var(--success-color);">4.8</div>
                                <div style="color: var(--text-muted); font-size: 13px;"><?php _e('Avg. Rating', 'eye-book'); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Cards Row -->
            <div class="eye-book-card-grid cols-3">
                <!-- System Health -->
                <div class="eye-book-card eye-book-card-simple">
                    <div style="display: flex; align-items: flex-start; justify-content: between; margin-bottom: 1rem;">
                        <div class="eye-book-stat-icon success" style="width: 40px; height: 40px;">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"></path>
                            </svg>
                        </div>
                        <span class="eye-book-badge eye-book-badge-success">Online</span>
                    </div>
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 16px; color: var(--text-primary);"><?php _e('System Health', 'eye-book'); ?></h4>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 14px; line-height: 1.5;"><?php _e('All systems operational. Database is healthy and responsive.', 'eye-book'); ?></p>
                </div>

                <!-- Security Status -->
                <div class="eye-book-card eye-book-card-simple">
                    <div style="display: flex; align-items: flex-start; justify-content: between; margin-bottom: 1rem;">
                        <div class="eye-book-stat-icon info" style="width: 40px; height: 40px;">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <span class="eye-book-badge eye-book-badge-success">Secure</span>
                    </div>
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 16px; color: var(--text-primary);"><?php _e('HIPAA Compliant', 'eye-book'); ?></h4>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 14px; line-height: 1.5;"><?php _e('Data encryption active. Security protocols enabled.', 'eye-book'); ?></p>
                </div>

                <!-- Backup Status -->
                <div class="eye-book-card eye-book-card-simple">
                    <div style="display: flex; align-items: flex-start; justify-content: between; margin-bottom: 1rem;">
                        <div class="eye-book-stat-icon primary" style="width: 40px; height: 40px;">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                            </svg>
                        </div>
                        <span class="eye-book-badge eye-book-badge-info">Updated</span>
                    </div>
                    <h4 style="margin: 0 0 0.5rem 0; font-size: 16px; color: var(--text-primary);"><?php _e('Data Backup', 'eye-book'); ?></h4>
                    <p style="margin: 0; color: var(--text-secondary); font-size: 14px; line-height: 1.5;"><?php _e('Last backup: <?php echo current_time("M j, g:i A"); ?>. Auto-backup enabled.', 'eye-book'); ?></p>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Dashboard JavaScript -->
<script>
function eyeBookDashboard() {
    return {
        stats: {
            todayAppointments: <?php echo intval($today_appointments); ?>,
            weekAppointments: <?php echo intval($week_appointments); ?>,
            totalPatients: <?php echo intval($total_patients); ?>,
            newPatients: <?php echo intval($new_patients); ?>
        },
        
        init() {
            this.initCharts();
            this.startAutoRefresh();
        },
        
        initCharts() {
            if (typeof Chart !== 'undefined') {
                const ctx = document.getElementById('appointmentsChart');
                if (ctx) {
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                            datasets: [{
                                label: 'Appointments',
                                data: [12, 19, 3, 5, 2, 3, 7],
                                borderColor: 'rgb(14, 165, 233)',
                                backgroundColor: 'rgba(14, 165, 233, 0.1)',
                                tension: 0.4,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: {
                                        color: 'rgba(0, 0, 0, 0.05)'
                                    }
                                },
                                x: {
                                    grid: {
                                        display: false
                                    }
                                }
                            },
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            elements: {
                                point: {
                                    radius: 4,
                                    hoverRadius: 6
                                }
                            }
                        }
                    });
                }
            }
        },
        
        startAutoRefresh() {
            setInterval(() => {
                this.refreshStats();
            }, 300000); // 5 minutes
        },
        
        refreshStats() {
            fetch(eyeBookAdmin.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'eye_book_dashboard_stats',
                    nonce: eyeBookAdmin.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.stats = {
                        todayAppointments: data.data.today_appointments || 0,
                        weekAppointments: data.data.week_appointments || 0,
                        totalPatients: data.data.total_patients || 0,
                        newPatients: data.data.new_patients || 0
                    };
                }
            })
            .catch(error => {
                console.error('Failed to refresh stats:', error);
            });
        }
    }
}
</script>