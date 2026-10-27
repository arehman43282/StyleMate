<?php
session_start();
require_once 'db_connect.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'You must be logged in to submit a rating.';
        echo json_encode($response);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $product_id = isset($_POST['products_id']) ? (int)$_POST['products_id'] : 0;
    $order_id = isset($_POST['order_id']) && $_POST['order_id'] !== '' ? (int)$_POST['order_id'] : null;
    $rating_value = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review_text = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';

    if ($product_id <= 0 || $rating_value < 1 || $rating_value > 5) {
        $response['message'] = 'Invalid product ID or rating value.';
        echo json_encode($response);
        exit();
    }

    // Check if the user has already rated this product
    if ($order_id) {
        $check_sql = "SELECT * FROM Products_Ratings WHERE Products_ID = ? AND User_ID = ? AND Order_ID = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("iii", $product_id, $user_id, $order_id);
    } else {
        $check_sql = "SELECT * FROM Products_Ratings WHERE Products_ID = ? AND User_ID = ? AND Order_ID IS NULL";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ii", $product_id, $user_id);
    }
    
    if ($stmt === false) {
        $response['message'] = 'Prepare statement failed: ' . $conn->error;
        echo json_encode($response);
        exit();
    }
    
    $stmt->execute();
    $check_result = $stmt->get_result();

    if ($check_result->num_rows > 0) {
        // User has already rated, update the existing rating
        if ($order_id) {
            $update_sql = "UPDATE Products_Ratings SET Rating_Value = ?, Review_Text = ?, Date = NOW() WHERE Products_ID = ? AND User_ID = ? AND Order_ID = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("isiii", $rating_value, $review_text, $product_id, $user_id, $order_id);
        } else {
            $update_sql = "UPDATE Products_Ratings SET Rating_Value = ?, Review_Text = ?, Date = NOW() WHERE Products_ID = ? AND User_ID = ? AND Order_ID IS NULL";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("isii", $rating_value, $review_text, $product_id, $user_id);
        }
    } else {
        // New rating, insert it
        $insert_sql = "INSERT INTO Products_Ratings (Products_ID, User_ID, Order_ID, Rating_Value, Review_Text, Date) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_sql);
        if ($stmt === false) {
            $response['message'] = 'Prepare statement failed: ' . $conn->error;
            echo json_encode($response);
            exit();
        }
        $stmt->bind_param("iiiss", $product_id, $user_id, $order_id, $rating_value, $review_text);
    }

    if ($stmt && $stmt->execute()) {
        $response['status'] = 'success';
        $response['message'] = 'Rating submitted successfully.';
    } else {
        $response['message'] = 'Database error: ' . ($stmt ? $stmt->error : $conn->error);
    }
    if ($stmt) $stmt->close();
}

$conn->close();
echo json_encode($response);
?>