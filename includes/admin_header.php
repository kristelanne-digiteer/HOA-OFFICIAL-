<?php
if (session_status() === PHP_SESSION_NONE) {
    session_name('HOA_ADMIN_SESSION');
    session_start();
}

if (!isset($conn)) {
    include("../config/db.php");
}

// Redirect to login if not logged in or not admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);

$admin_id    = $_SESSION['user_id'];
$admin_query = mysqli_query($conn, "SELECT * FROM users WHERE user_id='$admin_id' AND role='admin' LIMIT 1");

if ($admin_query === false) {
    // Query itself failed (timeout, brief DB hiccup, lock wait, etc).
    // Hindi ito dahilan para mag-logout — gamitin lang ang cached na info
    // mula sa session (ito mismo ang na-set noong successful login/last load).
    $admin = $_SESSION['admin_cache'] ?? [
        'first_name'      => '',
        'last_name'       => '',
        'profile_picture' => null,
    ];
} else {
    $admin = mysqli_fetch_assoc($admin_query);

    if (!$admin) {
        // Successful ang query pero walang nahanap na row — ibig sabihin
        // talagang na-delete na ang account o na-revoke na ang admin role.
        // Dito lang dapat mag-trigger ng force logout.
        session_destroy();
        header("Location: ../login.php");
        exit;
    }

    // I-cache para magamit bilang fallback kung sakaling mabigo ang query
    // sa susunod na page load/refresh.
    $_SESSION['admin_cache'] = $admin;
}

// Keep $admin_photo available for all admin pages that use it
$admin_photo = "../assets/css/uploads/admin.jpg";
if (!empty($admin['profile_picture'])) {
    $admin_photo = "../assets/uploads/users/" . $admin['profile_picture'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>WMS HOA Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="app">

    <aside class="sidebar">

        <div class="logo-area">
            <img src="../assets/css/uploads/logo.png" class="hoa-logo" alt="HOA Logo">
            <div class="portal-text">
                <div class="portal-title">SAPILARA<br>LOWER<br>PORTAL</div>
                <div class="admin-tag">ADMIN</div>
            </div>
        </div>

        <nav class="nav">
            <a href="dashboard.php"  class="<?= $current_page == 'dashboard.php'  ? 'active' : '' ?>">Dashboard</a>
            <a href="profile.php"    class="<?= $current_page == 'profile.php'    ? 'active' : '' ?>">My Profile</a>
            <a href="homeowners.php" class="<?= $current_page == 'homeowners.php' ? 'active' : '' ?>">Homeowners Management</a>
            <a href="payments.php"   class="<?= $current_page == 'payments.php'   ? 'active' : '' ?>">Payments & Billing</a>
            <a href="documents.php"  class="<?= $current_page == 'documents.php'  ? 'active' : '' ?>">Document Requests</a>
            <a href="rentals.php"    class="<?= $current_page == 'rentals.php'    ? 'active' : '' ?>">Rental Management</a>
            <a href="events.php"     class="<?= $current_page == 'events.php'     ? 'active' : '' ?>">Events & Announcements</a>
            <a href="reports.php"    class="<?= $current_page == 'reports.php'    ? 'active' : '' ?>">Reports & Analytics</a>
            <a href="users.php"      class="<?= $current_page == 'users.php'      ? 'active' : '' ?>">User Access Management</a>
            <a href="settings.php"   class="<?= $current_page == 'settings.php'   ? 'active' : '' ?>">Settings</a>
            <a href="audit.php"      class="<?= $current_page == 'audit.php'      ? 'active' : '' ?>">Audit Logs</a>
            <a href="../logout.php?role=admin" style="margin-top:auto;color:#ef2b35;">Logout</a>
        </nav>

    </aside>

    <main class="main">

        <div class="topbar">
            <input class="search" type="text" placeholder="Search...">

            <div class="admin-profile">
                <img src="<?php echo htmlspecialchars($admin_photo); ?>" class="admin-avatar" alt="Admin">
                <div>
                    <div class="admin-name"><?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></div>
                    <div class="admin-role">Administrator</div>
                </div>
            </div>
        </div>