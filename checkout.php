<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=checkout.php');
    exit();
}

if(empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit();
}

require_once 'db_connect.php';
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$discount = 0;
$coupon_code = '';
if (isset($_SESSION['coupon'])) {
    $coupon_code = $_SESSION['coupon']['code'];
    if ($_SESSION['coupon']['type'] == 'percent') {
        $discount = $subtotal * $_SESSION['coupon']['discount_percent'];
    }
}

// Shipping Logic: Free >= 999, else 30
$shipping_cost = ($subtotal >= 999) ? 0 : 30;
$total = $subtotal - $discount + $shipping_cost;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - StyleMate</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <?php require_once 'header.php'; ?>
    
    <main>
        <div class="checkout-grid-modern">
            <div class="checkout-form-section">
                <div class="cart-header-modern">
                    <h1><?php echo $lang['checkout']; ?></h1>
                </div>

                <form id="checkoutForm" action="process_order.php" method="POST">
                    <!-- Step 1: Shipping Information -->
                    <div class="checkout-step">
                        <div class="step-header">
                            <div class="step-number">1</div>
                            <h3><?php echo $lang['shipping_address']; ?></h3>
                        </div>
                        
                        <div class="form-row-2">
                            <div class="form-group-modern">
                                <label for="name"><?php echo $lang['full_name']; ?></label>
                                <input type="text" id="name" name="name" required placeholder="John Doe">
                            </div>
                            <div class="form-group-modern">
                                <label for="email"><?php echo $lang['email_address']; ?></label>
                                <input type="email" id="email" name="email" required placeholder="john@example.com">
                            </div>
                        </div>

                        <div class="form-group-modern mt-20">
                            <label for="address"><?php echo $lang['address']; ?></label>
                            <input type="text" id="address" name="address" required placeholder="123 Street Name, Apartment, Suite">
                        </div>

                        <div class="form-row-3">
                            <div class="form-group-modern">
                                <label for="city"><?php echo $lang['city']; ?></label>
                                <input type="text" id="city" name="city" required>
                            </div>
                            <div class="form-group-modern">
                                <label for="pincode"><?php echo $lang['pincode']; ?></label>
                                <input type="text" id="pincode" name="pincode" required>
                            </div>
                            <div class="form-group-modern">
                                <label for="phone"><?php echo $lang['phone_number']; ?></label>
                                <input type="tel" id="phone" name="phone" maxlength="10" required oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Payment Method -->
                    <div class="checkout-step">
                        <div class="step-header">
                            <div class="step-number">2</div>
                            <h3><?php echo $lang['payment_method']; ?></h3>
                        </div>
                        
                        <div class="payment-methods-container">
                            <!-- Stripe Card Option -->
                            <label class="payment-option-modern">
                                <input type="radio" id="card" name="payment_method" value="card">
                                <div class="payment-option-content">
                                    <span class="payment-option-title"><?php echo $lang['credit_debit_card']; ?></span>
                                    <span class="payment-option-desc"><?php echo $lang['secure_stripe']; ?></span>
                                </div>
                                <i class='bx bx-credit-card payment-icon'></i>
                            </label>

                            <div id="stripe-element-container" class="stripe-element-container">
                                <div id="stripe-loading" style="text-align: center; padding: 20px; display: none;">
                                    <i class='bx bx-loader-alt bx-spin' style="font-size: 2rem; color: var(--accent);"></i>
                                    <p style="margin-top: 10px; font-size: 14px; color: var(--text-muted);"><?php echo $lang['secure_payment_loading']; ?></p>
                                </div>
                                <div id="payment-element">
                                    <!-- Stripe Elements will be inserted here -->
                                </div>
                                <div id="payment-message" style="color: #dc3545; margin-top: 10px; font-size: 14px; text-align: center;"></div>
                            </div>

                            <label class="payment-option-modern">
                                <input type="radio" id="cod" name="payment_method" value="cod" checked>
                                <div class="payment-option-content">
                                    <span class="payment-option-title"><?php echo $lang['cash_on_delivery']; ?></span>
                                    <span class="payment-option-desc"><?php echo (isset($lang['pay_on_receive']) ? $lang['pay_on_receive'] : 'Pay when you receive'); ?></span>
                                </div>
                                <i class='bx bx-money payment-icon'></i>
                            </label>
                            
                            <label class="payment-option-modern">
                                <input type="radio" id="upi" name="payment_method" value="upi">
                                <div class="payment-option-content">
                                    <span class="payment-option-title"><?php echo $lang['upi_payment']; ?></span>
                                    <span class="payment-option-desc"><?php echo (isset($lang['instant_secure']) ? $lang['instant_secure'] : 'Instant & Secure'); ?></span>
                                </div>
                                <i class='bx bx-qr-scan payment-icon'></i>
                            </label>
                            
                            <?php
                            // Check wallet balance
                            $wallet_bal = 0;
                            if (isset($_SESSION['user_id'])) {
                                $w_sql = "SELECT amount FROM wallet WHERE user_id = ?";
                                $w_stmt = $conn->prepare($w_sql);
                                $w_stmt->bind_param("i", $_SESSION['user_id']);
                                $w_stmt->execute();
                                $w_res = $w_stmt->get_result();
                                if ($w_row = $w_res->fetch_assoc()) {
                                    $wallet_bal = $w_row['amount'];
                                }
                            }
                            ?>
                            <label class="payment-option-modern <?php echo ($wallet_bal < $total) ? 'disabled' : ''; ?>">
                                <input type="radio" id="wallet" name="payment_method" value="wallet" <?php echo ($wallet_bal < $total) ? 'disabled' : ''; ?>>
                                <div class="payment-option-content">
                                    <span class="payment-option-title"><?php echo $lang['wallet_balance']; ?> (₹<?php echo number_format($wallet_bal, 2); ?>)</span>
                                    <?php if ($wallet_bal < $total): ?>
                                        <span class="payment-option-desc" style="color: #ff4444;"><?php echo (isset($lang['insufficient_balance']) ? $lang['insufficient_balance'] : 'Insufficient balance'); ?></span>
                                    <?php else: ?>
                                        <span class="payment-option-desc"><?php echo (isset($lang['fast_easy']) ? $lang['fast_easy'] : 'Fast & Easy'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <i class='bx bx-wallet payment-icon'></i>
                            </label>

                            <div id="upi-section" class="upi-section">
                                <div class="form-group-modern">
                                    <label for="upi_id"><?php echo (isset($lang['your_upi_id']) ? $lang['your_upi_id'] : 'Your UPI ID'); ?></label>
                                    <input type="text" id="upi_id" name="upi_id" placeholder="example@upi">
                                </div>
                                <p style="font-size:12px; color: var(--text-muted); margin-top: 10px;">
                                    <i class='bx bx-info-circle'></i> <?php echo (isset($lang['upi_confirm_desc']) ? $lang['upi_confirm_desc'] : 'You will confirm payment in your UPI app.'); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-footer-modern">
                        <a href="index.php" class="back-link">
                            <i class='bx bx-home-alt-2' style="margin-right: 8px; font-size: 18px;"></i>
                            <?php echo (isset($lang['back_to_home']) ? $lang['back_to_home'] : 'Back to Home'); ?>
                        </a>
                        <button type="submit" id="submit-button" class="place-order-btn-modern">
                            <span id="button-text"><?php echo (isset($lang['place_order']) ? $lang['place_order'] : 'Place Order'); ?></span>
                            <div id="spinner" class="spinner" style="display: none;"></div>
                        </button>
                    </div>
                </form>
            </div>

            <div class="order-review-modern">
                <h2 class="summary-title"><?php echo (isset($lang['review_your_bag']) ? $lang['review_your_bag'] : 'Review Your Bag'); ?></h2>
                
                <div class="summary-items">
                    <?php
                    foreach ($_SESSION['cart'] as $cart_key => $item):
                        $product_id = isset($item['product_id']) ? $item['product_id'] : 0;
                        $subtotal = $item['price'] * $item['quantity'];
                        
                        // Dynamically detect image column
                        $img_col = 'image';
                        $cols_res = $conn->query("SHOW COLUMNS FROM products");
                        if ($cols_res) {
                            $all_cols = [];
                            while($c_row = $cols_res->fetch_assoc()) { $all_cols[] = $c_row['Field']; }
                            foreach(['image','image_url','product_image','img','Image'] as $candidate) {
                                if (in_array($candidate, $all_cols)) { $img_col = $candidate; break; }
                            }
                        }

                        $img_sql = "SELECT $img_col FROM products WHERE Products_ID = ?";
                        $img_stmt = $conn->prepare($img_sql);
                        if ($img_stmt) {
                            $img_stmt->bind_param("i", $product_id);
                            $img_stmt->execute();
                            $img_res = $img_stmt->get_result();
                            $product_img = 'placeholder.svg';
                            if ($img_row = $img_res->fetch_assoc()) {
                                $product_img = $img_row[$img_col] ?: 'placeholder.svg';
                            }
                            $img_stmt->close();
                        } else {
                            $product_img = 'placeholder.svg';
                        }
                    ?>
                    <div class="review-item-modern">
                        <img src="images/products/<?php echo htmlspecialchars($product_img); ?>" 
                             alt="" 
                             class="review-item-img"
                             onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1525507119028-ed4c629a60a3?auto=format&fit=crop&w=600&q=80'">
                        <div class="review-item-details">
                            <div class="review-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="review-item-meta"><?php echo $lang['quantity']; ?>: <?php echo $item['quantity']; ?></div>
                            <div class="review-item-price">₹<?php echo number_format($subtotal, 2); ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div style="margin-top: 30px;">
                    <div class="summary-row-modern">
                        <span><?php echo $lang['subtotal']; ?></span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    
                    <?php if ($discount > 0): ?>
                    <div class="summary-row-modern" style="color: #4CAF50;">
                        <span><?php echo $lang['discount']; ?> (<?php echo htmlspecialchars($coupon_code); ?>)</span>
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
                        <span><?php echo $lang['total_amount']; ?></span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <div class="summary-features">
                    <div class="feature-row">
                        <i class='bx bx-refresh' style="font-size: 24px; color: var(--accent);"></i>
                        <div style="font-size: 13px;">
                            <strong><?php echo (isset($lang['returns_30_day']) ? $lang['returns_30_day'] : '30-Day Returns'); ?></strong><br>
                            <?php echo (isset($lang['returns_desc']) ? $lang['returns_desc'] : 'Hassle-free returns on all items.'); ?>
                        </div>
                    </div>
                    <div class="feature-row">
                        <i class='bx bx-check-shield' style="font-size: 24px; color: var(--accent);"></i>
                        <div style="font-size: 13px;">
                            <strong><?php echo (isset($lang['quality_guarantee']) ? $lang['quality_guarantee'] : 'Quality Guarantee'); ?></strong><br>
                            <?php echo (isset($lang['quality_desc']) ? $lang['quality_desc'] : 'Authentic premium products.'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require_once 'footer.php'; ?>

    <!-- UPI Payment Modal -->
    <div id="upiModal" class="upi-modal">
        <div class="upi-modal-content">
            <span class="close-modal">&times;</span>
            <div id="paymentContent">
                <div class="upi-header">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/e/e1/UPI-Logo-vector.svg" alt="UPI">
                    <h3 style="margin:0;"><?php echo $lang['pay_with_upi']; ?></h3>
                </div>
                
                <p style="margin:0; color:#666;"><?php echo $lang['scan_qr_code']; ?> <strong>₹<?php echo number_format($total, 2); ?></strong></p>
                
                <div class="qr-container">
                    <div id="qrcode">
                        <!-- We can use a real QR generator service like goqr.me -->
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php 
                            // Simulated UPI link: upi://pay?pa=merchant@upi&pn=StyleMate&am=TOTAL&cu=INR
                            echo urlencode("upi://pay?pa=StyleMate@bank&pn=StyleMate&am=" . $total . "&cu=INR"); 
                        ?>" alt="UPI QR Code">
                    </div>
                </div>

                <div class="upi-timer" id="timer">05:00</div>
                
                <div class="payment-status" id="paymentStatus">
                    <span class="status-spinner"></span>
                    <?php echo $lang['waiting_for_payment']; ?>
                </div>

                <div style="margin-top: 15px; padding: 10px; background: #e8f5e9; border-radius: 8px; border: 1px dashed #4CAF50;">
                    <p style="font-size: 13px; color: #2e7d32; margin-bottom: 8px; font-weight: bold;">
                        <i class="fas fa-flask"></i> <?php echo $lang['demo_mode']; ?>
                    </p>
                    <button type="button" id="simulateSuccessBtn" style="background: #4CAF50; color: white; border: none; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-size: 14px; width: 100%;">
                        <i class="fas fa-check-double"></i> <?php echo $lang['click_to_simulate']; ?>
                    </button>
                    <p style="font-size: 11px; color: #555; margin-top: 5px; text-align: center;">
                        <?php echo $lang['demo_note']; ?>
                    </p>
                </div>

                <div class="upi-apps">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/f/f2/Google_Pay_Logo.svg" class="upi-app-icon" alt="GPay">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/7/71/PhonePe_Logo.svg" class="upi-app-icon" alt="PhonePe">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/24/Paytm_Logo_%28standalone%29.svg" class="upi-app-icon" alt="Paytm">
                </div>
            </div>

            <div id="successContent" style="display:none;">
                <div class="success-animation" style="display:block;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 style="color: #4CAF50;"><?php echo $lang['payment_successful']; ?></h2>
                <p><?php echo $lang['order_processing']; ?></p>
            </div>
        </div>
    </div>

    <script>
        (function(){
            const checkoutForm = document.getElementById('checkoutForm');
            const cod = document.getElementById('cod');
            const upi = document.getElementById('upi');
            const wallet = document.getElementById('wallet');
            const card = document.getElementById('card');
            const upiSection = document.getElementById('upi-section');
            const stripeSection = document.getElementById('stripe-element-container');
            const upiModal = document.getElementById('upiModal');
            const closeModal = document.querySelector('.close-modal');
            const timerDisplay = document.getElementById('timer');
            const paymentStatus = document.getElementById('paymentStatus');
            const paymentContent = document.getElementById('paymentContent');
            const successContent = document.getElementById('successContent');
            const simulateSuccessBtn = document.getElementById('simulateSuccessBtn');
            const submitButton = document.getElementById('submit-button');
            const buttonText = document.getElementById('button-text');
            const spinner = document.getElementById('spinner');
            
            let timerInterval;
            
            // Stripe Variables
            let stripe;
            let elements;
            let paymentElement;
            let isStripeInitialized = false;

            // Handle simulation button
            if (simulateSuccessBtn) {
                simulateSuccessBtn.addEventListener('click', async function() {
                    this.disabled = true;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    try {
                        const response = await fetch('simulate_upi_success.php');
                        const data = await response.json();
                        if (data.status === 'success') {
                            this.innerHTML = '<i class="fas fa-check"></i> Success!';
                            this.style.background = '#28a745';
                        } else {
                            alert('Simulation failed: ' + data.message);
                            this.disabled = false;
                            this.innerHTML = 'Simulate Payment Success';
                        }
                    } catch (e) {
                        console.error('Simulation failed', e);
                        alert('Error connecting to simulation server');
                        this.disabled = false;
                        this.innerHTML = 'Simulate Payment Success';
                    }
                });
            }

            function updateVisibility() {
                if (upiSection) {
                    upiSection.style.display = (upi && upi.checked) ? 'block' : 'none';
                }
                if (stripeSection) {
                    stripeSection.style.display = (card && card.checked) ? 'block' : 'none';
                    if (card && card.checked && !isStripeInitialized) {
                        initializeStripe();
                    }
                }
            }

            if (cod) cod.addEventListener('change', updateVisibility);
            if (upi) upi.addEventListener('change', updateVisibility);
            if (wallet) wallet.addEventListener('change', updateVisibility);
            if (card) card.addEventListener('change', updateVisibility);
            updateVisibility();

            // Initialize Stripe
            async function initializeStripe() {
                const loadingEl = document.getElementById('stripe-loading');
                const messageEl = document.getElementById('payment-message');
                
                if (isStripeInitialized) return;
                
                try {
                    loadingEl.style.display = 'block';
                    messageEl.innerText = '';
                    
                    const response = await fetch('payment/create_payment_intent.php');
                    
                    // Handle non-JSON responses (e.g., PHP errors)
                    if (!response.ok) {
                        const text = await response.text();
                        console.error('Server Error:', text);
                        try {
                            const json = JSON.parse(text);
                            throw new Error(json.error || 'Server returned an error');
                        } catch(e) {
                             throw new Error('Payment server connection failed (' + response.status + ')');
                        }
                    }

                    const data = await response.json();
                    
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    const { clientSecret, publishableKey } = data;

                    if (!publishableKey || !clientSecret) {
                         throw new Error('Invalid configuration from server');
                    }

                    stripe = Stripe(publishableKey);
                    const appearance = { theme: 'stripe' };
                    elements = stripe.elements({ appearance, clientSecret });

                    const paymentElementOptions = { layout: 'tabs' };
                    paymentElement = elements.create('payment', paymentElementOptions);
                    paymentElement.mount('#payment-element');
                    
                    isStripeInitialized = true;
                } catch (e) {
                    console.error('Failed to initialize Stripe:', e);
                    messageEl.innerText = 'Unable to load payment form: ' + e.message;
                } finally {
                    loadingEl.style.display = 'none';
                }
            }

            // Handle Form Submission
            checkoutForm.addEventListener('submit', async function(e) {
                if (upi && upi.checked) {
                    const upiId = document.getElementById('upi_id').value;
                    if (!upiId || !upiId.includes('@')) {
                        e.preventDefault();
                        alert('Please enter a valid UPI ID (e.g., name@bank)');
                        return;
                    }
                    e.preventDefault();
                    initiateUPIPayment();
                } else if (card && card.checked) {
                    e.preventDefault();
                    setLoading(true);

                    const { error, paymentIntent } = await stripe.confirmPayment({
                        elements,
                        confirmParams: {
                            // No return_url needed if we handle redirect manually, but Stripe requires it usually.
                            // We will use redirect: 'if_required' to avoid redirect if not needed.
                            return_url: window.location.href.replace('checkout.php', 'order_success.php'),
                        },
                        redirect: 'if_required'
                    });

                    if (error) {
                        document.getElementById('payment-message').innerText = error.message;
                        setLoading(false);
                    } else if (paymentIntent && paymentIntent.status === 'succeeded') {
                        // Payment succeeded
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'payment_intent_id';
                        input.value = paymentIntent.id;
                        checkoutForm.appendChild(input);
                        
                        checkoutForm.submit();
                    } else {
                        // Unexpected state
                        console.error('Unexpected payment state:', paymentIntent);
                        setLoading(false);
                        alert('Payment status unknown. Please try again.');
                    }
                }
            });

            function setLoading(isLoading) {
                if (isLoading) {
                    submitButton.disabled = true;
                    buttonText.style.display = 'none';
                    spinner.style.display = 'block';
                } else {
                    submitButton.disabled = false;
                    buttonText.style.display = 'block';
                    spinner.style.display = 'none';
                }
            }

            async function initiateUPIPayment() {
                try {
                    const response = await fetch('create_upi_order.php');
                    const data = await response.json();
                    
                    if (data.status === 'success') {
                        // Update QR Code with real API data
                        const qrImg = document.querySelector('#qrcode img');
                        qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(data.upi_link)}`;
                        
                        openUPIModal();
                        startPolling();
                    } else {
                        console.error('UPI Order Creation Failed:', data);
                        alert('Error creating UPI order: ' + (data.message || 'Unknown error'));
                    }
                } catch (error) {
                    console.error('API Error:', error);
                    alert('Could not connect to Payment API. Please check your network connection.');
                }
            }

            function openUPIModal() {
                upiModal.style.display = 'block';
                startTimer(300); // 5 minutes
            }

            function startPolling() {
                const pollInterval = setInterval(async () => {
                    try {
                        const response = await fetch('verify_payment.php');
                        const data = await response.json();

                        if (data.status === 'success') {
                            clearInterval(pollInterval);
                            clearInterval(timerInterval);
                            showSuccess();
                        } else if (data.status === 'error') {
                            clearInterval(pollInterval);
                            clearInterval(timerInterval);
                            alert('Transaction failed: ' + data.message);
                            upiModal.style.display = 'none';
                        }
                    } catch (error) {
                        console.error('Polling Error:', error);
                    }
                }, 2000); // Poll every 2 seconds
            }

            function showSuccess() {
                paymentContent.style.display = 'none';
                successContent.style.display = 'block';
                
                setTimeout(() => {
                    const hiddenPaymentStatus = document.createElement('input');
                    hiddenPaymentStatus.type = 'hidden';
                    hiddenPaymentStatus.name = 'upi_payment_status';
                    hiddenPaymentStatus.value = 'completed';
                    checkoutForm.appendChild(hiddenPaymentStatus);
                    checkoutForm.submit();
                }, 2000);
            }

            function startTimer(duration) {
                let timer = duration, minutes, seconds;
                timerInterval = setInterval(function () {
                    minutes = parseInt(timer / 60, 10);
                    seconds = parseInt(timer % 60, 10);

                    minutes = minutes < 10 ? "0" + minutes : minutes;
                    seconds = seconds < 10 ? "0" + seconds : seconds;

                    timerDisplay.textContent = minutes + ":" + seconds;

                    if (--timer < 0) {
                        clearInterval(timerInterval);
                        alert("Payment timeout. Please try again.");
                        upiModal.style.display = 'none';
                    }
                }, 1000);
            }

            closeModal.onclick = function() {
                if (confirm("Are you sure you want to cancel the payment?")) {
                    upiModal.style.display = "none";
                    clearInterval(timerInterval);
                }
            }

            window.onclick = function(event) {
                if (event.target == upiModal) {
                    // Prevent closing by clicking outside during payment
                }
            }
        })();
    </script>
</body>
</html>
