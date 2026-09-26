<?php
// includes/functions.php

require_once __DIR__ . '/../config/db.php';

// Level payout schedule in USD ($) (Level 1 to 6)
// Converted from handwritten schedule ratio (500/1000/2000/3000/4000/5000)
const MATRIX_PAYOUTS = [
    1 => 5.00,
    2 => 10.00,
    3 => 20.00,
    4 => 30.00,
    5 => 40.00,
    6 => 50.00,
];

// Matrix node capacity per level
const MATRIX_LEVEL_CAPACITY = [
    1 => 3,
    2 => 9,
    3 => 27,
    4 => 81,
    5 => 243,
    6 => 729
];

// Rebirth Rewards per Level Completion Schedule
const REBIRTH_REWARDS = [
    3 => 10,
    4 => 20,
    5 => 70,
    6 => 100
];

/**
 * Generate next unique Member ID (e.g., EMP100001)
 */
function generateMemberID($pdo) {
    $stmt = $pdo->query("SELECT member_id FROM members WHERE member_id LIKE 'EMP%' AND member_id != 'EMP100000' ORDER BY id DESC LIMIT 1");
    $lastId = $stmt->fetchColumn();

    if (!$lastId) {
        return 'EMP100001';
    }

    $num = (int)substr($lastId, 3);
    $nextNum = max(100001, $num + 1);
    return 'EMP' . $nextNum;
}

/**
 * Generate unique ePIN code
 */
