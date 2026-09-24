<?php
require_once __DIR__ . '/../api_helper.php';

$pdo = getDBConnection();
authenticateApiUser($pdo, 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();
    $withdrawalId = (int)($input['withdrawal_id'] ?? 0);
    $action = $input['action'] ?? '';

    if ($withdrawalId <= 0 || !in_array($action, ['Approve', 'Reject'])) {
        sendJsonResponse(['success' => false, 'message' => 'withdrawal_id and action (Approve/Reject) required.'], 400);
    }

    $stmtW = $pdo->prepare("SELECT w.*, m.kyc_status FROM withdrawals w JOIN members m ON w.member_id = m.member_id WHERE w.id = ?");
    $stmtW->execute([$withdrawalId]);
    $w = $stmtW->fetch();

    if (!$w || $w['status'] !== 'Pending') {
        sendJsonResponse(['success' => false, 'message' => 'Withdrawal request not found or not pending.'], 404);
    }

    $pdo->beginTransaction();
    try {
        if ($action === 'Approve') {
            if ($w['kyc_status'] !== 'Approved') {
                sendJsonResponse(['success' => false, 'message' => "Cannot approve payout: Member KYC status is {$w['kyc_status']}."], 400);
            }

            $stmtApprove = $pdo->prepare("UPDATE withdrawals SET status = 'Approved', processed_date = CURRENT_TIMESTAMP WHERE id = ?");
            $stmtApprove->execute([$withdrawalId]);

            sendJsonResponse(['success' => true, 'message' => "Payout request #WD-{$withdrawalId} APPROVED."]);
        } else {
            $stmtReject = $pdo->prepare("UPDATE withdrawals SET status = 'Rejected', processed_date = CURRENT_TIMESTAMP WHERE id = ?");
            $stmtReject->execute([$withdrawalId]);

            $stmtRefund = $pdo->prepare("UPDATE wallets SET user_wallet_50 = user_wallet_50 + ?, user_wallet_60 = user_wallet_60 + ? WHERE member_id = ?");
            $stmtRefund->execute([$w['amount'], $w['amount'], $w['member_id']]);

            $stmtTx = $pdo->prepare("
                INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
                VALUES (?, 'Admin_Adjustment', ?, 'User_Wallet', 'Credit', ?)
            ");
            $stmtTx->execute([$w['member_id'], $w['amount'], "Refund for rejected withdrawal request #WD-{$withdrawalId}."]);

            sendJsonResponse(['success' => true, 'message' => "Payout request #WD-{$withdrawalId} REJECTED and refunded."]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        sendJsonResponse(['success' => false, 'message' => "Processing failed: " . $e->getMessage()], 500);
    }
}

// GET request
$stmt = $pdo->query("
    SELECT w.*, m.name, m.email, m.phone, m.kyc_status, m.bank_name, m.bank_account_number, m.ifsc_code
    FROM withdrawals w
    JOIN members m ON w.member_id = m.member_id
    ORDER BY FIELD(w.status, 'Pending', 'Approved', 'Rejected'), w.id DESC
");
$withdrawals = $stmt->fetchAll();

sendJsonResponse([
    'success' => true,
    'data' => $withdrawals
]);
