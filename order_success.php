<?php
require_once 'db_connect.php';

// Get Order ID
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Redirect if no order ID
if ($order_id === 0) {
    // For development/debugging, show a message instead of redirecting
    // header('Location: index.php');
    // exit();
    die("<h3>Error: No Order ID specified.</h3><p>Please access this page with a valid order ID, e.g., <a href='?order_id=7'>order_success.php?order_id=7</a></p>");
}

// Fetch Order Details
$order_sql = "SELECT * FROM Orders WHERE Order_ID = ?";
if (isset($_SESSION['user_id'])) {
    $order_sql .= " AND User_ID = " . $_SESSION['user_id'];
}
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order_res = $stmt->get_result();
$order = $order_res->fetch_assoc();

if (!$order) {
    // Order not found or doesn't belong to user
    // header('Location: index.php');
    // exit();
    die("<h3>Error: Order not found or Access Denied.</h3><p>Ensure you are logged in as the user who placed Order #$order_id.</p>");
}

// Fetch Order Items
$items_sql = "SELECT od.*, p.Products_Name, p.image 
              FROM Order_Details od 
              JOIN products p ON od.Product_ID = p.Products_ID 
              WHERE od.Order_ID = ?";
$stmt = $conn->prepare($items_sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_res = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed - StyleMate</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .success-page-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .success-header {
            text-align: center;
            margin-bottom: 50px;
            padding: 60px 0;
            background: #fff;
            border-bottom: 1px solid #f0f0f0;
        }

        .success-icon-large {
            font-size: 80px;
            color: #4CAF50;
            margin-bottom: 20px;
            animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes scaleIn {
            from { transform: scale(0); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .order-meta {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 20px;
            color: var(--text-muted);
            font-size: 14px;
        }

        .order-summary-box {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }

        .summary-header {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px 0;
            border-bottom: 1px solid #f9f9f9;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .item-img {
            width: 70px;
            height: 90px;
            object-fit: cover;
            border-radius: 6px;
            background: #f5f5f5;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 5px;
            color: var(--text-main);
        }

        .item-meta {
            font-size: 12px;
            color: var(--text-muted);
        }

        .item-price {
            font-weight: 700;
            font-size: 14px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            font-size: 14px;
        }

        .total-row.final {
            border-top: 1px solid #eee;
            margin-top: 10px;
            padding-top: 20px;
            font-size: 18px;
            font-weight: 800;
        }

        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 40px;
        }

        @media (max-width: 600px) {
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
            font-size: 14px;
        }
        
        .info-col h4 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>

    <main style="padding-top: 100px; background: #fcfcfc; min-height: 100vh;">
        <div class="success-page-container">
            
            <div class="success-header">
                <i class='bx bxs-check-circle success-icon-large'></i>
                <h1 style="font-size: 32px; margin-bottom: 10px;">Order Confirmed!</h1>
                <p style="color: var(--text-muted); font-size: 16px;">Thank you for your purchase. Your order has been received.</p>
                
                <div class="order-meta">
                    <span>Order #: <strong><?php echo str_pad($order_id, 4, '0', STR_PAD_LEFT); ?></strong></span>
                    <span>Date: <strong><?php echo date('M d, Y', strtotime($order['Order_Date'])); ?></strong></span>
                </div>
            </div>

            <div class="order-summary-box">
                <div class="summary-header">
                    <span>Order Details</span>
                    <span style="font-size: 12px; padding: 5px 10px; background: #e8f5e9; color: #2e7d32; border-radius: 20px; text-transform: uppercase; font-weight: 700;">
                        <?php echo $order['Status'] == 'P' ? 'Processing' : $order['Status']; ?>
                    </span>
                </div>

                <div class="items-list">
                    <?php 
                    $subtotal = 0;
                    while($item = $items_res->fetch_assoc()): 
                        $item_subtotal = $item['Subtotal'];
                        $subtotal += $item_subtotal;
                        
                        // Image fallback logic
                        $img_src = 'images/products/' . ($item['image'] ?? 'placeholder.svg');
                    ?>
                    <div class="order-item">
                        <img src="<?php echo htmlspecialchars($img_src); ?>" alt="" class="item-img" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?auto=format&fit=crop&w=600&q=80'">
                        <div class="item-info">
                            <div class="item-name"><?php echo htmlspecialchars($item['Products_Name']); ?></div>
                            <div class="item-meta">Qty: <?php echo $item['Quantity']; ?></div>
                        </div>
                        <div class="item-price">₹<?php echo number_format($item_subtotal, 2); ?></div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <div style="margin-top: 30px;">
                    <div class="total-row">
                        <span style="color: var(--text-muted);">Subtotal</span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span style="color: var(--text-muted);">Shipping</span>
                        <span style="color: #4CAF50;">Free</span>
                    </div>
                    <div class="total-row final">
                        <span>Total Amount</span>
                        <span>₹<?php echo number_format($order['Total_Amount'], 2); ?></span>
                    </div>
                </div>

                <?php if (!empty($order['Address'])): ?>
                <div class="info-grid" style="border-top: 1px solid #f0f0f0; padding-top: 20px;">
                    <div class="info-col">
                        <h4>Shipping Address</h4>
                        <p style="line-height: 1.6;">
                            <?php echo htmlspecialchars($order['Customer_Name'] ?? 'Customer'); ?><br>
                            <?php echo htmlspecialchars($order['Address']); ?><br>
                            <?php echo htmlspecialchars($order['Phone'] ?? ''); ?>
                        </p>
                    </div>
                    <div class="info-col">
                        <h4>Payment Method</h4>
                        <p>
                            <?php 
                            // Fetch payment method if needed, or infer
                            // For now just generic text or fetch from Payment_Details if critical
                            echo "Online Payment / COD"; 
                            ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="action-buttons">
                <a href="index.php" class="premium-btn outline" style="text-align: center; justify-content: center; display: flex;">
                    Continue Shopping
                </a>
                <a href="track_order.php" class="premium-btn primary" style="text-align: center; justify-content: center; display: flex;">
                    Track Order
                </a>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="generate_invoice.php?order_id=<?php echo $order_id; ?>" target="_blank" style="color: var(--text-muted); text-decoration: underline; font-size: 13px;">
                    <i class="fas fa-file-download"></i> Download Invoice
                </a>
            </div>

        </div>
    </main>

    <?php require_once 'footer.php'; ?>
</body>
</html>
