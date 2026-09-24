<?php
// config/db.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'empress3_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $driver = getenv('DB_DRIVER') ?: 'mysql';

    if ($driver === 'sqlite') {
        return getSQLiteConnection();
    }

    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Fallback to SQLite if MySQL fails
        return getSQLiteConnection();
    }
}

function getSQLiteConnection() {
    static $sqlitePdo = null;
    if ($sqlitePdo !== null) {
        return $sqlitePdo;
    }

    $dbFile = __DIR__ . '/../empress3.sqlite';
    $isNew = !file_exists($dbFile);

    $sqlitePdo = new PDO("sqlite:" . $dbFile, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    initDatabaseSchema($sqlitePdo, true);

    return $sqlitePdo;
}

function initDatabaseSchema($pdo, $isSqlite = false) {
    $autoInc = $isSqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $timestamp = $isSqlite ? "DATETIME DEFAULT CURRENT_TIMESTAMP" : "TIMESTAMP DEFAULT CURRENT_TIMESTAMP";

    $queries = [
        "CREATE TABLE IF NOT EXISTS admins (
            id $autoInc,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'admin',
            created_at $timestamp
        )",

        "CREATE TABLE IF NOT EXISTS epins (
            id $autoInc,
            epin_code VARCHAR(50) NOT NULL UNIQUE,
            package_type VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'Unused',
            generated_by_admin_id INT DEFAULT NULL,
            used_by_member_id VARCHAR(50) DEFAULT NULL,
            assigned_to VARCHAR(50) DEFAULT NULL,
            created_at $timestamp
        )",

        "CREATE TABLE IF NOT EXISTS members (
            id $autoInc,
            member_id VARCHAR(20) NOT NULL UNIQUE,
            sponsor_id VARCHAR(20) DEFAULT NULL,
            placement_parent_id VARCHAR(20) DEFAULT NULL,
            matrix_position INT DEFAULT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            password VARCHAR(255) NOT NULL,
            used_epin VARCHAR(50) NOT NULL,
            package_type VARCHAR(50) NOT NULL,
            profile_image VARCHAR(255) DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'Active',
            address_line VARCHAR(255) DEFAULT NULL,
            place VARCHAR(100) DEFAULT NULL,
            city VARCHAR(100) DEFAULT NULL,
            pincode VARCHAR(20) DEFAULT NULL,
            state VARCHAR(100) DEFAULT NULL,
            pan_number VARCHAR(20) DEFAULT NULL,
            aadhaar_number VARCHAR(20) DEFAULT NULL,
            bank_name VARCHAR(100) DEFAULT NULL,
            bank_account_number VARCHAR(50) DEFAULT NULL,
            ifsc_code VARCHAR(20) DEFAULT NULL,
            crypto_wallet_address VARCHAR(255) DEFAULT NULL,
            wallet_network VARCHAR(50) DEFAULT 'USDT (TRC20)',
            kyc_status VARCHAR(20) NOT NULL DEFAULT 'Pending',
            created_at $timestamp
        )",

        "CREATE TABLE IF NOT EXISTS wallets (
            id $autoInc,
            member_id VARCHAR(20) NOT NULL UNIQUE,
            balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            user_wallet_50 DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            burfee_cart_wallet DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            charity_wallet DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            user_wallet_60 DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            company_wallet_40 DECIMAL(12,2) NOT NULL DEFAULT 0.00
        )",

        "CREATE TABLE IF NOT EXISTS transactions (
            id $autoInc,
            member_id VARCHAR(50) NOT NULL,
            type VARCHAR(50) NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            wallet_type VARCHAR(20) NOT NULL DEFAULT 'Main',
            status VARCHAR(20) NOT NULL DEFAULT 'Credit',
            description TEXT,
            created_at $timestamp
        )",

        "CREATE TABLE IF NOT EXISTS withdrawals (
            id $autoInc,
            member_id VARCHAR(20) NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'Pending',
            request_date $timestamp,
            processed_date DATETIME NULL DEFAULT NULL
        )",

        "CREATE TABLE IF NOT EXISTS api_tokens (
            id $autoInc,
            user_type VARCHAR(20) NOT NULL,
            user_id VARCHAR(50) NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at $timestamp
        )",

        "CREATE TABLE IF NOT EXISTS member_rebirths (
            id $autoInc,
            member_id VARCHAR(20) NOT NULL,
            completed_level INT NOT NULL,
            rebirth_count INT NOT NULL,
            created_at $timestamp
        )"
    ];

    foreach ($queries as $q) {
        $pdo->exec($q);
    }

    // Ensure role and wallet columns exist if SQLite/MySQL table already created
    try {
        $pdo->exec("ALTER TABLE admins ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'admin'");
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE wallets ADD COLUMN user_wallet_50 DECIMAL(12,2) NOT NULL DEFAULT 0.00");
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE wallets ADD COLUMN burfee_cart_wallet DECIMAL(12,2) NOT NULL DEFAULT 0.00");
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE wallets ADD COLUMN charity_wallet DECIMAL(12,2) NOT NULL DEFAULT 0.00");
    } catch (Exception $e) {}

    // Recalculate and correct existing wallets data and names once
    static $migrationExecuted = false;
    if (!$migrationExecuted) {
        $migrationExecuted = true;
        $flagFile = __DIR__ . '/../.migration_5050_applied';
        if (!file_exists($flagFile)) {
            try {
                $wallets = $pdo->query("SELECT member_id, balance FROM wallets WHERE balance > 0")->fetchAll();
                foreach ($wallets as $w) {
                    $mId = $w['member_id'];
                    $bal = (float)$w['balance'];

                    $stmtW = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE member_id = ? AND status != 'Rejected'");
                    $stmtW->execute([$mId]);
                    $withdrawals = (float)$stmtW->fetchColumn();

                    $userWallet50 = max(0.00, round(($bal * 0.50) - $withdrawals, 2));
                    $burfeeCart = round($bal * 0.30, 2);
                    $charity = round($bal * 0.20, 2);

                    $stmtUp = $pdo->prepare("UPDATE wallets SET user_wallet_50 = ?, burfee_cart_wallet = ?, charity_wallet = ? WHERE member_id = ?");
                    $stmtUp->execute([$userWallet50, $burfeeCart, $charity, $mId]);
                }

                $pdo->exec("UPDATE members SET name = 'Burfee Cart' WHERE member_id != 'EMP100000'");
                @file_put_contents($flagFile, date('Y-m-d H:i:s'));
            } catch (Exception $e) {
                // Data correction query fallback
            }
        }
    }

    // Seed superadmin if not existing
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
    $stmt->execute(['superadmin']);
    if ($stmt->fetchColumn() == 0) {
        $superPass = password_hash('superadmin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute(['superadmin', $superPass, 'superadmin']);
    }

    // Seed standard admin if not existing
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE username = ?");
    $stmt->execute(['admin']);
    if ($stmt->fetchColumn() == 0) {
        $adminPass = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO admins (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $adminPass, 'admin']);
    }

    // Seed Root Member (EMP100000) if not existing
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE member_id = ?");
    $stmt->execute(['EMP100000']);
    if ($stmt->fetchColumn() == 0) {
        $rootPass = password_hash('root123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO members (member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, password, used_epin, package_type, status, kyc_status)
            VALUES (?, NULL, NULL, NULL, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute(['EMP100000', 'Empress Root', 'root@empress2way.com', '9999999999', $rootPass, 'SYSTEM_ROOT_EPIN', 'Starter_1000', 'Active', 'Approved']);

        $stmt = $pdo->prepare("INSERT INTO wallets (member_id, balance, user_wallet_50, burfee_cart_wallet, charity_wallet, user_wallet_60, company_wallet_40) VALUES (?, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00)");
        $stmt->execute(['EMP100000']);
    }
}
