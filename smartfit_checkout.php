<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=StyleMate_checkout.php');
    exit();
}

require_once 'db_connect.php';

// Check if already a member
$user_id = $_SESSION['user_id'];
$check_sql = "SELECT is_premium, membership_expiry, Email, Phone_no FROM users WHERE User_ID = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user_data = $res->fetch_assoc();

if ($user_data && $user_data['is_premium'] == 1 && new DateTime($user_data['membership_expiry']) > new DateTime()) {
    // Already a member
    header('Location: StyleMate_club.php');
    exit();
}

$membership_price = 499;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join StyleMate Club - Checkout</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .membership-summary {
            background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
            color: #fff;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        .membership-summary::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 100%;
            background: radial-gradient(circle at center, rgba(255, 215, 0, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>
    
    <main>
        <div class="checkout-grid-modern">
            <div class="checkout-form-section">
                <div class="cart-header-modern">
                    <h1>Complete Membership</h1>
                </div>

                <form id="checkoutForm" action="process_membership.php" method="POST">
                    
                    <!-- Billing Info (Simplified) -->
                    <div class="checkout-step">
                        <div class="step-header">
                            <div class="step-number">1</div>
                            <h3>Billing Details</h3>
                        </div>
                        
                        <div class="form-row-2">
                            <div class="form-group-modern">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" required value="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>">
                            </div>
                            <div class="form-group-modern">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" required value="<?php echo isset($user_data['Email']) ? htmlspecialchars($user_data['Email']) : ''; ?>">
                            </div>
                        </div>
                        
                        <div class="form-row-2" style="margin-top: 20px;">
                            <div class="form-group-modern">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" placeholder="10-digit mobile number" maxlength="10" value="<?php echo isset($user_data['Phone_no']) ? htmlspecialchars($user_data['Phone_no']) : ''; ?>" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="checkout-step">
                        <div class="step-header">
                            <div class="step-number">2</div>
                            <h3>Payment Method</h3>
                        </div>
                        
                        <div class="payment-methods-container">
                            <!-- Stripe Card Option -->
                            <label class="payment-option-modern">
                                <input type="radio" id="card" name="payment_method" value="card">
                                <div class="payment-option-content">
                                    <span class="payment-option-title">Credit / Debit Card</span>
                                    <span class="payment-option-desc">Secure payment via Stripe</span>
                                </div>
                                <i class='bx bx-credit-card payment-icon'></i>
                            </label>

                            <div id="stripe-element-container" class="stripe-element-container">
                                <div id="stripe-loading" style="text-align: center; padding: 20px; display: none;">
                                    <i class='bx bx-loader-alt bx-spin' style="font-size: 2rem; color: var(--accent);"></i>
                                    <p style="margin-top: 10px; font-size: 14px; color: var(--text-muted);">Loading secure payment...</p>
                                </div>
                                <div id="payment-element">
                                    <!-- Stripe Elements will be inserted here -->
                                </div>
                                <div id="payment-message" style="color: #dc3545; margin-top: 10px; font-size: 14px; text-align: center;"></div>
                            </div>
                            
                            <label class="payment-option-modern">
                                <input type="radio" id="upi" name="payment_method" value="upi">
                                <div class="payment-option-content">
                                    <span class="payment-option-title">UPI Payment</span>
                                    <span class="payment-option-desc">Google Pay, PhonePe, Paytm</span>
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
                            <label class="payment-option-modern <?php echo ($wallet_bal < $membership_price) ? 'disabled' : ''; ?>">
                                <input type="radio" id="wallet" name="payment_method" value="wallet" <?php echo ($wallet_bal < $membership_price) ? 'disabled' : ''; ?>>
                                <div class="payment-option-content">
                                    <span class="payment-option-title">Wallet Balance (₹<?php echo number_format($wallet_bal, 2); ?>)</span>
                                    <?php if ($wallet_bal < $membership_price): ?>
                                        <span class="payment-option-desc" style="color: #ff4444;">Insufficient balance</span>
                                    <?php else: ?>
                                        <span class="payment-option-desc">Fast & Easy</span>
                                    <?php endif; ?>
                                </div>
                                <i class='bx bx-wallet payment-icon'></i>
                            </label>

                            <div id="upi-section" class="upi-section">
                                <div class="form-group-modern">
                                    <label for="upi_id">Your UPI ID</label>
                                    <input type="text" id="upi_id" name="upi_id" placeholder="example@upi">
                                </div>
                                <p style="font-size:12px; color: var(--text-muted); margin-top: 10px;">
                                    <i class='bx bx-info-circle'></i> You will confirm payment in your UPI app.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="checkout-footer-modern">
                        <a href="StyleMate_club.php" class="back-link">
                            <i class='bx bx-arrow-back' style="margin-right: 8px; font-size: 18px;"></i>
                            Back
                        </a>
                        <button type="submit" id="submit-button" class="place-order-btn-modern">
                            <span id="button-text">Pay ₹<?php echo $membership_price; ?></span>
                            <div id="spinner" class="spinner" style="display: none;"></div>
                        </button>
                    </div>
                </form>
            </div>

            <div class="order-review-modern">
                <h2 class="summary-title">Order Summary</h2>
                
                <div class="membership-summary">
                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                        <i class='bx bxs-crown' style="font-size: 24px; color: #FFD700;"></i>
                        <h3 style="margin: 0; font-size: 18px;">StyleMate Club</h3>
                    </div>
                    <p style="margin: 0; font-size: 14px; opacity: 0.8;">1 Year Premium Membership</p>
                </div>

                <div class="summary-totals">
                    <div class="summary-row">
                        <span>Membership Fee</span>
                        <span>₹<?php echo number_format($membership_price, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax</span>
                        <span>₹0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Pay</span>
                        <span>₹<?php echo number_format($membership_price, 2); ?></span>
                    </div>
                </div>
                
                <div style="margin-top: 30px; font-size: 13px; color: #666; text-align: center;">
                    <i class='bx bx-lock-alt'></i> Secure Payment Processing
                </div>
            </div>
        </div>
    </main>

    <?php require_once 'footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkoutForm = document.getElementById('checkoutForm');
            const submitButton = document.getElementById('submit-button');
            const buttonText = document.getElementById('button-text');
            const spinner = document.getElementById('spinner');
            const paymentMethods = document.getElementsByName('payment_method');
            const stripeContainer = document.getElementById('stripe-element-container');
            const upiSection = document.getElementById('upi-section');
            
            let stripe, elements;
            let isStripeInitialized = false;
            
            // Handle Payment Method Selection
            paymentMethods.forEach(method => {
                method.addEventListener('change', function() {
                    // Reset UI
                    stripeContainer.classList.remove('active');
                    upiSection.classList.remove('active');
                    
                    if (this.value === 'card') {
                        stripeContainer.classList.add('active');
                        initializeStripe();
                    } else if (this.value === 'upi') {
                        upiSection.classList.add('active');
                    }
                });
            });

            // Initialize Stripe
            async function initializeStripe() {
                if (isStripeInitialized) return;
                
                const loadingEl = document.getElementById('stripe-loading');
                const messageEl = document.getElementById('payment-message');
                
                try {
                    loadingEl.style.display = 'block';
                    messageEl.innerText = '';
                    
                    const response = await fetch('create_membership_intent.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ amount: <?php echo $membership_price; ?> })
                    });
                    
                    if (!response.ok) {
                        throw new Error('Failed to create PaymentIntent');
                    }
                    
                    const data = await response.json();
                    
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    
                    const { clientSecret, publicKey } = data;
                    
                    stripe = Stripe(publicKey);
                    const appearance = { theme: 'stripe' };
                    elements = stripe.elements({ appearance, clientSecret });
                    
                    const paymentElement = elements.create('payment');
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
                const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
                
                if (!selectedMethod) {
                    e.preventDefault();
                    alert('Please select a payment method.');
                    return;
                }
                
                if (selectedMethod.value === 'card') {
                    e.preventDefault();
                    setLoading(true);
                    
                    if (!stripe || !elements) {
                        alert('Stripe not initialized. Please refresh.');
                        setLoading(false);
                        return;
                    }

                    const { error, paymentIntent } = await stripe.confirmPayment({
                        elements,
                        confirmParams: {
                            return_url: window.location.href.replace('StyleMate_checkout.php', 'StyleMate_club.php?status=success'),
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
                        console.error('Unexpected payment state:', paymentIntent);
                        setLoading(false);
                        alert('Payment status unknown. Please try again.');
                    }
                } else if (selectedMethod.value === 'upi') {
                    const upiId = document.getElementById('upi_id').value.trim();
                    if (!upiId) {
                        e.preventDefault();
                        alert('Please enter your UPI ID.');
                        return;
                    }
                    if (!/^[\w.-]+@[\w.-]+$/.test(upiId)) {
                        e.preventDefault();
                        alert('Please enter a valid UPI ID (e.g., user@bank).');
                        return;
                    }
                    // Allow form submission for UPI
                }
                // For Wallet, allow default submission
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
        });
    </script>
</body>
</html>
