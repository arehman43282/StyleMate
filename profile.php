<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT Username, Email, Address FROM users WHERE User_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo (isset($lang['user_profile']) ? $lang['user_profile'] : 'User Profile'); ?> - <?php echo htmlspecialchars($user['Username']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <style>
        :root {
            --premium-black: #111111;
            --accent-gold: #c5a059;
            --soft-white: #fcfcfc;
            --border-light: #eeeeee;
        }

        body {
            background-color: #ffffff;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: #1a1a1a;
            -webkit-font-smoothing: antialiased;
        }

        .profile-container {
            max-width: 1000px;
            margin: 0 auto !important;
            padding: 130px 40px 60px !important;
            min-height: 100vh;
        }

        .profile-card {
            background: #ffffff;
            border-radius: 32px;
            border: 1px solid var(--border-light);
            overflow: hidden;
            box-shadow: 0 40px 100px rgba(0,0,0,0.03);
            display: flex;
            flex-direction: column;
        }

        .profile-header {
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
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 6px solid #ffffff;
            background-color: var(--soft-white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            color: var(--premium-black);
            box-shadow: 0 15px 45px rgba(0,0,0,0.1);
        }

        .profile-body {
            padding: 70px 60px 50px;
            text-align: center;
        }

        .profile-name {
            font-size: 32px;
            font-weight: 800;
            color: var(--premium-black);
            margin-bottom: 8px;
            letter-spacing: -0.04em;
        }

        .profile-email {
            color: #666;
            font-size: 15px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-weight: 500;
        }

        .profile-email i {
            font-size: 18px;
            color: var(--premium-black);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 10px;
            text-align: left;
        }

        .info-card {
            background: #fcfcfc;
            padding: 25px;
            border-radius: 20px;
            border: 1px solid #f5f5f5;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .info-card:hover {
            background: #ffffff;
            border-color: #000000;
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.04);
        }

        .info-icon {
            width: 44px;
            height: 44px;
            background: #ffffff;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--premium-black);
            font-size: 22px;
            margin-bottom: 15px;
            border: 1px solid var(--border-light);
        }

        .info-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 6px;
        }

        .info-value {
            font-size: 16px;
            color: var(--premium-black);
            font-weight: 700;
        }

        .action-buttons {
            margin-top: 50px;
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .btn-custom {
            padding: 12px 30px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
        }

        .btn-edit {
            background: var(--premium-black);
            color: #ffffff;
            border: 2px solid var(--premium-black);
        }

        .btn-edit:hover {
            background: #333333;
            border-color: #333333;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .btn-logout {
            background: transparent;
            color: #ef4444;
            border: 2px solid #fee2e2;
        }

        .btn-logout:hover {
            background: #fef2f2;
            border-color: #ef4444;
            color: #ef4444;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .info-grid { grid-template-columns: 1fr; }
            .profile-container { padding: 120px 20px 60px !important; }
            .profile-body { padding: 60px 25px 40px; }
            .profile-name { font-size: 28px; }
            .action-buttons { flex-direction: column; }
            .btn-custom { justify-content: center; }
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>

    <div class="container profile-container">
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar-container">
                    <div class="profile-avatar">
                        <i class='bx bx-user'></i>
                    </div>
                </div>
            </div>
            <div class="profile-body">
                <h1 class="profile-name"><?php echo htmlspecialchars($user['Username']); ?></h1>
                <p class="profile-email">
                    <i class='bx bx-envelope'></i>
                    <?php echo htmlspecialchars($user['Email']); ?>
                </p>

                <div class="info-grid">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class='bx bx-map-pin'></i>
                        </div>
                        <label class="info-label"><?php echo (isset($lang['current_address']) ? $lang['current_address'] : 'Current Address'); ?></label>
                        <div class="info-value">
                            <?php echo $user['Address'] ? htmlspecialchars($user['Address']) : (isset($lang['no_address_set']) ? $lang['no_address_set'] : 'No address set'); ?>
                        </div>
                    </div>

                    <div class="info-card">
                        <div class="info-icon">
                            <i class='bx bx-shield-check'></i>
                        </div>
                        <label class="info-label"><?php echo (isset($lang['account_status']) ? $lang['account_status'] : 'Account Status'); ?></label>
                        <div class="info-value">
                            <i class='bx bxs-check-circle' style="color: #27ae60; font-size: 18px; vertical-align: middle; margin-right: 5px;"></i>
                            <?php echo (isset($lang['verified_account']) ? $lang['verified_account'] : 'Verified Account'); ?>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <a href="settings.php" class="btn-custom btn-edit">
                        <i class='bx bx-cog'></i> <?php echo (isset($lang['account_settings']) ? $lang['account_settings'] : 'Account Settings'); ?>
                    </a>
                    <a href="logout.php" class="btn-custom btn-logout">
                        <i class='bx bx-log-out'></i> <?php echo $lang['logout']; ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
