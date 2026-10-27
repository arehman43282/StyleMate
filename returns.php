<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'header.php';
?>

<div class="info-container" style="max-width: 1000px; margin: 40px auto; padding: 20px; font-family: 'Open Sans', sans-serif; line-height: 1.8; color: #333;">
    <h1 style="color: #232f3e; border-bottom: 2px solid #f0c14b; padding-bottom: 10px; margin-bottom: 30px;">Returns & Exchanges</h1>
    
    <section style="margin-bottom: 40px;">
        <h2 style="color: #232f3e; font-size: 22px; margin-bottom: 15px;">Return Policy</h2>
        <p>We want you to love what you ordered! If you're not completely satisfied with your purchase, you can return or exchange the product within <strong>7 days</strong> of delivery.</p>
    </section>

    <section style="margin-bottom: 40px;">
        <h2 style="color: #232f3e; font-size: 22px; margin-bottom: 15px;">Eligibility for Returns</h2>
        <p>To be eligible for a return or exchange, the item must be:</p>
        <ul style="list-style-type: none; padding: 0;">
            <li style="margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-left: 4px solid #f0c14b;">Unused and in the same condition that you received it.</li>
            <li style="margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-left: 4px solid #f0c14b;">In its original packaging with all tags intact.</li>
            <li style="margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-left: 4px solid #f0c14b;">Accompanied by the original invoice.</li>
        </ul>
    </section>

    <section style="margin-bottom: 40px;">
        <h2 style="color: #232f3e; font-size: 22px; margin-bottom: 15px;">How to Initiate a Return</h2>
        <p>1. Log in to your account and go to 'My Orders'.<br>
           2. Select the item you wish to return or exchange.<br>
           3. Follow the instructions to schedule a pickup.<br>
           4. Alternatively, you can contact our support team for assistance.</p>
    </section>

    <section style="margin-bottom: 40px;">
        <h2 style="color: #232f3e; font-size: 22px; margin-bottom: 15px;">Refund Process</h2>
        <p>Once we receive and inspect your return, we will process your refund within 5-7 business days. The refund will be credited back to your original payment method or as StyleMate store credit, depending on your preference.</p>
    </section>
</div>

<?php require_once 'footer.php'; ?>
