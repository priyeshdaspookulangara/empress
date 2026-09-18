<?php
require_once __DIR__ . '/../api_helper.php';

$pdo = getDBConnection();
$tokenRow = authenticateApiUser($pdo, 'member');
$memberId = $tokenRow['user_id'];

// Member Profile
$stmt = $pdo->prepare("SELECT member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, package_type, status, kyc_status, created_at FROM members WHERE member_id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

// Wallet
$stmtW = $pdo->prepare("SELECT * FROM wallets WHERE member_id = ?");
$stmtW->execute([$memberId]);
$wallet = $stmtW->fetch() ?: ['balance' => 0.00, 'user_wallet_60' => 0.00, 'company_wallet_40' => 0.00];

// Team counts
$downline = getMemberDownline6Levels($pdo, $memberId);

// Recent Transactions
$stmtTx = $pdo->prepare("SELECT id, type, amount, wallet_type, status, description, created_at FROM transactions WHERE member_id = ? ORDER BY id DESC LIMIT 5");
$stmtTx->execute([$memberId]);
$recentTx = $stmtTx->fetchAll();

sendJsonResponse([
    'success' => true,
    'data' => [
        'profile' => $member,
        'wallet' => [
            'balance' => (float)$wallet['balance'],
            'user_wallet_60' => (float)$wallet['user_wallet_60'],
            'company_wallet_40' => (float)$wallet['company_wallet_40']
        ],
        'network' => [
            'total_downline_count' => count($downline)
        ],
        'recent_transactions' => $recentTx
    ]
]);
