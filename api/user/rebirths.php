<?php
require_once __DIR__ . '/../api_helper.php';

$pdo = getDBConnection();
$tokenRow = authenticateApiUser($pdo, 'member');
$memberId = $tokenRow['user_id'];

// Fetch Member Details
$stmtM = $pdo->prepare("SELECT email FROM members WHERE member_id = ?");
$stmtM->execute([$memberId]);
$member = $stmtM->fetch();

if (!$member) {
    sendJsonResponse(['success' => false, 'message' => 'Member not found.'], 404);
}

// Fetch Rewards
$stmtRewards = $pdo->prepare("SELECT completed_level, rebirth_count, created_at FROM member_rebirths WHERE member_id = ? ORDER BY completed_level ASC");
$stmtRewards->execute([$memberId]);
$rebirthRewards = $stmtRewards->fetchAll();

$totalRebirthsEarned = getMemberTotalRebirths($pdo, $memberId);

// Fetch Rebirth Positions in Tree
$stmtNodes = $pdo->prepare("
    SELECT member_id, sponsor_id, placement_parent_id, matrix_position, name, status, created_at
    FROM members
    WHERE (email = ? AND (used_epin LIKE 'REBIRTH_%' OR name LIKE '%Rebirth%'))
       OR (sponsor_id = ?)
    ORDER BY id ASC
");
$stmtNodes->execute([$member['email'], $memberId]);
$rebirthNodes = $stmtNodes->fetchAll();

sendJsonResponse([
    'success' => true,
    'data' => [
        'member_id' => $memberId,
        'total_rebirths_earned' => $totalRebirthsEarned,
        'rebirth_rewards' => $rebirthRewards,
        'rebirth_positions' => $rebirthNodes
    ]
]);
