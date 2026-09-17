<?php
include("config/db.php");

$token       = mysqli_real_escape_string($conn, trim($_GET['token'] ?? ($_POST['token'] ?? '')));
$valid_token = false;
$error_msg   = "";
$success_msg = "";
$user_id     = null;

// 1. I-VALIDATE ANG TOKEN (umiiral ba, at hindi pa expired)
if (!empty($token)) {
    $q = mysqli_query($conn, "SELECT user_id, reset_token_expiry FROM users WHERE reset_token='$token' LIMIT 1");

    if ($q && mysqli_num_rows($q) > 0) {
        $row = mysqli_fetch_assoc($q);

        if (strtotime($row['reset_token_expiry']) >= time()) {
            $valid_token = true;
            $user_id     = (int) $row['user_id'];
        } else {
            $error_msg = "Expired na ang reset link na ito. Mag-request ulit ng bago sa login page.";
        }
    } else {
        $error_msg = "Invalid o hindi na umiiral ang reset link na ito.";
    }
} else {
    $error_msg = "Walang token na natagpuan. Siguraduhing gamitin ang buong link na ipinadala sa email mo.";
}

// 2. PANG-PROSESO NG BAGONG PASSWORD
if ($valid_token && isset($_POST['reset_password'])) {
    $new_password     = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (strlen($new_password) < 6) {
        $error_msg = "Dapat hindi bababa sa 6 characters ang bagong password.";
    } elseif ($new_password !== $confirm_password) {
        $error_msg = "Hindi tugma ang dalawang password na in-type mo.";
    } else {
        $new_password_escaped = mysqli_real_escape_string($conn, $new_password);
        $update_q = mysqli_query($conn, "UPDATE users SET password='$new_password_escaped', reset_token=NULL, reset_token_expiry=NULL WHERE user_id=$user_id");

        if ($update_q) {
            $success_msg = "Successfully na-update ang iyong password! Pwede ka na mag-login gamit ang bagong password mo.";
            $valid_token = false; // itago na ang form pagkatapos ng successful reset
        } else {
            $error_msg = "Nagkaroon ng problema sa pag-save ng bagong password. Subukan muli.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - Sapilara Lower Portal</title>
    <style>
        * { box-sizing: border-box; font-family: Arial, sans-serif; }
        body { margin: 0; min-height: 100vh; background: #061514; color: #fff; display: flex; align-items: center; justify-content: center; }
        .login-box { width: 430px; background: rgba(15,31,34,.92); border: 1px solid #263b40; border-radius: 14px; padding: 35px; box-shadow: 0 20px 60px rgba(0,0,0,.35); margin: 20px; }
        .login-box h2 { margin: 0 0 8px; font-size: 28px; }
        .login-box p { margin: 0 0 25px; color: #9ca3af; }
        label { display: block; margin: 18px 0 8px; font-size: 14px; font-weight: bold; }
        input { width: 100%; padding: 14px; border-radius: 8px; border: 1px solid #263b40; background: #101d20; color: white; outline: none; }
        input:focus { border-color: #dc1623; }
        button { width: 100%; margin-top: 25px; padding: 14px; border: none; border-radius: 8px; background: #dc1623; color: white; font-weight: bold; cursor: pointer; }
        button:hover { background: #b80f1a; }
        .bottom { text-align: center; margin-top: 25px; color: #9ca3af; font-size: 14px; }
        a { color: #ef2b35; text-decoration: none; }
        .alert-box { padding: 15px; border-radius: 8px; font-size: 14px; margin-bottom: 20px; line-height: 1.5; }
        .alert-danger { background: rgba(239, 43, 53, 0.15); border: 1px solid #ef2b35; color: #ff6b72; }
        .alert-success { background: rgba(22, 163, 74, 0.15); border: 1px solid #16a34a; color: #4ade80; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Set Bagong Password</h2>
        <p>Mag-set ng bagong password para sa iyong account.</p>

        <?php if (!empty($error_msg)): ?>
            <div class="alert-box alert-danger"><?= htmlspecialchars($error_msg) ?></div>
        <?php endif; ?>

        <?php if (!empty($success_msg)): ?>
            <div class="alert-box alert-success"><?= htmlspecialchars($success_msg) ?></div>
            <a href="login.php" style="display:block; text-align:center; background:#16a34a; text-decoration:none; color:white; font-weight:bold; padding:14px; border-radius:8px; margin-top:15px;">Pumunta sa Login Screen</a>
        <?php elseif ($valid_token): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <label>Bagong Password</label>
                <input type="password" name="new_password" placeholder="Hindi bababa sa 6 characters" required minlength="6">

                <label>Kumpirmahin ang Bagong Password</label>
                <input type="password" name="confirm_password" placeholder="I-type ulit ang bagong password" required minlength="6">

                <button type="submit" name="reset_password">I-set ang Bagong Password</button>
            </form>
        <?php else: ?>
            <a href="login.php?view=forgot" style="display:block; text-align:center; background:#dc1623; text-decoration:none; color:white; font-weight:bold; padding:14px; border-radius:8px; margin-top:15px;">Mag-request Ulit ng Reset Link</a>
        <?php endif; ?>

        <div class="bottom">
            <a href="login.php">Bumalik sa Log In</a>
        </div>
    </div>
</body>
</html>
