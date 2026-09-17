<?php
include("config/db.php");

$forgot_mode = false;
$success_msg = "";
$error_msg = "";

// 1. PANG-PROSESO NG FORGOT PASSWORD REQUEST
if (isset($_POST['process_forgot'])) {
    $forgot_mode = true;
    $forgot_email = mysqli_real_escape_string($conn, trim($_POST['forgot_email']));
    
    // Tignan kung umiiral ang email sa users table
    $check_q = mysqli_query($conn, "SELECT user_id, first_name FROM users WHERE email='$forgot_email' LIMIT 1");
    
    if ($check_q && mysqli_num_rows($check_q) > 0) {
        $user_row = mysqli_fetch_assoc($check_q);
        $user_id = (int) $user_row['user_id'];
        $user_first_name = trim($user_row['first_name'] ?? '') ?: 'Homeowner';

        // Gumawa ng secure random token, valid lang for 1 hour
        $reset_token = bin2hex(random_bytes(32));
        $expiry      = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // I-load ang mailer helper
        require_once __DIR__ . "/includes/mailer.php";

        // I-build ang reset link papunta sa reset_password.php (same folder ng login.php)
        $protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $host      = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
        $reset_link = $protocol . $host . $base_path . "/reset_password.php?token=" . $reset_token;

        $email_subject = "Password Reset Request - Sapilara Lower Portal";
        $email_body = "
            <div style='font-family:Arial,sans-serif; max-width:500px; margin:0 auto;'>
                <div style='background:linear-gradient(135deg,#1e3a3a,#0f1f22); padding:28px; text-align:center; border-radius:10px 10px 0 0;'>
                    <h2 style='color:#fff; margin:0;'>Sapilara Lower Portal</h2>
                </div>
                <div style='background:#fff; padding:30px; border:1px solid #e5e7eb; border-radius:0 0 10px 10px;'>
                    <p>Hi <b>{$user_first_name}</b>,</p>
                    <p>Nakatanggap kami ng request para i-reset ang password ng iyong account. I-click ang button sa baba para makapag-set ng bagong password:</p>
                    <p style='text-align:center; margin:25px 0;'>
                        <a href='{$reset_link}' style='background:#dc1623; color:#fff; padding:14px 28px; border-radius:6px; text-decoration:none; font-weight:bold; display:inline-block;'>Reset Password</a>
                    </p>
                    <p style='font-size:13px; color:#6b7280;'>O i-copy/paste ang link na ito sa browser mo:<br><span style='word-break:break-all;'>{$reset_link}</span></p>
                    <p>Ang link na ito ay valid lang sa loob ng <b>1 oras</b>.</p>
                    <p style='color:#9ca3af; font-size:13px; margin-top:25px;'>Kung hindi ikaw ang nag-request nito, huwag mo na lang pansinin ang email na ito — ligtas pa rin ang iyong account.</p>
                </div>
            </div>
        ";

        // Tandaan: hindi natin pinapasa ang $user_id/$conn dito kasi security-critical email ito —
        // dapat laging maipadala kahit naka-OFF ang notif_email preference ng user.
        $email_sent = sendHOAEmail($forgot_email, $email_subject, $email_body);

        if ($email_sent) {
            // I-save lang ang token sa DB kapag successful ang pagpadala ng email
            $update_q = mysqli_query($conn, "UPDATE users SET reset_token='$reset_token', reset_token_expiry='$expiry' WHERE user_id=$user_id");

            if ($update_q) {
                $success_msg = "📧 <b>Naipadala na!</b><br><br>Pinadalhan na namin ng reset link ang <b>" . htmlspecialchars($forgot_email) . "</b>. Pakitingnan ang iyong inbox (o spam/junk folder), i-click ang link para makapag-set ka ng bagong password. Valid lang ito sa loob ng 1 oras.";
            } else {
                $error_msg = "Nagkaroon ng problema sa pag-save ng request. Subukan muli.";
            }
        } else {
            $error_msg = "Hindi naipadala ang email. I-check ang SMTP settings sa <code>includes/mailer.php</code>, o subukan muli mamaya.";
        }
    } else {
        $error_msg = "Hindi nahanap ang Email Address na iyan sa aming system. Pakisiguradong tama ang pagkakasulat.";
    }
}

