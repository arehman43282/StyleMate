<?php
session_start();
require_once 'db_connect.php';

if (!isset($_GET['order_id']) || !isset($_SESSION['user_id'])) {
    die("Access Denied");
}

$order_id = (int)$_GET['order_id'];
$user_id = (int)$_SESSION['user_id'];

// Fetch Order and User Details
$order_sql = "SELECT t.*, u.Username as Full_Name, u.Email, u.Phone_no as Phone_Number, u.Address, pay.Payment_Type 
              FROM Track_Orders t 
              JOIN Users u ON t.User_ID = u.User_ID 
              LEFT JOIN Payment_Details pay ON t.Order_ID = pay.Order_ID
              WHERE t.Order_ID = ? AND t.User_ID = ?
              LIMIT 1";
$stmt = $conn->prepare($order_sql);
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    die("Order not found");
}

// Fetch Order Items from Track_Orders (the dashboard table)
$items_sql = "SELECT t.*, p.Products_Name, p.Price as Unit_Price, 1 as Quantity, t.Total_Amount as Subtotal
              FROM Track_Orders t 
              JOIN products p ON t.Product_ID = p.Products_ID 
              WHERE t.Order_ID = ?";
$item_stmt = $conn->prepare($items_sql);
$item_stmt->bind_param("i", $order_id);
$item_stmt->execute();
$items = $item_stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - Order #<?php echo $order_id; ?></title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0, 0, 0, 0.15); }
        .invoice-header { display: flex; justify-content: space-between; border-bottom: 2px solid #f0c14b; padding-bottom: 20px; margin-bottom: 20px; }
        .logo { font-size: 28px; font-weight: bold; color: #232f3e; }
        .invoice-details { text-align: right; }
        .info-section { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 30px; }
        .info-box h3 { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px; font-size: 16px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background: #f8f9fa; text-align: left; padding: 12px; border-bottom: 2px solid #eee; }
        td { padding: 12px; border-bottom: 1px solid #eee; }
        .total-section { text-align: right; font-size: 18px; }
        .total-row { font-weight: bold; color: #B12704; font-size: 22px; }
        .footer { text-align: center; margin-top: 50px; color: #888; font-size: 12px; }
        @media print {
            .no-print { display: none; }
            .invoice-box { border: none; box-shadow: none; }
        }
        .print-btn { background: #232f3e; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <div class="no-print">
            <button onclick="window.print()" class="print-btn">Print Invoice / Download PDF</button>
            <button onclick="window.location.href='index.php'" class="print-btn" style="background:#666;">Back to Home</button>
        </div>

        <div class="invoice-header">
            <div class="logo">StyleMate</div>
            <div class="invoice-details">
                <strong>Invoice #:</strong> <?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?><br>
                <strong>Date:</strong> <?php echo date('d M Y', strtotime($order['Order_Date'])); ?><br>
                <strong>Status:</strong> Paid
            </div>
        </div>

        <div class="info-section">
            <div class="info-box">
                <h3>Sold By:</h3>
                <strong>StyleMate E-Commerce</strong><br>
                123 Tech Park, Silicon Valley<br>
                Email: support@StyleMate.com<br>
                Phone: +91 9876543210
            </div>
            <div class="info-box">
                <h3>Billed To:</h3>
                <strong><?php echo htmlspecialchars($order['Full_Name']); ?></strong><br>
                Email: <?php echo htmlspecialchars($order['Email']); ?><br>
                Phone: <?php echo htmlspecialchars($order['Phone_Number']); ?>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Unit Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while($item = $items->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['Products_Name']); ?></td>
                    <td>₹<?php echo number_format($item['Unit_Price'], 2); ?></td>
                    <td><?php echo $item['Quantity']; ?></td>
                    <td>₹<?php echo number_format($item['Subtotal'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div class="total-section">
            <p>Subtotal: ₹<?php echo number_format($order['Total_Amount'], 2); ?></p>
            <p>Tax (GST 0%): ₹0.00</p>
            <p class="total-row">Total: ₹<?php echo number_format($order['Total_Amount'], 2); ?></p>
        </div>

        <div class="footer">
            <p>This is a computer-generated invoice and does not require a signature.</p>
            <p>Thank you for shopping with StyleMate!</p>
        </div>
    </div>
</body>
</html>
<?php
$stmt->close();
$item_stmt->close();
?>