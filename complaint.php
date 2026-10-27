<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

// Login check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=complaint.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    $order_id = !empty($_POST['order_id']) ? (int)$_POST['order_id'] : null;
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (empty($subject) || empty($message)) {
        $error_msg = "Subject and message are required.";
    } else {
        // Based on debug schema: Complaint_ID, User_ID, Complaint_Text, Date, Status, Resolution
        // We will map Subject and Message into Complaint_Text if needed, or just use Message.
        // Let's combine Subject and Message into Complaint_Text for this specific schema.
        $complaint_text = $subject . ": " . $message;
        $date = date('Y-m-d');
        $status = 'P'; // Pending
        
        $sql = "INSERT INTO Complaint_records (User_ID, Complaint_Text, Date, Status) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("isss", $user_id, $complaint_text, $date, $status);
            if ($stmt->execute()) {
                $success_msg = "Your complaint has been submitted successfully. We will get back to you soon.";
            } else {
                $error_msg = "Error submitting complaint: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_msg = "Database error: Could not prepare statement. Please ensure the 'Complaint_records' table exists with columns (User_ID, Complaint_Text, Date, Status).";
        }
    }
}

// Fetch existing complaints
$complaints = [];
$c_sql = "SELECT * FROM Complaint_records WHERE User_ID = ? ORDER BY Date DESC";
$c_stmt = $conn->prepare($c_sql);

if ($c_stmt) {
    $c_stmt->bind_param("i", $user_id);
    $c_stmt->execute();
    $c_res = $c_stmt->get_result();
    while ($row = $c_res->fetch_assoc()) {
        $complaints[] = $row;
    }
    $c_stmt->close();
}

// Get user's orders for the dropdown
$orders = [];
$o_sql = "SELECT Order_ID, Order_Date FROM Orders WHERE User_ID = ? ORDER BY Order_Date DESC";
$o_stmt = $conn->prepare($o_sql);
if ($o_stmt) {
    $o_stmt->bind_param("i", $user_id);
    $o_stmt->execute();
    $o_res = $o_stmt->get_result();
    while ($row = $o_res->fetch_assoc()) {
        $orders[] = $row;
    }
    $o_stmt->close();
}

$pre_filled_order = isset($_GET['order_id']) ? (int)$_GET['order_id'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Support | StyleMate</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .support-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e1e1e1;
        }
        .support-card h1 {
            margin-top: 0;
            color: #232f3e;
            font-size: 24px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .form-control:focus {
            border-color: #ffd814;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 216, 20, 0.2);
        }
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }
        .btn-submit {
            background: #ffd814;
            border: 1px solid #fcd200;
            color: #0f1111;
            padding: 10px 25px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            font-size: 15px;
            transition: all 0.2s;
        }
        .btn-submit:hover {
            background: #f7ca00;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 25px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .complaints-section h2 {
            font-size: 20px;
            color: #232f3e;
            margin-bottom: 20px;
        }
        .complaint-item {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border: 1px solid #e1e1e1;
        }
        .complaint-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            border-bottom: 1px solid #f5f5f5;
            padding-bottom: 10px;
        }
        .complaint-subject {
            font-weight: 700;
            color: #111;
        }
        .complaint-status {
            font-size: 12px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 12px;
            text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-resolved { background: #d4edda; color: #155724; }
        
        .complaint-body {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
        }
        .complaint-footer {
            margin-top: 10px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="support-card">
            <h1>Submit a Complaint</h1>
            
            <?php if ($success_msg): ?>
                <div class="alert alert-success"><?php echo $success_msg; ?></div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-error"><?php echo $error_msg; ?></div>
            <?php endif; ?>

            <form action="complaint.php" method="POST">
                <div class="form-group">
                    <label for="order_id">Related Order (Optional)</label>
                    <select name="order_id" id="order_id" class="form-control">
                        <option value="">-- Select an order (if applicable) --</option>
                        <?php foreach ($orders as $order): ?>
                            <option value="<?php echo $order['Order_ID']; ?>" <?php echo ($pre_filled_order == $order['Order_ID']) ? 'selected' : ''; ?>>
                                Order #<?php echo $order['Order_ID']; ?> (<?php echo date('d M Y', strtotime($order['Order_Date'])); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" name="subject" id="subject" class="form-control" placeholder="Brief summary of the issue" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Detailed Description</label>
                    <textarea name="message" id="message" class="form-control" placeholder="Please provide as much detail as possible..." required></textarea>
                </div>
                
                <button type="submit" name="submit_complaint" class="btn-submit">Submit Complaint</button>
            </form>Support
        </div>

        <div class="complaints-section">
            <h2>Your Previous Complaints</h2>
            
            <?php if (!empty($complaints)): ?>
                <?php foreach ($complaints as $complaint): ?>
                    <div class="complaint-item">
                        <div class="complaint-header">
                            <span class="complaint-subject"><?php echo htmlspecialchars($complaint['Complaint_Text']); ?></span>
                            <span class="complaint-status status-<?php echo strtolower($complaint['Status']); ?>">
                                <?php echo ($complaint['Status'] == 'P') ? 'Pending' : (($complaint['Status'] == 'R') ? 'Resolved' : $complaint['Status']); ?>
                            </span>
                        </div>
                        <div class="complaint-body">
                            <?php if (!empty($complaint['Resolution'])): ?>
                                <div class="resolution" style="margin-top: 10px; padding: 10px; background: #e8f5e9; border-radius: 4px;">
                                    <strong>Resolution:</strong> <?php echo htmlspecialchars($complaint['Resolution']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="complaint-footer">
                            Submitted on <?php echo date('d M Y', strtotime($complaint['Date'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #666; padding: 20px;">You haven't submitted any complaints yet.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>