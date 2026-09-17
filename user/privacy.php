<?php
// privacy.php — now redirects to the new Settings page
session_name('HOA_USER_SESSION');
session_start();
header("Location: settings.php");
exit;
