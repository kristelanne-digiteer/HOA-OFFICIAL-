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
    DELETE FROM rental_items
    WHERE item_id='$id'
");

echo "<script>
    alert('Rental item deleted successfully.');
    window.location='rentals.php';
</script>";
exit;
?>