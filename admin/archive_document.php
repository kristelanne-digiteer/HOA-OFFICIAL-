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
    DELETE FROM document_requests
    WHERE request_id='$id'
");

echo "<script>
    alert('Document request archived.');
    window.location='documents.php';
</script>";
?>