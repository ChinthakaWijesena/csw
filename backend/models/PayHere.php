<?php
/**
 * PayHere Payment Gateway Model
 * Handles PayHere payment integration
 */

require_once __DIR__ . '/../../config/config.php';

class PayHere {
    private $merchant_id;
    private $merchant_secret;
    private $sandbox_mode;
    
    public function __construct() {
        $this->merchant_id = '1224197';
        $this->merchant_secret = 'MTI2NzEwODA0NDEyOTE1ODYzODc0MTc2OTgwNjk4NDIzNTM2NjA2Mw==';
        $this->sandbox_mode = true; // Set to false for production
    }
    
    /**
     * Generate payment form data for PayHere
     */
    public function generatePaymentData($booking_data) {
        $amount = $booking_data['amount'];
        $currency = 'LKR';
        $order_id = $booking_data['order_id'];
        $item_name = $booking_data['item_name'];
        $customer_name = $booking_data['customer_name'];
        $customer_email = $booking_data['customer_email'];
        $customer_phone = $booking_data['customer_phone'];
        $return_url = $booking_data['return_url'];
        $cancel_url = $booking_data['cancel_url'];
        $notify_url = $booking_data['notify_url'];
        
        // Create hash for security
        $hash = $this->generateHash($this->merchant_id, $order_id, $amount, $currency, $this->merchant_secret);
        
        $payment_data = [
            'merchant_id' => $this->merchant_id,
            'return_url' => $return_url,
            'cancel_url' => $cancel_url,
            'notify_url' => $notify_url,
            'first_name' => $customer_name,
            'last_name' => '',
            'email' => $customer_email,
            'phone' => $customer_phone,
            'address' => $booking_data['address'] ?? '',
            'city' => $booking_data['city'] ?? '',
            'country' => 'Sri Lanka',
            'order_id' => $order_id,
            'items' => $item_name,
            'currency' => $currency,
            'amount' => $amount,
            'hash' => $hash
        ];
        
        return $payment_data;
    }
    
    /**
     * Generate hash for PayHere
     */
    private function generateHash($merchant_id, $order_id, $amount, $currency, $merchant_secret) {
        // Decode the base64 encoded merchant secret
        $decoded_secret = base64_decode($merchant_secret);
        $hash_string = $merchant_id . $order_id . $amount . $currency . $decoded_secret;
        return strtoupper(hash('sha256', $hash_string));
    }
    
    /**
     * Verify PayHere callback
     */
    public function verifyCallback($data) {
        $merchant_id = $data['merchant_id'] ?? '';
        $order_id = $data['order_id'] ?? '';
        $payhere_amount = $data['payhere_amount'] ?? '';
        $payhere_currency = $data['payhere_currency'] ?? '';
        $status_code = $data['status_code'] ?? '';
        $md5sig = $data['md5sig'] ?? '';
        
        // Generate expected hash
        $expected_hash = $this->generateHash($merchant_id, $order_id, $payhere_amount, $payhere_currency, $this->merchant_secret);
        
        // Verify merchant ID
        if ($merchant_id !== $this->merchant_id) {
            return false;
        }
        
        // Verify hash
        if (strtoupper($md5sig) !== $expected_hash) {
            return false;
        }
        
        return [
            'valid' => true,
            'order_id' => $order_id,
            'amount' => $payhere_amount,
            'currency' => $payhere_currency,
            'status_code' => $status_code,
            'payment_id' => $data['payment_id'] ?? '',
            'method' => $data['method'] ?? '',
            'status_message' => $data['status_message'] ?? ''
        ];
    }
    
    /**
     * Get PayHere payment URL
     */
    public function getPaymentUrl() {
        if ($this->sandbox_mode) {
            return 'https://sandbox.payhere.lk/pay/checkout';
        } else {
            return 'https://www.payhere.lk/pay/checkout';
        }
    }
    
    /**
     * Create payment form HTML
     */
    public function createPaymentForm($payment_data) {
        $payment_url = $this->getPaymentUrl();
        
        $form_html = '<form action="' . $payment_url . '" method="post" id="payhere-payment-form">';
        
        foreach ($payment_data as $key => $value) {
            $form_html .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
        }
        
        $form_html .= '</form>';
        
        return $form_html;
    }
    
    /**
     * Get payment status text
     */
    public function getStatusText($status_code) {
        $statuses = [
            '2' => 'Success',
            '0' => 'Pending',
            '-1' => 'Canceled',
            '-2' => 'Failed',
            '-3' => 'Charged Back'
        ];
        
        return $statuses[$status_code] ?? 'Unknown';
    }
}
?>
