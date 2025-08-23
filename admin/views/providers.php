<?php
/**
 * Providers management view
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
    <h1 class="wp-heading-inline"><?php _e('Providers', 'eye-book'); ?></h1>
    <a href="#" class="page-title-action" id="add-new-provider"><?php _e('Add New', 'eye-book'); ?></a>
    <hr class="wp-header-end">

    <div class="eye-book-admin-header">
        <div class="eye-book-filters">
            <input type="text" id="provider-search" class="regular-text" placeholder="<?php _e('Search providers...', 'eye-book'); ?>">
            
            <select id="provider-specialization-filter">
                <option value=""><?php _e('All Specializations', 'eye-book'); ?></option>
                <option value="optometrist"><?php _e('Optometrist', 'eye-book'); ?></option>
                <option value="ophthalmologist"><?php _e('Ophthalmologist', 'eye-book'); ?></option>
                <option value="optician"><?php _e('Optician', 'eye-book'); ?></option>
                <option value="corneal_specialist"><?php _e('Corneal Specialist', 'eye-book'); ?></option>
                <option value="retinal_specialist"><?php _e('Retinal Specialist', 'eye-book'); ?></option>
                <option value="glaucoma_specialist"><?php _e('Glaucoma Specialist', 'eye-book'); ?></option>
                <option value="pediatric_specialist"><?php _e('Pediatric Specialist', 'eye-book'); ?></option>
            </select>

            <select id="provider-status-filter">
                <option value=""><?php _e('All Statuses', 'eye-book'); ?></option>
                <option value="active"><?php _e('Active', 'eye-book'); ?></option>
                <option value="inactive"><?php _e('Inactive', 'eye-book'); ?></option>
                <option value="on_leave"><?php _e('On Leave', 'eye-book'); ?></option>
            </select>

            <button type="button" class="button" id="provider-filter-apply"><?php _e('Filter', 'eye-book'); ?></button>
            <button type="button" class="button" id="provider-filter-clear"><?php _e('Clear', 'eye-book'); ?></button>
        </div>

        <div class="eye-book-actions">
            <button type="button" class="button button-secondary" id="manage-schedules"><?php _e('Manage Schedules', 'eye-book'); ?></button>
            <button type="button" class="button button-secondary" id="export-providers"><?php _e('Export', 'eye-book'); ?></button>
        </div>
    </div>

    <div class="eye-book-content">
        <table class="wp-list-table widefat fixed striped providers">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all"><?php _e('Select All', 'eye-book'); ?></label>
                        <input id="cb-select-all" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-provider-id sortable">
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
                    <th scope="col" class="manage-column column-specialization">
                        <?php _e('Specialization', 'eye-book'); ?>
                    </th>
                    <th scope="col" class="manage-column column-credentials">
                        <?php _e('Credentials', 'eye-book'); ?>
                    </th>
                    <th scope="col" class="manage-column column-locations">
                        <?php _e('Locations', 'eye-book'); ?>
                    </th>
                    <th scope="col" class="manage-column column-contact">
                        <?php _e('Contact', 'eye-book'); ?>
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
            <tbody id="providers-list">
                <!-- Providers will be loaded here via AJAX -->
            </tbody>
        </table>

        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="bulk-action" id="bulk-action-selector-bottom">
                    <option value="-1"><?php _e('Bulk Actions', 'eye-book'); ?></option>
                    <option value="activate"><?php _e('Activate', 'eye-book'); ?></option>
                    <option value="deactivate"><?php _e('Deactivate', 'eye-book'); ?></option>
                    <option value="set-on-leave"><?php _e('Set On Leave', 'eye-book'); ?></option>
                    <option value="export"><?php _e('Export Selected', 'eye-book'); ?></option>
                    <option value="delete"><?php _e('Delete', 'eye-book'); ?></option>
                </select>
                <input type="submit" id="bulk-action-apply" class="button action" value="<?php _e('Apply', 'eye-book'); ?>">
            </div>

            <div class="tablenav-pages">
                <span class="displaying-num"><span id="total-providers">0</span> <?php _e('items', 'eye-book'); ?></span>
                <span class="pagination-links" id="providers-pagination">
                    <!-- Pagination will be loaded here -->
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Provider Modal -->
<div id="provider-modal" class="eye-book-modal" style="display: none;">
    <div class="eye-book-modal-content large">
        <div class="eye-book-modal-header">
            <h2 id="provider-modal-title"><?php _e('Add New Provider', 'eye-book'); ?></h2>
            <button type="button" class="eye-book-modal-close">&times;</button>
        </div>
        <div class="eye-book-modal-body">
            <div class="eye-book-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#provider-basic-info" class="nav-tab nav-tab-active"><?php _e('Basic Information', 'eye-book'); ?></a>
                    <a href="#provider-professional-info" class="nav-tab"><?php _e('Professional Info', 'eye-book'); ?></a>
                    <a href="#provider-contact-info" class="nav-tab"><?php _e('Contact Information', 'eye-book'); ?></a>
                    <a href="#provider-schedule-info" class="nav-tab"><?php _e('Schedule & Availability', 'eye-book'); ?></a>
                    <a href="#provider-locations" class="nav-tab"><?php _e('Locations', 'eye-book'); ?></a>
                </nav>

                <form id="provider-form">
                    <input type="hidden" id="provider-id" name="provider_id" value="">
                    
                    <!-- Basic Information Tab -->
                    <div id="provider-basic-info" class="tab-content active">
                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="first-name"><?php _e('First Name', 'eye-book'); ?> <span class="required">*</span></label>
                                <input type="text" id="first-name" name="first_name" class="regular-text" required>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="last-name"><?php _e('Last Name', 'eye-book'); ?> <span class="required">*</span></label>
                                <input type="text" id="last-name" name="last_name" class="regular-text" required>
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="title"><?php _e('Title', 'eye-book'); ?></label>
                                <input type="text" id="title" name="title" class="regular-text" placeholder="Dr., OD, MD, etc.">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="suffix"><?php _e('Suffix', 'eye-book'); ?></label>
                                <input type="text" id="suffix" name="suffix" class="regular-text" placeholder="Jr., Sr., III, etc.">
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="display-name"><?php _e('Display Name', 'eye-book'); ?></label>
                                <input type="text" id="display-name" name="display_name" class="regular-text" placeholder="How the provider's name should appear">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="provider-status"><?php _e('Status', 'eye-book'); ?></label>
                                <select id="provider-status" name="status" class="regular-text">
                                    <option value="active"><?php _e('Active', 'eye-book'); ?></option>
                                    <option value="inactive"><?php _e('Inactive', 'eye-book'); ?></option>
                                    <option value="on_leave"><?php _e('On Leave', 'eye-book'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Information Tab -->
                    <div id="provider-professional-info" class="tab-content">
                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="specialization"><?php _e('Primary Specialization', 'eye-book'); ?> <span class="required">*</span></label>
                                <select id="specialization" name="specialization" class="regular-text" required>
                                    <option value=""><?php _e('Select Specialization', 'eye-book'); ?></option>
                                    <option value="optometrist"><?php _e('Optometrist', 'eye-book'); ?></option>
                                    <option value="ophthalmologist"><?php _e('Ophthalmologist', 'eye-book'); ?></option>
                                    <option value="optician"><?php _e('Optician', 'eye-book'); ?></option>
                                    <option value="corneal_specialist"><?php _e('Corneal Specialist', 'eye-book'); ?></option>
                                    <option value="retinal_specialist"><?php _e('Retinal Specialist', 'eye-book'); ?></option>
                                    <option value="glaucoma_specialist"><?php _e('Glaucoma Specialist', 'eye-book'); ?></option>
                                    <option value="pediatric_specialist"><?php _e('Pediatric Specialist', 'eye-book'); ?></option>
                                </select>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="license-number"><?php _e('License Number', 'eye-book'); ?></label>
                                <input type="text" id="license-number" name="license_number" class="regular-text">
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="npi-number"><?php _e('NPI Number', 'eye-book'); ?></label>
                                <input type="text" id="npi-number" name="npi_number" class="regular-text">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="dea-number"><?php _e('DEA Number', 'eye-book'); ?></label>
                                <input type="text" id="dea-number" name="dea_number" class="regular-text">
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group full-width">
                                <label for="credentials"><?php _e('Credentials', 'eye-book'); ?></label>
                                <input type="text" id="credentials" name="credentials" class="regular-text" placeholder="OD, MD, PhD, etc.">
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group full-width">
                                <label for="education"><?php _e('Education & Training', 'eye-book'); ?></label>
                                <textarea id="education" name="education" rows="4" class="large-text"></textarea>
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group full-width">
                                <label for="services"><?php _e('Services Provided', 'eye-book'); ?></label>
                                <textarea id="services" name="services_provided" rows="4" class="large-text" placeholder="List of services this provider offers"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Tab -->
                    <div id="provider-contact-info" class="tab-content">
                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="email"><?php _e('Email Address', 'eye-book'); ?> <span class="required">*</span></label>
                                <input type="email" id="email" name="email" class="regular-text" required>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="phone"><?php _e('Phone Number', 'eye-book'); ?></label>
                                <input type="tel" id="phone" name="phone" class="regular-text">
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="mobile-phone"><?php _e('Mobile Phone', 'eye-book'); ?></label>
                                <input type="tel" id="mobile-phone" name="mobile_phone" class="regular-text">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="emergency-phone"><?php _e('Emergency Phone', 'eye-book'); ?></label>
                                <input type="tel" id="emergency-phone" name="emergency_phone" class="regular-text">
                            </div>
                        </div>

                        <h4><?php _e('Mailing Address', 'eye-book'); ?></h4>
                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group full-width">
                                <label for="address"><?php _e('Street Address', 'eye-book'); ?></label>
                                <input type="text" id="address" name="address" class="regular-text">
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="city"><?php _e('City', 'eye-book'); ?></label>
                                <input type="text" id="city" name="city" class="regular-text">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="state"><?php _e('State', 'eye-book'); ?></label>
                                <input type="text" id="state" name="state" class="regular-text">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="zip-code"><?php _e('ZIP Code', 'eye-book'); ?></label>
                                <input type="text" id="zip-code" name="zip_code" class="regular-text">
                            </div>
                        </div>
                    </div>

                    <!-- Schedule & Availability Tab -->
                    <div id="provider-schedule-info" class="tab-content">
                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="default-appointment-duration"><?php _e('Default Appointment Duration (minutes)', 'eye-book'); ?></label>
                                <input type="number" id="default-appointment-duration" name="default_appointment_duration" class="regular-text" min="5" max="240" step="5" value="30">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="buffer-time"><?php _e('Buffer Time Between Appointments (minutes)', 'eye-book'); ?></label>
                                <input type="number" id="buffer-time" name="buffer_time" class="regular-text" min="0" max="60" step="5" value="0">
                            </div>
                        </div>

                        <h4><?php _e('Weekly Schedule Template', 'eye-book'); ?></h4>
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
                                        <input type="checkbox" name="schedule[<?php echo $day; ?>][enabled]" value="1" class="day-enabled">
                                        <?php echo $label; ?>
                                    </label>
                                </div>
                                <div class="day-times">
                                    <input type="time" name="schedule[<?php echo $day; ?>][start_time]" class="start-time" value="08:00">
                                    <span><?php _e('to', 'eye-book'); ?></span>
                                    <input type="time" name="schedule[<?php echo $day; ?>][end_time]" class="end-time" value="17:00">
                                </div>
                                <div class="break-times">
                                    <label>
                                        <input type="checkbox" name="schedule[<?php echo $day; ?>][has_break]" value="1" class="has-break">
                                        <?php _e('Lunch Break', 'eye-book'); ?>
                                    </label>
                                    <input type="time" name="schedule[<?php echo $day; ?>][break_start]" class="break-start" value="12:00" disabled>
                                    <span><?php _e('to', 'eye-book'); ?></span>
                                    <input type="time" name="schedule[<?php echo $day; ?>][break_end]" class="break-end" value="13:00" disabled>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Locations Tab -->
                    <div id="provider-locations" class="tab-content">
                        <h4><?php _e('Assigned Locations', 'eye-book'); ?></h4>
                        <div class="locations-list">
                            <?php
                            global $wpdb;
                            $locations = $wpdb->get_results("SELECT id, name FROM " . EYE_BOOK_TABLE_LOCATIONS . " WHERE status = 'active'");
                            foreach ($locations as $location) :
                            ?>
                            <label class="location-checkbox">
                                <input type="checkbox" name="locations[]" value="<?php echo esc_attr($location->id); ?>">
                                <?php echo esc_html($location->name); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group full-width">
                                <label for="provider-notes"><?php _e('Notes', 'eye-book'); ?></label>
                                <textarea id="provider-notes" name="notes" rows="4" class="large-text"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="eye-book-modal-footer">
            <button type="button" class="button button-secondary" id="provider-modal-cancel"><?php _e('Cancel', 'eye-book'); ?></button>
            <button type="button" class="button button-primary" id="provider-modal-save"><?php _e('Save Provider', 'eye-book'); ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize providers management
    EyeBook.Providers.init();
});
</script>