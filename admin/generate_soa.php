<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

$billing_month = "July 2026";
$monthly_dues = 500.00;
$due_date = "2026-07-30";

$check = mysqli_query($conn, "
    SELECT * FROM soa_records 
    WHERE billing_month = '$billing_month'
");

if (mysqli_num_rows($check) > 0) {
    echo "<script>
        alert('SOA for $billing_month already exists.');
        window.location='payments.php';
    </script>";
    exit;
}

$sql = "
    INSERT INTO soa_records
    (homeowner_id, billing_month, monthly_dues, penalties, total_balance, due_date, status)
    SELECT
        homeowner_id,
        '$billing_month',
        '$monthly_dues',
        0.00,
        '$monthly_dues',
        '$due_date',
        'unpaid'
    FROM homeowners_masterlist
";

if (mysqli_query($conn, $sql)) {
    echo "<script>
        alert('SOA records generated successfully.');
        window.location='payments.php';
    </script>";
} else {
    echo "<script>
        alert('Error generating SOA.');
        window.location='dashboard.php';
    </script>";
}
?>