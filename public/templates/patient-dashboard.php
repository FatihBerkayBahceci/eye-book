<?php
/**
 * Patient Dashboard Template
 *
 * @package EyeBook
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if patient is logged in
if (!is_user_logged_in() || !current_user_can('eye_book_patient_access')) {
    wp_redirect(home_url());
    exit;
}

$patient_id = get_current_user_id();
$patient = new Eye_Book_Patient();
$patient_data = $patient->get_by_user_id($patient_id);

if (!$patient_data) {
    wp_die(__('Patient data not found.', 'eye-book'));
}

$appointment = new Eye_Book_Appointment();
$patient_forms = new Eye_Book_Patient_Form();

// Get upcoming appointments
$upcoming_appointments = $appointment->get_by_patient($patient_id, array(
    'status' => 'confirmed',
    'start_date' => date('Y-m-d'),
    'orderby' => 'appointment_date',
    'order' => 'ASC',
    'limit' => 5
));

// Get recent appointments
$recent_appointments = $appointment->get_by_patient($patient_id, array(
    'end_date' => date('Y-m-d', strtotime('-1 day')),
    'orderby' => 'appointment_date',
    'order' => 'DESC',
    'limit' => 5
));

// Get pending forms
$pending_forms = $patient_forms->get_by_patient($patient_id, array(
    'status' => 'pending',
    'limit' => 5
));

// Get patient statistics
$total_appointments = $appointment->get_count_by_patient($patient_id);
$completed_appointments = $appointment->get_count_by_patient($patient_id, array('status' => 'completed'));
$upcoming_count = $appointment->get_count_by_patient($patient_id, array(
    'status' => 'confirmed',
    'start_date' => date('Y-m-d')
));
?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php _e('Patient Dashboard', 'eye-book'); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('eye-book-patient-dashboard'); ?>>

<div class="eye-book-container">
    <!-- Header -->
    <header class="eye-book-header">
        <div class="eye-book-header-content">
            <div class="eye-book-logo">
                <h1><?php bloginfo('name'); ?></h1>
            </div>
            <nav class="eye-book-nav">
                <a href="<?php echo esc_url(add_query_arg('page', 'dashboard')); ?>" class="active"><?php _e('Dashboard', 'eye-book'); ?></a>
                <a href="<?php echo esc_url(add_query_arg('page', 'appointments')); ?>"><?php _e('Appointments', 'eye-book'); ?></a>
                <a href="<?php echo esc_url(add_query_arg('page', 'forms')); ?>"><?php _e('Forms', 'eye-book'); ?></a>
                <a href="<?php echo esc_url(add_query_arg('page', 'profile')); ?>"><?php _e('Profile', 'eye-book'); ?></a>
                <a href="<?php echo wp_logout_url(home_url()); ?>"><?php _e('Logout', 'eye-book'); ?></a>
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="eye-book-main">
        <div class="eye-book-dashboard">
            <!-- Welcome Section -->
            <div class="eye-book-welcome">
                <h2><?php printf(__('Welcome back, %s!', 'eye-book'), esc_html($patient_data->first_name)); ?></h2>
                <p><?php _e('Here\'s an overview of your eye care journey.', 'eye-book'); ?></p>
            </div>

            <!-- Statistics Cards -->
            <div class="eye-book-stats-grid">
                <div class="eye-book-stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-content">
                        <h3><?php echo $upcoming_count; ?></h3>
                        <p><?php _e('Upcoming Appointments', 'eye-book'); ?></p>
                    </div>
                </div>
                
                <div class="eye-book-stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?php echo $completed_appointments; ?></h3>
                        <p><?php _e('Completed Visits', 'eye-book'); ?></p>
                    </div>
                </div>
                
                <div class="eye-book-stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <h3><?php echo count($pending_forms); ?></h3>
                        <p><?php _e('Pending Forms', 'eye-book'); ?></p>
                    </div>
                </div>
                
                <div class="eye-book-stat-card">
                    <div class="stat-icon">👁️</div>
                    <div class="stat-content">
                        <h3><?php echo $total_appointments; ?></h3>
                        <p><?php _e('Total Appointments', 'eye-book'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="eye-book-quick-actions">
                <h3><?php _e('Quick Actions', 'eye-book'); ?></h3>
                <div class="action-buttons">
                    <a href="<?php echo esc_url(add_query_arg('page', 'book-appointment')); ?>" class="eye-book-btn eye-book-btn-primary">
                        📅 <?php _e('Book New Appointment', 'eye-book'); ?>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('page', 'forms')); ?>" class="eye-book-btn eye-book-btn-secondary">
                        📝 <?php _e('Complete Forms', 'eye-book'); ?>
                    </a>
                    <a href="<?php echo esc_url(add_query_arg('page', 'profile')); ?>" class="eye-book-btn eye-book-btn-secondary">
                        👤 <?php _e('Update Profile', 'eye-book'); ?>
                    </a>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="eye-book-content-grid">
                <!-- Upcoming Appointments -->
                <div class="eye-book-section">
                    <h3><?php _e('Upcoming Appointments', 'eye-book'); ?></h3>
                    <?php if (empty($upcoming_appointments)): ?>
                        <p class="eye-book-no-data"><?php _e('No upcoming appointments scheduled.', 'eye-book'); ?></p>
                        <a href="<?php echo esc_url(add_query_arg('page', 'book-appointment')); ?>" class="eye-book-btn eye-book-btn-primary">
                            <?php _e('Book Your First Appointment', 'eye-book'); ?>
                        </a>
                    <?php else: ?>
                        <div class="eye-book-appointments-list">
                            <?php foreach ($upcoming_appointments as $apt): ?>
                                <div class="eye-book-appointment-card">
                                    <div class="appointment-date">
                                        <div class="date-day"><?php echo date('j', strtotime($apt->appointment_date)); ?></div>
                                        <div class="date-month"><?php echo date('M', strtotime($apt->appointment_date)); ?></div>
                                    </div>
                                    <div class="appointment-details">
                                        <h4><?php echo esc_html($apt->appointment_type_name); ?></h4>
                                        <p class="appointment-time">
                                            <i class="dashicons dashicons-clock"></i>
                                            <?php echo date_i18n('l, F j, Y \a\t g:i A', strtotime($apt->appointment_date . ' ' . $apt->appointment_time)); ?>
                                        </p>
                                        <p class="appointment-provider">
                                            <i class="dashicons dashicons-admin-users"></i>
                                            <?php echo esc_html($apt->provider_name); ?>
                                        </p>
                                        <p class="appointment-location">
                                            <i class="dashicons dashicons-location"></i>
                                            <?php echo esc_html($apt->location_name); ?>
                                        </p>
                                    </div>
                                    <div class="appointment-actions">
                                        <a href="<?php echo esc_url(add_query_arg(array('page' => 'appointments', 'action' => 'reschedule', 'id' => $apt->id))); ?>" 
                                           class="eye-book-btn eye-book-btn-small">
                                            <?php _e('Reschedule', 'eye-book'); ?>
                                        </a>
                                        <a href="<?php echo esc_url(add_query_arg(array('page' => 'appointments', 'action' => 'cancel', 'id' => $apt->id))); ?>" 
                                           class="eye-book-btn eye-book-btn-small eye-book-btn-danger"
                                           onclick="return confirm('<?php _e('Are you sure you want to cancel this appointment?', 'eye-book'); ?>')">
                                            <?php _e('Cancel', 'eye-book'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="eye-book-section-footer">
                            <a href="<?php echo esc_url(add_query_arg('page', 'appointments')); ?>" class="eye-book-btn eye-book-btn-secondary">
                                <?php _e('View All Appointments', 'eye-book'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Activity -->
                <div class="eye-book-section">
                    <h3><?php _e('Recent Activity', 'eye-book'); ?></h3>
                    <?php if (empty($recent_appointments) && empty($pending_forms)): ?>
                        <p class="eye-book-no-data"><?php _e('No recent activity to display.', 'eye-book'); ?></p>
                    <?php else: ?>
                        <div class="eye-book-activity-list">
                            <?php foreach ($recent_appointments as $apt): ?>
                                <div class="eye-book-activity-item">
                                    <div class="activity-icon">✅</div>
                                    <div class="activity-content">
                                        <p><strong><?php echo esc_html($apt->appointment_type_name); ?></strong> <?php _e('completed', 'eye-book'); ?></p>
                                        <small><?php echo date_i18n('M j, Y', strtotime($apt->appointment_date)); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php foreach ($pending_forms as $form): ?>
                                <div class="eye-book-activity-item">
                                    <div class="activity-icon">📝</div>
                                    <div class="activity-content">
                                        <p><strong><?php echo esc_html($form->form_type); ?></strong> <?php _e('form pending', 'eye-book'); ?></p>
                                        <small><?php echo date_i18n('M j, Y', strtotime($form->submitted_at)); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Health Tips -->
            <div class="eye-book-health-tips">
                <h3><?php _e('Eye Health Tips', 'eye-book'); ?></h3>
                <div class="tips-grid">
                    <div class="tip-card">
                        <div class="tip-icon">👁️</div>
                        <h4><?php _e('20-20-20 Rule', 'eye-book'); ?></h4>
                        <p><?php _e('Every 20 minutes, look at something 20 feet away for 20 seconds to reduce eye strain.', 'eye-book'); ?></p>
                    </div>
                    <div class="tip-card">
                        <div class="tip-icon">🕶️</div>
                        <h4><?php _e('UV Protection', 'eye-book'); ?></h4>
                        <p><?php _e('Wear sunglasses with UV protection to shield your eyes from harmful sun rays.', 'eye-book'); ?></p>
                    </div>
                    <div class="tip-card">
                        <div class="tip-icon">🥕</div>
                        <h4><?php _e('Healthy Diet', 'eye-book'); ?></h4>
                        <p><?php _e('Eat foods rich in omega-3, lutein, and vitamins C and E for better eye health.', 'eye-book'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="eye-book-footer">
        <div class="eye-book-footer-content">
            <p>&copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. <?php _e('All rights reserved.', 'eye-book'); ?></p>
            <p><?php _e('For medical emergencies, please call 911 or your local emergency services.', 'eye-book'); ?></p>
        </div>
    </footer>
</div>

<style>
.eye-book-container {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
    line-height: 1.6;
    color: #333;
    background: #f5f5f5;
    min-height: 100vh;
}

.eye-book-header {
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: sticky;
    top: 0;
    z-index: 100;
}

.eye-book-header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.eye-book-logo h1 {
    margin: 0;
    color: #007cba;
    font-size: 1.5rem;
}

.eye-book-nav {
    display: flex;
    gap: 1rem;
}

.eye-book-nav a {
    text-decoration: none;
    color: #666;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.eye-book-nav a:hover,
.eye-book-nav a.active {
    background: #007cba;
    color: white;
}

.eye-book-main {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem;
}

.eye-book-welcome {
    text-align: center;
    margin-bottom: 2rem;
    padding: 2rem;
    background: linear-gradient(135deg, #007cba, #005a87);
    color: white;
    border-radius: 8px;
}

.eye-book-welcome h2 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
}

.eye-book-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.eye-book-stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    font-size: 2rem;
}

.stat-content h3 {
    margin: 0;
    font-size: 2rem;
    color: #007cba;
}

.stat-content p {
    margin: 0;
    color: #666;
}

.eye-book-quick-actions {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 2rem;
}

.eye-book-quick-actions h3 {
    margin: 0 0 1rem 0;
    color: #333;
}

.action-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.eye-book-btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    text-decoration: none;
    border-radius: 4px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
}

.eye-book-btn-primary {
    background: #007cba;
    color: white;
}

.eye-book-btn-primary:hover {
    background: #005a87;
    color: white;
}

.eye-book-btn-secondary {
    background: #f0f0f0;
    color: #333;
}

.eye-book-btn-secondary:hover {
    background: #e0e0e0;
    color: #333;
}

.eye-book-btn-danger {
    background: #dc3545;
    color: white;
}

.eye-book-btn-danger:hover {
    background: #c82333;
    color: white;
}

.eye-book-btn-small {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

.eye-book-content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.eye-book-section {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.eye-book-section h3 {
    margin: 0 0 1rem 0;
    color: #333;
    border-bottom: 2px solid #007cba;
    padding-bottom: 0.5rem;
}

.eye-book-no-data {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 2rem;
}

.eye-book-appointments-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.eye-book-appointment-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.eye-book-appointment-card:hover {
    border-color: #007cba;
    box-shadow: 0 2px 8px rgba(0,123,255,0.1);
}

.appointment-date {
    text-align: center;
    min-width: 60px;
}

.date-day {
    font-size: 1.5rem;
    font-weight: bold;
    color: #007cba;
}

.date-month {
    font-size: 0.875rem;
    color: #666;
    text-transform: uppercase;
}

.appointment-details {
    flex: 1;
}

.appointment-details h4 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.appointment-details p {
    margin: 0.25rem 0;
    color: #666;
    font-size: 0.875rem;
}

.appointment-details i {
    margin-right: 0.5rem;
    color: #007cba;
}

.appointment-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.eye-book-activity-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.eye-book-activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: #f9f9f9;
    border-radius: 6px;
}

.activity-icon {
    font-size: 1.25rem;
}

.activity-content p {
    margin: 0;
    color: #333;
}

.activity-content small {
    color: #666;
}

.eye-book-section-footer {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #e0e0e0;
    text-align: center;
}

.eye-book-health-tips {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.eye-book-health-tips h3 {
    margin: 0 0 1rem 0;
    color: #333;
    border-bottom: 2px solid #007cba;
    padding-bottom: 0.5rem;
}

.tips-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
}

.tip-card {
    text-align: center;
    padding: 1.5rem;
    border: 1px solid #e0e0e0;
    border-radius: 6px;
    transition: all 0.3s ease;
}

.tip-card:hover {
    border-color: #007cba;
    box-shadow: 0 2px 8px rgba(0,123,255,0.1);
}

.tip-icon {
    font-size: 2rem;
    margin-bottom: 1rem;
}

.tip-card h4 {
    margin: 0 0 0.5rem 0;
    color: #333;
}

.tip-card p {
    margin: 0;
    color: #666;
    font-size: 0.875rem;
}

.eye-book-footer {
    background: #333;
    color: white;
    text-align: center;
    padding: 2rem;
    margin-top: 3rem;
}

.eye-book-footer-content p {
    margin: 0.5rem 0;
}

/* Responsive Design */
@media (max-width: 768px) {
    .eye-book-header-content {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
    
    .eye-book-nav {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .eye-book-main {
        padding: 1rem;
    }
    
    .eye-book-content-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .eye-book-stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .tips-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .eye-book-appointment-card {
        flex-direction: column;
        text-align: center;
    }
    
    .appointment-actions {
        flex-direction: row;
        justify-content: center;
    }
}
</style>

<?php wp_footer(); ?>
</body>
</html>
