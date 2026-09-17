<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

$completed = mysqli_query($conn, "
    SELECT rental_id
    FROM rental_requests
    WHERE status='completed'
");

while ($row = mysqli_fetch_assoc($completed)) {
    $rental_id = $row['rental_id'];

    mysqli_query($conn, "
        DELETE FROM rental_request_items
        WHERE rental_id='$rental_id'
    ");
}

mysqli_query($conn, "
    DELETE FROM rental_requests
    WHERE status='completed'
");

echo "<script>
    alert('Completed rental records cleared.');
    window.location='rentals.php';
</script>";
exit;
?>