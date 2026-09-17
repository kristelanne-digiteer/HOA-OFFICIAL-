<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

$id = $_GET['id'];

$q = mysqli_query($conn, "
    SELECT d.*, hp.masterlist_id
    FROM document_requests d
    LEFT JOIN homeowner_profiles hp ON d.user_id = hp.user_id
    WHERE d.request_id='$id'
");

$row = mysqli_fetch_assoc($q);

if (!$row) {
    echo "<script>
        alert('Document request not found.');
        window.location='documents.php';
    </script>";
    exit;
}


$homeowner_id = !empty($row['masterlist_id']) ? $row['masterlist_id'] : $row['user_id'];

$fee = 50.00;
$billing_month = date("F Y");
$due_date = date("Y-m-d", strtotime("+7 days"));
$purpose = "Document Fee - " . $row['document_type'];

mysqli_query($conn, "
    INSERT INTO soa_records
    (homeowner_id, billing_month, monthly_dues, penalties, total_balance, due_date, status, payment_purpose)
    VALUES
    ('{$homeowner_id}', '$billing_month', '$fee', 0, '$fee', '$due_date', 'unpaid', '$purpose')
");

$soa_id = mysqli_insert_id($conn);

mysqli_query($conn, "
    UPDATE document_requests
    SET status='approved',
        document_fee='$fee',
        soa_id='$soa_id'
    WHERE request_id='$id'
");

echo "<script>
    alert('Document request approved and billing created.');
    window.location='documents.php';
</script>";
?>