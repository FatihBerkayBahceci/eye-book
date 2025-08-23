<?php
/**
 * Locations management view
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
    <h1 class="wp-heading-inline"><?php _e('Locations', 'eye-book'); ?></h1>
    <a href="#" class="page-title-action" id="add-new-location"><?php _e('Add New', 'eye-book'); ?></a>
    <hr class="wp-header-end">

    <div class="eye-book-admin-header">
        <div class="eye-book-filters">
            <input type="text" id="location-search" class="regular-text" placeholder="<?php _e('Search locations...', 'eye-book'); ?>">
            
            <select id="location-status-filter">
                <option value=""><?php _e('All Statuses', 'eye-book'); ?></option>
                <option value="active"><?php _e('Active', 'eye-book'); ?></option>
                <option value="inactive"><?php _e('Inactive', 'eye-book'); ?></option>
                <option value="closed"><?php _e('Closed', 'eye-book'); ?></option>
            </select>

            <button type="button" class="button" id="location-filter-apply"><?php _e('Filter', 'eye-book'); ?></button>
            <button type="button" class="button" id="location-filter-clear"><?php _e('Clear', 'eye-book'); ?></button>
        </div>

        <div class="eye-book-actions">
            <button type="button" class="button button-secondary" id="export-locations"><?php _e('Export', 'eye-book'); ?></button>
        </div>
    </div>

    <div class="eye-book-content">
        <table class="wp-list-table widefat fixed striped locations">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all"><?php _e('Select All', 'eye-book'); ?></label>
                        <input id="cb-select-all" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-location-id sortable">
                        <a href="#">
                            <span><?php _e('ID', 'eye-book'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column column-name sortable">
                        <a href="#">
                            <span><?php _e('Name', 'eye-book'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column column-address">
                        <?php _e('Address', 'eye-book'); ?>
                    </th>
                    <th scope="col" class="manage-column column-contact">
                        <?php _e('Contact', 'eye-book'); ?>
                    </th>
                    <th scope="col" class="manage-column column-hours">
                        <?php _e('Operating Hours', 'eye-book'); ?>
                    </th>
                    <th scope="col" class="manage-column column-providers">
                        <?php _e('Providers', 'eye-book'); ?>
                    </th>
                    <th scope="col" class="manage-column column-status sortable">
                        <a href="#">
                            <span><?php _e('Status', 'eye-book'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column column-actions">
                        <?php _e('Actions', 'eye-book'); ?>
                    </th>
                </tr>
            </thead>
            <tbody id="locations-list">
                <!-- Locations will be loaded here via AJAX -->
            </tbody>
        </table>

        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="bulk-action" id="bulk-action-selector-bottom">
                    <option value="-1"><?php _e('Bulk Actions', 'eye-book'); ?></option>
                    <option value="activate"><?php _e('Activate', 'eye-book'); ?></option>
                    <option value="deactivate"><?php _e('Deactivate', 'eye-book'); ?></option>
                    <option value="export"><?php _e('Export Selected', 'eye-book'); ?></option>
                    <option value="delete"><?php _e('Delete', 'eye-book'); ?></option>
                </select>
                <input type="submit" id="bulk-action-apply" class="button action" value="<?php _e('Apply', 'eye-book'); ?>">
            </div>

            <div class="tablenav-pages">
                <span class="displaying-num"><span id="total-locations">0</span> <?php _e('items', 'eye-book'); ?></span>
                <span class="pagination-links" id="locations-pagination">
                    <!-- Pagination will be loaded here -->
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Location Modal -->
<div id="location-modal" class="eye-book-modal" style="display: none;">
    <div class="eye-book-modal-content large">
        <div class="eye-book-modal-header">
            <h2 id="location-modal-title"><?php _e('Add New Location', 'eye-book'); ?></h2>
            <button type="button" class="eye-book-modal-close">&times;</button>
        </div>
        <div class="eye-book-modal-body">
            <div class="eye-book-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#location-basic-info" class="nav-tab nav-tab-active"><?php _e('Basic Information', 'eye-book'); ?></a>
                    <a href="#location-contact-info" class="nav-tab"><?php _e('Contact Information', 'eye-book'); ?></a>
                    <a href="#location-hours" class="nav-tab"><?php _e('Operating Hours', 'eye-book'); ?></a>
                    <a href="#location-settings" class="nav-tab"><?php _e('Settings', 'eye-book'); ?></a>
                </nav>

                <form id="location-form">
                    <input type="hidden" id="location-id" name="location_id" value="">
                    
                    <!-- Basic Information Tab -->
                    <div id="location-basic-info" class="tab-content active">
                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="location-name"><?php _e('Location Name', 'eye-book'); ?> <span class="required">*</span></label>
                                <input type="text" id="location-name" name="name" class="regular-text" required>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="location-code"><?php _e('Location Code', 'eye-book'); ?></label>
                                <input type="text" id="location-code" name="location_code" class="regular-text" placeholder="Unique identifier">
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="location-type"><?php _e('Location Type', 'eye-book'); ?></label>
                                <select id="location-type" name="location_type" class="regular-text">
                                    <option value="clinic"><?php _e('Clinic', 'eye-book'); ?></option>
                                    <option value="hospital"><?php _e('Hospital', 'eye-book'); ?></option>
                                    <option value="surgical_center"><?php _e('Surgical Center', 'eye-book'); ?></option>
                                    <option value="satellite_office"><?php _e('Satellite Office', 'eye-book'); ?></option>
                                </select>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="location-status"><?php _e('Status', 'eye-book'); ?></label>
                                <select id="location-status" name="status" class="regular-text">
                                    <option value="active"><?php _e('Active', 'eye-book'); ?></option>
                                    <option value="inactive"><?php _e('Inactive', 'eye-book'); ?></option>
                                    <option value="closed"><?php _e('Closed', 'eye-book'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group full-width">
                                <label for="location-description"><?php _e('Description', 'eye-book'); ?></label>
                                <textarea id="location-description" name="description" rows="3" class="large-text"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Tab -->
                    <div id="location-contact-info" class="tab-content">
                        <h4><?php _e('Address', 'eye-book'); ?></h4>
                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group full-width">
                                <label for="address"><?php _e('Street Address', 'eye-book'); ?> <span class="required">*</span></label>
                                <input type="text" id="address" name="address" class="regular-text" required>
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="city"><?php _e('City', 'eye-book'); ?> <span class="required">*</span></label>
                                <input type="text" id="city" name="city" class="regular-text" required>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="state"><?php _e('State', 'eye-book'); ?> <span class="required">*</span></label>
                                <input type="text" id="state" name="state" class="regular-text" required>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="zip-code"><?php _e('ZIP Code', 'eye-book'); ?> <span class="required">*</span></label>
                                <input type="text" id="zip-code" name="zip_code" class="regular-text" required>
                            </div>
                        </div>

                        <h4><?php _e('Contact Information', 'eye-book'); ?></h4>
                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="phone"><?php _e('Phone Number', 'eye-book'); ?> <span class="required">*</span></label>
                                <input type="tel" id="phone" name="phone" class="regular-text" required>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="fax"><?php _e('Fax Number', 'eye-book'); ?></label>
                                <input type="tel" id="fax" name="fax" class="regular-text">
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="email"><?php _e('Email Address', 'eye-book'); ?></label>
                                <input type="email" id="email" name="email" class="regular-text">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="website"><?php _e('Website', 'eye-book'); ?></label>
                                <input type="url" id="website" name="website" class="regular-text">
                            </div>
                        </div>
                    </div>

                    <!-- Operating Hours Tab -->
                    <div id="location-hours" class="tab-content">
                        <h4><?php _e('Operating Hours', 'eye-book'); ?></h4>
                        <div class="schedule-grid">
                            <?php
                            $days = array(
                                'monday' => __('Monday', 'eye-book'),
                                'tuesday' => __('Tuesday', 'eye-book'),
                                'wednesday' => __('Wednesday', 'eye-book'),
                                'thursday' => __('Thursday', 'eye-book'),
                                'friday' => __('Friday', 'eye-book'),
                                'saturday' => __('Saturday', 'eye-book'),
                                'sunday' => __('Sunday', 'eye-book')
                            );
                            foreach ($days as $day => $label) :
                            ?>
                            <div class="schedule-day">
                                <div class="day-header">
                                    <label>
                                        <input type="checkbox" name="hours[<?php echo $day; ?>][enabled]" value="1" class="day-enabled">
                                        <?php echo $label; ?>
                                    </label>
                                </div>
                                <div class="day-times">
                                    <input type="time" name="hours[<?php echo $day; ?>][open_time]" class="open-time" value="08:00">
                                    <span><?php _e('to', 'eye-book'); ?></span>
                                    <input type="time" name="hours[<?php echo $day; ?>][close_time]" class="close-time" value="17:00">
                                </div>
                                <div class="break-times">
                                    <label>
                                        <input type="checkbox" name="hours[<?php echo $day; ?>][has_break]" value="1" class="has-break">
                                        <?php _e('Lunch Break', 'eye-book'); ?>
                                    </label>
                                    <input type="time" name="hours[<?php echo $day; ?>][break_start]" class="break-start" value="12:00" disabled>
                                    <span><?php _e('to', 'eye-book'); ?></span>
                                    <input type="time" name="hours[<?php echo $day; ?>][break_end]" class="break-end" value="13:00" disabled>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group full-width">
                                <label for="holiday-hours"><?php _e('Holiday Hours / Special Notes', 'eye-book'); ?></label>
                                <textarea id="holiday-hours" name="holiday_hours" rows="3" class="large-text" placeholder="Special hours for holidays, etc."></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Settings Tab -->
                    <div id="location-settings" class="tab-content">
                        <h4><?php _e('Booking Settings', 'eye-book'); ?></h4>
                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="booking-enabled">
                                    <input type="checkbox" id="booking-enabled" name="booking_enabled" value="1" checked>
                                    <?php _e('Enable Online Booking', 'eye-book'); ?>
                                </label>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="advance-booking-days"><?php _e('Advance Booking (days)', 'eye-book'); ?></label>
                                <input type="number" id="advance-booking-days" name="advance_booking_days" class="regular-text" min="1" max="365" value="30">
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="minimum-notice-hours"><?php _e('Minimum Notice (hours)', 'eye-book'); ?></label>
                                <input type="number" id="minimum-notice-hours" name="minimum_notice_hours" class="regular-text" min="1" max="72" value="2">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="timezone"><?php _e('Timezone', 'eye-book'); ?></label>
                                <select id="timezone" name="timezone" class="regular-text">
                                    <option value="America/New_York">Eastern Time</option>
                                    <option value="America/Chicago">Central Time</option>
                                    <option value="America/Denver">Mountain Time</option>
                                    <option value="America/Los_Angeles">Pacific Time</option>
                                    <option value="America/Anchorage">Alaska Time</option>
                                    <option value="Pacific/Honolulu">Hawaii Time</option>
                                </select>
                            </div>
                        </div>

                        <h4><?php _e('Facility Information', 'eye-book'); ?></h4>
                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="examination-rooms"><?php _e('Number of Examination Rooms', 'eye-book'); ?></label>
                                <input type="number" id="examination-rooms" name="examination_rooms" class="regular-text" min="1" max="50" value="1">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="parking-available">
                                    <input type="checkbox" id="parking-available" name="parking_available" value="1">
                                    <?php _e('Parking Available', 'eye-book'); ?>
                                </label>
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="wheelchair-accessible">
                                    <input type="checkbox" id="wheelchair-accessible" name="wheelchair_accessible" value="1">
                                    <?php _e('Wheelchair Accessible', 'eye-book'); ?>
                                </label>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="insurance-accepted"><?php _e('Insurance Plans Accepted', 'eye-book'); ?></label>
                                <textarea id="insurance-accepted" name="insurance_accepted" rows="3" class="large-text" placeholder="List accepted insurance plans"></textarea>
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group full-width">
                                <label for="location-notes"><?php _e('Notes', 'eye-book'); ?></label>
                                <textarea id="location-notes" name="notes" rows="4" class="large-text"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="eye-book-modal-footer">
            <button type="button" class="button button-secondary" id="location-modal-cancel"><?php _e('Cancel', 'eye-book'); ?></button>
            <button type="button" class="button button-primary" id="location-modal-save"><?php _e('Save Location', 'eye-book'); ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize locations management
    EyeBook.Locations.init();
});
</script>