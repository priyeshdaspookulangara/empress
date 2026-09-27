<?php
// includes/functions.php

require_once __DIR__ . '/../config/db.php';

// Level payout schedule in USD ($) (Level 1 to 6)
// Scaled to 90% of joining amount ($9.00 / 900 INR matrix pool allocation after 10% direct referrer commission)
const DIRECT_REFERRAL_AMOUNT = 1.00; // 10% of $10.00 joining package (100 INR)

const MATRIX_PAYOUTS = [
    1 => 4.50,
    2 => 9.00,
    3 => 18.00,
    4 => 27.00,
    5 => 36.00,
    6 => 45.00,
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
 * Process 10% Direct Referral Commission & Matrix Level Commissions for 6 Levels above $newMemberId
 * Applies 50:50 Smart Wallet division (50% Customer Wallet, 50% Company -> 60% Burfee Cart / 40% Charity).
 */
function distributeMatrixCommissions($pdo, $newMemberId) {
    // 1. Credit 10% Direct Referrer Income ($1.00 USD / 100 INR) to sponsor_id
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
        $descRef = "10% Direct Referrer Commission from new member {$newMemberId}. (50% Customer: \${$userRef}, Company 50%: \${$burfeeRef} Burfee Cart / \${$charityRef} Charity)";
        $stmtTx->execute([$sponsorId, $refAmount, $descRef]);
    }

    // 2. Distribute 90% Matrix Pool Allocation across 6 levels
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

    // Extract clean base name without previous Rebirth suffixes
    $cleanBaseName = trim(preg_replace('/\s*\(Rebirth\s*#.*$/i', '', $parent['name']));

    for ($i = 1; $i <= $count; $i++) {
        $placement = findBFSMatrixPlacement($pdo, 'EMP100000');
        $placementParentId = $placement['placement_parent_id'];
        $matrixPos = $placement['matrix_position'];

        $rebirthMemberId = generateMemberID($pdo);
        $rebirthName = $cleanBaseName . " (Rebirth #{$i} - L{$completedLevel})";
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
 * Register a new member WITHOUT requiring an ePIN code (for Admin SQL Import & direct registration).
 * Triggers full BFS Matrix placement, wallet initialization, 50:50 commission distribution, and rebirth checks.
 */
function registerMemberWithoutEpin($pdo, $data) {
    $sponsorId = !empty($data['sponsor_id']) ? trim($data['sponsor_id']) : 'EMP100000';
    $name = !empty($data['name']) ? trim($data['name']) : 'Burfee Cart';
    $email = !empty($data['email']) ? trim($data['email']) : ('member_' . time() . '_' . rand(100,999) . '@empress2way.com');
    $phone = !empty($data['phone']) ? trim($data['phone']) : ('9' . str_pad(rand(0, 999999999), 9, '0', STR_PAD_LEFT));
    $rawPassword = !empty($data['password']) ? $data['password'] : '123456';
    $password = password_hash($rawPassword, PASSWORD_BCRYPT);
    $packageType = !empty($data['package_type']) ? trim($data['package_type']) : 'Starter_1000';
    $epinCode = !empty($data['used_epin']) ? trim($data['used_epin']) : ('ADMIN_SQL_IMPORT_' . strtoupper(bin2hex(random_bytes(3))));

    // Check sponsor exists
    $stmtSponsor = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ? AND status = 'Active'");
    $stmtSponsor->execute([$sponsorId]);
    if (!$stmtSponsor->fetch()) {
        $sponsorId = 'EMP100000';
    }

    // Find BFS Matrix Placement
    $placement = findBFSMatrixPlacement($pdo, 'EMP100000');
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
 * Update P2P Wallet Settings (BEP-20 Address & Receiving QR Code Image)
 */
function updateP2PWalletSettings($pdo, $memberId, $bep20Address, $fileArr = null) {
    $bep20Address = trim($bep20Address);

    // Validate BEP-20 Address (42-character Ethereum/BSC standard address format starting with 0x)
    if (!empty($bep20Address) && !preg_match('/^0x[a-fA-F0-9]{40}$/', $bep20Address)) {
        return [
            'success' => false,
            'message' => 'Invalid BEP-20 Wallet Address. Address must be a valid 42-character hex string starting with "0x" (e.g., 0x9811cCf1E9dcc6451357D9f983E6E9bA615920B5).'
        ];
    }

    $qrCodeUrl = null;

    // Handle File Upload if provided
    if ($fileArr && isset($fileArr['tmp_name']) && is_uploaded_file($fileArr['tmp_name'])) {
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

        $fileName = $fileArr['name'];
        $fileTmp = $fileArr['tmp_name'];
        $fileSize = $fileArr['size'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Validate Extension & MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileTmp);
        finfo_close($finfo);

        if (!in_array($ext, $allowedExts) || !in_array($mimeType, $allowedMimes)) {
            return [
                'success' => false,
                'message' => 'Invalid file format. Only JPG, JPEG, PNG, and WEBP image files are allowed for personal QR code.'
            ];
        }

        if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
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
 * Re-allocate Direct Referral Commission when a member's sponsor_id is updated by Admin/Super Admin
 */
function reallocateDirectReferralCommission($pdo, $memberId, $oldSponsorId, $newSponsorId) {
    $oldSponsorId = strtoupper(trim($oldSponsorId));
    $newSponsorId = strtoupper(trim($newSponsorId));

    if (empty($oldSponsorId) || empty($newSponsorId) || $oldSponsorId === $newSponsorId) {
        return ['success' => true, 'message' => 'No sponsor change required.'];
    }

    $refAmount = DIRECT_REFERRAL_AMOUNT; // $1.00 USD (10% of $10.00)
    $userRef = round($refAmount * 0.50, 2);
    $burfeeRef = round($refAmount * 0.30, 2);
    $charityRef = round($refAmount * 0.20, 2);
    $companyRef = round($refAmount * 0.50, 2);

    $pdo->beginTransaction();
    try {
        // 1. Debit Direct Referral Commission from Old Sponsor
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

            // Log Debit Transaction
            $stmtTx1 = $pdo->prepare("
                INSERT INTO transactions (member_id, type, amount, wallet_type, status, description)
                VALUES (?, 'Admin_Adjustment', ?, 'Main', 'Debit', ?)
            ");
            $descDeb = "Direct Referrer Income (\${$refAmount}) transferred out to new sponsor {$newSponsorId} for member {$memberId}.";
            $stmtTx1->execute([$oldSponsorId, $refAmount, $descDeb]);
        }

        // 2. Ensure New Sponsor Wallet Exists and Credit Direct Referral Commission
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

        // Log Credit Transaction
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

/**
 * Clean & Format Member Name (Separates person's actual name from Rebirth badges/labels)
 * Returns array: ['clean_name' => string, 'is_rebirth' => bool, 'rebirth_label' => string]
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
 * Verify USDT (BEP-20) Transaction on BSC Blockchain (via BSC JSON-RPC and BscScan API)
 * Returns array: ['success' => bool, 'verified' => bool, 'amount' => float, 'sender' => string, 'receiver' => string, 'message' => string]
 */
function verifyBscTransactionOnChain($txHash, $expectedReceiverWallet = '0x9811cCf1E9dcc6451357D9f983E6E9bA615920B5', $bscScanApiKey = '') {
    $txHash = trim($txHash);
    $expectedReceiverWallet = strtolower(trim($expectedReceiverWallet));
    $usdtContract = '0x55d398326f99059ff775485246999027b3197955'; // Official BSC USDT Token Contract

    if (!preg_match('/^0x[a-fA-F0-9]{64}$/', $txHash)) {
        return [
            'success' => false,
            'verified' => false,
            'message' => 'Invalid 66-character TxHash format.'
        ];
    }

    // 1. Attempt BscScan API Endpoint
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
    $curlErr = curl_error($ch);
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

    // 2. Fallback: Public BSC JSON-RPC Node (https://bsc-dataseed.binance.org/)
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
        'message' => 'Transaction Hash not found or not yet confirmed on BSC blockchain. Ensure funds were sent to ' . $expectedReceiverWallet
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
        // Mark deposit as Approved
        $stmtUp = $pdo->prepare("UPDATE deposits SET status = 'Approved' WHERE id = ?");
        $stmtUp->execute([$depositId]);

        // Ensure user wallet exists
        $stmtW = $pdo->prepare("SELECT member_id FROM wallets WHERE member_id = ?");
        $stmtW->execute([$userId]);
        if (!$stmtW->fetch()) {
            $pdo->prepare("INSERT INTO wallets (member_id, balance, user_wallet_50, burfee_cart_wallet, charity_wallet, user_wallet_60, company_wallet_40) VALUES (?, 0, 0, 0, 0, 0, 0)")->execute([$userId]);
        }

        // Credit user wallet
        $stmtCredit = $pdo->prepare("
            UPDATE wallets
            SET balance = balance + ?,
                user_wallet_50 = user_wallet_50 + ?,
                user_wallet_60 = user_wallet_60 + ?
            WHERE member_id = ?
        ");
        $stmtCredit->execute([$amount, $amount, $amount, $userId]);

        // Record Transaction
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
 * Reset Database to Fresh Initial State (0 Customer Members, only Root EMP100000 & Admins remain)
 */
function resetDatabaseToCleanState($pdo) {
    $pdo->beginTransaction();
    try {
        $pdo->exec("DELETE FROM members WHERE member_id != 'EMP100000'");
        $pdo->exec("DELETE FROM wallets WHERE member_id != 'EMP100000'");
        $pdo->exec("UPDATE wallets SET balance = 0.00, user_wallet_50 = 0.00, burfee_cart_wallet = 0.00, charity_wallet = 0.00, user_wallet_60 = 0.00, company_wallet_40 = 0.00 WHERE member_id = 'EMP100000'");
        $pdo->exec("DELETE FROM transactions");
        $pdo->exec("DELETE FROM withdrawals");
        $pdo->exec("DELETE FROM deposits");
        $pdo->exec("DELETE FROM member_rebirths");
        $pdo->exec("DELETE FROM epins WHERE epin_code != 'SYSTEM_ROOT_EPIN'");
        $pdo->exec("DELETE FROM api_tokens");

        $pdo->commit();
        return ['success' => true, 'message' => 'Database successfully reset to clean state! All customer members and transactions purged. Only Root EMP100000 and Admins remain.'];
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['success' => false, 'message' => 'Database reset failed: ' . $e->getMessage()];
    }
}
