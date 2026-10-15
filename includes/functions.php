<?php
// includes/functions.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/email.php';

// Level payout schedule in USD ($) (Level 1 to 7)
const DIRECT_REFERRAL_AMOUNT = 1.00; // 10% of $10.00 joining package

const MATRIX_PAYOUTS = [
    1 => 5.00,
    2 => 10.00,
    3 => 20.00,
    4 => 30.00,
    5 => 40.00,
    6 => 50.00,
    7 => 60.00
];

// Matrix node capacity per level (3^level)
const MATRIX_LEVEL_CAPACITY = [
    1 => 3,
    2 => 9,
    3 => 27,
    4 => 81,
    5 => 243,
    6 => 729,
    7 => 2187
];

// Rebirth Rewards per Level Completion Schedule
// Level 7: 50% of total gross amount ($131,220 * 0.50 = $65,610 -> 6,561 Rebirths @ $10 package)
const REBIRTH_REWARDS = [
    3 => 10,
    4 => 20,
    5 => 70,
    6 => 100,
    7 => 6561
];

// Configurable Rebirth Drip Time Interval (Minutes between consecutive rebirth placements for the same member)
const REBIRTH_INTERVAL_MINUTES = 15;

// Master Matrix Level Specification with Helping Fund & Rebirth Cost Deductions
const MATRIX_LEVEL_SPECS = [
    1 => [
        'capacity' => 3,
        'gross_per_node' => 5.00,             // $5.00
        'helping_per_node' => 3.33333333333,  // Total $10.00 / 3
        'net_pool_per_node' => 1.66666666667, // $1.6667
        'user_wallet_per_node' => 0.83333333333, // Total $2.50 / 3
        'burfee_cart_per_node' => 0.50,        // Total $1.50 / 3
        'charity_per_node' => 0.33333333333,    // Total $1.00 / 3
        'rebirth_cost_per_node' => 0.00,
    ],
    2 => [
        'capacity' => 9,
        'gross_per_node' => 10.00,            // $10.00
        'helping_per_node' => 2.22222222222,  // Total $20.00 / 9
        'net_pool_per_node' => 7.77777777778, // $7.7778
        'user_wallet_per_node' => 3.88888888889, // Total $35.00 / 9
        'burfee_cart_per_node' => 2.33333333333, // Total $21.00 / 9
        'charity_per_node' => 1.55555555556,    // Total $14.00 / 9
        'rebirth_cost_per_node' => 0.00,
    ],
    3 => [
        'capacity' => 27,
        'gross_per_node' => 20.00,            // $20.00
        'helping_per_node' => 4.44444444444,  // Total $120.00 / 27
        'net_pool_per_node' => 15.55555555556, // Total $420.00 / 27
        'user_wallet_per_node' => 4.07407407407, // Total $110.00 / 27
        'burfee_cart_per_node' => 4.66666666667, // Total $126.00 / 27
        'charity_per_node' => 3.11111111111,    // Total $84.00 / 27
        'rebirth_cost_per_node' => 3.70370370370, // Total $100.00 / 27
    ],
    4 => [
        'capacity' => 81,
        'gross_per_node' => 30.00,            // $30.00 ($2,430.00 total)
        'helping_per_node' => 0.74074074074,  // Total $60.00 / 81 deducted from Customer Share
        'net_pool_per_node' => 30.00,          // Total $2,430.00 / 81
        'user_wallet_per_node' => 11.79012345679, // Total $955.00 / 81 ($1,215 50% share - $60 helping fund - $200 20 rebirths)
        'burfee_cart_per_node' => 9.00,        // Total $729.00 / 81 (60% of $1,215 Company share)
        'charity_per_node' => 6.00,            // Total $486.00 / 81 (40% of $1,215 Company share)
        'rebirth_cost_per_node' => 2.46913580247, // Total $200.00 / 81
    ],
    5 => [
        'capacity' => 243,
        'gross_per_node' => 40.00,            // $40.00
        'helping_per_node' => 0.00,
        'net_pool_per_node' => 40.00,
        'user_wallet_per_node' => 17.11934156379, // Total $4160.00 / 243
        'burfee_cart_per_node' => 12.00,       // Total $2916.00 / 243
        'charity_per_node' => 8.00,            // Total $1944.00 / 243
        'rebirth_cost_per_node' => 2.88065843621, // Total $700.00 / 243
    ],
    6 => [
        'capacity' => 729,
        'gross_per_node' => 50.00,            // $50.00
        'helping_per_node' => 0.00,
        'net_pool_per_node' => 50.00,
        'user_wallet_per_node' => 23.62825788752, // Total $17225.00 / 729
        'burfee_cart_per_node' => 15.00,       // Total $10935.00 / 729
        'charity_per_node' => 10.00,           // Total $7290.00 / 729
        'rebirth_cost_per_node' => 1.37174211248, // Total $1000.00 / 729
    ],
    7 => [
        'capacity' => 2187,                    // 729 * 3
        'gross_per_node' => 60.00,            // $60.00 ($131,220 total gross)
        'helping_per_node' => 0.00,
        'net_pool_per_node' => 60.00,
        'user_wallet_per_node' => 0.00,        // Customer 50% ($30.00 per node) used entirely for rebirths ($0.00 net withdrawable cash)
        'burfee_cart_per_node' => 18.00,       // Burfee Cart 30% of gross ($18.00 per node = $39,366 total)
        'charity_per_node' => 12.00,           // Charity 20% of gross ($12.00 per node = $26,244 total)
        'rebirth_cost_per_node' => 30.00,      // 50% Customer Wallet portion ($30.00 per node = $65,610 total)
    ]
];

/**
 * Generate random unique non-sequential Member ID (e.g., RGI784920)
 * Prevents members from inferring tree placement or join sequence from sequential IDs
 */
function generateMemberID($pdo) {
    do {
        // Generate random 6-digit number between 100001 and 999999
        $randomNum = random_int(100001, 999999);
        $candidateId = 'RGI' . $randomNum;

        // Ensure uniqueness and avoid colliding with Root RGI100000 or existing members
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE member_id = ?");
        $stmt->execute([$candidateId]);
        $exists = ($stmt->fetchColumn() > 0);
    } while ($exists);

    return $candidateId;
}

/**
 * Generate unique ePIN code
 */
