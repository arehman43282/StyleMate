<?php
session_start();
require_once 'db_connection.php';

if (!isset($_SESSION['users_id'])) {
    header('Location: login.php');
    exit();
}

// Handle return request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['return_order'])) {
    $order_id = $_POST['order_id'];
    $reason = $_POST['reason'];
    $user_id = $_SESSION['user']['id'];
    
    $sql = "INSERT INTO returns (order_id, user_id, reason) VALUES ($order_id, $user_id, '$reason')";
    mysqli_query($conn, $sql);
}

// Fetch user's orders with items
$sql = "SELECT o.orders_id, o.total_amount, o.status, o.order_date, 
        od.quantity, od.price_per_unit, p.name, p.image_url 
        FROM orders o 
        LEFT JOIN orders_details od ON o.orders_id = od.orders_id 
        LEFT JOIN products p ON od.products_id = p.products_id 
        WHERE o.users_id = $user_id 
        ORDER BY o.order_date DESC";

$result = mysqli_query($conn, $sql);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Returns & Orders - StyleMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }

        .order-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .order-date {
            color: #666;
        }

        .order-status {
            padding: 5px 15px;
            border-radius: 15px;
            font-weight: 500;
        }

        .status-delivered {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .status-processing {
            background: #fff3e0;
            color: #ef6c00;
        }

        .status-cancelled {
            background: #ffebee;
            color: #c62828;
        }

        .order-items {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }

        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }

        .item-details {
            flex-grow: 1;
        }

        .order-actions {
            display: flex;
            gap: 10px;
        }

        .btn-track, .btn-return {
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 500;
        }

        .btn-track {
            background: #232f3e;
            color: white;
        }

        .btn-return {
            background: #febd69;
            color: #232f3e;
        }

        .no-orders {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <?php include 'banner.php'; ?>

    <div class="orders-container">
        <h1 class="mb-4">Your Orders</h1>

        // Add this after the query execution
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<div class="order-card">';
                echo '<div class="order-header">';
                echo '<div>';
                echo '<h5>Order #' . $row['orders_id'] . '</h5>';
                echo '<span class="order-date">' . date('F j, Y', strtotime($row['order_date'])) . '</span>';
                echo '</div>';
                echo '<span class="order-status status-' . strtolower($row['status']) . '">' . $row['status'] . '</span>';
                echo '</div>';
                echo '<div class="order-items">';
                echo '<img src="img/products/' . $row['image_url'] . '" alt="Product" class="item-image">';
                echo '<div class="item-details">';
                echo '<h6>' . $row['name'] . '</h6>';
                echo '<p>Quantity: ' . $row['quantity'] . '</p>';
                echo '<p>Price per unit: ₹' . number_format($row['price_per_unit'], 2) . '</p>';
                echo '<p>Total: ₹' . number_format($row['total_amount'], 2) . '</p>';
                echo '</div>';
                echo '</div>';
                echo '<div class="order-actions">';
                echo '<button class="btn btn-track" onclick="trackOrder(' . $row['orders_id'] . ')">Track Order</button>';
                if ($row['status'] === 'Delivered') {
                    echo '<button class="btn btn-return" onclick="returnOrder(' . $row['orders_id'] . ')">Return</button>';
                }
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<div class="no-orders">';
            echo '<i class="fas fa-box-open fa-3x mb-3"></i>';
            echo '<h3>No orders found</h3>';
            echo '<p>When you place orders, they will appear here</p>';
            echo '<a href="index.php" class="btn btn-track mt-3">Start Shopping</a>';
            echo '</div>';
        }

        // Add JavaScript functions for tracking and returns
        ?>
        <script>
        function trackOrder(orderId) {
            // Add tracking functionality
            alert('Tracking order #' + orderId);
        }

        function returnOrder(orderId) {
            let reason = prompt('Please enter the reason for return:');
            if (reason) {
                // Submit return request
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="return_order" value="1">
                    <input type="hidden" name="order_id" value="${orderId}">
                    <input type="hidden" name="reason" value="${reason}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        </script>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>