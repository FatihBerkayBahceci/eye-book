<?php
/**
 * Admin Settings Class
 *
 * @package EyeBook
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eye_Book_Admin_Settings Class
 *
 * Handles admin settings page
 *
 * @since 1.0.0
 */
class Eye_Book_Admin_Settings {

    /**
     * Settings instance
     *
     * @var Eye_Book_Settings
     */
    private $settings;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->settings = new Eye_Book_Settings();
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_eye_book_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_eye_book_reset_settings', array($this, 'ajax_reset_settings'));
        add_action('wp_ajax_eye_book_export_settings', array($this, 'ajax_export_settings'));
        add_action('wp_ajax_eye_book_import_settings', array($this, 'ajax_import_settings'));
    }

    /**
     * Add settings page to admin menu
     *
     * @since 1.0.0
     */
    public function add_settings_page() {
        add_submenu_page(
            'eye-book',
            __('Settings', 'eye-book'),
            __('Settings', 'eye-book'),
            'eye_book_manage_settings',
            'eye-book-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     *
     * @since 1.0.0
     */
    public function register_settings() {
        // General settings
        register_setting('eye_book_general', 'eye_book_booking_enabled');
        register_setting('eye_book_general', 'eye_book_booking_advance_days');
        register_setting('eye_book_general', 'eye_book_appointment_duration');
        register_setting('eye_book_general', 'eye_book_timezone');
        register_setting('eye_book_general', 'eye_book_date_format');
        register_setting('eye_book_general', 'eye_book_time_format');

        // Notification settings
        register_setting('eye_book_notifications', 'eye_book_reminder_email_hours');
        register_setting('eye_book_notifications', 'eye_book_reminder_sms_hours');
        register_setting('eye_book_notifications', 'eye_book_confirmation_email');
        register_setting('eye_book_notifications', 'eye_book_cancellation_email');

        // Security settings
        register_setting('eye_book_security', 'eye_book_hipaa_compliance_mode');
        register_setting('eye_book_security', 'eye_book_encryption_enabled');
        register_setting('eye_book_security', 'eye_book_session_timeout_minutes');
        register_setting('eye_book_security', 'eye_book_max_login_attempts');

        // Payment settings
        register_setting('eye_book_payments', 'eye_book_payment_enabled');
        register_setting('eye_book_payments', 'eye_book_payment_gateway');
        register_setting('eye_book_payments', 'eye_book_stripe_publishable_key');
        register_setting('eye_book_payments', 'eye_book_stripe_secret_key');

        // SMS settings
        register_setting('eye_book_sms', 'eye_book_sms_enabled');
        register_setting('eye_book_sms', 'eye_book_twilio_account_sid');
        register_setting('eye_book_sms', 'eye_book_twilio_auth_token');
        register_setting('eye_book_sms', 'eye_book_twilio_phone_number');
    }

    /**
     * Render settings page
     *
     * @since 1.0.0
     */
    public function render_settings_page() {
        if (!current_user_can('eye_book_manage_settings')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'eye-book'));
        }

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h1><?php _e('Eye-Book Settings', 'eye-book'); ?></h1>
            
            <nav class="nav-tab-wrapper">
                <a href="?page=eye-book-settings&tab=general" 
                   class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General', 'eye-book'); ?>
                </a>
                <a href="?page=eye-book-settings&tab=notifications" 
                   class="nav-tab <?php echo $active_tab === 'notifications' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Notifications', 'eye-book'); ?>
                </a>
                <a href="?page=eye-book-settings&tab=security" 
                   class="nav-tab <?php echo $active_tab === 'security' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Security', 'eye-book'); ?>
                </a>
                <a href="?page=eye-book-settings&tab=payments" 
                   class="nav-tab <?php echo $active_tab === 'payments' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Payments', 'eye-book'); ?>
                </a>
                <a href="?page=eye-book-settings&tab=sms" 
                   class="nav-tab <?php echo $active_tab === 'sms' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('SMS', 'eye-book'); ?>
                </a>
                <a href="?page=eye-book-settings&tab=advanced" 
                   class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Advanced', 'eye-book'); ?>
                </a>
            </nav>

            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'general':
                        $this->render_general_tab();
                        break;
                    case 'notifications':
                        $this->render_notifications_tab();
                        break;
                    case 'security':
                        $this->render_security_tab();
                        break;
                    case 'payments':
                        $this->render_payments_tab();
                        break;
                    case 'sms':
                        $this->render_sms_tab();
                        break;
                    case 'advanced':
                        $this->render_advanced_tab();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render general settings tab
     *
     * @since 1.0.0
     */
    private function render_general_tab() {
        ?>
        <form method="post" action="options.php" class="eye-book-settings-form">
            <?php settings_fields('eye_book_general'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="eye_book_booking_enabled"><?php _e('Enable Online Booking', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="eye_book_booking_enabled" 
                               name="eye_book_booking_enabled" value="1" 
                               <?php checked(get_option('eye_book_booking_enabled', 1)); ?>>
                        <p class="description"><?php _e('Allow patients to book appointments online', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_booking_advance_days"><?php _e('Advance Booking Days', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="eye_book_booking_advance_days" 
                               name="eye_book_booking_advance_days" 
                               value="<?php echo esc_attr(get_option('eye_book_booking_advance_days', 30)); ?>" 
                               min="1" max="365" class="regular-text">
                        <p class="description"><?php _e('How many days in advance patients can book appointments', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_appointment_duration"><?php _e('Default Appointment Duration', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <select id="eye_book_appointment_duration" name="eye_book_appointment_duration">
                            <option value="15" <?php selected(get_option('eye_book_appointment_duration', 30), 15); ?>>15 minutes</option>
                            <option value="30" <?php selected(get_option('eye_book_appointment_duration', 30), 30); ?>>30 minutes</option>
                            <option value="45" <?php selected(get_option('eye_book_appointment_duration', 30), 45); ?>>45 minutes</option>
                            <option value="60" <?php selected(get_option('eye_book_appointment_duration', 30), 60); ?>>1 hour</option>
                            <option value="90" <?php selected(get_option('eye_book_appointment_duration', 30), 90); ?>>1.5 hours</option>
                        </select>
                        <p class="description"><?php _e('Default duration for new appointments', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_timezone"><?php _e('Timezone', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <select id="eye_book_timezone" name="eye_book_timezone">
                            <?php echo wp_timezone_choice(get_option('eye_book_timezone', get_option('timezone_string', 'America/New_York'))); ?>
                        </select>
                        <p class="description"><?php _e('Timezone for appointment scheduling', 'eye-book'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Render notifications settings tab
     *
     * @since 1.0.0
     */
    private function render_notifications_tab() {
        ?>
        <form method="post" action="options.php" class="eye-book-settings-form">
            <?php settings_fields('eye_book_notifications'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="eye_book_reminder_email_hours"><?php _e('Email Reminder Hours', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="eye_book_reminder_email_hours" 
                               name="eye_book_reminder_email_hours" 
                               value="<?php echo esc_attr(get_option('eye_book_reminder_email_hours', 24)); ?>" 
                               min="1" max="168" class="regular-text">
                        <p class="description"><?php _e('Hours before appointment to send email reminder', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_reminder_sms_hours"><?php _e('SMS Reminder Hours', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="eye_book_reminder_sms_hours" 
                               name="eye_book_reminder_sms_hours" 
                               value="<?php echo esc_attr(get_option('eye_book_reminder_sms_hours', 2)); ?>" 
                               min="1" max="24" class="regular-text">
                        <p class="description"><?php _e('Hours before appointment to send SMS reminder', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_confirmation_email"><?php _e('Send Confirmation Emails', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="eye_book_confirmation_email" 
                               name="eye_book_confirmation_email" value="1" 
                               <?php checked(get_option('eye_book_confirmation_email', 1)); ?>>
                        <p class="description"><?php _e('Send confirmation email when appointment is booked', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_cancellation_email"><?php _e('Send Cancellation Emails', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="eye_book_cancellation_email" 
                               name="eye_book_cancellation_email" value="1" 
                               <?php checked(get_option('eye_book_cancellation_email', 1)); ?>>
                        <p class="description"><?php _e('Send cancellation confirmation email', 'eye-book'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Render security settings tab
     *
     * @since 1.0.0
     */
    private function render_security_tab() {
        ?>
        <form method="post" action="options.php" class="eye-book-settings-form">
            <?php settings_fields('eye_book_security'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="eye_book_hipaa_compliance_mode"><?php _e('HIPAA Compliance Mode', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="eye_book_hipaa_compliance_mode" 
                               name="eye_book_hipaa_compliance_mode" value="1" 
                               <?php checked(get_option('eye_book_hipaa_compliance_mode', 1)); ?>>
                        <p class="description"><?php _e('Enable full HIPAA compliance features', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_encryption_enabled"><?php _e('Enable Data Encryption', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="eye_book_encryption_enabled" 
                               name="eye_book_encryption_enabled" value="1" 
                               <?php checked(get_option('eye_book_encryption_enabled', 1)); ?>>
                        <p class="description"><?php _e('Encrypt all patient data (AES-256)', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_session_timeout_minutes"><?php _e('Session Timeout (minutes)', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="eye_book_session_timeout_minutes" 
                               name="eye_book_session_timeout_minutes" 
                               value="<?php echo esc_attr(get_option('eye_book_session_timeout_minutes', 30)); ?>" 
                               min="5" max="480" class="regular-text">
                        <p class="description"><?php _e('Minutes of inactivity before automatic logout', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_max_login_attempts"><?php _e('Max Login Attempts', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="eye_book_max_login_attempts" 
                               name="eye_book_max_login_attempts" 
                               value="<?php echo esc_attr(get_option('eye_book_max_login_attempts', 5)); ?>" 
                               min="3" max="10" class="regular-text">
                        <p class="description"><?php _e('Maximum failed login attempts before lockout', 'eye-book'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Render payments settings tab
     *
     * @since 1.0.0
     */
    private function render_payments_tab() {
        ?>
        <form method="post" action="options.php" class="eye-book-settings-form">
            <?php settings_fields('eye_book_payments'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="eye_book_payment_enabled"><?php _e('Enable Payments', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="eye_book_payment_enabled" 
                               name="eye_book_payment_enabled" value="1" 
                               <?php checked(get_option('eye_book_payment_enabled', 0)); ?>>
                        <p class="description"><?php _e('Enable payment processing for appointments', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_payment_gateway"><?php _e('Payment Gateway', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <select id="eye_book_payment_gateway" name="eye_book_payment_gateway">
                            <option value="stripe" <?php selected(get_option('eye_book_payment_gateway', 'stripe'), 'stripe'); ?>>Stripe</option>
                            <option value="square" <?php selected(get_option('eye_book_payment_gateway', 'stripe'), 'square'); ?>>Square</option>
                            <option value="paypal" <?php selected(get_option('eye_book_payment_gateway', 'stripe'), 'paypal'); ?>>PayPal</option>
                        </select>
                        <p class="description"><?php _e('Select your preferred payment processor', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_stripe_publishable_key"><?php _e('Stripe Publishable Key', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="eye_book_stripe_publishable_key" 
                               name="eye_book_stripe_publishable_key" 
                               value="<?php echo esc_attr(get_option('eye_book_stripe_publishable_key')); ?>" 
                               class="regular-text">
                        <p class="description"><?php _e('Your Stripe publishable key (pk_test_... or pk_live_...)', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_stripe_secret_key"><?php _e('Stripe Secret Key', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="eye_book_stripe_secret_key" 
                               name="eye_book_stripe_secret_key" 
                               value="<?php echo esc_attr(get_option('eye_book_stripe_secret_key')); ?>" 
                               class="regular-text">
                        <p class="description"><?php _e('Your Stripe secret key (sk_test_... or sk_live_...)', 'eye-book'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Render SMS settings tab
     *
     * @since 1.0.0
     */
    private function render_sms_tab() {
        ?>
        <form method="post" action="options.php" class="eye-book-settings-form">
            <?php settings_fields('eye_book_sms'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="eye_book_sms_enabled"><?php _e('Enable SMS Notifications', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="eye_book_sms_enabled" 
                               name="eye_book_sms_enabled" value="1" 
                               <?php checked(get_option('eye_book_sms_enabled', 0)); ?>>
                        <p class="description"><?php _e('Enable SMS reminders and notifications', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_twilio_account_sid"><?php _e('Twilio Account SID', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="eye_book_twilio_account_sid" 
                               name="eye_book_twilio_account_sid" 
                               value="<?php echo esc_attr(get_option('eye_book_twilio_account_sid')); ?>" 
                               class="regular-text">
                        <p class="description"><?php _e('Your Twilio Account SID', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_twilio_auth_token"><?php _e('Twilio Auth Token', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="password" id="eye_book_twilio_auth_token" 
                               name="eye_book_twilio_auth_token" 
                               value="<?php echo esc_attr(get_option('eye_book_twilio_auth_token')); ?>" 
                               class="regular-text">
                        <p class="description"><?php _e('Your Twilio Auth Token', 'eye-book'); ?></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="eye_book_twilio_phone_number"><?php _e('Twilio Phone Number', 'eye-book'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="eye_book_twilio_phone_number" 
                               name="eye_book_twilio_phone_number" 
                               value="<?php echo esc_attr(get_option('eye_book_twilio_phone_number')); ?>" 
                               class="regular-text" placeholder="+1234567890">
                        <p class="description"><?php _e('Your Twilio phone number (with country code)', 'eye-book'); ?></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
        <?php
    }

    /**
     * Render advanced settings tab
     *
     * @since 1.0.0
     */
    private function render_advanced_tab() {
        ?>
        <div class="eye-book-advanced-settings">
            <h3><?php _e('Settings Management', 'eye-book'); ?></h3>
            
            <div class="eye-book-settings-actions">
                <button type="button" class="button button-secondary" id="eye-book-export-settings">
                    <?php _e('Export Settings', 'eye-book'); ?>
                </button>
                
                <button type="button" class="button button-secondary" id="eye-book-import-settings">
                    <?php _e('Import Settings', 'eye-book'); ?>
                </button>
                
                <button type="button" class="button button-secondary" id="eye-book-reset-settings">
                    <?php _e('Reset to Defaults', 'eye-book'); ?>
                </button>
            </div>
            
            <div class="eye-book-import-upload" style="display: none;">
                <h4><?php _e('Import Settings File', 'eye-book'); ?></h4>
                <input type="file" id="eye-book-settings-file" accept=".json">
                <button type="button" class="button button-primary" id="eye-book-upload-settings">
                    <?php _e('Upload and Import', 'eye-book'); ?>
                </button>
            </div>
            
            <div class="eye-book-settings-info">
                <h4><?php _e('System Information', 'eye-book'); ?></h4>
                <table class="widefat">
                    <tr>
                        <td><strong><?php _e('Plugin Version:', 'eye-book'); ?></strong></td>
                        <td><?php echo EYE_BOOK_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('WordPress Version:', 'eye-book'); ?></strong></td>
                        <td><?php echo get_bloginfo('version'); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('PHP Version:', 'eye-book'); ?></strong></td>
                        <td><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Database Version:', 'eye-book'); ?></strong></td>
                        <td><?php echo get_option('eye_book_db_version', '1.0.0'); ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Export settings
            $('#eye-book-export-settings').on('click', function() {
                $.post(ajaxurl, {
                    action: 'eye_book_export_settings',
                    nonce: '<?php echo wp_create_nonce('eye_book_export_settings'); ?>'
                }, function(response) {
                    if (response.success) {
                        // Create download link
                        var dataStr = JSON.stringify(response.data, null, 2);
                        var dataBlob = new Blob([dataStr], {type: 'application/json'});
                        var url = window.URL.createObjectURL(dataBlob);
                        var link = document.createElement('a');
                        link.href = url;
                        link.download = 'eye-book-settings.json';
                        link.click();
                        window.URL.revokeObjectURL(url);
                    } else {
                        alert('Export failed: ' + response.data);
                    }
                });
            });
            
            // Import settings
            $('#eye-book-import-settings').on('click', function() {
                $('.eye-book-import-upload').toggle();
            });
            
            // Upload and import
            $('#eye-book-upload-settings').on('click', function() {
                var file = $('#eye-book-settings-file')[0].files[0];
                if (!file) {
                    alert('Please select a file to import.');
                    return;
                }
                
                var reader = new FileReader();
                reader.onload = function(e) {
                    try {
                        var settings = JSON.parse(e.target.result);
                        
                        $.post(ajaxurl, {
                            action: 'eye_book_import_settings',
                            nonce: '<?php echo wp_create_nonce('eye_book_import_settings'); ?>',
                            settings: settings,
                            overwrite: true
                        }, function(response) {
                            if (response.success) {
                                alert('Settings imported successfully!');
                                location.reload();
                            } else {
                                alert('Import failed: ' + response.data);
                            }
                        });
                    } catch (error) {
                        alert('Invalid JSON file: ' + error.message);
                    }
                };
                reader.readAsText(file);
            });
            
            // Reset settings
            $('#eye-book-reset-settings').on('click', function() {
                if (confirm('Are you sure you want to reset all settings to defaults? This action cannot be undone.')) {
                    $.post(ajaxurl, {
                        action: 'eye_book_reset_settings',
                        nonce: '<?php echo wp_create_nonce('eye_book_reset_settings'); ?>'
                    }, function(response) {
                        if (response.success) {
                            alert('Settings reset successfully!');
                            location.reload();
                        } else {
                            alert('Reset failed: ' + response.data);
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX save settings
     *
     * @since 1.0.0
     */
    public function ajax_save_settings() {
        check_ajax_referer('eye_book_save_settings', 'nonce');
        
        if (!current_user_can('eye_book_manage_settings')) {
            wp_die(__('Insufficient permissions.', 'eye-book'));
        }
        
        $settings = $_POST['settings'] ?? array();
        $results = array();
        
        foreach ($settings as $key => $value) {
            $results[$key] = $this->settings->set($key, $value);
        }
        
        wp_send_json_success($results);
    }

    /**
     * AJAX reset settings
     *
     * @since 1.0.0
     */
    public function ajax_reset_settings() {
        check_ajax_referer('eye_book_reset_settings', 'nonce');
        
        if (!current_user_can('eye_book_manage_settings')) {
            wp_die(__('Insufficient permissions.', 'eye-book'));
        }
        
        $result = $this->settings->reset_to_defaults();
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(__('Settings reset successfully.', 'eye-book'));
    }

    /**
     * AJAX export settings
     *
     * @since 1.0.0
     */
    public function ajax_export_settings() {
        check_ajax_referer('eye_book_export_settings', 'nonce');
        
        if (!current_user_can('eye_book_manage_settings')) {
            wp_die(__('Insufficient permissions.', 'eye-book'));
        }
        
        $export_data = $this->settings->export();
        wp_send_json_success($export_data);
    }

    /**
     * AJAX import settings
     *
     * @since 1.0.0
     */
    public function ajax_import_settings() {
        check_ajax_referer('eye_book_import_settings', 'nonce');
        
        if (!current_user_can('eye_book_manage_settings')) {
            wp_die(__('Insufficient permissions.', 'eye-book'));
        }
        
        $settings = $_POST['settings'] ?? array();
        $overwrite = (bool) ($_POST['overwrite'] ?? false);
        
        if (empty($settings)) {
            wp_send_json_error(__('No settings data provided.', 'eye-book'));
        }
        
        $results = $this->settings->import($settings, $overwrite);
        
        if (is_wp_error($results)) {
            wp_send_json_error($results->get_error_message());
        }
        
        wp_send_json_success(__('Settings imported successfully.', 'eye-book'));
    }
}
