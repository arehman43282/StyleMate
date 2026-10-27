<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$error = '';
$success = '';
if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $success = 'Account created. Please sign in.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';
    require_once 'db_connect.php';
    if ($conn->connect_error) {
        $error = 'Database connection failed';
    } else {
        $identifier = trim($email); // This could be email or username
        $p = trim($password);
        
        // Allow login with either Email or Username
        $stmt = $conn->prepare("SELECT User_ID, Username, Email, Password FROM users WHERE Email=? OR Username=? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows === 1) {
                $user = $res->fetch_assoc();
                
                // First try password_verify for hashed passwords
                if (password_verify($p, $user['Password'])) {
                    $passOk = true;
                } 
                // Fallback for old plain text passwords (if any)
                else if ($user['Password'] === $p) {
                    $passOk = true;
                } else {
                    $passOk = false;
                }

                if ($passOk) {
                    $_SESSION['user_id'] = $user['User_ID'];
                    $_SESSION['username'] = $user['Username'];
                    $_SESSION['email'] = $user['Email'];
                    
                    $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
                    header('Location: ' . $redirect);
                    exit;
                } else {
                    $error = 'Invalid email or password';
                }
            } else {
                $error = 'Invalid email or password';
            }
            $stmt->close();
        } else {
            $error = 'Database query failed';
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Login</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root { 
      --primary-color: #000000; 
      --secondary-color: #333333; 
      --accent-color: #e7b97c;
      --glass-bg: rgba(255, 255, 255, 0.85);
      --glass-border: rgba(255, 255, 255, 0.2);
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
      padding: 60px 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      background: #fff;
      position: relative;
      z-index: 10;
      box-shadow: 20px 0 50px rgba(0,0,0,0.05);
      animation: slideRight 1.2s cubic-bezier(0.23, 1, 0.32, 1);
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
      margin-bottom: 50px;
      animation: fadeIn 1s ease-out 0.4s backwards;
      display: flex;
      justify-content: center;
    }

    .logo-circle {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      border: 3px solid #000;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--transition);
      background: #fff;
      overflow: hidden;
      padding: 15px;
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
      font-size: 38px; 
      font-weight: 900;
      margin-bottom: 12px;
      letter-spacing: -1.5px;
      color: #000;
      animation: fadeIn 1s ease-out 0.6s backwards;
    }

    .auth-header p {
      color: #666;
      margin-bottom: 40px;
      font-size: 16px;
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
      margin-bottom: 24px; 
    }

    .form-group label {
      display: block;
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 10px;
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
      font-size: 16px;
    }

    .form-group input { 
      width: 100%; 
      padding: 15px 0 15px 35px; 
      border: none;
      border-bottom: 2px solid #e1e1e1;
      font-size: 16px;
      background: transparent;
      transition: var(--transition);
      color: #000;
      font-weight: 500;
    }

    .form-group input:focus { 
      border-color: #000; 
      outline: none;
    }

    .form-group input::placeholder {
      color: #bbb;
    }

    .toggle-password { 
      position: absolute; 
      right: 0; 
      top: 50%; 
      transform: translateY(-50%); 
      cursor: pointer; 
      color: #999;
      transition: var(--transition);
    }

    .toggle-password:hover {
      color: #000;
    }

    .form-options { 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      margin-bottom: 35px;
      font-size: 14px;
    }

    .remember-me {
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      color: #666;
    }

    .remember-me input {
      width: 18px;
      height: 18px;
      accent-color: #000;
      cursor: pointer;
    }

    .forgot-password { 
      color: #000; 
      text-decoration: none; 
      font-weight: 600;
    }

    .forgot-password:hover { 
      text-decoration: underline;
    }

    .auth-button { 
      width: 100%; 
      padding: 20px; 
      background: #000; 
      border: none; 
      color: #fff; 
      font-size: 14px; 
      font-weight: 800; 
      text-transform: uppercase;
      letter-spacing: 2px;
      cursor: pointer; 
      transition: var(--transition);
      margin-bottom: 30px;
    }

    .auth-button:hover { 
      background: #333;
      transform: translateY(-3px);
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    .switch-form { 
      text-align: center;
    }

    .switch-form p { 
      color: #888;
      font-size: 14px;
      margin-bottom: 10px;
    }

    .create-account-link {
      color: #000;
      text-decoration: none;
      font-weight: 800;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 1px;
      display: inline-block;
      padding-bottom: 2px;
      border-bottom: 2px solid #000;
      transition: var(--transition);
    }

    .create-account-link:hover {
      opacity: 0.7;
      padding-left: 5px;
    }

    .error-banner, .success-banner { 
      padding: 16px;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 30px;
      display: flex;
      align-items: center;
      gap: 12px;
      border-left: 4px solid;
    }

    .error-banner { background: #fff5f5; color: #c53030; border-color: #c53030; }
    .success-banner { background: #f0fff4; color: #2f855a; border-color: #2f855a; }

    /* Custom scrollbar for small heights */
    @media (max-height: 700px) {
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
                    <h1>Login</h1>
                    <p>Enter your account details to continue</p>
                </div>

                <form id="loginForm" class="auth-form" method="post" action="login.php<?php echo isset($_GET['redirect']) ? '?redirect='.htmlspecialchars($_GET['redirect']) : ''; ?>" novalidate>
                    <?php if ($success): ?>
                        <div class="success-banner">
                            <i class="fas fa-check-circle"></i>
                            <span><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="error-banner">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="email">Email / Username</label>
                        <div class="input-wrapper">
                            <input id="email" name="email" type="text" required />
                            <i class="far fa-envelope"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input id="password" name="password" type="password" required />
                            <i class="far fa-lock"></i>
                            <span id="togglePwd" class="toggle-password">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input id="remember" type="checkbox" /> 
                            <span>Stay logged in</span>
                        </label>
                        <a class="forgot-password" href="#">Forgot?</a>
                    </div>

                    <button class="auth-button" type="submit">Sign In</button>

                    <div class="switch-form">
                        <p>No account yet?</p>
                        <a href="register.php" class="create-account-link">Join StyleMate</a>
                    </div>
                </form>
            </section>

            <aside class="auth-right">
                <img src="https://images.unsplash.com/photo-1483985988355-763728e1935b?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80" alt="Fashion Editorial">
                <div class="hero-content">
                    <h2>Elevate Your Style.</h2>
                    <p>Experience the intersection of classic luxury and contemporary design with our latest arrivals.</p>
                </div>
            </aside>
        </div>
    </div>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const emailInput = document.getElementById('email');
      const pwdInput = document.getElementById('password');
      const remember = document.getElementById('remember');
      const togglePwd = document.getElementById('togglePwd');
      const savedEmail = localStorage.getItem('rememberEmail');
      if (savedEmail) { emailInput.value = savedEmail; remember.checked = true; }
      togglePwd.addEventListener('click', () => {
        const t = pwdInput.getAttribute('type');
        pwdInput.setAttribute('type', t === 'password' ? 'text' : 'password');
      });
      document.getElementById('loginForm').addEventListener('submit', (e) => {
        const email = emailInput.value.trim();
        const password = pwdInput.value.trim();
        const emailOk = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        if (!emailOk || password.length < 4) { e.preventDefault(); alert('Enter valid email and password'); return; }
        if (remember.checked) { localStorage.setItem('rememberEmail', email); } else { localStorage.removeItem('rememberEmail'); }
      });
    });
  </script>
</body>
</html>
