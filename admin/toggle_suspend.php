<?php
require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

$memberId = trim($_GET['member_id'] ?? '');

if (empty($memberId) || $memberId === 'EMP100000') {
    header("Location: /admin/members.php?err=" . urlencode("Cannot modify Root account."));
    exit();
}

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT status FROM members WHERE member_id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    header("Location: /admin/members.php?err=" . urlencode("Member not found."));
    exit();
}

$newStatus = ($member['status'] === 'Active') ? 'Inactive' : 'Active';
$stmtUp = $pdo->prepare("UPDATE members SET status = ? WHERE member_id = ?");
$stmtUp->execute([$newStatus, $memberId]);

$msg = "Member {$memberId} status updated to {$newStatus}.";
header("Location: /admin/members.php?msg=" . urlencode($msg));
exit();
