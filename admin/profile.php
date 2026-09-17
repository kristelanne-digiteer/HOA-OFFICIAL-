<?php
include("../config/db.php");
include("../includes/admin_header.php");

$admin_id = $_SESSION['user_id'];

// Fetch admin user data + link to homeowner_profiles (para pareho ng pwedeng i-edit sa user)
$result = mysqli_query($conn, "
    SELECT u.*, 
           hp.profile_id, hp.address, hp.unit_number, hp.contact_number, hp.emergency_contact 
    FROM users u
    LEFT JOIN homeowner_profiles hp ON u.user_id = hp.user_id
    WHERE u.user_id='$admin_id'
    LIMIT 1
");
$user = mysqli_fetch_assoc($result);

// ── UPDATE PROFILE ────────────────────────────────────────────────────────────
if (isset($_POST['update_profile'])) {
    $first_name        = mysqli_real_escape_string($conn, trim($_POST['first_name']));
    $last_name         = mysqli_real_escape_string($conn, trim($_POST['last_name']));
    $email              = mysqli_real_escape_string($conn, trim($_POST['email']));
    $address           = mysqli_real_escape_string($conn, trim($_POST['address']));
    $unit_number       = mysqli_real_escape_string($conn, trim($_POST['unit_number']));
    $contact_number    = mysqli_real_escape_string($conn, trim($_POST['contact_number']));
    $emergency_contact = mysqli_real_escape_string($conn, trim($_POST['emergency_contact']));

    // Check email conflict with other users
    $email_check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email' AND user_id!='$admin_id' LIMIT 1");
    if (mysqli_num_rows($email_check) > 0) {
        echo "<script>alert('That email is already used by another account.');</script>";
    } else {
        $profile_picture = $user['profile_picture'];

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $upload_dir = "../assets/uploads/users/";
            if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_name = time() . "_" . basename($_FILES['profile_picture']['name']);
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $file_name)) {
                $profile_picture = $file_name;
            }
        }

        // Update users table
        mysqli_query($conn, "
            UPDATE users
            SET first_name='$first_name',
                last_name='$last_name',
                email='$email',
                profile_picture='$profile_picture'
            WHERE user_id='$admin_id'
        ");

        // Update or Insert to homeowner_profiles table for Admin
        if (!empty($user['profile_id'])) {
            mysqli_query($conn, "
                UPDATE homeowner_profiles
                SET address='$address',
                    unit_number='$unit_number',
                    contact_number='$contact_number',
                    emergency_contact='$emergency_contact'
                WHERE profile_id='{$user['profile_id']}'
            ");
        } else {
            mysqli_query($conn, "
                INSERT INTO homeowner_profiles (user_id, address, unit_number, contact_number, emergency_contact)
                VALUES ('$admin_id', '$address', '$unit_number', '$contact_number', '$emergency_contact')
            ");
        }

        // Update session
        $_SESSION['first_name']      = $first_name;
        $_SESSION['last_name']       = $last_name;
        $_SESSION['profile_picture'] = $profile_picture;

        logActivity($conn, $admin_id, "Admin updated profile", "Profile");

        echo "<script>alert('Profile updated successfully.'); window.location='profile.php';</script>";
        exit;
    }
}

// ── CHANGE PASSWORD ───────────────────────────────────────────────────────────
if (isset($_POST['change_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password     = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($current_password != $user['password']) {
        echo "<script>alert('Current password is incorrect.');</script>";
    } elseif ($new_password !== $confirm_password) {
        echo "<script>alert('New passwords do not match.');</script>";
    } elseif (strlen($new_password) < 6) {
        echo "<script>alert('Password must be at least 6 characters.');</script>";
    } else {
        $safe_pass = mysqli_real_escape_string($conn, $new_password);
        mysqli_query($conn, "UPDATE users SET password='$safe_pass' WHERE user_id='$admin_id'");
        logActivity($conn, $admin_id, "Admin changed password", "Security");
        echo "<script>alert('Password updated successfully.'); window.location='profile.php';</script>";
        exit;
    }
}

$profile_src = "../assets/css/uploads/admin.jpg";
if (!empty($user['profile_picture'])) {
    $profile_src = "../assets/uploads/users/" . $user['profile_picture'];
}
?>

<section class="page-title">
    <h1>My Profile</h1>
    <p>Manage your administrator account information</p>
</section>

<div class="tabs">
    <button class="tab-btn active" onclick="openTab(event, 'personal')">Personal Information</button>
    <button class="tab-btn" onclick="openTab(event, 'password')">Change Password</button>
    <button class="tab-btn" onclick="openTab(event, 'account')">Account Details</button>
</div>

<div id="personal" class="tab-content active-tab">
    <div class="panel">
        <h2>Personal Information</h2>

        <form method="POST" enctype="multipart/form-data">

            <div style="display:flex;align-items:center;gap:20px;margin-bottom:25px;">
                <img src="<?php echo htmlspecialchars($profile_src); ?>"
                     id="preview"
                     style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #263b40;"
                     alt="Profile Picture">
                <div>
                    <label style="margin:0 0 6px;display:block;">Profile Picture</label>
                    <input type="file" name="profile_picture" class="form-input" accept="image/*"
                           onchange="previewImg(this)" style="padding:6px;">
                    <small style="color:#9ca3af;">JPG, PNG, GIF — max 2MB</small>
                </div>
            </div>

            <h3 style="margin:20px 0 10px;font-size:14px;text-transform:uppercase;color:#9ca3af;letter-spacing:1px;">Basic Info</h3>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                <div>
                    <label>First Name</label>
                    <input type="text" name="first_name" class="form-input"
                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                <div>
                    <label>Last Name</label>
                    <input type="text" name="last_name" class="form-input"
                           value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                </div>
            </div>

            <label>Email Address</label>
            <input type="email" name="email" class="form-input"
                   value="<?php echo htmlspecialchars($user['email']); ?>" required>

            <h3 style="margin:25px 0 10px;font-size:14px;text-transform:uppercase;color:#9ca3af;letter-spacing:1px;">Address & Unit</h3>

            <label>Home Address</label>
            <input type="text" name="address" class="form-input"
                   value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>"
                   placeholder="e.g. 123 Sapilara St., Lower Village">

            <label>Unit / Block / Lot Number</label>
            <input type="text" name="unit_number" class="form-input"
                   value="<?php echo htmlspecialchars($user['unit_number'] ?? ''); ?>"
                   placeholder="e.g. Block 5, Lot 12">

            <h3 style="margin:25px 0 10px;font-size:14px;text-transform:uppercase;color:#9ca3af;letter-spacing:1px;">Contact Information</h3>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                <div>
                    <label>Contact Number</label>
                    <input type="text" name="contact_number" class="form-input"
                           value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>"
                           placeholder="e.g. 09XX XXX XXXX">
                </div>
                <div>
                    <label>Emergency Contact</label>
                    <input type="text" name="emergency_contact" class="form-input"
                           value="<?php echo htmlspecialchars($user['emergency_contact'] ?? ''); ?>"
                           placeholder="Name - Number">
                </div>
            </div>

            <button type="submit" name="update_profile" class="btn" style="margin-top:20px;">
                Save Changes
            </button>
        </form>
    </div>
</div>

<div id="password" class="tab-content">
    <div class="panel">
        <h2>Change Password</h2>

        <form method="POST" style="max-width:480px;">
            <label>Current Password</label>
            <div style="position:relative;">
                <input type="password" name="current_password" id="cur_pass" class="form-input" required style="padding-right:45px;">
                <span onclick="togglePass('cur_pass', this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;">👁</span>
            </div>

            <label>New Password</label>
            <div style="position:relative;">
                <input type="password" name="new_password" id="new_pass" class="form-input" required style="padding-right:45px;" minlength="6">
                <span onclick="togglePass('new_pass', this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;">👁</span>
            </div>

            <label>Confirm New Password</label>
            <div style="position:relative;">
                <input type="password" name="confirm_password" id="con_pass" class="form-input" required style="padding-right:45px;" minlength="6">
                <span onclick="togglePass('con_pass', this)" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;color:#9ca3af;">👁</span>
            </div>

            <small style="color:#9ca3af;display:block;margin-top:8px;">Minimum 6 characters</small>

            <button type="submit" name="change_password" class="btn" style="margin-top:20px;">
                Update Password
            </button>
        </form>
    </div>
</div>

<div id="account" class="tab-content">
    <div class="panel">
        <h2>Account Details</h2>

        <table class="table" style="max-width:600px;">
            <tr>
                <td style="font-weight:bold;width:180px;">User ID</td>
                <td><?php echo $user['user_id']; ?></td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Full Name</td>
                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Email</td>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Role</td>
                <td><span class="badge green"><?php echo htmlspecialchars($user['role']); ?></span></td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Status</td>
                <td><span class="badge <?php echo $user['status']=='active'?'green':'red'; ?>"><?php echo htmlspecialchars($user['status']); ?></span></td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Address (Profile)</td>
                <td><?php echo !empty($user['address']) ? htmlspecialchars($user['address']) : '<span style="color:#9ca3af;">Not set</span>'; ?></td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Unit / Block / Lot</td>
                <td><?php echo !empty($user['unit_number']) ? htmlspecialchars($user['unit_number']) : '<span style="color:#9ca3af;">Not set</span>'; ?></td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Contact Number</td>
                <td><?php echo !empty($user['contact_number']) ? htmlspecialchars($user['contact_number']) : '<span style="color:#9ca3af;">Not set</span>'; ?></td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Emergency Contact</td>
                <td><?php echo !empty($user['emergency_contact']) ? htmlspecialchars($user['emergency_contact']) : '<span style="color:#9ca3af;">Not set</span>'; ?></td>
            </tr>
            <tr>
                <td style="font-weight:bold;">Account Created</td>
                <td><?php echo !empty($user['created_at']) ? $user['created_at'] : 'N/A'; ?></td>
            </tr>
        </table>
    </div>
</div>

<script>
function openTab(event, tabName) {
    document.querySelectorAll(".tab-content").forEach(c => c.classList.remove("active-tab"));
    document.querySelectorAll(".tab-btn").forEach(b => b.classList.remove("active"));
    document.getElementById(tabName).classList.add("active-tab");
    event.currentTarget.classList.add("active");
}

function previewImg(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => document.getElementById('preview').src = e.target.result;
        reader.readAsDataURL(input.files[0]);
    }
}

function togglePass(id, el) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        el.textContent = '🙈';
    } else {
        input.type = 'password';
        el.textContent = '👁';
    }
}
</script>

<?php include("../includes/admin_footer.php"); ?>