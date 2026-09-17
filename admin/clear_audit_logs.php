<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

mysqli_query($conn, "DELETE FROM audit_logs");

// Log the clearing action itself (so may record na si admin na nag-clear)
logActivity($conn, $_SESSION['user_id'], "Admin cleared all audit logs", "System");

echo "<script>
    alert('Audit logs cleared successfully.');
    window.location='audit.php';
</script>";
?>
