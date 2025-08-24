<?php
/**
 * Eye-Book Enterprise Location Management Dashboard
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

// Get search and filter parameters
$search_query = sanitize_text_field($_GET['s'] ?? '');
$status_filter = sanitize_text_field($_GET['status_filter'] ?? '');
$current_page = max(1, intval($_GET['paged'] ?? 1));
$per_page = 12;

// Get statistics
global $wpdb;

// Total locations count
$total_locations = $wpdb->get_var("SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_LOCATIONS . " WHERE status = 'active'");

// Active locations today (with appointments)
$active_today = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(DISTINCT l.id) FROM " . EYE_BOOK_TABLE_LOCATIONS . " l 
     JOIN " . EYE_BOOK_TABLE_APPOINTMENTS . " a ON l.id = a.location_id 
     WHERE l.status = 'active' AND DATE(a.start_datetime) = %s",
    current_time('Y-m-d')
));

// Total appointments this week across locations
$total_appointments_week = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " 
     WHERE start_datetime >= %s AND start_datetime < %s AND status IN ('scheduled', 'confirmed', 'completed')",
    date('Y-m-d', strtotime('monday this week')),
    date('Y-m-d', strtotime('monday next week'))
));

// Total providers across locations
$total_providers = $wpdb->get_var("SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_PROVIDERS . " WHERE status = 'active'");

// Build query for location list with filters and search
$where_clauses = array("l.status != 'deleted'");
$query_args = array();

if (!empty($search_query)) {
    $where_clauses[] = "(l.name LIKE %s OR l.address LIKE %s OR l.city LIKE %s OR l.phone LIKE %s)";
    $search_term = '%' . $wpdb->esc_like($search_query) . '%';
    $query_args = array_merge($query_args, array($search_term, $search_term, $search_term, $search_term));
}

if (!empty($status_filter)) {
    $where_clauses[] = "l.status = %s";
    $query_args[] = $status_filter;
}

$where_clause = implode(' AND ', $where_clauses);

// Get total count for pagination
$total_query = "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_LOCATIONS . " l WHERE " . $where_clause;
$total_count = $wpdb->get_var($wpdb->prepare($total_query, $query_args));
$total_pages = ceil($total_count / $per_page);

// Get locations with pagination
$offset = ($current_page - 1) * $per_page;
$locations_query = "
    SELECT l.*,
           (SELECT COUNT(DISTINCT p.id) FROM " . EYE_BOOK_TABLE_PROVIDERS . " p WHERE p.status = 'active' AND FIND_IN_SET(l.id, p.location_ids)) as provider_count,
           (SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a WHERE a.location_id = l.id AND a.status = 'completed' AND a.start_datetime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as appointments_month,
           (SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a WHERE a.location_id = l.id AND a.status IN ('scheduled', 'confirmed') AND a.start_datetime > NOW()) as upcoming_appointments,
           (SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a WHERE a.location_id = l.id AND DATE(a.start_datetime) = CURDATE() AND a.status IN ('scheduled', 'confirmed', 'in_progress')) as today_appointments
    FROM " . EYE_BOOK_TABLE_LOCATIONS . " l 
    WHERE " . $where_clause . "
    ORDER BY l.name
    LIMIT %d OFFSET %d
";

$query_args[] = $per_page;
$query_args[] = $offset;
$locations = $wpdb->get_results($wpdb->prepare($locations_query, $query_args));

// Calculate percentage changes (mock data for demo)
$location_change = '+3.2%';
$appointments_change = '+15.7%';
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
                        <a href="<?php echo admin_url('admin.php?page=eye-book-locations'); ?>" class="eye-book-nav-link active">
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
                    <h1 class="eye-book-page-title">Location Management</h1>
                    <nav class="eye-book-breadcrumb">
                        <a href="<?php echo admin_url(); ?>">Admin</a>
                        <span class="eye-book-breadcrumb-separator">/</span>
                        <span>Eye-Book</span>
                        <span class="eye-book-breadcrumb-separator">/</span>
                        <span>Locations</span>
                    </nav>
                </div>
                
                <div class="eye-book-header-actions">
                    <!-- Search -->
                    <div class="eye-book-search">
                        <svg class="eye-book-search-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M7 13C10.3137 13 13 10.3137 13 7C13 3.68629 10.3137 1 7 1C3.68629 1 1 3.68629 1 7C1 10.3137 3.68629 13 7 13Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M15 15L11 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <input type="text" class="eye-book-search-input" placeholder="Search locations..." id="location-search" value="<?php echo esc_attr($search_query); ?>">
                    </div>
                    
                    <!-- Add New Location Button -->
                    <a href="<?php echo admin_url('admin.php?page=eye-book-locations&action=add'); ?>" class="eye-book-btn eye-book-btn-primary">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 2v6h6v2H8v6H6v-6H0V8h6V2h2z"/>
                        </svg>
                        <span>Add Location</span>
                    </a>
                    
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
            <!-- Statistics Cards -->
            <div class="eye-book-stats-grid">
                <div class="eye-book-stat-card eye-book-fade-in">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon primary">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 2L10 6H7V10H5V6H2L6 2Z"/>
                            </svg>
                            <?php echo $location_change; ?>
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo number_format($total_locations); ?></div>
                    <div class="eye-book-stat-label">Total Locations</div>
                    <div class="eye-book-stat-footer">
                        <span><?php echo number_format($active_today); ?> active today</span>
                    </div>
                </div>

                <div class="eye-book-stat-card eye-book-fade-in" style="animation-delay: 0.1s;">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon success">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11z"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 2L10 6H7V10H5V6H2L6 2Z"/>
                            </svg>
                            <?php echo $appointments_change; ?>
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo number_format($total_appointments_week); ?></div>
                    <div class="eye-book-stat-label">Weekly Appointments</div>
                    <div class="eye-book-stat-footer">
                        <span>Across all locations</span>
                    </div>
                </div>

                <div class="eye-book-stat-card eye-book-fade-in" style="animation-delay: 0.2s;">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon warning">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M16 4c0-1.11.89-2 2-2s2 .89 2 2-.89 2-2 2-2-.89-2-2zM4 18v-4h3v4H4zM15 11c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm-4 7H8v-4h3v4zm-4-7H4V8h3v3z"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 2L10 6H7V10H5V6H2L6 2Z"/>
                            </svg>
                            +7.4%
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo number_format($total_providers); ?></div>
                    <div class="eye-book-stat-label">Healthcare Providers</div>
                    <div class="eye-book-stat-footer">
                        <span>Assigned to locations</span>
                    </div>
                </div>

                <div class="eye-book-stat-card eye-book-fade-in" style="animation-delay: 0.3s;">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon info">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 2L10 6H7V10H5V6H2L6 2Z"/>
                            </svg>
                            +2.1%
                        </div>
                    </div>
                    <div class="eye-book-stat-value">94%</div>
                    <div class="eye-book-stat-label">Capacity Utilization</div>
                    <div class="eye-book-stat-footer">
                        <span>Average across locations</span>
                    </div>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="eye-book-card eye-book-mb-4">
                <div class="eye-book-card-header">
                    <div class="eye-book-card-header-content">
                        <h3 class="eye-book-card-title">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5;">
                                <path d="M3 5c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2v2c0 .4-.1.8-.3 1.1L11 14v4l-2-1v-3L3.3 8.1c-.2-.3-.3-.7-.3-1.1V5z"/>
                            </svg>
                            Filter Locations
                        </h3>
                    </div>
                </div>
                <div class="eye-book-card-body">
                    <form method="GET" action="">
                        <input type="hidden" name="page" value="eye-book-locations">
                        
                        <div class="eye-book-d-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-lg);">
                            <div class="eye-book-form-group">
                                <label class="eye-book-form-label">Status</label>
                                <select name="status_filter" class="eye-book-form-select">
                                    <option value="">All Status</option>
                                    <option value="active" <?php selected($status_filter, 'active'); ?>>Active</option>
                                    <option value="inactive" <?php selected($status_filter, 'inactive'); ?>>Inactive</option>
                                </select>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label class="eye-book-form-label">Search Query</label>
                                <input type="text" name="s" class="eye-book-form-input" placeholder="Name, address, phone..." value="<?php echo esc_attr($search_query); ?>">
                            </div>
                        </div>
                        
                        <div class="eye-book-mt-2" style="display: flex; gap: var(--spacing-md);">
                            <button type="submit" class="eye-book-btn eye-book-btn-primary">Apply Filters</button>
                            <a href="<?php echo admin_url('admin.php?page=eye-book-locations'); ?>" class="eye-book-btn eye-book-btn-secondary">Clear All</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Locations Grid -->
            <div class="eye-book-card">
                <div class="eye-book-card-header">
                    <div class="eye-book-card-header-content">
                        <h3 class="eye-book-card-title">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5;">
                                <path d="M10 2C6.69 2 4 4.69 4 8c0 4.5 6 10 6 10s6-5.5 6-10c0-3.31-2.69-6-6-6zm0 8c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"/>
                            </svg>
                            Practice Locations
                            <?php if (!empty($search_query) || !empty($status_filter)): ?>
                                <span style="font-size: 14px; font-weight: normal; color: var(--text-muted);"> - Filtered Results</span>
                            <?php endif; ?>
                        </h3>
                    </div>
                    <p class="eye-book-card-subtitle">
                        Showing <?php echo number_format(min($per_page, count($locations))); ?> of <?php echo number_format($total_count); ?> locations
                        <?php if ($total_pages > 1): ?>
                            (Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>)
                        <?php endif; ?>
                    </p>
                </div>
                <div class="eye-book-card-body" style="padding: 0;">
                    <?php if (!empty($locations)): ?>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: var(--spacing-lg); padding: var(--spacing-xl);">
                            <?php foreach ($locations as $location): ?>
                                <div class="eye-book-card location-card" style="margin: 0; transition: all var(--transition-base); cursor: pointer;" onclick="viewLocationDetails(<?php echo $location->id; ?>)">
                                    <div class="eye-book-card-body">
                                        <!-- Location Header -->
                                        <div class="eye-book-d-flex eye-book-gap-3" style="align-items: flex-start; margin-bottom: var(--spacing-lg);">
                                            <div style="width: 64px; height: 64px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 24px; flex-shrink: 0;">
                                                <svg width="32" height="32" viewBox="0 0 32 32" fill="currentColor">
                                                    <path d="M16 4C11.58 4 8 7.58 8 12c0 7 8 16 8 16s8-9 8-16c0-4.42-3.58-8-8-8zm0 11c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3z"/>
                                                </svg>
                                            </div>
                                            <div style="flex: 1; min-width: 0;">
                                                <div style="font-weight: 700; font-size: 18px; margin-bottom: 4px;">
                                                    <?php echo esc_html($location->name); ?>
                                                </div>
                                                <?php if ($location->description): ?>
                                                    <div style="color: var(--text-muted); font-size: 14px; margin-bottom: 8px;">
                                                        <?php echo esc_html($location->description); ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <!-- Status Badge -->
                                                <?php
                                                $status_class = 'success';
                                                $status_text = 'Active';
                                                switch($location->status) {
                                                    case 'inactive': $status_class = 'warning'; $status_text = 'Inactive'; break;
                                                }
                                                ?>
                                                <span class="eye-book-badge eye-book-badge-<?php echo $status_class; ?>" style="font-size: 11px;">
                                                    <span class="eye-book-badge-dot"></span>
                                                    <?php echo esc_html($status_text); ?>
                                                </span>
                                            </div>
                                            
                                            <!-- Actions Dropdown -->
                                            <div class="eye-book-dropdown" style="position: relative;">
                                                <button class="eye-book-btn eye-book-btn-sm eye-book-btn-icon eye-book-btn-secondary" onclick="toggleDropdown(event, <?php echo $location->id; ?>)">
                                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                                        <path d="M3 9.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zm5 0a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <!-- Location Stats -->
                                        <div class="eye-book-d-grid eye-book-gap-3" style="grid-template-columns: repeat(3, 1fr); margin-bottom: var(--spacing-lg);">
                                            <div style="text-align: center;">
                                                <div style="font-weight: 700; font-size: 20px; color: var(--primary-color);">
                                                    <?php echo number_format($location->provider_count ?: 0); ?>
                                                </div>
                                                <div style="font-size: 12px; color: var(--text-muted);">Providers</div>
                                            </div>
                                            <div style="text-align: center;">
                                                <div style="font-weight: 700; font-size: 20px; color: var(--success-color);">
                                                    <?php echo number_format($location->upcoming_appointments ?: 0); ?>
                                                </div>
                                                <div style="font-size: 12px; color: var(--text-muted);">Upcoming</div>
                                            </div>
                                            <div style="text-align: center;">
                                                <div style="font-weight: 700; font-size: 20px; color: var(--warning-color);">
                                                    <?php echo number_format($location->today_appointments ?: 0); ?>
                                                </div>
                                                <div style="font-size: 12px; color: var(--text-muted);">Today</div>
                                            </div>
                                        </div>
                                        
                                        <!-- Address and Contact -->
                                        <div style="padding-top: var(--spacing-md); border-top: 1px solid var(--border-light);">
                                            <?php if ($location->address): ?>
                                                <div class="eye-book-d-flex eye-book-gap-1" style="align-items: flex-start; margin-bottom: 8px;">
                                                    <svg width="14" height="14" viewBox="0 0 14 14" fill="var(--text-muted)" style="margin-top: 2px; flex-shrink: 0;">
                                                        <path d="M7 1C4.24 1 2 3.24 2 6c0 3.5 5 8 5 8s5-4.5 5-8c0-2.76-2.24-5-5-5zm0 6.5c-.83 0-1.5-.67-1.5-1.5S6.17 4.5 7 4.5 8.5 5.17 8.5 6 7.83 7.5 7 7.5z"/>
                                                    </svg>
                                                    <div style="font-size: 13px; line-height: 1.4;">
                                                        <?php 
                                                        $address_parts = array_filter([
                                                            $location->address,
                                                            implode(', ', array_filter([$location->city, $location->state, $location->zip_code]))
                                                        ]);
                                                        echo esc_html(implode('<br>', $address_parts));
                                                        ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: var(--spacing-sm);">
                                                <?php if ($location->phone): ?>
                                                    <a href="tel:<?php echo esc_attr($location->phone); ?>" style="color: var(--primary-color); text-decoration: none; font-size: 13px; font-weight: 500;">
                                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" style="margin-right: 4px;">
                                                            <path d="M3.5 1C2.67 1 2 1.67 2 2.5v9C2 12.33 2.67 13 3.5 13h7c.83 0 1.5-.67 1.5-1.5v-9C12 1.67 11.33 1 10.5 1h-7zM4 3h6v6H4V3z"/>
                                                        </svg>
                                                        <?php echo esc_html($location->phone); ?>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if ($location->address): ?>
                                                    <a href="https://maps.google.com/?q=<?php echo urlencode($location->address . ' ' . $location->city . ' ' . $location->state . ' ' . $location->zip_code); ?>" target="_blank" class="eye-book-btn eye-book-btn-sm eye-book-btn-secondary" style="font-size: 11px; padding: 4px 8px;">
                                                        <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor" style="margin-right: 4px;">
                                                            <path d="M6 1C3.79 1 2 2.79 2 5c0 2.5 4 6 4 6s4-3.5 4-6c0-2.21-1.79-4-4-4zm0 5.5c-.83 0-1.5-.67-1.5-1.5S5.17 3.5 6 3.5 7.5 4.17 7.5 5 6.83 6.5 6 6.5z"/>
                                                        </svg>
                                                        Map
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Hours of Operation (if available) -->
                                            <?php if ($location->hours_monday || $location->hours_tuesday || $location->hours_wednesday): ?>
                                                <div style="margin-top: var(--spacing-sm); padding-top: var(--spacing-sm); border-top: 1px solid var(--border-light);">
                                                    <div style="font-size: 11px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Hours</div>
                                                    <div style="font-size: 12px; color: var(--text-secondary); margin-top: 2px;">
                                                        Mon-Fri: 9:00 AM - 6:00 PM
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="eye-book-empty">
                            <div class="eye-book-empty-icon">
                                <?php if (!empty($search_query) || !empty($status_filter)): ?>
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor" opacity="0.3">
                                        <path d="M20 28c5.52 0 10-4.48 10-10S25.52 8 20 8s-10 4.48-10 10 4.48 10 10 10zm0-16c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6 2.69-6 6-6zm16 14l-6-6c1.38-2.09 1.38-4.91 0-7l6-6 2.83 2.83L33.17 18l5.66 5.66L36 26.83z"/>
                                    </svg>
                                <?php else: ?>
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor" opacity="0.3">
                                        <path d="M24 8C17.37 8 12 13.37 12 20c0 10 12 20 12 20s12-10 12-20c0-6.63-5.37-12-12-12zm0 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($search_query) || !empty($status_filter)): ?>
                                <h4 class="eye-book-empty-title">No Locations Found</h4>
                                <p class="eye-book-empty-description">
                                    No locations match your current filter criteria. Try adjusting your search or filters.
                                </p>
                                <a href="<?php echo admin_url('admin.php?page=eye-book-locations'); ?>" class="eye-book-btn eye-book-btn-secondary">Clear Filters</a>
                            <?php else: ?>
                                <h4 class="eye-book-empty-title">No Locations Yet</h4>
                                <p class="eye-book-empty-description">
                                    You haven't added any practice locations yet. Add your first location to get started with appointment scheduling.
                                </p>
                                <a href="<?php echo admin_url('admin.php?page=eye-book-locations&action=add'); ?>" class="eye-book-btn eye-book-btn-primary">Add First Location</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="eye-book-card-footer">
                        <div style="display: flex; justify-content: between; align-items: center; width: 100%;">
                            <div style="color: var(--text-muted); font-size: 14px;">
                                Showing <?php echo (($current_page - 1) * $per_page) + 1; ?>-<?php echo min($current_page * $per_page, $total_count); ?> of <?php echo number_format($total_count); ?> locations
                            </div>
                            <div>
                                <?php
                                $pagination_args = array();
                                if ($search_query) $pagination_args['s'] = $search_query;
                                if ($status_filter) $pagination_args['status_filter'] = $status_filter;
                                
                                $base_url = admin_url('admin.php?page=eye-book-locations');
                                if (!empty($pagination_args)) {
                                    $base_url .= '&' . http_build_query($pagination_args);
                                }
                                
                                echo paginate_links(array(
                                    'base' => $base_url . '%_%',
                                    'format' => '&paged=%#%',
                                    'current' => $current_page,
                                    'total' => $total_pages,
                                    'prev_text' => '‹ Previous',
                                    'next_text' => 'Next ›',
                                    'type' => 'plain'
                                ));
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<!-- Location Details Modal -->
<div id="locationDetailsModal" class="eye-book-modal" style="display: none;">
    <div class="eye-book-modal-content" style="max-width: 800px;">
        <div class="eye-book-modal-header">
            <h3 class="eye-book-modal-title">Location Details</h3>
            <button class="eye-book-modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="eye-book-modal-body" id="locationDetailsContent">
            <div class="eye-book-loading">
                <div class="eye-book-spinner"></div>
                <p>Loading location details...</p>
            </div>
        </div>
        <div class="eye-book-modal-footer">
            <button class="eye-book-btn eye-book-btn-secondary" onclick="closeModal()">Close</button>
            <button class="eye-book-btn eye-book-btn-secondary" id="viewMapBtn">View on Map</button>
            <button class="eye-book-btn eye-book-btn-primary" id="editLocationBtn">Edit Location</button>
        </div>
    </div>
</div>

<style>
/* Modal Styles */
.eye-book-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: var(--bg-overlay);
    backdrop-filter: blur(4px);
    z-index: var(--z-modal);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    visibility: hidden;
    transition: all var(--transition-base);
}

