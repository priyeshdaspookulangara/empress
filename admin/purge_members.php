<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

$pdo = getDBConnection();
$res = resetDatabaseToCleanState($pdo);

if ($res['success']) {
    header("Location: /admin/members.php?msg=" . urlencode($res['message']));
} else {
    header("Location: /admin/members.php?err=" . urlencode($res['message']));
}
exit();
