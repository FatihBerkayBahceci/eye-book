<?php
/**
 * Admin locations list view
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

<div class="wrap eye-book-locations">
    <h1 class="wp-heading-inline">
        <?php _e('Locations', 'eye-book'); ?>
    </h1>
    
    <a href="<?php echo admin_url('admin.php?page=eye-book-locations&action=add'); ?>" class="page-title-action">
        <?php _e('Add New Location', 'eye-book'); ?>
    </a>
    
    <hr class="wp-header-end">

    <div class="eye-book-locations-intro">
        <p><?php _e('Manage your practice locations where appointments can be scheduled.', 'eye-book'); ?></p>
    </div>

    <!-- Locations Table -->
    <div class="eye-book-table-container">
        <?php if (!empty($locations)): ?>
            <table class="wp-list-table widefat fixed striped eye-book-locations-table">
                <thead>
                    <tr>
                        <th scope="col" class="column-location"><?php _e('Location', 'eye-book'); ?></th>
                        <th scope="col" class="column-address"><?php _e('Address', 'eye-book'); ?></th>
                        <th scope="col" class="column-contact"><?php _e('Contact', 'eye-book'); ?></th>
                        <th scope="col" class="column-providers"><?php _e('Providers', 'eye-book'); ?></th>
                        <th scope="col" class="column-appointments"><?php _e('This Week', 'eye-book'); ?></th>
                        <th scope="col" class="column-status"><?php _e('Status', 'eye-book'); ?></th>
                        <th scope="col" class="column-actions"><?php _e('Actions', 'eye-book'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locations as $location): 
                        // Get providers count for this location
                        global $wpdb;
                        $providers_count = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(DISTINCT ps.provider_id) 
                             FROM " . EYE_BOOK_TABLE_PREFIX . "provider_schedules ps
                             INNER JOIN " . EYE_BOOK_TABLE_PROVIDERS . " p ON ps.provider_id = p.id
                             WHERE ps.location_id = %d AND p.status = 'active'",
                            $location->id
                        ));
                        
                        // Get appointments count for this week
                        $week_start = date('Y-m-d', strtotime('monday this week'));
                        $week_end = date('Y-m-d', strtotime('sunday this week'));
                        
                        $week_appointments = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM " . EYE_BOOK_TABLE_APPOINTMENTS . " 
                             WHERE location_id = %d 
                             AND DATE(start_datetime) BETWEEN %s AND %s
                             AND status NOT IN ('cancelled', 'no_show')",
                            $location->id, $week_start, $week_end
                        ));
                    ?>
                    <tr>
                        <td class="column-location">
                            <div class="location-info">
                                <strong>
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-locations&action=edit&id=' . $location->id); ?>">
                                        <?php echo esc_html($location->name); ?>
                                    </a>
                                </strong>
                                <?php if ($location->description): ?>
                                    <div class="location-description">
                                        <small><?php echo esc_html($location->description); ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="column-address">
                            <?php if ($location->address): ?>
                                <div class="address-info">
                                    <?php echo nl2br(esc_html($location->address)); ?>
                                    
                                    <?php if ($location->city || $location->state || $location->zip_code): ?>
                                        <br>
                                        <?php 
                                        $address_parts = array_filter([$location->city, $location->state, $location->zip_code]);
                                        echo esc_html(implode(', ', $address_parts));
                                        ?>
                                    <?php endif; ?>
                                    
                                    <div class="address-actions">
                                        <a href="https://maps.google.com/?q=<?php echo urlencode($location->address . ' ' . $location->city . ' ' . $location->state . ' ' . $location->zip_code); ?>" target="_blank" class="button button-small">
                                            <span class="dashicons dashicons-location-alt"></span>
                                            <?php _e('View on Map', 'eye-book'); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="description"><?php _e('Address not provided', 'eye-book'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="column-contact">
                            <div class="contact-info">
                                <?php if ($location->phone): ?>
                                    <div class="phone">
                                        <span class="dashicons dashicons-phone"></span>
                                        <a href="tel:<?php echo esc_attr($location->phone); ?>">
                                            <?php echo esc_html($location->phone); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($location->email): ?>
                                    <div class="email">
                                        <span class="dashicons dashicons-email-alt"></span>
                                        <a href="mailto:<?php echo esc_attr($location->email); ?>">
                                            <?php echo esc_html($location->email); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!$location->phone && !$location->email): ?>
                                    <span class="description"><?php _e('Contact info not provided', 'eye-book'); ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="column-providers">
                            <div class="providers-count">
                                <strong><?php echo number_format($providers_count); ?></strong>
                                <small><?php echo _n('provider', 'providers', $providers_count, 'eye-book'); ?></small>
                            </div>
                            
                            <?php if ($providers_count > 0): ?>
                                <div class="providers-actions">
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-providers&location_filter=' . $location->id); ?>" class="button button-small">
                                        <?php _e('View Providers', 'eye-book'); ?>
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="no-providers">
                                    <small class="description"><?php _e('No providers assigned', 'eye-book'); ?></small>
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-provider-schedules&action=add&location_id=' . $location->id); ?>" class="button button-small">
                                        <?php _e('Assign Provider', 'eye-book'); ?>
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
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&location_filter=' . $location->id); ?>" class="button button-small">
                                        <?php _e('View All', 'eye-book'); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="column-status">
                            <span class="location-status location-status-<?php echo esc_attr($location->status); ?>">
                                <?php echo esc_html(ucfirst($location->status)); ?>
                            </span>
                        </td>
                        <td class="column-actions">
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-locations&action=edit&id=' . $location->id); ?>">
                                        <?php _e('Edit', 'eye-book'); ?>
                                    </a>
                                </span>
                                | <span class="schedule">
                                    <a href="<?php echo admin_url('admin.php?page=eye-book-appointments&action=add&location_id=' . $location->id); ?>">
                                        <?php _e('Book Appointment', 'eye-book'); ?>
                                    </a>
                                </span>
                                <?php if ($location->status === 'active'): ?>
                                | <span class="deactivate">
                                    <a href="#" onclick="updateLocationStatus(<?php echo $location->id; ?>, 'inactive')" class="delete">
                                        <?php _e('Deactivate', 'eye-book'); ?>
                                    </a>
                                </span>
                                <?php else: ?>
                                | <span class="activate">
                                    <a href="#" onclick="updateLocationStatus(<?php echo $location->id; ?>, 'active')">
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
                    <span class="dashicons dashicons-location-alt"></span>
                </div>
                <h3><?php _e('No Locations Yet', 'eye-book'); ?></h3>
                <p><?php _e('You need to add at least one practice location where appointments can be scheduled.', 'eye-book'); ?></p>
                
                <div class="empty-state-actions">
                    <a href="<?php echo admin_url('admin.php?page=eye-book-locations&action=add'); ?>" class="button button-primary button-large">
                        <?php _e('Add Your First Location', 'eye-book'); ?>
                    </a>
                </div>
                
                <div class="empty-state-help">
                    <h4><?php _e('Why do I need Locations?', 'eye-book'); ?></h4>
                    <p><?php _e('Locations represent your physical practice locations where patients visit for appointments. Even if you have just one clinic, you need to set it up as a location. This helps with scheduling, reporting, and patient communications.', 'eye-book'); ?></p>
                    
                    <h4><?php _e('Multi-Location Practices', 'eye-book'); ?></h4>
                    <p><?php _e('If you have multiple clinic locations, you can set up each one separately. Providers can be assigned to work at different locations on different days, and patients can book appointments at their preferred location.', 'eye-book'); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Location status update function
    window.updateLocationStatus = function(locationId, newStatus) {
        var message = newStatus === 'active' ? 
            '<?php _e("Are you sure you want to activate this location?", "eye-book"); ?>' :
            '<?php _e("Are you sure you want to deactivate this location? This will affect appointment scheduling.", "eye-book"); ?>';
            
        if (!confirm(message)) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'eye_book_update_location_status',
            location_id: locationId,
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