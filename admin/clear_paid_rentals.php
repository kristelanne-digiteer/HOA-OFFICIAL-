<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

/* Get rental IDs that are done: cancelled, rejected, completed, or approved+paid */
$to_clear = mysqli_query($conn, "
    SELECT r.rental_id
    FROM rental_requests r
    LEFT JOIN soa_records s ON r.soa_id = s.soa_id
    WHERE r.status IN ('cancelled', 'rejected', 'completed')
       OR (r.status = 'approved' AND s.status = 'paid')
");

if (!$to_clear || mysqli_num_rows($to_clear) == 0) {
    echo "<script>
        alert('Nothing to clear.');
        window.location='rentals.php';
    </script>";
    exit;
}

while ($row = mysqli_fetch_assoc($to_clear)) {
    $rental_id = $row['rental_id'];
    mysqli_query($conn, "
        DELETE FROM rental_request_items
        WHERE rental_id='$rental_id'
    ");
}

mysqli_query($conn, "
    DELETE r FROM rental_requests r
    LEFT JOIN soa_records s ON r.soa_id = s.soa_id
    WHERE r.status IN ('cancelled', 'rejected', 'completed')
       OR (r.status = 'approved' AND s.status = 'paid')
");

echo "<script>
    alert('Rental requests cleared.');
    window.location='rentals.php';
</script>";
exit;
?>