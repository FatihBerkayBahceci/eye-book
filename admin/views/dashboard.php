<?php
/**
 * Eye-Book Enterprise Dashboard
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
$user_initials = substr($current_user->first_name, 0, 1) . substr($current_user->last_name, 0, 1);
if (empty($user_initials)) {
    $user_initials = substr($current_user->display_name, 0, 2);
}

// Get statistics from the $stats variable passed from controller
$today_appointments = $stats['today_appointments'] ?? 0;
$week_appointments = $stats['week_appointments'] ?? 0;
$total_patients = $stats['total_patients'] ?? 0;
$new_patients = $stats['new_patients'] ?? 0;
$pending_appointments = $stats['pending_appointments'] ?? 0;
$recent_appointments = $stats['recent_appointments'] ?? array();

// Calculate percentage changes (mock data for now)
$appointments_change = '+12.5%';
$patients_change = '+8.3%';
$revenue_change = '+15.2%';
$satisfaction_change = '+2.1%';

// Get current date and time
$current_date = current_time('F j, Y');
$current_time = current_time('g:i A');
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
                        <a href="<?php echo admin_url('admin.php?page=eye-book'); ?>" class="eye-book-nav-link active">
                            <span class="eye-book-nav-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M3 3H8V10H3V3Z" fill="currentColor" opacity="0.5"/>
                                    <path d="M12 3H17V7H12V3Z" fill="currentColor"/>
                                    <path d="M12 10H17V17H12V10Z" fill="currentColor" opacity="0.5"/>
                                    <path d="M3 13H8V17H3V13Z" fill="currentColor"/>
                                </svg>
                            </span>
                            <span>Dashboard</span>
                            <?php if ($pending_appointments > 0): ?>
                                <span class="eye-book-nav-badge"><?php echo $pending_appointments; ?></span>
                            <?php endif; ?>
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
                    <li class="eye-book-nav-item">
                        <a href="<?php echo admin_url('admin.php?page=eye-book-services'); ?>" class="eye-book-nav-link">
                            <span class="eye-book-nav-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10 1L2 5v6c0 3.55 1.84 6.74 4.65 8.65L10 21l3.35-1.35C16.16 17.74 18 14.55 18 11V5l-8-4zm3 9h-2v3H9v-3H7V8h2V5h2v3h2v2z"/>
                                </svg>
                            </span>
                            <span>Services</span>
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
                        <a href="<?php echo admin_url('admin.php?page=eye-book-settings'); ?>" class="eye-book-nav-link">
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
                    <h1 class="eye-book-page-title">Dashboard</h1>
                    <nav class="eye-book-breadcrumb">
                        <a href="<?php echo admin_url(); ?>">Admin</a>
                        <span class="eye-book-breadcrumb-separator">/</span>
                        <span>Eye-Book</span>
                        <span class="eye-book-breadcrumb-separator">/</span>
                        <span>Dashboard</span>
                    </nav>
                </div>
                
                <div class="eye-book-header-actions">
                    <!-- Search -->
                    <div class="eye-book-search">
                        <svg class="eye-book-search-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M7 13C10.3137 13 13 10.3137 13 7C13 3.68629 10.3137 1 7 1C3.68629 1 1 3.68629 1 7C1 10.3137 3.68629 13 7 13Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M15 15L11 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <input type="text" class="eye-book-search-input" placeholder="Search patients, appointments...">
                    </div>
                    
                    <!-- Quick Actions -->
                    <button class="eye-book-header-btn">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 2v6h6v2H8v6H6v-6H0V8h6V2h2z"/>
                        </svg>
                        <span>New Appointment</span>
                    </button>
                    
                    <!-- Notifications -->
                    <button class="eye-book-header-btn">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 16c1.1 0 2-.9 2-2H6c0 1.1.9 2 2 2zm4-5V7c0-2.32-1.25-4.26-3.5-4.76V1.5C8.5.67 7.83 0 7 0S5.5.67 5.5 1.5v.74C3.25 2.74 2 4.68 2 7v4l-1 1v1h10v-1l-1-1z"/>
                        </svg>
                        <span class="eye-book-notification-badge"></span>
                    </button>
                    
                    <!-- User Menu -->
                    <div class="eye-book-user-menu">
                        <div class="eye-book-user-avatar"><?php echo strtoupper($user_initials); ?></div>
                        <div class="eye-book-user-info">
                            <div class="eye-book-user-name"><?php echo esc_html($current_user->display_name); ?></div>
                            <div class="eye-book-user-role"><?php echo esc_html(ucfirst(str_replace('_', ' ', $user_role))); ?></div>
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
            <!-- Welcome Section -->
            <div class="eye-book-welcome eye-book-mb-4">
                <h2 style="font-size: 28px; font-weight: 700; color: var(--text-primary); margin-bottom: 8px;">
                    Good <?php echo (date('H') < 12) ? 'Morning' : ((date('H') < 18) ? 'Afternoon' : 'Evening'); ?>, <?php echo esc_html($current_user->first_name ?: $current_user->display_name); ?>! 👋
                </h2>
                <p style="color: var(--text-tertiary); font-size: 16px;">
                    Today is <?php echo $current_date; ?> • <?php echo $current_time; ?> • You have <strong><?php echo $today_appointments; ?> appointments</strong> scheduled for today
                </p>
            </div>

            <!-- Stats Grid -->
            <div class="eye-book-stats-grid">
                <!-- Today's Appointments -->
                <div class="eye-book-stat-card eye-book-fade-in">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon primary">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 2L10 6H7V10H5V6H2L6 2Z"/>
                            </svg>
                            <?php echo $appointments_change; ?>
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo number_format($today_appointments); ?></div>
                    <div class="eye-book-stat-label">Today's Appointments</div>
                    <div class="eye-book-stat-footer">
                        <span>This week: <?php echo $week_appointments; ?></span>
                        <a href="<?php echo admin_url('admin.php?page=eye-book-appointments'); ?>">View all →</a>
                    </div>
                </div>

                <!-- Total Patients -->
                <div class="eye-book-stat-card eye-book-fade-in" style="animation-delay: 0.1s;">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon success">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 2L10 6H7V10H5V6H2L6 2Z"/>
                            </svg>
                            <?php echo $patients_change; ?>
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo number_format($total_patients); ?></div>
                    <div class="eye-book-stat-label">Total Patients</div>
                    <div class="eye-book-stat-footer">
                        <span>New this month: <?php echo $new_patients; ?></span>
                        <a href="<?php echo admin_url('admin.php?page=eye-book-patients'); ?>">Manage →</a>
                    </div>
                </div>

                <!-- Monthly Revenue -->
                <div class="eye-book-stat-card eye-book-fade-in" style="animation-delay: 0.2s;">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon warning">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1.41 16.09V20h-2.67v-1.93c-1.71-.36-3.16-1.46-3.27-3.4h1.96c.1.81.45 1.61 1.67 1.61 1.16 0 1.6-.64 1.6-1.39 0-.93-.53-1.28-2.05-1.71-1.56-.44-3.38-.93-3.38-3.31 0-1.59 1.22-2.84 2.94-3.25V5h2.67v1.62c1.7.4 2.81 1.54 2.91 3.31h-1.91c-.06-.78-.53-1.45-1.56-1.45-1.04 0-1.54.59-1.54 1.3 0 .71.39 1.11 1.98 1.55 1.82.49 3.47 1.12 3.47 3.45 0 1.67-1.26 2.95-3.09 3.31z" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 2L10 6H7V10H5V6H2L6 2Z"/>
                            </svg>
                            <?php echo $revenue_change; ?>
                        </div>
                    </div>
                    <div class="eye-book-stat-value">$24,580</div>
                    <div class="eye-book-stat-label">Monthly Revenue</div>
                    <div class="eye-book-stat-footer">
                        <span>Target: $30,000</span>
                        <a href="<?php echo admin_url('admin.php?page=eye-book-reports'); ?>">View report →</a>
                    </div>
                </div>

                <!-- Patient Satisfaction -->
                <div class="eye-book-stat-card eye-book-fade-in" style="animation-delay: 0.3s;">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon info">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" fill="currentColor"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 2L10 6H7V10H5V6H2L6 2Z"/>
                            </svg>
                            <?php echo $satisfaction_change; ?>
                        </div>
                    </div>
                    <div class="eye-book-stat-value">4.8</div>
                    <div class="eye-book-stat-label">Patient Satisfaction</div>
                    <div class="eye-book-stat-footer">
                        <span>Based on 142 reviews</span>
                        <a href="<?php echo admin_url('admin.php?page=eye-book-feedback'); ?>">View feedback →</a>
                    </div>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="eye-book-d-grid" style="grid-template-columns: 2fr 1fr; gap: var(--spacing-xl); margin-top: var(--spacing-xl);">
                <!-- Today's Schedule -->
                <div class="eye-book-card eye-book-fade-in" style="animation-delay: 0.4s;">
                    <div class="eye-book-card-header">
                        <div class="eye-book-card-header-content">
                            <h3 class="eye-book-card-title">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5;">
                                    <path d="M10 0C4.5 0 0 4.5 0 10s4.5 10 10 10 10-4.5 10-10S15.5 0 10 0zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H9v6l5.25 3.15.75-1.23-4.5-2.67z"/>
                                </svg>
                                Today's Schedule
                            </h3>
                            <div class="eye-book-card-actions">
                                <button class="eye-book-btn eye-book-btn-sm eye-book-btn-secondary">View Calendar</button>
                                <button class="eye-book-btn eye-book-btn-sm eye-book-btn-primary">Add Appointment</button>
                            </div>
                        </div>
                        <p class="eye-book-card-subtitle">Your appointments for <?php echo $current_date; ?></p>
                    </div>
                    <div class="eye-book-card-body">
                        <?php if (!empty($recent_appointments)): ?>
                            <div class="eye-book-table-container">
                                <table class="eye-book-table">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>Patient</th>
                                            <th>Type</th>
                                            <th>Provider</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_appointments as $appointment): 
                                            $appointment_time = date('g:i A', strtotime($appointment->start_datetime));
                                            $provider_user = get_user_by('id', $appointment->provider_user_id);
                                            $provider_name = $provider_user ? $provider_user->display_name : 'N/A';
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo esc_html($appointment_time); ?></strong>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo esc_html($appointment->first_name . ' ' . $appointment->last_name); ?></strong>
                                                        <br>
                                                        <small style="color: var(--text-muted);">ID: #<?php echo esc_html($appointment->patient_id); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="eye-book-badge eye-book-badge-info">
                                                        <span class="eye-book-badge-dot"></span>
                                                        Eye Exam
                                                    </span>
                                                </td>
                                                <td><?php echo esc_html($provider_name); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = 'info';
                                                    switch($appointment->status) {
                                                        case 'confirmed': $status_class = 'success'; break;
                                                        case 'cancelled': $status_class = 'danger'; break;
                                                        case 'completed': $status_class = 'secondary'; break;
                                                        case 'no_show': $status_class = 'warning'; break;
                                                    }
                                                    ?>
                                                    <span class="eye-book-badge eye-book-badge-<?php echo $status_class; ?>">
                                                        <?php echo esc_html(ucfirst($appointment->status)); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="eye-book-table-actions">
                                                        <button class="eye-book-btn eye-book-btn-sm eye-book-btn-secondary">View</button>
                                                        <button class="eye-book-btn eye-book-btn-sm eye-book-btn-icon eye-book-btn-secondary">
                                                            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                                                <path d="M3 12.5v1h1l8-8-1-1-8 8zm10.5-7.5l-1-1L11 2.5l1 1L13.5 5z"/>
                                                            </svg>
                                                        </button>
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
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor" opacity="0.3">
                                        <path d="M38 6h-2V2h-4v4H16V2h-4v4h-2c-2.2 0-4 1.8-4 4v28c0 2.2 1.8 4 4 4h28c2.2 0 4-1.8 4-4V10c0-2.2-1.8-4-4-4zm0 32H10V16h28v22z"/>
                                    </svg>
                                </div>
                                <h4 class="eye-book-empty-title">No Appointments Today</h4>
                                <p class="eye-book-empty-description">You don't have any appointments scheduled for today.</p>
                                <button class="eye-book-btn eye-book-btn-primary">Schedule New Appointment</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions & Notifications -->
                <div class="eye-book-d-flex eye-book-gap-3" style="flex-direction: column;">
                    <!-- Quick Actions -->
                    <div class="eye-book-card eye-book-fade-in" style="animation-delay: 0.5s;">
                        <div class="eye-book-card-header">
                            <div class="eye-book-card-header-content">
                                <h3 class="eye-book-card-title">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5;">
                                        <path d="M10 2C5.59 2 2 5.59 2 10s3.59 8 8 8 8-3.59 8-8-3.59-8-8-8zm3.5 9h-2.5v2.5h-2v-2.5h-2.5v-2h2.5v-2.5h2v2.5h2.5v2z"/>
                                    </svg>
                                    Quick Actions
                                </h3>
                            </div>
                        </div>
                        <div class="eye-book-card-body">
                            <div class="eye-book-d-grid eye-book-gap-2" style="grid-template-columns: 1fr 1fr;">
                                <button class="eye-book-btn eye-book-btn-secondary" style="flex-direction: column; padding: var(--spacing-lg); height: auto;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="opacity: 0.5; margin-bottom: 8px;">
                                        <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/>
                                    </svg>
                                    <span style="font-size: 12px;">New Appointment</span>
                                </button>
                                <button class="eye-book-btn eye-book-btn-secondary" style="flex-direction: column; padding: var(--spacing-lg); height: auto;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="opacity: 0.5; margin-bottom: 8px;">
                                        <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                    </svg>
                                    <span style="font-size: 12px;">Add Patient</span>
                                </button>
                                <button class="eye-book-btn eye-book-btn-secondary" style="flex-direction: column; padding: var(--spacing-lg); height: auto;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="opacity: 0.5; margin-bottom: 8px;">
                                        <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z"/>
                                    </svg>
                                    <span style="font-size: 12px;">View Calendar</span>
                                </button>
                                <button class="eye-book-btn eye-book-btn-secondary" style="flex-direction: column; padding: var(--spacing-lg); height: auto;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="opacity: 0.5; margin-bottom: 8px;">
                                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/>
                                    </svg>
                                    <span style="font-size: 12px;">View Reports</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="eye-book-card eye-book-fade-in" style="animation-delay: 0.6s;">
                        <div class="eye-book-card-header">
                            <div class="eye-book-card-header-content">
                                <h3 class="eye-book-card-title">
                                    <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5;">
                                        <path d="M10 2C5.59 2 2 5.59 2 10s3.59 8 8 8 8-3.59 8-8-3.59-8-8-8zm0 14c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z"/>
                                        <path d="M10.5 7h-1v4l3.5 2.1.5-.8-3-1.8z"/>
                                    </svg>
                                    Recent Activity
                                </h3>
                            </div>
                        </div>
                        <div class="eye-book-card-body">
                            <div class="eye-book-d-flex eye-book-gap-2" style="flex-direction: column;">
                                <div class="eye-book-d-flex eye-book-gap-2" style="align-items: flex-start;">
                                    <div style="width: 8px; height: 8px; background: var(--success-color); border-radius: 50%; margin-top: 6px;"></div>
                                    <div style="flex: 1;">
                                        <p style="font-size: 13px; margin: 0;"><strong>New patient registered</strong></p>
                                        <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Sarah Johnson • 2 hours ago</p>
                                    </div>
                                </div>
                                <div class="eye-book-d-flex eye-book-gap-2" style="align-items: flex-start;">
                                    <div style="width: 8px; height: 8px; background: var(--primary-color); border-radius: 50%; margin-top: 6px;"></div>
                                    <div style="flex: 1;">
                                        <p style="font-size: 13px; margin: 0;"><strong>Appointment confirmed</strong></p>
                                        <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Michael Chen • 3 hours ago</p>
                                    </div>
                                </div>
                                <div class="eye-book-d-flex eye-book-gap-2" style="align-items: flex-start;">
                                    <div style="width: 8px; height: 8px; background: var(--warning-color); border-radius: 50%; margin-top: 6px;"></div>
                                    <div style="flex: 1;">
                                        <p style="font-size: 13px; margin: 0;"><strong>Appointment rescheduled</strong></p>
                                        <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Emma Davis • 5 hours ago</p>
                                    </div>
                                </div>
                                <div class="eye-book-d-flex eye-book-gap-2" style="align-items: flex-start;">
                                    <div style="width: 8px; height: 8px; background: var(--info-color); border-radius: 50%; margin-top: 6px;"></div>
                                    <div style="flex: 1;">
                                        <p style="font-size: 13px; margin: 0;"><strong>Report generated</strong></p>
                                        <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Monthly summary • Yesterday</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="eye-book-card-footer">
                            <a href="<?php echo admin_url('admin.php?page=eye-book-activity'); ?>" class="eye-book-btn eye-book-btn-sm eye-book-btn-secondary eye-book-w-full">View All Activity</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance Charts -->
            <div class="eye-book-d-grid" style="grid-template-columns: repeat(2, 1fr); gap: var(--spacing-xl); margin-top: var(--spacing-xl);">
                <!-- Appointment Trends -->
                <div class="eye-book-chart-container eye-book-fade-in" style="animation-delay: 0.7s;">
                    <div class="eye-book-chart-header">
                        <h3 class="eye-book-chart-title">Appointment Trends</h3>
                        <div class="eye-book-chart-legend">
                            <div class="eye-book-chart-legend-item">
                                <div class="eye-book-chart-legend-dot" style="background: var(--primary-color);"></div>
                                <span>Scheduled</span>
                            </div>
                            <div class="eye-book-chart-legend-item">
                                <div class="eye-book-chart-legend-dot" style="background: var(--success-color);"></div>
                                <span>Completed</span>
                            </div>
                        </div>
                    </div>
                    <div style="height: 250px; display: flex; align-items: center; justify-content: center;">
                        <canvas id="appointmentChart"></canvas>
                    </div>
                </div>

                <!-- Provider Performance -->
                <div class="eye-book-chart-container eye-book-fade-in" style="animation-delay: 0.8s;">
                    <div class="eye-book-chart-header">
                        <h3 class="eye-book-chart-title">Provider Utilization</h3>
                        <div class="eye-book-chart-legend">
                            <div class="eye-book-chart-legend-item">
                                <div class="eye-book-chart-legend-dot" style="background: var(--secondary-color);"></div>
                                <span>This Week</span>
                            </div>
                        </div>
                    </div>
                    <div style="height: 250px; display: flex; align-items: center; justify-content: center;">
                        <canvas id="providerChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- System Health Status -->
            <div class="eye-book-card eye-book-mt-4 eye-book-fade-in" style="animation-delay: 0.9s;">
                <div class="eye-book-card-header">
                    <div class="eye-book-card-header-content">
                        <h3 class="eye-book-card-title">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5;">
                                <path d="M10 1L2 5v6c0 3.5 1.8 6.7 4.7 8.7L10 21l3.3-1.3C16.2 17.7 18 14.5 18 11V5l-8-4z"/>
                            </svg>
                            System Health & Compliance
                        </h3>
                    </div>
                </div>
                <div class="eye-book-card-body">
                    <div class="eye-book-d-grid" style="grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-xl);">
                        <div class="eye-book-d-flex eye-book-gap-2" style="align-items: center;">
                            <div style="width: 48px; height: 48px; background: var(--success-bg); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="var(--success-color)">
                                    <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
                                </svg>
                            </div>
                            <div>
                                <strong style="font-size: 14px;">HIPAA Compliance</strong>
                                <p style="font-size: 12px; color: var(--text-muted); margin: 0;">All safeguards active</p>
                            </div>
                        </div>
                        
                        <div class="eye-book-d-flex eye-book-gap-2" style="align-items: center;">
                            <div style="width: 48px; height: 48px; background: var(--success-bg); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="var(--success-color)">
                                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                                </svg>
                            </div>
                            <div>
                                <strong style="font-size: 14px;">Data Encryption</strong>
                                <p style="font-size: 12px; color: var(--text-muted); margin: 0;">AES-256 encryption active</p>
                            </div>
                        </div>
                        
                        <div class="eye-book-d-flex eye-book-gap-2" style="align-items: center;">
                            <div style="width: 48px; height: 48px; background: var(--success-bg); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="var(--success-color)">
                                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4z"/>
                                </svg>
                            </div>
                            <div>
                                <strong style="font-size: 14px;">Security Status</strong>
                                <p style="font-size: 12px; color: var(--text-muted); margin: 0;">No threats detected</p>
                            </div>
                        </div>
                        
                        <div class="eye-book-d-flex eye-book-gap-2" style="align-items: center;">
                            <div style="width: 48px; height: 48px; background: var(--success-bg); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="var(--success-color)">
                                    <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96z"/>
                                </svg>
                            </div>
                            <div>
                                <strong style="font-size: 14px;">Backup Status</strong>
                                <p style="font-size: 12px; color: var(--text-muted); margin: 0;">Last backup: 2 hours ago</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts if Chart.js is available
    if (typeof Chart !== 'undefined') {
        // Appointment Trends Chart
        const appointmentCtx = document.getElementById('appointmentChart');
        if (appointmentCtx) {
            new Chart(appointmentCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                    datasets: [{
                        label: 'Scheduled',
                        data: [12, 19, 15, 22, 18, 8],
                        borderColor: '#0ea5e9',
                        backgroundColor: 'rgba(14, 165, 233, 0.1)',
                        tension: 0.4
                    }, {
                        label: 'Completed',
                        data: [10, 17, 14, 20, 16, 7],
                        borderColor: '#22c55e',
                        backgroundColor: 'rgba(34, 197, 94, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // Provider Utilization Chart
        const providerCtx = document.getElementById('providerChart');
        if (providerCtx) {
            new Chart(providerCtx.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Dr. Smith', 'Dr. Johnson', 'Dr. Williams', 'Dr. Brown'],
                    datasets: [{
                        label: 'Appointments',
                        data: [28, 32, 25, 30],
                        backgroundColor: '#6366f1'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    }

    // Add smooth scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    document.querySelectorAll('.eye-book-fade-in').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
});</script>