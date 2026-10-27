<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $order_id = $_POST['order_id'] ?? null;
    $dp_id = $_POST['deliveryperson_id'] ?? null;
    $rating = $_POST['delivery_rating'] ?? null;
    $review = $_POST['delivery_review_text'] ?? '';

    if (!$order_id || !$dp_id || !$rating) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
        exit();
    }

    // Check if rating already exists for this order
    $check_sql = "SELECT id FROM Delivery_Person_Ratings WHERE Order_ID = ? AND User_ID = ?";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing rating
        $update_sql = "UPDATE Delivery_Person_Ratings SET Rating_Value = ?, Review_Text = ?, Rating_Date = CURRENT_TIMESTAMP WHERE Order_ID = ? AND User_ID = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("isii", $rating, $review, $order_id, $user_id);
    } else {
        // Insert new rating
        $insert_sql = "INSERT INTO Delivery_Person_Ratings (Deliveryperson_ID, Order_ID, User_ID, Rating_Value, Review_Text) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iiiis", $dp_id, $order_id, $user_id, $rating, $review);
    }

    if ($stmt->execute()) {
        // Optional: Update average rating in Delivery_Person table
        $avg_sql = "SELECT AVG(Rating_Value) as avg_rating FROM Delivery_Person_Ratings WHERE Deliveryperson_ID = ?";
        $avg_stmt = $conn->prepare($avg_sql);
        $avg_stmt->bind_param("i", $dp_id);
        $avg_stmt->execute();
        $avg_res = $avg_stmt->get_result();
        $avg_row = $avg_res->fetch_assoc();
        $new_avg = round($avg_row['avg_rating']);

        $update_dp_sql = "UPDATE Delivery_Person SET Ratings = ? WHERE Deliveryperson_ID = ?";
        $upd_stmt = $conn->prepare($update_dp_sql);
        $upd_stmt->bind_param("ii", $new_avg, $dp_id);
        $upd_stmt->execute();

        echo json_encode(['status' => 'success', 'message' => 'Rating submitted successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save rating: ' . $conn->error]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>
