<?php
/**
 * Eye-Book Enterprise Appointments Management
 *
 * @package EyeBook
 * @subpackage Admin/Views
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

// Get filters
$date_filter = $_GET['date_filter'] ?? date('Y-m-d');
$status_filter = $_GET['status_filter'] ?? '';
$provider_filter = intval($_GET['provider_filter'] ?? 0);
$search = sanitize_text_field($_GET['s'] ?? '');
$page = intval($_GET['paged'] ?? 1);

// Get appointments data
global $wpdb;

// Build query
$where_conditions = array("1=1");
$query_params = array();

if ($date_filter) {
    $where_conditions[] = "DATE(a.start_datetime) = %s";
    $query_params[] = $date_filter;
}

if ($status_filter) {
    $where_conditions[] = "a.status = %s";
    $query_params[] = $status_filter;
}

if ($provider_filter) {
    $where_conditions[] = "a.provider_id = %d";
    $query_params[] = $provider_filter;
}

if ($search) {
    $where_conditions[] = "(p.first_name LIKE %s OR p.last_name LIKE %s OR p.email LIKE %s)";
    $search_term = '%' . $wpdb->esc_like($search) . '%';
    $query_params[] = $search_term;
    $query_params[] = $search_term;
    $query_params[] = $search_term;
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count
$total_query = "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a 
                LEFT JOIN " . EYE_BOOK_TABLE_PATIENTS . " p ON a.patient_id = p.id 
                WHERE " . $where_clause;

$total_appointments = $wpdb->get_var($wpdb->prepare($total_query, ...$query_params));

// Get appointments with pagination
$per_page = 20;
$offset = ($page - 1) * $per_page;
$total_pages = ceil($total_appointments / $per_page);

$appointments_query = "
    SELECT 
        a.*,
        p.first_name as patient_first_name,
        p.last_name as patient_last_name,
        p.email as patient_email,
        p.phone as patient_phone,
        pr.wp_user_id as provider_user_id,
        l.name as location_name,
        at.name as appointment_type_name,
        at.duration as appointment_duration
    FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a
    LEFT JOIN " . EYE_BOOK_TABLE_PATIENTS . " p ON a.patient_id = p.id
    LEFT JOIN " . EYE_BOOK_TABLE_PROVIDERS . " pr ON a.provider_id = pr.id
    LEFT JOIN " . EYE_BOOK_TABLE_LOCATIONS . " l ON a.location_id = l.id
    LEFT JOIN " . EYE_BOOK_TABLE_APPOINTMENT_TYPES . " at ON a.appointment_type_id = at.id
    WHERE " . $where_clause . "
    ORDER BY a.start_datetime DESC
    LIMIT %d OFFSET %d";

$query_params[] = $per_page;
$query_params[] = $offset;

$appointments = $wpdb->get_results($wpdb->prepare($appointments_query, ...$query_params));

// Get providers for filter
$providers = $wpdb->get_results("
    SELECT p.*, u.display_name 
    FROM " . EYE_BOOK_TABLE_PROVIDERS . " p
    LEFT JOIN {$wpdb->users} u ON p.wp_user_id = u.ID
    WHERE p.status = 'active'
    ORDER BY u.display_name ASC
");

// Calculate statistics
$today_appointments = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " 
     WHERE DATE(start_datetime) = %s AND status NOT IN ('cancelled')",
    current_time('Y-m-d')
));

$pending_confirmations = $wpdb->get_var(
    "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " 
     WHERE status = 'pending'"
);

$upcoming_appointments = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " 
     WHERE start_datetime > %s AND status = 'confirmed'",
    current_time('mysql')
));

$no_shows_today = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " 
     WHERE DATE(start_datetime) = %s AND status = 'no_show'",
    current_time('Y-m-d')
));
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
                        <a href="<?php echo admin_url('admin.php?page=eye-book-appointments'); ?>" class="eye-book-nav-link active">
                            <span class="eye-book-nav-icon">
                                <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M17 3h-1V1h-2v2H6V1H4v2H3c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 14H3V8h14v9zM5 10h2v2H5v-2zm4 0h2v2H9v-2zm4 0h2v2h-2v-2z"/>
                                </svg>
                            </span>
                            <span>Appointments</span>
                            <?php if ($pending_confirmations > 0): ?>
                                <span class="eye-book-nav-badge"><?php echo $pending_confirmations; ?></span>
                            <?php endif; ?>
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
                    <h1 class="eye-book-page-title">Appointments</h1>
                    <nav class="eye-book-breadcrumb">
                        <a href="<?php echo admin_url(); ?>">Admin</a>
                        <span class="eye-book-breadcrumb-separator">/</span>
                        <span>Eye-Book</span>
                        <span class="eye-book-breadcrumb-separator">/</span>
                        <span>Appointments</span>
                    </nav>
                </div>
                
                <div class="eye-book-header-actions">
                    <!-- Quick Actions -->
                    <button class="eye-book-btn eye-book-btn-primary" onclick="window.location.href='<?php echo admin_url('admin.php?page=eye-book-appointments&action=add'); ?>'">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M8 2v6h6v2H8v6H6v-6H0V8h6V2h2z"/>
                        </svg>
                        <span>New Appointment</span>
                    </button>
                    
                    <!-- User Menu -->
                    <div class="eye-book-user-menu">
                        <div class="eye-book-user-avatar"><?php echo strtoupper($user_initials); ?></div>
                        <div class="eye-book-user-info">
                            <div class="eye-book-user-name"><?php echo esc_html($current_user->display_name); ?></div>
                            <div class="eye-book-user-role"><?php echo esc_html(ucfirst(str_replace('_', ' ', $current_user->roles[0] ?? ''))); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="eye-book-content">
            <!-- Stats Overview -->
            <div class="eye-book-stats-grid">
                <div class="eye-book-stat-card">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon primary">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z" fill="currentColor"/>
                            </svg>
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo number_format($today_appointments); ?></div>
                    <div class="eye-book-stat-label">Today's Appointments</div>
                </div>

                <div class="eye-book-stat-card">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon warning">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z" fill="currentColor"/>
                            </svg>
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo number_format($pending_confirmations); ?></div>
                    <div class="eye-book-stat-label">Pending Confirmations</div>
                </div>

                <div class="eye-book-stat-card">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon success">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm2-7h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11z" fill="currentColor"/>
                            </svg>
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo number_format($upcoming_appointments); ?></div>
                    <div class="eye-book-stat-label">Upcoming Appointments</div>
                </div>

                <div class="eye-book-stat-card">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon danger">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z" fill="currentColor"/>
                            </svg>
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo number_format($no_shows_today); ?></div>
                    <div class="eye-book-stat-label">No Shows Today</div>
                </div>
            </div>

            <!-- Filters Section -->
            <div class="eye-book-card eye-book-mt-4">
                <div class="eye-book-card-header">
                    <div class="eye-book-card-header-content">
                        <h3 class="eye-book-card-title">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5;">
                                <path d="M3 5v10c0 .55.45 1 1 1h3c.55 0 1-.45 1-1v-3h2v3c0 .55.45 1 1 1h3c.55 0 1-.45 1-1V5c0-.55-.45-1-1-1h-3c-.55 0-1 .45-1 1v3H8V5c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1z"/>
                            </svg>
                            Filter Appointments
                        </h3>
                    </div>
                </div>
                <div class="eye-book-card-body">
                    <form method="GET" action="" class="eye-book-filters-form">
                        <input type="hidden" name="page" value="eye-book-appointments">
                        
                        <div class="eye-book-d-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md);">
                            <!-- Date Filter -->
                            <div class="eye-book-form-group">
                                <label class="eye-book-form-label" for="date_filter">Date</label>
                                <input type="date" 
                                       id="date_filter" 
                                       name="date_filter" 
                                       value="<?php echo esc_attr($date_filter); ?>"
                                       class="eye-book-form-input">
                            </div>
                            
                            <!-- Status Filter -->
                            <div class="eye-book-form-group">
                                <label class="eye-book-form-label" for="status_filter">Status</label>
                                <select id="status_filter" name="status_filter" class="eye-book-form-select">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php selected($status_filter, 'pending'); ?>>Pending</option>
                                    <option value="confirmed" <?php selected($status_filter, 'confirmed'); ?>>Confirmed</option>
                                    <option value="completed" <?php selected($status_filter, 'completed'); ?>>Completed</option>
                                    <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>Cancelled</option>
                                    <option value="no_show" <?php selected($status_filter, 'no_show'); ?>>No Show</option>
                                </select>
                            </div>
                            
                            <!-- Provider Filter -->
                            <div class="eye-book-form-group">
                                <label class="eye-book-form-label" for="provider_filter">Provider</label>
                                <select id="provider_filter" name="provider_filter" class="eye-book-form-select">
                                    <option value="">All Providers</option>
                                    <?php foreach ($providers as $provider): ?>
                                        <option value="<?php echo esc_attr($provider->id); ?>" <?php selected($provider_filter, $provider->id); ?>>
                                            <?php echo esc_html($provider->display_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Search -->
                            <div class="eye-book-form-group">
                                <label class="eye-book-form-label" for="search">Search Patient</label>
                                <input type="text" 
                                       id="search" 
                                       name="s" 
                                       value="<?php echo esc_attr($search); ?>"
                                       placeholder="Name or email..."
                                       class="eye-book-form-input">
                            </div>
                            
                            <!-- Filter Actions -->
                            <div class="eye-book-form-group" style="display: flex; align-items: flex-end; gap: var(--spacing-sm);">
                                <button type="submit" class="eye-book-btn eye-book-btn-primary">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                        <path d="M6 2C3.79 2 2 3.79 2 6s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm8.71 12.29l-3.4-3.39A5.97 5.97 0 0012 6c0-3.31-2.69-6-6-6S0 2.69 0 6s2.69 6 6 6c1.85 0 3.51-.85 4.61-2.18l3.39 3.4c.2.2.45.29.71.29s.51-.1.71-.29a1.003 1.003 0 000-1.42z"/>
                                    </svg>
                                    Apply Filters
                                </button>
                                <a href="<?php echo admin_url('admin.php?page=eye-book-appointments'); ?>" class="eye-book-btn eye-book-btn-secondary">
                                    Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Appointments Table -->
            <div class="eye-book-card eye-book-mt-4">
                <div class="eye-book-card-header">
                    <div class="eye-book-card-header-content">
                        <h3 class="eye-book-card-title">
                            Appointments List
                            <span style="font-size: 14px; font-weight: 400; color: var(--text-tertiary); margin-left: 8px;">
                                (<?php echo number_format($total_appointments); ?> total)
                            </span>
                        </h3>
                        <div class="eye-book-card-actions">
                            <button class="eye-book-btn eye-book-btn-sm eye-book-btn-secondary">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M14 10.5v3c0 .28-.22.5-.5.5h-11c-.28 0-.5-.22-.5-.5v-3c0-.28.22-.5.5-.5s.5.22.5.5V13h10v-2.5c0-.28.22-.5.5-.5s.5.22.5.5zM11.85 7.85l-3.5 3.5c-.1.1-.23.15-.35.15s-.25-.05-.35-.15l-3.5-3.5c-.2-.2-.2-.51 0-.71s.51-.2.71 0L7.5 9.79V2.5c0-.28.22-.5.5-.5s.5.22.5.5v7.29l2.65-2.65c.2-.2.51-.2.71 0s.2.51 0 .71z"/>
                                </svg>
                                Export
                            </button>
                            <button class="eye-book-btn eye-book-btn-sm eye-book-btn-secondary">
                                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M5 7V2.5L1.5 6 5 9.5V7zm6 2V13.5L14.5 10 11 6.5V9z"/>
                                </svg>
                                Sync
                            </button>
                        </div>
                    </div>
                </div>
                <div class="eye-book-card-body" style="padding: 0;">
                    <?php if (empty($appointments)): ?>
                        <div class="eye-book-empty">
                            <div class="eye-book-empty-icon">
                                <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor" opacity="0.3">
                                    <path d="M38 6h-2V2h-4v4H16V2h-4v4h-2c-2.2 0-4 1.8-4 4v28c0 2.2 1.8 4 4 4h28c2.2 0 4-1.8 4-4V10c0-2.2-1.8-4-4-4zm0 32H10V16h28v22z"/>
                                </svg>
                            </div>
                            <h4 class="eye-book-empty-title">No Appointments Found</h4>
                            <p class="eye-book-empty-description">No appointments match your current filters.</p>
                            <button class="eye-book-btn eye-book-btn-primary" onclick="window.location.href='<?php echo admin_url('admin.php?page=eye-book-appointments&action=add'); ?>'">
                                Create New Appointment
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="eye-book-table-container">
                            <table class="eye-book-table">
                                <thead>
                                    <tr>
                                        <th style="width: 40px;">
                                            <input type="checkbox" class="eye-book-checkbox-all">
                                        </th>
                                        <th>Date & Time</th>
                                        <th>Patient</th>
                                        <th>Provider</th>
                                        <th>Type</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appointment): 
                                        $provider_user = get_user_by('id', $appointment->provider_user_id);
                                        $provider_name = $provider_user ? $provider_user->display_name : 'N/A';
                                        $appointment_datetime = new DateTime($appointment->start_datetime);
                                        $appointment_end = clone $appointment_datetime;
                                        $appointment_end->add(new DateInterval('PT' . ($appointment->appointment_duration ?: 30) . 'M'));
                                    ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="eye-book-checkbox" value="<?php echo $appointment->id; ?>">
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo $appointment_datetime->format('M j, Y'); ?></strong><br>
                                                    <small style="color: var(--text-muted);">
                                                        <?php echo $appointment_datetime->format('g:i A'); ?> - <?php echo $appointment_end->format('g:i A'); ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?php echo esc_html($appointment->patient_first_name . ' ' . $appointment->patient_last_name); ?></strong><br>
                                                    <small style="color: var(--text-muted);">
                                                        <?php echo esc_html($appointment->patient_phone ?: 'No phone'); ?>
                                                    </small>
                                                </div>
                                            </td>
                                            <td><?php echo esc_html($provider_name); ?></td>
                                            <td>
                                                <span class="eye-book-badge eye-book-badge-info">
                                                    <?php echo esc_html($appointment->appointment_type_name ?: 'General'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo esc_html($appointment->location_name ?: 'Main Office'); ?></td>
                                            <td>
                                                <?php
                                                $status_class = 'info';
                                                switch($appointment->status) {
                                                    case 'confirmed': $status_class = 'success'; break;
                                                    case 'pending': $status_class = 'warning'; break;
                                                    case 'cancelled': $status_class = 'danger'; break;
                                                    case 'completed': $status_class = 'info'; break;
                                                    case 'no_show': $status_class = 'danger'; break;
                                                }
                                                ?>
                                                <span class="eye-book-badge eye-book-badge-<?php echo $status_class; ?>">
                                                    <span class="eye-book-badge-dot"></span>
                                                    <?php echo esc_html(ucfirst($appointment->status)); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="eye-book-table-actions">
                                                    <button class="eye-book-btn eye-book-btn-sm eye-book-btn-icon eye-book-btn-secondary" 
                                                            title="View Details"
                                                            onclick="window.location.href='<?php echo admin_url('admin.php?page=eye-book-appointments&action=view&id=' . $appointment->id); ?>'">
                                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                                            <path d="M8 3C4.5 3 1.61 5.55 0 8c1.61 2.45 4.5 5 8 5s6.39-2.55 8-5c-1.61-2.45-4.5-5-8-5zm0 8c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3z"/>
                                                            <circle cx="8" cy="8" r="1.5"/>
                                                        </svg>
                                                    </button>
                                                    <button class="eye-book-btn eye-book-btn-sm eye-book-btn-icon eye-book-btn-secondary" 
                                                            title="Edit"
                                                            onclick="window.location.href='<?php echo admin_url('admin.php?page=eye-book-appointments&action=edit&id=' . $appointment->id); ?>'">
                                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                                            <path d="M3 12.5v1h1l8-8-1-1-8 8zm10.5-7.5l-1-1L11 2.5l1 1L13.5 5z"/>
                                                        </svg>
                                                    </button>
                                                    <button class="eye-book-btn eye-book-btn-sm eye-book-btn-icon eye-book-btn-danger" 
                                                            title="Cancel"
                                                            onclick="if(confirm('Are you sure you want to cancel this appointment?')) { eyeBookCancelAppointment(<?php echo $appointment->id; ?>); }">
                                                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                                                            <path d="M4 4l8 8m0-8l-8 8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div style="padding: var(--spacing-lg); border-top: 1px solid var(--border-color);">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="color: var(--text-tertiary); font-size: 14px;">
                                        Showing <?php echo (($page - 1) * $per_page) + 1; ?> to <?php echo min($page * $per_page, $total_appointments); ?> of <?php echo $total_appointments; ?> appointments
                                    </div>
                                    <div style="display: flex; gap: var(--spacing-sm);">
                                        <?php if ($page > 1): ?>
                                            <a href="<?php echo add_query_arg('paged', $page - 1); ?>" class="eye-book-btn eye-book-btn-sm eye-book-btn-secondary">Previous</a>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                            <a href="<?php echo add_query_arg('paged', $i); ?>" 
                                               class="eye-book-btn eye-book-btn-sm <?php echo $i == $page ? 'eye-book-btn-primary' : 'eye-book-btn-secondary'; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <a href="<?php echo add_query_arg('paged', $page + 1); ?>" class="eye-book-btn eye-book-btn-sm eye-book-btn-secondary">Next</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Appointment management functions
function eyeBookCancelAppointment(appointmentId) {
    jQuery.ajax({
        url: eyeBookAdmin.ajax_url,
        type: 'POST',
        data: {
            action: 'eye_book_cancel_appointment',
            appointment_id: appointmentId,
            nonce: eyeBookAdmin.nonce
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.data.message);
            }
        },
        error: function() {
            alert('An error occurred while cancelling the appointment.');
        }
    });
}

// Select all checkboxes
jQuery('.eye-book-checkbox-all').on('change', function() {
    jQuery('.eye-book-checkbox').prop('checked', jQuery(this).prop('checked'));
});

// Bulk actions
function eyeBookBulkAction(action) {
    var selectedIds = [];
    jQuery('.eye-book-checkbox:checked').each(function() {
        selectedIds.push(jQuery(this).val());
    });
    
    if (selectedIds.length === 0) {
        alert('Please select at least one appointment.');
        return;
    }
    
    if (confirm('Are you sure you want to ' + action + ' the selected appointments?')) {
        jQuery.ajax({
            url: eyeBookAdmin.ajax_url,
            type: 'POST',
            data: {
                action: 'eye_book_bulk_action',
                bulk_action: action,
                appointment_ids: selectedIds,
                nonce: eyeBookAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            },
            error: function() {
                alert('An error occurred while processing the bulk action.');
            }
        });
    }
}
</script>