function generateEpinCode($prefix = 'RGI') {
    return $prefix . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Global BFS Auto-Spillover Matrix Placement Algorithm
 * Returns ['placement_parent_id' => string, 'matrix_position' => int]
 */
function findBFSMatrixPlacement($pdo, $startMemberId = 'RGI100000') {
    // Verify start member exists
    $stmt = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ? AND status = 'Active'");
    $stmt->execute([$startMemberId]);
    if (!$stmt->fetch()) {
        $startMemberId = 'RGI100000';
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
        'placement_parent_id' => 'RGI100000',
        'matrix_position' => 1
    ];
}

/**
 * Process 10% Direct Referral Commission & Matrix Level Commissions for up to 7 Levels above $newMemberId
 * Applies 50:50 Smart Wallet division (50% Customer Wallet, 50% Company -> 60% Burfee Cart / 40% Charity).
 */
function distributeMatrixCommissions($pdo, $newMemberId) {
    // 1. Credit 10% Direct Referrer Income ($1.00 USD) to sponsor_id
    $stmtSp = $pdo->prepare("SELECT sponsor_id FROM members WHERE member_id = ?");
    $stmtSp->execute([$newMemberId]);
    $sponsorId = $stmtSp->fetchColumn();

    if ($sponsorId) {
        $refAmount = DIRECT_REFERRAL_AMOUNT;
        $userRef = round($refAmount * 0.50, 2);
        $burfeeRef = round($refAmount * 0.30, 2);
        $charityRef = round($refAmount * 0.20, 2);
        $companyRef = round($refAmount * 0.50, 2);

        // Ensure sponsor wallet exists
        $stmtW = $pdo->prepare("SELECT member_id FROM wallets WHERE member_id = ?");
        $stmtW->execute([$sponsorId]);
        if (!$stmtW->fetch()) {
            $pdo->prepare("INSERT INTO wallets (member_id, balance, user_wallet_50, burfee_cart_wallet, charity_wallet, user_wallet_60, company_wallet_40) VALUES (?, 0, 0, 0, 0, 0, 0)")->execute([$sponsorId]);
        }

        // Update sponsor wallet
        $upW = $pdo->prepare("
            UPDATE wallets
            SET balance = balance + ?,
                user_wallet_50 = user_wallet_50 + ?,
                burfee_cart_wallet = burfee_cart_wallet + ?,
                charity_wallet = charity_wallet + ?,
                user_wallet_60 = user_wallet_60 + ?,
                company_wallet_40 = company_wallet_40 + ?
            WHERE member_id = ?
        ");
        $upW->execute([$refAmount, $userRef, $burfeeRef, $charityRef, $userRef, $companyRef, $sponsorId]);

        // Log Referral Transaction
        $stmtTx = $pdo->prepare("
            INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
            VALUES (?, 'Direct_Referral', ?, 'Main', 'Credit', ?)
        ");
        $descRef = "10% Direct Referrer Commission from new member {$newMemberId}. (50% Customer: \${$userRef}, Company 50%: \${$burfeeRef} Rayon Cart / \${$charityRef} Charity)";
        $stmtTx->execute([$sponsorId, $refAmount, $descRef]);

        // Record 10% Referrer Income Deduction Entry on the new member audit log
        $stmtTxDed = $pdo->prepare("
            INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
            VALUES (?, 'Admin_Adjustment', ?, 'Main', 'Debit', ?)
        ");
        $descDed = "10% Direct Referrer Income Deduction (-\${$refAmount}) set aside from $10.00 joining package for sponsor {$sponsorId}.";
        $stmtTxDed->execute([$newMemberId, $refAmount, $descDed]);
    }

    // 2. Distribute Matrix Level Commissions & Helping Funds for Levels 1 through 7 up the upline tree
    $stmt = $pdo->prepare("SELECT placement_parent_id FROM members WHERE member_id = ?");
    $stmt->execute([$newMemberId]);
    $placementParentId = $stmt->fetchColumn();

    $currentUplineId = $placementParentId;
    for ($distLevel = 1; $distLevel <= 7; $distLevel++) {
        if (!$currentUplineId) {
            break;
        }

        if (isset(MATRIX_LEVEL_SPECS[$distLevel])) {
            $spec = MATRIX_LEVEL_SPECS[$distLevel];
            $userAmount = round($spec['user_wallet_per_node'], 4);
            $burfeeAmount = round($spec['burfee_cart_per_node'], 4);
            $charityAmount = round($spec['charity_per_node'], 4);
            $grossAmount = round($spec['gross_per_node'], 2);

            // Ensure upline wallet row exists
            $stmtWallet = $pdo->prepare("SELECT member_id FROM wallets WHERE member_id = ?");
            $stmtWallet->execute([$currentUplineId]);
            if (!$stmtWallet->fetch()) {
                $insW = $pdo->prepare("INSERT INTO wallets (member_id, balance, user_wallet_50, burfee_cart_wallet, charity_wallet, user_wallet_60, company_wallet_40) VALUES (?, 0, 0, 0, 0, 0, 0)");
                $insW->execute([$currentUplineId]);
            }

            // Update upline wallet with Level $distLevel Matrix Income
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
            $updateWallet->execute([$grossAmount, $userAmount, $burfeeAmount, $charityAmount, $userAmount, $burfeeAmount + $charityAmount, $currentUplineId]);

            // Log Transaction
            $logTx = $pdo->prepare("
                INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
                VALUES (?, 'Matrix_Income_L" . $distLevel . "', ?, 'Main', 'Credit', ?)
            ");
            $desc = "Level {$distLevel} Matrix Commission from downline member {$newMemberId}. (Gross: \${$grossAmount}, Net User Wallet: \${$userAmount}, Rayon Cart: \${$burfeeAmount}, Charity: \${$charityAmount})";
            $logTx->execute([$currentUplineId, $grossAmount, $desc]);

            // Credit Helping Fund portion if applicable
            $helpingPerNode = round($spec['helping_per_node'], 4);
            if ($helpingPerNode > 0) {
                $stmtAncest = $pdo->prepare("SELECT placement_parent_id FROM members WHERE member_id = ?");
                $stmtAncest->execute([$currentUplineId]);
                $ancestorParent = $stmtAncest->fetchColumn();
                $targetAncestorId = $ancestorParent ? $ancestorParent : 'RGI100000';

                // Ensure ancestor wallet exists
                $stmtWAnc = $pdo->prepare("SELECT member_id FROM wallets WHERE member_id = ?");
                $stmtWAnc->execute([$targetAncestorId]);
                if (!$stmtWAnc->fetch()) {
                    $pdo->prepare("INSERT INTO wallets (member_id, balance, user_wallet_50, burfee_cart_wallet, charity_wallet, user_wallet_60, company_wallet_40) VALUES (?, 0, 0, 0, 0, 0, 0)")->execute([$targetAncestorId]);
                }

                $ancUser = round($helpingPerNode * 0.50, 4);
                $ancBurf = round($helpingPerNode * 0.30, 4);
                $ancChar = round($helpingPerNode * 0.20, 4);

                $upAncW = $pdo->prepare("
                    UPDATE wallets
                    SET balance = balance + ?,
                        user_wallet_50 = user_wallet_50 + ?,
                        burfee_cart_wallet = burfee_cart_wallet + ?,
                        charity_wallet = charity_wallet + ?,
                        user_wallet_60 = user_wallet_60 + ?,
                        company_wallet_40 = company_wallet_40 + ?
                    WHERE member_id = ?
                ");
                $upAncW->execute([$helpingPerNode, $ancUser, $ancBurf, $ancChar, $ancUser, $ancBurf + $ancChar, $targetAncestorId]);

                $logHelpTx = $pdo->prepare("
                    INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
                    VALUES (?, 'Admin_Adjustment', ?, 'Main', 'Credit', ?)
                ");
                $descHelp = "Level {$distLevel} Helping Fund Received from downline member {$newMemberId} via {$currentUplineId}. (\${$helpingPerNode})";
                $logHelpTx->execute([$targetAncestorId, $helpingPerNode, $descHelp]);
            }
        }

        // Fetch next upline placement parent
        $stmtNext = $pdo->prepare("SELECT placement_parent_id FROM members WHERE member_id = ?");
        $stmtNext->execute([$currentUplineId]);
        $currentUplineId = $stmtNext->fetchColumn();
    }

    // 3. Check level completion rebirth triggers across ancestors up the tree (7 Levels)
    $currentParentId = $placementParentId;
    $level = 1;
    while ($currentParentId && $level <= 7) {
        checkAndGrantLevelRebirths($pdo, $currentParentId, $level);
        $stmtParent = $pdo->prepare("SELECT placement_parent_id FROM members WHERE member_id = ?");
        $stmtParent->execute([$currentParentId]);
        $currentParentId = $stmtParent->fetchColumn();
        $level++;
    }
}

/**
 * Verification mechanism to check if a member has completed Level 3 (27 downline nodes at depth 3)
 */
function hasMemberCompletedLevel3($pdo, $memberId) {
    $currentLevelMembers = [$memberId];
    for ($l = 1; $l <= 3; $l++) {
        if (empty($currentLevelMembers)) return false;
        $inClause = implode(',', array_fill(0, count($currentLevelMembers), '?'));
        $stmt = $pdo->prepare("SELECT member_id FROM members WHERE placement_parent_id IN ($inClause)");
        $stmt->execute($currentLevelMembers);
        $currentLevelMembers = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    return count($currentLevelMembers) >= MATRIX_LEVEL_CAPACITY[3];
}

/**
 * Check level completion and grant automated Rebirths:
 * Level 3 Completion => 10 Rebirths
 * Level 4 Completion => 20 Rebirths
 * Level 5 Completion => 70 Rebirths
 * Level 6 Completion => 100 Rebirths
 * Level 7 Completion => 6,561 Rebirths (50% of gross amount used for rebirths)
 */
function checkAndGrantLevelRebirths($pdo, $memberId, $level) {
    if (!isset(REBIRTH_REWARDS[$level])) {
        return;
    }

    // STRICT CHECK: Ensure level is at least Level 3 and member has achieved Level 3 completion
    if ($level < 3 || !hasMemberCompletedLevel3($pdo, $memberId)) {
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

            // Schedule/Queue Rebirth Positions with time gap intervals
            scheduleRebirthPositions($pdo, $memberId, $rebirthCount, $level);
        }
    }
}

/**
 * Schedule rebirth positions in the queue table with configurable time gaps (drip placement)
 */
function scheduleRebirthPositions($pdo, $parentMemberId, $count, $completedLevel) {
    $intervalSecs = REBIRTH_INTERVAL_MINUTES * 60;
    $now = time();

    for ($i = 1; $i <= $count; $i++) {
        $scheduledTimestamp = $now + (($i - 1) * $intervalSecs);
        $scheduledAt = date('Y-m-d H:i:s', $scheduledTimestamp);

        $stmtQ = $pdo->prepare("
            INSERT INTO queued_rebirths (member_id, completed_level, rebirth_index, scheduled_at, status)
            VALUES (?, ?, ?, ?, 'Pending')
        ");
        $stmtQ->execute([$parentMemberId, $completedLevel, $i, $scheduledAt]);
    }

    // Process immediately due rebirths in queue
    processScheduledRebirthQueue($pdo);
}

/**
 * Process due rebirth positions from the queue with round-robin member interleaving
 */
function processScheduledRebirthQueue($pdo) {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM queued_rebirths WHERE status = 'Pending' AND scheduled_at <= ? ORDER BY scheduled_at ASC, id ASC");
    $stmt->execute([$now]);
    $dueRebirths = $stmt->fetchAll();

    if (empty($dueRebirths)) {
        return;
    }

    // Group due rebirths by member_id
    $byMember = [];
    foreach ($dueRebirths as $q) {
        $mId = $q['member_id'];
        if (!isset($byMember[$mId])) {
            $byMember[$mId] = [];
        }
        $byMember[$mId][] = $q;
    }

    // Interleave rebirths across members in round-robin sequence
    $interleavedQueue = [];
    $hasMore = true;
    while ($hasMore) {
        $hasMore = false;
        foreach ($byMember as $mId => &$queueList) {
            if (!empty($queueList)) {
                $interleavedQueue[] = array_shift($queueList);
                if (!empty($queueList)) {
                    $hasMore = true;
                }
            }
        }
    }

    // Execute placement in round-robin interleaved order
    foreach ($interleavedQueue as $q) {
        $qId = $q['id'];
        $parentMemberId = $q['member_id'];
        $completedLevel = $q['completed_level'];
        $rebirthIndex = $q['rebirth_index'];

        // Mark as Processed
        $stmtUp = $pdo->prepare("UPDATE queued_rebirths SET status = 'Processed', processed_at = ? WHERE id = ? AND status = 'Pending'");
        $stmtUp->execute([date('Y-m-d H:i:s'), $qId]);

        if ($stmtUp->rowCount() > 0) {
            executeSingleRebirthPlacement($pdo, $parentMemberId, $rebirthIndex, $completedLevel);
        }
    }
}

/**
 * Execute placement of a single rebirth node into the 3-matrix tree
 */
function executeSingleRebirthPlacement($pdo, $parentMemberId, $rebirthIndex, $completedLevel) {
    $stmtM = $pdo->prepare("SELECT name, email, phone, package_type, used_epin, sponsor_id FROM members WHERE member_id = ?");
    $stmtM->execute([$parentMemberId]);
    $parent = $stmtM->fetch();

    if (!$parent) return;

    $isRebirthNode = (strpos($parent['used_epin'], 'REBIRTH_') === 0 || strpos($parent['name'], 'Rebirth') !== false);
    $sponsorId = $isRebirthNode ? 'RGI100000' : $parentMemberId;
    $cleanBaseName = trim(preg_replace('/\s*\(Rebirth\s*#.*$/i', '', $parent['name']));

    $placement = findBFSMatrixPlacement($pdo, 'RGI100000');
    $placementParentId = $placement['placement_parent_id'];
    $matrixPos = $placement['matrix_position'];

    $rebirthMemberId = generateMemberID($pdo);
    $rebirthName = $cleanBaseName . " (Rebirth #{$rebirthIndex} - L{$completedLevel})";
    $dummyEpin = "REBIRTH_L" . $completedLevel . "_" . bin2hex(random_bytes(3));
    $passHash = password_hash('REBIRTH_NODE', PASSWORD_BCRYPT);

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

    $stmtW = $pdo->prepare("INSERT INTO wallets (member_id, balance, user_wallet_50, burfee_cart_wallet, charity_wallet, user_wallet_60, company_wallet_40) VALUES (?, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00)");
    $stmtW->execute([$rebirthMemberId]);

    $stmtTx = $pdo->prepare("
        INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
        VALUES (?, 'Admin_Adjustment', 0.00, 'Main', 'Credit', ?)
    ");
    $desc = "Rebirth position {$rebirthMemberId} placed automatically upon Level {$completedLevel} schedule.";
    $stmtTx->execute([$parentMemberId, $desc]);

    distributeMatrixCommissions($pdo, $rebirthMemberId);

    // Send Rebirth Notification Email
    $rebirthSubject = "New Rebirth Position Placed - Level " . $completedLevel . " (" . $rebirthMemberId . ")";
    $rebirthTpl = "
        <h2 style='color: #c5a059; margin-top:0;'>Rebirth Position Placed!</h2>
        <p>Congratulations, {{name}}! Your scheduled rebirth position #{{rebirth_index}} for Level {{completed_level}} has been placed into the 3-matrix tree.</p>
        <div style='background: rgba(197, 160, 89, 0.1); border: 1px solid rgba(197, 160, 89, 0.3); padding: 15px; border-radius: 10px; margin: 20px 0;'>
            <p style='margin: 5px 0;'><strong>Rebirth Position ID:</strong> <span style='color: #c5a059;'>{{rebirth_member_id}}</span></p>
            <p style='margin: 5px 0;'><strong>Primary Account:</strong> {{member_id}}</p>
            <p style='margin: 5px 0;'><strong>Placement Parent:</strong> {{placement_parent_id}} (Position {{matrix_position}})</p>
            <p style='margin: 5px 0;'><strong>Rebirth Node Name:</strong> {{rebirth_name}}</p>
        </div>
        <p>All earnings generated by this rebirth node accumulate directly into your master account.</p>
        <a href='{{site_url}}/customer/rebirths.php' class='btn'>View Active Rebirth Nodes</a>
    ";
    $rebirthEmailHtml = renderEmailTemplate($rebirthTpl, [
        'name' => $cleanBaseName,
        'rebirth_index' => $rebirthIndex,
        'completed_level' => $completedLevel,
        'rebirth_member_id' => $rebirthMemberId,
        'member_id' => $parentMemberId,
        'placement_parent_id' => $placementParentId,
        'matrix_position' => $matrixPos,
        'rebirth_name' => $rebirthName
    ]);
    sendEmpressHtmlEmail($parent['email'], $rebirthSubject, $rebirthEmailHtml);
}

/**
 * Backward compatibility wrapper function
 */
function createRebirthPositions($pdo, $parentMemberId, $count, $completedLevel) {
    scheduleRebirthPositions($pdo, $parentMemberId, $count, $completedLevel);
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
    $sponsorId = !empty($data['sponsor_id']) ? trim($data['sponsor_id']) : 'RGI100000';
    $name = trim($data['name']);
    $email = trim($data['email']);
    $phone = trim($data['phone']);
    $password = password_hash($data['password'], PASSWORD_BCRYPT);
    $epinCode = trim($data['epin_code']);

    // Check sponsor exists
    $stmtSponsor = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ? AND status = 'Active'");
    $stmtSponsor->execute([$sponsorId]);
    if (!$stmtSponsor->fetch()) {
        $sponsorId = 'RGI100000';
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
    $placement = findBFSMatrixPlacement($pdo, 'RGI100000');
    $placementParentId = $placement['placement_parent_id'];
    $matrixPos = $placement['matrix_position'];

    $memberId = generateMemberID($pdo);

    $pdo->beginTransaction();

    try {
        // Insert Member
        $stmtIns = $pdo->prepare("
            INSERT INTO members (member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, password, used_epin, package_type, status, kyc_status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 'Approved')
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

        // Trigger Welcome HTML Email
        $welcomeSubject = "Welcome to Reyon Global Impact - Position Activated (" . $memberId . ")";
        $welcomeTpl = "
            <h2 style='color: #c5a059; margin-top:0;'>Congratulations, {{name}}!</h2>
            <p>Your 3-Matrix position has been successfully activated in <strong>Reyon Global Impact</strong>.</p>
            <div style='background: rgba(197, 160, 89, 0.1); border: 1px solid rgba(197, 160, 89, 0.3); padding: 15px; border-radius: 10px; margin: 20px 0;'>
                <p style='margin: 5px 0;'><strong>Member ID:</strong> <span style='color: #c5a059;'>{{member_id}}</span></p>
                <p style='margin: 5px 0;'><strong>Email:</strong> {{email}}</p>
                <p style='margin: 5px 0;'><strong>Sponsor ID:</strong> {{sponsor_id}}</p>
                <p style='margin: 5px 0;'><strong>Placement Parent:</strong> {{placement_parent_id}} (Position {{matrix_position}})</p>
                <p style='margin: 5px 0;'><strong>Package:</strong> {{package_type}}</p>
            </div>
            <p>Access your member portal to view your 3-matrix downline tree and smart wallet balance:</p>
            <a href='{{site_url}}/login.php' class='btn'>Login to Member Portal</a>
        ";
        $emailHtml = renderEmailTemplate($welcomeTpl, [
            'name' => $name,
            'member_id' => $memberId,
            'email' => $email,
            'sponsor_id' => $sponsorId,
            'placement_parent_id' => $placementParentId,
            'matrix_position' => $matrixPos,
            'package_type' => $packageType
        ]);
        sendEmpressHtmlEmail($email, $welcomeSubject, $emailHtml);

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
 * Register a new member WITHOUT requiring an ePIN code
 */
function registerMemberWithoutEpin($pdo, $data) {
    $sponsorId = !empty($data['sponsor_id']) ? trim($data['sponsor_id']) : 'RGI100000';
    $name = !empty($data['name']) ? trim($data['name']) : 'Burfee Cart';
    $email = !empty($data['email']) ? trim($data['email']) : ('member_' . time() . '_' . rand(100,999) . '@reyonglobal.com');
    $phone = !empty($data['phone']) ? trim($data['phone']) : ('9' . str_pad(rand(0, 999999999), 9, '0', STR_PAD_LEFT));
    $rawPassword = !empty($data['password']) ? $data['password'] : '123456';
    $password = password_hash($rawPassword, PASSWORD_BCRYPT);
    $packageType = !empty($data['package_type']) ? trim($data['package_type']) : 'Royal_Starter';
    $epinCode = !empty($data['used_epin']) ? trim($data['used_epin']) : ('ADMIN_SQL_IMPORT_' . strtoupper(bin2hex(random_bytes(3))));

    // Check sponsor exists
    $stmtSponsor = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ? AND status = 'Active'");
    $stmtSponsor->execute([$sponsorId]);
    if (!$stmtSponsor->fetch()) {
        $sponsorId = 'RGI100000';
    }

    // Find BFS Matrix Placement
    $placement = findBFSMatrixPlacement($pdo, 'RGI100000');
    $placementParentId = $placement['placement_parent_id'];
    $matrixPos = $placement['matrix_position'];

    $memberId = generateMemberID($pdo);

    $pdo->beginTransaction();

    $addressLine = !empty($data['address_line']) ? trim($data['address_line']) : null;
    $city = !empty($data['city']) ? trim($data['city']) : null;
    $state = !empty($data['state']) ? trim($data['state']) : null;
    $pincode = !empty($data['pincode']) ? trim($data['pincode']) : null;
    $panNumber = !empty($data['pan_number']) ? trim($data['pan_number']) : null;
    $bep20Address = !empty($data['bep20_address']) ? trim($data['bep20_address']) : null;

    try {
        // Insert Member
        $stmtIns = $pdo->prepare("
            INSERT INTO members (member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, password, used_epin, package_type, status, kyc_status, address_line, city, state, pincode, pan_number, bep20_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', 'Approved', ?, ?, ?, ?, ?, ?)
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
            $packageType,
            $addressLine,
            $city,
            $state,
            $pincode,
            $panNumber,
            $bep20Address
        ]);

        // Initialize Wallet
        $stmtWallet = $pdo->prepare("INSERT INTO wallets (member_id, balance, user_wallet_50, burfee_cart_wallet, charity_wallet, user_wallet_60, company_wallet_40) VALUES (?, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00)");
        $stmtWallet->execute([$memberId]);

        // Distribute Matrix Commissions across ancestors
        distributeMatrixCommissions($pdo, $memberId);

        $pdo->commit();

        // Trigger Welcome HTML Email
        $welcomeSubject = "Welcome to Reyon Global Impact - Position Activated (" . $memberId . ")";
        $welcomeTpl = "
            <h2 style='color: #c5a059; margin-top:0;'>Congratulations, {{name}}!</h2>
            <p>Your 3-Matrix position has been successfully activated in <strong>Reyon Global Impact</strong>.</p>
            <div style='background: rgba(197, 160, 89, 0.1); border: 1px solid rgba(197, 160, 89, 0.3); padding: 15px; border-radius: 10px; margin: 20px 0;'>
                <p style='margin: 5px 0;'><strong>Member ID:</strong> <span style='color: #c5a059;'>{{member_id}}</span></p>
                <p style='margin: 5px 0;'><strong>Email:</strong> {{email}}</p>
                <p style='margin: 5px 0;'><strong>Sponsor ID:</strong> {{sponsor_id}}</p>
                <p style='margin: 5px 0;'><strong>Placement Parent:</strong> {{placement_parent_id}} (Position {{matrix_position}})</p>
                <p style='margin: 5px 0;'><strong>Package:</strong> {{package_type}}</p>
            </div>
            <p>Access your member portal to view your 3-matrix downline tree and smart wallet balance:</p>
            <a href='{{site_url}}/login.php' class='btn'>Login to Member Portal</a>
        ";
        $emailHtml = renderEmailTemplate($welcomeTpl, [
            'name' => $name,
            'member_id' => $memberId,
            'email' => $email,
            'sponsor_id' => $sponsorId,
            'placement_parent_id' => $placementParentId,
            'matrix_position' => $matrixPos,
            'package_type' => $packageType
        ]);
        sendEmpressHtmlEmail($email, $welcomeSubject, $emailHtml);

        return [
            'success' => true,
            'member_id' => $memberId,
            'placement_parent_id' => $placementParentId,
            'matrix_position' => $matrixPos,
            'name' => $name,
            'email' => $email,
            'sponsor_id' => $sponsorId,
            'message' => "Member {$memberId} successfully registered and placed under {$placementParentId} (Position {$matrixPos})."
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
 * Get Downline List for a Member up to 7 Levels
 */
function getMemberDownline6Levels($pdo, $memberId) {
    return getMemberDownline7Levels($pdo, $memberId);
}

function getMemberDownline7Levels($pdo, $memberId) {
    $downline = [];
    $currentLevelMembers = [$memberId];

    for ($level = 1; $level <= 7; $level++) {
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
 * Update P2P Wallet Settings
 */
function updateP2PWalletSettings($pdo, $memberId, $bep20Address, $fileArr = null) {
    $bep20Address = trim($bep20Address);

    if (!empty($bep20Address) && !preg_match('/^0x[a-fA-F0-9]{40}$/', $bep20Address)) {
        return [
            'success' => false,
            'message' => 'Invalid BEP-20 Wallet Address. Address must be a valid 42-character hex string starting with "0x".'
        ];
    }

    $qrCodeUrl = null;

    if ($fileArr && isset($fileArr['tmp_name']) && is_uploaded_file($fileArr['tmp_name'])) {
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

        $fileName = $fileArr['name'];
        $fileTmp = $fileArr['tmp_name'];
        $fileSize = $fileArr['size'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);

        if (!in_array($ext, $allowedExts) || !in_array($mimeType, $allowedMimes)) {
            return [
                'success' => false,
                'message' => 'Invalid file format. Only JPG, JPEG, PNG, and WEBP image files are allowed.'
            ];
        }

        if ($fileSize > 5 * 1024 * 1024) {
            return [
                'success' => false,
                'message' => 'File size exceeds 5MB maximum limit.'
            ];
        }

        $uploadDir = __DIR__ . '/../uploads/qrs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newFileName = 'qr_' . preg_replace('/[^a-zA-Z0-9]/', '', $memberId) . '_' . time() . '.' . $ext;
        $targetFile = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmp, $targetFile)) {
            $qrCodeUrl = '/uploads/qrs/' . $newFileName;
        } else {
            return [
                'success' => false,
                'message' => 'Failed to save QR code image file to server.'
            ];
        }
    }

    try {
        if ($qrCodeUrl !== null) {
            $stmt = $pdo->prepare("UPDATE members SET bep20_address = ?, qr_code_url = ? WHERE member_id = ?");
            $stmt->execute([$bep20Address, $qrCodeUrl, $memberId]);
        } else {
            $stmt = $pdo->prepare("UPDATE members SET bep20_address = ? WHERE member_id = ?");
            $stmt->execute([$bep20Address, $memberId]);
        }

        return [
            'success' => true,
            'message' => 'P2P Wallet Settings updated successfully!',
            'qr_code_url' => $qrCodeUrl
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Database update failed: ' . $e->getMessage()
        ];
    }
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
 * Re-allocate Direct Referral Commission
 */
function reallocateDirectReferralCommission($pdo, $memberId, $oldSponsorId, $newSponsorId) {
    $oldSponsorId = strtoupper(trim($oldSponsorId));
    $newSponsorId = strtoupper(trim($newSponsorId));

    if (empty($oldSponsorId) || empty($newSponsorId) || $oldSponsorId === $newSponsorId) {
        return ['success' => true, 'message' => 'No sponsor change required.'];
    }

    $refAmount = DIRECT_REFERRAL_AMOUNT;
    $userRef = round($refAmount * 0.50, 2);
    $burfeeRef = round($refAmount * 0.30, 2);
    $charityRef = round($refAmount * 0.20, 2);
    $companyRef = round($refAmount * 0.50, 2);

    $pdo->beginTransaction();
    try {
        $stmtWOld = $pdo->prepare("SELECT member_id FROM wallets WHERE member_id = ?");
        $stmtWOld->execute([$oldSponsorId]);
        if ($stmtWOld->fetch()) {
            $stmtDeb = $pdo->prepare("
                UPDATE wallets
                SET balance = balance - ?,
                    user_wallet_50 = user_wallet_50 - ?,
                    burfee_cart_wallet = burfee_cart_wallet - ?,
                    charity_wallet = charity_wallet - ?,
                    user_wallet_60 = user_wallet_60 - ?,
                    company_wallet_40 = company_wallet_40 - ?
                WHERE member_id = ?
            ");
            $stmtDeb->execute([$refAmount, $userRef, $burfeeRef, $charityRef, $userRef, $companyRef, $oldSponsorId]);

            $stmtTx1 = $pdo->prepare("
                INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
                VALUES (?, 'Admin_Adjustment', ?, 'Main', 'Debit', ?)
            ");
            $descDeb = "Direct Referrer Income (\${$refAmount}) transferred out to new sponsor {$newSponsorId} for member {$memberId}.";
            $stmtTx1->execute([$oldSponsorId, $refAmount, $descDeb]);
        }

        $stmtWNew = $pdo->prepare("SELECT member_id FROM wallets WHERE member_id = ?");
        $stmtWNew->execute([$newSponsorId]);
        if (!$stmtWNew->fetch()) {
            $pdo->prepare("INSERT INTO wallets (member_id, balance, user_wallet_50, burfee_cart_wallet, charity_wallet, user_wallet_60, company_wallet_40) VALUES (?, 0, 0, 0, 0, 0, 0)")->execute([$newSponsorId]);
        }

        $stmtCred = $pdo->prepare("
            UPDATE wallets
            SET balance = balance + ?,
                user_wallet_50 = user_wallet_50 + ?,
                burfee_cart_wallet = burfee_cart_wallet + ?,
                charity_wallet = charity_wallet + ?,
                user_wallet_60 = user_wallet_60 + ?,
                company_wallet_40 = company_wallet_40 + ?
            WHERE member_id = ?
        ");
        $stmtCred->execute([$refAmount, $userRef, $burfeeRef, $charityRef, $userRef, $companyRef, $newSponsorId]);

        $stmtTx2 = $pdo->prepare("
            INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
            VALUES (?, 'Direct_Referral', ?, 'Main', 'Credit', ?)
        ");
        $descCred = "10% Direct Referrer Income (\${$refAmount}) re-allocated from old sponsor {$oldSponsorId} for member {$memberId}.";
        $stmtTx2->execute([$newSponsorId, $refAmount, $descCred]);

        $pdo->commit();
        return [
            'success' => true,
            'message' => "Successfully transferred \${$refAmount} Direct Referrer Income from {$oldSponsorId} to new sponsor {$newSponsorId}."
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Failed to re-allocate referrer income: ' . $e->getMessage()];
    }
}

/**
 * Safe Member Deletion: Cascades deletion of rebirth nodes, queues, rebirth rewards, wallets,
 * transactions, withdrawals, deposits, and re-parents matrix children to Root RGI100000.
 */
function deleteMemberSafely($pdo, $memberIdToDelete) {
    if ($memberIdToDelete === 'RGI100000') {
        return ['success' => false, 'message' => 'Cannot delete system root member RGI100000.'];
    }

    $pdo->beginTransaction();
    try {
        // Collect all associated rebirth member nodes (where sponsor_id = $memberIdToDelete and name LIKE '%Rebirth%')
        $stmtRebirthNodes = $pdo->prepare("SELECT member_id FROM members WHERE sponsor_id = ? AND (used_epin LIKE 'REBIRTH_%' OR name LIKE '%Rebirth%')");
        $stmtRebirthNodes->execute([$memberIdToDelete]);
        $rebirthNodeIds = $stmtRebirthNodes->fetchAll(PDO::FETCH_COLUMN);

        $allMembersToDelete = array_merge([$memberIdToDelete], $rebirthNodeIds);

        foreach ($allMembersToDelete as $targetId) {
            // Re-parent direct children of targetId to Root RGI100000
            $stmtChildren = $pdo->prepare("SELECT member_id FROM members WHERE placement_parent_id = ?");
            $stmtChildren->execute([$targetId]);
            $children = $stmtChildren->fetchAll(PDO::FETCH_COLUMN);

            foreach ($children as $childId) {
                if (in_array($childId, $allMembersToDelete)) continue; // Skip if child is also being deleted
                $placement = findBFSMatrixPlacement($pdo, 'RGI100000');
                $stmtReparent = $pdo->prepare("UPDATE members SET placement_parent_id = ?, matrix_position = ? WHERE member_id = ?");
                $stmtReparent->execute([$placement['placement_parent_id'], $placement['matrix_position'], $childId]);
            }

            // Delete linked records for targetId
            $pdo->prepare("DELETE FROM wallets WHERE member_id = ?")->execute([$targetId]);
            $pdo->prepare("DELETE FROM transactions WHERE member_id = ?")->execute([$targetId]);
            $pdo->prepare("DELETE FROM withdrawals WHERE member_id = ?")->execute([$targetId]);
            $pdo->prepare("DELETE FROM deposits WHERE user_id = ?")->execute([$targetId]);
            $pdo->prepare("DELETE FROM api_tokens WHERE user_id = ?")->execute([$targetId]);
            $pdo->prepare("DELETE FROM member_rebirths WHERE member_id = ?")->execute([$targetId]);
            $pdo->prepare("DELETE FROM queued_rebirths WHERE member_id = ?")->execute([$targetId]);
            $pdo->prepare("UPDATE epins SET used_by_member_id = NULL, status = 'Unused' WHERE used_by_member_id = ?")->execute([$targetId]);
            $pdo->prepare("DELETE FROM members WHERE member_id = ?")->execute([$targetId]);
        }

        $pdo->commit();
        return ['success' => true, 'message' => "Member {$memberIdToDelete} and all associated rebirth positions/data deleted cleanly."];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()];
    }
}

/**
 * Clean & Format Member Name
 */
function formatMemberName($name) {
    if (preg_match('/^(.*?)\s*\((Rebirth\s*#[^)]+)\)$/i', trim($name), $matches)) {
        return [
            'clean_name' => trim($matches[1]),
            'is_rebirth' => true,
            'rebirth_label' => trim($matches[2])
        ];
    }
    return [
        'clean_name' => trim($name),
        'is_rebirth' => false,
        'rebirth_label' => ''
    ];
}

/**
 * Verify USDT (BEP-20) Transaction on BSC Blockchain
 */
function verifyBscTransactionOnChain($txHash, $expectedReceiverWallet = '0x9811cCf1E9dcc6451357D9f983E6E9bA615920B5', $bscScanApiKey = '') {
    $txHash = trim($txHash);
    $expectedReceiverWallet = strtolower(trim($expectedReceiverWallet));
    $usdtContract = '0x55d398326f99059ff775485246999027b3197955';

    if (!preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
        return [
            'success' => false,
            'verified' => false,
            'message' => 'Invalid 66-character TxHash format.'
        ];
    }

    $apiUrl = "https://api.bscscan.com/api?module=account&action=tokentx&contractaddress=" . $usdtContract . "&address=" . $expectedReceiverWallet . "&sort=desc";
    if (!empty($bscScanApiKey)) {
        $apiUrl .= "&apikey=" . urlencode($bscScanApiKey);
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 8,
        CURLOPT_HTTPHEADER => ['Accept: application/json']
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $json = json_decode($response, true);
        if (isset($json['status']) && $json['status'] == '1' && !empty($json['result'])) {
            foreach ($json['result'] as $tx) {
                if (strtolower($tx['hash']) === strtolower($txHash)) {
                    $toAddr = strtolower($tx['to']);
                    if ($toAddr === $expectedReceiverWallet) {
                        $rawAmount = (float)($tx['value'] ?? 0);
                        $decimals = (int)($tx['tokenDecimal'] ?? 18);
                        $usdtAmount = $rawAmount / pow(10, $decimals);

                        return [
                            'success' => true,
                            'verified' => true,
                            'amount' => round($usdtAmount, 2),
                            'sender' => $tx['from'] ?? '',
                            'receiver' => $tx['to'] ?? '',
                            'message' => 'Transaction verified successfully on BSC via BscScan API!'
                        ];
                    }
                }
            }
        }
    }

    $rpcPayload = json_encode([
        'jsonrpc' => '2.0',
        'method' => 'eth_getTransactionReceipt',
        'params' => [$txHash],
        'id' => 1
    ]);

    $chRpc = curl_init('https://bsc-dataseed.binance.org/');
    curl_setopt_array($chRpc, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $rpcPayload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 8
    ]);
    $rpcResponse = curl_exec($chRpc);
    curl_close($chRpc);

    if ($rpcResponse) {
        $rpcJson = json_decode($rpcResponse, true);
        if (isset($rpcJson['result']) && !empty($rpcJson['result'])) {
            $receipt = $rpcJson['result'];
            $status = $receipt['status'] ?? '0x0';

            if ($status === '0x1') {
                $logs = $receipt['logs'] ?? [];
                $transferTopic = '0xddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef';

                foreach ($logs as $log) {
                    $address = strtolower($log['address'] ?? '');
                    $topics = $log['topics'] ?? [];

                    if ($address === strtolower($usdtContract) && isset($topics[0]) && strtolower($topics[0]) === $transferTopic) {
                        $toHex = strtolower($topics[2] ?? '');
                        $recipientPadded = '0x' . str_pad(substr($expectedReceiverWallet, 2), 64, '0', STR_PAD_LEFT);

                        if ($toHex === strtolower($recipientPadded)) {
                            $dataHex = $log['data'] ?? '0x0';
                            $hexClean = ltrim(substr($dataHex, 2), '0');
                            $rawVal = !empty($hexClean) ? hexdec($hexClean) : 0;
                            $usdtAmount = $rawVal / 1e18;

                            return [
                                'success' => true,
                                'verified' => true,
                                'amount' => round($usdtAmount, 2),
                                'sender' => isset($topics[1]) ? '0x' . substr($topics[1], 26) : '',
                                'receiver' => $expectedReceiverWallet,
                                'message' => 'Transaction receipt verified on BSC Smart Contract!'
                            ];
                        }
                    }
                }
            }
        }
    }

    return [
        'success' => false,
        'verified' => false,
        'message' => 'Transaction Hash not found or not yet confirmed on BSC blockchain.'
    ];
}

/**
 * Process Automatic or Admin Deposit Approval on Database
 */
function approveDepositAndCreditWallet($pdo, $depositId, $adminNotes = 'Verified on BSC Blockchain') {
    $stmt = $pdo->prepare("SELECT * FROM deposits WHERE id = ?");
    $stmt->execute([$depositId]);
    $dep = $stmt->fetch();

    if (!$dep) {
        return ['success' => false, 'message' => 'Deposit record not found.'];
    }

    if ($dep['status'] === 'Approved') {
        return ['success' => false, 'message' => 'Deposit has already been approved and credited previously.'];
    }

    $userId = $dep['user_id'];
    $amount = (float)$dep['amount'];

    $pdo->beginTransaction();
    try {
        $stmtUp = $pdo->prepare("UPDATE deposits SET status = 'Approved' WHERE id = ?");
        $stmtUp->execute([$depositId]);

        $stmtW = $pdo->prepare("SELECT member_id FROM wallets WHERE member_id = ?");
        $stmtW->execute([$userId]);
        if (!$stmtW->fetch()) {
            $pdo->prepare("INSERT INTO wallets (member_id, balance, user_wallet_50, burfee_cart_wallet, charity_wallet, user_wallet_60, company_wallet_40) VALUES (?, 0, 0, 0, 0, 0, 0)")->execute([$userId]);
        }

        $stmtCredit = $pdo->prepare("
            UPDATE wallets
            SET balance = balance + ?,
                user_wallet_50 = user_wallet_50 + ?,
                user_wallet_60 = user_wallet_60 + ?
            WHERE member_id = ?
        ");
        $stmtCredit->execute([$amount, $amount, $amount, $userId]);

        $stmtTx = $pdo->prepare("
            INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
            VALUES (?, 'Admin_Adjustment', ?, 'User_Wallet', 'Credit', ?)
        ");
        $desc = "USDT (BEP-20) Deposit Credited. TxID: " . $dep['tx_hash'] . " (" . $adminNotes . ")";
        $stmtTx->execute([$userId, $amount, $desc]);

        $pdo->commit();

        return [
            'success' => true,
            'message' => "Deposit #{$depositId} (\${$amount} USDT) successfully verified and credited to member {$userId} wallet!"
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Failed to approve deposit: ' . $e->getMessage()];
    }
}

/**
 * Reset Database to Fresh Initial State
 */
function resetDatabaseToCleanState($pdo) {
    $pdo->beginTransaction();
    try {
        $pdo->exec("DELETE FROM members WHERE member_id != 'RGI100000'");
        $pdo->exec("DELETE FROM wallets WHERE member_id != 'RGI100000'");
        $pdo->exec("UPDATE wallets SET balance = 0.00, user_wallet_50 = 0.00, burfee_cart_wallet = 0.00, charity_wallet = 0.00, user_wallet_60 = 0.00, company_wallet_40 = 0.00 WHERE member_id = 'RGI100000'");
        $pdo->exec("DELETE FROM transactions");
        $pdo->exec("DELETE FROM withdrawals");
        $pdo->exec("DELETE FROM deposits");
        $pdo->exec("DELETE FROM member_rebirths");
        $pdo->exec("DELETE FROM epins WHERE epin_code != 'SYSTEM_ROOT_EPIN'");
        $pdo->exec("DELETE FROM api_tokens");

        $pdo->commit();
        return ['success' => true, 'message' => 'Database successfully reset to clean state!'];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Database reset failed: ' . $e->getMessage()];
    }
}
/**
 * Duplicate a user as a brand new independent member.
 * Unlike rebirth nodes, this creates a completely independent account with its own Member ID,
 * new BFS matrix spillover placement under RGI100000 (or specified sponsor), initialized wallet,
 * and full matrix commission distribution.
 *
 * @param PDO $pdo Database connection
 * @param string $sourceMemberId ID of member to clone
 * @param string|null $customSponsorId Optional custom sponsor ID (defaults to original sponsor or RGI100000)
 * @return array Result array with success status, new_member_id, and message
 */
function duplicateMemberAsIndependent($pdo, $sourceMemberId, $customSponsorId = null) {
    $stmtSrc = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmtSrc->execute([$sourceMemberId]);
    $src = $stmtSrc->fetch();

    if (!$src) {
        return ['success' => false, 'message' => 'Source member account not found.'];
    }

    // Determine sponsor ID
    $sponsorId = 'RGI100000';
    if (!empty($customSponsorId)) {
        $stmtSp = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ? AND status = 'Active'");
        $stmtSp->execute([$customSponsorId]);
        if ($stmtSp->fetch()) {
            $sponsorId = $customSponsorId;
        }
    } elseif (!empty($src['sponsor_id'])) {
        $stmtSp = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ? AND status = 'Active'");
        $stmtSp->execute([$src['sponsor_id']]);
        if ($stmtSp->fetch()) {
            $sponsorId = $src['sponsor_id'];
        }
    }

    // Generate clean base name without rebirth labels or duplicate tags
    $cleanName = trim(preg_replace('/\s*\((Rebirth|Duplicate)\s*#.*$/i', '', $src['name']));
    $duplicateName = $cleanName . " (Clone)";

    // Find BFS Matrix Placement starting from Root
    $placement = findBFSMatrixPlacement($pdo, 'RGI100000');
    $placementParentId = $placement['placement_parent_id'];
    $matrixPos = $placement['matrix_position'];

    $newMemberId = generateMemberID($pdo);
    $epinCode = "DUPLICATE_ACC_" . strtoupper(bin2hex(random_bytes(3)));
    $packageType = !empty($src['package_type']) ? $src['package_type'] : 'Royal_Starter';

    // Generate unique email variant to keep independent
    $emailParts = explode('@', $src['email']);
    $uniqueEmail = $emailParts[0] . '_' . rand(100, 999) . '@' . ($emailParts[1] ?? 'reyonglobal.com');
    // If password hash exists, use same password hash so user can log in with same password
    $passwordHash = !empty($src['password']) ? $src['password'] : password_hash('passWord123#', PASSWORD_BCRYPT);

    $pdo->beginTransaction();

    try {
        // Insert new independent member
        $stmtIns = $pdo->prepare("
            INSERT INTO members (
                member_id, sponsor_id, placement_parent_id, matrix_position,
                name, email, phone, password, used_epin, package_type, status, kyc_status,
                address_line, place, city, pincode, state, pan_number, aadhaar_number,
                bank_name, bank_account_number, ifsc_code, bep20_address, qr_code_url
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, 'Active', 'Approved',
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?
            )
        ");

        $stmtIns->execute([
            $newMemberId,
            $sponsorId,
            $placementParentId,
            $matrixPos,
            $duplicateName,
            $src['email'], // keep same contact details or updated
            $src['phone'],
            $passwordHash,
            $epinCode,
            $packageType,
            $src['address_line'] ?? null,
            $src['place'] ?? null,
            $src['city'] ?? null,
            $src['pincode'] ?? null,
            $src['state'] ?? null,
            $src['pan_number'] ?? null,
            $src['aadhaar_number'] ?? null,
            $src['bank_name'] ?? null,
            $src['bank_account_number'] ?? null,
            $src['ifsc_code'] ?? null,
            $src['bep20_address'] ?? null,
            $src['qr_code_url'] ?? null
        ]);

        // Initialize Independent Wallet
        $stmtWallet = $pdo->prepare("INSERT INTO wallets (member_id, balance, user_wallet_50, burfee_cart_wallet, charity_wallet, user_wallet_60, company_wallet_40) VALUES (?, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00)");
        $stmtWallet->execute([$newMemberId]);

        // Log creation transaction
        $stmtTx = $pdo->prepare("
            INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
            VALUES (?, 'Admin_Adjustment', 0.00, 'Main', 'Credit', ?)
        ");
        $desc = "Independent member account duplicated from {$sourceMemberId}. Placed under {$placementParentId} (Position {$matrixPos}).";
        $stmtTx->execute([$newMemberId, $desc]);

        // Distribute Matrix Level Commissions for the new independent account
        distributeMatrixCommissions($pdo, $newMemberId);

        $pdo->commit();

        return [
            'success' => true,
            'new_member_id' => $newMemberId,
            'placement_parent_id' => $placementParentId,
            'matrix_position' => $matrixPos,
            'message' => "Successfully duplicated member {$sourceMemberId} as new independent account {$newMemberId} placed under {$placementParentId} (Position {$matrixPos})."
        ];

    } catch (Exception $e) {
        $pdo->rollBack();
        return [
            'success' => false,
            'message' => "Failed to duplicate member: " . $e->getMessage()
        ];
    }
}
