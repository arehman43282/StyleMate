<?php
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = isset($_POST['name']) ? trim($_POST['name']) : '';
  $username = isset($_POST['username']) ? trim($_POST['username']) : '';
  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  $password = isset($_POST['password']) ? trim($_POST['password']) : '';
  $address = isset($_POST['address']) ? trim($_POST['address']) : '';
  $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

  require_once 'db_connect.php';
  if ($conn->connect_error) {
    $message = 'Database connection failed';
  } else {
    $u = $conn->real_escape_string($username !== '' ? $username : $name);
    $e = $conn->real_escape_string($email);
    $a = $conn->real_escape_string($address);
    $phDigits = preg_replace('/\D+/', '', $phone);
    if ($phDigits === '') { $phDigits = '0'; }
    $phDigits = substr($phDigits, 0, 20);

    // Check if Email, Username, or Phone already exists
    $check_sql = "SELECT Username, Email, Phone_no FROM users WHERE Email='$e' OR Username='$u' OR Phone_no='$phDigits' LIMIT 1";
    $exists_res = $conn->query($check_sql);
    
    if ($exists_res && $exists_res->num_rows > 0) {
      $row = $exists_res->fetch_assoc();
      if ($row['Email'] === $email) {
        $message = 'Email already exists';
      } elseif ($row['Username'] === ($username !== '' ? $username : $name)) {
        $message = 'Username already exists';
      } elseif ($row['Phone_no'] == $phDigits) {
        $message = 'Phone number already exists';
      } else {
        $message = 'User already exists with these details';
      }
      $conn->close();
    } else {
      $hp = password_hash($password, PASSWORD_DEFAULT);
      $res = $conn->query("SELECT IFNULL(MAX(User_ID),0) AS maxid FROM users");
      $nextId = 1;
      if ($res && $res->num_rows) { $nextId = ((int)$res->fetch_assoc()['maxid']) + 1; }
      
      try {
        $sql = "INSERT INTO users (User_ID, Username, Password, Email, Phone_no, Address, role)
                VALUES ($nextId, '$u', '".$conn->real_escape_string($hp)."', '$e', '$phDigits', '$a', 'user')";
        if ($conn->query($sql) === TRUE) {
          $conn->close();
          header('Location: login.php?registered=1');
          exit;
        } else {
          $message = 'Failed to create account: '.$conn->error;
        }
      } catch (mysqli_sql_exception $ex) {
        if (strpos($ex->getMessage(), 'Duplicate entry') !== false) {
          if (strpos($ex->getMessage(), 'Username') !== false) {
            $message = 'Username already exists';
          } elseif (strpos($ex->getMessage(), 'Email') !== false) {
            $message = 'Email already exists';
          } elseif (strpos($ex->getMessage(), 'Phone_no') !== false) {
            $message = 'Phone number already exists';
          } else {
            $message = 'A user with these details already exists';
          }
        } else {
          $message = 'Database error: ' . $ex->getMessage();
        }
      }
      $conn->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Account</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root { 
      --primary-color: #000000; 
      --secondary-color: #333333; 
      --accent-color: #e7b97c;
      --text-color: #1a1a1a; 
      --transition: all 0.4s cubic-bezier(0.23, 1, 0.32, 1);
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    
    body { 
      font-family: 'Inter', sans-serif;
      background: #000;
      color: var(--text-color);
      height: 100vh;
      overflow: hidden;
    }

    .auth-page-wrapper {
      height: 100vh;
      width: 100vw;
      display: flex;
      position: relative;
      background: #000;
    }

    .auth-split { 
      display: grid; 
      grid-template-columns: 450px 1fr; 
      width: 100%; 
      height: 100%;
      background: #fff;
    }

    .auth-left { 
      padding: 40px 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      background: #fff;
      position: relative;
      z-index: 10;
      box-shadow: 20px 0 50px rgba(0,0,0,0.05);
      animation: slideRight 1.2s cubic-bezier(0.23, 1, 0.32, 1);
      overflow-y: auto;
    }

    @keyframes slideRight {
      from { transform: translateX(-100%); }
      to { transform: translateX(0); }
    }

    .auth-right { 
      position: relative; 
      overflow: hidden;
      background: #111;
      display: block;
      min-height: 100%;
    }

    .auth-right img { 
      width: 100%; 
      height: 100%; 
      object-fit: cover; 
      opacity: 1;
      display: block;
      background-repeat: no-repeat;
      background-size: cover;
      animation: slowZoom 20s infinite alternate;
    }

    @keyframes slowZoom {
      from { transform: scale(1); }
      to { transform: scale(1.15); }
    }

    .auth-right::after {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: linear-gradient(to right, rgba(0,0,0,0.4), transparent 40%);
      pointer-events: none;
    }

    .hero-content { 
      position: absolute; 
      bottom: 80px; 
      left: 80px; 
      right: 80px;
      color: #fff; 
      z-index: 2;
      animation: fadeInUp 1s ease-out 0.8s backwards;
    }

    .hero-content h2 { 
      font-size: 48px;
      margin-bottom: 20px; 
      font-weight: 800;
      letter-spacing: -1.5px;
      line-height: 1.1;
    }

    .hero-content p { 
      font-size: 18px;
      line-height: 1.6;
      opacity: 0.9; 
      max-width: 500px;
    }

    .brand-logo-container {
      margin-bottom: 30px;
      animation: fadeIn 1s ease-out 0.4s backwards;
      display: flex;
      justify-content: center;
    }

    .logo-circle {
      width: 90px;
      height: 90px;
      border-radius: 50%;
      border: 3px solid #000;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition);
      background: #fff;
      overflow: hidden;
      padding: 12px;
      box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }

    .logo-circle:hover {
      transform: scale(1.05);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .brand-logo { 
      width: 100%;
      height: auto;
      object-fit: contain;
    }

    .auth-header h1 { 
      font-size: 32px; 
      font-weight: 900;
      margin-bottom: 8px;
      letter-spacing: -1.5px;
      color: #000;
      text-align: center;
      animation: fadeIn 1s ease-out 0.6s backwards;
    }

    .auth-header p {
      color: #666;
      margin-bottom: 30px;
      font-size: 15px;
      text-align: center;
      animation: fadeIn 1s ease-out 0.7s backwards;
    }

    .auth-form {
      animation: fadeIn 1s ease-out 0.8s backwards;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(40px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .form-group { 
      margin-bottom: 16px; 
    }

    .form-group label {
      display: block;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 8px;
      color: #000;
    }

    .input-wrapper {
      position: relative;
    }

    .form-group i { 
      position: absolute; 
      left: 0; 
      top: 50%; 
      transform: translateY(-50%); 
      color: #000;
      font-size: 14px;
    }

    .form-group input, .form-group textarea { 
      width: 100%; 
      padding: 12px 0 12px 30px; 
      border: none;
      border-bottom: 2px solid #e1e1e1;
      font-size: 15px;
      background: transparent;
      transition: var(--transition);
      color: #000;
      font-weight: 500;
    }

    .form-group textarea {
      min-height: 60px;
      resize: none;
    }

    .form-group input:focus, .form-group textarea:focus { 
      border-color: #000; 
      outline: none;
    }

    .auth-button { 
      width: 100%; 
      padding: 16px; 
      background: #000; 
      border: none; 
      color: #fff; 
      font-size: 13px; 
      font-weight: 800; 
      text-transform: uppercase;
      letter-spacing: 2px;
      cursor: pointer; 
      transition: var(--transition);
      margin: 20px 0;
    }

    .auth-button:hover { 
      background: #333;
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .switch-form { 
      text-align: center;
      padding-bottom: 20px;
    }

    .switch-form p { 
      color: #888;
      font-size: 14px;
      margin-bottom: 8px;
    }

    .login-link {
      color: #000;
      text-decoration: none;
      font-weight: 800;
      font-size: 13px;
      text-transform: uppercase;
      letter-spacing: 1px;
      display: inline-block;
      padding-bottom: 2px;
      border-bottom: 2px solid #000;
      transition: var(--transition);
    }

    .login-link:hover {
      opacity: 0.7;
      padding-left: 5px;
    }

    .error-banner { 
      padding: 12px;
      font-size: 12px;
      font-weight: 600;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
      background: #fff5f5; 
      color: #c53030; 
      border-left: 4px solid #c53030;
    }

    @media (max-height: 800px) {
      body { overflow-y: auto; height: auto; }
      .auth-page-wrapper { height: auto; min-height: 100vh; }
      .auth-split { height: auto; min-height: 100vh; }
    }

    @media (max-width: 900px) {
      .auth-split { grid-template-columns: 1fr; }
      .auth-right { display: none; }
      .auth-left { width: 100%; max-width: 100%; padding: 40px 30px; }
    }
  </style>
</head>
<body>
    <div class="auth-page-wrapper">
        <div class="auth-split">
            <section class="auth-left">
                <div class="brand-logo-container">
                    <a href="index.php" class="logo-circle">
                        <img src="StyleMate-removebg-preview.png" alt="StyleMate" class="brand-logo">
                    </a>
                </div>

                <div class="auth-header">
                    <h1>Create Account</h1>
                    <p>Join the StyleMate community today</p>
                </div>

                <form class="auth-form" method="post" action="register.php" novalidate>
                    <?php if ($message): ?>
                        <div class="error-banner">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <div class="input-wrapper">
                            <input type="text" name="name" id="name" required />
                            <i class="fas fa-user"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <input type="text" name="username" id="username" required />
                            <i class="fas fa-id-badge"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" name="email" id="email" required />
                            <i class="fas fa-envelope"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" name="password" id="password" required />
                            <i class="fas fa-lock"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Delivery Address</label>
                        <div class="input-wrapper">
                            <textarea name="address" id="address" required></textarea>
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <div class="input-wrapper">
                            <input type="text" name="phone" id="phone" maxlength="10" required oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)" />
                            <i class="fas fa-phone"></i>
                        </div>
                    </div>

                    <button class="auth-button" type="submit">Create Account</button>

                    <div class="switch-form">
                        <p>Already have an account?</p>
                        <a href="login.php" class="login-link">Sign In</a>
                    </div>
                </form>
            </section>

            <aside class="auth-right">
                <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80" alt="Fashion Editorial">
                <div class="hero-content">
                    <h2>Define Your Aesthetic.</h2>
                    <p>Unlock a world of exclusive fashion and personalized experiences by joining our community.</p>
                </div>
            </aside>
        </div>
    </div>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const form = document.querySelector('form');
      form.addEventListener('submit', (e) => {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        const phone = document.getElementById('phone').value.trim();
        const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        const phoneOk = /^\d{10}$/.test(phone);
        if (!emailOk) { e.preventDefault(); alert('Enter a valid email'); return; }
        if (password.length < 4) { e.preventDefault(); alert('Password must be at least 4 characters'); return; }
        if (!phoneOk) { e.preventDefault(); alert('Phone must be 10 digits'); return; }
      });
    });
  </script>
</body>
</html>
