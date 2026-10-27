<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = intval($_GET['order_id']);

// Fetch Order Details
$sql = "SELECT o.Order_ID, o.Order_Date, o.Total_Amount, p.Payment_Type, u.Username, u.Email 
        FROM Orders o
        JOIN users u ON o.User_ID = u.User_ID
        LEFT JOIN Payment_Details p ON o.Order_ID = p.Order_ID
        WHERE o.Order_ID = ? AND o.User_ID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Receipt not found or access denied.");
}

$order = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Receipt #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="styles.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        body {
            background-color: #f5f5f5;
            padding: 40px 0;
            font-family: 'Inter', sans-serif;
        }
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        .receipt-header {
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .company-info h2 {
            margin: 0;
            color: #000;
            font-size: 24px;
            font-weight: 800;
        }
        .receipt-title {
            text-align: right;
        }
        .receipt-title h1 {
            margin: 0;
            font-size: 20px;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .receipt-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .bill-to, .order-info {
            width: 48%;
        }
        .info-group {
            margin-bottom: 15px;
        }
        .info-label {
            font-weight: 600;
            color: #888;
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            color: #000;
            font-size: 15px;
            font-weight: 500;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .receipt-table th {
            text-align: left;
            padding: 15px;
            background: #f9f9f9;
            color: #666;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
        }
        .receipt-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            color: #333;
        }
        .total-section {
            display: flex;
            justify-content: flex-end;
        }
        .total-box {
            width: 300px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            color: #555;
        }
        .total-row.final {
            border-bottom: none;
            font-size: 20px;
            font-weight: 800;
            color: #000;
            margin-top: 10px;
            padding-top: 15px;
            border-top: 2px solid #000;
        }
        .print-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px;
            background: #000;
            color: #fff;
            text-align: center;
            text-decoration: none;
            font-weight: 600;
            border-radius: 6px;
            margin-top: 40px;
            cursor: pointer;
            border: none;
            font-size: 16px;
            transition: background 0.3s;
        }
        .print-btn:hover {
            background: #333;
        }
        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .receipt-container {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .print-btn, .back-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <div class="company-info">
                <h2>StyleMate</h2>
                <p style="color: #666; margin-top: 5px; font-size: 14px;">Fashion & Lifestyle</p>
            </div>
            <div class="receipt-title">
                <h1>Payment Receipt</h1>
                <p style="color: #666; margin-top: 5px; font-size: 14px;">#<?php echo str_pad($order_id, 8, '0', STR_PAD_LEFT); ?></p>
            </div>
        </div>

        <div class="receipt-details">
            <div class="bill-to">
                <div class="info-group">
                    <span class="info-label">Billed To</span>
                    <div class="info-value"><?php echo htmlspecialchars($order['Username']); ?></div>
                    <div class="info-value" style="font-size: 14px; color: #666;"><?php echo htmlspecialchars($order['Email']); ?></div>
                </div>
            </div>
            <div class="order-info">
                <div class="info-group">
                    <span class="info-label">Date</span>
                    <div class="info-value"><?php echo date('F d, Y', strtotime($order['Order_Date'])); ?></div>
                </div>
                <div class="info-group">
                    <span class="info-label">Payment Method</span>
                    <div class="info-value"><?php echo htmlspecialchars($order['Payment_Type'] ?? 'Online'); ?></div>
                </div>
                <div class="info-group">
                    <span class="info-label">Status</span>
                    <div class="info-value" style="color: #4CAF50;">Paid</div>
                </div>
            </div>
        </div>

        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>StyleMate Club Premium Membership</strong><br>
                        <span style="color: #666; font-size: 12px; margin-top: 4px; display: block;">1 Year Subscription (Valid until <?php echo date('d M Y', strtotime('+1 year', strtotime($order['Order_Date']))); ?>)</span>
                    </td>
                    <td style="text-align: right;">₹<?php echo number_format($order['Total_Amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="total-section">
            <div class="total-box">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span>₹<?php echo number_format($order['Total_Amount'], 2); ?></span>
                </div>
                <div class="total-row">
                    <span>Tax (0%)</span>
                    <span>₹0.00</span>
                </div>
                <div class="total-row final">
                    <span>Total Paid</span>
                    <span>₹<?php echo number_format($order['Total_Amount'], 2); ?></span>
                </div>
            </div>
        </div>

        <div style="margin-top: 60px; text-align: center; color: #888; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px;">
            <p>Thank you for being a premium member.</p>
            <p>StyleMate Inc. | 123 Fashion Street, Design District</p>
        </div>

        <button onclick="window.print()" class="print-btn">
            <i class='bx bx-printer'></i> Download / Print Receipt
        </button>
        <a href="StyleMate_club.php" style="display: block; text-align: center; margin-top: 20px; color: #666; text-decoration: none; font-size: 14px;" class="back-btn">
            <i class='bx bx-arrow-back'></i> Back to Club Page
        </a>
    </div>
</body>
</html>