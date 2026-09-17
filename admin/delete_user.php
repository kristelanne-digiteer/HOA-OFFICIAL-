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

$check = mysqli_query($conn, "SELECT user_id, first_name, last_name FROM users WHERE user_id='$id' LIMIT 1");
if (mysqli_num_rows($check) == 0) {
    echo "<script>alert('User not found.'); window.location='homeowners.php';</script>";
    exit;
}

$user = mysqli_fetch_assoc($check);

mysqli_query($conn, "DELETE FROM users WHERE user_id='$id'");

$display_name = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
echo "<script>alert('{$display_name} has been deleted.'); window.location='homeowners.php';</script>";
exit;
?>
