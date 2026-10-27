<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
$reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

if ($order_id <= 0 || empty($reason)) {
    echo json_encode(['success' => false, 'message' => 'Order ID and reason are required.']);
    exit();
}

// Check if order belongs to user and is delivered
$check_sql = "SELECT Status, Order_Date, Delivery_Date FROM Track_Orders WHERE Order_ID = ? AND User_ID = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $order_id, $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found.']);
    exit();
}

$order = $result->fetch_assoc();
if ($order['Status'] !== 'Delivered' && $order['Status'] !== 'D') {
    echo json_encode(['success' => false, 'message' => 'Only delivered orders can be returned.']);
    exit();
}

// Check 7-day return limit
$delivery_date = $order['Delivery_Date'] ? $order['Delivery_Date'] : $order['Order_Date'];
$delivery_ts = strtotime($delivery_date);
$current_ts = time();
$days_since_delivery = floor(($current_ts - $delivery_ts) / (60 * 60 * 24));

if ($days_since_delivery > 7) {
    echo json_encode(['success' => false, 'message' => 'The return window for this order has expired (7 days from delivery).']);
    exit();
}

// Check if return request already exists
$check_return_sql = "SELECT id FROM return_requests WHERE order_id = ?";
$check_return_stmt = $conn->prepare($check_return_sql);
$check_return_stmt->bind_param("i", $order_id);
$check_return_stmt->execute();
if ($check_return_stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'A return request for this order already exists.']);
    exit();
}

// Insert return request
$insert_sql = "INSERT INTO return_requests (order_id, user_id, reason) VALUES (?, ?, ?)";
$insert_stmt = $conn->prepare($insert_sql);
$insert_stmt->bind_param("iis", $order_id, $user_id, $reason);

if ($insert_stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Return request submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit return request. Please try again.']);
}

$insert_stmt->close();
$conn->close();
?>