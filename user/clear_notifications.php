<?php
session_name('HOA_USER_SESSION');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");
include("../includes/notifications_helper.php");

$user_id = $_SESSION['user_id'];

$profile_q = mysqli_query($conn, "
    SELECT masterlist_id
    FROM homeowner_profiles
    WHERE user_id='$user_id'
");
$profile_row   = mysqli_fetch_assoc($profile_q);
$masterlist_id = $profile_row['masterlist_id'] ?? 0;

// Kunin lahat ng kasalukuyang nakikitang notifications ng user na ito,
// tapos i-dismiss lahat ng keys nito (parang "Clear All" sa phone).
// Hindi natin dinedelete ang aktwal na announcements/document/rental/
// payment/soa records — ginagamit pa rin yun sa ibang parte ng portal.
$current_notifications = getUserNotifications($conn, $user_id, $masterlist_id);
$current_keys          = array_column($current_notifications, 'key');

$cleared_keys = $_SESSION['cleared_notification_keys'] ?? [];
$cleared_keys = array_unique(array_merge($cleared_keys, $current_keys));

$_SESSION['cleared_notification_keys'] = $cleared_keys;

header("Location: dashboard.php");
exit;
?>