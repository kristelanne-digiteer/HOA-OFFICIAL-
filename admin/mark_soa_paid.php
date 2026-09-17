<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../config/db.php");

$id = $_GET['id'];

$soa = mysqli_query($conn, "
    SELECT 
        s.*, 
        h.name,
        hp.user_id
    FROM soa_records s
    JOIN homeowners_masterlist h ON s.homeowner_id = h.homeowner_id
    LEFT JOIN homeowner_profiles hp ON h.homeowner_id = hp.masterlist_id
    WHERE s.soa_id='$id'
");

if (!$soa) {
    die("SOA query error: " . mysqli_error($conn));
}

$row = mysqli_fetch_assoc($soa);

if (!$row) {
    echo "<script>
        alert('SOA record not found.');
        window.location='payments.php';
    </script>";
    exit;
}

$receipt_number = "OR-" . date("Ymd") . "-" . str_pad($id, 4, "0", STR_PAD_LEFT);
$amount_paid = $row['monthly_dues'] + $row['penalties'];
$payment_purpose = $row['payment_purpose'] ?? 'Payment';
$user_id = $row['user_id'] ?? null;

$update_soa = mysqli_query($conn, "
    UPDATE soa_records
    SET status='paid',
        total_balance=0
    WHERE soa_id='$id'
");

if (!$update_soa) {
    die("SOA update error: " . mysqli_error($conn));
}

$insert_payment = mysqli_query($conn, "
    INSERT INTO payments
    (bill_id, soa_id, user_id, amount_paid, payment_method, receipt_number, status, payment_date, payment_purpose, hidden_by_user)
    VALUES
    (NULL, '$id', " . ($user_id ? "'$user_id'" : "NULL") . ", '$amount_paid', 'Cash', '$receipt_number', 'approved', NOW(), '$payment_purpose', 0)
");

if (!$insert_payment) {
    die("Payment insert error: " . mysqli_error($conn));
}

/* Update rental payment status if this SOA belongs to a rental */
mysqli_query($conn, "
    UPDATE rental_requests
    SET payment_status='paid'
    WHERE soa_id='$id'
");

echo "<script>
    alert('Marked as paid and receipt generated.');
    window.location='rentals.php';
</script>";
exit;
?>