<?php
/**
 * Admin providers list view
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

<div class="wrap eye-book-providers">
    <h1 class="wp-heading-inline">
        <?php _e('Providers', 'eye-book'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=eye-book-providers&action=add'); ?>" class="page-title-action">
        <?php _e('Add New Provider', 'eye-book'); ?>
    </a>
    
    <hr class="wp-header-end">

    <div class="eye-book-providers-intro">
        <p><?php _e('Manage healthcare providers who can see patients and manage appointments.', 'eye-book'); ?></p>
    </div>

    <!-- Providers Table -->
    <div class="eye-book-table-container">
        <?php if (!empty($providers)): ?>
            <table class="wp-list-table widefat fixed striped eye-book-providers-table">
                <thead>
                    <tr>
                        <th scope="col" class="column-provider"><?php _e('Provider', 'eye-book'); ?></th>
                        <th scope="col" class="column-specialization"><?php _e('Specialization', 'eye-book'); ?></th>
                        <th scope="col" class="column-contact"><?php _e('Contact', 'eye-book'); ?></th>
                        <th scope="col" class="column-schedule"><?php _e('Schedule Status', 'eye-book'); ?></th>
                        <th scope="col" class="column-appointments"><?php _e('This Week', 'eye-book'); ?></th>
                        <th scope="col" class="column-actions"><?php _e('Actions', 'eye-book'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($providers as $provider): 
                        // Get WordPress user info
                        $user = get_user_by('ID', $provider->wp_user_id);
                        
                        // Get appointments count for this week
                        global $wpdb;
                        $week_start = date('Y-m-d', strtotime('monday this week'));
                        $week_end = date('Y-m-d', strtotime('sunday this week'));
                        
                        $week_appointments = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " 
                             WHERE provider_id = %d 
                             AND DATE(start_datetime) BETWEEN %s AND %s
                             AND status NOT IN ('cancelled', 'no_show')",
                            $provider->id, $week_start, $week_end
                        ));
                        
                        // Check if provider has schedule set up
                        $has_schedule = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_PREFIX . "provider_schedules 
                             WHERE provider_id = %d AND is_active = 1",
                            $provider->id
                        ));
                    ?>
                    <tr>
                        <td class="column-provider">
                            <?php if ($user): ?>
                                <div class="provider-info">
                                    <?php echo get_avatar($user->ID, 40); ?>
                                    <div class="provider-details">
                                        <strong>
                                            <a href="<?php echo admin_url('admin.php?page=eye-book-providers&action=edit&id=' . $provider->id); ?>">
                                                <?php echo esc_html($user->display_name); ?>
                                            </a>
                                        </strong>
                                        <div class="provider-meta">
                                            <span class="license"><?php echo esc_html($provider->license_number ?: __('License: Not provided', 'eye-book')); ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="provider-error">
                                    <strong><?php _e('User Not Found', 'eye-book'); ?></strong>
                                    <div class="error-details">
                                        <?php printf(__('WordPress user ID %d does not exist', 'eye-book'), $provider->wp_user_id); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="column-specialization">
                            <?php if ($provider->specialization): ?>
                                <span class="specialization-badge">
                                    <?php echo esc_html($provider->specialization); ?>
                                </span>
                            <?php else: ?>
                                <span class="description"><?php _e('Not specified', 'eye-book'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-contact">
                            <?php if ($user): ?>
                                <div class="contact-info">
                                    <div class="email">
                                        <span class="dashicons dashicons-email-alt"></span>
                                        <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                                            <?php echo esc_html($user->user_email); ?>
                                        </a>
                                    </div>
                                    
                                    <?php if ($provider->phone): ?>
                                        <div class="phone">
                                            <span class="dashicons dashicons-phone"></span>
                                            <a href="tel:<?php echo esc_attr($provider->phone); ?>">
                                                <?php echo esc_html($provider->phone); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="column-schedule">
                            <?php if ($has_schedule > 0): ?>
                                <span class="schedule-status active">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php _e('Active Schedule', 'eye-book'); ?>
                                </span>
                                <div class="schedule-actions">
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-provider-schedules&provider_id=' . $provider->id); ?>" class="button button-small">
                                        <?php _e('Manage Schedule', 'eye-book'); ?>
                                    </a>
                                </div>
                            <?php else: ?>
                                <span class="schedule-status inactive">
                                    <span class="dashicons dashicons-warning"></span>
                                    <?php _e('No Schedule', 'eye-book'); ?>
                                </span>
                                <div class="schedule-actions">
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-provider-schedules&action=add&provider_id=' . $provider->id); ?>" class="button button-small">
                                        <?php _e('Set Up Schedule', 'eye-book'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="column-appointments">
                            <div class="appointment-stats">
                                <strong><?php echo number_format($week_appointments); ?></strong>
                                <small><?php _e('appointments', 'eye-book'); ?></small>
                            </div>
                            
                            <?php if ($week_appointments > 0): ?>
                                <div class="view-appointments">
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&provider_filter=' . $provider->id); ?>" class="button button-small">
                                        <?php _e('View All', 'eye-book'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="column-actions">
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-providers&action=edit&id=' . $provider->id); ?>">
                                        <?php _e('Edit', 'eye-book'); ?>
                                    </a>
                                </span>
                                | <span class="schedule">
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-provider-schedules&provider_id=' . $provider->id); ?>">
                                        <?php _e('Schedule', 'eye-book'); ?>
                                    </a>
                                </span>
                                <?php if ($provider->status === 'active'): ?>
                                | <span class="deactivate">
                                    <a href="#" onclick="updateProviderStatus(<?php echo $provider->id; ?>, 'inactive')" class="delete">
                                        <?php _e('Deactivate', 'eye-book'); ?>
                                    </a>
                                </span>
                                <?php else: ?>
                                | <span class="activate">
                                    <a href="#" onclick="updateProviderStatus(<?php echo $provider->id; ?>, 'active')">
                                        <?php _e('Activate', 'eye-book'); ?>
                                    </a>
                                </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
        <?php else: ?>
            <div class="eye-book-empty-state">
                <div class="empty-state-icon">
                    <span class="dashicons dashicons-businessperson"></span>
                </div>
                <h3><?php _e('No Providers Yet', 'eye-book'); ?></h3>
                <p><?php _e('You need to add healthcare providers to your system before you can schedule appointments.', 'eye-book'); ?></p>
                
                <div class="empty-state-actions">
                    <a href="<?php echo admin_url('admin.php?page=eye-book-providers&action=add'); ?>" class="button button-primary button-large">
                        <?php _e('Add Your First Provider', 'eye-book'); ?>
                    </a>
                </div>
                
                <div class="empty-state-help">
                    <h4><?php _e('What are Providers?', 'eye-book'); ?></h4>
                    <p><?php _e('Providers are the healthcare professionals in your practice (doctors, nurses, specialists) who can see patients and manage appointments. Each provider needs a WordPress user account and can have their own schedule and availability settings.', 'eye-book'); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Provider status update function
    window.updateProviderStatus = function(providerId, newStatus) {
        var message = newStatus === 'active' ? 
            '<?php _e("Are you sure you want to activate this provider?", "eye-book"); ?>' :
            '<?php _e("Are you sure you want to deactivate this provider? This will affect their ability to see patients.", "eye-book"); ?>';
            
        if (!confirm(message)) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'eye_book_update_provider_status',
            provider_id: providerId,
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
});
</script>