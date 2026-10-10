<?php
/**
 * Cron Worker Script: Process Scheduled Rebirths
 * Reyon Global Impact Platform
 *
 * Runs background check for pending queued rebirths whose scheduled_at time has arrived.
 * Can be run via CLI cron (e.g., `* * * * * php /path/to/cron/process_rebirths.php`)
 */

// Allow CLI execution or web access with secret key / admin context
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../includes/functions.php';

    // Simple key or session check if accessed via HTTP
    $key = $_GET['key'] ?? '';
    if ($key !== 'empress_cron_secret_2025' && (!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['superadmin_logged_in']))) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized cron trigger']);
        exit;
    }
} else {
    require_once __DIR__ . '/../config/db.php';
    require_once __DIR__ . '/../includes/functions.php';
}

try {
    $pdo = getDBConnection();
    processScheduledRebirthQueue($pdo);

    $stmtCount = $pdo->query("SELECT COUNT(*) FROM queued_rebirths WHERE status = 'Pending'");
    $pendingCount = $stmtCount->fetchColumn();

    if (php_sapi_name() === 'cli') {
        echo "[" . date('Y-m-d H:i:s') . "] Rebirth queue processed successfully. Pending items remaining: " . $pendingCount . "\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Rebirth queue processed', 'pending_remaining' => $pendingCount]);
    }
} catch (Exception $e) {
    if (php_sapi_name() === 'cli') {
        echo "[" . date('Y-m-d H:i:s') . "] ERROR: " . $e->getMessage() . "\n";
    } else {
        header('Content-Type: application/json', true, 500);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
