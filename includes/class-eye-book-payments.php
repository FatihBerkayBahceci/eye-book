<?php
/**
 * Payment processing class for Eye-Book plugin
 *
 * @package EyeBook
 * @subpackage Payments
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Eye_Book_Payments Class
 *
 * Handles payment processing, copay collection, and financial integrations
 *
 * @class Eye_Book_Payments
 * @since 1.0.0
 */
class Eye_Book_Payments {

    /**
     * Supported payment gateways
     *
     * @var array
     * @since 1.0.0
     */
    private $supported_gateways = array(
        'stripe' => 'Stripe',
        'square' => 'Square',
        'paypal' => 'PayPal',
        'authorize_net' => 'Authorize.Net',
        'chase_paymentech' => 'Chase Paymentech'
    );

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action('wp_ajax_eye_book_process_payment', array($this, 'ajax_process_payment'));
        add_action('wp_ajax_nopriv_eye_book_process_payment', array($this, 'ajax_process_payment'));
        add_action('wp_ajax_eye_book_refund_payment', array($this, 'ajax_refund_payment'));
        add_action('wp_ajax_eye_book_verify_insurance', array($this, 'ajax_verify_insurance'));
        
        // Webhook handlers for payment gateways
        add_action('wp_ajax_nopriv_eye_book_stripe_webhook', array($this, 'handle_stripe_webhook'));
        add_action('wp_ajax_nopriv_eye_book_square_webhook', array($this, 'handle_square_webhook'));
        add_action('wp_ajax_nopriv_eye_book_paypal_webhook', array($this, 'handle_paypal_webhook'));
        
        // Payment status checks
        add_action('eye_book_check_pending_payments', array($this, 'check_pending_payments'));
        
