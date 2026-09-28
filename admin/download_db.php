<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: /admin_login.php");
    exit();
}

$pdo = getDBConnection();
$filename = "empress3_db_backup_" . date('Y-m-d_H-i-s') . ".sql";

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "-- EMPRESS TWO WAY 3.0 DATABASE BACKUP DUMP\n";
echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
echo "-- Server: " . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\n\n";

$tables = ['admins', 'members', 'wallets', 'transactions', 'withdrawals', 'deposits', 'epins', 'member_rebirths', 'api_tokens'];

foreach ($tables as $table) {
    echo "-- --------------------------------------------------------\n";
    echo "-- Table structure & Data for table `$table`\n";
    echo "-- --------------------------------------------------------\n\n";

    $stmt = $pdo->query("SELECT * FROM $table");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($rows)) {
        foreach ($rows as $row) {
            $cols = array_keys($row);
            $escapedCols = array_map(function($col) { return "`$col`"; }, $cols);

            $vals = array_map(function($val) use ($pdo) {
                if ($val === null) return "NULL";
                return $pdo->quote($val);
            }, array_values($row));

            echo "INSERT INTO `$table` (" . implode(', ', $escapedCols) . ") VALUES (" . implode(', ', $vals) . ");\n";
        }
        echo "\n";
    }
}

exit();
