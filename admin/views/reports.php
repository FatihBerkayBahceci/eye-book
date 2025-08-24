<?php
/**
 * Eye-Book Enterprise Analytics & Reports Dashboard
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

// Get filter parameters
$date_range = sanitize_text_field($_GET['date_range'] ?? 'this_month');
$location_id = intval($_GET['location_id'] ?? 0);
$provider_id = intval($_GET['provider_id'] ?? 0);

// Get statistics and data
global $wpdb;

// Date range calculations
switch ($date_range) {
    case 'today':
        $start_date = current_time('Y-m-d');
        $end_date = current_time('Y-m-d');
        break;
    case 'yesterday':
        $start_date = date('Y-m-d', strtotime('-1 day'));
        $end_date = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'this_week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'last_week':
        $start_date = date('Y-m-d', strtotime('monday last week'));
        $end_date = date('Y-m-d', strtotime('sunday last week'));
        break;
    case 'this_month':
    default:
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-t');
        break;
    case 'last_month':
        $start_date = date('Y-m-01', strtotime('first day of last month'));
        $end_date = date('Y-m-t', strtotime('last day of last month'));
        break;
    case 'this_year':
        $start_date = date('Y-01-01');
        $end_date = date('Y-12-31');
        break;
}

// Build filter conditions
$filter_conditions = array();
$filter_args = array();

if ($location_id) {
    $filter_conditions[] = "a.location_id = %d";
    $filter_args[] = $location_id;
}

if ($provider_id) {
    $filter_conditions[] = "a.provider_id = %d";
    $filter_args[] = $provider_id;
}

$filter_where = !empty($filter_conditions) ? ' AND ' . implode(' AND ', $filter_conditions) : '';

// Get current period stats
$current_stats = $wpdb->get_row($wpdb->prepare("
    SELECT 
        COUNT(*) as total_appointments,
        COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_appointments,
        COUNT(CASE WHEN a.status = 'no_show' THEN 1 END) as no_show_appointments,
        COUNT(CASE WHEN a.status = 'cancelled' THEN 1 END) as cancelled_appointments,
        COUNT(DISTINCT a.patient_id) as unique_patients,
        COUNT(DISTINCT CASE WHEN p.created_at >= %s THEN a.patient_id END) as new_patients,
        AVG(CASE WHEN a.status = 'completed' AND a.revenue > 0 THEN a.revenue END) as avg_revenue,
        SUM(CASE WHEN a.status = 'completed' THEN a.revenue ELSE 0 END) as total_revenue
    FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a
    LEFT JOIN " . EYE_BOOK_TABLE_PATIENTS . " p ON a.patient_id = p.id
    WHERE DATE(a.start_datetime) BETWEEN %s AND %s" . $filter_where,
    array_merge(array($start_date, $start_date, $end_date), $filter_args)
));

// Get comparison period stats (previous period)
$period_diff = (strtotime($end_date) - strtotime($start_date)) / 86400; // days
$prev_start = date('Y-m-d', strtotime($start_date . ' -' . ($period_diff + 1) . ' days'));
$prev_end = date('Y-m-d', strtotime($start_date . ' -1 day'));

$prev_stats = $wpdb->get_row($wpdb->prepare("
    SELECT 
        COUNT(*) as total_appointments,
        COUNT(CASE WHEN a.status = 'completed' THEN 1 END) as completed_appointments,
        COUNT(CASE WHEN a.status = 'no_show' THEN 1 END) as no_show_appointments,
        COUNT(DISTINCT a.patient_id) as unique_patients,
        SUM(CASE WHEN a.status = 'completed' THEN a.revenue ELSE 0 END) as total_revenue
    FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a
    WHERE DATE(a.start_datetime) BETWEEN %s AND %s" . $filter_where,
    array_merge(array($prev_start, $prev_end), $filter_args)
));

// Calculate changes
$appointment_change = $prev_stats->total_appointments ? round((($current_stats->total_appointments - $prev_stats->total_appointments) / $prev_stats->total_appointments) * 100, 1) : 0;
$revenue_change = $prev_stats->total_revenue ? round((($current_stats->total_revenue - $prev_stats->total_revenue) / $prev_stats->total_revenue) * 100, 1) : 0;
$patient_change = $prev_stats->unique_patients ? round((($current_stats->unique_patients - $prev_stats->unique_patients) / $prev_stats->unique_patients) * 100, 1) : 0;

// Calculate no-show rate
$no_show_rate = $current_stats->total_appointments ? round(($current_stats->no_show_appointments / $current_stats->total_appointments) * 100, 1) : 0;
$prev_no_show_rate = $prev_stats->total_appointments ? round(($prev_stats->no_show_appointments / $prev_stats->total_appointments) * 100, 1) : 0;
$no_show_change = $prev_no_show_rate ? round($no_show_rate - $prev_no_show_rate, 1) : 0;

// Get locations and providers for filters
$locations = $wpdb->get_results("SELECT id, name FROM " . EYE_BOOK_TABLE_LOCATIONS . " WHERE status = 'active' ORDER BY name");
$providers = $wpdb->get_results("SELECT p.id, u.display_name FROM " . EYE_BOOK_TABLE_PROVIDERS . " p JOIN {$wpdb->users} u ON p.wp_user_id = u.ID WHERE p.status = 'active' ORDER BY u.display_name");
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
                        <a href="<?php echo admin_url('admin.php?page=eye-book-reports'); ?>" class="eye-book-nav-link active">
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
                    <h1 class="eye-book-page-title">Analytics & Reports</h1>
                    <nav class="eye-book-breadcrumb">
                        <a href="<?php echo admin_url(); ?>">Admin</a>
                        <span class="eye-book-breadcrumb-separator">/</span>
                        <span>Eye-Book</span>
                        <span class="eye-book-breadcrumb-separator">/</span>
                        <span>Reports</span>
                    </nav>
                </div>
                
                <div class="eye-book-header-actions">
                    <!-- Export Button -->
                    <button class="eye-book-btn eye-book-btn-secondary" onclick="exportReports()">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8.5 1.5V11h1V1.5l2.5 2.5.7-.7L8 .6 3.3 3.3l.7.7L6.5 1.5V11h2V1.5zm-7 11V14h13v-1.5H1.5z"/>
                        </svg>
                        <span>Export</span>
                    </button>
                    
                    <!-- Schedule Report Button -->
                    <button class="eye-book-btn eye-book-btn-primary" onclick="scheduleReport()">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M14 2h-1V0H11v2H5V0H3v2H2C.89 2 0 2.89 0 4v10c0 1.11.89 2 2 2h12c1.11 0 2-.89 2-2V4c0-1.11-.89-2-2-2zm0 12H2V7h12v7z"/>
                        </svg>
                        <span>Schedule Report</span>
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
            <!-- Filter Controls -->
            <div class="eye-book-card eye-book-mb-4">
                <div class="eye-book-card-header">
                    <div class="eye-book-card-header-content">
                        <h3 class="eye-book-card-title">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5;">
                                <path d="M3 5c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2v2c0 .4-.1.8-.3 1.1L11 14v4l-2-1v-3L3.3 8.1c-.2-.3-.3-.7-.3-1.1V5z"/>
                            </svg>
                            Report Filters
                        </h3>
                    </div>
                </div>
                <div class="eye-book-card-body">
                    <form method="GET" action="" id="reports-filter-form">
                        <input type="hidden" name="page" value="eye-book-reports">
                        
                        <div class="eye-book-d-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-lg);">
                            <div class="eye-book-form-group">
                                <label class="eye-book-form-label">Date Range</label>
                                <select name="date_range" class="eye-book-form-select" id="date-range-select">
                                    <option value="today" <?php selected($date_range, 'today'); ?>>Today</option>
                                    <option value="yesterday" <?php selected($date_range, 'yesterday'); ?>>Yesterday</option>
                                    <option value="this_week" <?php selected($date_range, 'this_week'); ?>>This Week</option>
                                    <option value="last_week" <?php selected($date_range, 'last_week'); ?>>Last Week</option>
                                    <option value="this_month" <?php selected($date_range, 'this_month'); ?>>This Month</option>
                                    <option value="last_month" <?php selected($date_range, 'last_month'); ?>>Last Month</option>
                                    <option value="this_year" <?php selected($date_range, 'this_year'); ?>>This Year</option>
                                </select>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label class="eye-book-form-label">Location</label>
                                <select name="location_id" class="eye-book-form-select">
                                    <option value="">All Locations</option>
                                    <?php foreach ($locations as $location): ?>
                                        <option value="<?php echo esc_attr($location->id); ?>" <?php selected($location_id, $location->id); ?>>
                                            <?php echo esc_html($location->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label class="eye-book-form-label">Provider</label>
                                <select name="provider_id" class="eye-book-form-select">
                                    <option value="">All Providers</option>
                                    <?php foreach ($providers as $provider): ?>
                                        <option value="<?php echo esc_attr($provider->id); ?>" <?php selected($provider_id, $provider->id); ?>>
                                            Dr. <?php echo esc_html($provider->display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="eye-book-mt-2" style="display: flex; gap: var(--spacing-md);">
                            <button type="submit" class="eye-book-btn eye-book-btn-primary">Apply Filters</button>
                            <a href="<?php echo admin_url('admin.php?page=eye-book-reports'); ?>" class="eye-book-btn eye-book-btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Key Metrics Overview -->
            <div class="eye-book-stats-grid">
                <div class="eye-book-stat-card eye-book-fade-in">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon primary">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change <?php echo $appointment_change >= 0 ? 'positive' : 'negative'; ?>">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 <?php echo $appointment_change >= 0 ? '2L10 6H7V10H5V6H2L6 2Z' : '10L2 6H5V2H7V6H10L6 10Z'; ?>"/>
                            </svg>
                            <?php echo ($appointment_change >= 0 ? '+' : '') . $appointment_change; ?>%
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo number_format($current_stats->total_appointments ?: 0); ?></div>
                    <div class="eye-book-stat-label">Total Appointments</div>
                    <div class="eye-book-stat-footer">
                        <span><?php echo number_format($current_stats->completed_appointments ?: 0); ?> completed</span>
                    </div>
                </div>

                <div class="eye-book-stat-card eye-book-fade-in" style="animation-delay: 0.1s;">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon success">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change <?php echo $patient_change >= 0 ? 'positive' : 'negative'; ?>">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 <?php echo $patient_change >= 0 ? '2L10 6H7V10H5V6H2L6 2Z' : '10L2 6H5V2H7V6H10L6 10Z'; ?>"/>
                            </svg>
                            <?php echo ($patient_change >= 0 ? '+' : '') . $patient_change; ?>%
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo number_format($current_stats->new_patients ?: 0); ?></div>
                    <div class="eye-book-stat-label">New Patients</div>
                    <div class="eye-book-stat-footer">
                        <span><?php echo number_format($current_stats->unique_patients ?: 0); ?> total unique</span>
                    </div>
                </div>

                <div class="eye-book-stat-card eye-book-fade-in" style="animation-delay: 0.2s;">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon warning">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change <?php echo $no_show_change <= 0 ? 'positive' : 'negative'; ?>">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 <?php echo $no_show_change <= 0 ? '10L2 6H5V2H7V6H10L6 10Z' : '2L10 6H7V10H5V6H2L6 2Z'; ?>"/>
                            </svg>
                            <?php echo ($no_show_change >= 0 ? '+' : '') . $no_show_change; ?>%
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo $no_show_rate; ?>%</div>
                    <div class="eye-book-stat-label">No-Show Rate</div>
                    <div class="eye-book-stat-footer">
                        <span><?php echo number_format($current_stats->no_show_appointments ?: 0); ?> no-shows</span>
                    </div>
                </div>

                <div class="eye-book-stat-card eye-book-fade-in" style="animation-delay: 0.3s;">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon info">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change <?php echo $revenue_change >= 0 ? 'positive' : 'negative'; ?>">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 <?php echo $revenue_change >= 0 ? '2L10 6H7V10H5V6H2L6 2Z' : '10L2 6H5V2H7V6H10L6 10Z'; ?>"/>
                            </svg>
                            <?php echo ($revenue_change >= 0 ? '+' : '') . $revenue_change; ?>%
                        </div>
                    </div>
                    <div class="eye-book-stat-value">$<?php echo number_format($current_stats->total_revenue ?: 0, 0); ?></div>
                    <div class="eye-book-stat-label">Total Revenue</div>
                    <div class="eye-book-stat-footer">
                        <span>$<?php echo number_format($current_stats->avg_revenue ?: 0, 0); ?> avg per visit</span>
                    </div>
                </div>
            </div>

            <!-- Charts and Analytics -->
            <div class="eye-book-d-grid" style="grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: var(--spacing-lg); margin-top: var(--spacing-xl);">
                <!-- Appointment Trends Chart -->
                <div class="eye-book-card">
                    <div class="eye-book-card-header">
                        <div class="eye-book-card-header-content">
                            <h3 class="eye-book-card-title">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5;">
                                    <path d="M3 13l4-4 4 4 6-6v3h2V4h-6v2h3l-4 4-4-4-6 6v2z"/>
                                </svg>
                                Appointment Trends
                            </h3>
                        </div>
                    </div>
                    <div class="eye-book-card-body">
                        <div class="chart-container">
                            <canvas id="appointmentTrendsChart" style="width: 100%; height: 300px;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Status Distribution -->
                <div class="eye-book-card">
                    <div class="eye-book-card-header">
                        <div class="eye-book-card-header-content">
                            <h3 class="eye-book-card-title">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5;">
                                    <path d="M10 2C5.59 2 2 5.59 2 10s3.59 8 8 8 8-3.59 8-8-3.59-8-8-8zm0 14c-3.31 0-6-2.69-6-6s2.69-6 6-6 6 2.69 6 6-2.69 6-6 6z"/>
                                </svg>
                                Appointment Status
                            </h3>
                        </div>
                    </div>
                    <div class="eye-book-card-body">
                        <div class="chart-container">
                            <canvas id="statusDistributionChart" style="width: 100%; height: 300px;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Provider Performance -->
                <div class="eye-book-card">
                    <div class="eye-book-card-header">
                        <div class="eye-book-card-header-content">
                            <h3 class="eye-book-card-title">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5;">
                                    <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zM4 18v-4h3v4H4zM15 11c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm-4 7H8v-4h3v4zm-4-7H4V8h3v3z"/>
                                </svg>
                                Provider Utilization
                            </h3>
                        </div>
                    </div>
                    <div class="eye-book-card-body">
                        <div class="chart-container">
                            <canvas id="providerUtilizationChart" style="width: 100%; height: 300px;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Revenue Analysis -->
                <div class="eye-book-card">
                    <div class="eye-book-card-header">
                        <div class="eye-book-card-header-content">
                            <h3 class="eye-book-card-title">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5;">
                                    <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                                </svg>
                                Revenue Analysis
                            </h3>
                        </div>
                    </div>
                    <div class="eye-book-card-body">
                        <div class="chart-container">
                            <canvas id="revenueAnalysisChart" style="width: 100%; height: 300px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detailed Reports Table -->
            <div class="eye-book-card eye-book-mt-4">
                <div class="eye-book-card-header">
                    <div class="eye-book-card-header-content">
                        <h3 class="eye-book-card-title">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5;">
                                <path d="M3 3v14h14V3H3zm12 10H5v-2h10v2zm0-4H5V7h10v2z"/>
                            </svg>
                            Detailed Report
                        </h3>
                    </div>
                    <div style="display: flex; gap: var(--spacing-md);">
                        <button class="eye-book-btn eye-book-btn-secondary eye-book-btn-sm" onclick="exportToCSV()">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                                <path d="M8.5 1.5V8h1V1.5l2.5 2.5.7-.7L8 .6 3.3 3.3l.7.7L6.5 1.5V8h2V1.5zm-7 8V11h13V9.5H1.5z"/>
                            </svg>
                            CSV
                        </button>
                        <button class="eye-book-btn eye-book-btn-secondary eye-book-btn-sm" onclick="exportToPDF()">
                            <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                                <path d="M12 2H2C.9 2 0 2.9 0 4v6c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 8H2V4h10v6z"/>
                            </svg>
                            PDF
                        </button>
                    </div>
                </div>
                <div class="eye-book-card-body" style="padding: 0;">
                    <div class="eye-book-table-container">
                        <?php
                        // Get detailed appointment data
                        $detailed_data = $wpdb->get_results($wpdb->prepare("
                            SELECT 
                                a.id,
                                a.start_datetime,
                                a.status,
                                a.revenue,
                                CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                                u.display_name as provider_name,
                                l.name as location_name,
                                at.name as appointment_type
                            FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a
                            LEFT JOIN " . EYE_BOOK_TABLE_PATIENTS . " p ON a.patient_id = p.id
                            LEFT JOIN " . EYE_BOOK_TABLE_PROVIDERS . " pr ON a.provider_id = pr.id
                            LEFT JOIN {$wpdb->users} u ON pr.wp_user_id = u.ID
                            LEFT JOIN " . EYE_BOOK_TABLE_LOCATIONS . " l ON a.location_id = l.id
                            LEFT JOIN " . EYE_BOOK_TABLE_APPOINTMENT_TYPES . " at ON a.appointment_type_id = at.id
                            WHERE DATE(a.start_datetime) BETWEEN %s AND %s" . $filter_where . "
                            ORDER BY a.start_datetime DESC
                            LIMIT 50",
                            array_merge(array($start_date, $end_date), $filter_args)
                        ));
                        ?>
                        
                        <table class="eye-book-table" id="detailedReportsTable">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Patient</th>
                                    <th>Provider</th>
                                    <th>Location</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($detailed_data)): ?>
                                    <?php foreach ($detailed_data as $row): ?>
                                        <tr>
                                            <td>
                                                <div style="font-weight: 600;">
                                                    <?php echo date('M j, Y', strtotime($row->start_datetime)); ?>
                                                </div>
                                                <div style="font-size: 12px; color: var(--text-muted);">
                                                    <?php echo date('g:i A', strtotime($row->start_datetime)); ?>
                                                </div>
                                            </td>
                                            <td><?php echo esc_html($row->patient_name ?: 'N/A'); ?></td>
                                            <td>Dr. <?php echo esc_html($row->provider_name ?: 'N/A'); ?></td>
                                            <td><?php echo esc_html($row->location_name ?: 'N/A'); ?></td>
                                            <td><?php echo esc_html($row->appointment_type ?: 'Standard'); ?></td>
                                            <td>
                                                <?php
                                                $status_class = 'secondary';
                                                switch($row->status) {
                                                    case 'completed': $status_class = 'success'; break;
                                                    case 'confirmed': $status_class = 'info'; break;
                                                    case 'no_show': $status_class = 'warning'; break;
                                                    case 'cancelled': $status_class = 'danger'; break;
                                                }
                                                ?>
                                                <span class="eye-book-badge eye-book-badge-<?php echo $status_class; ?>">
                                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $row->status))); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($row->revenue): ?>
                                                    $<?php echo number_format($row->revenue, 2); ?>
                                                <?php else: ?>
                                                    <span style="color: var(--text-muted);">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: var(--spacing-xl); color: var(--text-muted);">
                                            No data available for the selected period.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
/* Charts Container */
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

