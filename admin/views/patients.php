<?php
/**
 * Patients management view
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
    <h1 class="wp-heading-inline"><?php _e('Patients', 'eye-book'); ?></h1>
    <a href="#" class="page-title-action" id="add-new-patient"><?php _e('Add New', 'eye-book'); ?></a>
    <hr class="wp-header-end">

    <div class="eye-book-admin-header">
        <div class="eye-book-filters">
            <input type="text" id="patient-search" class="regular-text" placeholder="<?php _e('Search patients...', 'eye-book'); ?>">
            
            <select id="patient-insurance-filter">
                <option value=""><?php _e('All Insurance Providers', 'eye-book'); ?></option>
                <?php
                global $wpdb;
                $insurance_providers = $wpdb->get_col("SELECT DISTINCT insurance_provider FROM " . EYE_BOOK_TABLE_PATIENTS . " WHERE insurance_provider IS NOT NULL AND insurance_provider != '' ORDER BY insurance_provider");
                foreach ($insurance_providers as $provider) {
                    echo '<option value="' . esc_attr($provider) . '">' . esc_html($provider) . '</option>';
                }
                ?>
            </select>

            <select id="patient-age-filter">
                <option value=""><?php _e('All Ages', 'eye-book'); ?></option>
                <option value="0-17"><?php _e('Under 18', 'eye-book'); ?></option>
                <option value="18-30"><?php _e('18-30', 'eye-book'); ?></option>
                <option value="31-50"><?php _e('31-50', 'eye-book'); ?></option>
                <option value="51-70"><?php _e('51-70', 'eye-book'); ?></option>
                <option value="71+"><?php _e('Over 70', 'eye-book'); ?></option>
            </select>

            <button type="button" class="button" id="patient-filter-apply"><?php _e('Filter', 'eye-book'); ?></button>
            <button type="button" class="button" id="patient-filter-clear"><?php _e('Clear', 'eye-book'); ?></button>
        </div>

        <div class="eye-book-actions">
            <button type="button" class="button button-secondary" id="export-patients"><?php _e('Export', 'eye-book'); ?></button>
            <button type="button" class="button button-secondary" id="import-patients"><?php _e('Import', 'eye-book'); ?></button>
        </div>
    </div>

    <div class="eye-book-content">
        <table class="wp-list-table widefat fixed striped patients">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all"><?php _e('Select All', 'eye-book'); ?></label>
                        <input id="cb-select-all" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-patient-id sortable">
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
                    <th scope="col" class="manage-column column-contact">
                        <?php _e('Contact', 'eye-book'); ?>
                    </th>
                    <th scope="col" class="manage-column column-dob sortable">
                        <a href="#">
                            <span><?php _e('Date of Birth', 'eye-book'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column column-insurance">
                        <?php _e('Insurance', 'eye-book'); ?>
                    </th>
                    <th scope="col" class="manage-column column-last-visit sortable">
                        <a href="#">
                            <span><?php _e('Last Visit', 'eye-book'); ?></span>
                            <span class="sorting-indicator"></span>
                        </a>
                    </th>
                    <th scope="col" class="manage-column column-next-appointment">
                        <?php _e('Next Appointment', 'eye-book'); ?>
                    </th>
                    <th scope="col" class="manage-column column-actions">
                        <?php _e('Actions', 'eye-book'); ?>
                    </th>
                </tr>
            </thead>
            <tbody id="patients-list">
                <!-- Patients will be loaded here via AJAX -->
            </tbody>
        </table>

        <div class="tablenav bottom">
            <div class="alignleft actions bulkactions">
                <select name="bulk-action" id="bulk-action-selector-bottom">
                    <option value="-1"><?php _e('Bulk Actions', 'eye-book'); ?></option>
                    <option value="export"><?php _e('Export Selected', 'eye-book'); ?></option>
                    <option value="send-reminder"><?php _e('Send Reminder', 'eye-book'); ?></option>
                    <option value="merge"><?php _e('Merge Records', 'eye-book'); ?></option>
                    <option value="delete"><?php _e('Delete', 'eye-book'); ?></option>
                </select>
                <input type="submit" id="bulk-action-apply" class="button action" value="<?php _e('Apply', 'eye-book'); ?>">
            </div>

            <div class="tablenav-pages">
                <span class="displaying-num"><span id="total-patients">0</span> <?php _e('items', 'eye-book'); ?></span>
                <span class="pagination-links" id="patients-pagination">
                    <!-- Pagination will be loaded here -->
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Patient Modal -->
<div id="patient-modal" class="eye-book-modal" style="display: none;">
    <div class="eye-book-modal-content large">
        <div class="eye-book-modal-header">
            <h2 id="patient-modal-title"><?php _e('Add New Patient', 'eye-book'); ?></h2>
            <button type="button" class="eye-book-modal-close">&times;</button>
        </div>
        <div class="eye-book-modal-body">
            <div class="eye-book-tabs">
                <nav class="nav-tab-wrapper">
                    <a href="#patient-basic-info" class="nav-tab nav-tab-active"><?php _e('Basic Information', 'eye-book'); ?></a>
                    <a href="#patient-contact-info" class="nav-tab"><?php _e('Contact Information', 'eye-book'); ?></a>
                    <a href="#patient-insurance-info" class="nav-tab"><?php _e('Insurance', 'eye-book'); ?></a>
                    <a href="#patient-medical-info" class="nav-tab"><?php _e('Medical Information', 'eye-book'); ?></a>
                    <a href="#patient-emergency-contact" class="nav-tab"><?php _e('Emergency Contact', 'eye-book'); ?></a>
                </nav>

                <form id="patient-form">
                    <input type="hidden" id="patient-id" name="patient_id" value="">
                    
                    <!-- Basic Information Tab -->
                    <div id="patient-basic-info" class="tab-content active">
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
                                <label for="date-of-birth"><?php _e('Date of Birth', 'eye-book'); ?> <span class="required">*</span></label>
                                <input type="date" id="date-of-birth" name="date_of_birth" class="regular-text" required>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="gender"><?php _e('Gender', 'eye-book'); ?></label>
                                <select id="gender" name="gender" class="regular-text">
                                    <option value=""><?php _e('Select Gender', 'eye-book'); ?></option>
                                    <option value="male"><?php _e('Male', 'eye-book'); ?></option>
                                    <option value="female"><?php _e('Female', 'eye-book'); ?></option>
                                    <option value="other"><?php _e('Other', 'eye-book'); ?></option>
                                    <option value="prefer_not_to_say"><?php _e('Prefer not to say', 'eye-book'); ?></option>
                                </select>
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="social-security"><?php _e('Social Security Number', 'eye-book'); ?></label>
                                <input type="text" id="social-security" name="social_security_number" class="regular-text" placeholder="XXX-XX-XXXX">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="patient-status"><?php _e('Status', 'eye-book'); ?></label>
                                <select id="patient-status" name="status" class="regular-text">
                                    <option value="active"><?php _e('Active', 'eye-book'); ?></option>
                                    <option value="inactive"><?php _e('Inactive', 'eye-book'); ?></option>
                                    <option value="archived"><?php _e('Archived', 'eye-book'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Tab -->
                    <div id="patient-contact-info" class="tab-content">
                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="email"><?php _e('Email Address', 'eye-book'); ?> <span class="required">*</span></label>
                                <input type="email" id="email" name="email" class="regular-text" required>
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="phone"><?php _e('Phone Number', 'eye-book'); ?> <span class="required">*</span></label>
                                <input type="tel" id="phone" name="phone" class="regular-text" required>
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="mobile-phone"><?php _e('Mobile Phone', 'eye-book'); ?></label>
                                <input type="tel" id="mobile-phone" name="mobile_phone" class="regular-text">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="work-phone"><?php _e('Work Phone', 'eye-book'); ?></label>
                                <input type="tel" id="work-phone" name="work_phone" class="regular-text">
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

                    <!-- Insurance Information Tab -->
                    <div id="patient-insurance-info" class="tab-content">
                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="insurance-provider"><?php _e('Insurance Provider', 'eye-book'); ?></label>
                                <input type="text" id="insurance-provider" name="insurance_provider" class="regular-text">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="insurance-member-id"><?php _e('Member ID', 'eye-book'); ?></label>
                                <input type="text" id="insurance-member-id" name="insurance_member_id" class="regular-text">
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="insurance-group"><?php _e('Group Number', 'eye-book'); ?></label>
                                <input type="text" id="insurance-group" name="insurance_group_number" class="regular-text">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="copay-amount"><?php _e('Copay Amount', 'eye-book'); ?></label>
                                <input type="number" id="copay-amount" name="copay_amount" class="regular-text" step="0.01" min="0">
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group full-width">
                                <label for="insurance-notes"><?php _e('Insurance Notes', 'eye-book'); ?></label>
                                <textarea id="insurance-notes" name="insurance_notes" rows="3" class="large-text"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Medical Information Tab -->
                    <div id="patient-medical-info" class="tab-content">
                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group full-width">
                                <label for="medical-history"><?php _e('Medical History', 'eye-book'); ?></label>
                                <textarea id="medical-history" name="medical_history" rows="4" class="large-text"></textarea>
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group full-width">
                                <label for="current-medications"><?php _e('Current Medications', 'eye-book'); ?></label>
                                <textarea id="current-medications" name="current_medications" rows="4" class="large-text"></textarea>
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group full-width">
                                <label for="allergies"><?php _e('Allergies', 'eye-book'); ?></label>
                                <textarea id="allergies" name="allergies" rows="3" class="large-text"></textarea>
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group full-width">
                                <label for="eye-care-history"><?php _e('Eye Care History', 'eye-book'); ?></label>
                                <textarea id="eye-care-history" name="eye_care_history" rows="4" class="large-text"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contact Tab -->
                    <div id="patient-emergency-contact" class="tab-content">
                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="emergency-contact-name"><?php _e('Emergency Contact Name', 'eye-book'); ?></label>
                                <input type="text" id="emergency-contact-name" name="emergency_contact_name" class="regular-text">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="emergency-contact-relationship"><?php _e('Relationship', 'eye-book'); ?></label>
                                <input type="text" id="emergency-contact-relationship" name="emergency_contact_relationship" class="regular-text">
                            </div>
                        </div>

                        <div class="eye-book-form-row">
                            <div class="eye-book-form-group">
                                <label for="emergency-contact-phone"><?php _e('Emergency Contact Phone', 'eye-book'); ?></label>
                                <input type="tel" id="emergency-contact-phone" name="emergency_contact_phone" class="regular-text">
                            </div>
                            
                            <div class="eye-book-form-group">
                                <label for="emergency-contact-email"><?php _e('Emergency Contact Email', 'eye-book'); ?></label>
                                <input type="email" id="emergency-contact-email" name="emergency_contact_email" class="regular-text">
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div class="eye-book-modal-footer">
            <button type="button" class="button button-secondary" id="patient-modal-cancel"><?php _e('Cancel', 'eye-book'); ?></button>
            <button type="button" class="button button-primary" id="patient-modal-save"><?php _e('Save Patient', 'eye-book'); ?></button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize patients management
    EyeBook.Patients.init();
});
</script>