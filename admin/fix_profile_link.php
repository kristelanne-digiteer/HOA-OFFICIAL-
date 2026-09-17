<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

$fixed = 0;
$skipped = 0;
$log = [];

// Get all users who are HOA members
$users = mysqli_query($conn, "SELECT user_id, first_name, last_name FROM users WHERE hoa_member=1");

while ($user = mysqli_fetch_assoc($users)) {
    $uid = $user['user_id'];
    $full_name = mysqli_real_escape_string($conn, trim($user['first_name'] . ' ' . $user['last_name']));

    // Check their current profile link
    $prof = mysqli_query($conn, "SELECT profile_id, masterlist_id FROM homeowner_profiles WHERE user_id='$uid' LIMIT 1");
    $profile = mysqli_fetch_assoc($prof);

    // If profile link is already correct, skip
    if ($profile && !empty($profile['masterlist_id'])) {
        // Verify the masterlist entry still exists
        $ml_check = mysqli_query($conn, "SELECT homeowner_id FROM homeowners_masterlist WHERE homeowner_id='{$profile['masterlist_id']}' LIMIT 1");
        if (mysqli_num_rows($ml_check) > 0) {
            $skipped++;
            continue;
        }
    }

    // Find in masterlist by name
    $ml_q = mysqli_query($conn, "SELECT homeowner_id FROM homeowners_masterlist WHERE LOWER(name) = LOWER('$full_name') LIMIT 1");

    if (mysqli_num_rows($ml_q) > 0) {
        $ml_row = mysqli_fetch_assoc($ml_q);
        $masterlist_id = $ml_row['homeowner_id'];
    } else {
        // Create new masterlist entry for this user
        mysqli_query($conn, "
            INSERT INTO homeowners_masterlist (name, full_address, contact_no, gender, status, created_at)
            VALUES ('$full_name', '', '', '', 'active', NOW())
        ");
        $masterlist_id = mysqli_insert_id($conn);
        $log[] = "Created masterlist entry for: $full_name";
    }

    // Fix or create the profile link
    if ($profile) {
        mysqli_query($conn, "UPDATE homeowner_profiles SET masterlist_id='$masterlist_id' WHERE user_id='$uid'");
    } else {
        mysqli_query($conn, "INSERT INTO homeowner_profiles (user_id, masterlist_id) VALUES ('$uid', '$masterlist_id')");
    }

    $log[] = "Fixed: $full_name (user_id=$uid → masterlist_id=$masterlist_id)";
    $fixed++;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Profile Links</title>
    <style>
        body { background: #0f1f22; color: #fff; font-family: Arial, sans-serif; padding: 30px; }
        h1 { color: #dc2626; }
        .box { background: #1a2f33; border-radius: 10px; padding: 20px; margin-top: 20px; }
        .success { color: #4ade80; }
        .info { color: #facc15; }
        a { color: #dc2626; }
        pre { color: #cbd5e1; font-size: 13px; line-height: 1.8; }
    </style>
</head>
<body>
    <h1>Profile Link Repair Tool</h1>
    <div class="box">
        <p class="success">✅ Fixed: <strong><?php echo $fixed; ?></strong> user(s)</p>
        <p class="info">⏭ Skipped (already correct): <strong><?php echo $skipped; ?></strong> user(s)</p>

        <?php if (!empty($log)): ?>
            <hr style="border-color:#263b40; margin:15px 0;">
            <pre><?php echo implode("\n", array_map('htmlspecialchars', $log)); ?></pre>
        <?php endif; ?>

        <br>
        <a href="homeowners.php">← Back to Homeowners</a>
    </div>
</body>
</html>
