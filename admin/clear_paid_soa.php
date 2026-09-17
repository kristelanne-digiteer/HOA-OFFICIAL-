<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

mysqli_query($conn, "
    DELETE FROM soa_records
    WHERE status='paid'
");

echo "<script>
    alert('All paid SOA records cleared successfully.');
    window.location='payments.php';
</script>";
exit;
?>