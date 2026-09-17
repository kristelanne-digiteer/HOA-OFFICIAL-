<?php
session_name('HOA_USER_SESSION');
session_start();
include("../config/db.php");

$user_id = $_SESSION['user_id'];

mysqli_query($conn, "
    UPDATE payments
    SET hidden_by_user = 1
    WHERE user_id='$user_id'
    AND status='approved'
");

echo "<script>
    alert('Paid records cleared from your history.');
    window.location='payments.php';
</script>";
exit;
?>