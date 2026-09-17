<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

$id = $_GET['id'];
$admin_id = $_SESSION['user_id'];

mysqli_query($conn, "UPDATE rental_requests SET status='rejected' WHERE rental_id='$id'");

logActivity($conn, $admin_id, "Rejected rental request ID #$id", "Rental");

echo "<script>alert('Rental request rejected.'); window.location='rentals.php';</script>";
?>