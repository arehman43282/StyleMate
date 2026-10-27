<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Enforce login requirement
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=cart.php");
    exit;
}

$pageTitle = "Shopping Cart - StyleMate";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <?php require_once 'header.php'; ?>
    
    <main>
        <div class="cart-page-container">
            <div class="cart-items-section">
                <div class="cart-header-modern">
                    <h1><?php echo (isset($lang['shopping_cart']) ? $lang['shopping_cart'] : 'Shopping Bag'); ?></h1>
                </div>
                
                <?php if (!empty($_SESSION['cart'])): ?>
                    <?php 
                    $total = 0;
                    foreach ($_SESSION['cart'] as $product_id => $item): 
                        $total += $item['price'] * $item['quantity'];
                    ?>
                        <div class="cart-item-modern">
                            <div class="cart-item-image">
                                <?php 
                                // Fetch image from DB or use fallback
                                // Dynamically detect image column to avoid "Unknown column" errors
                                $img_col = 'image'; // default
                                $cols_res = $conn->query("SHOW COLUMNS FROM products");
                                if ($cols_res) {
                                    $all_cols = [];
                                    while($c_row = $cols_res->fetch_assoc()) { $all_cols[] = $c_row['Field']; }
                                    foreach(['image','image_url','product_image','img','Image'] as $candidate) {
                                        if (in_array($candidate, $all_cols)) { $img_col = $candidate; break; }
                                    }
                                }

                                $img_sql = "SELECT $img_col FROM products WHERE Products_ID = '$product_id'";
                                $img_res = $conn->query($img_sql);
                                $img_url = 'https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?auto=format&fit=crop&w=600&q=80';
                                if ($img_res && $img_row = $img_res->fetch_assoc()) {
                                    if (!empty($img_row[$img_col])) {
                                        $img_url = 'images/products/' . $img_row[$img_col];
                                    }
                                }
                                ?>
                                <img src="<?php echo $img_url; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?auto=format&fit=crop&w=600&q=80'">
                            </div>
                            
                            <div class="cart-item-info">
                                <a href="product_detail.php?id=<?php echo $product_id; ?>" class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></a>
                                <div class="cart-item-price">₹<?php echo number_format($item['price'] ?? 0.00, 2); ?></div>
                                
                                <div class="cart-item-actions">
                                    <div class="quantity-selector-modern">
                                        <button class="q-btn" onclick="updateQuantity('<?php echo $product_id; ?>', 'decrease')">−</button>
                                        <div class="q-count"><?php echo $item['quantity']; ?></div>
                                        <button class="q-btn" onclick="updateQuantity('<?php echo $product_id; ?>', 'increase')">+</button>
                                    </div>
                                    <span class="remove-link" onclick="removeItem('<?php echo $product_id; ?>')"><?php echo $lang['remove']; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state-modern">
                        <i class='bx bx-shopping-bag'></i>
                        <h2><?php echo $lang['your_cart_is_empty']; ?></h2>
                        <p><?php echo (isset($lang['empty_cart_desc']) ? $lang['empty_cart_desc'] : "Look like you haven't added anything to your bag yet."); ?></p>
                        <a href="index.php" class="premium-btn primary"><?php echo $lang['start_shopping']; ?></a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($_SESSION['cart'])): ?>
                <div class="cart-summary-modern">
                    <h2 class="summary-title"><?php echo $lang['order_summary']; ?></h2>
                    
                    <?php 
                        $subtotal = $total;
                        $discount = 0;
                        $coupon_code = '';
                        
                        // Apply Coupon
                        if (isset($_SESSION['coupon'])) {
                            $coupon_code = $_SESSION['coupon']['code'];
                            if ($_SESSION['coupon']['type'] == 'percent') {
                                $discount = $subtotal * $_SESSION['coupon']['discount_percent'];
                            }
                        }

                        // Shipping Logic
                        $shipping_cost = ($subtotal >= 999) ? 0 : 30; // Free above 999, else 30
                        $final_total = $subtotal - $discount + $shipping_cost;
                    ?>

                    <div class="summary-row-modern">
                        <span><?php echo $lang['subtotal']; ?></span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>

                    <?php if ($discount > 0): ?>
                    <div class="summary-row-modern" style="color: #4CAF50;">
                        <span><?php echo isset($lang['discount']) ? $lang['discount'] : 'Discount'; ?> (<?php echo htmlspecialchars($coupon_code); ?>)</span>
                        <span>-₹<?php echo number_format($discount, 2); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="summary-row-modern">
                        <span><?php echo $lang['shipping']; ?></span>
                        <span>
                            <?php if ($shipping_cost == 0): ?>
                                <span style="color: #4CAF50;"><?php echo (isset($lang['free']) ? $lang['free'] : 'FREE'); ?></span>
                            <?php else: ?>
                                ₹<?php echo number_format($shipping_cost, 2); ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="summary-row-modern total">
                        <span><?php echo $lang['total']; ?></span>
                        <span>₹<?php echo number_format($final_total, 2); ?></span>
                    </div>

                    <!-- Coupon Input -->
                    <div class="coupon-section" style="margin-bottom: 20px;">
                        <?php if (isset($_SESSION['coupon'])): ?>
                             <div class="applied-coupon" style="background: #e8f5e9; padding: 10px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; border: 1px dashed #4CAF50;">
                                <span style="color: #2e7d32; font-size: 14px;"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($_SESSION['coupon']['code']); ?> applied</span>
                                <button onclick="removeCoupon()" style="background: none; border: none; color: #d32f2f; cursor: pointer; font-size: 12px; text-decoration: underline;">Remove</button>
                            </div>
                        <?php else: ?>
                            <div class="coupon-input-group" style="display: flex; gap: 10px;">
                                <input type="text" id="coupon_code" placeholder="Enter coupon code" style="flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                                <button onclick="applyCoupon()" style="background: #333; color: #fff; border: none; padding: 0 15px; border-radius: 4px; cursor: pointer;">Apply</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <a href="checkout.php" class="checkout-btn-modern"><?php echo (isset($lang['proceed_to_checkout']) ? $lang['proceed_to_checkout'] : 'Proceed to Checkout'); ?></a>
                    
                    <div class="secure-checkout-note">
                        <p>
                            <i class='bx bx-shield-quarter'></i> 
                            <?php echo (isset($lang['secure_checkout_desc']) ? $lang['secure_checkout_desc'] : 'Secure checkout with encrypted payment processing.'); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>


