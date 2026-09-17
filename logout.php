<?php

if (isset($_GET['role']) && $_GET['role'] == 'admin') {
    session_name('HOA_ADMIN_SESSION');
} else {
    session_name('HOA_USER_SESSION');
}

session_start();


if (!empty($_SESSION['user_id'])) {
    include("config/db.php");
    $role_label = ($_SESSION['role'] ?? 'user') === 'admin' ? 'Admin' : 'User';
    logActivity($conn, $_SESSION['user_id'], "$role_label logged out", "Authentication");
}


$_SESSION = array();


if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}


session_destroy();


header("Location: login.php");
exit;
?>