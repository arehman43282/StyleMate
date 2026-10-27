<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $user_id = $_SESSION['user_id'];
    $order_id = (int)$_POST['order_id'];

    // Verify order exists, belongs to user, and is rejected
    $check_sql = "SELECT Order_ID, Total_Amount, Status FROM Track_Orders WHERE Order_ID = ? AND User_ID = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }

    if ($order['Status'] !== 'R' && $order['Status'] !== 'Rejected') {
        echo json_encode(['success' => false, 'message' => 'Only rejected orders can be refunded']);
        exit();
    }

    // Check if already refunded
    $check_refund = "SELECT id FROM wallet_refunds WHERE order_id = ?";
    $stmt_refund = $conn->prepare($check_refund);
    $stmt_refund->bind_param("i", $order_id);
    $stmt_refund->execute();
    if ($stmt_refund->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Order already refunded to wallet']);
        exit();
    }

    $amount = $order['Total_Amount'];

    $conn->begin_transaction();
    try {
        // Update wallet
        $upsert_wallet = "INSERT INTO wallet (user_id, amount) VALUES (?, ?) 
                        ON DUPLICATE KEY UPDATE amount = amount + ?";
        $w_stmt = $conn->prepare($upsert_wallet);
        $w_stmt->bind_param("idd", $user_id, $amount, $amount);
        $w_stmt->execute();

        // Record refund
        $record_refund = "INSERT INTO wallet_refunds (order_id, amount) VALUES (?, ?)";
        $r_stmt = $conn->prepare($record_refund);
        $r_stmt->bind_param("id", $order_id, $amount);
        $r_stmt->execute();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Amount ₹' . number_format($amount, 2) . ' refunded to wallet successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>