.eye-book-modal.show {
    opacity: 1;
    visibility: visible;
}

.eye-book-modal-content {
    background: var(--bg-primary);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-2xl);
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow: hidden;
    transform: translateY(-20px);
    transition: transform var(--transition-base);
}

.eye-book-modal.show .eye-book-modal-content {
    transform: translateY(0);
}

.eye-book-modal-header {
    padding: var(--spacing-xl);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.eye-book-modal-title {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
}

.eye-book-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: var(--text-muted);
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-md);
    transition: var(--transition-base);
}

.eye-book-modal-close:hover {
    background: var(--gray-100);
    color: var(--text-primary);
}

.eye-book-modal-body {
    padding: var(--spacing-xl);
    max-height: 500px;
    overflow-y: auto;
}

.eye-book-modal-footer {
    padding: var(--spacing-lg) var(--spacing-xl);
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-md);
}

/* Location Card Hover Effects */
.location-card:hover {
    box-shadow: var(--shadow-xl);
    transform: translateY(-2px);
    border-color: var(--primary-color);
}

/* Map Integration Placeholder */
.location-map-placeholder {
    width: 100%;
    height: 200px;
    background: var(--gray-100);
    border: 2px dashed var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: var(--radius-lg);
    color: var(--text-muted);
    font-size: 14px;
}

