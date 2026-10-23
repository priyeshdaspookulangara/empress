<?php
// achiever_club.php - Root Router Script for Achiever Club Console
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['member_id'])) {
    header("Location: /customer/achiever_club.php");
    exit();
} elseif (isset($_SESSION['superadmin_id'])) {
    header("Location: /superadmin/achiever_club.php");
    exit();
} elseif (isset($_SESSION['admin_id'])) {
    header("Location: /admin/achiever_club.php");
    exit();
} else {
    header("Location: /login.php");
    exit();
}
