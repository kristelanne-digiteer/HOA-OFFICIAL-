<?php
session_name('HOA_USER_SESSION');
session_start();
include("../config/db.php");

$user_id = $_SESSION['user_id'];

$profile_q = mysqli_query($conn, "
    SELECT masterlist_id
    FROM homeowner_profiles
    WHERE user_id='$user_id'
");

$profile = mysqli_fetch_assoc($profile_q);
$masterlist_id = $profile['masterlist_id'] ?? 0;

mysqli_query($conn, "
    DELETE FROM soa_records
    WHERE homeowner_id='$masterlist_id'
    AND status='paid'
");

echo "<script>
    alert('Paid bills cleared from current bills.');
    window.location='payments.php';
</script>";
exit;
?>