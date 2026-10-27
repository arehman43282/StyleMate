<?php
session_start();
require_once 'db_connect.php';

// Login check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=wallet.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get wallet balance
$wallet_balance = 0;
$wallet_sql = "SELECT amount FROM wallet WHERE user_id = ?";
$stmt = $conn->prepare($wallet_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $wallet_balance = $row['amount'];
}
$stmt->close();

// Get transaction history (refunds and payments)
// We'll need a way to track wallet payments too. 
// For now, let's show refunds from wallet_refunds.
$transactions = [];

// 1. Get Refunds
$refund_sql = "SELECT wr.amount, wr.refund_date, wr.order_id, 'Refund' as type 
               FROM wallet_refunds wr 
               JOIN Track_Orders t ON wr.order_id = t.Order_ID 
               WHERE t.User_ID = ? 
               ORDER BY wr.refund_date DESC";
$stmt = $conn->prepare($refund_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $transactions[] = [
        'amount' => $row['amount'],
        'date' => $row['refund_date'],
        'description' => (isset($lang['refund_for_order']) ? $lang['refund_for_order'] : 'Refund for Order #') . $row['order_id'],
        'type' => 'credit'
    ];
}
$stmt->close();

// 2. Get Payments made via Wallet (from Payment_Details)
$payment_sql = "SELECT pd.Amount, o.Order_Date, pd.Order_ID, 'Payment' as type 
                FROM Payment_Details pd 
                JOIN Orders o ON pd.Order_ID = o.Order_ID 
                WHERE pd.User_ID = ? AND pd.Payment_Type = 'WALLET'
                ORDER BY o.Order_Date DESC";
$stmt = $conn->prepare($payment_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $transactions[] = [
        'amount' => $row['Amount'],
        'date' => $row['Order_Date'],
        'description' => (isset($lang['payment_for_order']) ? $lang['payment_for_order'] : 'Payment for Order #') . $row['Order_ID'],
        'type' => 'debit'
    ];
}
$stmt->close();

