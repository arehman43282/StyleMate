<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if cart is not empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect shipping info (though current schema doesn't have columns for them in Orders table)
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $pincode = $_POST['pincode'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $user_id = $_SESSION['user_id'];

    // Calculate total with discount and shipping
    $subtotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    $discount = 0;
    if (isset($_SESSION['coupon'])) {
        if ($_SESSION['coupon']['type'] == 'percent') {
            $discount = $subtotal * $_SESSION['coupon']['discount_percent'];
        }
    }

    // Shipping Logic: Free >= 999, else 30
    $shipping_cost = ($subtotal >= 999) ? 0 : 30;
    
    $total_amount = $subtotal - $discount + $shipping_cost;

    // Start transaction
    $conn->begin_transaction();

    try {
        // 0. Handle Wallet Payment
        if ($payment_method === 'wallet') {
            $w_sql = "SELECT amount FROM wallet WHERE user_id = ? FOR UPDATE";
            $w_stmt = $conn->prepare($w_sql);
            $w_stmt->bind_param("i", $user_id);
            $w_stmt->execute();
            $w_res = $w_stmt->get_result();
            $w_row = $w_res->fetch_assoc();
            
            if (!$w_row || $w_row['amount'] < $total_amount) {
                throw new Exception("Insufficient wallet balance.");
            }
            
            // Deduct from wallet
            $deduct_sql = "UPDATE wallet SET amount = amount - ? WHERE user_id = ?";
            $d_stmt = $conn->prepare($deduct_sql);
            $d_stmt->bind_param("di", $total_amount, $user_id);
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
            $amount_expected = round($total_amount * 100);
            
            if ($amount_paid < $amount_expected) {
                throw new Exception("Payment verification failed: Amount mismatch.");
            }
        }

        // 1. Insert into Orders table
        // Status: 'Pending' for COD, 'Confirmed' for Paid (UPI/Card/Wallet)
        $order_date = date('Y-m-d');
        
        if ($payment_method === 'cod') {
            $status = 'Pending';
        } else {
            $status = 'Confirmed';
        }
        
        // Dynamically handle Orders table columns
        $order_cols = [];
        $res = $conn->query("SHOW COLUMNS FROM Orders");
        while($r = $res->fetch_assoc()) { $order_cols[] = $r['Field']; }
        
        $insert_fields = ["User_ID", "Order_Date", "Total_Amount", "Status"];
        $params = [$user_id, $order_date, $total_amount, $status];
        $types = "isds";
        
        // Check for optional address/phone/name columns in Orders table
        if (in_array('Customer_Name', $order_cols)) { $insert_fields[] = "Customer_Name"; $params[] = $name; $types .= "s"; }
        if (in_array('Phone', $order_cols)) { $insert_fields[] = "Phone"; $params[] = $phone; $types .= "s"; }
        if (in_array('Address', $order_cols)) { $insert_fields[] = "Address"; $params[] = $address; $types .= "s"; }

        $fields_str = implode(", ", $insert_fields);
        $placeholders = implode(", ", array_fill(0, count($insert_fields), "?"));
        
        $stmt = $conn->prepare("INSERT INTO Orders ($fields_str) VALUES ($placeholders)");
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $order_id = $conn->insert_id;
        $stmt->close();

        // 2. Insert into Order_Details, Track_Orders and Update Stock
        // Based on actual schema: Order_ID, Order_Number, Product_ID, Quantity, Subtotal
        $stmt = $conn->prepare("INSERT INTO Order_Details (Order_ID, Order_Number, Product_ID, Quantity, Subtotal) VALUES (?, ?, ?, ?, ?)");
        
        // Prepare Track_Orders statement once
        // Explicitly insert Order_ID to prevent auto-increment drift
        $track_stmt = $conn->prepare("INSERT INTO Track_Orders (Order_ID, User_ID, Product_ID, Order_Date, Status, Delivery_Address, Total_Amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        // Prepare Stock Update statement once
        $stock_stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE Products_ID = ?");
        
        foreach ($_SESSION['cart'] as $item) {
            $product_id = $item['product_id'];
            $quantity = $item['quantity'];
            $price = $item['price'];
            $subtotal = $price * $quantity;
            
            // 1. Insert into Order_Details
            $stmt->bind_param("iiiid", $order_id, $order_id, $product_id, $quantity, $subtotal);
            $stmt->execute();

            // 2. Also insert into Track_Orders table for track_order.php visibility
            $current_datetime = date('Y-m-d H:i:s');
            // Use the same status as the main order
            $status_code = $status; 
            $full_address = "$address, $city - $pincode";
            $track_stmt->bind_param("iiisssd", $order_id, $user_id, $product_id, $current_datetime, $status_code, $full_address, $subtotal);
            $track_stmt->execute();

            // 3. Deduct stock from products table
            $stock_stmt->bind_param("ii", $quantity, $product_id);
            $stock_stmt->execute();
        }
        $stmt->close();
        $track_stmt->close();
        $stock_stmt->close();

        // 4. Insert into Payment_Details table
        $stmt = $conn->prepare("INSERT INTO Payment_Details (Order_ID, User_ID, Amount, Payment_Type) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $payment_type = strtoupper($payment_method); // 'cod' -> 'COD', 'upi' -> 'UPI'
            $stmt->bind_param("iids", $order_id, $user_id, $total_amount, $payment_type);
            $stmt->execute();
            $stmt->close();
        }

        // Commit transaction
        $conn->commit();

        // Clear cart
        $_SESSION['cart'] = array();

        // Success message and redirect
        $_SESSION['success_message'] = "Order placed successfully! Your Order ID is #" . $order_id;
        header('Location: order_success.php?order_id=' . $order_id);
        exit();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        die("Error processing order: " . $e->getMessage());
    }
} else {
    header('Location: checkout.php');
    exit();
}
?>