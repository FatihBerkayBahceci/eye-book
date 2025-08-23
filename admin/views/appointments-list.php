<?php
/**
 * Admin appointments list view
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

<div class="wrap eye-book-appointments">
    <h1 class="wp-heading-inline">
        <?php _e('Appointments', 'eye-book'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&action=add'); ?>" class="page-title-action">
        <?php _e('Add New', 'eye-book'); ?>
    </a>
    
    <hr class="wp-header-end">

    <!-- Filters -->
    <div class="eye-book-filters">
        <form method="GET" action="">
            <input type="hidden" name="page" value="eye-book-appointments">
            
            <label for="date_filter"><?php _e('Date:', 'eye-book'); ?></label>
            <input type="date" id="date_filter" name="date_filter" value="<?php echo esc_attr($_GET['date_filter'] ?? ''); ?>">
            
            <label for="status_filter"><?php _e('Status:', 'eye-book'); ?></label>
            <select id="status_filter" name="status_filter">
                <option value=""><?php _e('All Statuses', 'eye-book'); ?></option>
                <option value="scheduled" <?php selected($_GET['status_filter'] ?? '', 'scheduled'); ?>><?php _e('Scheduled', 'eye-book'); ?></option>
                <option value="confirmed" <?php selected($_GET['status_filter'] ?? '', 'confirmed'); ?>><?php _e('Confirmed', 'eye-book'); ?></option>
                <option value="completed" <?php selected($_GET['status_filter'] ?? '', 'completed'); ?>><?php _e('Completed', 'eye-book'); ?></option>
                <option value="cancelled" <?php selected($_GET['status_filter'] ?? '', 'cancelled'); ?>><?php _e('Cancelled', 'eye-book'); ?></option>
                <option value="no_show" <?php selected($_GET['status_filter'] ?? '', 'no_show'); ?>><?php _e('No Show', 'eye-book'); ?></option>
            </select>
            
            <label for="provider_filter"><?php _e('Provider:', 'eye-book'); ?></label>
            <select id="provider_filter" name="provider_filter">
                <option value=""><?php _e('All Providers', 'eye-book'); ?></option>
                <?php
                // Get providers for filter
                global $wpdb;
                $providers = $wpdb->get_results(
                    "SELECT p.id, u.display_name 
                     FROM " . EYE_BOOK_TABLE_PROVIDERS . " p
                     LEFT JOIN {$wpdb->users} u ON p.wp_user_id = u.ID
                     WHERE p.status = 'active'
                     ORDER BY u.display_name"
                );
                foreach ($providers as $provider) {
                    $selected = selected($_GET['provider_filter'] ?? '', $provider->id, false);
                    echo '<option value="' . $provider->id . '" ' . $selected . '>' . esc_html($provider->display_name) . '</option>';
                }
                ?>
            </select>
            
            <input type="submit" class="button" value="<?php _e('Filter', 'eye-book'); ?>">
            
            <?php if (!empty($_GET['date_filter']) || !empty($_GET['status_filter']) || !empty($_GET['provider_filter'])): ?>
                <a href="<?php echo admin_url('admin.php?page=eye-book-appointments'); ?>" class="button">
                    <?php _e('Clear Filters', 'eye-book'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Appointments Table -->
    <div class="eye-book-table-container">
        <?php if (!empty($appointments) && !empty($appointments['items'])): ?>
            <table class="wp-list-table widefat fixed striped eye-book-appointments-table">
                <thead>
                    <tr>
                        <th scope="col" class="column-patient"><?php _e('Patient', 'eye-book'); ?></th>
                        <th scope="col" class="column-provider"><?php _e('Provider', 'eye-book'); ?></th>
                        <th scope="col" class="column-datetime"><?php _e('Date & Time', 'eye-book'); ?></th>
                        <th scope="col" class="column-duration"><?php _e('Duration', 'eye-book'); ?></th>
                        <th scope="col" class="column-status"><?php _e('Status', 'eye-book'); ?></th>
                        <th scope="col" class="column-actions"><?php _e('Actions', 'eye-book'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments['items'] as $appointment): 
                        // Get patient info
                        $patient = get_user_meta($appointment->patient_id, 'eye_book_patient_data', true);
                        $patient_name = $patient ? ($patient['first_name'] . ' ' . $patient['last_name']) : __('Unknown Patient', 'eye-book');
                        
                        // Get provider info  
                        $provider = get_user_by('ID', $appointment->provider_user_id ?? 0);
                        $provider_name = $provider ? $provider->display_name : __('Unknown Provider', 'eye-book');
                        
                        // Calculate duration
                        $start = strtotime($appointment->start_datetime);
                        $end = strtotime($appointment->end_datetime);
                        $duration = ($end - $start) / 60; // minutes
                    ?>
                    <tr>
                        <td class="column-patient">
                            <strong><?php echo esc_html($patient_name); ?></strong>
                            <?php if ($appointment->notes): ?>
                                <br><small class="description"><?php echo esc_html(wp_trim_words($appointment->notes, 8)); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="column-provider">
                            <?php echo esc_html($provider_name); ?>
                        </td>
                        <td class="column-datetime">
                            <strong><?php echo date_i18n(get_option('date_format'), $start); ?></strong><br>
                            <span class="time"><?php echo date_i18n(get_option('time_format'), $start); ?></span>
                        </td>
                        <td class="column-duration">
                            <?php echo sprintf(_n('%d minute', '%d minutes', $duration, 'eye-book'), $duration); ?>
                        </td>
                        <td class="column-status">
                            <span class="eye-book-status eye-book-status-<?php echo esc_attr($appointment->status); ?>">
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $appointment->status))); ?>
                            </span>
                        </td>
                        <td class="column-actions">
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&action=edit&id=' . $appointment->id); ?>">
                                        <?php _e('Edit', 'eye-book'); ?>
                                    </a>
                                </span>
                                <?php if ($appointment->status !== 'completed' && $appointment->status !== 'cancelled'): ?>
                                | <span class="complete">
                                    <a href="#" onclick="updateAppointmentStatus(<?php echo $appointment->id; ?>, 'completed')">
                                        <?php _e('Mark Complete', 'eye-book'); ?>
                                    </a>
                                </span>
                                | <span class="cancel">
                                    <a href="#" onclick="updateAppointmentStatus(<?php echo $appointment->id; ?>, 'cancelled')" class="delete">
                                        <?php _e('Cancel', 'eye-book'); ?>
                                    </a>
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($appointments['total_pages'] > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $current_page = max(1, intval($_GET['paged'] ?? 1));
                    $total_pages = $appointments['total_pages'];
                    $base_url = admin_url('admin.php?page=eye-book-appointments');
                    
                    // Add current filters to pagination links
                    $query_args = array();
                    if (!empty($_GET['date_filter'])) $query_args['date_filter'] = $_GET['date_filter'];
                    if (!empty($_GET['status_filter'])) $query_args['status_filter'] = $_GET['status_filter'];
                    if (!empty($_GET['provider_filter'])) $query_args['provider_filter'] = $_GET['provider_filter'];
                    
                    if (!empty($query_args)) {
                        $base_url .= '&' . http_build_query($query_args);
                    }
                    
                    echo paginate_links(array(
                        'base' => $base_url . '%_%',
                        'format' => '&paged=%#%',
                        'current' => $current_page,
                        'total' => $total_pages,
                        'prev_text' => '&lsaquo; ' . __('Previous', 'eye-book'),
                        'next_text' => __('Next', 'eye-book') . ' &rsaquo;'
                    ));
                    ?>
                </div>
            </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="eye-book-empty-state">
                <div class="empty-state-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <h3><?php _e('No Appointments Found', 'eye-book'); ?></h3>
                <p><?php _e('No appointments match your current filters, or no appointments have been scheduled yet.', 'eye-book'); ?></p>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&action=add'); ?>" class="button button-primary">
                        <?php _e('Schedule New Appointment', 'eye-book'); ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Quick status update function
    window.updateAppointmentStatus = function(appointmentId, newStatus) {
        if (!confirm(eyeBookAdmin.strings.confirm_delete)) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'eye_book_update_appointment_status',
            appointment_id: appointmentId,
            status: newStatus,
            nonce: eyeBookAdmin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert(response.data.message || eyeBookAdmin.strings.error);
            }
        });
    };
    
    // Auto-refresh every 5 minutes
    setInterval(function() {
        var currentUrl = window.location.href;
        if (currentUrl.indexOf('paged=') === -1 && currentUrl.indexOf('action=') === -1) {
            location.reload();
        }
    }, 300000); // 5 minutes
});
</script>