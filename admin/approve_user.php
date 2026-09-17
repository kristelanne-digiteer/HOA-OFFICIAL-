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

// Check kung existing ba yung user
$check = mysqli_query($conn, "SELECT user_id, first_name, last_name, status, hoa_member FROM users WHERE user_id='$id' LIMIT 1");

if (mysqli_num_rows($check) == 0) {
    echo "<script>alert('User not found.'); window.location='homeowners.php';</script>";
    exit;
}

$user = mysqli_fetch_assoc($check);

if ($user['status'] === 'active') {
    echo "<script>alert('This user is already active.'); window.location='homeowners.php';</script>";
    exit;
}

// Approve — set status to active
mysqli_query($conn, "UPDATE users SET status='active' WHERE user_id='$id'");

// FIX: If this user is an HOA member, make sure homeowner_profiles is linked to masterlist
// Without this, the user's bills/SOA will never appear in their portal
if ($user['hoa_member'] == 1) {
    $full_name = mysqli_real_escape_string($conn, trim($user['first_name'] . ' ' . $user['last_name']));

    // Find their masterlist entry
    $masterlist_q = mysqli_query($conn, "SELECT homeowner_id FROM homeowners_masterlist WHERE name='$full_name' LIMIT 1");

    if (mysqli_num_rows($masterlist_q) > 0) {
        $masterlist_row = mysqli_fetch_assoc($masterlist_q);
        $masterlist_id = $masterlist_row['homeowner_id'];

        // Check if profile exists
        $profile_check = mysqli_query($conn, "SELECT profile_id, masterlist_id FROM homeowner_profiles WHERE user_id='$id' LIMIT 1");

        if (mysqli_num_rows($profile_check) > 0) {
            $profile = mysqli_fetch_assoc($profile_check);
            // Update masterlist_id if it's NULL or 0
            if (empty($profile['masterlist_id'])) {
                mysqli_query($conn, "
                    UPDATE homeowner_profiles
                    SET masterlist_id='$masterlist_id'
                    WHERE user_id='$id'
                ");
            }
        } else {
            // Create profile link
            mysqli_query($conn, "
                INSERT INTO homeowner_profiles (user_id, masterlist_id)
                VALUES ('$id', '$masterlist_id')
            ");
        }
    }
}

echo "<script>alert('{$user['first_name']} {$user['last_name']} has been approved successfully.'); window.location='homeowners.php';</script>";
exit;
?>