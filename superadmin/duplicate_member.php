<?php
// superadmin/duplicate_member.php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['superadmin_id'])) {
    header("Location: /superadmin_login.php");
    exit();
}

$sourceMemberId = trim($_GET['member_id'] ?? '');

if (empty($sourceMemberId)) {
    header("Location: /superadmin/members.php?err=" . urlencode("No member ID provided for duplication."));
    exit();
}

$pdo = getDBConnection();
$res = duplicateMemberAsIndependent($pdo, $sourceMemberId);

if ($res['success']) {
    header("Location: /superadmin/members.php?msg=" . urlencode($res['message']));
} else {
    header("Location: /superadmin/members.php?err=" . urlencode($res['message']));
}
exit();
