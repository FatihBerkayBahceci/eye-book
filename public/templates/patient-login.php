<?php
/**
 * Patient login template
 *
 * @package EyeBook
 * @subpackage Public/Templates
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Handle login messages
$login_message = '';
$message_type = '';

if (isset($_GET['login_error'])) {
    $login_message = urldecode($_GET['login_error']);
    $message_type = 'error';
} elseif (isset($_GET['login_success'])) {
    $login_message = urldecode($_GET['login_success']);
    $message_type = 'success';
}
?>

<div class="eye-book-patient-login">
    
    <?php if ($login_message): ?>
    <div class="eye-book-<?php echo esc_attr($message_type); ?>">
        <?php echo esc_html($login_message); ?>
    </div>
    <?php endif; ?>
    
    <div class="login-container">
        <div class="login-header">
            <h2><?php _e('Patient Portal Login', 'eye-book'); ?></h2>
            <p><?php _e('Access your appointments and health information', 'eye-book'); ?></p>
        </div>
        
        <div class="login-options">
            
            <!-- Token-based Login -->
            <div class="login-option token-login">
                <h3><?php _e('Access with Secure Link', 'eye-book'); ?></h3>
                <p><?php _e('If you received an email with a secure access link, click it to login automatically.', 'eye-book'); ?></p>
                
                <form class="token-login-form" method="post" action="">
                    <?php wp_nonce_field('eye_book_token_login', 'nonce'); ?>
                    <input type="hidden" name="eye_book_action" value="token_login">
                    
                    <div class="form-group">
                        <label for="access_token"><?php _e('Access Token:', 'eye-book'); ?></label>
                        <input type="text" id="access_token" name="access_token" 
                               placeholder="<?php _e('Enter your access token', 'eye-book'); ?>" required>
                        <div class="description">
                            <?php _e('Enter the token from your email or SMS message', 'eye-book'); ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <?php _e('Access Portal', 'eye-book'); ?>
                    </button>
                </form>
            </div>
            
            <div class="login-divider">
                <span><?php _e('OR', 'eye-book'); ?></span>
            </div>
            
            <!-- Email/Phone Login -->
            <div class="login-option credential-login">
                <h3><?php _e('Login with Your Information', 'eye-book'); ?></h3>
                <p><?php _e('Use your email and date of birth to access your portal.', 'eye-book'); ?></p>
                
                <form class="credential-login-form patient-login-form" method="post" action="">
                    <?php wp_nonce_field('eye_book_credential_login', 'nonce'); ?>
                    <input type="hidden" name="eye_book_action" value="credential_login">
                    
                    <div class="form-group">
                        <label for="login_email"><?php _e('Email Address:', 'eye-book'); ?></label>
                        <input type="email" id="login_email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="login_dob"><?php _e('Date of Birth:', 'eye-book'); ?></label>
                        <input type="date" id="login_dob" name="date_of_birth" required>
                        <div class="description">
                            <?php _e('For security, we verify your identity with your date of birth', 'eye-book'); ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="login_phone"><?php _e('Phone Number (last 4 digits):', 'eye-book'); ?></label>
                        <input type="text" id="login_phone" name="phone_last_4" 
                               placeholder="1234" maxlength="4" pattern="[0-9]{4}" required>
                        <div class="description">
                            <?php _e('Enter the last 4 digits of your phone number on file', 'eye-book'); ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <?php _e('Login to Portal', 'eye-book'); ?>
                    </button>
                </form>
            </div>
            
            <?php if ($atts['show_registration'] === 'true'): ?>
            <div class="login-divider">
                <span><?php _e('OR', 'eye-book'); ?></span>
            </div>
            
            <!-- Quick Access Request -->
            <div class="login-option access-request">
                <h3><?php _e('Request Portal Access', 'eye-book'); ?></h3>
                <p><?php _e('New patient or need help accessing your portal?', 'eye-book'); ?></p>
                
                <form class="access-request-form" method="post" action="">
                    <?php wp_nonce_field('eye_book_access_request', 'nonce'); ?>
                    <input type="hidden" name="eye_book_action" value="access_request">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="request_first_name"><?php _e('First Name:', 'eye-book'); ?></label>
                            <input type="text" id="request_first_name" name="first_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="request_last_name"><?php _e('Last Name:', 'eye-book'); ?></label>
                            <input type="text" id="request_last_name" name="last_name" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="request_email"><?php _e('Email Address:', 'eye-book'); ?></label>
                            <input type="email" id="request_email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="request_phone"><?php _e('Phone Number:', 'eye-book'); ?></label>
                            <input type="tel" id="request_phone" name="phone" class="phone-field" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="request_dob"><?php _e('Date of Birth:', 'eye-book'); ?></label>
                        <input type="date" id="request_dob" name="date_of_birth" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="request_reason"><?php _e('Reason for Access:', 'eye-book'); ?></label>
                        <select id="request_reason" name="reason" required>
                            <option value=""><?php _e('Select a reason', 'eye-book'); ?></option>
                            <option value="new_patient"><?php _e('I am a new patient', 'eye-book'); ?></option>
                            <option value="existing_patient"><?php _e('I am an existing patient', 'eye-book'); ?></option>
                            <option value="forgot_access"><?php _e('I forgot my access information', 'eye-book'); ?></option>
                            <option value="technical_issue"><?php _e('I am having technical issues', 'eye-book'); ?></option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary">
                        <?php _e('Request Access', 'eye-book'); ?>
                    </button>
                    
                    <div class="access-request-note">
                        <small>
                            <?php _e('We will verify your information and send you access details within 24 hours.', 'eye-book'); ?>
                        </small>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Help Section -->
        <div class="login-help">
            <h4><?php _e('Need Help?', 'eye-book'); ?></h4>
            
            <div class="help-options">
                <div class="help-item">
                    <h5><?php _e('Trouble Logging In?', 'eye-book'); ?></h5>
                    <p><?php _e('Make sure you are using the email address and date of birth exactly as provided to our office.', 'eye-book'); ?></p>
                </div>
                
                <div class="help-item">
                    <h5><?php _e('Lost Your Access Token?', 'eye-book'); ?></h5>
                    <p><?php _e('Request a new access link by using the "Request Portal Access" form above.', 'eye-book'); ?></p>
                </div>
                
                <div class="help-item">
                    <h5><?php _e('Technical Support', 'eye-book'); ?></h5>
                    <p>
                        <?php _e('Call us at', 'eye-book'); ?> 
                        <a href="tel:<?php echo esc_attr(get_option('eye_book_clinic_phone', '(555) 123-4567')); ?>">
                            <?php echo esc_html(get_option('eye_book_clinic_phone', '(555) 123-4567')); ?>
                        </a>
                        <?php _e('for assistance.', 'eye-book'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Security Notice -->
        <div class="security-notice">
            <div class="security-icon">
                <span class="dashicons dashicons-lock"></span>
            </div>
            <div class="security-content">
                <h5><?php _e('Your Privacy is Protected', 'eye-book'); ?></h5>
                <p>
                    <?php _e('This portal is HIPAA compliant and uses advanced security measures to protect your health information. All data is encrypted and access is logged for your security.', 'eye-book'); ?>
                </p>
            </div>
        </div>
        
    </div>
</div>

<style>
.eye-book-patient-login {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.login-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.login-header {
    background: linear-gradient(135deg, #3498db, #2980b9);
    color: white;
    padding: 30px;
    text-align: center;
}

.login-header h2 {
    margin: 0 0 10px 0;
    font-size: 28px;
    font-weight: 600;
}

.login-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 16px;
}

.login-options {
    padding: 30px;
}

.login-option {
    margin-bottom: 30px;
    padding: 25px;
    border: 2px solid #f8f9fa;
    border-radius: 8px;
    background: #fafbfc;
}

.login-option:last-child {
    margin-bottom: 0;
}

.login-option h3 {
    margin: 0 0 10px 0;
    color: #2c3e50;
    font-size: 20px;
}

.login-option p {
    margin: 0 0 20px 0;
    color: #7f8c8d;
}

.login-divider {
    text-align: center;
    margin: 20px 0;
    position: relative;
}

.login-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e9ecef;
}

.login-divider span {
    background: white;
    padding: 0 15px;
    color: #7f8c8d;
    font-weight: 600;
    position: relative;
}

.access-request-note {
    margin-top: 15px;
    padding: 10px;
    background: #e3f2fd;
    border-radius: 4px;
    text-align: center;
}

.login-help {
    padding: 30px;
    background: #f8f9fa;
    border-top: 1px solid #e9ecef;
}

.login-help h4 {
    margin: 0 0 20px 0;
    color: #2c3e50;
}

.help-options {
    display: grid;
    gap: 20px;
}

.help-item h5 {
    margin: 0 0 5px 0;
    color: #495057;
    font-size: 16px;
}

.help-item p {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
}

.security-notice {
    padding: 20px 30px;
    background: #e8f5e8;
    border-top: 1px solid #c3e6cb;
    display: flex;
    align-items: flex-start;
    gap: 15px;
}

.security-icon .dashicons {
    font-size: 24px;
    color: #28a745;
}

.security-content h5 {
    margin: 0 0 5px 0;
    color: #155724;
    font-size: 16px;
}

.security-content p {
    margin: 0;
    color: #155724;
    font-size: 14px;
    line-height: 1.5;
}

@media (max-width: 768px) {
    .eye-book-patient-login {
        padding: 10px;
    }
    
    .login-container {
        border-radius: 0;
    }
    
    .login-header,
    .login-options,
    .login-help {
        padding: 20px;
    }
    
    .login-option {
        padding: 20px;
    }
    
    .security-notice {
        padding: 15px 20px;
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    
    // Handle credential login form
    $('.credential-login-form').on('submit', function(e) {
        var email = $('#login_email').val();
        var dob = $('#login_dob').val();
        var phoneLastFour = $('#login_phone').val();
        
        if (!email || !dob || !phoneLastFour) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
        
        if (phoneLastFour.length !== 4 || !/^\d{4}$/.test(phoneLastFour)) {
            e.preventDefault();
            alert('Please enter exactly 4 digits for your phone number.');
            return false;
        }
    });
    
    // Handle access request form
    $('.access-request-form').on('submit', function(e) {
        var $submitButton = $(this).find('[type="submit"]');
        var originalText = $submitButton.text();
        
        $submitButton.prop('disabled', true).text('Submitting...');
        
        // Form will submit normally, but we provide user feedback
        setTimeout(function() {
            $submitButton.prop('disabled', false).text(originalText);
        }, 3000);
    });
    
    // Auto-format phone number in access request
    $('#request_phone').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        var formattedValue = '';
        
        if (value.length >= 6) {
            formattedValue = '(' + value.substring(0, 3) + ') ' + value.substring(3, 6) + '-' + value.substring(6, 10);
        } else if (value.length >= 3) {
            formattedValue = '(' + value.substring(0, 3) + ') ' + value.substring(3);
        } else {
            formattedValue = value;
        }
        
        $(this).val(formattedValue);
    });
    
    // Restrict phone last 4 to numbers only
    $('#login_phone').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        $(this).val(value.substring(0, 4));
    });
    
});
</script>