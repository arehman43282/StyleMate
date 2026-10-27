<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $user_id = $_SESSION['user_id'];
    $amount = 499;

    // Start transaction
    $conn->begin_transaction();

    try {
        // Update user's phone number if provided
        if (!empty($phone)) {
            $upd_phone_sql = "UPDATE users SET Phone_no = ? WHERE User_ID = ?";
            $up_stmt = $conn->prepare($upd_phone_sql);
            $up_stmt->bind_param("si", $phone, $user_id);
            $up_stmt->execute();
            $up_stmt->close();
        }
        // Handle Wallet Payment
        if ($payment_method === 'wallet') {
            $w_sql = "SELECT amount FROM wallet WHERE user_id = ? FOR UPDATE";
            $w_stmt = $conn->prepare($w_sql);
            $w_stmt->bind_param("i", $user_id);
            $w_stmt->execute();
            $w_res = $w_stmt->get_result();
            $w_row = $w_res->fetch_assoc();
            
            if (!$w_row || $w_row['amount'] < $amount) {
                throw new Exception("Insufficient wallet balance.");
            }
            
            // Deduct from wallet
            $deduct_sql = "UPDATE wallet SET amount = amount - ? WHERE user_id = ?";
            $d_stmt = $conn->prepare($deduct_sql);
            $d_stmt->bind_param("di", $amount, $user_id);
            $d_stmt->execute();
        }
        // Handle Stripe Card Payment
        elseif ($payment_method === 'card') {
            require_once 'payment/StripeService.php';
            
            $payment_intent_id = $_POST['payment_intent_id'] ?? '';
            
            if (empty($payment_intent_id)) {
                throw new Exception("Payment verification failed: No payment ID provided.");
            }
            
            $stripe = new StripeService();
            $intent = $stripe->retrievePaymentIntent($payment_intent_id);
            
            if (!isset($intent['status']) || $intent['status'] !== 'succeeded') {
                throw new Exception("Payment verification failed: Payment not successful.");
            }
            
            // Verify amount matches
            $amount_paid = $intent['amount'];
            $amount_expected = round($amount * 100);
            
            if ($amount_paid < $amount_expected) {
                throw new Exception("Payment verification failed: Amount mismatch.");
            }
        }

        // Update User Membership Status
        $expiry_date = date('Y-m-d H:i:s', strtotime('+1 year'));
        $update_user_sql = "UPDATE users SET is_premium = 1, membership_expiry = ? WHERE User_ID = ?";
        $stmt = $conn->prepare($update_user_sql);
        $stmt->bind_param("si", $expiry_date, $user_id);
        $stmt->execute();
        $stmt->close();

        // Log Transaction (Optional - creating a record in Payment_Details with a dummy Order_ID or 0)
        // Since Payment_Details requires Order_ID, we might skip this or use a dummy order.
        // Let's check if we can insert with Order_ID = 0 (if FK allows) or just skip logging for now to avoid FK constraint errors.
        // Alternatively, create a "Membership Order" in Orders table?
        
        // Let's create a record in Orders table to track this payment properly
        $order_date = date('Y-m-d');
        $status = 'Completed';
        
        // Check Orders table columns to insert correctly
        // Minimal insert for membership record
        $o_sql = "INSERT INTO Orders (User_ID, Order_Date, Total_Amount, Status) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($o_sql);
        $stmt->bind_param("isds", $user_id, $order_date, $amount, $status);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();
        
        // Record Payment
        $p_sql = "INSERT INTO Payment_Details (Order_ID, User_ID, Amount, Payment_Type) VALUES (?, ?, ?, ?)";
        $payment_type = strtoupper($payment_method);
        $stmt = $conn->prepare($p_sql);
        $stmt->bind_param("iids", $order_id, $user_id, $amount, $payment_type);
        $stmt->execute();
        $stmt->close();

        // Commit transaction
        $conn->commit();

        // Success redirect
        header('Location: StyleMate_club.php?status=success&order_id=' . $order_id);
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Error processing membership: " . $e->getMessage());
    }
} else {
    header('Location: StyleMate_checkout.php');
    exit();
}
?>