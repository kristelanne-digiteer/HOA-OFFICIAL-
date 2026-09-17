<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

/* delete child records first */
mysqli_query($conn,"
DELETE FROM rental_request_items
WHERE rental_id IN (
    SELECT rental_id
    FROM rental_requests
    WHERE status IN ('rejected','cancelled','completed')
)
");

/* delete rentals */
mysqli_query($conn,"
DELETE FROM rental_requests
WHERE status IN ('rejected','cancelled','completed')
");

header("Location: rentals.php");
exit;
?>