/* Report Specific Styles */
.eye-book-stat-change.positive {
    color: var(--success-color);
}

.eye-book-stat-change.negative {
    color: var(--danger-color);
}

/* Analytics specific animations */
@keyframes countUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.eye-book-stat-value {
    animation: countUp 0.8s ease-out;
}

/* Chart placeholder for when charts aren't loaded */
.chart-placeholder {
    width: 100%;
    height: 300px;
    background: var(--gray-50);
    border: 2px dashed var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-lg);
    color: var(--text-muted);
    font-size: 14px;
}
</style>

<script>
// Mock Chart Data and Functionality (replace with actual Chart.js implementation)
document.addEventListener('DOMContentLoaded', function() {
    // Initialize mock charts
    initializeCharts();
    
    // Add interactivity
    setupReportInteractions();
});

function initializeCharts() {
    // Mock chart initialization
    const chartElements = ['appointmentTrendsChart', 'statusDistributionChart', 'providerUtilizationChart', 'revenueAnalysisChart'];
    
    chartElements.forEach(chartId => {
        const canvas = document.getElementById(chartId);
        if (canvas) {
            const ctx = canvas.getContext('2d');
            
            // Draw placeholder chart
            ctx.fillStyle = '#f8f9fa';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            ctx.fillStyle = '#6c757d';
            ctx.font = '16px Arial';
            ctx.textAlign = 'center';
            ctx.fillText('Chart: ' + chartId.replace('Chart', ''), canvas.width/2, canvas.height/2);
            
            // Add note about Chart.js
            ctx.font = '12px Arial';
            ctx.fillText('(Chart.js integration recommended)', canvas.width/2, canvas.height/2 + 30);
        }
    });
}

