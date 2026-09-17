<?php
include("../config/db.php");
include("../includes/user_header.php");

$user_id = $_SESSION['user_id'];

$pw_error = '';

if (isset($_POST['change_password'])) {
    $result = mysqli_query($conn, "SELECT password FROM users WHERE user_id='$user_id' LIMIT 1");
    $user   = mysqli_fetch_assoc($result);

    $current = trim($_POST['current_password']);
    $new     = trim($_POST['new_password']);
    $confirm = trim($_POST['confirm_password']);

    if ($current !== $user['password']) {
        $pw_error = 'Current password is incorrect.';
    } elseif ($new !== $confirm) {
        $pw_error = 'New passwords do not match.';
    } elseif (strlen($new) < 6) {
        $pw_error = 'Password must be at least 6 characters.';
    } else {
        $safe = mysqli_real_escape_string($conn, $new);
        mysqli_query($conn, "UPDATE users SET password='$safe' WHERE user_id='$user_id'");
        logActivity($conn, $user_id, "User changed password", "Security");
        echo "<script>alert('Password updated successfully!'); window.location='settings.php';</script>";
        exit;
    }
}
?>

<style>
.settings-title {
    font-size: 11px; text-transform: uppercase; letter-spacing: 1px;
    color: #64748b; font-weight: 700; margin: 28px 0 12px;
    padding-bottom: 6px; border-bottom: 1px solid #1e3235;
}
.settings-title:first-of-type { margin-top: 0; }

.pref-row {
    display: flex; align-items: center; justify-content: space-between;
    background: #162a2d; border: 1px solid #263b40; border-radius: 8px;
    padding: 14px 16px; margin-bottom: 8px; gap: 16px;
}
.pref-label span  { font-size: 14px; color: #e2e8f0; display: block; }
.pref-label small { font-size: 12px; color: #64748b; margin-top: 2px; display: block; }

.seg-group { display: flex; gap: 6px; flex-shrink: 0; }
.seg-btn {
    padding: 5px 14px; border-radius: 6px; font-size: 12px; font-weight: 600;
    border: 1px solid #263b40; background: #0f1f22; color: #64748b;
    cursor: pointer; transition: .2s;
}
.seg-btn.active { background: #dc1623; border-color: #dc1623; color: #fff; }

#strength_bar  { height: 4px; border-radius: 2px; margin-top: 6px; background: #1e3235; }
#strength_fill { height: 100%; border-radius: 2px; width: 0; transition: .3s; }
.pw-error { color: #ef4444; font-size: 13px; margin-bottom: 12px; }
</style>

<section class="page-title">
    <h1>Settings</h1>
    <p>Customize your experience and manage your password</p>
</section>

<div class="panel" style="max-width:540px;">

    <!-- ── FONT SIZE ── -->
    <p class="settings-title">Appearance</p>

    <div class="pref-row">
        <div class="pref-label">
            <span>🔡 Font Size</span>
            <small>Adjust the text size across the portal</small>
        </div>
        <div class="seg-group" id="font_seg">
            <button class="seg-btn" onclick="setFont('small', this)">S</button>
            <button class="seg-btn active" onclick="setFont('medium', this)">M</button>
            <button class="seg-btn" onclick="setFont('large', this)">L</button>
        </div>
    </div>

    <!-- ── CHANGE PASSWORD ── -->
    <p class="settings-title">Change Password</p>

    <?php if ($pw_error): ?>
        <p class="pw-error">⚠️ <?= htmlspecialchars($pw_error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <div style="margin-bottom:14px;">
            <label>Current Password</label>
            <div style="position:relative;">
                <input type="password" name="current_password" id="cur_pass" class="form-input" required style="padding-right:46px;">
                <span onclick="togglePass('cur_pass',this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;user-select:none;">👁</span>
            </div>
        </div>

        <div style="margin-bottom:8px;">
            <label>New Password</label>
            <div style="position:relative;">
                <input type="password" name="new_password" id="new_pass" class="form-input" required
                       minlength="6" oninput="checkStrength(this.value)" style="padding-right:46px;">
                <span onclick="togglePass('new_pass',this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;user-select:none;">👁</span>
            </div>
            <div id="strength_bar"><div id="strength_fill"></div></div>
            <small id="strength_text" style="color:#9ca3af; font-size:11px;"></small>
        </div>

        <div style="margin-bottom:20px;">
            <label>Confirm New Password</label>
            <div style="position:relative;">
                <input type="password" name="confirm_password" id="con_pass" class="form-input" required
                       minlength="6" style="padding-right:46px;">
                <span onclick="togglePass('con_pass',this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;user-select:none;">👁</span>
            </div>
        </div>

        <div style="background:#162a2d; border-radius:7px; padding:12px 14px; margin-bottom:20px; font-size:13px; color:#64748b;">
            <strong style="color:#9ca3af;">Requirements:</strong><br>
            • Minimum 6 characters<br>
            • Mix of letters, numbers, and symbols recommended
        </div>

        <button type="submit" name="change_password" class="btn">🔑 Update Password</button>
    </form>

</div>

<script>
const fontMap = { small: '13px', medium: '15px', large: '17px' };

function setFont(size, btn) {
    document.querySelectorAll('#font_seg .seg-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.body.style.fontSize = fontMap[size];
    try { localStorage.setItem('hoa_font', size); } catch(e) {}
}

(function() {
    const font = localStorage.getItem('hoa_font') || 'medium';
    document.body.style.fontSize = fontMap[font];
    document.querySelectorAll('#font_seg .seg-btn').forEach(b => {
        b.classList.toggle('active', b.textContent.toLowerCase() === font[0]);
    });
})();

function togglePass(id, el) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
    el.textContent = input.type === 'password' ? '👁' : '🙈';
}

function checkStrength(val) {
    const fill = document.getElementById('strength_fill');
    const text = document.getElementById('strength_text');
    let score = 0;
    if (val.length >= 6)          score++;
    if (val.length >= 10)         score++;
    if (/[A-Z]/.test(val))        score++;
    if (/[0-9]/.test(val))        score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const colors = ['#dc1623','#e05c00','#e0a800','#4caf50','#2e7d32'];
    const labels = ['Very Weak','Weak','Fair','Strong','Very Strong'];
    fill.style.width      = (score / 5 * 100) + '%';
    fill.style.background = colors[score - 1] || '#263b40';
    text.textContent      = score > 0 ? labels[score - 1] : '';
}
</script>

<?php include("../includes/user_footer.php"); ?>