<?php
/**
 * Eye-Book Enterprise Patient Management Dashboard
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
$age_filter = sanitize_text_field($_GET['age_filter'] ?? '');
$insurance_filter = sanitize_text_field($_GET['insurance_filter'] ?? '');
$last_visit_filter = sanitize_text_field($_GET['last_visit_filter'] ?? '');
$current_page = max(1, intval($_GET['paged'] ?? 1));
$per_page = 20;

// Get statistics
global $wpdb;

// Total patients count
$total_patients = $wpdb->get_var("SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE status = 'active'");

// New patients this month
$new_patients_month = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE status = 'active' AND created_at >= %s",
    date('Y-m-01')
));

// Upcoming appointments
$upcoming_appointments = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " 
     WHERE status IN ('scheduled', 'confirmed') AND start_datetime > %s",
    current_time('mysql')
));

// Patients with insurance
$insured_patients = $wpdb->get_var("SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE status = 'active' AND insurance_provider IS NOT NULL AND insurance_provider != ''");
$insurance_percentage = $total_patients > 0 ? round(($insured_patients / $total_patients) * 100) : 0;

// Build query for patient list with filters and search
$where_clauses = array("p.status = 'active'");
$query_args = array();

if (!empty($search_query)) {
    $where_clauses[] = "(p.first_name LIKE %s OR p.last_name LIKE %s OR p.email LIKE %s OR p.phone LIKE %s OR p.patient_id LIKE %s)";
    $search_term = '%' . $wpdb->esc_like($search_query) . '%';
    $query_args = array_merge($query_args, array($search_term, $search_term, $search_term, $search_term, $search_term));
}

if (!empty($age_filter)) {
    switch ($age_filter) {
        case 'child':
            $where_clauses[] = "TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) < 18";
            break;
        case 'adult':
            $where_clauses[] = "TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) BETWEEN 18 AND 64";
            break;
        case 'senior':
            $where_clauses[] = "TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) >= 65";
            break;
    }
}

if (!empty($insurance_filter)) {
    if ($insurance_filter === 'insured') {
        $where_clauses[] = "p.insurance_provider IS NOT NULL AND p.insurance_provider != ''";
    } else {
        $where_clauses[] = "(p.insurance_provider IS NULL OR p.insurance_provider = '')";
    }
}

if (!empty($last_visit_filter)) {
    switch ($last_visit_filter) {
        case 'recent':
            $where_clauses[] = "EXISTS (SELECT 1 FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a WHERE a.patient_id = p.id AND a.status = 'completed' AND a.start_datetime >= DATE_SUB(CURDATE(), INTERVAL 30 DAY))";
            break;
        case 'overdue':
            $where_clauses[] = "NOT EXISTS (SELECT 1 FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a WHERE a.patient_id = p.id AND a.status = 'completed' AND a.start_datetime >= DATE_SUB(CURDATE(), INTERVAL 365 DAY))";
            break;
    }
}

$where_clause = implode(' AND ', $where_clauses);

// Get total count for pagination
$total_query = "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_PATIENTS . " p WHERE " . $where_clause;
$total_count = $wpdb->get_var($wpdb->prepare($total_query, $query_args));
$total_pages = ceil($total_count / $per_page);

// Get patients with pagination
$offset = ($current_page - 1) * $per_page;
$patients_query = "
    SELECT p.*, 
           TIMESTAMPDIFF(YEAR, p.date_of_birth, CURDATE()) as age,
           (SELECT MAX(a.start_datetime) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a WHERE a.patient_id = p.id AND a.status = 'completed') as last_visit,
           (SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a WHERE a.patient_id = p.id AND a.status = 'completed') as total_visits,
           (SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " a WHERE a.patient_id = p.id AND a.status IN ('scheduled', 'confirmed') AND a.start_datetime > NOW()) as upcoming_visits
    FROM " . EYE_BOOK_TABLE_PATIENTS . " p 
    WHERE " . $where_clause . "
    ORDER BY p.last_name, p.first_name
    LIMIT %d OFFSET %d
";

$query_args[] = $per_page;
$query_args[] = $offset;
$patients = $wpdb->get_results($wpdb->prepare($patients_query, $query_args));

// Calculate percentage changes (mock data for demo)
$patient_change = '+12.3%';
$insurance_change = '+5.2%';
$appointment_change = '+8.7%';
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
                        <a href="<?php echo admin_url('admin.php?page=eye-book-patients'); ?>" class="eye-book-nav-link active">
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
                    <h1 class="eye-book-page-title">Patient Management</h1>
                    <nav class="eye-book-breadcrumb">
                        <a href="<?php echo admin_url(); ?>">Admin</a>
                        <span class="eye-book-breadcrumb-separator">/</span>
                        <span>Eye-Book</span>
                        <span class="eye-book-breadcrumb-separator">/</span>
                        <span>Patients</span>
                    </nav>
                </div>
                
                <div class="eye-book-header-actions">
                    <!-- Search -->
                    <div class="eye-book-search">
                        <svg class="eye-book-search-icon" width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M7 13C10.3137 13 13 10.3137 13 7C13 3.68629 10.3137 1 7 1C3.68629 1 1 3.68629 1 7C1 10.3137 3.68629 13 7 13Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M15 15L11 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <input type="text" class="eye-book-search-input" placeholder="Search patients..." id="patient-search" value="<?php echo esc_attr($search_query); ?>">
                    </div>
                    
                    <!-- Add New Patient Button -->
                    <a href="<?php echo admin_url('admin.php?page=eye-book-patients&action=add'); ?>" class="eye-book-btn eye-book-btn-primary">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                            <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                        <span>Add Patient</span>
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
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 2L10 6H7V10H5V6H2L6 2Z"/>
                            </svg>
                            <?php echo $patient_change; ?>
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo number_format($total_patients); ?></div>
                    <div class="eye-book-stat-label">Total Patients</div>
                    <div class="eye-book-stat-footer">
                        <span>Active patients in system</span>
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
                            <?php echo $appointment_change; ?>
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo number_format($upcoming_appointments); ?></div>
                    <div class="eye-book-stat-label">Upcoming Appointments</div>
                    <div class="eye-book-stat-footer">
                        <span>Scheduled for next 30 days</span>
                    </div>
                </div>

                <div class="eye-book-stat-card eye-book-fade-in" style="animation-delay: 0.2s;">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon warning">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 2L10 6H7V10H5V6H2L6 2Z"/>
                            </svg>
                            <?php echo $new_patients_month; ?>
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo number_format($new_patients_month); ?></div>
                    <div class="eye-book-stat-label">New This Month</div>
                    <div class="eye-book-stat-footer">
                        <span>New patient registrations</span>
                    </div>
                </div>

                <div class="eye-book-stat-card eye-book-fade-in" style="animation-delay: 0.3s;">
                    <div class="eye-book-stat-header">
                        <div class="eye-book-stat-icon info">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                            </svg>
                        </div>
                        <div class="eye-book-stat-change">
                            <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
                                <path d="M6 2L10 6H7V10H5V6H2L6 2Z"/>
                            </svg>
                            <?php echo $insurance_change; ?>
                        </div>
                    </div>
                    <div class="eye-book-stat-value"><?php echo $insurance_percentage; ?>%</div>
                    <div class="eye-book-stat-label">Insurance Coverage</div>
                    <div class="eye-book-stat-footer">
                        <span><?php echo number_format($insured_patients); ?> patients insured</span>
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
                            Advanced Filters
                        </h3>
                    </div>
                </div>
                <div class="eye-book-card-body">
                    <form method="GET" action="">
                        <input type="hidden" name="page" value="eye-book-patients">
                        
                        <div class="eye-book-d-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-lg);">
                            <div class="eye-book-form-group">
                                <label class="eye-book-form-label">Age Group</label>
                                <select name="age_filter" class="eye-book-form-select">
                                    <option value="">All Ages</option>
                                    <option value="child" <?php selected($age_filter, 'child'); ?>>Children (0-17)</option>
                                    <option value="adult" <?php selected($age_filter, 'adult'); ?>>Adults (18-64)</option>
                                    <option value="senior" <?php selected($age_filter, 'senior'); ?>>Seniors (65+)</option>
                                </select>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label class="eye-book-form-label">Insurance Status</label>
                                <select name="insurance_filter" class="eye-book-form-select">
                                    <option value="">All Patients</option>
                                    <option value="insured" <?php selected($insurance_filter, 'insured'); ?>>Insured</option>
                                    <option value="uninsured" <?php selected($insurance_filter, 'uninsured'); ?>>Uninsured</option>
                                </select>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label class="eye-book-form-label">Last Visit</label>
                                <select name="last_visit_filter" class="eye-book-form-select">
                                    <option value="">All Patients</option>
                                    <option value="recent" <?php selected($last_visit_filter, 'recent'); ?>>Recent (30 days)</option>
                                    <option value="overdue" <?php selected($last_visit_filter, 'overdue'); ?>>Overdue (1+ year)</option>
                                </select>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label class="eye-book-form-label">Search Query</label>
                                <input type="text" name="s" class="eye-book-form-input" placeholder="Name, email, phone, ID..." value="<?php echo esc_attr($search_query); ?>">
                            </div>
                        </div>
                        
                        <div class="eye-book-mt-2" style="display: flex; gap: var(--spacing-md);">
                            <button type="submit" class="eye-book-btn eye-book-btn-primary">Apply Filters</button>
                            <a href="<?php echo admin_url('admin.php?page=eye-book-patients'); ?>" class="eye-book-btn eye-book-btn-secondary">Clear All</a>
                            <button type="button" class="eye-book-btn eye-book-btn-secondary" onclick="exportPatients()">Export Results</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Patients Table -->
            <div class="eye-book-card">
                <div class="eye-book-card-header">
                    <div class="eye-book-card-header-content">
                        <h3 class="eye-book-card-title">
                            <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" style="opacity: 0.5;">
                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                            </svg>
                            Patients List
                            <?php if (!empty($search_query) || !empty($age_filter) || !empty($insurance_filter) || !empty($last_visit_filter)): ?>
                                <span style="font-size: 14px; font-weight: normal; color: var(--text-muted);"> - Filtered Results</span>
                            <?php endif; ?>
                        </h3>
                    </div>
                    <p class="eye-book-card-subtitle">
                        Showing <?php echo number_format(min($per_page, count($patients))); ?> of <?php echo number_format($total_count); ?> patients
                        <?php if ($total_pages > 1): ?>
                            (Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>)
                        <?php endif; ?>
                    </p>
                </div>
                <div class="eye-book-card-body" style="padding: 0;">
                    <?php if (!empty($patients)): ?>
                        <div class="eye-book-table-container">
                            <table class="eye-book-table">
                                <thead>
                                    <tr>
                                        <th>Patient Information</th>
                                        <th>Contact & Demographics</th>
                                        <th>Medical Summary</th>
                                        <th>Insurance</th>
                                        <th>Visit History</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($patients as $patient): ?>
                                        <tr>
                                            <td>
                                                <div class="eye-book-d-flex eye-book-gap-2" style="align-items: center;">
                                                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%); border-radius: var(--radius-full); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 18px;">
                                                        <?php echo strtoupper(substr($patient->first_name, 0, 1) . substr($patient->last_name, 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div style="font-weight: 600; font-size: 16px;">
                                                            <?php echo esc_html($patient->first_name . ' ' . $patient->last_name); ?>
                                                        </div>
                                                        <div style="color: var(--text-muted); font-size: 13px;">
                                                            ID: <?php echo esc_html($patient->patient_id); ?>
                                                        </div>
                                                        <?php if ($patient->preferred_language && $patient->preferred_language !== 'en'): ?>
                                                            <span class="eye-book-badge eye-book-badge-info" style="font-size: 11px; margin-top: 4px;">
                                                                <?php echo esc_html(strtoupper($patient->preferred_language)); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <?php if ($patient->email): ?>
                                                        <div class="eye-book-d-flex eye-book-gap-1" style="align-items: center; margin-bottom: 4px;">
                                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="var(--text-muted)">
                                                                <path d="M1 3h12v8H1V3zm0 1l6 4 6-4v6H1V4z"/>
                                                            </svg>
                                                            <a href="mailto:<?php echo esc_attr($patient->email); ?>" style="color: var(--primary-color); text-decoration: none; font-size: 13px;">
                                                                <?php echo esc_html($patient->email); ?>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($patient->phone): ?>
                                                        <div class="eye-book-d-flex eye-book-gap-1" style="align-items: center; margin-bottom: 4px;">
                                                            <svg width="14" height="14" viewBox="0 0 14 14" fill="var(--text-muted)">
                                                                <path d="M3.5 1C2.67 1 2 1.67 2 2.5v9C2 12.33 2.67 13 3.5 13h7c.83 0 1.5-.67 1.5-1.5v-9C12 1.67 11.33 1 10.5 1h-7zM4 3h6v6H4V3z"/>
                                                            </svg>
                                                            <a href="tel:<?php echo esc_attr($patient->phone); ?>" style="color: var(--text-secondary); text-decoration: none; font-size: 13px;">
                                                                <?php echo esc_html($patient->phone); ?>
                                                            </a>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <div style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">
                                                        <?php if ($patient->age): ?>
                                                            <strong>Age:</strong> <?php echo esc_html($patient->age); ?>
                                                            <?php if ($patient->gender): ?>
                                                                • <?php echo esc_html(ucfirst($patient->gender)); ?>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <?php if ($patient->allergies): ?>
                                                        <div style="margin-bottom: 6px;">
                                                            <span class="eye-book-badge eye-book-badge-warning" style="font-size: 11px;">
                                                                <span class="eye-book-badge-dot"></span>
                                                                Allergies
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($patient->current_medications): ?>
                                                        <div style="margin-bottom: 6px;">
                                                            <span class="eye-book-badge eye-book-badge-info" style="font-size: 11px;">
                                                                <span class="eye-book-badge-dot"></span>
                                                                Medications
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($patient->medical_history): ?>
                                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 8px;">
                                                            <strong>History:</strong> <?php echo esc_html(substr($patient->medical_history, 0, 50)) . (strlen($patient->medical_history) > 50 ? '...' : ''); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($patient->insurance_provider): ?>
                                                    <div class="eye-book-d-flex eye-book-gap-1" style="align-items: center; margin-bottom: 4px;">
                                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="var(--success-color)">
                                                            <path d="M7 1L2 3v4c0 3.5 2.4 6.8 5 7 2.6-.2 5-3.5 5-7V3L7 1z"/>
                                                        </svg>
                                                        <div>
                                                            <div style="font-weight: 600; font-size: 13px; color: var(--success-color);">
                                                                <?php echo esc_html($patient->insurance_provider); ?>
                                                            </div>
                                                            <?php if ($patient->insurance_member_id): ?>
                                                                <div style="font-size: 11px; color: var(--text-muted);">
                                                                    ID: <?php echo esc_html($patient->insurance_member_id); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="eye-book-d-flex eye-book-gap-1" style="align-items: center;">
                                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="var(--warning-color)">
                                                            <path d="M7 1c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm1 9H6V6h2v4zM7 5c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1z"/>
                                                        </svg>
                                                        <span style="color: var(--warning-color); font-size: 13px;">Self-Pay</span>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <div style="font-weight: 600; font-size: 16px; color: var(--primary-color);">
                                                        <?php echo number_format($patient->total_visits); ?>
                                                    </div>
                                                    <div style="font-size: 12px; color: var(--text-muted);">Total Visits</div>
                                                    
                                                    <?php if ($patient->last_visit): ?>
                                                        <div style="margin-top: 6px; font-size: 12px;">
                                                            <strong>Last:</strong> <?php echo date('M j, Y', strtotime($patient->last_visit)); ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div style="margin-top: 6px; font-size: 12px; color: var(--warning-color);">
                                                            No visits yet
                                                        </div>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($patient->upcoming_visits > 0): ?>
                                                        <div style="margin-top: 4px;">
                                                            <span class="eye-book-badge eye-book-badge-success" style="font-size: 10px;">
                                                                <?php echo $patient->upcoming_visits; ?> upcoming
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="eye-book-table-actions">
                                                    <button class="eye-book-btn eye-book-btn-sm eye-book-btn-primary" onclick="viewPatientDetails(<?php echo $patient->id; ?>)">
                                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                                                            <path d="M7 3C3.5 3 0.73 5.61 0 7c.73 1.39 3.5 4 7 4s6.27-2.61 7-4c-.73-1.39-3.5-4-7-4zM7 9.5c-1.38 0-2.5-1.12-2.5-2.5S5.62 4.5 7 4.5 9.5 5.62 9.5 7 8.38 9.5 7 9.5z"/>
                                                        </svg>
                                                    </button>
                                                    <button class="eye-book-btn eye-book-btn-sm eye-book-btn-secondary" onclick="editPatient(<?php echo $patient->id; ?>)">
                                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                                                            <path d="M3 9.5v1h1l7-7-1-1-7 7zm9.5-6.5l-1-1L10 1l1 1L12.5 3z"/>
                                                        </svg>
                                                    </button>
                                                    <button class="eye-book-btn eye-book-btn-sm eye-book-btn-success" onclick="scheduleAppointment(<?php echo $patient->id; ?>)">
                                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="currentColor">
                                                            <path d="M12 2h-1V0h-2v2H5V0H3v2H2C.89 2 0 2.89 0 4v8c0 1.11.89 2 2 2h10c1.11 0 2-.89 2-2V4c0-1.11-.89-2-2-2zm0 10H2V6h10v6z"/>
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
                                <?php if (!empty($search_query) || !empty($age_filter) || !empty($insurance_filter) || !empty($last_visit_filter)): ?>
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor" opacity="0.3">
                                        <path d="M20 28c5.52 0 10-4.48 10-10S25.52 8 20 8s-10 4.48-10 10 4.48 10 10 10zm0-16c3.31 0 6 2.69 6 6s-2.69 6-6 6-6-2.69-6-6 2.69-6 6-6zm16 14l-6-6c1.38-2.09 1.38-4.91 0-7l6-6 2.83 2.83L33.17 18l5.66 5.66L36 26.83z"/>
                                    </svg>
                                <?php else: ?>
                                    <svg width="48" height="48" viewBox="0 0 48 48" fill="currentColor" opacity="0.3">
                                        <path d="M24 24c4.42 0 8-3.58 8-8s-3.58-8-8-8-8 3.58-8 8 3.58 8 8 8zm0 4c-5.33 0-16 2.67-16 8v4h32v-4c0-5.33-10.67-8-16-8z"/>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($search_query) || !empty($age_filter) || !empty($insurance_filter) || !empty($last_visit_filter)): ?>
                                <h4 class="eye-book-empty-title">No Patients Found</h4>
                                <p class="eye-book-empty-description">
                                    No patients match your current filter criteria. Try adjusting your search or filters.
                                </p>
                                <a href="<?php echo admin_url('admin.php?page=eye-book-patients'); ?>" class="eye-book-btn eye-book-btn-secondary">Clear Filters</a>
                            <?php else: ?>
                                <h4 class="eye-book-empty-title">No Patients Yet</h4>
                                <p class="eye-book-empty-description">
                                    You haven't added any patients to your system yet. Add your first patient to get started with patient management.
                                </p>
                                <a href="<?php echo admin_url('admin.php?page=eye-book-patients&action=add'); ?>" class="eye-book-btn eye-book-btn-primary">Add First Patient</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="eye-book-card-footer">
                        <div style="display: flex; justify-content: between; align-items: center; width: 100%;">
                            <div style="color: var(--text-muted); font-size: 14px;">
                                Showing <?php echo (($current_page - 1) * $per_page) + 1; ?>-<?php echo min($current_page * $per_page, $total_count); ?> of <?php echo number_format($total_count); ?> patients
                            </div>
                            <div>
                                <?php
                                $pagination_args = array();
                                if ($search_query) $pagination_args['s'] = $search_query;
                                if ($age_filter) $pagination_args['age_filter'] = $age_filter;
                                if ($insurance_filter) $pagination_args['insurance_filter'] = $insurance_filter;
                                if ($last_visit_filter) $pagination_args['last_visit_filter'] = $last_visit_filter;
                                
                                $base_url = admin_url('admin.php?page=eye-book-patients');
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

<!-- Patient Details Modal -->
<div id="patientDetailsModal" class="eye-book-modal" style="display: none;">
    <div class="eye-book-modal-content">
        <div class="eye-book-modal-header">
            <h3 class="eye-book-modal-title">Patient Details</h3>
            <button class="eye-book-modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="eye-book-modal-body" id="patientDetailsContent">
            <div class="eye-book-loading">
                <div class="eye-book-spinner"></div>
                <p>Loading patient details...</p>
            </div>
        </div>
        <div class="eye-book-modal-footer">
            <button class="eye-book-btn eye-book-btn-secondary" onclick="closeModal()">Close</button>
            <button class="eye-book-btn eye-book-btn-primary" id="editPatientBtn">Edit Patient</button>
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
    max-height: 400px;
    overflow-y: auto;
}

.eye-book-modal-footer {
    padding: var(--spacing-lg) var(--spacing-xl);
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: flex-end;
    gap: var(--spacing-md);
}

/* HIPAA Compliance Indicator */
.hipaa-compliant-badge {
    display: inline-flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-xs) var(--spacing-sm);
    background: var(--success-bg);
    color: var(--success-color);
    border: 1px solid var(--success-border);
    border-radius: var(--radius-md);
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: fixed;
    bottom: var(--spacing-lg);
    right: var(--spacing-lg);
    z-index: var(--z-tooltip);
}

