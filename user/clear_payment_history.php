<?php
include("../config/db.php");
include("../includes/user_header.php");

$user_id = $_SESSION['user_id'];

mysqli_query($conn, "
    DELETE FROM payments
    WHERE user_id='$user_id'
");

echo "<script>
    alert('Payment history cleared.');
    window.location='payments.php';
</script>";
exit;
?>