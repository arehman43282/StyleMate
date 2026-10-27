<?php
session_start();
require_once 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_username = trim($_POST['username']);
    $new_address = trim($_POST['address']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate username
    if (empty($new_username)) {
        $error = 'Username cannot be empty.';
    } else {
        // Check if new username already exists for another user
        $stmt = $conn->prepare("SELECT User_ID FROM users WHERE Username = ? AND User_ID != ?");
        $stmt->bind_param("si", $new_username, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = 'Username already taken.';
        }
        $stmt->close();
    }

    // Handle password change
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $error = 'Please enter your current password to change it.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New password and confirm password do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT Password FROM users WHERE User_ID = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $db_password = $res->fetch_assoc()['Password'];
            $stmt->close();

            if (!password_verify($current_password, $db_password)) {
                $error = 'Incorrect current password.';
            }
        }
    }

    if (empty($error)) {
        // Update user data
        $update_sql = "UPDATE users SET Username = ?, Address = ? WHERE User_ID = ?";
        $params = "ssi";
        $values = [$new_username, $new_address, $user_id];

        if (!empty($new_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET Username = ?, Address = ?, Password = ? WHERE User_ID = ?";
            $params = "sssi";
            $values = [$new_username, $new_address, $hashed_password, $user_id];
        }

        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param($params, ...$values);

        if ($update_stmt->execute()) {
            $_SESSION['username'] = $new_username; // Update session username
            $message = 'Profile updated successfully!';
            $update_stmt->close(); // Close the update statement

            // Re-fetch user data to display updated info
            $fetch_stmt = $conn->prepare("SELECT Username, Email, Address FROM users WHERE User_ID = ?");
            $fetch_stmt->bind_param("i", $user_id);
            $fetch_stmt->execute();
            $result = $fetch_stmt->get_result();
            $user = $result->fetch_assoc();
            $fetch_stmt->close(); // Close the fetch statement
        } else {
            $error = 'Error updating profile: ' . $conn->error;
            $update_stmt->close(); // Close the update statement if it failed
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Settings</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        :root {
            --premium-accent: #000;
            --premium-hover: #333;
            --premium-bg: #fdfdfd;
            --sidebar-bg: #f9f9f9;
            --border-color: #f0f0f0;
            --text-muted: #999;
        }

        body {
            background-color: var(--premium-bg);
            color: var(--premium-accent);
        }

        .settings-section {
            padding: 120px 0 80px;
            min-height: calc(100vh - 130px);
            display: flex;
            align-items: flex-start;
        }

        .settings-card {
            max-width: 1000px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.03);
            overflow: hidden;
            border: 1px solid var(--border-color);
            display: flex;
        }

        .settings-sidebar {
            width: 260px;
            background: var(--sidebar-bg);
            padding: 40px 15px;
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .sidebar-link {
            padding: 15px 20px;
            border-radius: 12px;
            color: #555;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
        }

        .sidebar-link i {
            font-size: 20px;
            transition: transform 0.3s ease;
        }

        .sidebar-link:hover {
            background: #fff;
            color: var(--premium-accent);
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.02);
        }

        .sidebar-link:hover i {
            transform: scale(1.1);
        }

        .sidebar-link.active {
            background: var(--premium-accent);
            color: #fff;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .sidebar-link.active::after {
            content: '';
            position: absolute;
            right: -15px;
            top: 50%;
            transform: translateY(-50%);
            border-top: 8px solid transparent;
            border-bottom: 8px solid transparent;
            border-left: 8px solid var(--premium-accent);
        }

        .settings-main {
            flex: 1;
            padding: 60px;
            background: #fff;
        }

        .settings-header {
            margin-bottom: 40px;
        }

        .settings-header h2 {
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 10px;
            color: #000;
        }

        .settings-header p {
            color: #777;
            font-size: 15px;
        }

        .form-group-custom {
            margin-bottom: 30px;
        }

        .form-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: var(--text-muted);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: color 0.3s ease;
        }

        .form-group-custom:focus-within .form-label {
            color: var(--premium-accent);
        }

        .form-control {
            width: 100%;
            padding: 16px 20px;
            border-radius: 12px;
            border: 1px solid #f0f0f0;
            font-size: 14px;
            color: #333;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #fafafa;
        }

        .form-control:focus {
            border-color: var(--premium-accent);
            background: #fff;
            box-shadow: 0 8px 20px rgba(0,0,0,0.03);
            outline: none;
            transform: translateY(-2px);
        }

        .btn-save {
            background: var(--premium-accent);
            color: #fff;
            padding: 18px 40px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 15px;
            border: none;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 12px;
            justify-content: center;
        }

        .btn-save:hover {
            background: var(--premium-hover);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 30px rgba(0,0,0,0.12);
        }

        .btn-save:active {
            transform: translateY(-1px) scale(1);
        }

        .alert {
            border-radius: 14px;
            padding: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 35px;
            display: flex;
            align-items: center;
            gap: 15px;
            border: none;
            animation: slideDown 0.5s ease;
        }

        .form-control[readonly] {
            background: #f5f5f5;
            color: #999;
            cursor: not-allowed;
            border-color: #eee;
        }

        textarea.form-control {
            resize: none;
        }

        .password-field {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #ccc;
            font-size: 20px;
            padding: 5px;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--premium-accent);
            transform: translateY(-50%) scale(1.1);
        }

        .settings-footer {
            margin-top: 50px;
            padding-top: 35px;
            border-top: 1px solid var(--border-color);
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border: 1px solid #dcfce7;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fee2e2;
        }

        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
            animation: fadeIn 0.6s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 992px) {
            .settings-card {
                flex-direction: column;
            }
            .settings-sidebar {
                width: 100%;
                flex-direction: row;
                overflow-x: auto;
                padding: 20px;
                border-right: none;
                border-bottom: 1px solid #f0f0f0;
            }
            .sidebar-link {
                white-space: nowrap;
            }
        }

        @media (max-width: 576px) {
            .settings-main {
                padding: 30px 20px;
            }
            .btn-save {
                width: 100%;
            }
        }

        @media (max-width: 768px) {
            .settings-main {
                padding: 30px;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'header.php'; ?>

    <main class="settings-section">
        <div class="container">
            <div class="settings-card">
                <div class="settings-sidebar">
                    <a href="#" class="sidebar-link active" data-section="general">
                        <i class='bx bx-user-circle'></i> General
                    </a>
                    <a href="#" class="sidebar-link" data-section="security">
                        <i class='bx bx-shield-lock'></i> Security
                    </a>
                </div>

                <div class="settings-main">
                    <form action="settings.php" method="POST">
                        <div id="general" class="form-section active">
                            <div class="settings-header">
                                <h2>General Settings</h2>
                                <p>Update your profile information and contact details.</p>
                            </div>

                            <?php if (!empty($message)): ?>
                                <div class="alert alert-success" role="alert">
                                    <i class='bx bxs-check-circle'></i><?php echo $message; ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($error)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <i class='bx bxs-error-circle'></i><?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <div class="form-group-custom">
                                <label for="email" class="form-label">
                                    <i class='bx bx-envelope'></i> Email Address
                                </label>
                                <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($user['Email']); ?>" readonly>
                                <div style="font-size: 12px; color: #999; margin-top: 8px;">Email cannot be changed for security reasons.</div>
                            </div>

                            <div class="form-group-custom">
                                <label for="username" class="form-label">
                                    <i class='bx bx-user'></i> Username
                                </label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>
                            </div>

                            <div class="form-group-custom">
                                <label for="address" class="form-label">
                                    <i class='bx bx-map-pin'></i> Shipping Address
                                </label>
                                <textarea class="form-control" id="address" name="address" rows="4" placeholder="Enter your full shipping address"><?php echo htmlspecialchars($user['Address']); ?></textarea>
                            </div>
                        </div>

                        <div id="security" class="form-section">
                            <div class="settings-header">
                                <h2>Security Preferences</h2>
                                <p>Manage your password and account security.</p>
                            </div>

                            <div class="form-group-custom">
                                <label for="current_password" class="form-label">
                                    <i class='bx bx-lock-alt'></i> Current Password
                                </label>
                                <div class="password-field">
                                    <input type="password" class="form-control" id="current_password" name="current_password" placeholder="Enter current password to verify">
                                    <i class='bx bx-show password-toggle' onclick="togglePassword('current_password', this)"></i>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group-custom">
                                        <label for="new_password" class="form-label">
                                            <i class='bx bx-key'></i> New Password
                                        </label>
                                        <div class="password-field">
                                            <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Min. 6 characters">
                                            <i class='bx bx-show password-toggle' onclick="togglePassword('new_password', this)"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group-custom">
                                        <label for="confirm_password" class="form-label">
                                            <i class='bx bx-shield-quarter'></i> Confirm Password
                                        </label>
                                        <div class="password-field">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Repeat new password">
                                            <i class='bx bx-show password-toggle' onclick="togglePassword('confirm_password', this)"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="settings-footer">
                            <button type="submit" class="btn-save">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        // Tab switching logic
        const sidebarLinks = document.querySelectorAll('.sidebar-link[data-section]');
        const formSections = document.querySelectorAll('.form-section');

        sidebarLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const sectionId = link.getAttribute('data-section');

                // Update active link
                sidebarLinks.forEach(l => l.classList.remove('active'));
                link.classList.add('active');

                // Update active section
                formSections.forEach(section => {
                    section.classList.remove('active');
                    if (section.id === sectionId) {
                        section.classList.add('active');
                    }
                });
            });
        });

        function togglePassword(inputId, icon) {
            const passwordInput = document.getElementById(inputId);
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.replace('bx-show', 'bx-hide');
            } else {
                passwordInput.type = 'password';
                icon.classList.replace('bx-hide', 'bx-show');
            }
        }
    </script>
</body>
</html>