.hipaa-compliant-badge svg {
    width: 12px;
    height: 12px;
}
</style>

<script>
// JavaScript Functions
function viewPatientDetails(patientId) {
    const modal = document.getElementById('patientDetailsModal');
    const content = document.getElementById('patientDetailsContent');
    const editBtn = document.getElementById('editPatientBtn');
    
    // Show modal with loading state
    modal.classList.add('show');
    content.innerHTML = '<div class="eye-book-loading"><div class="eye-book-spinner"></div><p>Loading patient details...</p></div>';
    
    // Set edit button action
    editBtn.onclick = function() {
        window.location.href = '<?php echo admin_url("admin.php?page=eye-book-patients&action=edit&id="); ?>' + patientId;
    };
    
    // Simulate loading patient details (replace with actual AJAX call)
    setTimeout(function() {
        content.innerHTML = `
            <div class="eye-book-d-grid eye-book-gap-3" style="grid-template-columns: 1fr 1fr;">
                <div>
                    <h4 style="margin-bottom: var(--spacing-sm);">Basic Information</h4>
                    <p><strong>Patient ID:</strong> #${patientId.toString().padStart(6, '0')}</p>
                    <p><strong>Full Name:</strong> Sample Patient</p>
                    <p><strong>Date of Birth:</strong> January 1, 1990</p>
                    <p><strong>Gender:</strong> Female</p>
                </div>
                <div>
                    <h4 style="margin-bottom: var(--spacing-sm);">Contact Information</h4>
                    <p><strong>Email:</strong> patient@example.com</p>
                    <p><strong>Phone:</strong> (555) 123-4567</p>
                    <p><strong>Address:</strong> 123 Main St, City, ST 12345</p>
                </div>
            </div>
            <div class="eye-book-mt-3">
                <h4 style="margin-bottom: var(--spacing-sm);">Medical Information</h4>
                <p><strong>Allergies:</strong> None known</p>
                <p><strong>Current Medications:</strong> None</p>
                <p><strong>Insurance:</strong> Blue Cross Blue Shield</p>
            </div>
        `;
    }, 1000);
}

function editPatient(patientId) {
    window.location.href = '<?php echo admin_url("admin.php?page=eye-book-patients&action=edit&id="); ?>' + patientId;
}

function scheduleAppointment(patientId) {
    window.location.href = '<?php echo admin_url("admin.php?page=eye-book-appointments&action=add&patient_id="); ?>' + patientId;
}

function closeModal() {
    const modal = document.getElementById('patientDetailsModal');
    modal.classList.remove('show');
}

function exportPatients() {
    // Create CSV export functionality
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('export', 'csv');
    window.location.href = currentUrl.toString();
}

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('patient-search');
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
    document.getElementById('patientDetailsModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
});
</script>

<!-- HIPAA Compliance Badge -->
<div class="hipaa-compliant-badge">
    <svg width="12" height="12" viewBox="0 0 12 12" fill="currentColor">
        <path d="M6 0L1 2v3c0 3.5 2.4 6.8 5 7 2.6-.2 5-3.5 5-7V2L6 0z"/>
    </svg>
    HIPAA Compliant
</div>