// Sort transactions by date descending
usort($transactions, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo (isset($lang['my_wallet']) ? $lang['my_wallet'] : 'My Wallet'); ?> - StyleMate</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --accent: #000;
            --muted: #666;
            --light-bg: #f9f9f9;
            --border: #eeeeee;
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            --premium-black: #111111;
        }

        @keyframes slideUpFade {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        body {
            background-color: #ffffff;
            font-family: var(--font-sans);
            color: #1a1a1a;
            -webkit-font-smoothing: antialiased;
        }

        .wallet-container {
            max-width: 1000px;
            margin: 0 auto !important;
            padding: 130px 40px 60px !important;
            min-height: 100vh;
            animation: slideUpFade 0.8s ease-out;
            position: relative;
            z-index: 1;
        }

        .wallet-card {
            background: #ffffff;
            border-radius: 40px;
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: 0 40px 100px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            margin-top: 0;
        }

        .wallet-profile-header {
            background: linear-gradient(135deg, #111 0%, #333 100%);
            height: 160px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .profile-avatar-container {
            position: absolute;
            bottom: -45px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 6px solid #ffffff;
            background-color: #fcfcfc;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: var(--premium-black);
            box-shadow: 0 15px 35px rgba(0,0,0,0.08);
        }

        .wallet-body {
            padding: 40px 50px 40px;
            text-align: center;
        }

        .wallet-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--premium-black);
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }

        /* Premium Wallet Card */
        .premium-balance-card {
            background: linear-gradient(135deg, #000000 0%, #222222 100%);
            color: #ffffff;
            padding: 30px 35px;
            border-radius: 20px;
            margin-bottom: 10px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            display: flex;
            justify-content: space-between;
            align-items: center;
            animation: scaleIn 1s cubic-bezier(0.2, 0, 0, 1);
            text-align: left;
            border: 1px solid rgba(255,255,255,0.06);
        }

        .premium-balance-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(231,185,124,0.1) 0%, transparent 70%);
            pointer-events: none;
        }

        .balance-info h2 {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: rgba(255,255,255,0.5);
            margin-bottom: 12px;
            font-weight: 700;
        }

        .balance-display {
            font-size: 42px;
            font-weight: 700;
            letter-spacing: -0.03em;
            line-height: 1;
            margin: 0;
            color: #e7b97c;
            text-shadow: 0 5px 15px rgba(231,185,124,0.1);
        }

        .card-visual {
            text-align: left;
            opacity: 0.9;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .card-visual i {
            font-size: 48px;
            margin-bottom: 10px;
            color: #e7b97c;
            filter: drop-shadow(0 3px 8px rgba(231,185,124,0.2));
        }

        .card-holder-name {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #ffffff;
            font-weight: 700;
            opacity: 0.8;
            margin-right: 0;
        }

        /* Transactions Section */
        .transactions-section {
            background: #ffffff;
            text-align: left;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f5f5f5;
        }

        .section-header h3 {
            font-size: 16px;
            font-weight: 800;
            letter-spacing: -0.01em;
            margin: 0;
            color: var(--premium-black);
        }

        .transaction-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .transaction-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            background: #ffffff;
            border: 1px solid #f9f9f9;
            border-radius: 12px;
            transition: all 0.3s ease;
        }

        .transaction-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.03);
            border-color: #f0f0f0;
        }

        .trans-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .trans-icon {
            width: 38px;
            height: 38px;
            background: #fcfcfc;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            border: 1px solid #f5f5f5;
            transition: 0.3s ease;
        }

        .transaction-item:hover .trans-icon {
            background: #ffffff;
            border-color: #e7b97c;
            color: #e7b97c !important;
        }

        .trans-icon.credit { color: #27ae60; }
        .trans-icon.debit { color: #111111; }

        .trans-meta h4 {
            margin: 0 0 3px 0;
            font-size: 14px;
            font-weight: 700;
            color: var(--premium-black);
        }

        .trans-meta span {
            font-size: 12px;
            color: var(--muted);
            font-weight: 500;
        }

        .trans-amount {
            font-size: 15px;
            font-weight: 800;
            letter-spacing: -0.01em;
        }

        .trans-amount.credit { color: #27ae60; }
        .trans-amount.debit { color: #111111; }

        .empty-state {
            padding: 80px 0;
            text-align: center;
            color: var(--muted);
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        @media (max-width: 768px) {
            .wallet-container { padding: 120px 20px 60px !important; }
            .wallet-body { padding: 40px 20px 30px; }
            .wallet-title { font-size: 20px; }
            .premium-balance-card { padding: 25px; flex-direction: column; align-items: flex-start; gap: 20px; }
            .balance-display { font-size: 32px; }
            .card-visual { align-items: flex-start; }
            .card-visual i { font-size: 36px; }
            .transaction-item { padding: 15px; }
            .trans-amount { font-size: 14px; }
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>

    <div class="wallet-container">
        <div class="wallet-card">
            <div class="wallet-profile-header">
                <div class="profile-avatar-container">
                    <div class="profile-avatar">
                        <i class='bx bx-wallet'></i>
                    </div>
                </div>
            </div>
            
            <div class="wallet-body">
                <h1 class="wallet-title">My Wallet</h1>

                <div class="premium-balance-card">
                    <div class="balance-info">
                        <h2>Total Available Balance</h2>
                        <p class="balance-display">₹<?php echo number_format($wallet_balance, 2); ?></p>
                    </div>
                    <div class="card-visual">
                        <i class='bx bx-chip'></i>
                        <div class="card-holder-name">
                            <?php echo htmlspecialchars($_SESSION['username']); ?>
                        </div>
                    </div>
                </div>

                <div class="transactions-section">
                    <div class="section-header">
                        <h3>Transaction History</h3>
                    </div>

                    <div class="transaction-list">
                        <?php if (empty($transactions)): ?>
                            <div class="empty-state">
                                <i class='bx bx-receipt'></i>
                                <p>No transactions recorded yet.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($transactions as $trans): ?>
                                <div class="transaction-item">
                                    <div class="trans-left">
                                        <div class="trans-icon <?php echo $trans['type']; ?>">
                                            <i class='bx <?php echo $trans['type'] === 'credit' ? 'bx-plus-circle' : 'bx-minus-circle'; ?>'></i>
                                        </div>
                                        <div class="trans-meta">
                                            <h4><?php echo htmlspecialchars($trans['description']); ?></h4>
                                            <span><?php echo date('F d, Y', strtotime($trans['date'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="trans-amount <?php echo $trans['type']; ?>">
                                        <?php echo $trans['type'] === 'credit' ? '+' : '-'; ?> ₹<?php echo number_format($trans['amount'], 2); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
