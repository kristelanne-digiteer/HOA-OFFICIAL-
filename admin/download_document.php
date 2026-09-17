<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

$id = $_GET['id'];

$result = mysqli_query($conn, "
    SELECT * FROM document_requests
    WHERE request_id='$id'
");

$row = mysqli_fetch_assoc($result);

if (!$row) {
    echo "Document not found.";
    exit;
}

if ($row['document_type'] == 'Certificate of Residency') {
    header("Location: https://canva.link/4zzt4l3ctsknqqz");
    exit;
}

if ($row['document_type'] == 'SOA') {
    header("Location: payments.php");
    exit;
}

echo "No downloadable template available.";
?>