<?php
require_once __DIR__ . '/../api_helper.php';

$pdo = getDBConnection();
$tokenRow = authenticateApiUser($pdo, 'member');
$memberId = $tokenRow['user_id'];

// Handle Withdrawal Request Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();
    $amount = (float)($input['amount'] ?? 0);

    // Fetch Member Details & KYC
    $stmtM = $pdo->prepare("SELECT kyc_status FROM members WHERE member_id = ?");
    $stmtM->execute([$memberId]);
    $kycStatus = $stmtM->fetchColumn();

    // Fetch Wallet
    $stmtW = $pdo->prepare("SELECT user_wallet_50, user_wallet_60 FROM wallets WHERE member_id = ?");
    $stmtW->execute([$memberId]);
    $wRow = $stmtW->fetch() ?: [];
    $userWallet = (float)($wRow['user_wallet_50'] ?? $wRow['user_wallet_60'] ?? 0);

    if ($kycStatus !== 'Approved') {
        sendJsonResponse(['success' => false, 'message' => "Withdrawal blocked: KYC status must be Approved. Current status: {$kycStatus}"], 403);
    }

    if ($amount < 10.00) {
        sendJsonResponse(['success' => false, 'message' => "Withdrawal blocked: Minimum withdrawal amount is $10.00 USD."], 400);
    }

    if ($amount > $userWallet) {
        sendJsonResponse(['success' => false, 'message' => "Withdrawal blocked: Insufficient balance in Customer Wallet (50%). Available: $" . number_format($userWallet, 2) . " USD"], 400);
    }

    $pdo->beginTransaction();
    try {
        // Deduct
        $stmtDeduct = $pdo->prepare("UPDATE wallets SET user_wallet_50 = user_wallet_50 - ?, user_wallet_60 = user_wallet_60 - ? WHERE member_id = ?");
        $stmtDeduct->execute([$amount, $amount, $memberId]);

        // Insert Request
        $stmtWithdraw = $pdo->prepare("INSERT INTO withdrawals (member_id, amount, status) VALUES (?, ?, 'Pending')");
        $stmtWithdraw->execute([$memberId, $amount]);

        // Log Tx
        $stmtTx = $pdo->prepare("
            INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
            VALUES (?, 'Withdrawal_Request', ?, 'User_Wallet', 'Debit', ?)
        ");
        $stmtTx->execute([$memberId, $amount, "Withdrawal request of $" . number_format($amount, 2) . " submitted via Mobile API."]);

        $pdo->commit();
        sendJsonResponse(['success' => true, 'message' => "Withdrawal request of $" . number_format($amount, 2) . " submitted successfully."]);

    } catch (Exception $e) {
        $pdo->rollBack();
        sendJsonResponse(['success' => false, 'message' => "Withdrawal request failed: " . $e->getMessage()], 500);
    }
}

// GET request: Return balances, withdrawals, and transactions
$stmtW = $pdo->prepare("SELECT * FROM wallets WHERE member_id = ?");
$stmtW->execute([$memberId]);
$wallet = $stmtW->fetch() ?: ['balance' => 0.00, 'user_wallet_60' => 0.00, 'company_wallet_40' => 0.00];

$stmtWList = $pdo->prepare("SELECT * FROM withdrawals WHERE member_id = ? ORDER BY id DESC");
$stmtWList->execute([$memberId]);
$withdrawals = $stmtWList->fetchAll();

$stmtTxList = $pdo->prepare("SELECT * FROM transactions WHERE member_id = ? ORDER BY id DESC");
$stmtTxList->execute([$memberId]);
$transactions = $stmtTxList->fetchAll();

sendJsonResponse([
    'success' => true,
    'data' => [
        'wallet' => [
            'balance' => (float)$wallet['balance'],
            'user_wallet_50' => (float)($wallet['user_wallet_50'] ?? $wallet['user_wallet_60']),
            'burfee_cart_wallet' => (float)($wallet['burfee_cart_wallet'] ?? 0),
            'charity_wallet' => (float)($wallet['charity_wallet'] ?? 0)
        ],
        'withdrawals' => $withdrawals,
        'transactions' => $transactions
    ]
]);
