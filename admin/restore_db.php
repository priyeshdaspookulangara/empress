<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
    $file = $_FILES['backup_file'];

    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        header("Location: /admin/members.php?err=" . urlencode("No backup file uploaded."));
        exit();
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'sql') {
        header("Location: /admin/members.php?err=" . urlencode("Invalid file format. Please upload a valid .sql backup file."));
        exit();
    }

    $sqlContent = file_get_contents($file['tmp_name']);
    if (empty($sqlContent)) {
        header("Location: /admin/members.php?err=" . urlencode("Uploaded SQL backup file is empty."));
        exit();
    }

    $pdo = getDBConnection();

    try {
        $pdo->beginTransaction();

        $queries = explode(";\n", $sqlContent);
        $executed = 0;

        foreach ($queries as $q) {
            $trimmed = trim($q);
            if (empty($trimmed) || strpos($trimmed, '--') === 0) {
                continue;
            }
            $pdo->exec($trimmed);
            $executed++;
        }

        $pdo->commit();

        header("Location: /admin/members.php?msg=" . urlencode("Database backup successfully restored! ({$executed} statements executed)."));
        exit();

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: /admin/members.php?err=" . urlencode("Database restore failed: " . $e->getMessage()));
        exit();
    }
}

header("Location: /admin/members.php");
exit();