// 2. PANG-PROSESO NG ORDINARYONG LOGIN
if (isset($_POST['login'])) {
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = trim($_POST['password']);

    $q    = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' LIMIT 1");
    $user = mysqli_fetch_assoc($q);

    if ($user && $password == $user['password']) {
        if ($user['status'] != 'active') {
            echo "<script>alert('Your account is not active. Please contact HOA admin.');</script>";
        } else {
            if ($user['role'] == 'admin') {
                session_name('HOA_ADMIN_SESSION');
            } else {
                session_name('HOA_USER_SESSION');
            }
            session_start();

            $_SESSION['user_id']         = $user['user_id'];
            $_SESSION['role']            = $user['role'];
            $_SESSION['first_name']      = $user['first_name'];
            $_SESSION['last_name']       = $user['last_name'];
            $_SESSION['profile_picture'] = $user['profile_picture'] ?? null;

            logActivity($conn, $user['user_id'], "User logged in", "Authentication");

            if ($user['role'] == 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit;
        }
    } else {
        session_name('HOA_USER_SESSION');
        session_start();
        echo "<script>alert('Invalid email or password.');</script>";
    }
} else {
    // I-trigger ang check kung papasok ba sa forgot pass layout via link
    if (isset($_GET['view']) && $_GET['view'] == 'forgot') {
        $forgot_mode = true;
    }
    
    if (!isset($_SESSION)) {
        session_name('HOA_USER_SESSION');
        session_start();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Sapilara Lower Portal</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; }
        body { margin: 0; min-height: 100vh; background: #061514; color: #fff; }
        .auth-page { min-height: 100vh; display: grid; grid-template-columns: 1.2fr 0.9fr; }
        .left-panel {
            position: relative; padding: 35px 45px;
            background: linear-gradient(rgba(5,13,15,.55), rgba(5,13,15,.75)), url("assets/css/uploads/login_bg.png");
            background-size: cover; background-position: center;
        }
        .brand { display: flex; align-items: center; gap: 12px; font-weight: 800; font-size: 20px; line-height: 1.1; }
        .brand img { width: 55px; height: 55px; border-radius: 50%; }
        .hero { margin-top: 90px; max-width: 520px; }
        .hero h1 { font-size: 34px; margin: 0 0 15px; }
        .hero h1 span { color: #ef2b35; }
        .hero p { color: #e5e7eb; line-height: 1.6; }
        .features { margin-top: 35px; display: grid; gap: 20px; }
        .feature { display: flex; gap: 14px; align-items: flex-start; }
        .icon { width: 42px; height: 42px; border-radius: 50%; background: #dc1623; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0; }
        .feature b { display: block; margin-bottom: 5px; }
        .feature small { color: #e5e7eb; line-height: 1.4; }
        .right-panel { display: flex; align-items: center; justify-content: center; background: #071314; padding: 40px; }
        .login-box { width: 100%; max-width: 430px; background: rgba(15,31,34,.92); border: 1px solid #263b40; border-radius: 14px; padding: 35px; box-shadow: 0 20px 60px rgba(0,0,0,.35); }
        .login-box h2 { margin: 0 0 8px; font-size: 28px; }
        .login-box p { margin: 0 0 25px; color: #9ca3af; }
        label { display: block; margin: 18px 0 8px; font-size: 14px; font-weight: bold; }
        input { width: 100%; padding: 14px; border-radius: 8px; border: 1px solid #263b40; background: #101d20; color: white; outline: none; }
        input:focus { border-color: #dc1623; }
        .row { margin-top: 15px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; color: #9ca3af; }
        .row input { width: auto; }
        button { width: 100%; margin-top: 25px; padding: 14px; border: none; border-radius: 8px; background: #dc1623; color: white; font-weight: bold; cursor: pointer; }
        button:hover { background: #b80f1a; }
        .bottom { text-align: center; margin-top: 25px; color: #9ca3af; font-size: 14px; }
        a { color: #ef2b35; text-decoration: none; }
        @media(max-width: 900px) { .auth-page { grid-template-columns: 1fr; } .left-panel { display: none; } }
        @media(max-width: 480px) {
            .right-panel { padding: 20px 15px; }
            .login-box { padding: 25px 20px; border-radius: 10px; }
            .login-box h2 { font-size: 22px; }
            .login-box p { font-size: 13px; margin-bottom: 18px; }
            label { margin: 14px 0 6px; font-size: 13px; }
            input { padding: 12px; font-size: 14px; }
            .row { flex-direction: column; align-items: flex-start; gap: 8px; }
            button { padding: 12px; }
        }

        /* CSS ALERTS DESIGN */
        .alert-box { padding: 15px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; line-height: 1.5; }
        .alert-danger { background: rgba(239, 43, 53, 0.15); border: 1px solid #ef2b35; color: #ff6b72; }
        .alert-success { background: rgba(22, 163, 74, 0.15); border: 1px solid #16a34a; color: #4ade80; }

        /* PASSWORD CONTAINER & EYE TOGGLE BUTTON */
        .password-container { position: relative; width: 100%; }
        .password-container input { width: 100%; padding-right: 45px; }
        .toggle-password-btn {
            position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
            cursor: pointer; width: 20px; height: 20px; display: flex; align-items: center; justify-content: center;
            user-select: none; opacity: 0.5; transition: opacity 0.2s;
        }
        .toggle-password-btn:hover { opacity: 0.9; }
        .eye-icon { position: relative; width: 16px; height: 10px; border: 2px solid #fff; border-radius: 50% 50%; }
        .eye-icon::after { content: ''; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 4px; height: 4px; background: #fff; border-radius: 50%; }
        .eye-icon.hidden-slash::before { content: ''; position: absolute; top: -4px; left: 5px; width: 2px; height: 14px; background: #fff; transform: rotate(45deg); }
    </style>
</head>
<body>
<div class="auth-page">
    <div class="left-panel">
        <div class="brand">
            <img src="assets/css/uploads/logo.png" alt="Logo">
            <div>SAPILARA<br>LOWER PORTAL</div>
        </div>
        <div class="hero">
            <h1>Building a Stronger<br><span>Community Together</span></h1>
            <p>Sapilara Lower Portal is your centralized platform for managing requests, payments, documents, events, and community updates.</p>
            <div class="features">
                <div class="feature">
                    <div class="icon">👥</div>
                    <div><b>Community Focused</b><small>Empowering residents and board members to work together.</small></div>
                </div>
                <div class="feature">
                    <div class="icon">📄</div>
                    <div><b>Easy Management</b><small>Submit requests, make payments, and manage documents seamlessly.</small></div>
                </div>
                <div class="feature">
                    <div class="icon">📅</div>
                    <div><b>Stay Connected</b><small>Get updates on events, announcements, and important notifications.</small></div>
                </div>
                <div class="feature">
                    <div class="icon">🛡</div>
                    <div><b>Secure & Reliable</b><small>Your information is protected with enterprise-grade security.</small></div>
                </div>
            </div>
        </div>
    </div>
    <div class="right-panel">
        
        <?php if ($forgot_mode): ?>
        <div class="login-box">
            <h2>Reset Password</h2>
            <p>Ipasok ang iyong email para makatanggap ng reset link.</p>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert-box alert-danger"><?= $error_msg ?></div>
            <?php endif; ?>

            <?php if (!empty($success_msg)): ?>
                <div class="alert-box alert-success"><?= $success_msg ?></div>
                <a href="login.php" class="btn" style="display:block; text-align:center; background:#16a34a; text-decoration:none; color:white; font-weight:bold; padding:14px; border-radius:8px; margin-top:15px;">Pumunta sa Login Screen</a>
            <?php else: ?>
                <form method="POST">
                    <label>Email Address</label>
                    <input type="email" name="forgot_email" placeholder="Ipasok ang rehistradong email" required value="<?= isset($_POST['forgot_email']) ? htmlspecialchars($_POST['forgot_email']) : '' ?>">
                    
                    <button type="submit" name="process_forgot">I-request ang Bagong Password</button>
                </form>
                <div class="bottom">
                    Naaalala mo na? <a href="login.php">Bumalik sa Log In</a>
                </div>
            <?php endif; ?>
        </div>

        <?php else: ?>
        <div class="login-box">
            <h2>Welcome Back</h2>
            <p>Log in to continue to your account</p>
            <form method="POST">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="Enter your email" required>
                
                <label>Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <div class="toggle-password-btn" onclick="togglePasswordVisibility()">
                        <div class="eye-icon hidden-slash" id="eyeIcon"></div>
                    </div>
                </div>

                <div class="row">
                    <label style="margin:0;font-weight:normal;"><input type="checkbox"> Remember me</label>
                    <a href="login.php?view=forgot">Forgot Password?</a>
                </div>
                <button type="submit" name="login">Log In</button>
            </form>
            <div class="bottom">
                Don't have an account? <a href="register.php">Sign up</a>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<script>
function togglePasswordVisibility() {
    const passwordInput = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");
    
    if (passwordInput && passwordInput.type === "password") {
        passwordInput.type = "text";
        eyeIcon.classList.remove("hidden-slash");
    } else if (passwordInput) {
        passwordInput.type = "password";
        eyeIcon.classList.add("hidden-slash");
    }
}
</script>
</body>
</html>