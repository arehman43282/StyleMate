<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);

    // Check if already in wishlist
    $check = $conn->prepare("SELECT wishlist_id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $check->bind_param("ii", $user_id, $product_id);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Remove from wishlist
        $delete = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $delete->bind_param("ii", $user_id, $product_id);
        $delete->execute();
        echo json_encode(['status' => 'success', 'action' => 'removed']);
    } else {
        // Add to wishlist
        $insert = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $insert->bind_param("ii", $user_id, $product_id);
        $insert->execute();
        echo json_encode(['status' => 'success', 'action' => 'added']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
}
?>