<script>
    const cartLang = {
        confirmRemove: "<?php echo $lang['confirm_remove_cart']; ?>",
        failedUpdate: "<?php echo $lang['failed_update_cart']; ?>",
        errorUpdate: "<?php echo $lang['error_update_cart']; ?>",
        failedRemove: "<?php echo $lang['failed_remove_item']; ?>",
        errorRemove: "<?php echo $lang['error_remove_item']; ?>"
    };

function updateQuantity(productId, action) {
    const formData = new FormData();
    formData.append('product_id', productId);
    formData.append('action', action);

    fetch('update_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || cartLang.failedUpdate);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert(cartLang.errorUpdate);
    });
}

function removeItem(productId) {
    console.log('Attempting to remove product with ID:', productId);
    if (confirm(cartLang.confirmRemove)) {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('action', 'remove');

        fetch('update_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(cartLang.failedRemove);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(cartLang.errorRemove);
        });
    }
}

function addToCart(productId, name, price) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&name=${encodeURIComponent(name)}&price=${price}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update cart count display
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                // cartCountElement.textContent = data.cartCount;
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function applyCoupon() {
    const code = document.getElementById('coupon_code').value;
    if (!code) {
        alert('Please enter a coupon code');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'apply_coupon');
    formData.append('coupon_code', code);

    fetch('update_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while applying the coupon');
    });
}

function removeCoupon() {
    const formData = new FormData();
    formData.append('action', 'remove_coupon');

    fetch('update_cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while removing the coupon');
    });
}

</script>
<?php require_once 'footer.php' ?>
</body>
</html>