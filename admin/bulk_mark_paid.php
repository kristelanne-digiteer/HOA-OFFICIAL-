<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

if (isset($_POST['selected_soa'])) {

    foreach ($_POST['selected_soa'] as $soa_id) {

        $soa = mysqli_query($conn, "SELECT * FROM soa_records WHERE soa_id='$soa_id'");
        $row = mysqli_fetch_assoc($soa);

        if ($row && $row['status'] != 'paid') {

            $receipt_number = "OR-" . date("Ymd") . "-" . str_pad($soa_id, 4, "0", STR_PAD_LEFT);
            $amount_paid = $row['monthly_dues'] + $row['penalties'];
            $purpose = $row['payment_purpose'] ?? 'Monthly Dues';

            mysqli_query($conn, "
                UPDATE soa_records
                SET status='paid',
                    total_balance=0
                WHERE soa_id='$soa_id'
            ");

            mysqli_query($conn, "
                INSERT INTO payments
                (bill_id, soa_id, user_id, amount_paid, payment_method, receipt_number, status, payment_date, payment_purpose)
                VALUES
                (NULL, '$soa_id', NULL, '$amount_paid', 'Cash', '$receipt_number', 'approved', NOW(), '$purpose')
            ");
        }
    }

    echo "<script>
        alert('Selected SOA records marked as paid and receipts generated.');
        window.location='payments.php';
    </script>";

} else {
    echo "<script>
        alert('No SOA selected.');
        window.location='payments.php';
    </script>";
}
?>