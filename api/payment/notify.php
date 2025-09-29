<?php
/**
 * PayHere Payment Notification Handler
 * Handles PayHere payment callbacks
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../backend/models/PayHere.php';
require_once __DIR__ . '/../../backend/models/Payment.php';
require_once __DIR__ . '/../../backend/models/Booking.php';

// Log the notification
error_log('PayHere Notification: ' . json_encode($_POST));

try {
    $payhere = new PayHere();
    $payment_model = new Payment();
    $booking_model = new Booking();
    
    // Verify the callback
    $verification = $payhere->verifyCallback($_POST);
    
    if (!$verification['valid']) {
        error_log('PayHere verification failed');
        http_response_code(400);
        echo 'Verification failed';
        exit;
    }
    
    // Find payment record by order ID
    $order_id = $verification['order_id'];
    $payment = $payment_model->getByTransactionId($order_id);
    
    if (!$payment) {
        error_log('Payment record not found for order: ' . $order_id);
        http_response_code(404);
        echo 'Payment record not found';
        exit;
    }
    
    // Update payment status
    $status = $verification['status_code'] == '2' ? 'completed' : 'failed';
    
    $update_data = [
        'payment_status' => $status,
        'payment_reference' => $verification['payment_id'] ?? '',
        'payment_method' => $verification['method'] ?? 'payhere',
        'paid_date' => $status === 'completed' ? date('Y-m-d H:i:s') : null
    ];
    
    $payment_model->update($payment['id'], $update_data);
    
    // Update booking status if payment successful
    if ($status === 'completed') {
        $booking_model->update($payment['booking_id'], [
            'status' => 'active' // Use valid status from enum
        ]);
        
        // Send confirmation email (optional)
        // sendBookingConfirmation($payment['booking_id']);
    }
    
    // Log success
    error_log('Payment updated successfully: ' . $order_id . ' - Status: ' . $status);
    
    // Return success response to PayHere
    http_response_code(200);
    echo 'OK';
    
} catch (Exception $e) {
    error_log('PayHere notification error: ' . $e->getMessage());
    http_response_code(500);
    echo 'Error processing notification';
}
?>
