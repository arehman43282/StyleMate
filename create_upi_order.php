<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || empty($_SESSION['cart'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid session']);
    exit();
}

// Generate a mock transaction ID
$transaction_id = 'TXN' . time() . rand(1000, 9999);
$_SESSION['pending_upi_txn'] = [
    'id' => $transaction_id,
    'status' => 'pending',
    'start_time' => time()
];

// Calculate total for the response
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

echo json_encode([
    'status' => 'success',
    'transaction_id' => $transaction_id,
    'amount' => $total,
    'upi_link' => "upi://pay?pa=StyleMate@bank&pn=StyleMate&am=$total&cu=INR&tid=$transaction_id"
]);
?>