<?php
session_start();
require_once 'config/db.php';
require_once 'includes/functions.php';

if (isset($_SESSION['user_id'])) {
    log_activity('LOGOUT', 'users', $_SESSION['user_id'], $_SESSION['full_name'] . ' logged out');
}

session_destroy();
header("Location: login.php");
exit();
?>