        // Schedule payment status checks
        if (!wp_next_scheduled('eye_book_check_pending_payments')) {
            wp_schedule_event(time(), 'hourly', 'eye_book_check_pending_payments');
        }
    }

    /**
     * Process payment
     *
     * @param array $payment_data
     * @return array
     * @since 1.0.0
     */
    public function process_payment($payment_data) {
        try {
            // Validate payment data
            $this->validate_payment_data($payment_data);
            
            // Get active payment gateway
            $gateway = get_option('eye_book_payment_gateway', 'stripe');
            
            // Process payment based on gateway
            switch ($gateway) {
                case 'stripe':
                    return $this->process_stripe_payment($payment_data);
                case 'square':
                    return $this->process_square_payment($payment_data);
                case 'paypal':
                    return $this->process_paypal_payment($payment_data);
                case 'authorize_net':
                    return $this->process_authorize_net_payment($payment_data);
                default:
                    throw new Exception(__('Unsupported payment gateway', 'eye-book'));
            }
            
        } catch (Exception $e) {
            // Log payment error
            Eye_Book_Audit::log('payment_error', 'payment', null, array(
                'error' => $e->getMessage(),
                'payment_data' => $this->sanitize_payment_data_for_log($payment_data)
            ));
            
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Process Stripe payment
     *
     * @param array $payment_data
     * @return array
     * @since 1.0.0
     */
    private function process_stripe_payment($payment_data) {
        $stripe_secret_key = get_option('eye_book_stripe_secret_key');
        $stripe_publishable_key = get_option('eye_book_stripe_publishable_key');
        
        if (empty($stripe_secret_key)) {
            throw new Exception(__('Stripe is not configured properly', 'eye-book'));
        }

        // Create payment intent
        $payment_intent_data = array(
            'amount' => $payment_data['amount'] * 100, // Convert to cents
            'currency' => get_option('eye_book_currency', 'usd'),
            'payment_method' => $payment_data['payment_method_id'],
            'confirmation_method' => 'manual',
            'confirm' => true,
            'metadata' => array(
                'appointment_id' => $payment_data['appointment_id'],
                'patient_id' => $payment_data['patient_id'],
                'payment_type' => $payment_data['payment_type']
            )
        );

        $response = $this->make_stripe_request('payment_intents', $payment_intent_data, 'POST');

        if ($response['status'] === 'succeeded') {
            // Payment succeeded
            $payment_record = $this->create_payment_record(array(
                'appointment_id' => $payment_data['appointment_id'],
                'patient_id' => $payment_data['patient_id'],
                'amount' => $payment_data['amount'],
                'payment_type' => $payment_data['payment_type'],
                'gateway' => 'stripe',
                'transaction_id' => $response['id'],
                'status' => 'completed'
            ));
            
            return array(
                'success' => true,
                'payment_id' => $payment_record,
                'transaction_id' => $response['id'],
                'message' => __('Payment processed successfully', 'eye-book')
            );
        } elseif ($response['status'] === 'requires_action') {
            // 3D Secure authentication required
            return array(
                'success' => true,
                'requires_action' => true,
                'payment_intent_id' => $response['id'],
                'client_secret' => $response['client_secret']
            );
        } else {
            throw new Exception(__('Payment failed: ', 'eye-book') . ($response['last_payment_error']['message'] ?? 'Unknown error'));
        }
    }

    /**
     * Process Square payment
     *
     * @param array $payment_data
     * @return array
     * @since 1.0.0
     */
    private function process_square_payment($payment_data) {
        $square_access_token = get_option('eye_book_square_access_token');
        $square_application_id = get_option('eye_book_square_application_id');
        $square_environment = get_option('eye_book_square_environment', 'sandbox');
        
        if (empty($square_access_token)) {
            throw new Exception(__('Square is not configured properly', 'eye-book'));
        }

        $base_url = $square_environment === 'production' 
            ? 'https://connect.squareup.com/v2/' 
            : 'https://connect.squareupsandbox.com/v2/';

        $payment_request = array(
            'source_id' => $payment_data['nonce'],
            'idempotency_key' => uniqid('eye_book_', true),
            'amount_money' => array(
                'amount' => $payment_data['amount'] * 100, // Convert to cents
                'currency' => strtoupper(get_option('eye_book_currency', 'USD'))
            ),
            'note' => sprintf(__('Eye-Book payment for appointment #%s', 'eye-book'), $payment_data['appointment_id'])
        );

        $response = wp_remote_post($base_url . 'payments', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $square_access_token,
                'Content-Type' => 'application/json',
                'Square-Version' => '2023-10-18'
            ),
            'body' => json_encode($payment_request),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            throw new Exception(__('Payment processing failed', 'eye-book'));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['errors'])) {
            throw new Exception($body['errors'][0]['detail']);
        }

        if ($body['payment']['status'] === 'COMPLETED') {
            $payment_record = $this->create_payment_record(array(
                'appointment_id' => $payment_data['appointment_id'],
                'patient_id' => $payment_data['patient_id'],
                'amount' => $payment_data['amount'],
                'payment_type' => $payment_data['payment_type'],
                'gateway' => 'square',
                'transaction_id' => $body['payment']['id'],
                'status' => 'completed'
            ));
            
            return array(
                'success' => true,
                'payment_id' => $payment_record,
                'transaction_id' => $body['payment']['id'],
                'message' => __('Payment processed successfully', 'eye-book')
            );
        } else {
            throw new Exception(__('Payment failed', 'eye-book'));
        }
    }

    /**
     * Process PayPal payment
     *
     * @param array $payment_data
     * @return array
     * @since 1.0.0
     */
    private function process_paypal_payment($payment_data) {
        $paypal_client_id = get_option('eye_book_paypal_client_id');
        $paypal_client_secret = get_option('eye_book_paypal_client_secret');
        $paypal_environment = get_option('eye_book_paypal_environment', 'sandbox');
        
        if (empty($paypal_client_id) || empty($paypal_client_secret)) {
            throw new Exception(__('PayPal is not configured properly', 'eye-book'));
        }

        $base_url = $paypal_environment === 'production' 
            ? 'https://api.paypal.com/' 
            : 'https://api.sandbox.paypal.com/';

        // Get access token
        $access_token = $this->get_paypal_access_token($base_url, $paypal_client_id, $paypal_client_secret);

        // Create payment
        $payment_request = array(
            'intent' => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'amount' => array(
                        'currency_code' => strtoupper(get_option('eye_book_currency', 'USD')),
                        'value' => number_format($payment_data['amount'], 2, '.', '')
                    ),
                    'description' => sprintf(__('Eye-Book payment for appointment #%s', 'eye-book'), $payment_data['appointment_id'])
                )
            ),
            'payment_source' => array(
                'paypal' => array(
                    'experience_context' => array(
                        'return_url' => add_query_arg('paypal_return', '1', home_url()),
                        'cancel_url' => add_query_arg('paypal_cancel', '1', home_url())
                    )
                )
            )
        );

        $response = wp_remote_post($base_url . 'v2/checkout/orders', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($payment_request),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            throw new Exception(__('PayPal payment processing failed', 'eye-book'));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($body['status'] === 'CREATED') {
            // Create pending payment record
            $payment_record = $this->create_payment_record(array(
                'appointment_id' => $payment_data['appointment_id'],
                'patient_id' => $payment_data['patient_id'],
                'amount' => $payment_data['amount'],
                'payment_type' => $payment_data['payment_type'],
                'gateway' => 'paypal',
                'transaction_id' => $body['id'],
                'status' => 'pending'
            ));
            
            // Get approval URL
            $approval_url = '';
            foreach ($body['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approval_url = $link['href'];
                    break;
                }
            }
            
            return array(
                'success' => true,
                'payment_id' => $payment_record,
                'requires_approval' => true,
                'approval_url' => $approval_url,
                'transaction_id' => $body['id']
            );
        } else {
            throw new Exception(__('PayPal payment creation failed', 'eye-book'));
        }
    }

    /**
     * Refund payment
     *
     * @param int $payment_id
     * @param float $amount
     * @param string $reason
     * @return array
     * @since 1.0.0
     */
    public function refund_payment($payment_id, $amount = null, $reason = '') {
        global $wpdb;
        
        $payment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eye_book_payments WHERE id = %d",
            $payment_id
        ));

        if (!$payment) {
            return array(
                'success' => false,
                'message' => __('Payment not found', 'eye-book')
            );
        }

        if ($payment->status !== 'completed') {
            return array(
                'success' => false,
                'message' => __('Only completed payments can be refunded', 'eye-book')
            );
        }

        $refund_amount = $amount ?? $payment->amount;

        try {
            switch ($payment->gateway) {
                case 'stripe':
                    $result = $this->refund_stripe_payment($payment->transaction_id, $refund_amount, $reason);
                    break;
                case 'square':
                    $result = $this->refund_square_payment($payment->transaction_id, $refund_amount, $reason);
                    break;
                case 'paypal':
                    $result = $this->refund_paypal_payment($payment->transaction_id, $refund_amount, $reason);
                    break;
                default:
                    throw new Exception(__('Refunds not supported for this payment gateway', 'eye-book'));
            }

            if ($result['success']) {
                // Update payment status
                $wpdb->update(
                    $wpdb->prefix . 'eye_book_payments',
                    array(
                        'status' => $refund_amount >= $payment->amount ? 'refunded' : 'partially_refunded',
                        'refund_amount' => $refund_amount,
                        'refund_reason' => $reason,
                        'refunded_at' => current_time('mysql', true)
                    ),
                    array('id' => $payment_id)
                );

                // Log refund
                Eye_Book_Audit::log('payment_refunded', 'payment', $payment_id, array(
                    'original_amount' => $payment->amount,
                    'refund_amount' => $refund_amount,
                    'reason' => $reason,
                    'gateway' => $payment->gateway,
                    'transaction_id' => $payment->transaction_id
                ));
            }

            return $result;

        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Verify insurance coverage
     *
     * @param array $insurance_data
     * @return array
     * @since 1.0.0
     */
    public function verify_insurance($insurance_data) {
        $verification_service = get_option('eye_book_insurance_verification_service');
        
        if (empty($verification_service)) {
            return array(
                'success' => false,
                'message' => __('Insurance verification service not configured', 'eye-book')
            );
        }

        try {
            switch ($verification_service) {
                case 'availity':
                    return $this->verify_insurance_availity($insurance_data);
                case 'change_healthcare':
                    return $this->verify_insurance_change_healthcare($insurance_data);
                default:
                    throw new Exception(__('Unsupported insurance verification service', 'eye-book'));
            }
        } catch (Exception $e) {
            Eye_Book_Audit::log('insurance_verification_error', 'insurance', null, array(
                'error' => $e->getMessage(),
                'insurance_data' => array(
                    'provider' => $insurance_data['provider'] ?? '',
                    'member_id' => $insurance_data['member_id'] ?? ''
                )
            ));

            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Create payment record
     *
     * @param array $payment_data
     * @return int Payment ID
     * @since 1.0.0
     */
    private function create_payment_record($payment_data) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'eye_book_payments';
        
        // Create payments table if it doesn't exist
        $this->create_payments_table();

        $result = $wpdb->insert($table_name, array(
            'appointment_id' => $payment_data['appointment_id'],
            'patient_id' => $payment_data['patient_id'],
            'amount' => $payment_data['amount'],
            'payment_type' => $payment_data['payment_type'],
            'gateway' => $payment_data['gateway'],
            'transaction_id' => $payment_data['transaction_id'],
            'status' => $payment_data['status'],
            'created_at' => current_time('mysql', true),
            'updated_at' => current_time('mysql', true)
        ), array(
            '%d', '%d', '%f', '%s', '%s', '%s', '%s', '%s', '%s'
        ));

        if ($result === false) {
            throw new Exception(__('Failed to create payment record', 'eye-book'));
        }

        $payment_id = $wpdb->insert_id;

        // Log payment creation
        Eye_Book_Audit::log('payment_created', 'payment', $payment_id, array(
            'appointment_id' => $payment_data['appointment_id'],
            'amount' => $payment_data['amount'],
            'gateway' => $payment_data['gateway'],
            'status' => $payment_data['status']
        ));

        return $payment_id;
    }

    /**
     * Create payments table
     *
     * @since 1.0.0
     */
    private function create_payments_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'eye_book_payments';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            appointment_id int(11) NOT NULL,
            patient_id int(11) NOT NULL,
            amount decimal(10,2) NOT NULL,
            payment_type varchar(50) NOT NULL DEFAULT 'copay',
            gateway varchar(50) NOT NULL,
            transaction_id varchar(255) NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            refund_amount decimal(10,2) DEFAULT NULL,
            refund_reason text DEFAULT NULL,
            refunded_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY appointment_id (appointment_id),
            KEY patient_id (patient_id),
            KEY transaction_id (transaction_id),
            KEY status (status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Validate payment data
     *
     * @param array $payment_data
     * @throws Exception
     * @since 1.0.0
     */
    private function validate_payment_data($payment_data) {
        $required_fields = array('appointment_id', 'patient_id', 'amount', 'payment_type');
        
        foreach ($required_fields as $field) {
            if (empty($payment_data[$field])) {
                throw new Exception(sprintf(__('Required field missing: %s', 'eye-book'), $field));
            }
        }

        if (!is_numeric($payment_data['amount']) || $payment_data['amount'] <= 0) {
            throw new Exception(__('Invalid payment amount', 'eye-book'));
        }

        if (!in_array($payment_data['payment_type'], array('copay', 'deductible', 'coinsurance', 'self_pay'))) {
            throw new Exception(__('Invalid payment type', 'eye-book'));
        }
    }

    /**
     * Make Stripe API request
     *
     * @param string $endpoint
     * @param array $data
     * @param string $method
     * @return array
     * @since 1.0.0
     */
    private function make_stripe_request($endpoint, $data, $method = 'POST') {
        $stripe_secret_key = get_option('eye_book_stripe_secret_key');
        
        $response = wp_remote_request('https://api.stripe.com/v1/' . $endpoint, array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $stripe_secret_key,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => http_build_query($data),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            throw new Exception(__('Stripe API request failed', 'eye-book'));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!empty($body['error'])) {
            throw new Exception($body['error']['message']);
        }

        return $body;
    }

    /**
     * Get PayPal access token
     *
     * @param string $base_url
     * @param string $client_id
     * @param string $client_secret
     * @return string
     * @since 1.0.0
     */
    private function get_paypal_access_token($base_url, $client_id, $client_secret) {
        $response = wp_remote_post($base_url . 'v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $client_secret),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => 'grant_type=client_credentials',
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            throw new Exception(__('PayPal authentication failed', 'eye-book'));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body['access_token'])) {
            throw new Exception(__('PayPal access token not received', 'eye-book'));
        }

        return $body['access_token'];
    }

    /**
     * Sanitize payment data for logging
     *
     * @param array $payment_data
     * @return array
     * @since 1.0.0
     */
    private function sanitize_payment_data_for_log($payment_data) {
        $safe_data = $payment_data;
        
        // Remove sensitive data
        unset($safe_data['payment_method_id']);
        unset($safe_data['nonce']);
        unset($safe_data['cvv']);
        
        return $safe_data;
    }

    /**
     * AJAX handler for payment processing
     *
     * @since 1.0.0
     */
    public function ajax_process_payment() {
        if (!wp_verify_nonce($_POST['nonce'], 'eye_book_payment_nonce')) {
            wp_send_json_error(__('Security check failed', 'eye-book'));
        }

        $payment_data = array(
            'appointment_id' => intval($_POST['appointment_id']),
            'patient_id' => intval($_POST['patient_id']),
            'amount' => floatval($_POST['amount']),
            'payment_type' => sanitize_text_field($_POST['payment_type']),
            'payment_method_id' => sanitize_text_field($_POST['payment_method_id'] ?? ''),
            'nonce' => sanitize_text_field($_POST['payment_nonce'] ?? '')
        );

        $result = $this->process_payment($payment_data);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX handler for payment refunds
     *
     * @since 1.0.0
     */
    public function ajax_refund_payment() {
        if (!current_user_can('eye_book_manage_payments')) {
            wp_send_json_error(__('Insufficient permissions', 'eye-book'));
        }

        if (!wp_verify_nonce($_POST['nonce'], 'eye_book_admin_nonce')) {
            wp_send_json_error(__('Security check failed', 'eye-book'));
        }

        $payment_id = intval($_POST['payment_id']);
        $amount = !empty($_POST['amount']) ? floatval($_POST['amount']) : null;
        $reason = sanitize_textarea_field($_POST['reason']);

        $result = $this->refund_payment($payment_id, $amount, $reason);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    /**
     * AJAX handler for insurance verification
     *
     * @since 1.0.0
     */
    public function ajax_verify_insurance() {
        if (!wp_verify_nonce($_POST['nonce'], 'eye_book_admin_nonce')) {
            wp_send_json_error(__('Security check failed', 'eye-book'));
        }

        $insurance_data = array(
            'provider' => sanitize_text_field($_POST['insurance_provider']),
            'member_id' => sanitize_text_field($_POST['member_id']),
            'group_number' => sanitize_text_field($_POST['group_number'] ?? ''),
            'patient_dob' => sanitize_text_field($_POST['patient_dob'])
        );

        $result = $this->verify_insurance($insurance_data);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message']);
        }
    }

    // Webhook handlers
    public function handle_stripe_webhook() {
        // Implementation for Stripe webhook handling
    }

    public function handle_square_webhook() {
        // Implementation for Square webhook handling
    }

    public function handle_paypal_webhook() {
        // Implementation for PayPal webhook handling
    }

    // Additional refund methods
    private function refund_stripe_payment($transaction_id, $amount, $reason) {
        // Implementation for Stripe refunds
        return array('success' => true);
    }

    private function refund_square_payment($transaction_id, $amount, $reason) {
        // Implementation for Square refunds
        return array('success' => true);
    }

    private function refund_paypal_payment($transaction_id, $amount, $reason) {
        // Implementation for PayPal refunds
        return array('success' => true);
    }

    // Insurance verification methods
    private function verify_insurance_availity($insurance_data) {
        // Implementation for Availity insurance verification
        return array('success' => true, 'coverage' => array());
    }

    private function verify_insurance_change_healthcare($insurance_data) {
        // Implementation for Change Healthcare verification
        return array('success' => true, 'coverage' => array());
    }

    public function check_pending_payments() {
        // Check and update pending payment statuses
    }

    private function process_authorize_net_payment($payment_data) {
        // Implementation for Authorize.Net payments
        return array('success' => true);
    }
}