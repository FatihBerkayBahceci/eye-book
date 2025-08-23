<?php
/**
 * Appointment Types Admin View
 *
 * @package EyeBook
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('eye_book_manage_appointments')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'eye-book'));
}

$appointment_type = new Eye_Book_Appointment_Type();
$action = $_GET['action'] ?? 'list';
$message = '';

// Handle form submissions
if ($_POST && isset($_POST['eye_book_appointment_type_nonce'])) {
    if (wp_verify_nonce($_POST['eye_book_appointment_type_nonce'], 'eye_book_appointment_type_action')) {
        if (isset($_POST['action']) && $_POST['action'] === 'create') {
            $result = $appointment_type->create($_POST);
            if (is_wp_error($result)) {
                $message = '<div class="notice notice-error"><p>' . $result->get_error_message() . '</p></div>';
            } else {
                $message = '<div class="notice notice-success"><p>' . __('Appointment type created successfully.', 'eye-book') . '</p></div>';
                $action = 'list';
            }
        } elseif (isset($_POST['action']) && $_POST['action'] === 'update') {
            $id = intval($_POST['id']);
            $result = $appointment_type->update($id, $_POST);
            if (is_wp_error($result)) {
                $message = '<div class="notice notice-error"><p>' . $result->get_error_message() . '</p></div>';
            } else {
                $message = '<div class="notice notice-success"><p>' . __('Appointment type updated successfully.', 'eye-book') . '</p></div>';
                $action = 'list';
            }
        }
    }
}

// Handle delete action
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    if (wp_verify_nonce($_GET['_wpnonce'], 'delete_appointment_type_' . $id)) {
        $result = $appointment_type->delete($id);
        if (is_wp_error($result)) {
            $message = '<div class="notice notice-error"><p>' . $result->get_error_message() . '</p></div>';
        } else {
            $message = '<div class="notice notice-success"><p>' . __('Appointment type deleted successfully.', 'eye-book') . '</p></div>';
        }
        $action = 'list';
    }
}

// Get appointment type for editing
$edit_type = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $edit_type = $appointment_type->get($id);
    if (!$edit_type) {
        $message = '<div class="notice notice-error"><p>' . __('Appointment type not found.', 'eye-book') . '</p></div>';
        $action = 'list';
    }
}

/**
 * Render list view
 *
 * @param Eye_Book_Appointment_Type $appointment_type
 */
