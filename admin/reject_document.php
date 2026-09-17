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

$q   = mysqli_query($conn, "SELECT document_type FROM document_requests WHERE request_id='$id'");
$row = mysqli_fetch_assoc($q);

mysqli_query($conn, "UPDATE document_requests SET status='rejected' WHERE request_id='$id'");

logActivity($conn, $admin_id, "Rejected document request ID #$id (" . ($row['document_type'] ?? '') . ")", "Document");

echo "<script>
    alert('Document request rejected.');
    window.location='documents.php';
</script>";
?>