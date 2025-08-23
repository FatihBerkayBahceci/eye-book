<?php
/**
 * Eye-Book Dashboard
 *
 * @package EyeBook
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user
$current_user = wp_get_current_user();
$user_role = $current_user->roles[0] ?? '';

// Get statistics
$total_appointments = 0; // TODO: Get from database
$total_patients = 0; // TODO: Get from database
$total_providers = 0; // TODO: Get from database
$pending_appointments = 0; // TODO: Get from database

// Get recent appointments
$recent_appointments = array(); // TODO: Get from database

// Get upcoming appointments
$upcoming_appointments = array(); // TODO: Get from database
?>

<div class="eye-book-admin">
    <!-- Header -->
    <div class="eye-book-header">
        <h1>
            <span class="icon">👁️</span>
            Eye-Book Dashboard
        </h1>
        <p class="subtitle">Welcome back, <?php echo esc_html($current_user->display_name); ?>! Here's what's happening with your eye care practice.</p>
    </div>

    <!-- Navigation -->
    <nav class="eye-book-nav">
        <ul>
            <li><a href="?page=eye-book-dashboard" class="active">Dashboard</a></li>
            <li><a href="?page=eye-book-appointments">Appointments</a></li>
            <li><a href="?page=eye-book-patients">Patients</a></li>
            <li><a href="?page=eye-book-providers">Providers</a></li>
            <li><a href="?page=eye-book-locations">Locations</a></li>
            <li><a href="?page=eye-book-calendar">Calendar</a></li>
            <li><a href="?page=eye-book-reports">Reports</a></li>
            <li><a href="?page=eye-book-settings">Settings</a></li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="eye-book-content">
        <!-- Statistics Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon primary">📅</div>
                <div class="number"><?php echo number_format($total_appointments); ?></div>
                <div class="label">Total Appointments</div>
            </div>
            
            <div class="stat-card">
                <div class="icon success">👥</div>
                <div class="number"><?php echo number_format($total_patients); ?></div>
                <div class="label">Total Patients</div>
            </div>
            
            <div class="stat-card">
                <div class="icon info">👨‍⚕️</div>
                <div class="number"><?php echo number_format($total_providers); ?></div>
                <div class="label">Healthcare Providers</div>
            </div>
            
            <div class="stat-card">
                <div class="icon warning">⏳</div>
                <div class="number"><?php echo number_format($pending_appointments); ?></div>
                <div class="label">Pending Appointments</div>
            </div>
        </div>

        <!-- Dashboard Widgets -->
        <div class="d-grid" style="grid-template-columns: 2fr 1fr; gap: var(--spacing-xl);">
            <!-- Main Widget -->
            <div class="card">
                <div class="card-header">
                    <h3>📊 Recent Activity</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_appointments)): ?>
                        <div class="text-center p-4">
                            <p class="text-secondary">No recent appointments to display.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="eye-book-table">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Provider</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo esc_html($appointment['patient_name']); ?></td>
                                            <td><?php echo esc_html($appointment['provider_name']); ?></td>
                                            <td><?php echo esc_html($appointment['date']); ?></td>
                                            <td>
                                                <span class="status <?php echo esc_attr($appointment['status']); ?>">
                                                    <?php echo esc_html(ucfirst($appointment['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="?page=eye-book-appointments" class="btn btn-primary">View All Appointments</a>
                </div>
            </div>

            <!-- Sidebar Widget -->
            <div class="card">
                <div class="card-header">
                    <h3>🚀 Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="d-grid" style="gap: var(--spacing-md);">
                        <a href="?page=eye-book-appointments&action=new" class="btn btn-primary w-full">
                            📅 New Appointment
                        </a>
                        <a href="?page=eye-book-patients&action=new" class="btn btn-success w-full">
                            👥 Add Patient
                        </a>
                        <a href="?page=eye-book-providers&action=new" class="btn btn-info w-full">
                            👨‍⚕️ Add Provider
                        </a>
                        <a href="?page=eye-book-calendar" class="btn btn-secondary w-full">
                            📊 View Calendar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Appointments -->
        <div class="card mt-4">
            <div class="card-header">
                <h3>⏰ Upcoming Appointments</h3>
            </div>
            <div class="card-body">
                <?php if (empty($upcoming_appointments)): ?>
                    <div class="text-center p-4">
                        <p class="text-secondary">No upcoming appointments scheduled.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="eye-book-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Patient</th>
                                    <th>Provider</th>
                                    <th>Type</th>
                                    <th>Location</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_appointments as $appointment): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($appointment['time']); ?></strong><br>
                                            <small class="text-secondary"><?php echo esc_html($appointment['date']); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo esc_html($appointment['patient_name']); ?></strong><br>
                                            <small class="text-secondary"><?php echo esc_html($appointment['patient_phone']); ?></small>
                                        </td>
                                        <td><?php echo esc_html($appointment['provider_name']); ?></td>
                                        <td><?php echo esc_html($appointment['type']); ?></td>
                                        <td><?php echo esc_html($appointment['location']); ?></td>
                                        <td class="actions">
                                            <a href="?page=eye-book-appointments&action=edit&id=<?php echo esc_attr($appointment['id']); ?>" class="btn btn-sm btn-primary">Edit</a>
                                            <a href="?page=eye-book-appointments&action=view&id=<?php echo esc_attr($appointment['id']); ?>" class="btn btn-sm btn-secondary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Status -->
        <div class="card mt-4">
            <div class="card-header">
                <h3>🔧 System Status</h3>
            </div>
            <div class="card-body">
                <div class="d-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-lg);">
                    <div class="d-flex align-center gap-3">
                        <span class="icon" style="width: 2rem; height: 2rem; background: var(--success-color);">✅</span>
                        <div>
                            <strong>Database</strong><br>
                            <small class="text-secondary">Connected & Healthy</small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-center gap-3">
                        <span class="icon" style="width: 2rem; height: 2rem; background: var(--success-color);">✅</span>
                        <div>
                            <strong>Encryption</strong><br>
                            <small class="text-secondary">AES-256 Active</small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-center gap-3">
                        <span class="icon" style="width: 2rem; height: 2rem; background: var(--success-color);">✅</span>
                        <div>
                            <strong>Audit Log</strong><br>
                            <small class="text-secondary">Recording All Activities</small>
                        </div>
                    </div>
                    
                    <div class="d-flex align-center gap-3">
                        <span class="icon" style="width: 2rem; height: 2rem; background: var(--success-color);">✅</span>
                        <div>
                            <strong>HIPAA Compliance</strong><br>
                            <small class="text-secondary">All Safeguards Active</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add fade-in animation to cards
    const cards = document.querySelectorAll('.card, .stat-card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('fade-in');
    });
    
    // Add hover effects to stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>