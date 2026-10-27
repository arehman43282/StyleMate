<?php
session_start();
require_once 'db_connect.php';

// Load language
$lang_code = isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en';
if (file_exists("lang/$lang_code.php")) {
    include "lang/$lang_code.php";
} else {
    include "lang/en.php";
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang_code; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StyleMate Club | Exclusive Member Benefits</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        .club-hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1556905055-8f358a7a47b2?auto=format&fit=crop&w=1920&q=80');
            background-size: cover;
            background-position: center;
            height: 60vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #fff;
            margin-top: 85px;
        }

        .club-hero-content h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 20px;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .club-hero-content p {
            font-size: 1.5rem;
            max-width: 800px;
            margin: 0 auto;
            opacity: 0.9;
        }

        .benefits-section {
            padding: 80px 0;
            background: #f9f9f9;
        }

        .benefits-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .benefit-card {
            background: #fff;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: transform 0.3s ease;
        }

        .benefit-card:hover {
            transform: translateY(-10px);
        }

        .benefit-icon {
            font-size: 3rem;
            color: #000;
            margin-bottom: 20px;
        }

        .benefit-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .benefit-desc {
            color: #666;
            line-height: 1.6;
        }

        .join-section {
            padding: 100px 20px;
            text-align: center;
            background: #fff;
        }

        .join-code {
            display: inline-block;
            background: #000;
            color: #fff;
            padding: 15px 30px;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 50px;
            margin: 30px 0;
            letter-spacing: 1px;
        }

        .note {
            font-size: 0.9rem;
            color: #888;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>

    <?php
    $is_premium = false;
    $membership_expiry = '';
    if (isset($_SESSION['user_id'])) {
        $u_id = $_SESSION['user_id'];
        $chk_sql = "SELECT is_premium, membership_expiry FROM users WHERE User_ID = ?";
        $stmt = $conn->prepare($chk_sql);
        $stmt->bind_param("i", $u_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if ($row['is_premium'] == 1 && new DateTime($row['membership_expiry']) > new DateTime()) {
                $is_premium = true;
                $membership_expiry = date('d M Y', strtotime($row['membership_expiry']));
            }
        }
    }
    ?>
    <div class="club-hero">
        <div class="club-hero-content">
            <h1>StyleMate Club</h1>
            <p>Join the elite community of fashion enthusiasts. Unlock exclusive perks, early access, and premium rewards.</p>
            <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
                <div style="background: #4CAF50; color: white; padding: 15px; border-radius: 10px; margin-top: 20px; display: inline-block;">
                    <i class='bx bxs-check-circle'></i> Welcome to the Club! Your membership is active.
                </div>
                <?php if (isset($_GET['order_id'])): ?>
                    <div style="margin-top: 15px;">
                        <a href="membership_receipt.php?order_id=<?php echo intval($_GET['order_id']); ?>" target="_blank" style="
                            display: inline-flex;
                            align-items: center;
                            gap: 10px;
                            background: rgba(255,255,255,0.2);
                            color: #fff;
                            padding: 10px 20px;
                            border-radius: 30px;
                            text-decoration: none;
                            font-weight: 600;
                            font-size: 14px;
                            backdrop-filter: blur(5px);
                            border: 1px solid rgba(255,255,255,0.3);
                            transition: all 0.3s ease;
                        " onmouseover="this.style.background='rgba(255,255,255,0.3)'" onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                            <i class='bx bx-download'></i> Download Receipt
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <section class="benefits-section">
        <div class="container">
            <h2 style="text-align: center; margin-bottom: 60px; font-size: 2.5rem;">Member Privileges</h2>
            <div class="benefits-grid">
                <div class="benefit-card">
                    <i class='bx bxs-discount benefit-icon'></i>
                    <h3 class="benefit-title">20% Flat Discount</h3>
                    <p class="benefit-desc">Get an instant 20% off on your entire cart value. No minimum purchase required for the discount.</p>
                </div>
                <div class="benefit-card">
                    <i class='bx bxs-truck benefit-icon'></i>
                    <h3 class="benefit-title">Free Priority Shipping</h3>
                    <p class="benefit-desc">Enjoy free shipping on all orders above ₹999. Your orders get priority processing and faster delivery.</p>
                </div>
                <div class="benefit-card">
                    <i class='bx bxs-star benefit-icon'></i>
                    <h3 class="benefit-title">Exclusive Access</h3>
                    <p class="benefit-desc">Be the first to know about new collection drops, seasonal sales, and member-only events.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="join-section">
        <div class="container">
            <h2 style="font-size: 2.5rem; margin-bottom: 20px;">Join the Club</h2>
            <p style="font-size: 1.2rem; color: #666; max-width: 600px; margin: 0 auto 40px;">Upgrade your shopping experience today. One simple membership, endless rewards.</p>
            
            <div style="
                background: linear-gradient(135deg, #000 0%, #1a1a1a 100%);
                color: #fff;
                padding: 50px;
                border-radius: 30px;
                max-width: 500px;
                margin: 0 auto;
                box-shadow: 0 20px 50px rgba(0,0,0,0.2);
                border: 1px solid #333;
                position: relative;
                overflow: hidden;
            ">
                <!-- Decorative Circle -->
                <div style="
                    position: absolute;
                    top: -50px;
                    right: -50px;
                    width: 150px;
                    height: 150px;
                    border-radius: 50%;
                    background: radial-gradient(circle, rgba(255,215,0,0.2) 0%, transparent 70%);
                "></div>

                <h3 style="font-size: 1.5rem; margin-bottom: 15px; color: #a0a0a0;">Annual Membership</h3>
                <div style="font-size: 3.5rem; font-weight: 800; margin-bottom: 10px; line-height: 1;">
                    <span style="font-size: 1.5rem; vertical-align: top; margin-top: 10px; display: inline-block;">₹</span>499
                </div>
                <p style="color: #888; margin-bottom: 30px;">per year</p>
                
                <ul style="text-align: left; margin-bottom: 40px; list-style: none; padding: 0 20px;">
                    <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class='bx bxs-check-circle' style="color: #FFD700;"></i> 20% OFF on all orders
                    </li>
                    <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                        <i class='bx bxs-check-circle' style="color: #FFD700;"></i> Free Priority Shipping > ₹999
                    </li>
                    <li style="display: flex; align-items: center; gap: 10px;">
                        <i class='bx bxs-check-circle' style="color: #FFD700;"></i> Early Access to Sales
                    </li>
                </ul>

                <?php if ($is_premium): ?>
                <button disabled class="premium-btn primary" style="
                    width: 100%;
                    background: #28a745;
                    color: #fff;
                    border: none;
                    padding: 18px;
                    font-size: 1.1rem;
                    font-weight: 700;
                    cursor: default;
                    opacity: 0.9;
                ">
                    <i class='bx bxs-check-shield'></i> Membership Active
                </button>
                <p style="margin-top: 10px; color: #888; font-size: 0.9rem;">Valid until <?php echo $membership_expiry; ?></p>
                <?php else: ?>
                <button onclick="window.location.href='StyleMate_checkout.php'" class="premium-btn primary" style="
                    width: 100%;
                    background: #fff;
                    color: #000;
                    border: none;
                    padding: 18px;
                    font-size: 1.1rem;
                    font-weight: 700;
                    cursor: pointer;
                    transition: transform 0.2s;
                " onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                    Join Now
                </button>
                <?php endif; ?>
            </div>
            
            <p class="note" style="margin-top: 30px;">* Membership valid for 1 year from date of purchase.</p>
        </div>
    </section>

    <?php require_once 'footer.php'; ?>
</body>
</html>