function generateEpinCode($prefix = 'EMP') {
    return $prefix . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Global BFS Auto-Spillover Matrix Placement Algorithm
 * Returns ['placement_parent_id' => string, 'matrix_position' => int]
 */
function findBFSMatrixPlacement($pdo, $startMemberId = 'EMP100000') {
    // Verify start member exists
    $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ? AND status = 'Active'");
    $stmt->execute([$startMemberId]);
    if (!$stmt->fetch()) {
        $startMemberId = 'EMP100000';
    }

    $queue = [$startMemberId];

    while (!empty($queue)) {
        $currentParent = array_shift($queue);

        // Fetch current children of $currentParent ordered by position
        $stmt = $pdo->prepare("SELECT member_id, matrix_position FROM members WHERE placement_parent_id = ? ORDER BY matrix_position ASC");
        $stmt->execute([$currentParent]);
        $children = $stmt->fetchAll();

        $takenPositions = array_column($children, 'matrix_position');

        // Check if there is space (max 3 children)
        if (count($children) < 3) {
            for ($pos = 1; $pos <= 3; $pos++) {
                if (!in_array($pos, $takenPositions)) {
                    return [
                        'placement_parent_id' => $currentParent,
                        'matrix_position' => $pos
                    ];
                }
            }
        }

        // If currentParent is full, push children into queue in order 1, 2, 3
        usort($children, function($a, $b) {
            return $a['matrix_position'] <=> $b['matrix_position'];
        });

        foreach ($children as $child) {
            $queue[] = $child['member_id'];
        }
    }

    return [
        'placement_parent_id' => 'EMP100000',
        'matrix_position' => 1
    ];
}

/**
 * Process Matrix Level Commissions and Helping Contributions for 6 Levels above $newMemberId
 * Applies 50:50 Smart Wallet division (50% Customer Wallet, 50% Company -> 60% Burfee Cart / 40% Charity).
 * Helping contribution logic:
 * - Join: $5.00 (500 INR) matrix payout credited to direct parent (Level 1).
 * - When 3 direct joins complete: $10.00 (1000 INR) upgrade helping contribution passed to grandparent (Level 2).
 * - When 9 Level 2 members complete: $20.00 (2000 INR) upgrade helping contribution passed to grand-grandparent (Level 3).
 */
function distributeMatrixCommissions($pdo, $newMemberId) {
    $stmt = $pdo->prepare("SELECT placement_parent_id FROM members WHERE member_id = ?");
    $stmt->execute([$newMemberId]);
    $currentParentId = $stmt->fetchColumn();

    $level = 1;

    while ($currentParentId && $level <= 6) {
        $amount = MATRIX_PAYOUTS[$level] ?? 0;

        if ($amount > 0) {
            $userAmount = round($amount * 0.50, 2);       // 50% Customer Wallet
            $burfeeAmount = round($amount * 0.30, 2);     // 60% of Company 50% = 30% total
            $charityAmount = round($amount * 0.20, 2);    // 40% of Company 50% = 20% total
            $companyAmount = round($amount * 0.50, 2);    // Total Company Portion (30% Burfee + 20% Charity)

            // Ensure parent wallet row exists
            $stmtWallet = $pdo->prepare("SELECT member_id FROM wallets WHERE member_id = ?");
            $stmtWallet->execute([$currentParentId]);
            if (!$stmtWallet->fetch()) {
                $insW = $pdo->prepare("INSERT INTO wallets (member_id, balance, user_wallet_50, burfee_cart_wallet, charity_wallet, user_wallet_60, company_wallet_40) VALUES (?, 0, 0, 0, 0, 0, 0)");
                $insW->execute([$currentParentId]);
            }

            // Update parent wallet
            $updateWallet = $pdo->prepare("
                UPDATE wallets
                SET balance = balance + ?,
                    user_wallet_50 = user_wallet_50 + ?,
                    burfee_cart_wallet = burfee_cart_wallet + ?,
                    charity_wallet = charity_wallet + ?,
                    user_wallet_60 = user_wallet_60 + ?,
                    company_wallet_40 = company_wallet_40 + ?
                WHERE member_id = ?
            ");
            $updateWallet->execute([$amount, $userAmount, $burfeeAmount, $charityAmount, $userAmount, $companyAmount, $currentParentId]);

            // Log Transaction
            $logTx = $pdo->prepare("
                INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
                VALUES (?, ?, ?, 'Main', 'Credit', ?)
            ");
            $txType = "Matrix_Income_L" . $level;
            $desc = "Level {$level} Helping Contribution / Matrix Commission from member {$newMemberId}. (50% Customer: \${$userAmount}, Company 50%: \${$burfeeAmount} Burfee Cart / \${$charityAmount} Charity)";
            $logTx->execute([$currentParentId, $txType, $amount, $desc]);
        }

        // Check for level completion rebirth triggers
        checkAndGrantLevelRebirths($pdo, $currentParentId, $level);

        // Move to next parent up
        $stmtParent = $pdo->prepare("SELECT placement_parent_id FROM members WHERE member_id = ?");
        $stmtParent->execute([$currentParentId]);
        $currentParentId = $stmtParent->fetchColumn();

        $level++;
    }
}

/**
 * Check level completion and grant automated Rebirths:
 * Level 3 Completion => 10 Rebirths
 * Level 4 Completion => 20 Rebirths
 * Level 5 Completion => 70 Rebirths
 * Level 6 Completion => 100 Rebirths
 */
function checkAndGrantLevelRebirths($pdo, $memberId, $level) {
    if (!isset(REBIRTH_REWARDS[$level])) {
        return;
    }

    $requiredNodes = MATRIX_LEVEL_CAPACITY[$level];

    // Count downline members specifically at this level depth relative to $memberId
    $currentLevelMembers = [$memberId];
    for ($l = 1; $l <= $level; $l++) {
        if (empty($currentLevelMembers)) break;
        $inClause = implode(',', array_fill(0, count($currentLevelMembers), '?'));
        $stmt = $pdo->prepare("SELECT member_id FROM members WHERE placement_parent_id IN ($inClause)");
        $stmt->execute($currentLevelMembers);
        $currentLevelMembers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    $nodeCountAtLevel = count($currentLevelMembers);

    if ($nodeCountAtLevel >= $requiredNodes) {
        // Check if rebirths were already granted for this level
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM member_rebirths WHERE member_id = ? AND completed_level = ?");
        $stmtCheck->execute([$memberId, $level]);

        if ($stmtCheck->fetchColumn() == 0) {
            $rebirthCount = REBIRTH_REWARDS[$level];

            // Record rebirth reward entry
            $stmtIns = $pdo->prepare("INSERT INTO member_rebirths (member_id, completed_level, rebirth_count) VALUES (?, ?, ?)");
            $stmtIns->execute([$memberId, $level, $rebirthCount]);

            // Create Rebirth Positions in the global 3-matrix tree
            createRebirthPositions($pdo, $memberId, $rebirthCount, $level);
        }
    }
}

/**
 * Create rebirth spillover positions in global 3-matrix
 */
function createRebirthPositions($pdo, $parentMemberId, $count, $completedLevel) {
    $stmtM = $pdo->prepare("SELECT name, email, phone, package_type, used_epin, sponsor_id FROM members WHERE member_id = ?");
    $stmtM->execute([$parentMemberId]);
    $parent = $stmtM->fetch();

    if (!$parent) return;

    // Check if parent node is itself a rebirth position
    $isRebirthNode = (strpos($parent['used_epin'], 'REBIRTH_') === 0 || strpos($parent['name'], 'Rebirth') !== false);

    // Referral ID (sponsor_id) for 2nd generation rebirths (rebirths generated by a rebirth node) will be 'EMP100000' (Root)
    $sponsorId = $isRebirthNode ? 'EMP100000' : $parentMemberId;

    for ($i = 1; $i <= $count; $i++) {
        $placement = findBFSMatrixPlacement($pdo, 'EMP100000');
        $placementParentId = $placement['placement_parent_id'];
        $matrixPos = $placement['matrix_position'];

        $rebirthMemberId = generateMemberID($pdo);
        $rebirthName = $parent['name'] . " (Rebirth #{$i} - L{$completedLevel})";
        $dummyEpin = "REBIRTH_L" . $completedLevel . "_" . bin2hex(random_bytes(3));
        $passHash = password_hash('REBIRTH_NODE', PASSWORD_BCRYPT);

        // Insert Rebirth Member
        $stmtIns = $pdo->prepare("
            INSERT INTO members (member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, password, used_epin, package_type, status, kyc_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 'Approved')
        ");
        $stmtIns->execute([
            $rebirthMemberId,
            $sponsorId,
            $placementParentId,
            $matrixPos,
            $rebirthName,
            $parent['email'],
            $parent['phone'],
            $passHash,
            $dummyEpin,
            $parent['package_type']
        ]);

        // Initialize Wallet for Rebirth Node
        $stmtW = $pdo->prepare("INSERT INTO wallets (member_id, balance, user_wallet_50, burfee_cart_wallet, charity_wallet, user_wallet_60, company_wallet_40) VALUES (?, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00)");
        $stmtW->execute([$rebirthMemberId]);

        // Log transaction for rebirth reward creation
        $stmtTx = $pdo->prepare("
            INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
            VALUES (?, 'Admin_Adjustment', 0.00, 'Main', 'Credit', ?)
        ");
        $desc = "Rebirth position {$rebirthMemberId} created automatically upon Level {$completedLevel} completion.";
        $stmtTx->execute([$parentMemberId, $desc]);
    }
}

/**
 * Get total rebirths granted to a member
 */
function getMemberTotalRebirths($pdo, $memberId) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(rebirth_count), 0) FROM member_rebirths WHERE member_id = ?");
    $stmt->execute([$memberId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Get all active rebirth member nodes created in matrix for a user
 */
function getMemberRebirthNodes($pdo, $memberId) {
    $stmtM = $pdo->prepare("SELECT email FROM members WHERE member_id = ?");
    $stmtM->execute([$memberId]);
    $email = $stmtM->fetchColumn();

    $stmtNodes = $pdo->prepare("
        SELECT * FROM members
        WHERE (email = ? AND (used_epin LIKE 'REBIRTH_%' OR name LIKE '%Rebirth%'))
           OR (sponsor_id = ? AND (used_epin LIKE 'REBIRTH_%' OR name LIKE '%Rebirth%'))
        ORDER BY id ASC
    ");
    $stmtNodes->execute([$email, $memberId]);
    return $stmtNodes->fetchAll();
}

/**
 * Get aggregated earnings and wallet balances across all rebirth nodes for a member
 */
function getAggregateRebirthEarnings($pdo, $memberId) {
    $rebirthNodes = getMemberRebirthNodes($pdo, $memberId);
    $nodeIds = array_column($rebirthNodes, 'member_id');

    $totals = [
        'count' => count($rebirthNodes),
        'total_balance' => 0.00,
        'user_wallet_50' => 0.00,
        'burfee_cart_wallet' => 0.00,
        'charity_wallet' => 0.00,
        'nodes' => []
    ];

    if (empty($nodeIds)) {
        return $totals;
    }

    $inClause = implode(',', array_fill(0, count($nodeIds), '?'));
    $stmtW = $pdo->prepare("
        SELECT member_id, balance, user_wallet_50, burfee_cart_wallet, charity_wallet, user_wallet_60, company_wallet_40
        FROM wallets
        WHERE member_id IN ($inClause)
    ");
    $stmtW->execute($nodeIds);
    $wallets = $stmtW->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

    foreach ($rebirthNodes as $rn) {
        $nid = $rn['member_id'];
        $w = $wallets[$nid] ?? ['balance' => 0, 'user_wallet_50' => 0, 'burfee_cart_wallet' => 0, 'charity_wallet' => 0, 'user_wallet_60' => 0];

        $uW = $w['user_wallet_50'] > 0 ? $w['user_wallet_50'] : $w['user_wallet_60'];
        $bal = (float)$w['balance'];
        $burfee = (float)($w['burfee_cart_wallet'] ?? 0);
        $charity = (float)($w['charity_wallet'] ?? 0);

        $totals['total_balance'] += $bal;
        $totals['user_wallet_50'] += $uW;
        $totals['burfee_cart_wallet'] += $burfee;
        $totals['charity_wallet'] += $charity;

        $rn['wallet'] = [
            'balance' => $bal,
            'user_wallet_50' => $uW,
            'burfee_cart_wallet' => $burfee,
            'charity_wallet' => $charity
        ];
        $totals['nodes'][] = $rn;
    }

    return $totals;
}

/**
 * Register a new member with ePIN validation and BFS Matrix placement
 */
function registerMember($pdo, $data) {
    $sponsorId = !empty($data['sponsor_id']) ? trim($data['sponsor_id']) : 'EMP100000';
    $name = trim($data['name']);
    $email = trim($data['email']);
    $phone = trim($data['phone']);
    $password = password_hash($data['password'], PASSWORD_BCRYPT);
    $epinCode = trim($data['epin_code']);

    // Check sponsor exists
    $stmtSponsor = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ? AND status = 'Active'");
    $stmtSponsor->execute([$sponsorId]);
    if (!$stmtSponsor->fetch()) {
        $sponsorId = 'EMP100000';
    }

    // Validate ePIN
    $stmtEpin = $pdo->prepare("SELECT * FROM epins WHERE epin_code = ? AND status = 'Unused'");
    $stmtEpin->execute([$epinCode]);
    $epin = $stmtEpin->fetch();

    if (!$epin) {
        return ['success' => false, 'message' => 'Invalid or already used ePIN code.'];
    }

    $packageType = $epin['package_type'];

    // Find BFS Matrix Placement
    $placement = findBFSMatrixPlacement($pdo, 'EMP100000');
    $placementParentId = $placement['placement_parent_id'];
    $matrixPos = $placement['matrix_position'];

    $memberId = generateMemberID($pdo);

    $pdo->beginTransaction();

    try {
        // Insert Member
        $stmtIns = $pdo->prepare("
            INSERT INTO members (member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, password, used_epin, package_type, status, kyc_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 'Pending')
        ");
        $stmtIns->execute([
            $memberId,
            $sponsorId,
            $placementParentId,
            $matrixPos,
            $name,
            $email,
            $phone,
            $password,
            $epinCode,
            $packageType
        ]);

        // Mark ePIN as used
        $stmtUpdateEpin = $pdo->prepare("UPDATE epins SET status = 'Used', used_by_member_id = ? WHERE id = ?");
        $stmtUpdateEpin->execute([$memberId, $epin['id']]);

        // Initialize Wallet
        $stmtWallet = $pdo->prepare("INSERT INTO wallets (member_id, balance, user_wallet_50, burfee_cart_wallet, charity_wallet, user_wallet_60, company_wallet_40) VALUES (?, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00)");
        $stmtWallet->execute([$memberId]);

        // Distribute Matrix Commissions across ancestors
        distributeMatrixCommissions($pdo, $memberId);

        $pdo->commit();

        return [
            'success' => true,
            'member_id' => $memberId,
            'message' => 'Registration successful! Your Member ID is ' . $memberId
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

/**
 * Get visual tree data for a member up to 3 levels deep
 */
function getMemberMatrixTree($pdo, $memberId) {
    $stmt = $pdo->prepare("SELECT member_id, name, package_type, kyc_status, created_at FROM members WHERE member_id = ?");
    $stmt->execute([$memberId]);
    $node = $stmt->fetch();

    if (!$node) return null;

    $stmtChildren = $pdo->prepare("SELECT member_id, name, package_type, matrix_position, kyc_status FROM members WHERE placement_parent_id = ? ORDER BY matrix_position ASC");
    $stmtChildren->execute([$memberId]);
    $rawChildren = $stmtChildren->fetchAll();

    $childrenByPos = [];
    foreach ($rawChildren as $child) {
        $childrenByPos[$child['matrix_position']] = $child;
    }

    $node['children'] = [];
    for ($pos = 1; $pos <= 3; $pos++) {
        if (isset($childrenByPos[$pos])) {
            $childMemberId = $childrenByPos[$pos]['member_id'];
            $node['children'][$pos] = getMemberMatrixTree($pdo, $childMemberId);
        } else {
            $node['children'][$pos] = null; // Empty slot
        }
    }

    return $node;
}

/**
 * Get 6-Level Downline List for a Member
 */
function getMemberDownline6Levels($pdo, $memberId) {
    $downline = [];
    $currentLevelMembers = [$memberId];

    for ($level = 1; $level <= 6; $level++) {
        if (empty($currentLevelMembers)) break;

        $inClause = implode(',', array_fill(0, count($currentLevelMembers), '?'));
        $stmt = $pdo->prepare("
            SELECT member_id, sponsor_id, placement_parent_id, name, email, phone, package_type, status, created_at
            FROM members
            WHERE placement_parent_id IN ($inClause)
            ORDER BY created_at ASC
        ");
        $stmt->execute($currentLevelMembers);
        $levelMembers = $stmt->fetchAll();

        if (empty($levelMembers)) break;

        $nextLevelIds = [];
        foreach ($levelMembers as $m) {
            $m['matrix_level'] = $level;
            $downline[] = $m;
            $nextLevelIds[] = $m['member_id'];
        }

        $currentLevelMembers = $nextLevelIds;
    }

    return $downline;
}

/**
 * Submit USDT Fund Deposit Request
 */
function submitFundDeposit($pdo, $userId, $txHash, $amount, $network = 'BEP-20') {
    $txHash = trim($txHash);
    $amount = (float)$amount;

    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Deposit amount must be greater than 0 USDT.'];
    }

    if (!preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash) && !preg_match('/^0x[a-fA-F0-9]+$/', $txHash)) {
        return ['success' => false, 'message' => 'Invalid Transaction Hash format. Must start with "0x" followed by valid hexadecimal character string.'];
    }

    // Check if Tx Hash already submitted
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM deposits WHERE tx_hash = ?");
    $stmtCheck->execute([$txHash]);
    if ($stmtCheck->fetchColumn() > 0) {
        return ['success' => false, 'message' => 'This Transaction Hash / TxID has already been submitted.'];
    }

    try {
        $stmtIns = $pdo->prepare("
            INSERT INTO deposits (user_id, tx_hash, amount, network, status)
            VALUES (?, ?, ?, ?, 'Pending')
        ");
        $stmtIns->execute([$userId, $txHash, $amount, $network]);

        return [
            'success' => true,
            'message' => 'USDT (BEP-20) deposit request submitted successfully! Pending verification by financial auditor.'
        ];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Failed to submit deposit: ' . $e->getMessage()];
    }
}

/**
 * Get user deposit history
 */
function getUserDeposits($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM deposits WHERE user_id = ? ORDER BY id DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Safe Member Deletion: Re-parents matrix children to Root EMP100000
 */
function deleteMemberSafely($pdo, $memberIdToDelete) {
    if ($memberIdToDelete === 'EMP100000') {
        return ['success' => false, 'message' => 'Cannot delete system root member EMP100000.'];
    }

    $pdo->beginTransaction();
    try {
        // Re-parent direct children of deleted member to Root EMP100000 or find new BFS placement under Root
        $stmtChildren = $pdo->prepare("SELECT member_id FROM members WHERE placement_parent_id = ?");
        $stmtChildren->execute([$memberIdToDelete]);
        $children = $stmtChildren->fetchAll(PDO::FETCH_COLUMN);

        foreach ($children as $childId) {
            $placement = findBFSMatrixPlacement($pdo, 'EMP100000');
            $stmtReparent = $pdo->prepare("UPDATE members SET placement_parent_id = ?, matrix_position = ? WHERE member_id = ?");
            $stmtReparent->execute([$placement['placement_parent_id'], $placement['matrix_position'], $childId]);
        }

        // Delete from wallets, transactions, withdrawals, api_tokens, epins, members
        $pdo->prepare("DELETE FROM wallets WHERE member_id = ?")->execute([$memberIdToDelete]);
        $pdo->prepare("DELETE FROM withdrawals WHERE member_id = ?")->execute([$memberIdToDelete]);
        $pdo->prepare("DELETE FROM api_tokens WHERE user_id = ?")->execute([$memberIdToDelete]);
        $pdo->prepare("UPDATE epins SET used_by_member_id = NULL, status = 'Unused' WHERE used_by_member_id = ?")->execute([$memberIdToDelete]);
        $pdo->prepare("DELETE FROM members WHERE member_id = ?")->execute([$memberIdToDelete]);

        $pdo->commit();
        return ['success' => true, 'message' => "Member {$memberIdToDelete} deleted and children safely re-parented."];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()];
    }
}
