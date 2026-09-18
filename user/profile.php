<?php
include("../config/db.php");
include("../includes/user_header.php");

$user_id = $_SESSION['user_id'];

$result = mysqli_query($conn, "
    SELECT u.*,
           hp.profile_id, hp.address, hp.unit_number, hp.contact_number,
           hp.emergency_contact, hp.masterlist_id,
           hm.full_address AS masterlist_address,
           hm.birthday, hm.contact_no AS masterlist_contact,
           hm.gender
    FROM users u
    LEFT JOIN homeowner_profiles hp ON u.user_id = hp.user_id
    LEFT JOIN homeowners_masterlist hm ON hp.masterlist_id = hm.homeowner_id
    WHERE u.user_id = '$user_id'
    LIMIT 1
");
$user = mysqli_fetch_assoc($result);

// ── UPDATE PERSONAL INFO ──────────────────────────────────────────────────────
if (isset($_POST['update_profile'])) {
    $first_name        = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name         = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email             = mysqli_real_escape_string($conn, trim($_POST['email']));
    $address           = mysqli_real_escape_string($conn, trim($_POST['address']));
    $unit_number       = mysqli_real_escape_string($conn, trim($_POST['unit_number']));
    $contact_number    = mysqli_real_escape_string($conn, trim($_POST['contact_number']));
    $emergency_contact = mysqli_real_escape_string($conn, trim($_POST['emergency_contact']));

    $email_check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email' AND user_id!='$user_id' LIMIT 1");
    if (mysqli_num_rows($email_check) > 0) {
        echo "<script>alert('That email is already used by another account.');</script>";
    } else {
        $profile_picture = $user['profile_picture'];

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $upload_dir = "../assets/uploads/users/";
                if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
                $file_name = time() . "_" . basename($_FILES['profile_picture']['name']);
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $file_name)) {
                    $profile_picture = $file_name;
                }
            }
        }

        mysqli_query($conn, "
            UPDATE users
            SET first_name='$first_name', last_name='$last_name',
                email='$email', profile_picture='$profile_picture'
            WHERE user_id='$user_id'
        ");

        if (!empty($user['profile_id'])) {
            mysqli_query($conn, "
                UPDATE homeowner_profiles
                SET address='$address', unit_number='$unit_number',
                    contact_number='$contact_number', emergency_contact='$emergency_contact'
                WHERE profile_id='{$user['profile_id']}'
            ");
        } else {
            mysqli_query($conn, "
                INSERT INTO homeowner_profiles (user_id, address, unit_number, contact_number, emergency_contact)
                VALUES ('$user_id','$address','$unit_number','$contact_number','$emergency_contact')
            ");
        }

        $_SESSION['first_name']      = $first_name;
        $_SESSION['last_name']       = $last_name;
        $_SESSION['profile_picture'] = $profile_picture;

        logActivity($conn, $user_id, "User updated profile info", "Profile");
        echo "<script>alert('Profile updated successfully.'); window.location='profile.php';</script>";
        exit;
    }
}

// ── CHANGE PASSWORD ───────────────────────────────────────────────────────────
if (isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password     = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($current_password !== $user['password']) {
        echo "<script>alert('Current password is incorrect.');</script>";
    } elseif ($new_password !== $confirm_password) {
        echo "<script>alert('New passwords do not match.');</script>";
    } elseif (strlen($new_password) < 6) {
        echo "<script>alert('Password must be at least 6 characters.');</script>";
    } else {
        $safe_pass = mysqli_real_escape_string($conn, $new_password);
        mysqli_query($conn, "UPDATE users SET password='$safe_pass' WHERE user_id='$user_id'");
        logActivity($conn, $user_id, "User changed password", "Security");
        echo "<script>alert('Password updated successfully.'); window.location='profile.php';</script>";
        exit;
    }
}


$profile_src = "../assets/css/uploads/default-user.png";
if (!empty($user['profile_picture'])) {
    $profile_src = "../assets/uploads/users/" . $user['profile_picture'];
}
?>

<style>
.profile-section-title {
    font-size: 11px; text-transform: uppercase; letter-spacing: 1px;
    color: #64748b; font-weight: 700; margin: 24px 0 10px;
    padding-bottom: 6px; border-bottom: 1px solid #1e3235;
}
.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.readonly-badge {
    display: inline-block; font-size: 10px; background: #1e3235;
    color: #64748b; border-radius: 4px; padding: 2px 6px; margin-left: 6px;
}
#strength_bar  { height: 4px; border-radius: 2px; margin-top: 6px; background: #1e3235; }
#strength_fill { height: 100%; border-radius: 2px; width: 0; transition: .3s; }
.info-table { width: 100%; border-collapse: collapse; max-width: 580px; }
.info-table td { padding: 10px 12px; border-bottom: 1px solid #1e3235; font-size: 14px; }
.info-table td:first-child { color: #64748b; width: 200px; font-weight: 600; }
.info-table tr:last-child td { border-bottom: none; }


</style>

<section class="page-title">
    <h1>Profile Management</h1>
    <p>Manage your personal information and account settings</p>
</section>

<div class="tabs">
    <button class="tab-btn active" onclick="openTab(event,'personal')">👤 Personal Info</button>
    <button class="tab-btn" onclick="openTab(event,'password')">🔒 Change Password</button>
    <button class="tab-btn" onclick="openTab(event,'account')">📋 Account Details</button>
</div>

<!-- ══ TAB 1: PERSONAL INFO ══ -->
<div id="personal" class="tab-content active-tab">
    <div class="panel" style="max-width:620px;">
        <h2>Personal Information</h2>

        <form method="POST" enctype="multipart/form-data">

            <!-- Profile Photo -->
            <div style="display:flex; align-items:center; gap:20px; margin-bottom:24px; padding:16px; background:#162a2d; border-radius:10px; border:1px solid #263b40;">
                <img src="<?php echo htmlspecialchars($profile_src); ?>"
                     id="preview"
                     style="width:80px; height:80px; border-radius:50%; object-fit:cover; border:3px solid #263b40; flex-shrink:0;"
                     alt="Profile Picture">
                <div style="flex:1;">
                    <strong style="color:#e2e8f0; font-size:15px;">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')); ?>
                    </strong><br>
                    <span style="color:#9ca3af; font-size:13px;"><?php echo htmlspecialchars($user['email']); ?></span>
                    <div style="margin-top:10px;">
                        <label style="font-size:13px; color:#94a3b8;">Change Profile Photo</label>
                        <input type="file" name="profile_picture" accept="image/*"
                               onchange="previewImg(this)"
                               style="font-size:13px; color:#9ca3af; margin-top:4px; display:block;">
                    </div>
                </div>
            </div>

            <p class="profile-section-title">Basic Info</p>
            <div class="info-grid">
                <div>
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-input"
                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                <div>
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-input"
                           value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                </div>
            </div>
            <div style="margin-top:14px;">
                <label>Email Address</label>
                <input type="email" name="email" class="form-input"
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <p class="profile-section-title">Address & Unit</p>
            <div style="margin-bottom:14px;">
                <label>Home Address</label>
                <input type="text" name="address" class="form-input"
                       value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"
                       placeholder="e.g. 123 Sapilara St., Lower Village">
            </div>
            <div>
                <label>Unit / Block / Lot Number</label>
                <input type="text" name="unit_number" class="form-input"
                       value="<?php echo htmlspecialchars($user['unit_number'] ?? ''); ?>"
                       placeholder="e.g. Block 5, Lot 12">
            </div>

            <p class="profile-section-title">Contact Information</p>
            <div class="info-grid">
                <div>
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" class="form-input"
                           value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>"
                           placeholder="09XX XXX XXXX">
                </div>
                <div>
                    <label>Emergency Contact</label>
                    <input type="text" name="emergency_contact" class="form-input"
                           value="<?php echo htmlspecialchars($user['emergency_contact'] ?? ''); ?>"
                           placeholder="Name – Number">
                </div>
            </div>

            <?php if (!empty($user['birthday']) || !empty($user['gender'])): ?>
            <p class="profile-section-title">HOA Masterlist Info <span class="readonly-badge">READ ONLY</span></p>
            <div class="info-grid">
                <?php if (!empty($user['birthday'])): ?>
                <div>
                    <label>Birthday</label>
                    <input type="text" class="form-input"
                           value="<?php echo htmlspecialchars($user['birthday']); ?>"
                           disabled style="opacity:.5; cursor:not-allowed;">
                </div>
                <?php endif; ?>
                <?php if (!empty($user['gender'])): ?>
                <div>
                    <label>Gender</label>
                    <input type="text" class="form-input"
                           value="<?php echo htmlspecialchars($user['gender']); ?>"
                           disabled style="opacity:.5; cursor:not-allowed;">
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <button type="submit" name="update_profile" class="btn" style="margin-top:24px;">
                💾 Save Changes
            </button>
        </form>
    </div>
</div>

<!-- ══ TAB 2: CHANGE PASSWORD ══ -->
<div id="password" class="tab-content">
    <div class="panel" style="max-width:480px;">
        <h2>Change Password</h2>
        <p style="color:#9ca3af; font-size:14px; margin-bottom:20px;">
            Enter your current password before setting a new one.
        </p>

        <form method="POST">
            <div style="margin-bottom:16px;">
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
</div>

<!-- ══ TAB 3: ACCOUNT DETAILS ══ -->
<div id="account" class="tab-content">
    <div class="panel">
        <h2>Account Details</h2>
        <p style="color:#9ca3af; font-size:14px; margin-bottom:20px;">Your HOA account information on record.</p>

        <table class="info-table">
            <tr>
                <td>User ID</td>
                <td><code style="background:#162a2d; padding:2px 8px; border-radius:4px; color:#38bdf8;">#<?php echo $user['user_id']; ?></code></td>
            </tr>
            <tr>
                <td>Full Name</td>
                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')); ?></td>
            </tr>
            <tr>
                <td>Email Address</td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
            </tr>
            <tr>
                <td>Role</td>
                <td><span class="badge green"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span></td>
            </tr>
            <tr>
                <td>Account Status</td>
                <td>
                    <span class="badge <?php echo $user['status']=='active' ? 'green' : 'red'; ?>">
                        <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                    </span>
                </td>
            </tr>
            <?php if (!empty($user['masterlist_id'])): ?>
            <tr>
                <td>HOA Masterlist ID</td>
                <td><?php echo $user['masterlist_id']; ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>Home Address</td>
                <td><?php echo !empty($user['address']) ? htmlspecialchars($user['address']) : '<span style="color:#4b6a70;">Not set</span>'; ?></td>
            </tr>
            <tr>
                <td>Unit / Block / Lot</td>
                <td><?php echo !empty($user['unit_number']) ? htmlspecialchars($user['unit_number']) : '<span style="color:#4b6a70;">Not set</span>'; ?></td>
            </tr>
            <tr>
                <td>Contact Number</td>
                <td><?php echo !empty($user['contact_number']) ? htmlspecialchars($user['contact_number']) : '<span style="color:#4b6a70;">Not set</span>'; ?></td>
            </tr>
            <tr>
                <td>Emergency Contact</td>
                <td><?php echo !empty($user['emergency_contact']) ? htmlspecialchars($user['emergency_contact']) : '<span style="color:#4b6a70;">Not set</span>'; ?></td>
            </tr>
            <tr>
                <td>Account Created</td>
                <td><?php echo !empty($user['created_at']) ? date('F j, Y', strtotime($user['created_at'])) : 'N/A'; ?></td>
            </tr>
        </table>
    </div>
</div>


<script>
// ── Tab switching ──────────────────────────────────────────────────────────────
function openTab(event, tabName) {
    document.querySelectorAll(".tab-content").forEach(c => c.classList.remove("active-tab"));
    document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
    document.getElementById(tabName).classList.add("active-tab");
    event.currentTarget.classList.add("active");
}


// ── Profile photo preview ──────────────────────────────────────────────────────
function previewImg(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('preview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}

// ── Password toggle / strength ─────────────────────────────────────────────────
function togglePass(id, el) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
    el.textContent = input.type === 'password' ? '👁' : '🙈';
}

function checkStrength(val) {
    const fill = document.getElementById('strength_fill');
    const text = document.getElementById('strength_text');
    let score = 0;
    if (val.length >= 6)  score++;
    if (val.length >= 10) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^A-Za-z0-9]/.test(val)) score++;
    const colors = ['#dc1623','#e05c00','#e0a800','#4caf50','#2e7d32'];
    const labels = ['Very Weak','Weak','Fair','Strong','Very Strong'];
    fill.style.width      = (score / 5 * 100) + '%';
    fill.style.background = colors[score - 1] || '#263b40';
    text.textContent      = score > 0 ? labels[score - 1] : '';
}


</script>

<?php include("../includes/user_footer.php"); ?>