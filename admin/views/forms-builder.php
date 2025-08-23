<?php
/**
 * Forms Builder View
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
    <h1 class="wp-heading-inline"><?php _e('Forms Builder', 'eye-book'); ?></h1>
    <a href="#" class="page-title-action" id="create-new-form"><?php _e('Create Form', 'eye-book'); ?></a>
    <hr class="wp-header-end">

    <div class="eye-book-admin-header">
        <div class="eye-book-filters">
            <input type="text" id="forms-search" class="regular-text" placeholder="<?php _e('Search forms...', 'eye-book'); ?>">
            
            <select id="forms-type-filter">
                <option value=""><?php _e('All Types', 'eye-book'); ?></option>
                <option value="intake"><?php _e('Patient Intake', 'eye-book'); ?></option>
                <option value="medical_history"><?php _e('Medical History', 'eye-book'); ?></option>
                <option value="consent"><?php _e('Consent Form', 'eye-book'); ?></option>
                <option value="insurance"><?php _e('Insurance Form', 'eye-book'); ?></option>
                <option value="questionnaire"><?php _e('Questionnaire', 'eye-book'); ?></option>
                <option value="feedback"><?php _e('Feedback Form', 'eye-book'); ?></option>
                <option value="custom"><?php _e('Custom Form', 'eye-book'); ?></option>
            </select>

            <select id="forms-status-filter">
                <option value=""><?php _e('All Statuses', 'eye-book'); ?></option>
                <option value="active"><?php _e('Active', 'eye-book'); ?></option>
                <option value="inactive"><?php _e('Inactive', 'eye-book'); ?></option>
                <option value="draft"><?php _e('Draft', 'eye-book'); ?></option>
            </select>

            <button type="button" class="button" id="forms-filter-apply"><?php _e('Filter', 'eye-book'); ?></button>
            <button type="button" class="button" id="forms-filter-clear"><?php _e('Clear', 'eye-book'); ?></button>
        </div>

        <div class="eye-book-actions">
            <button type="button" class="button button-secondary" id="import-forms"><?php _e('Import Forms', 'eye-book'); ?></button>
            <button type="button" class="button button-secondary" id="form-templates"><?php _e('Templates', 'eye-book'); ?></button>
        </div>
    </div>

    <div class="eye-book-content">
        <div class="forms-management-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#forms-list" class="nav-tab nav-tab-active"><?php _e('Forms List', 'eye-book'); ?></a>
                <a href="#form-builder" class="nav-tab"><?php _e('Form Builder', 'eye-book'); ?></a>
                <a href="#form-responses" class="nav-tab"><?php _e('Responses', 'eye-book'); ?></a>
                <a href="#form-analytics" class="nav-tab"><?php _e('Analytics', 'eye-book'); ?></a>
            </nav>

            <!-- Forms List Tab -->
            <div id="forms-list" class="tab-content active">
                <table class="wp-list-table widefat fixed striped forms">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column">
                                <label class="screen-reader-text" for="cb-select-all"><?php _e('Select All', 'eye-book'); ?></label>
                                <input id="cb-select-all" type="checkbox">
                            </td>
                            <th scope="col" class="manage-column column-form-name sortable">
                                <a href="#">
                                    <span><?php _e('Form Name', 'eye-book'); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th scope="col" class="manage-column column-form-type">
                                <?php _e('Type', 'eye-book'); ?>
                            </th>
                            <th scope="col" class="manage-column column-fields-count">
                                <?php _e('Fields', 'eye-book'); ?>
                            </th>
                            <th scope="col" class="manage-column column-responses sortable">
                                <a href="#">
                                    <span><?php _e('Responses', 'eye-book'); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th scope="col" class="manage-column column-created-date sortable">
                                <a href="#">
                                    <span><?php _e('Created', 'eye-book'); ?></span>
                                    <span class="sorting-indicator"></span>
                                </a>
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
                    <tbody id="forms-list-body">
                        <!-- Forms will be loaded here via AJAX -->
                    </tbody>
                </table>

                <div class="tablenav bottom">
                    <div class="alignleft actions bulkactions">
                        <select name="bulk-action" id="bulk-action-selector-bottom">
                            <option value="-1"><?php _e('Bulk Actions', 'eye-book'); ?></option>
                            <option value="activate"><?php _e('Activate', 'eye-book'); ?></option>
                            <option value="deactivate"><?php _e('Deactivate', 'eye-book'); ?></option>
                            <option value="duplicate"><?php _e('Duplicate', 'eye-book'); ?></option>
                            <option value="export"><?php _e('Export', 'eye-book'); ?></option>
                            <option value="delete"><?php _e('Delete', 'eye-book'); ?></option>
                        </select>
                        <input type="submit" id="bulk-action-apply" class="button action" value="<?php _e('Apply', 'eye-book'); ?>">
                    </div>

                    <div class="tablenav-pages">
                        <span class="displaying-num"><span id="total-forms">0</span> <?php _e('items', 'eye-book'); ?></span>
                        <span class="pagination-links" id="forms-pagination">
                            <!-- Pagination will be loaded here -->
                        </span>
                    </div>
                </div>
            </div>

            <!-- Form Builder Tab -->
            <div id="form-builder" class="tab-content">
                <div class="form-builder-container">
                    <!-- Form Settings Panel -->
                    <div class="form-settings-panel">
                        <h3><?php _e('Form Settings', 'eye-book'); ?></h3>
                        <form id="form-settings">
                            <input type="hidden" id="form-id" name="form_id" value="">
                            
                            <div class="settings-group">
                                <label for="form-title"><?php _e('Form Title', 'eye-book'); ?> <span class="required">*</span></label>
                                <input type="text" id="form-title" name="form_title" class="regular-text" required>
                            </div>

                            <div class="settings-group">
                                <label for="form-description"><?php _e('Description', 'eye-book'); ?></label>
                                <textarea id="form-description" name="form_description" rows="3" class="large-text"></textarea>
                            </div>

                            <div class="settings-group">
                                <label for="form-type"><?php _e('Form Type', 'eye-book'); ?></label>
                                <select id="form-type" name="form_type" class="regular-text">
                                    <option value="intake"><?php _e('Patient Intake', 'eye-book'); ?></option>
                                    <option value="medical_history"><?php _e('Medical History', 'eye-book'); ?></option>
                                    <option value="consent"><?php _e('Consent Form', 'eye-book'); ?></option>
                                    <option value="insurance"><?php _e('Insurance Form', 'eye-book'); ?></option>
                                    <option value="questionnaire"><?php _e('Questionnaire', 'eye-book'); ?></option>
                                    <option value="feedback"><?php _e('Feedback Form', 'eye-book'); ?></option>
                                    <option value="custom"><?php _e('Custom Form', 'eye-book'); ?></option>
                                </select>
                            </div>

                            <div class="settings-group">
                                <label for="form-status"><?php _e('Status', 'eye-book'); ?></label>
                                <select id="form-status" name="form_status" class="regular-text">
                                    <option value="draft"><?php _e('Draft', 'eye-book'); ?></option>
                                    <option value="active"><?php _e('Active', 'eye-book'); ?></option>
                                    <option value="inactive"><?php _e('Inactive', 'eye-book'); ?></option>
                                </select>
                            </div>

                            <div class="settings-group">
                                <label>
                                    <input type="checkbox" id="form-required" name="form_required" value="1">
                                    <?php _e('Required for New Patients', 'eye-book'); ?>
                                </label>
                            </div>

                            <div class="settings-group">
                                <label>
                                    <input type="checkbox" id="form-hipaa-compliant" name="hipaa_compliant" value="1" checked>
                                    <?php _e('HIPAA Compliant Form', 'eye-book'); ?>
                                </label>
                            </div>
                        </form>
                    </div>

                    <!-- Form Builder Area -->
                    <div class="form-builder-area">
                        <div class="form-builder-toolbar">
                            <button type="button" class="button button-primary" id="save-form"><?php _e('Save Form', 'eye-book'); ?></button>
                            <button type="button" class="button" id="preview-form"><?php _e('Preview', 'eye-book'); ?></button>
                            <button type="button" class="button" id="clear-form"><?php _e('Clear All', 'eye-book'); ?></button>
                        </div>

                        <div class="form-canvas" id="form-canvas">
                            <div class="form-canvas-placeholder">
                                <p><?php _e('Drag fields from the sidebar to build your form', 'eye-book'); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Field Types Panel -->
                    <div class="field-types-panel">
                        <h3><?php _e('Field Types', 'eye-book'); ?></h3>
                        
                        <div class="field-categories">
                            <div class="field-category">
                                <h4><?php _e('Basic Fields', 'eye-book'); ?></h4>
                                <div class="field-type" data-type="text">
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php _e('Text Field', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="textarea">
                                    <span class="dashicons dashicons-text"></span>
                                    <?php _e('Textarea', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="select">
                                    <span class="dashicons dashicons-menu-alt"></span>
                                    <?php _e('Dropdown', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="radio">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php _e('Radio Buttons', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="checkbox">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php _e('Checkboxes', 'eye-book'); ?>
                                </div>
                            </div>

                            <div class="field-category">
                                <h4><?php _e('Input Fields', 'eye-book'); ?></h4>
                                <div class="field-type" data-type="email">
                                    <span class="dashicons dashicons-email"></span>
                                    <?php _e('Email', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="phone">
                                    <span class="dashicons dashicons-phone"></span>
                                    <?php _e('Phone', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="date">
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                    <?php _e('Date', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="number">
                                    <span class="dashicons dashicons-calculator"></span>
                                    <?php _e('Number', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="url">
                                    <span class="dashicons dashicons-admin-links"></span>
                                    <?php _e('URL', 'eye-book'); ?>
                                </div>
                            </div>

                            <div class="field-category">
                                <h4><?php _e('Advanced Fields', 'eye-book'); ?></h4>
                                <div class="field-type" data-type="signature">
                                    <span class="dashicons dashicons-edit-large"></span>
                                    <?php _e('Signature', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="file">
                                    <span class="dashicons dashicons-media-document"></span>
                                    <?php _e('File Upload', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="rating">
                                    <span class="dashicons dashicons-star-filled"></span>
                                    <?php _e('Rating', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="address">
                                    <span class="dashicons dashicons-location-alt"></span>
                                    <?php _e('Address', 'eye-book'); ?>
                                </div>
                            </div>

                            <div class="field-category">
                                <h4><?php _e('Medical Fields', 'eye-book'); ?></h4>
                                <div class="field-type" data-type="medical_history">
                                    <span class="dashicons dashicons-heart"></span>
                                    <?php _e('Medical History', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="medications">
                                    <span class="dashicons dashicons-admin-tools"></span>
                                    <?php _e('Medications', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="allergies">
                                    <span class="dashicons dashicons-warning"></span>
                                    <?php _e('Allergies', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="insurance_info">
                                    <span class="dashicons dashicons-id-alt"></span>
                                    <?php _e('Insurance Info', 'eye-book'); ?>
                                </div>
                            </div>

                            <div class="field-category">
                                <h4><?php _e('Layout Elements', 'eye-book'); ?></h4>
                                <div class="field-type" data-type="section_break">
                                    <span class="dashicons dashicons-minus"></span>
                                    <?php _e('Section Break', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="html_block">
                                    <span class="dashicons dashicons-editor-code"></span>
                                    <?php _e('HTML Block', 'eye-book'); ?>
                                </div>
                                <div class="field-type" data-type="page_break">
                                    <span class="dashicons dashicons-media-document"></span>
                                    <?php _e('Page Break', 'eye-book'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Responses Tab -->
            <div id="form-responses" class="tab-content">
                <div class="responses-controls">
                    <select id="responses-form-filter">
                        <option value=""><?php _e('Select Form', 'eye-book'); ?></option>
                        <!-- Forms will be loaded here via AJAX -->
                    </select>
                    
                    <input type="date" id="responses-date-from" class="regular-text">
                    <input type="date" id="responses-date-to" class="regular-text">
                    
                    <button type="button" class="button" id="filter-responses"><?php _e('Filter', 'eye-book'); ?></button>
                    <button type="button" class="button" id="export-responses"><?php _e('Export', 'eye-book'); ?></button>
                </div>

                <table class="wp-list-table widefat fixed striped responses">
                    <thead>
                        <tr>
                            <th><?php _e('Patient', 'eye-book'); ?></th>
                            <th><?php _e('Form', 'eye-book'); ?></th>
                            <th><?php _e('Submitted', 'eye-book'); ?></th>
                            <th><?php _e('Status', 'eye-book'); ?></th>
                            <th><?php _e('Actions', 'eye-book'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="form-responses-list">
                        <!-- Responses will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>

            <!-- Form Analytics Tab -->
            <div id="form-analytics" class="tab-content">
                <div class="analytics-dashboard">
                    <div class="analytics-metrics">
                        <div class="metric-card">
                            <h4><?php _e('Total Forms', 'eye-book'); ?></h4>
                            <div class="metric-value" id="total-forms-count">-</div>
                        </div>
                        <div class="metric-card">
                            <h4><?php _e('Total Responses', 'eye-book'); ?></h4>
                            <div class="metric-value" id="total-responses-count">-</div>
                        </div>
                        <div class="metric-card">
                            <h4><?php _e('Completion Rate', 'eye-book'); ?></h4>
                            <div class="metric-value" id="completion-rate">-</div>
                        </div>
                        <div class="metric-card">
                            <h4><?php _e('Avg. Completion Time', 'eye-book'); ?></h4>
                            <div class="metric-value" id="avg-completion-time">-</div>
                        </div>
                    </div>

                    <div class="analytics-charts">
                        <div class="chart-container">
                            <h4><?php _e('Form Submissions Over Time', 'eye-book'); ?></h4>
                            <canvas id="submissions-chart" width="400" height="200"></canvas>
                        </div>
                        
                        <div class="chart-container">
                            <h4><?php _e('Popular Forms', 'eye-book'); ?></h4>
                            <canvas id="popular-forms-chart" width="400" height="200"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Field Properties Modal -->
<div id="field-properties-modal" class="eye-book-modal" style="display: none;">
    <div class="eye-book-modal-content">
        <div class="eye-book-modal-header">
            <h2 id="field-properties-title"><?php _e('Field Properties', 'eye-book'); ?></h2>
            <button type="button" class="eye-book-modal-close">&times;</button>
        </div>
        <div class="eye-book-modal-body">
            <form id="field-properties-form">
                <div id="field-properties-content">
                    <!-- Field-specific properties will be loaded here -->
                </div>
            </form>
        </div>
        <div class="eye-book-modal-footer">
            <button type="button" class="button button-secondary" id="field-properties-cancel"><?php _e('Cancel', 'eye-book'); ?></button>
            <button type="button" class="button button-primary" id="field-properties-save"><?php _e('Save Properties', 'eye-book'); ?></button>
        </div>
    </div>
</div>

<!-- Form Preview Modal -->
<div id="form-preview-modal" class="eye-book-modal" style="display: none;">
    <div class="eye-book-modal-content large">
        <div class="eye-book-modal-header">
            <h2><?php _e('Form Preview', 'eye-book'); ?></h2>
            <button type="button" class="eye-book-modal-close">&times;</button>
        </div>
        <div class="eye-book-modal-body">
            <div id="form-preview-content">
                <!-- Form preview will be loaded here -->
            </div>
        </div>
        <div class="eye-book-modal-footer">
            <button type="button" class="button button-secondary" id="form-preview-close"><?php _e('Close', 'eye-book'); ?></button>
            <button type="button" class="button button-primary" id="form-preview-test"><?php _e('Test Form', 'eye-book'); ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize forms builder
    EyeBook.FormsBuilder.init();
});
</script>