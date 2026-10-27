<footer class="site-footer">
    <div class="footer-content">
        <div class="footer-section">
            <h3><?php echo $lang['about_us']; ?></h3>
            <p><?php echo $lang['footer_tagline']; ?></p>
            <div class="social-links">
                <a href="#"><i class='bx bxl-facebook'></i></a>
                <a href="#"><i class='bx bxl-instagram'></i></a>
                <a href="#"><i class='bx bxl-twitter'></i></a>
                <a href="#"><i class='bx bxl-pinterest'></i></a>
            </div>
        </div>
        <div class="footer-section">
            <h3><?php echo $lang['shop_by']; ?></h3>
            <ul>
                <li><a href="women_fashion.php"><?php echo $lang['women']; ?></a></li>
                <li><a href="men_fashion.php"><?php echo $lang['men']; ?></a></li>
                <li><a href="boys_fashion.php"><?php echo $lang['boys']; ?></a></li>
                <li><a href="girls_fashion.php"><?php echo $lang['girls']; ?></a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3><?php echo $lang['customer_service']; ?></h3>
            <ul>
                <li><a href="track_order.php"><?php echo $lang['track_order']; ?></a></li>
                <li><a href="shipping_info.php"><?php echo $lang['shipping']; ?></a></li>
                <li><a href="returns.php"><?php echo $lang['returns_exchanges'] ?? 'Returns & Exchanges'; ?></a></li>
                <li><a href="contact.php"><?php echo $lang['contact_us']; ?></a></li>
            </ul>
        </div>
        <div class="footer-section contact-info">
            <h3><?php echo $lang['stay_connected']; ?></h3>
            <p><?php echo $lang['subscribe_news']; ?></p>
            <form class="newsletter-form" onsubmit="event.preventDefault();">
                <input type="email" placeholder="<?php echo $lang['enter_email']; ?>">
                <button type="submit"><?php echo $lang['subscribe']; ?></button>
            </form>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="footer-bottom-content">
            <p>&copy; <?php echo date('Y'); ?> StyleMate. <?php echo $lang['all_rights_reserved']; ?>.</p>
            <div class="payment-methods">
                <i class='bx bxl-visa'></i>
                <i class='bx bxl-mastercard'></i>
                <i class='bx bxl-paypal'></i>
            </div>
        </div>
    </div>
    <style>
        .site-footer {
            background: #1a1a1a;
            color: #fff;
            padding: 80px 0 30px;
            margin-top: 80px;
            font-family: 'Inter', sans-serif;
        }
        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1.5fr;
            gap: 40px;
        }
        .footer-section h3 {
            color: #fff;
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .footer-section p {
            color: #999;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .footer-section ul li {
            margin-bottom: 12px;
        }
        .footer-section a {
            color: #999;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        .footer-section a:hover {
            color: #fff;
        }
        .social-links {
            display: flex;
            gap: 15px;
        }
        .social-links a {
            font-size: 20px;
            color: #999;
        }
        .newsletter-form {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .newsletter-form input {
            background: #2a2a2a;
            border: 1px solid #333;
            padding: 10px 15px;
            color: #fff;
            flex: 1;
            font-size: 14px;
            border-radius: 2px;
        }
        .newsletter-form button {
            background: #fff;
            color: #1a1a1a;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            transition: opacity 0.3s;
            border-radius: 2px;
        }
        .newsletter-form button:hover {
            opacity: 0.9;
        }
        .footer-bottom {
            margin-top: 60px;
            padding-top: 30px;
            border-top: 1px solid #333;
        }
        .footer-bottom-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #666;
            font-size: 13px;
        }
        .payment-methods {
            display: flex;
            gap: 15px;
            font-size: 24px;
        }
        @media (max-width: 1024px) {
            .footer-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        @media (max-width: 600px) {
            .footer-content {
                grid-template-columns: 1fr;
            }
            .footer-bottom-content {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
        }
    </style>
</footer>
