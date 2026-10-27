<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['pending_upi_txn'])) {
    echo json_encode(['status' => 'error', 'message' => 'No pending transaction']);
    exit();
}

$txn = $_SESSION['pending_upi_txn'];
$elapsed = time() - $txn['start_time'];

// Only verify if a 'payment_made' flag is set in the session
// This flag can be set by a separate simulation endpoint
if (isset($_SESSION['pending_upi_txn']['payment_made']) && $_SESSION['pending_upi_txn']['payment_made'] === true) {
    $_SESSION['pending_upi_txn']['status'] = 'success';
    echo json_encode([
        'status' => 'success',
        'message' => 'Payment verified',
        'transaction_id' => $txn['id']
    ]);
} else {
    echo json_encode([
        'status' => 'pending',
        'message' => 'Waiting for payment...',
        'remaining_time' => 300 - $elapsed
    ]);
}
?>