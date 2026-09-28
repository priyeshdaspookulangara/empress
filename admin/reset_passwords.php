<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commonPassword = $_POST['common_password'] ?? '';

    if (strlen($commonPassword) < 4) {
        header("Location: /admin/members.php?err=" . urlencode("Common password must be at least 4 characters long."));
        exit();
    }

    $pdo = getDBConnection();
    $hashed = password_hash($commonPassword, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("UPDATE members SET password = ? WHERE member_id != 'EMP100000'");
    $stmt->execute([$hashed]);
    $count = $stmt->rowCount();

    // Also update Root password if desired
    $stmtRoot = $pdo->prepare("UPDATE members SET password = ? WHERE member_id = 'EMP100000'");
    $stmtRoot->execute([$hashed]);

    header("Location: /admin/members.php?msg=" . urlencode("Successfully updated common password for all member accounts ({$count} members updated)."));
    exit();
}

header("Location: /admin/members.php");
exit();
