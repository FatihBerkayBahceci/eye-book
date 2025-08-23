<?php
/**
 * Reports and analytics view
 *
 * @package EyeBook
 * @subpackage Admin/Views
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Reports & Analytics', 'eye-book'); ?></h1>
    <hr class="wp-header-end">

    <div class="eye-book-admin-header">
        <div class="eye-book-filters">
            <select id="report-period">
                <option value="today"><?php _e('Today', 'eye-book'); ?></option>
                <option value="yesterday"><?php _e('Yesterday', 'eye-book'); ?></option>
                <option value="this_week"><?php _e('This Week', 'eye-book'); ?></option>
                <option value="last_week"><?php _e('Last Week', 'eye-book'); ?></option>
                <option value="this_month" selected><?php _e('This Month', 'eye-book'); ?></option>
                <option value="last_month"><?php _e('Last Month', 'eye-book'); ?></option>
                <option value="this_quarter"><?php _e('This Quarter', 'eye-book'); ?></option>
                <option value="last_quarter"><?php _e('Last Quarter', 'eye-book'); ?></option>
                <option value="this_year"><?php _e('This Year', 'eye-book'); ?></option>
                <option value="custom"><?php _e('Custom Range', 'eye-book'); ?></option>
            </select>

            <div id="custom-date-range" style="display: none;">
                <input type="date" id="start-date" class="regular-text">
                <span><?php _e('to', 'eye-book'); ?></span>
                <input type="date" id="end-date" class="regular-text">
            </div>

            <select id="report-location">
                <option value=""><?php _e('All Locations', 'eye-book'); ?></option>
                <?php
                global $wpdb;
                $locations = $wpdb->get_results("SELECT id, name FROM " . EYE_BOOK_TABLE_LOCATIONS . " WHERE status = 'active'");
                foreach ($locations as $location) {
                    echo '<option value="' . esc_attr($location->id) . '">' . esc_html($location->name) . '</option>';
                }
                ?>
            </select>

            <select id="report-provider">
                <option value=""><?php _e('All Providers', 'eye-book'); ?></option>
                <?php
                $providers = $wpdb->get_results("SELECT id, first_name, last_name FROM " . EYE_BOOK_TABLE_PROVIDERS . " WHERE status = 'active'");
                foreach ($providers as $provider) {
                    echo '<option value="' . esc_attr($provider->id) . '">' . esc_html($provider->first_name . ' ' . $provider->last_name) . '</option>';
                }
                ?>
            </select>

            <button type="button" class="button button-primary" id="apply-filters"><?php _e('Apply Filters', 'eye-book'); ?></button>
        </div>

        <div class="eye-book-actions">
            <button type="button" class="button button-secondary" id="export-report"><?php _e('Export Report', 'eye-book'); ?></button>
            <button type="button" class="button button-secondary" id="schedule-report"><?php _e('Schedule Report', 'eye-book'); ?></button>
        </div>
    </div>

    <div class="eye-book-content">
        <!-- Key Metrics Overview -->
        <div class="eye-book-metrics-grid">
            <div class="metric-card">
                <div class="metric-header">
                    <h3><?php _e('Total Appointments', 'eye-book'); ?></h3>
                    <span class="metric-icon dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="metric-value" id="total-appointments">-</div>
                <div class="metric-change" id="appointments-change">-</div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <h3><?php _e('New Patients', 'eye-book'); ?></h3>
                    <span class="metric-icon dashicons dashicons-groups"></span>
                </div>
                <div class="metric-value" id="new-patients">-</div>
                <div class="metric-change" id="patients-change">-</div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <h3><?php _e('No-Show Rate', 'eye-book'); ?></h3>
                    <span class="metric-icon dashicons dashicons-dismiss"></span>
                </div>
                <div class="metric-value" id="no-show-rate">-</div>
                <div class="metric-change" id="no-show-change">-</div>
            </div>

            <div class="metric-card">
                <div class="metric-header">
                    <h3><?php _e('Revenue', 'eye-book'); ?></h3>
                    <span class="metric-icon dashicons dashicons-money-alt"></span>
                </div>
                <div class="metric-value" id="total-revenue">-</div>
                <div class="metric-change" id="revenue-change">-</div>
            </div>
        </div>

        <!-- Charts and Detailed Reports -->
        <div class="eye-book-reports-grid">
            <!-- Appointment Trends Chart -->
            <div class="report-widget">
                <div class="widget-header">
                    <h3><?php _e('Appointment Trends', 'eye-book'); ?></h3>
                    <div class="widget-actions">
                        <select id="trends-chart-type">
                            <option value="line"><?php _e('Line Chart', 'eye-book'); ?></option>
                            <option value="bar"><?php _e('Bar Chart', 'eye-book'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="widget-content">
                    <canvas id="appointments-trend-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Appointment Status Distribution -->
            <div class="report-widget">
                <div class="widget-header">
                    <h3><?php _e('Appointment Status Distribution', 'eye-book'); ?></h3>
                </div>
                <div class="widget-content">
                    <canvas id="status-distribution-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Provider Utilization -->
            <div class="report-widget">
                <div class="widget-header">
                    <h3><?php _e('Provider Utilization', 'eye-book'); ?></h3>
                    <div class="widget-actions">
                        <select id="utilization-metric">
                            <option value="appointments"><?php _e('Appointments', 'eye-book'); ?></option>
                            <option value="hours"><?php _e('Hours Booked', 'eye-book'); ?></option>
                            <option value="revenue"><?php _e('Revenue', 'eye-book'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="widget-content">
                    <canvas id="provider-utilization-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Patient Demographics -->
            <div class="report-widget">
                <div class="widget-header">
                    <h3><?php _e('Patient Demographics', 'eye-book'); ?></h3>
                    <div class="widget-actions">
                        <select id="demographics-type">
                            <option value="age"><?php _e('Age Groups', 'eye-book'); ?></option>
                            <option value="gender"><?php _e('Gender', 'eye-book'); ?></option>
                            <option value="insurance"><?php _e('Insurance', 'eye-book'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="widget-content">
                    <canvas id="demographics-chart" width="400" height="200"></canvas>
                </div>
            </div>

            <!-- Appointment Types -->
            <div class="report-widget">
                <div class="widget-header">
                    <h3><?php _e('Appointment Types', 'eye-book'); ?></h3>
                </div>
                <div class="widget-content">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Appointment Type', 'eye-book'); ?></th>
                                <th><?php _e('Count', 'eye-book'); ?></th>
                                <th><?php _e('Percentage', 'eye-book'); ?></th>
                                <th><?php _e('Avg Duration', 'eye-book'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="appointment-types-data">
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Top Performing Locations -->
            <div class="report-widget">
                <div class="widget-header">
                    <h3><?php _e('Location Performance', 'eye-book'); ?></h3>
                </div>
                <div class="widget-content">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Location', 'eye-book'); ?></th>
                                <th><?php _e('Appointments', 'eye-book'); ?></th>
                                <th><?php _e('New Patients', 'eye-book'); ?></th>
                                <th><?php _e('No-Show Rate', 'eye-book'); ?></th>
                                <th><?php _e('Revenue', 'eye-book'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="location-performance-data">
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detailed Reports Tabs -->
        <div class="eye-book-detailed-reports">
            <nav class="nav-tab-wrapper">
                <a href="#appointments-report" class="nav-tab nav-tab-active"><?php _e('Appointments Report', 'eye-book'); ?></a>
                <a href="#patients-report" class="nav-tab"><?php _e('Patients Report', 'eye-book'); ?></a>
                <a href="#revenue-report" class="nav-tab"><?php _e('Revenue Report', 'eye-book'); ?></a>
                <a href="#operational-report" class="nav-tab"><?php _e('Operational Report', 'eye-book'); ?></a>
                <a href="#compliance-report" class="nav-tab"><?php _e('Compliance Report', 'eye-book'); ?></a>
            </nav>

            <!-- Appointments Report -->
            <div id="appointments-report" class="tab-content active">
                <div class="report-controls">
                    <button type="button" class="button" id="export-appointments-report"><?php _e('Export CSV', 'eye-book'); ?></button>
                    <button type="button" class="button" id="print-appointments-report"><?php _e('Print', 'eye-book'); ?></button>
                </div>
                <table class="wp-list-table widefat fixed striped" id="appointments-report-table">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'eye-book'); ?></th>
                            <th><?php _e('Total', 'eye-book'); ?></th>
                            <th><?php _e('Scheduled', 'eye-book'); ?></th>
                            <th><?php _e('Confirmed', 'eye-book'); ?></th>
                            <th><?php _e('Completed', 'eye-book'); ?></th>
                            <th><?php _e('Cancelled', 'eye-book'); ?></th>
                            <th><?php _e('No-Show', 'eye-book'); ?></th>
                            <th><?php _e('Online Bookings', 'eye-book'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>

            <!-- Patients Report -->
            <div id="patients-report" class="tab-content">
                <div class="report-controls">
                    <button type="button" class="button" id="export-patients-report"><?php _e('Export CSV', 'eye-book'); ?></button>
                </div>
                <table class="wp-list-table widefat fixed striped" id="patients-report-table">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'eye-book'); ?></th>
                            <th><?php _e('New Patients', 'eye-book'); ?></th>
                            <th><?php _e('Returning Patients', 'eye-book'); ?></th>
                            <th><?php _e('Total Active', 'eye-book'); ?></th>
                            <th><?php _e('Age 0-17', 'eye-book'); ?></th>
                            <th><?php _e('Age 18-64', 'eye-book'); ?></th>
                            <th><?php _e('Age 65+', 'eye-book'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>

            <!-- Revenue Report -->
            <div id="revenue-report" class="tab-content">
                <div class="report-controls">
                    <button type="button" class="button" id="export-revenue-report"><?php _e('Export CSV', 'eye-book'); ?></button>
                </div>
                <table class="wp-list-table widefat fixed striped" id="revenue-report-table">
                    <thead>
                        <tr>
                            <th><?php _e('Date', 'eye-book'); ?></th>
                            <th><?php _e('Gross Revenue', 'eye-book'); ?></th>
                            <th><?php _e('Copay Collected', 'eye-book'); ?></th>
                            <th><?php _e('Insurance Claims', 'eye-book'); ?></th>
                            <th><?php _e('Self-Pay', 'eye-book'); ?></th>
                            <th><?php _e('Average Per Visit', 'eye-book'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>

            <!-- Operational Report -->
            <div id="operational-report" class="tab-content">
                <div class="report-controls">
                    <button type="button" class="button" id="export-operational-report"><?php _e('Export CSV', 'eye-book'); ?></button>
                </div>
                <table class="wp-list-table widefat fixed striped" id="operational-report-table">
                    <thead>
                        <tr>
                            <th><?php _e('Metric', 'eye-book'); ?></th>
                            <th><?php _e('Current Period', 'eye-book'); ?></th>
                            <th><?php _e('Previous Period', 'eye-book'); ?></th>
                            <th><?php _e('Change', 'eye-book'); ?></th>
                            <th><?php _e('Target', 'eye-book'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php _e('Average Wait Time', 'eye-book'); ?></td>
                            <td id="avg-wait-time">-</td>
                            <td id="prev-avg-wait-time">-</td>
                            <td id="wait-time-change">-</td>
                            <td><?php _e('< 15 minutes', 'eye-book'); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Provider Utilization Rate', 'eye-book'); ?></td>
                            <td id="utilization-rate">-</td>
                            <td id="prev-utilization-rate">-</td>
                            <td id="utilization-change">-</td>
                            <td><?php _e('80%', 'eye-book'); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Same-Day Appointments', 'eye-book'); ?></td>
                            <td id="same-day-appointments">-</td>
                            <td id="prev-same-day">-</td>
                            <td id="same-day-change">-</td>
                            <td><?php _e('20%', 'eye-book'); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e('Patient Satisfaction', 'eye-book'); ?></td>
                            <td id="patient-satisfaction">-</td>
                            <td id="prev-satisfaction">-</td>
                            <td id="satisfaction-change">-</td>
                            <td><?php _e('4.5/5', 'eye-book'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Compliance Report -->
            <div id="compliance-report" class="tab-content">
                <div class="report-controls">
                    <button type="button" class="button" id="export-compliance-report"><?php _e('Export PDF', 'eye-book'); ?></button>
                </div>
                
                <div class="compliance-sections">
                    <div class="compliance-section">
                        <h4><?php _e('HIPAA Compliance Metrics', 'eye-book'); ?></h4>
                        <table class="wp-list-table widefat fixed striped">
                            <tbody>
                                <tr>
                                    <td><?php _e('Total Audit Log Entries', 'eye-book'); ?></td>
                                    <td id="total-audit-entries">-</td>
                                </tr>
                                <tr>
                                    <td><?php _e('Patient Data Access Events', 'eye-book'); ?></td>
                                    <td id="data-access-events">-</td>
                                </tr>
                                <tr>
                                    <td><?php _e('Failed Login Attempts', 'eye-book'); ?></td>
                                    <td id="failed-logins">-</td>
                                </tr>
                                <tr>
                                    <td><?php _e('Security Incidents', 'eye-book'); ?></td>
                                    <td id="security-incidents">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="compliance-section">
                        <h4><?php _e('Data Retention Compliance', 'eye-book'); ?></h4>
                        <table class="wp-list-table widefat fixed striped">
                            <tbody>
                                <tr>
                                    <td><?php _e('Records Due for Archival', 'eye-book'); ?></td>
                                    <td id="records-for-archival">-</td>
                                </tr>
                                <tr>
                                    <td><?php _e('Records Due for Deletion', 'eye-book'); ?></td>
                                    <td id="records-for-deletion">-</td>
                                </tr>
                                <tr>
                                    <td><?php _e('Audit Logs Retention Status', 'eye-book'); ?></td>
                                    <td id="audit-retention-status">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scheduled Reports Modal -->
<div id="schedule-report-modal" class="eye-book-modal" style="display: none;">
    <div class="eye-book-modal-content">
        <div class="eye-book-modal-header">
            <h2><?php _e('Schedule Report', 'eye-book'); ?></h2>
            <button type="button" class="eye-book-modal-close">&times;</button>
        </div>
        <div class="eye-book-modal-body">
            <form id="schedule-report-form">
                <div class="eye-book-form-row">
                    <div class="eye-book-form-group">
                        <label for="report-name"><?php _e('Report Name', 'eye-book'); ?></label>
                        <input type="text" id="report-name" name="report_name" class="regular-text" required>
                    </div>
                    
                    <div class="eye-book-form-group">
                        <label for="report-type"><?php _e('Report Type', 'eye-book'); ?></label>
                        <select id="report-type" name="report_type" class="regular-text" required>
                            <option value="appointments"><?php _e('Appointments Report', 'eye-book'); ?></option>
                            <option value="patients"><?php _e('Patients Report', 'eye-book'); ?></option>
                            <option value="revenue"><?php _e('Revenue Report', 'eye-book'); ?></option>
                            <option value="operational"><?php _e('Operational Report', 'eye-book'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="eye-book-form-row">
                    <div class="eye-book-form-group">
                        <label for="schedule-frequency"><?php _e('Frequency', 'eye-book'); ?></label>
                        <select id="schedule-frequency" name="frequency" class="regular-text" required>
                            <option value="daily"><?php _e('Daily', 'eye-book'); ?></option>
                            <option value="weekly"><?php _e('Weekly', 'eye-book'); ?></option>
                            <option value="monthly"><?php _e('Monthly', 'eye-book'); ?></option>
                            <option value="quarterly"><?php _e('Quarterly', 'eye-book'); ?></option>
                        </select>
                    </div>
                    
                    <div class="eye-book-form-group">
                        <label for="report-format"><?php _e('Format', 'eye-book'); ?></label>
                        <select id="report-format" name="format" class="regular-text">
                            <option value="pdf">PDF</option>
                            <option value="csv">CSV</option>
                            <option value="excel">Excel</option>
                        </select>
                    </div>
                </div>

                <div class="eye-book-form-row">
                    <div class="eye-book-form-group full-width">
                        <label for="recipients"><?php _e('Email Recipients', 'eye-book'); ?></label>
                        <input type="text" id="recipients" name="recipients" class="regular-text" placeholder="email1@example.com, email2@example.com">
                    </div>
                </div>
            </form>
        </div>
        <div class="eye-book-modal-footer">
            <button type="button" class="button button-secondary" id="schedule-report-cancel"><?php _e('Cancel', 'eye-book'); ?></button>
            <button type="button" class="button button-primary" id="schedule-report-save"><?php _e('Schedule Report', 'eye-book'); ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize reports
    EyeBook.Reports.init();
});
</script>