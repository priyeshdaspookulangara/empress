<?php
/**
 * Cron / CLI Background Automated USDT Deposit Verification Script
 * Executed periodically via crontab (e.g. * * * * * php /path/to/cron/verify_deposits.php)
 */

if (php_sapi_name() !== 'cli' && (!isset($_GET['key']) || $_GET['key'] !== 'empress_cron_key_123')) {
    die("CLI access or valid security key required.");
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();

echo "[" . date('Y-m-d H:i:s') . "] Starting automated USDT (BEP-20) deposit verification...\n";

$stmtPend = $pdo->query("SELECT id, user_id, tx_hash, amount FROM deposits WHERE status = 'Pending' ORDER BY id ASC");
$pendingList = $stmtPend->fetchAll();

if (empty($pendingList)) {
    echo "[" . date('Y-m-d H:i:s') . "] No pending deposits found. Exiting.\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Found " . count($pendingList) . " pending deposit(s).\n";

$approvedCount = 0;
$failedCount = 0;

foreach ($pendingList as $dep) {
    echo " -> Checking Deposit #{$dep['id']} (Member: {$dep['user_id']}, TxHash: {$dep['tx_hash']})... ";

    $verResult = verifyBscTransactionOnChain($dep['tx_hash']);

    if ($verResult['verified']) {
        $appRes = approveDepositAndCreditWallet($pdo, $dep['id'], "Automated Cron Blockchain Verification");
        if ($appRes['success']) {
            echo "VERIFIED & CREDITED (\${$dep['amount']} USDT)!\n";
            $approvedCount++;
        } else {
            echo "FAILED TO CREDIT: " . $appRes['message'] . "\n";
            $failedCount++;
        }
    } else {
        echo "UNVERIFIED ON CHAIN: " . $verResult['message'] . "\n";
        $failedCount++;
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Cron completed: {$approvedCount} deposit(s) approved and credited. {$failedCount} unverified.\n";
