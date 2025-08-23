<?php
/**
 * Payment Settings View
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
    <h1 class="wp-heading-inline"><?php _e('Payment Settings', 'eye-book'); ?></h1>
    <hr class="wp-header-end">

    <div class="eye-book-content">
        <div class="payment-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#gateway-settings" class="nav-tab nav-tab-active"><?php _e('Payment Gateways', 'eye-book'); ?></a>
                <a href="#payment-types" class="nav-tab"><?php _e('Payment Types', 'eye-book'); ?></a>
                <a href="#insurance-settings" class="nav-tab"><?php _e('Insurance Verification', 'eye-book'); ?></a>
                <a href="#financial-reporting" class="nav-tab"><?php _e('Financial Reporting', 'eye-book'); ?></a>
            </nav>

            <!-- Gateway Settings Tab -->
            <div id="gateway-settings" class="tab-content active">
                <form method="post" action="options.php" id="payment-gateway-form">
                    <?php settings_fields('eye_book_payment_settings'); ?>

                    <div class="gateway-selection">
                        <h3><?php _e('Select Payment Gateway', 'eye-book'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Active Gateway', 'eye-book'); ?></th>
                                <td>
                                    <select name="eye_book_payment_gateway" id="payment-gateway-select">
                                        <option value=""><?php _e('Select Gateway', 'eye-book'); ?></option>
                                        <option value="stripe" <?php selected(get_option('eye_book_payment_gateway'), 'stripe'); ?>>Stripe</option>
                                        <option value="square" <?php selected(get_option('eye_book_payment_gateway'), 'square'); ?>>Square</option>
                                        <option value="paypal" <?php selected(get_option('eye_book_payment_gateway'), 'paypal'); ?>>PayPal</option>
                                        <option value="authorize_net" <?php selected(get_option('eye_book_payment_gateway'), 'authorize_net'); ?>>Authorize.Net</option>
                                    </select>
                                    <p class="description"><?php _e('Select your preferred payment gateway', 'eye-book'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Test Mode', 'eye-book'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="eye_book_payment_test_mode" value="1" <?php checked(get_option('eye_book_payment_test_mode'), 1); ?>>
                                        <?php _e('Enable test/sandbox mode', 'eye-book'); ?>
                                    </label>
                                    <p class="description"><?php _e('When enabled, all payments will be processed in test mode', 'eye-book'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Currency', 'eye-book'); ?></th>
                                <td>
                                    <select name="eye_book_currency">
                                        <option value="usd" <?php selected(get_option('eye_book_currency', 'usd'), 'usd'); ?>>USD - US Dollar</option>
                                        <option value="cad" <?php selected(get_option('eye_book_currency'), 'cad'); ?>>CAD - Canadian Dollar</option>
                                        <option value="eur" <?php selected(get_option('eye_book_currency'), 'eur'); ?>>EUR - Euro</option>
                                        <option value="gbp" <?php selected(get_option('eye_book_currency'), 'gbp'); ?>>GBP - British Pound</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Stripe Settings -->
                    <div id="stripe-settings" class="gateway-settings-panel" style="display: none;">
                        <h3><span class="dashicons dashicons-admin-generic"></span> Stripe Configuration</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Publishable Key', 'eye-book'); ?></th>
                                <td>
                                    <input type="text" name="eye_book_stripe_publishable_key" value="<?php echo esc_attr(get_option('eye_book_stripe_publishable_key')); ?>" class="regular-text">
                                    <p class="description"><?php _e('Your Stripe publishable key (starts with pk_)', 'eye-book'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Secret Key', 'eye-book'); ?></th>
                                <td>
                                    <input type="password" name="eye_book_stripe_secret_key" value="<?php echo esc_attr(get_option('eye_book_stripe_secret_key')); ?>" class="regular-text">
                                    <p class="description"><?php _e('Your Stripe secret key (starts with sk_)', 'eye-book'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Webhook Endpoint', 'eye-book'); ?></th>
                                <td>
                                    <code><?php echo admin_url('admin-ajax.php?action=eye_book_stripe_webhook'); ?></code>
                                    <p class="description"><?php _e('Add this URL to your Stripe webhook endpoints', 'eye-book'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Square Settings -->
                    <div id="square-settings" class="gateway-settings-panel" style="display: none;">
                        <h3><span class="dashicons dashicons-admin-generic"></span> Square Configuration</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Application ID', 'eye-book'); ?></th>
                                <td>
                                    <input type="text" name="eye_book_square_application_id" value="<?php echo esc_attr(get_option('eye_book_square_application_id')); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Access Token', 'eye-book'); ?></th>
                                <td>
                                    <input type="password" name="eye_book_square_access_token" value="<?php echo esc_attr(get_option('eye_book_square_access_token')); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Environment', 'eye-book'); ?></th>
                                <td>
                                    <select name="eye_book_square_environment">
                                        <option value="sandbox" <?php selected(get_option('eye_book_square_environment', 'sandbox'), 'sandbox'); ?>>Sandbox</option>
                                        <option value="production" <?php selected(get_option('eye_book_square_environment'), 'production'); ?>>Production</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- PayPal Settings -->
                    <div id="paypal-settings" class="gateway-settings-panel" style="display: none;">
                        <h3><span class="dashicons dashicons-admin-generic"></span> PayPal Configuration</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Client ID', 'eye-book'); ?></th>
                                <td>
                                    <input type="text" name="eye_book_paypal_client_id" value="<?php echo esc_attr(get_option('eye_book_paypal_client_id')); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Client Secret', 'eye-book'); ?></th>
                                <td>
                                    <input type="password" name="eye_book_paypal_client_secret" value="<?php echo esc_attr(get_option('eye_book_paypal_client_secret')); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Environment', 'eye-book'); ?></th>
                                <td>
                                    <select name="eye_book_paypal_environment">
                                        <option value="sandbox" <?php selected(get_option('eye_book_paypal_environment', 'sandbox'), 'sandbox'); ?>>Sandbox</option>
                                        <option value="production" <?php selected(get_option('eye_book_paypal_environment'), 'production'); ?>>Live</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Authorize.Net Settings -->
                    <div id="authorize-net-settings" class="gateway-settings-panel" style="display: none;">
                        <h3><span class="dashicons dashicons-admin-generic"></span> Authorize.Net Configuration</h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('API Login ID', 'eye-book'); ?></th>
                                <td>
                                    <input type="text" name="eye_book_authnet_api_login" value="<?php echo esc_attr(get_option('eye_book_authnet_api_login')); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Transaction Key', 'eye-book'); ?></th>
                                <td>
                                    <input type="password" name="eye_book_authnet_transaction_key" value="<?php echo esc_attr(get_option('eye_book_authnet_transaction_key')); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Environment', 'eye-book'); ?></th>
                                <td>
                                    <select name="eye_book_authnet_environment">
                                        <option value="sandbox" <?php selected(get_option('eye_book_authnet_environment', 'sandbox'), 'sandbox'); ?>>Sandbox</option>
                                        <option value="production" <?php selected(get_option('eye_book_authnet_environment'), 'production'); ?>>Production</option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <?php submit_button(__('Save Payment Settings', 'eye-book')); ?>
                </form>
            </div>

            <!-- Payment Types Tab -->
            <div id="payment-types" class="tab-content">
                <form method="post" action="options.php">
                    <?php settings_fields('eye_book_payment_types'); ?>
                    
                    <h3><?php _e('Payment Type Configuration', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Accept Copays', 'eye-book'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="eye_book_accept_copays" value="1" <?php checked(get_option('eye_book_accept_copays', 1), 1); ?>>
                                    <?php _e('Enable copay collection', 'eye-book'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Accept Deductibles', 'eye-book'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="eye_book_accept_deductibles" value="1" <?php checked(get_option('eye_book_accept_deductibles', 1), 1); ?>>
                                    <?php _e('Enable deductible collection', 'eye-book'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Accept Self-Pay', 'eye-book'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="eye_book_accept_self_pay" value="1" <?php checked(get_option('eye_book_accept_self_pay', 1), 1); ?>>
                                    <?php _e('Enable self-pay patients', 'eye-book'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Default Copay Amount', 'eye-book'); ?></th>
                            <td>
                                <input type="number" name="eye_book_default_copay" value="<?php echo esc_attr(get_option('eye_book_default_copay', '25')); ?>" step="0.01" min="0" class="small-text">
                                <p class="description"><?php _e('Default copay amount for new appointment types', 'eye-book'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Payment Due', 'eye-book'); ?></th>
                            <td>
                                <select name="eye_book_payment_due">
                                    <option value="at_booking" <?php selected(get_option('eye_book_payment_due', 'at_booking'), 'at_booking'); ?>><?php _e('At time of booking', 'eye-book'); ?></option>
                                    <option value="at_checkin" <?php selected(get_option('eye_book_payment_due'), 'at_checkin'); ?>><?php _e('At check-in', 'eye-book'); ?></option>
                                    <option value="after_service" <?php selected(get_option('eye_book_payment_due'), 'after_service'); ?>><?php _e('After service', 'eye-book'); ?></option>
                                </select>
                            </td>
                        </tr>
                    </table>

                    <h3><?php _e('Payment Policies', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Cancellation Policy', 'eye-book'); ?></th>
                            <td>
                                <textarea name="eye_book_cancellation_policy" rows="4" class="large-text"><?php echo esc_textarea(get_option('eye_book_cancellation_policy', 'Cancellations must be made at least 24 hours in advance to avoid cancellation fees.')); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Refund Policy', 'eye-book'); ?></th>
                            <td>
                                <textarea name="eye_book_refund_policy" rows="4" class="large-text"><?php echo esc_textarea(get_option('eye_book_refund_policy', 'Refunds will be processed within 5-7 business days to the original payment method.')); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Late Payment Fee', 'eye-book'); ?></th>
                            <td>
                                <input type="number" name="eye_book_late_payment_fee" value="<?php echo esc_attr(get_option('eye_book_late_payment_fee', '0')); ?>" step="0.01" min="0" class="small-text">
                                <p class="description"><?php _e('Fee charged for late payments (0 to disable)', 'eye-book'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(__('Save Payment Types', 'eye-book')); ?>
                </form>
            </div>

            <!-- Insurance Settings Tab -->
            <div id="insurance-settings" class="tab-content">
                <form method="post" action="options.php">
                    <?php settings_fields('eye_book_insurance_settings'); ?>
                    
                    <h3><?php _e('Insurance Verification Service', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Verification Service', 'eye-book'); ?></th>
                            <td>
                                <select name="eye_book_insurance_verification_service" id="insurance-service-select">
                                    <option value=""><?php _e('Select Service', 'eye-book'); ?></option>
                                    <option value="availity" <?php selected(get_option('eye_book_insurance_verification_service'), 'availity'); ?>>Availity</option>
                                    <option value="change_healthcare" <?php selected(get_option('eye_book_insurance_verification_service'), 'change_healthcare'); ?>>Change Healthcare</option>
                                    <option value="manual" <?php selected(get_option('eye_book_insurance_verification_service'), 'manual'); ?>><?php _e('Manual Verification Only', 'eye-book'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Auto-Verify Insurance', 'eye-book'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="eye_book_auto_verify_insurance" value="1" <?php checked(get_option('eye_book_auto_verify_insurance'), 1); ?>>
                                    <?php _e('Automatically verify insurance when new patients register', 'eye-book'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <!-- Availity Settings -->
                    <div id="availity-settings" class="insurance-settings-panel" style="display: none;">
                        <h4>Availity Configuration</h4>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Username', 'eye-book'); ?></th>
                                <td>
                                    <input type="text" name="eye_book_availity_username" value="<?php echo esc_attr(get_option('eye_book_availity_username')); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Password', 'eye-book'); ?></th>
                                <td>
                                    <input type="password" name="eye_book_availity_password" value="<?php echo esc_attr(get_option('eye_book_availity_password')); ?>" class="regular-text">
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Change Healthcare Settings -->
                    <div id="change-healthcare-settings" class="insurance-settings-panel" style="display: none;">
                        <h4>Change Healthcare Configuration</h4>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('API Key', 'eye-book'); ?></th>
                                <td>
                                    <input type="password" name="eye_book_change_healthcare_api_key" value="<?php echo esc_attr(get_option('eye_book_change_healthcare_api_key')); ?>" class="regular-text">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Client ID', 'eye-book'); ?></th>
                                <td>
                                    <input type="text" name="eye_book_change_healthcare_client_id" value="<?php echo esc_attr(get_option('eye_book_change_healthcare_client_id')); ?>" class="regular-text">
                                </td>
                            </tr>
                        </table>
                    </div>

                    <h3><?php _e('Insurance Policies', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Require Insurance Verification', 'eye-book'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="eye_book_require_insurance_verification" value="1" <?php checked(get_option('eye_book_require_insurance_verification'), 1); ?>>
                                    <?php _e('Require insurance verification before appointments', 'eye-book'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Insurance Card Upload', 'eye-book'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="eye_book_require_insurance_card" value="1" <?php checked(get_option('eye_book_require_insurance_card'), 1); ?>>
                                    <?php _e('Require patients to upload insurance card images', 'eye-book'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(__('Save Insurance Settings', 'eye-book')); ?>
                </form>
            </div>

            <!-- Financial Reporting Tab -->
            <div id="financial-reporting" class="tab-content">
                <form method="post" action="options.php">
                    <?php settings_fields('eye_book_financial_settings'); ?>
                    
                    <h3><?php _e('Financial Reporting Configuration', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Enable Financial Reports', 'eye-book'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="eye_book_enable_financial_reports" value="1" <?php checked(get_option('eye_book_enable_financial_reports', 1), 1); ?>>
                                    <?php _e('Enable detailed financial reporting', 'eye-book'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Daily Revenue Reports', 'eye-book'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="eye_book_daily_revenue_reports" value="1" <?php checked(get_option('eye_book_daily_revenue_reports'), 1); ?>>
                                    <?php _e('Generate daily revenue summary reports', 'eye-book'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Export Format', 'eye-book'); ?></th>
                            <td>
                                <select name="eye_book_financial_export_format">
                                    <option value="csv" <?php selected(get_option('eye_book_financial_export_format', 'csv'), 'csv'); ?>>CSV</option>
                                    <option value="excel" <?php selected(get_option('eye_book_financial_export_format'), 'excel'); ?>>Excel</option>
                                    <option value="pdf" <?php selected(get_option('eye_book_financial_export_format'), 'pdf'); ?>>PDF</option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('QuickBooks Integration', 'eye-book'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" name="eye_book_quickbooks_integration" value="1" <?php checked(get_option('eye_book_quickbooks_integration'), 1); ?>>
                                    <?php _e('Enable QuickBooks data export', 'eye-book'); ?>
                                </label>
                                <p class="description"><?php _e('Export payment data for import into QuickBooks', 'eye-book'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <h3><?php _e('Tax Settings', 'eye-book'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Tax Rate (%)', 'eye-book'); ?></th>
                            <td>
                                <input type="number" name="eye_book_tax_rate" value="<?php echo esc_attr(get_option('eye_book_tax_rate', '0')); ?>" step="0.01" min="0" max="100" class="small-text">
                                <p class="description"><?php _e('Enter 0 if medical services are tax-exempt in your location', 'eye-book'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Tax ID Number', 'eye-book'); ?></th>
                            <td>
                                <input type="text" name="eye_book_tax_id" value="<?php echo esc_attr(get_option('eye_book_tax_id')); ?>" class="regular-text">
                                <p class="description"><?php _e('Your business tax ID number for reporting', 'eye-book'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button(__('Save Financial Settings', 'eye-book')); ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });

    // Gateway settings panels
    $('#payment-gateway-select').change(function() {
        $('.gateway-settings-panel').hide();
        var selected = $(this).val();
        if (selected) {
            $('#' + selected + '-settings').show();
        }
    });

    // Insurance service panels
    $('#insurance-service-select').change(function() {
        $('.insurance-settings-panel').hide();
        var selected = $(this).val();
        if (selected && selected !== 'manual') {
            $('#' + selected + '-settings').show();
        }
    });

    // Initialize panels
    $('#payment-gateway-select').trigger('change');
    $('#insurance-service-select').trigger('change');
});
</script>

<style>
.payment-settings-tabs .nav-tab-wrapper {
    margin-bottom: 20px;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.gateway-settings-panel, .insurance-settings-panel {
    background: #f9f9f9;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin: 20px 0;
}

.gateway-settings-panel h3, .insurance-settings-panel h4 {
    margin-top: 0;
    color: #23282d;
}

.gateway-settings-panel .dashicons {
    margin-right: 8px;
    color: #666;
}

.form-table th {
    width: 200px;
}

.description {
    font-style: italic;
    color: #666;
}

code {
    background: #f1f1f1;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: monospace;
}
</style>