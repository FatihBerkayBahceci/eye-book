<?php
/**
 * Admin patients list view
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

<div class="wrap eye-book-patients">
    <h1 class="wp-heading-inline">
        <?php _e('Patients', 'eye-book'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=eye-book-patients&action=add'); ?>" class="page-title-action">
        <?php _e('Add New Patient', 'eye-book'); ?>
    </a>
    
    <hr class="wp-header-end">

    <!-- Search Form -->
    <div class="eye-book-search-form">
        <form method="GET" action="">
            <input type="hidden" name="page" value="eye-book-patients">
            <p class="search-box">
                <label class="screen-reader-text" for="patient-search-input"><?php _e('Search Patients:', 'eye-book'); ?></label>
                <input type="search" id="patient-search-input" name="s" value="<?php echo esc_attr($_GET['s'] ?? ''); ?>" placeholder="<?php _e('Search patients by name, email, or phone...', 'eye-book'); ?>">
                <input type="submit" id="search-submit" class="button" value="<?php _e('Search Patients', 'eye-book'); ?>">
            </p>
            
            <?php if (!empty($_GET['s'])): ?>
                <p>
                    <a href="<?php echo admin_url('admin.php?page=eye-book-patients'); ?>" class="button">
                        <?php _e('Clear Search', 'eye-book'); ?>
                    </a>
                </p>
            <?php endif; ?>
        </form>
    </div>

    <!-- Patients Table -->
    <div class="eye-book-table-container">
        <?php if (!empty($patients) && !empty($patients['items'])): ?>
            <table class="wp-list-table widefat fixed striped eye-book-patients-table">
                <thead>
                    <tr>
                        <th scope="col" class="column-patient"><?php _e('Patient', 'eye-book'); ?></th>
                        <th scope="col" class="column-contact"><?php _e('Contact Information', 'eye-book'); ?></th>
                        <th scope="col" class="column-dob"><?php _e('Date of Birth', 'eye-book'); ?></th>
                        <th scope="col" class="column-last-visit"><?php _e('Last Visit', 'eye-book'); ?></th>
                        <th scope="col" class="column-total-visits"><?php _e('Total Visits', 'eye-book'); ?></th>
                        <th scope="col" class="column-actions"><?php _e('Actions', 'eye-book'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($patients['items'] as $patient): 
                        // Get last appointment
                        global $wpdb;
                        $last_appointment = $wpdb->get_row($wpdb->prepare(
                            "SELECT start_datetime FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " 
                             WHERE patient_id = %d AND status IN ('completed', 'confirmed') 
                             ORDER BY start_datetime DESC LIMIT 1",
                            $patient->id
                        ));
                        
                        // Get total visits count
                        $total_visits = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " 
                             WHERE patient_id = %d AND status = 'completed'",
                            $patient->id
                        ));
                    ?>
                    <tr>
                        <td class="column-patient">
                            <strong>
                                <a href="<?php echo admin_url('admin.php?page=eye-book-patients&action=view&id=' . $patient->id); ?>">
                                    <?php echo esc_html($patient->first_name . ' ' . $patient->last_name); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="view">
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-patients&action=view&id=' . $patient->id); ?>">
                                        <?php _e('View Profile', 'eye-book'); ?>
                                    </a>
                                </span>
                                | <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-patients&action=edit&id=' . $patient->id); ?>">
                                        <?php _e('Edit', 'eye-book'); ?>
                                    </a>
                                </span>
                                | <span class="appointment">
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&action=add&patient_id=' . $patient->id); ?>">
                                        <?php _e('Schedule Appointment', 'eye-book'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td class="column-contact">
                            <?php if ($patient->email): ?>
                                <div class="email">
                                    <span class="dashicons dashicons-email-alt"></span>
                                    <a href="mailto:<?php echo esc_attr($patient->email); ?>">
                                        <?php echo esc_html($patient->email); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($patient->phone): ?>
                                <div class="phone">
                                    <span class="dashicons dashicons-phone"></span>
                                    <a href="tel:<?php echo esc_attr($patient->phone); ?>">
                                        <?php echo esc_html($patient->phone); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="column-dob">
                            <?php if ($patient->date_of_birth): ?>
                                <?php 
                                $dob = date_create($patient->date_of_birth);
                                $now = date_create();
                                $age = date_diff($dob, $now)->y;
                                ?>
                                <div class="dob">
                                    <?php echo date_i18n(get_option('date_format'), strtotime($patient->date_of_birth)); ?>
                                </div>
                                <div class="age">
                                    <small>(<?php printf(__('Age: %d', 'eye-book'), $age); ?>)</small>
                                </div>
                            <?php else: ?>
                                <span class="description"><?php _e('Not provided', 'eye-book'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-last-visit">
                            <?php if ($last_appointment): ?>
                                <div class="last-visit">
                                    <?php echo date_i18n(
                                        get_option('date_format'), 
                                        strtotime($last_appointment->start_datetime)
                                    ); ?>
                                </div>
                                <div class="time-ago">
                                    <small><?php echo human_time_diff(strtotime($last_appointment->start_datetime)); ?> <?php _e('ago', 'eye-book'); ?></small>
                                </div>
                            <?php else: ?>
                                <span class="description"><?php _e('No visits', 'eye-book'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-total-visits">
                            <strong><?php echo number_format($total_visits); ?></strong>
                            <?php if ($total_visits > 0): ?>
                                <div>
                                    <small>
                                        <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&patient_filter=' . $patient->id); ?>">
                                            <?php _e('View history', 'eye-book'); ?>
                                        </a>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="column-actions">
                            <div class="button-group">
                                <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&action=add&patient_id=' . $patient->id); ?>" class="button button-small">
                                    <?php _e('Book Appointment', 'eye-book'); ?>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($patients['total_pages'] > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $current_page = max(1, intval($_GET['paged'] ?? 1));
                    $total_pages = $patients['total_pages'];
                    $base_url = admin_url('admin.php?page=eye-book-patients');
                    
                    // Add search query to pagination
                    $query_args = array();
                    if (!empty($_GET['s'])) $query_args['s'] = $_GET['s'];
                    
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
                    <span class="dashicons dashicons-admin-users"></span>
                </div>
                
                <?php if (!empty($_GET['s'])): ?>
                    <h3><?php _e('No Patients Found', 'eye-book'); ?></h3>
                    <p><?php printf(__('No patients match your search for "%s".', 'eye-book'), esc_html($_GET['s'])); ?></p>
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=eye-book-patients'); ?>" class="button">
                            <?php _e('View All Patients', 'eye-book'); ?>
                        </a>
                        <a href="<?php echo admin_url('admin.php?page=eye-book-patients&action=add'); ?>" class="button button-primary">
                            <?php _e('Add New Patient', 'eye-book'); ?>
                        </a>
                    </p>
                <?php else: ?>
                    <h3><?php _e('No Patients Yet', 'eye-book'); ?></h3>
                    <p><?php _e('You haven\'t added any patients to your system yet. Add your first patient to get started.', 'eye-book'); ?></p>
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=eye-book-patients&action=add'); ?>" class="button button-primary">
                            <?php _e('Add First Patient', 'eye-book'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Focus search input on page load if there's a search term
    <?php if (!empty($_GET['s'])): ?>
        $('#patient-search-input').focus();
    <?php endif; ?>
    
    // Live search with debouncing
    var searchTimeout;
    $('#patient-search-input').on('input', function() {
        clearTimeout(searchTimeout);
        var searchTerm = $(this).val();
        
        if (searchTerm.length >= 3) {
            searchTimeout = setTimeout(function() {
                // You can implement AJAX live search here if needed
                console.log('Search for: ' + searchTerm);
            }, 500);
        }
    });
});
</script>