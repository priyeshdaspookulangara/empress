<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: /superadmin_login.php");
    exit();
}

$pdo = getDBConnection();
$res = resetDatabaseToCleanState($pdo);

if ($res['success']) {
    header("Location: /superadmin/members.php?msg=" . urlencode($res['message']));
} else {
    header("Location: /superadmin/members.php?err=" . urlencode($res['message']));
}
exit();
