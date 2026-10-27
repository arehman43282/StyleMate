<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_connect.php';

// Login check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=track_order.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Create tracking table for refunds if not exists
$conn->query("CREATE TABLE IF NOT EXISTS wallet_refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNIQUE,
    amount DECIMAL(10,2),
    refund_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Get user info for sidebar
$user_query = "SELECT Username, Email FROM users WHERE User_ID = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// Get user's orders from Track_Orders table
$sql = "SELECT t.Order_ID, t.Order_Date, t.Delivery_Date, t.Status, t.Total_Amount, t.Tracking_Number, t.Delivery_Address, t.Rejected_Reason,
        p.Products_ID, p.Products_Name, p.image,
        pr.Rating_Value as Product_Rating,
        pay.Payment_Type,
        wr.id as refund_id,
        o.Deliveryperson_ID,
        dp.DP_Name,
        dpr.Rating_Value as Delivery_Rating,
        rr.id as return_id,
        rr.status as return_status
        FROM Track_Orders t
        LEFT JOIN products p ON t.Product_ID = p.Products_ID
        LEFT JOIN Products_Ratings pr ON p.Products_ID = pr.Products_ID AND pr.User_ID = ? AND pr.Order_ID = t.Order_ID
        LEFT JOIN Payment_Details pay ON t.Order_ID = pay.Order_ID
        LEFT JOIN wallet_refunds wr ON t.Order_ID = wr.order_id
        LEFT JOIN Orders o ON t.Order_ID = o.Order_ID
        LEFT JOIN Delivery_Person dp ON o.Deliveryperson_ID = dp.Deliveryperson_ID
        LEFT JOIN Delivery_Person_Ratings dpr ON t.Order_ID = dpr.Order_ID AND dpr.User_ID = ?
        LEFT JOIN return_requests rr ON t.Order_ID = rr.order_id
        WHERE t.User_ID = ? 
        ORDER BY t.Order_Date ASC, t.Order_ID ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $order_id = $row['Order_ID'];
    if (!isset($orders[$order_id])) {
        // Calculate if return is allowed (within 7 days of delivery)
        $delivery_date = $row['Delivery_Date'] ? $row['Delivery_Date'] : $row['Order_Date'];
        $delivery_ts = strtotime($delivery_date);
        $current_ts = time();
        $days_since_delivery = floor(($current_ts - $delivery_ts) / (60 * 60 * 24));
        $can_return = ($days_since_delivery <= 7);

        $orders[$order_id] = [
            'date' => $row['Order_Date'],
            'delivery_date' => $row['Delivery_Date'],
            'status' => $row['Status'],
            'total' => $row['Total_Amount'],
            'tracking' => $row['Tracking_Number'],
            'address' => $row['Delivery_Address'],
            'rejected_reason' => $row['Rejected_Reason'] ?? null,
            'payment_type' => $row['Payment_Type'] ?? 'N/A',
            'is_refunded' => !empty($row['refund_id']),
            'return_requested' => !empty($row['return_id']),
            'return_status' => $row['return_status'],
            'can_return' => $can_return,
            'delivery_person_id' => $row['Deliveryperson_ID'],
            'delivery_person_name' => $row['DP_Name'],
            'delivery_rating' => $row['Delivery_Rating'],
            'items' => []
        ];
    }
    if ($row['Products_Name']) {
        $orders[$order_id]['items'][] = [
            'id' => $row['Products_ID'],
            'name' => $row['Products_Name'],
            'image' => $row['image'],
            'user_rating' => $row['Product_Rating']
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Orders | StyleMate</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="track_order.css?v=<?php echo time(); ?>">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="account-page-container">
        <main class="account-main">
            <header class="account-header-top">
                <h1 class="section-title">Track Your Orders</h1>
                <p>Monitor the status of your current and past orders.</p>
            </header>

            <div class="orders-section">
                <?php if (!empty($orders)): ?>
                    <?php foreach ($orders as $oid => $order): ?>
                        <div class="order-card-modern">
                            <div class="order-card-header">
                                <div class="order-meta-grid">
                                    <div class="meta-item">
                                        <span class="meta-label">Order Date</span>
                                        <span class="meta-value"><?php echo date('d M Y', strtotime($order['date'])); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Order ID</span>
                                        <span class="meta-value">#<?php echo str_pad($oid, 4, '0', STR_PAD_LEFT); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Total Amount</span>
                                        <span class="meta-value">₹<?php echo number_format($order['total'], 2); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <span class="meta-label">Status</span>
                                        <span class="order-status-badge status-<?php echo strtolower($order['status']); ?>">
                                            <?php 
                                                $status_map = [
                                                    'P' => 'Pending', 
                                                    'S' => 'Shipped', 
                                                    'D' => 'Delivered', 
                                                    'C' => 'Cancelled',
                                                    'R' => 'Rejected',
                                                    'Assigned' => 'Pending',
                                                    'Pending' => 'Pending',
                                                    'Confirmed' => 'Confirmed',
                                                    'Shipped' => 'Shipped',
                                                    'Delivered' => 'Delivered',
                                                    'Cancelled' => 'Cancelled'
                                                ];
                                                echo htmlspecialchars($status_map[$order['status']] ?? $order['status']);
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="order-card-body">
                                <div class="order-delivery-info">
                                    <div class="info-block">
                                        <i class='bx bx-map-pin'></i>
                                        <div>
                                            <strong>Delivery Address</strong>
                                            <p><?php echo htmlspecialchars($order['address']); ?></p>
                                        </div>
                                    </div>
                                    <?php if (!empty($order['tracking'])): ?>
                                        <div class="info-block">
                                            <i class='bx bx-package'></i>
                                            <div>
                                                <strong>Tracking Number</strong>
                                                <p><?php echo htmlspecialchars($order['tracking']); ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($order['delivery_person_name'])): ?>
                                        <div class="info-block">
                                            <i class='bx bx-user-voice'></i>
                                            <div>
                                                <strong>Delivery Person</strong>
                                                <p><?php echo htmlspecialchars($order['delivery_person_name']); ?></p>
                                                <?php if ($order['delivery_rating']): ?>
                                        <div class="item-rating-stars delivery-stars readonly" title="You rated: <?php echo $order['delivery_rating']; ?> stars">
                                            <?php for($i=1; $i<=5; $i++) echo $i <= $order['delivery_rating'] ? "<i class='bx bxs-star'></i>" : "<i class='bx bx-star'></i>"; ?>
                                        </div>
                                    <?php elseif ($order['status'] === 'D' || $order['status'] === 'Delivered'): ?>
                                        <div class="item-rating-stars delivery-stars" 
                                             onclick="openDeliveryRatingModal(<?php echo $oid; ?>, <?php echo $order['delivery_person_id']; ?>, '<?php echo addslashes($order['delivery_person_name']); ?>')" 
                                             title="Rate Delivery Person">
                                            <?php for($i=1; $i<=5; $i++) echo "<i class='bx bx-star'></i>"; ?>
                                        </div>
                                    <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="order-items-list">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <div class="minimal-item-card order-item">
                                            <div class="item-img">
                                                <img src="images/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     onerror="this.onerror=null;this.src='images/products/placeholder.svg'">
                                            </div>
                                            <div class="item-main">
                                                <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                                <?php if (($order['status'] === 'D' || $order['status'] === 'Delivered')): ?>
                                                    <?php if ($item['user_rating']): ?>
                                                        <div class="item-rating-stars readonly" title="You rated: <?php echo $item['user_rating']; ?> stars">
                                                            <?php 
                                                                $rating = $item['user_rating'];
                                                                for($i=1; $i<=5; $i++) echo $i <= $rating ? "<i class='bx bxs-star'></i>" : "<i class='bx bx-star'></i>"; 
                                                            ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="item-rating-stars" 
                                                             onclick="openRatingModal(<?php echo $oid; ?>, <?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>')" 
                                                             title="Rate Product">
                                                            <?php for($i=1; $i<=5; $i++) echo "<i class='bx bx-star'></i>"; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="item-side">
                                                
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="order-card-footer">
                                <div class="footer-actions">
                                    <?php if ($order['status'] !== 'R' && $order['status'] !== 'Rejected'): ?>
                                        <a href="generate_invoice.php?order_id=<?php echo $oid; ?>" target="_blank" class="footer-btn secondary">
                                            <i class='bx bx-file'></i> Invoice
                                        </a>
                                    <?php else: ?>
                                        <?php if ($order['is_refunded']): ?>
                                            <span class="status-tag success">
                                                <i class='bx bx-check-circle'></i> Refunded to Wallet
                                            </span>
                                        <?php else: ?>
                                            <button class="footer-btn primary" onclick="refundToWallet(<?php echo $oid; ?>, <?php echo $order['total']; ?>)">
                                                <i class='bx bx-wallet'></i> Refund to Wallet
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <a href="complaint.php?order_id=<?php echo $oid; ?>" class="footer-btn secondary">
                                        <i class='bx bx-message-square-detail'></i> Complaint
                                    </a>
                                    <?php if ($order['status'] === 'Delivered' || $order['status'] === 'D'): ?>
                                        <?php if ($order['return_requested']): ?>
                                            <span class="status-tag info">
                                                <i class='bx bx-time-five'></i> Return <?php echo htmlspecialchars($order['return_status']); ?>
                                            </span>
                                        <?php elseif ($order['can_return']): ?>
                                            <button class="footer-btn secondary" onclick="openReturnModal(<?php echo $oid; ?>)">
                                                <i class='bx bx-undo'></i> Return
                                            </button>
                                        <?php else: ?>
                                            <span class="status-tag muted" title="Return window expired (7 days)">
                                                <i class='bx bx-lock-alt'></i> Return Expired
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button class="footer-btn primary" onclick="alert('Order Tracking Details:\nOrder #<?php echo str_pad($oid, 4, '0', STR_PAD_LEFT); ?>\nStatus: <?php echo $status_map[$order['status']] ?? $order['status']; ?>\nTracking: <?php echo $order['tracking'] ?: 'Not Available'; ?>')">
                                        <i class='bx bx-target-lock'></i> Track
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-minimal">
                        <i class='bx bx-package'></i>
                        <h3>No orders yet</h3>
                        <p>You haven't placed any orders. Start shopping now!</p>
                        <a href="index.php" class="btn-primary">Go to Shop</a>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Rating Modal -->
    <div id="ratingModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3 class="modal-title">Rate Product</h3>
            <p id="modalProductName" class="modal-subtitle"></p>
            
            <form id="ratingForm">
                <input type="hidden" name="order_id" id="modalOrderId">
                <input type="hidden" name="products_id" id="modalProductId">
                
                <div class="stars-wrapper">
                    <div class="stars-container">
                        <input type="radio" id="star5" name="rating" value="5" /><label for="star5" title="5 stars"><i class='bx bxs-star'></i></label>
                        <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="4 stars"><i class='bx bxs-star'></i></label>
                        <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="3 stars"><i class='bx bxs-star'></i></label>
                        <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="2 stars"><i class='bx bxs-star'></i></label>
                        <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="1 star"><i class='bx bxs-star'></i></label>
                    </div>
                    <span id="ratingText" class="rating-label-text">Select your rating</span>
                </div>

                <textarea name="review_text" id="reviewText" class="review-textarea" placeholder="Write your review here (optional)..." maxlength="200"></textarea>
                
                <button type="submit" class="submit-rating-btn" id="submitRatingBtn">Submit Review</button>
            </form>
        </div>
    </div>

    <!-- Delivery Rating Modal -->
    <div id="deliveryRatingModal" class="modal">
        <div class="modal-content">
            <span class="close-delivery">&times;</span>
            <h3 class="modal-title">Rate Delivery Person</h3>
            <p id="modalDPName" class="modal-subtitle"></p>
            
            <form id="deliveryRatingForm">
                <input type="hidden" name="order_id" id="modalOrder_Id">
                <input type="hidden" name="deliveryperson_id" id="modalDPId">
                
                <div class="stars-wrapper">
                    <div class="stars-container">
                        <input type="radio" name="delivery_rating" id="dpstar5" value="5"><label for="dpstar5" class='bx bxs-star'></label>
                        <input type="radio" name="delivery_rating" id="dpstar4" value="4"><label for="dpstar4" class='bx bxs-star'></label>
                        <input type="radio" name="delivery_rating" id="dpstar3" value="3"><label for="dpstar3" class='bx bxs-star'></label>
                        <input type="radio" name="delivery_rating" id="dpstar2" value="2"><label for="dpstar2" class='bx bxs-star'></label>
                        <input type="radio" name="delivery_rating" id="dpstar1" value="1"><label for="dpstar1" class='bx bxs-star'></label>
                    </div>
                    <span id="deliveryRatingText" class="rating-label-text">Select your rating</span>
                </div>

                <textarea name="delivery_review_text" id="deliveryReviewText" class="review-textarea" placeholder="Write your feedback about delivery here (optional)..." maxlength="500"></textarea>
                
                <button type="submit" class="submit-rating-btn" id="submitDeliveryRatingBtn">Submit Delivery Rating</button>
            </form>
        </div>
    </div>

    <!-- Return Modal -->
    <div id="returnModal" class="modal">
        <div class="modal-content">
            <span class="close-return">&times;</span>
            <h3 class="return-modal-title">Request Return</h3>
            <p class="return-modal-subtitle">Please provide a reason for your return request.</p>

            <form id="returnForm">
                <input type="hidden" name="order_id" id="returnOrderId">
                <select name="reason_dropdown" id="reasonDropdown" class="reason-select" required>
                    <option value="" disabled selected>Select a reason for return</option>
                    <option value="Defective item">Defective item</option>
                    <option value="Wrong size">Wrong size</option>
                    <option value="Item not as described">Item not as described</option>
                    <option value="Changed my mind">Changed my mind</option>
                    <option value="Other">Other</option>
                </select>
                <textarea name="return_reason" id="returnReason" class="review-textarea" placeholder="Please specify your reason..." maxlength="500"></textarea>
                <button type="submit" class="submit-rating-btn" id="submitReturnBtn">Submit Return Request</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById("ratingModal");
            const deliveryModal = document.getElementById("deliveryRatingModal");
            const returnModal = document.getElementById("returnModal");
            const span = document.getElementsByClassName("close")[0];
            const deliverySpan = document.getElementsByClassName("close-delivery")[0];
            const returnSpan = document.getElementsByClassName("close-return")[0];
            const ratingForm = document.getElementById("ratingForm");
            const deliveryRatingForm = document.getElementById("deliveryRatingForm");
            const returnForm = document.getElementById("returnForm");
            const ratingText = document.getElementById("ratingText");
            const deliveryRatingText = document.getElementById("deliveryRatingText");
            
            const ratingLabels = {
                '5': 'Excellent',
                '4': 'Good',
                '3': 'Average',
                '2': 'Poor',
                '1': 'Very Poor'
            };

            // Define functions on window object to ensure global access from onclick
            window.openRatingModal = function(orderId, productId, productName) {
                document.getElementById("modalOrderId").value = orderId;
                document.getElementById("modalProductId").value = productId;
                document.getElementById("modalProductName").innerText = productName;
                modal.style.display = "block";
            };

            window.openDeliveryRatingModal = function(orderId, dpId, dpName) {
                document.getElementById("modalOrder_Id").value = orderId;
                document.getElementById("modalDPId").value = dpId;
                document.getElementById("modalDPName").innerText = "Delivery by: " + dpName;
                deliveryModal.style.display = "block";
            };

            window.openReturnModal = function(orderId) {
                document.getElementById("returnOrderId").value = orderId;
                returnModal.style.display = "block";
            };

            window.refundToWallet = function(orderId, amount) {
                if (confirm('Are you sure you want to refund ₹' + amount.toFixed(2) + ' to your wallet?')) {
                    fetch('process_wallet_refund.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'order_id=' + orderId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while processing the refund.');
                    });
                }
            };

            if (span) {
                span.onclick = function() {
                    modal.style.display = "none";
                }
            }

            if (deliverySpan) {
                deliverySpan.onclick = function() {
                    deliveryModal.style.display = "none";
                }
            }

            if (returnSpan) {
                returnSpan.onclick = function() {
                    returnModal.style.display = "none";
                }
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
                if (event.target == deliveryModal) {
                    deliveryModal.style.display = "none";
                }
                if (event.target == returnModal) {
                    returnModal.style.display = "none";
                }
            }

            // Handle star label hover/click for product rating
            document.querySelectorAll('#ratingForm .stars-container input[name="rating"]').forEach(input => {
                input.addEventListener('change', (e) => {
                    if (ratingText) ratingText.innerText = ratingLabels[e.target.value];
                });
            });

            // Handle star label hover/click for delivery rating
            document.querySelectorAll('#deliveryRatingForm .stars-container input[name="delivery_rating"]').forEach(input => {
                input.addEventListener('change', (e) => {
                    if (deliveryRatingText) deliveryRatingText.innerText = ratingLabels[e.target.value];
                });
            });

            const reasonDropdown = document.getElementById("reasonDropdown");
            const returnReasonTextarea = document.getElementById("returnReason");

            if (reasonDropdown) {
                reasonDropdown.addEventListener('change', function() {
                    if (this.value === 'Other') {
                        returnReasonTextarea.style.display = 'block';
                        returnReasonTextarea.required = true;
                    } else {
                        returnReasonTextarea.style.display = 'none';
                        returnReasonTextarea.required = false;
                    }
                });
            }

            if (ratingForm) {
                ratingForm.onsubmit = function(e) {
                    e.preventDefault();
                    const formData = new FormData(ratingForm);
                    const submitBtn = document.getElementById("submitRatingBtn");
                    
                    if (!formData.get('rating')) {
                        alert("Please select a star rating!");
                        return;
                    }

                    submitBtn.disabled = true;
                    submitBtn.innerText = "Submitting...";

                    fetch('submit_rating.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert("Rating submitted successfully!");
                            location.reload();
                        } else {
                            alert("Error: " + data.message);
                            submitBtn.disabled = false;
                            submitBtn.innerText = "Submit Review";
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert("An error occurred. Please try again.");
                        submitBtn.disabled = false;
                        submitBtn.innerText = "Submit Review";
                    });
                }
            }

            if (deliveryRatingForm) {
                deliveryRatingForm.onsubmit = function(e) {
                    e.preventDefault();
                    const formData = new FormData(deliveryRatingForm);
                    const submitBtn = document.getElementById("submitDeliveryRatingBtn");
                    
                    if (!formData.get('delivery_rating')) {
                        alert("Please select a star rating for the delivery person!");
                        return;
                    }

                    submitBtn.disabled = true;
                    submitBtn.innerText = "Submitting...";

                    fetch('submit_delivery_rating.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert("Delivery rating submitted successfully!");
                            location.reload();
                        } else {
                            alert("Error: " + data.message);
                            submitBtn.disabled = false;
                            submitBtn.innerText = "Submit Delivery Rating";
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert("An error occurred. Please try again.");
                        submitBtn.disabled = false;
                        submitBtn.innerText = "Submit Delivery Rating";
                    });
                }
            }

            if (returnForm) {
                returnForm.onsubmit = function(e) {
                    e.preventDefault();
                    const orderId = document.getElementById("returnOrderId").value;
                    const dropdownReason = document.getElementById("reasonDropdown").value;
                    const customReason = document.getElementById("returnReason").value;
                    
                    let finalReason = dropdownReason;
                    if (dropdownReason === 'Other') {
                        finalReason = customReason;
                    }

                    if (!finalReason) {
                        alert("Please provide a reason for return.");
                        return;
                    }

                    const submitBtn = document.getElementById("submitReturnBtn");
                    submitBtn.disabled = true;
                    submitBtn.innerText = "Submitting...";

                    fetch('process_return.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'order_id=' + orderId + '&reason=' + encodeURIComponent(finalReason)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert(data.message);
                            returnModal.style.display = "none";
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                            submitBtn.disabled = false;
                            submitBtn.innerText = "Submit Return Request";
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while processing your return request.');
                        submitBtn.disabled = false;
                        submitBtn.innerText = "Submit Return Request";
                    });
                };
            }
        });
    </script>
</body>
</html>