function setupReportInteractions() {
    // Date range change handler
    document.getElementById('date-range-select').addEventListener('change', function() {
        document.getElementById('reports-filter-form').submit();
    });
}

function exportReports() {
    // Export functionality
    alert('Export functionality - integrate with your preferred export library');
}

function scheduleReport() {
    // Schedule report functionality
    alert('Schedule report functionality - integrate with cron job system');
}

function exportToCSV() {
    // CSV export functionality
    const table = document.getElementById('detailedReportsTable');
    let csv = [];
    
    // Get headers
    const headers = Array.from(table.querySelectorAll('th')).map(th => th.textContent.trim());
    csv.push(headers.join(','));
    
    // Get data rows
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    rows.forEach(row => {
        const cells = Array.from(row.querySelectorAll('td')).map(td => {
            return '"' + td.textContent.trim().replace(/"/g, '""') + '"';
        });
        csv.push(cells.join(','));
    });
    
    // Download CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'eye-book-report-' + new Date().toISOString().slice(0, 10) + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
}

function exportToPDF() {
    // PDF export functionality - integrate with jsPDF or similar library
    alert('PDF export functionality - integrate with jsPDF library');
}

// Real-time data refresh (optional)
function refreshReportsData() {
    // AJAX call to refresh report data
    console.log('Refreshing reports data...');
}

// Auto-refresh every 5 minutes
setInterval(refreshReportsData, 300000);
</script>