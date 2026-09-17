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
    UPDATE rental_items
    SET status='damaged'
    WHERE item_id='$id'
");

echo "<script>
    alert('Item marked as damaged.');
    window.location='rentals.php';
</script>";
?>