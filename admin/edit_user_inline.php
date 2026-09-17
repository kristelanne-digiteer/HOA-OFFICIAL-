<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: users.php");
    exit;
}

$user_id    = (int) $_POST['user_id'];
$first_name = mysqli_real_escape_string($conn, trim($_POST['first_name']));
$last_name  = mysqli_real_escape_string($conn, trim($_POST['last_name']));
$email      = mysqli_real_escape_string($conn, trim($_POST['email']));
$role       = mysqli_real_escape_string($conn, $_POST['role']);
$status     = mysqli_real_escape_string($conn, $_POST['status']);

if ($user_id <= 0 || empty($first_name) || empty($last_name) || empty($email)) {
    echo "<script>alert('Invalid input.'); window.location='users.php';</script>";
    exit;
}

// Check for duplicate email (excluding current user)
$email_check = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email' AND user_id != '$user_id' LIMIT 1");
if (mysqli_num_rows($email_check) > 0) {
    echo "<script>alert('Email is already used by another account.'); window.history.back();</script>";
    exit;
}

mysqli_query($conn, "
    UPDATE users
    SET first_name = '$first_name',
        last_name  = '$last_name',
        email      = '$email',
        role       = '$role',
        status     = '$status'
    WHERE user_id  = '$user_id'
");

echo "<script>alert('User updated successfully.'); window.location='users.php';</script>";
exit;
?>