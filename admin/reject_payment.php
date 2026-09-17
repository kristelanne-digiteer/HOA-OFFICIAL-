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

mysqli_query($conn, "UPDATE payments SET status='rejected' WHERE payment_id='$id'");

logActivity($conn, $admin_id, "Rejected payment ID #$id", "Payment");

echo "<script>
    alert('Payment rejected.');
    window.location='payments.php';
</script>";
?>