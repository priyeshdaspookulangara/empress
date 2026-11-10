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
$wallet = $stmtW->fetch() ?: ['balance' => 0.00, 'user_wallet_50' => 0.00, 'burfee_cart_wallet' => 0.00, 'charity_wallet' => 0.00, 'user_wallet_60' => 0.00, 'company_wallet_40' => 0.00];

// Team counts & Rebirths
$downline = getMemberDownline6Levels($pdo, $memberId);
$totalRebirths = getMemberTotalRebirths($pdo, $memberId);

// Direct Referrals Count & Direct Referral Commission
$stmtRef = $pdo->prepare("SELECT COUNT(*) FROM members WHERE sponsor_id = ?");
$stmtRef->execute([$memberId]);
$directReferralsCount = (int)$stmtRef->fetchColumn();

$stmtRefInc = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE member_id = ? AND type = 'Direct_Referral' AND status = 'Credit'");
$stmtRefInc->execute([$memberId]);
$directReferralCommission = (float)$stmtRefInc->fetchColumn();

// Matrix Level Commission & Breakdown (Levels 1 to 6)
$stmtMatInc = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE member_id = ? AND type LIKE 'Matrix_Income%' AND status = 'Credit'");
$stmtMatInc->execute([$memberId]);
$totalLevelCommission = (float)$stmtMatInc->fetchColumn();

$stmtLvlBreakdown = $pdo->prepare("SELECT type, COALESCE(SUM(amount), 0) as total FROM transactions WHERE member_id = ? AND type LIKE 'Matrix_Income_L%' AND status = 'Credit' GROUP BY type");
$stmtLvlBreakdown->execute([$memberId]);
$levelCommissionsMap = $stmtLvlBreakdown->fetchAll(PDO::FETCH_KEY_PAIR);

$levelCommissionsByLevel = [];
$downlineCountsByLevel = array_fill(1, 6, 0);
foreach ($downline as $d) {
    $lvl = (int)($d['matrix_level'] ?? 0);
    if ($lvl >= 1 && $lvl <= 6) {
        $downlineCountsByLevel[$lvl]++;
    }
}
for ($i = 1; $i <= 6; $i++) {
    $levelCommissionsByLevel[$i] = [
        'level' => $i,
        'nodes_count' => $downlineCountsByLevel[$i],
        'commission' => (float)($levelCommissionsMap["Matrix_Income_L$i"] ?? 0.00)
    ];
}

// Total Approved Withdrawals
$stmtWd = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM withdrawals WHERE member_id = ? AND status = 'Approved'");
$stmtWd->execute([$memberId]);
$totalWithdrawn = (float)$stmtWd->fetchColumn();

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
            'user_wallet_50' => (float)($wallet['user_wallet_50'] ?? $wallet['user_wallet_60']),
            'burfee_cart_wallet' => (float)($wallet['burfee_cart_wallet'] ?? 0),
            'charity_wallet' => (float)($wallet['charity_wallet'] ?? 0),
            'total_withdrawn' => $totalWithdrawn
        ],
        'kpis' => [
            'direct_referrals_count' => $directReferralsCount,
            'direct_referral_commission' => $directReferralCommission,
            'total_level_commission' => $totalLevelCommission,
            'level_breakdown' => $levelCommissionsByLevel
        ],
        'network' => [
            'total_downline_count' => count($downline),
            'total_rebirths_earned' => $totalRebirths
        ],
        'recent_transactions' => $recentTx
    ]
]);
