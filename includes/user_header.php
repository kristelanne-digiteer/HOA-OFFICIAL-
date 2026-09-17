<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('HOA_USER_SESSION');
    session_start();
}

if (!isset($conn)) {
    include("../config/db.php");
}

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

$user_id    = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id='$user_id' AND role='homeowner' LIMIT 1");

if ($user_query === false) {
    // Query mismo ang nabigo (timeout, brief hiccup, lock wait, etc).
    // Hindi ito dahilan para mag-logout — gamitin ang cached na info
    // mula sa session imbes na sirain ang session.
    $current_user = $_SESSION['user_cache'] ?? [
        'first_name'      => '',
        'last_name'       => '',
        'profile_picture' => null,
    ];
} else {
    $current_user = mysqli_fetch_assoc($user_query);

    if (!$current_user) {
        // Successful ang query pero talagang walang nahanap na row
        // (na-delete na account o nabago ang role) — dito lang dapat
        // mag-force logout.
        session_destroy();
        header("Location: ../login.php");
        exit;
    }

    // I-cache para magamit bilang fallback kapag mabigo ang query
    // sa susunod na refresh/page load.
    $_SESSION['user_cache'] = $current_user;
}

$user_name = trim($current_user['first_name'] . " " . $current_user['last_name']);

if (!empty($current_user['profile_picture'])) {
    $user_photo = "../assets/uploads/users/" . $current_user['profile_picture'];
} else {
    $user_photo = "../assets/css/uploads/default-user.png";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>WMS HOA User</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app">

    <aside class="sidebar">

        <div class="logo-area">
            <img src="../assets/css/uploads/logo.png" class="hoa-logo" alt="HOA Logo">
            <div class="portal-text">
                <div class="portal-title">SAPILARA<br>LOWER<br>PORTAL</div>
                <div class="admin-tag">USER</div>
            </div>
        </div>

        <nav class="nav">
            <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
            <a href="profile.php"   class="<?= $current_page == 'profile.php'   ? 'active' : '' ?>">Profile Management</a>
            <a href="payments.php"  class="<?= $current_page == 'payments.php'  ? 'active' : '' ?>">Payment Management</a>
            <a href="documents.php" class="<?= $current_page == 'documents.php' ? 'active' : '' ?>">Document Requests</a>
            <a href="rentals.php"   class="<?= $current_page == 'rentals.php'   ? 'active' : '' ?>">Rental Requests</a>
            <a href="events.php"    class="<?= $current_page == 'events.php'    ? 'active' : '' ?>">Events Calendar</a>
            <a href="support.php"   class="<?= $current_page == 'support.php'   ? 'active' : '' ?>">Help & Support</a>
            <a href="settings.php"  class="<?= $current_page == 'settings.php'  ? 'active' : '' ?>">Settings</a>
            <a href="../logout.php?role=user" style="margin-top:auto;color:#ef2b35;">Logout</a>
        </nav>

    </aside>

    <main class="main">

        <div class="topbar">
            <input class="search" type="text" placeholder="Search...">

            <div class="admin-profile">
                <img src="<?php echo htmlspecialchars($user_photo); ?>" class="admin-avatar" alt="User">
                <div>
                    <div class="admin-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="admin-role">Homeowner</div>
                </div>
            </div>
        </div>