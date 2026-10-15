<?php
/**
 * SQLite to MySQL Data Migration Tool
 * Reyon Global Impact Platform
 *
 * Usage:
 *   php migrate_sqlite_to_mysql.php [sqlite_file_path]
 *
 * Example:
 *   DB_HOST=127.0.0.1 DB_NAME=empress3_db DB_USER=root DB_PASS=secret php migrate_sqlite_to_mysql.php
 */

require_once __DIR__ . '/config/db.php';

echo "=======================================================\n";
echo " REYON GLOBAL IMPACT - SQLITE TO MYSQL DATA MIGRATION \n";
echo "=======================================================\n\n";

// 1. Locate SQLite database file
$sqliteFile = $argv[1] ?? __DIR__ . '/empress3.sqlite';

if (!file_exists($sqliteFile)) {
    echo "ERROR: SQLite database file not found at: {$sqliteFile}\n";
    echo "Please specify the path to your SQLite database file.\n";
    echo "Usage: php migrate_sqlite_to_mysql.php /path/to/empress3.sqlite\n";
    exit(1);
}

echo "[1/4] Connecting to SQLite database ({$sqliteFile})... ";
try {
    $sqlitePdo = new PDO("sqlite:" . $sqliteFile, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "SUCCESS!\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// 2. Connect to Target MySQL database
$host = getenv('DB_HOST') ?: '127.0.0.1';
$dbname = getenv('DB_NAME') ?: 'empress3_db';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

echo "[2/4] Connecting to MySQL database ({$user}@{$host}/{$dbname})... ";
try {
    // First attempt to connect directly to the database
    $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
    $mysqlPdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    echo "SUCCESS!\n";
} catch (Exception $e) {
    // Attempt to create database if it doesn't exist
    try {
        echo "\n      Database '{$dbname}' not found or unreachable. Attempting auto-creation... ";
        $rootDsn = "mysql:host={$host};charset=utf8mb4";
        $rootPdo = new PDO($rootDsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $mysqlPdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        echo "SUCCESS!\n";
    } catch (Exception $ex) {
        echo "FAILED: " . $ex->getMessage() . "\n";
        echo "Please check your MySQL server credentials and ensure the MySQL service is running.\n";
        exit(1);
    }
}

// 3. Initialize MySQL Schema
echo "[3/4] Initializing MySQL Database Schema... ";
try {
    initDatabaseSchema($mysqlPdo, false);
    echo "SUCCESS!\n";
} catch (Exception $e) {
    echo "FAILED: " . $e->getMessage() . "\n";
    exit(1);
}

// 4. Migrate Data Table by Table
echo "[4/4] Migrating Data from SQLite to MySQL...\n";

$tables = [
    'admins',
    'epins',
    'members',
    'wallets',
    'transactions',
    'withdrawals',
    'api_tokens',
    'member_rebirths',
    'queued_rebirths',
    'deposits'
];

try {
    $mysqlPdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    $totalRecords = 0;
    foreach ($tables as $table) {
        // Check if table exists in SQLite
        $checkStmt = $sqlitePdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
        if (!$checkStmt->fetch()) {
            echo "  - Table `{$table}`: Not present in SQLite source, skipping.\n";
            continue;
        }

        // Retrieve rows from SQLite
        $rowsStmt = $sqlitePdo->query("SELECT * FROM `{$table}`");
        $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);
        $count = count($rows);

        if ($count === 0) {
            echo "  - Table `{$table}`: 0 records found.\n";
            continue;
        }

        // Get table columns
        $columns = array_keys($rows[0]);
        $escapedCols = implode(', ', array_map(function($col) { return "`{$col}`"; }, $columns));
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));

        $insertSql = "REPLACE INTO `{$table}` ({$escapedCols}) VALUES ({$placeholders})";
        $insertStmt = $mysqlPdo->prepare($insertSql);

        $inserted = 0;
        $mysqlPdo->beginTransaction();
        foreach ($rows as $row) {
            $insertStmt->execute(array_values($row));
            $inserted++;
        }
        $mysqlPdo->commit();

        $totalRecords += $inserted;
        echo "  - Table `{$table}`: Successfully migrated {$inserted} / {$count} records.\n";
    }

    $mysqlPdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n=======================================================\n";
    echo " MIGRATION COMPLETE! Total Records Transferred: {$totalRecords} \n";
    echo "=======================================================\n";
    echo "Next Steps:\n";
    echo "1. Update your environment file (.env) or server env variables:\n";
    echo "   DB_DRIVER=mysql\n";
    echo "   DB_HOST={$host}\n";
    echo "   DB_NAME={$dbname}\n";
    echo "   DB_USER={$user}\n";
    echo "   DB_PASS=" . ($pass ? "*****" : "(empty)") . "\n";
    echo "2. Restart your web server or PHP application process if cached.\n\n";

} catch (Exception $e) {
    if ($mysqlPdo->inTransaction()) {
        $mysqlPdo->rollBack();
    }
    $mysqlPdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "\nERROR DURING DATA MIGRATION: " . $e->getMessage() . "\n";
    exit(1);
}
