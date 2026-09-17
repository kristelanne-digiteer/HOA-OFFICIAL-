<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

mysqli_query($conn,"
    DELETE FROM document_requests
    WHERE status='rejected'
");

echo "<script>
alert('Rejected requests cleared.');
window.location='documents.php';
</script>";
exit;
?>