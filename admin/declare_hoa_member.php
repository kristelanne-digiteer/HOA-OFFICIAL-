<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo "<script>alert('Invalid user ID.'); window.location='homeowners.php';</script>";
    exit;
}

// Fetch the user
$check = mysqli_query($conn, "SELECT user_id, first_name, last_name, email, hoa_member FROM users WHERE user_id='$id' LIMIT 1");

if (mysqli_num_rows($check) == 0) {
    echo "<script>alert('User not found.'); window.location='homeowners.php';</script>";
    exit;
}

$user = mysqli_fetch_assoc($check);
$full_name = mysqli_real_escape_string($conn, trim($user['first_name'] . ' ' . $user['last_name']));

// Check if already in masterlist
$existing = mysqli_query($conn, "SELECT homeowner_id FROM homeowners_masterlist WHERE LOWER(name) = LOWER('$full_name') LIMIT 1");

if (mysqli_num_rows($existing) == 0) {
    // Add to homeowners_masterlist
    mysqli_query($conn, "
        INSERT INTO homeowners_masterlist (name, full_address, contact_no, gender, status, created_at)
        VALUES ('$full_name', '', '', '', 'active', NOW())
    ");
    $masterlist_id = mysqli_insert_id($conn);
} else {
    $masterlist_row = mysqli_fetch_assoc($existing);
    $masterlist_id = $masterlist_row['homeowner_id'];
}

// Mark the user as HOA member and activate account
mysqli_query($conn, "UPDATE users SET hoa_member=1, status='active' WHERE user_id='$id'");

// Always create or fix homeowner_profiles link (this is the key fix)
$profile_check = mysqli_query($conn, "SELECT profile_id, masterlist_id FROM homeowner_profiles WHERE user_id='$id' LIMIT 1");

if (mysqli_num_rows($profile_check) > 0) {
    $profile_row = mysqli_fetch_assoc($profile_check);
    // Update masterlist_id if it's missing or wrong
    if (empty($profile_row['masterlist_id']) || $profile_row['masterlist_id'] != $masterlist_id) {
        mysqli_query($conn, "
            UPDATE homeowner_profiles
            SET masterlist_id='$masterlist_id'
            WHERE user_id='$id'
        ");
    }
} else {
    // No profile at all — create one
    mysqli_query($conn, "
        INSERT INTO homeowner_profiles (user_id, masterlist_id)
        VALUES ('$id', '$masterlist_id')
    ");
}

$display_name = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);

if ($user['hoa_member'] == 1) {
    echo "<script>alert('{$display_name} profile link has been fixed. Bills will now appear correctly.'); window.location='homeowners.php';</script>";
} else {
    echo "<script>alert('{$display_name} has been declared as an HOA member and added to the masterlist.'); window.location='homeowners.php';</script>";
}
exit;
?>