/* Dropdown Styles */
.eye-book-dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    min-width: 160px;
    z-index: var(--z-dropdown);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all var(--transition-base);
}

.eye-book-dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.eye-book-dropdown-item {
    display: block;
    padding: var(--spacing-sm) var(--spacing-md);
    color: var(--text-primary);
    text-decoration: none;
    font-size: 14px;
    transition: var(--transition-base);
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
}

.eye-book-dropdown-item:hover {
    background: var(--gray-50);
    color: var(--primary-color);
}

.eye-book-dropdown-item:first-child {
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}

.eye-book-dropdown-item:last-child {
    border-radius: 0 0 var(--radius-lg) var(--radius-lg);
}
</style>

<script>
// JavaScript Functions
function viewLocationDetails(locationId) {
    const modal = document.getElementById('locationDetailsModal');
    const content = document.getElementById('locationDetailsContent');
    const editBtn = document.getElementById('editLocationBtn');
    const mapBtn = document.getElementById('viewMapBtn');
    
    // Show modal with loading state
    modal.classList.add('show');
    content.innerHTML = '<div class="eye-book-loading"><div class="eye-book-spinner"></div><p>Loading location details...</p></div>';
    
    // Set button actions
    editBtn.onclick = function() {
        window.location.href = '<?php echo admin_url("admin.php?page=eye-book-locations&action=edit&id="); ?>' + locationId;
    };
    
    mapBtn.onclick = function() {
        window.open('https://maps.google.com/?q=Location+' + locationId, '_blank');
    };
    
    // Simulate loading location details (replace with actual AJAX call)
    setTimeout(function() {
        content.innerHTML = `
            <div class="eye-book-d-grid eye-book-gap-3" style="grid-template-columns: 1fr 1fr;">
                <div>
                    <h4 style="margin-bottom: var(--spacing-sm);">Location Information</h4>
                    <p><strong>Name:</strong> Sample Location</p>
                    <p><strong>Address:</strong> 123 Main St, City, ST 12345</p>
                    <p><strong>Phone:</strong> (555) 123-4567</p>
                    <p><strong>Status:</strong> <span class="eye-book-badge eye-book-badge-success">Active</span></p>
                </div>
                <div>
                    <h4 style="margin-bottom: var(--spacing-sm);">Operational Details</h4>
                    <p><strong>Providers:</strong> 3 assigned</p>
                    <p><strong>Capacity:</strong> 50 appointments/day</p>
                    <p><strong>Equipment:</strong> Modern optical suite</p>
                </div>
            </div>
            <div class="eye-book-mt-3">
                <h4 style="margin-bottom: var(--spacing-sm);">Hours of Operation</h4>
                <div class="eye-book-d-grid eye-book-gap-2" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));">
                    <div style="padding: var(--spacing-sm); background: var(--gray-50); border-radius: var(--radius-md); text-align: center;">
                        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">MON-FRI</div>
                        <div style="font-size: 14px; font-weight: 600; color: var(--text-primary);">9:00 AM - 6:00 PM</div>
                    </div>
                    <div style="padding: var(--spacing-sm); background: var(--gray-50); border-radius: var(--radius-md); text-align: center;">
                        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">SATURDAY</div>
                        <div style="font-size: 14px; font-weight: 600; color: var(--text-primary);">9:00 AM - 2:00 PM</div>
                    </div>
                    <div style="padding: var(--spacing-sm); background: var(--gray-50); border-radius: var(--radius-md); text-align: center;">
                        <div style="font-size: 12px; color: var(--text-muted); font-weight: 600;">SUNDAY</div>
                        <div style="font-size: 14px; font-weight: 600; color: var(--warning-color);">Closed</div>
                    </div>
                </div>
            </div>
            <div class="eye-book-mt-3">
                <h4 style="margin-bottom: var(--spacing-sm);">Map Integration</h4>
                <div class="location-map-placeholder">
                    <div style="text-align: center;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="margin-bottom: 8px;">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                        </svg>
                        <div>Interactive Map Placeholder</div>
                        <div style="font-size: 12px; margin-top: 4px;">Google Maps integration available in pro version</div>
                    </div>
                </div>
            </div>
        `;
    }, 1000);
}