function render_list_view($appointment_type) {
    $types = $appointment_type->get_all();
    ?>
    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="bulk_action">
                <option value="-1"><?php _e('Bulk Actions', 'eye-book'); ?></option>
                <option value="delete"><?php _e('Delete', 'eye-book'); ?></option>
            </select>
            <input type="submit" class="button action" value="<?php _e('Apply', 'eye-book'); ?>">
        </div>
        <br class="clear">
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <td class="manage-column column-cb check-column">
                    <input type="checkbox" id="cb-select-all-1">
                </td>
                <th scope="col" class="manage-column column-name"><?php _e('Name', 'eye-book'); ?></th>
                <th scope="col" class="manage-column column-duration"><?php _e('Duration', 'eye-book'); ?></th>
                <th scope="col" class="manage-column column-color"><?php _e('Color', 'eye-book'); ?></th>
                <th scope="col" class="manage-column column-price"><?php _e('Price', 'eye-book'); ?></th>
                <th scope="col" class="manage-column column-requires-forms"><?php _e('Forms Required', 'eye-book'); ?></th>
                <th scope="col" class="manage-column column-actions"><?php _e('Actions', 'eye-book'); ?></th>
            </tr>
        </thead>
        
        <tbody>
            <?php if (empty($types)): ?>
                <tr>
                    <td colspan="7"><?php _e('No appointment types found.', 'eye-book'); ?></td>
                </tr>
            <?php else: ?>
                <?php foreach ($types as $type): ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <input type="checkbox" name="appointment_type_ids[]" value="<?php echo $type->id; ?>">
                        </th>
                        <td class="column-name">
                            <strong>
                                <a href="?page=eye-book-appointment-types&action=edit&id=<?php echo $type->id; ?>">
                                    <?php echo esc_html($type->name); ?>
                                </a>
                            </strong>
                            <?php if (!empty($type->description)): ?>
                                <br><small><?php echo esc_html($type->description); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="column-duration"><?php echo esc_html($type->duration); ?> min</td>
                        <td class="column-color">
                            <span class="color-preview" style="background-color: <?php echo esc_attr($type->color); ?>; width: 20px; height: 20px; display: inline-block; border: 1px solid #ccc;"></span>
                            <?php echo esc_html($type->color); ?>
                        </td>
                        <td class="column-price">$<?php echo number_format($type->price, 2); ?></td>
                        <td class="column-requires-forms">
                            <?php echo $type->requires_forms ? __('Yes', 'eye-book') : __('No', 'eye-book'); ?>
                        </td>
                        <td class="column-actions">
                            <a href="?page=eye-book-appointment-types&action=edit&id=<?php echo $type->id; ?>" 
                               class="button button-small"><?php _e('Edit', 'eye-book'); ?></a>
                            <a href="<?php echo wp_nonce_url('?page=eye-book-appointment-types&action=delete&id=' . $type->id, 'delete_appointment_type_' . $type->id); ?>" 
                               class="button button-small button-link-delete" 
                               onclick="return confirm('<?php _e('Are you sure you want to delete this appointment type?', 'eye-book'); ?>')">
                                <?php _e('Delete', 'eye-book'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}

/**
 * Render form view
 *
 * @param Eye_Book_Appointment_Type $appointment_type
 * @param object|null $edit_type
 */
function render_form_view($appointment_type, $edit_type) {
    $is_edit = $edit_type !== null;
    $title = $is_edit ? __('Edit Appointment Type', 'eye-book') : __('Add New Appointment Type', 'eye-book');
    $button_text = $is_edit ? __('Update Appointment Type', 'eye-book') : __('Add Appointment Type', 'eye-book');
    ?>
    <h2><?php echo $title; ?></h2>
    
    <form method="post" action="" class="eye-book-form">
        <?php wp_nonce_field('eye_book_appointment_type_action', 'eye_book_appointment_type_nonce'); ?>
        <input type="hidden" name="action" value="<?php echo $is_edit ? 'update' : 'create'; ?>">
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?php echo $edit_type->id; ?>">
        <?php endif; ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="name"><?php _e('Name', 'eye-book'); ?> <span class="required">*</span></label>
                </th>
                <td>
                    <input type="text" id="name" name="name" 
                           value="<?php echo $is_edit ? esc_attr($edit_type->name) : ''; ?>" 
                           class="regular-text" required>
                    <p class="description"><?php _e('The name of the appointment type (e.g., Routine Exam, Consultation)', 'eye-book'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="description"><?php _e('Description', 'eye-book'); ?></label>
                </th>
                <td>
                    <textarea id="description" name="description" rows="3" class="large-text"><?php echo $is_edit ? esc_textarea($edit_type->description) : ''; ?></textarea>
                    <p class="description"><?php _e('Optional description of the appointment type', 'eye-book'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="duration"><?php _e('Duration (minutes)', 'eye-book'); ?></label>
                </th>
                <td>
                    <select id="duration" name="duration">
                        <option value="15" <?php echo ($is_edit && $edit_type->duration == 15) ? 'selected' : ''; ?>>15 minutes</option>
                        <option value="30" <?php echo ($is_edit && $edit_type->duration == 30) ? 'selected' : ''; ?>>30 minutes</option>
                        <option value="45" <?php echo ($is_edit && $edit_type->duration == 45) ? 'selected' : ''; ?>>45 minutes</option>
                        <option value="60" <?php echo ($is_edit && $edit_type->duration == 60) ? 'selected' : ''; ?>>1 hour</option>
                        <option value="90" <?php echo ($is_edit && $edit_type->duration == 90) ? 'selected' : ''; ?>>1.5 hours</option>
                        <option value="120" <?php echo ($is_edit && $edit_type->duration == 120) ? 'selected' : ''; ?>>2 hours</option>
                    </select>
                    <p class="description"><?php _e('How long this appointment type typically takes', 'eye-book'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="color"><?php _e('Color', 'eye-book'); ?></label>
                </th>
                <td>
                    <input type="color" id="color" name="color" 
                           value="<?php echo $is_edit ? esc_attr($edit_type->color) : '#007cba'; ?>" 
                           class="regular-text">
                    <p class="description"><?php _e('Color used to represent this appointment type in calendars', 'eye-book'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="price"><?php _e('Price', 'eye-book'); ?></label>
                </th>
                <td>
                    <input type="number" id="price" name="price" 
                           value="<?php echo $is_edit ? esc_attr($edit_type->price) : '0.00'; ?>" 
                           class="regular-text" step="0.01" min="0">
                    <p class="description"><?php _e('Standard price for this appointment type (optional)', 'eye-book'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="requires_forms"><?php _e('Requires Forms', 'eye-book'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="requires_forms" name="requires_forms" value="1" 
                           <?php echo ($is_edit && $edit_type->requires_forms) ? 'checked' : ''; ?>>
                    <label for="requires_forms"><?php _e('Patients must complete forms before this appointment', 'eye-book'); ?></label>
                </td>
            </tr>
        </table>
        
        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $button_text; ?>">
            <a href="?page=eye-book-appointment-types" class="button button-secondary"><?php _e('Cancel', 'eye-book'); ?></a>
        </p>
    </form>
    <?php
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Appointment Types', 'eye-book'); ?></h1>
    <a href="?page=eye-book-appointment-types&action=create" class="page-title-action">
        <?php _e('Add New', 'eye-book'); ?>
    </a>
    
    <?php echo $message; ?>
    
    <?php if ($action === 'list'): ?>
        <?php render_list_view($appointment_type); ?>
    <?php elseif ($action === 'create'): ?>
        <?php render_form_view($appointment_type, null); ?>
    <?php elseif ($action === 'edit' && $edit_type): ?>
        <?php render_form_view($appointment_type, $edit_type); ?>
    <?php endif; ?>
</div>
