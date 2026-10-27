<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'header.php';
?>

<div class="info-container" style="max-width: 1000px; margin: 40px auto; padding: 20px; font-family: 'Open Sans', sans-serif; line-height: 1.8; color: #333;">
    <h1 style="color: #232f3e; border-bottom: 2px solid #f0c14b; padding-bottom: 10px; margin-bottom: 30px;">Shipping Information</h1>
    
    <section style="margin-bottom: 40px;">
        <h2 style="color: #232f3e; font-size: 22px; margin-bottom: 15px;">Shipping Policy</h2>
        <p>At StyleMate, we strive to deliver your fashion choices as quickly and safely as possible. We partner with reliable courier services to ensure your orders reach you in perfect condition.</p>
    </section>

    <section style="margin-bottom: 40px;">
        <h2 style="color: #232f3e; font-size: 22px; margin-bottom: 15px;">Delivery Timelines</h2>
        <ul style="list-style-type: none; padding: 0;">
            <li style="margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-left: 4px solid #f0c14b;"><strong>Standard Delivery:</strong> 4-7 business days across India.</li>
            <li style="margin-bottom: 10px; padding: 10px; background: #f8f9fa; border-left: 4px solid #f0c14b;"><strong>Express Delivery:</strong> 2-3 business days (Available in select metro cities).</li>
        </ul>
        <p><small>*Delivery times may vary depending on your location and external factors like weather or public holidays.</small></p>
    </section>

    <section style="margin-bottom: 40px;">
        <h2 style="color: #232f3e; font-size: 22px; margin-bottom: 15px;">Shipping Charges</h2>
        <p>We offer <strong>FREE shipping</strong> on all orders above ₹999. For orders below ₹999, a flat shipping fee of ₹99 will be applied at checkout.</p>
    </section>

</div>

<?php require_once 'footer.php'; ?>
