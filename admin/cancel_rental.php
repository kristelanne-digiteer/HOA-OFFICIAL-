<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

$id = $_GET['id'];

mysqli_query($conn, "
    UPDATE rental_requests
    SET status='cancelled',
        payment_status='cancelled'
    WHERE rental_id='$id'
");

echo "<script>
    alert('Rental reservation cancelled.');
    window.location='rentals.php';
</script>";
exit;
?>