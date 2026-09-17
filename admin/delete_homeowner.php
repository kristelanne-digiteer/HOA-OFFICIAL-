<?php
session_name('HOA_ADMIN_SESSION');
session_start();
if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../login.php");
    exit;
}

include("../config/db.php");

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    $sql = "DELETE FROM homeowners_masterlist WHERE homeowner_id = '$id'";

    if (mysqli_query($conn, $sql)) {
        echo "<script>
            alert('Homeowner deleted successfully');
            window.location='homeowners.php';
        </script>";
    } else {
        echo "<script>
            alert('Cannot delete homeowner. This record may be linked to other records.');
            window.location='homeowners.php';
        </script>";
    }
} else {
    header("Location: homeowners.php");
}
?>