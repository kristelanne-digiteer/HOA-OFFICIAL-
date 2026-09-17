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

$receipt_number = "OR-" . date("Ymd") . "-" . str_pad($id, 4, "0", STR_PAD_LEFT);

$payment = mysqli_query($conn, "SELECT * FROM payments WHERE payment_id='$id'");
$row = mysqli_fetch_assoc($payment);

if ($row) {
    mysqli_query($conn, "
        UPDATE payments
        SET status='approved',
            receipt_number='$receipt_number'
        WHERE payment_id='$id'
    ");

    if (!empty($row['bill_id'])) {
        mysqli_query($conn, "
            UPDATE soa_records
            SET status='paid', total_balance=0
            WHERE soa_id='{$row['bill_id']}'
        ");
    }

    logActivity($conn, $admin_id, "Approved payment ID #$id (Receipt: $receipt_number)", "Payment");

    echo "<script>
        alert('Payment approved and receipt generated.');
        window.location='payments.php';
    </script>";
} else {
    echo "<script>
        alert('Payment not found.');
        window.location='payments.php';
    </script>";
}
?>