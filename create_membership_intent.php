<?php
session_start();
require_once 'payment/StripeService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

try {
    // Fixed Membership Fee: ₹499
    $amount = 499;
    $amount_in_paise = round($amount * 100);

    $stripe = new StripeService();
    $paymentIntent = $stripe->createPaymentIntent($amount_in_paise, 'inr', [
        'user_id' => $_SESSION['user_id'],
        'type' => 'membership_fee'
    ]);

    echo json_encode([
        'clientSecret' => $paymentIntent['client_secret'],
        'publicKey' => STRIPE_PUBLISHABLE_KEY // Note: Previous file used 'publishableKey' but my JS used 'publicKey'. I'll match JS.
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>