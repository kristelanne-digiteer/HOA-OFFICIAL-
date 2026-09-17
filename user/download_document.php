<?php
session_name('HOA_USER_SESSION');
session_start();
include("../config/db.php");

$user_id = $_SESSION['user_id'];
$id = $_GET['id'];

$result = mysqli_query($conn, "
    SELECT *
    FROM document_requests
    WHERE request_id='$id'
    AND user_id='$user_id'
");

$row = mysqli_fetch_assoc($result);

if (!$row) {
    die("Document request not found.");
}

if ($row['status'] != 'completed') {
    die("Document is not yet completed.");
}

if (empty($row['released_file'])) {
    die("No released document uploaded yet.");
}

$file = "../assets/uploads/documents/" . $row['released_file'];

if (!file_exists($file)) {
    die("Released document file not found.");
}

header("Content-Type: application/octet-stream");
header("Content-Disposition: attachment; filename=\"" . basename($file) . "\"");
header("Content-Length: " . filesize($file));
readfile($file);
exit;
?>