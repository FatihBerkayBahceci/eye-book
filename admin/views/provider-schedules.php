<?php
/**
 * Provider Schedules Management View
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
    <h1 class="wp-heading-inline"><?php _e('Provider Schedules', 'eye-book'); ?></h1>
    <a href="#" class="page-title-action" id="add-time-off"><?php _e('Add Time Off', 'eye-book'); ?></a>
    <hr class="wp-header-end">

    <div class="eye-book-admin-header">
        <div class="eye-book-filters">
            <select id="schedule-provider-filter">
                <option value=""><?php _e('Select Provider', 'eye-book'); ?></option>
                <?php
                global $wpdb;
                $providers = $wpdb->get_results("SELECT id, first_name, last_name FROM " . EYE_BOOK_TABLE_PROVIDERS . " WHERE status = 'active'");
                foreach ($providers as $provider) {
                    echo '<option value="' . esc_attr($provider->id) . '">' . esc_html($provider->first_name . ' ' . $provider->last_name) . '</option>';
                }
                ?>
            </select>

            <select id="schedule-location-filter">
                <option value=""><?php _e('All Locations', 'eye-book'); ?></option>
                <?php
                $locations = $wpdb->get_results("SELECT id, name FROM " . EYE_BOOK_TABLE_LOCATIONS . " WHERE status = 'active'");
                foreach ($locations as $location) {
                    echo '<option value="' . esc_attr($location->id) . '">' . esc_html($location->name) . '</option>';
                }
                ?>
            </select>

            <input type="week" id="schedule-week-filter" class="regular-text" value="<?php echo date('Y-\WW'); ?>">

            <button type="button" class="button" id="schedule-filter-apply"><?php _e('Apply Filters', 'eye-book'); ?></button>
        </div>

        <div class="eye-book-actions">
            <button type="button" class="button button-secondary" id="copy-schedule"><?php _e('Copy Schedule', 'eye-book'); ?></button>
            <button type="button" class="button button-secondary" id="bulk-schedule-update"><?php _e('Bulk Update', 'eye-book'); ?></button>
            <button type="button" class="button button-primary" id="schedule-templates"><?php _e('Templates', 'eye-book'); ?></button>
        </div>
    </div>

    <div class="eye-book-content">
        <div class="schedule-management-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#weekly-schedule" class="nav-tab nav-tab-active"><?php _e('Weekly Schedule', 'eye-book'); ?></a>
                <a href="#time-off-management" class="nav-tab"><?php _e('Time Off', 'eye-book'); ?></a>
                <a href="#schedule-templates-tab" class="nav-tab"><?php _e('Templates', 'eye-book'); ?></a>
                <a href="#schedule-overrides" class="nav-tab"><?php _e('Overrides', 'eye-book'); ?></a>
            </nav>

            <!-- Weekly Schedule Tab -->
            <div id="weekly-schedule" class="tab-content active">
                <div class="schedule-grid-container">
                    <div class="schedule-provider-info" id="provider-info-panel">
                        <div class="no-provider-selected">
                            <p><?php _e('Please select a provider to view their schedule', 'eye-book'); ?></p>
                        </div>
                    </div>

                    <div class="weekly-schedule-grid" id="weekly-schedule-grid">
                        <!-- Schedule grid will be loaded here via AJAX -->
                    </div>
                </div>
            </div>

            <!-- Time Off Management Tab -->
            <div id="time-off-management" class="tab-content">
                <div class="time-off-controls">
                    <button type="button" class="button button-primary" id="add-time-off-btn"><?php _e('Add Time Off', 'eye-book'); ?></button>
                    <button type="button" class="button" id="import-time-off"><?php _e('Import', 'eye-book'); ?></button>
                    <button type="button" class="button" id="export-time-off"><?php _e('Export', 'eye-book'); ?></button>
                </div>

                <table class="wp-list-table widefat fixed striped time-off-table">
                    <thead>
                        <tr>
                            <th><?php _e('Provider', 'eye-book'); ?></th>
                            <th><?php _e('Type', 'eye-book'); ?></th>
                            <th><?php _e('Start Date', 'eye-book'); ?></th>
                            <th><?php _e('End Date', 'eye-book'); ?></th>
                            <th><?php _e('Reason', 'eye-book'); ?></th>
                            <th><?php _e('Status', 'eye-book'); ?></th>
                            <th><?php _e('Actions', 'eye-book'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="time-off-list">
                        <!-- Time off entries will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>

            <!-- Schedule Templates Tab -->
            <div id="schedule-templates-tab" class="tab-content">
                <div class="templates-controls">
                    <button type="button" class="button button-primary" id="create-template"><?php _e('Create Template', 'eye-book'); ?></button>
                    <button type="button" class="button" id="import-template"><?php _e('Import Template', 'eye-book'); ?></button>
                </div>

                <div class="templates-grid" id="schedule-templates-grid">
                    <!-- Templates will be loaded here via AJAX -->
                </div>
            </div>

            <!-- Schedule Overrides Tab -->
            <div id="schedule-overrides" class="tab-content">
                <div class="overrides-controls">
                    <button type="button" class="button button-primary" id="add-override"><?php _e('Add Override', 'eye-book'); ?></button>
                    <input type="date" id="override-date-filter" class="regular-text">
                    <button type="button" class="button" id="apply-override-filter"><?php _e('Filter', 'eye-book'); ?></button>
                </div>

                <table class="wp-list-table widefat fixed striped overrides-table">
                    <thead>
                        <tr>
                            <th><?php _e('Provider', 'eye-book'); ?></th>
                            <th><?php _e('Date', 'eye-book'); ?></th>
                            <th><?php _e('Original Schedule', 'eye-book'); ?></th>
                            <th><?php _e('Override Schedule', 'eye-book'); ?></th>
                            <th><?php _e('Reason', 'eye-book'); ?></th>
                            <th><?php _e('Actions', 'eye-book'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="schedule-overrides-list">
                        <!-- Schedule overrides will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Time Off Modal -->
<div id="time-off-modal" class="eye-book-modal" style="display: none;">
    <div class="eye-book-modal-content">
        <div class="eye-book-modal-header">
            <h2 id="time-off-modal-title"><?php _e('Add Time Off', 'eye-book'); ?></h2>
            <button type="button" class="eye-book-modal-close">&times;</button>
        </div>
        <div class="eye-book-modal-body">
            <form id="time-off-form">
                <input type="hidden" id="time-off-id" name="time_off_id" value="">
                
                <div class="eye-book-form-row">
                    <div class="eye-book-form-group">
                        <label for="time-off-provider"><?php _e('Provider', 'eye-book'); ?> <span class="required">*</span></label>
                        <select id="time-off-provider" name="provider_id" class="regular-text" required>
                            <option value=""><?php _e('Select Provider', 'eye-book'); ?></option>
                            <?php foreach ($providers as $provider) : ?>
                                <option value="<?php echo esc_attr($provider->id); ?>">
                                    <?php echo esc_html($provider->first_name . ' ' . $provider->last_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="eye-book-form-group">
                        <label for="time-off-type"><?php _e('Type', 'eye-book'); ?> <span class="required">*</span></label>
                        <select id="time-off-type" name="type" class="regular-text" required>
                            <option value="vacation"><?php _e('Vacation', 'eye-book'); ?></option>
                            <option value="sick_leave"><?php _e('Sick Leave', 'eye-book'); ?></option>
                            <option value="personal_time"><?php _e('Personal Time', 'eye-book'); ?></option>
                            <option value="conference"><?php _e('Conference/Training', 'eye-book'); ?></option>
                            <option value="emergency"><?php _e('Emergency', 'eye-book'); ?></option>
                            <option value="other"><?php _e('Other', 'eye-book'); ?></option>
                        </select>
                    </div>
                </div>

                <div class="eye-book-form-row">
                    <div class="eye-book-form-group">
                        <label for="time-off-start-date"><?php _e('Start Date', 'eye-book'); ?> <span class="required">*</span></label>
                        <input type="date" id="time-off-start-date" name="start_date" class="regular-text" required>
                    </div>
                    
                    <div class="eye-book-form-group">
                        <label for="time-off-end-date"><?php _e('End Date', 'eye-book'); ?> <span class="required">*</span></label>
                        <input type="date" id="time-off-end-date" name="end_date" class="regular-text" required>
                    </div>
                </div>

                <div class="eye-book-form-row">
                    <div class="eye-book-form-group">
                        <label for="time-off-all-day">
                            <input type="checkbox" id="time-off-all-day" name="all_day" value="1" checked>
                            <?php _e('All Day', 'eye-book'); ?>
                        </label>
                    </div>
                </div>

                <div id="time-off-time-range" style="display: none;">
                    <div class="eye-book-form-row">
                        <div class="eye-book-form-group">
                            <label for="time-off-start-time"><?php _e('Start Time', 'eye-book'); ?></label>
                            <input type="time" id="time-off-start-time" name="start_time" class="regular-text">
                        </div>
                        
                        <div class="eye-book-form-group">
                            <label for="time-off-end-time"><?php _e('End Time', 'eye-book'); ?></label>
                            <input type="time" id="time-off-end-time" name="end_time" class="regular-text">
                        </div>
                    </div>
                </div>

                <div class="eye-book-form-row">
                    <div class="eye-book-form-group full-width">
                        <label for="time-off-reason"><?php _e('Reason / Notes', 'eye-book'); ?></label>
                        <textarea id="time-off-reason" name="reason" rows="3" class="large-text"></textarea>
                    </div>
                </div>

                <div class="eye-book-form-row">
                    <div class="eye-book-form-group">
                        <label for="time-off-recurring">
                            <input type="checkbox" id="time-off-recurring" name="recurring" value="1">
                            <?php _e('Recurring', 'eye-book'); ?>
                        </label>
                    </div>
                </div>

                <div id="time-off-recurrence-options" style="display: none;">
                    <div class="eye-book-form-row">
                        <div class="eye-book-form-group">
                            <label for="recurrence-pattern"><?php _e('Repeat Pattern', 'eye-book'); ?></label>
                            <select id="recurrence-pattern" name="recurrence_pattern" class="regular-text">
                                <option value="weekly"><?php _e('Weekly', 'eye-book'); ?></option>
                                <option value="monthly"><?php _e('Monthly', 'eye-book'); ?></option>
                                <option value="yearly"><?php _e('Yearly', 'eye-book'); ?></option>
                            </select>
                        </div>
                        
                        <div class="eye-book-form-group">
                            <label for="recurrence-end"><?php _e('End Recurrence', 'eye-book'); ?></label>
                            <input type="date" id="recurrence-end" name="recurrence_end" class="regular-text">
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="eye-book-modal-footer">
            <button type="button" class="button button-secondary" id="time-off-modal-cancel"><?php _e('Cancel', 'eye-book'); ?></button>
            <button type="button" class="button button-primary" id="time-off-modal-save"><?php _e('Save Time Off', 'eye-book'); ?></button>
        </div>
    </div>
</div>

<!-- Schedule Template Modal -->
<div id="schedule-template-modal" class="eye-book-modal" style="display: none;">
    <div class="eye-book-modal-content large">
        <div class="eye-book-modal-header">
            <h2 id="template-modal-title"><?php _e('Create Schedule Template', 'eye-book'); ?></h2>
            <button type="button" class="eye-book-modal-close">&times;</button>
        </div>
        <div class="eye-book-modal-body">
            <form id="schedule-template-form">
                <input type="hidden" id="template-id" name="template_id" value="">
                
                <div class="eye-book-form-row">
                    <div class="eye-book-form-group">
                        <label for="template-name"><?php _e('Template Name', 'eye-book'); ?> <span class="required">*</span></label>
                        <input type="text" id="template-name" name="template_name" class="regular-text" required>
                    </div>
                    
                    <div class="eye-book-form-group">
                        <label for="template-description"><?php _e('Description', 'eye-book'); ?></label>
                        <input type="text" id="template-description" name="description" class="regular-text">
                    </div>
                </div>

                <h4><?php _e('Weekly Schedule Template', 'eye-book'); ?></h4>
                <div class="template-schedule-grid">
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
                    <div class="template-day">
                        <div class="day-header">
                            <label>
                                <input type="checkbox" name="template[<?php echo $day; ?>][enabled]" value="1" class="template-day-enabled">
                                <?php echo $label; ?>
                            </label>
                        </div>
                        <div class="day-times">
                            <label><?php _e('Start Time', 'eye-book'); ?></label>
                            <input type="time" name="template[<?php echo $day; ?>][start_time]" class="template-start-time" value="08:00">
                            
                            <label><?php _e('End Time', 'eye-book'); ?></label>
                            <input type="time" name="template[<?php echo $day; ?>][end_time]" class="template-end-time" value="17:00">
                        </div>
                        <div class="break-times">
                            <label>
                                <input type="checkbox" name="template[<?php echo $day; ?>][has_break]" value="1" class="template-has-break">
                                <?php _e('Lunch Break', 'eye-book'); ?>
                            </label>
                            <div class="break-time-inputs" style="display: none;">
                                <input type="time" name="template[<?php echo $day; ?>][break_start]" class="template-break-start" value="12:00">
                                <span><?php _e('to', 'eye-book'); ?></span>
                                <input type="time" name="template[<?php echo $day; ?>][break_end]" class="template-break-end" value="13:00">
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="eye-book-form-row">
                    <div class="eye-book-form-group">
                        <label for="template-default-duration"><?php _e('Default Appointment Duration (minutes)', 'eye-book'); ?></label>
                        <input type="number" id="template-default-duration" name="default_duration" class="regular-text" min="5" max="240" step="5" value="30">
                    </div>
                    
                    <div class="eye-book-form-group">
                        <label for="template-buffer-time"><?php _e('Buffer Time (minutes)', 'eye-book'); ?></label>
                        <input type="number" id="template-buffer-time" name="buffer_time" class="regular-text" min="0" max="60" step="5" value="0">
                    </div>
                </div>
            </form>
        </div>
        <div class="eye-book-modal-footer">
            <button type="button" class="button button-secondary" id="template-modal-cancel"><?php _e('Cancel', 'eye-book'); ?></button>
            <button type="button" class="button button-primary" id="template-modal-save"><?php _e('Save Template', 'eye-book'); ?></button>
        </div>
    </div>
</div>

<!-- Schedule Override Modal -->
<div id="schedule-override-modal" class="eye-book-modal" style="display: none;">
    <div class="eye-book-modal-content">
        <div class="eye-book-modal-header">
            <h2 id="override-modal-title"><?php _e('Add Schedule Override', 'eye-book'); ?></h2>
            <button type="button" class="eye-book-modal-close">&times;</button>
        </div>
        <div class="eye-book-modal-body">
            <form id="schedule-override-form">
                <input type="hidden" id="override-id" name="override_id" value="">
                
                <div class="eye-book-form-row">
                    <div class="eye-book-form-group">
                        <label for="override-provider"><?php _e('Provider', 'eye-book'); ?> <span class="required">*</span></label>
                        <select id="override-provider" name="provider_id" class="regular-text" required>
                            <option value=""><?php _e('Select Provider', 'eye-book'); ?></option>
                            <?php foreach ($providers as $provider) : ?>
                                <option value="<?php echo esc_attr($provider->id); ?>">
                                    <?php echo esc_html($provider->first_name . ' ' . $provider->last_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="eye-book-form-group">
                        <label for="override-date"><?php _e('Date', 'eye-book'); ?> <span class="required">*</span></label>
                        <input type="date" id="override-date" name="override_date" class="regular-text" required>
                    </div>
                </div>

                <div class="eye-book-form-row">
                    <div class="eye-book-form-group">
                        <label for="override-start-time"><?php _e('Start Time', 'eye-book'); ?></label>
                        <input type="time" id="override-start-time" name="start_time" class="regular-text">
                    </div>
                    
                    <div class="eye-book-form-group">
                        <label for="override-end-time"><?php _e('End Time', 'eye-book'); ?></label>
                        <input type="time" id="override-end-time" name="end_time" class="regular-text">
                    </div>
                </div>

                <div class="eye-book-form-row">
                    <div class="eye-book-form-group">
                        <label for="override-unavailable">
                            <input type="checkbox" id="override-unavailable" name="unavailable" value="1">
                            <?php _e('Mark as Unavailable', 'eye-book'); ?>
                        </label>
                    </div>
                </div>

                <div class="eye-book-form-row">
                    <div class="eye-book-form-group full-width">
                        <label for="override-reason"><?php _e('Reason', 'eye-book'); ?></label>
                        <textarea id="override-reason" name="reason" rows="3" class="large-text"></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="eye-book-modal-footer">
            <button type="button" class="button button-secondary" id="override-modal-cancel"><?php _e('Cancel', 'eye-book'); ?></button>
            <button type="button" class="button button-primary" id="override-modal-save"><?php _e('Save Override', 'eye-book'); ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize provider schedules management
    EyeBook.ProviderSchedules.init();
});
</script>