function toggleDropdown(event, locationId) {
    event.stopPropagation();
    
    // Close any existing dropdowns
    document.querySelectorAll('.eye-book-dropdown-menu').forEach(menu => {
        menu.remove();
    });
    
    // Create dropdown menu
    const dropdown = document.createElement('div');
    dropdown.className = 'eye-book-dropdown-menu';
    dropdown.innerHTML = `
        <button class="eye-book-dropdown-item" onclick="viewLocationDetails(${locationId})">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" style="margin-right: 8px;">
                <path d="M7 3C3.5 3 0.73 5.61 0 7c.73 1.39 3.5 4 7 4s6.27-2.61 7-4c-.73-1.39-3.5-4-7-4zM7 9.5c-1.38 0-2.5-1.12-2.5-2.5S5.62 4.5 7 4.5 9.5 5.62 9.5 7 8.38 9.5 7 9.5z"/>
            </svg>
            View Details
        </button>
        <button class="eye-book-dropdown-item" onclick="editLocation(${locationId})">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" style="margin-right: 8px;">
                <path d="M3 9.5v1h1l7-7-1-1-7 7zm9.5-6.5l-1-1L10 1l1 1L12.5 3z"/>
            </svg>
            Edit Location
        </button>
        <button class="eye-book-dropdown-item" onclick="viewOnMap(${locationId})">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" style="margin-right: 8px;">
                <path d="M7 1C4.79 1 3 2.79 3 5c0 2.5 4 6 4 6s4-3.5 4-6c0-2.21-1.79-4-4-4zm0 5.5c-.83 0-1.5-.67-1.5-1.5S6.17 3.5 7 3.5 8.5 4.17 8.5 5 7.83 6.5 7 6.5z"/>
            </svg>
            View on Map
        </button>
        <button class="eye-book-dropdown-item" onclick="bookAppointment(${locationId})">
            <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor" style="margin-right: 8px;">
                <path d="M12 2h-1V0h-2v2H5V0H3v2H2C.89 2 0 2.89 0 4v8c0 1.11.89 2 2 2h10c1.11 0 2-.89 2-2V4c0-1.11-.89-2-2-2zm0 10H2V6h10v6z"/>
            </svg>
            Book Appointment
        </button>
    `;
    
    // Position and show dropdown
    const button = event.target.closest('.eye-book-btn');
    const dropdown_container = button.parentNode;
    dropdown_container.appendChild(dropdown);
    
    setTimeout(() => {
        dropdown.classList.add('show');
    }, 10);
    
    // Close dropdown when clicking outside
    setTimeout(() => {
        document.addEventListener('click', function closeDropdown(e) {
            if (!dropdown_container.contains(e.target)) {
                dropdown.classList.remove('show');
                setTimeout(() => {
                    if (dropdown.parentNode) {
                        dropdown.remove();
                    }
                }, 200);
                document.removeEventListener('click', closeDropdown);
            }
        });
    }, 100);
}

function editLocation(locationId) {
    window.location.href = '<?php echo admin_url("admin.php?page=eye-book-locations&action=edit&id="); ?>' + locationId;
}

function viewOnMap(locationId) {
    window.open('https://maps.google.com/?q=Location+' + locationId, '_blank');
}

function bookAppointment(locationId) {
    window.location.href = '<?php echo admin_url("admin.php?page=eye-book-appointments&action=add&location_id="); ?>' + locationId;
}

function closeModal() {
    const modal = document.getElementById('locationDetailsModal');
    modal.classList.remove('show');
}

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('location-search');
    let searchTimeout;
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value;
            
            if (searchTerm.length >= 3) {
                searchTimeout = setTimeout(function() {
                    const url = new URL(window.location);
                    url.searchParams.set('s', searchTerm);
                    url.searchParams.set('paged', '1');
                    window.location.href = url.toString();
                }, 500);
            } else if (searchTerm.length === 0) {
                searchTimeout = setTimeout(function() {
                    const url = new URL(window.location);
                    url.searchParams.delete('s');
                    url.searchParams.set('paged', '1');
                    window.location.href = url.toString();
                }, 300);
            }
        });
    }
    
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });
    
    // Close modal on background click
    document.getElementById('locationDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
});
</script>