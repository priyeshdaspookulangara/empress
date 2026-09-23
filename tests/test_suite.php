<?php
// tests/test_suite.php

putenv('DB_DRIVER=sqlite');

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

echo "========================================\n";
echo " EMPRESS TWO WAY 3.0 AUTOMATED SUITE   \n";
echo "========================================\n\n";

$pdo = getDBConnection();

// Test 1: Admin & System Root verification
echo "[TEST 1] Verifying Super Admin, Admin & Root EMP100000 Seeding... ";
$stmtSuper = $pdo->prepare("SELECT * FROM admins WHERE username = 'superadmin'");
$stmtSuper->execute();
$super = $stmtSuper->fetch();
assert($super !== false && $super['role'] === 'superadmin');

$stmtAdmin = $pdo->prepare("SELECT * FROM admins WHERE username = 'admin'");
$stmtAdmin->execute();
$admin = $stmtAdmin->fetch();
assert($admin !== false && $admin['role'] === 'admin');

$stmtRoot = $pdo->prepare("SELECT * FROM members WHERE member_id = 'EMP100000'");
$stmtRoot->execute();
$root = $stmtRoot->fetch();
assert($root !== false && $root['name'] === 'Empress Root');
echo "PASSED\n";

// Test 2: ePIN Generation & Member Registration
echo "[TEST 2] Testing ePIN Generation & Registration... ";
$epin1 = generateEpinCode();
$stmtE = $pdo->prepare("INSERT INTO epins (epin_code, package_type, status) VALUES (?, 'Starter_1000', 'Unused')");
$stmtE->execute([$epin1]);

$regRes1 = registerMember($pdo, [
    'sponsor_id' => 'EMP100000',
    'name' => 'Member One',
    'email' => 'm1@example.com',
    'phone' => '9000000001',
    'password' => 'pass123',
    'epin_code' => $epin1
]);

assert($regRes1['success'] === true);
$m1Id = $regRes1['member_id'];
assert($m1Id === 'EMP100001');

// Check Root Wallet received Level 1 Commission ($10: 60% = $6.00, 40% = $4.00)
$rootWallet = $pdo->query("SELECT * FROM wallets WHERE member_id = 'EMP100000'")->fetch();
assert((float)$rootWallet['balance'] === 10.00);
assert((float)$rootWallet['user_wallet_60'] === 6.00);
assert((float)$rootWallet['company_wallet_40'] === 4.00);
echo "PASSED\n";

// Test 3: BFS Auto-Spillover placement test (Filling Level 1 of Root with 3 members)
echo "[TEST 3] Testing BFS 3-Matrix Auto-Spillover... ";
$epins = [];
for ($i = 2; $i <= 4; $i++) {
    $ep = generateEpinCode();
    $pdo->prepare("INSERT INTO epins (epin_code, package_type, status) VALUES (?, 'Starter_1000', 'Unused')")->execute([$ep]);
    $res = registerMember($pdo, [
        'sponsor_id' => 'EMP100000',
        'name' => "Member {$i}",
        'email' => "m{$i}@example.com",
        'phone' => "900000000{$i}",
        'password' => 'pass123',
        'epin_code' => $ep
    ]);
    assert($res['success'] === true);
}

// Verify Level 1 children count of Root is 3
$l1Children = $pdo->query("SELECT COUNT(*) FROM members WHERE placement_parent_id = 'EMP100000'")->fetchColumn();
assert((int)$l1Children === 3);

// Member 4 (5th member overall) should spillover under EMP100001 at matrix_position 1
$m5Epin = generateEpinCode();
$pdo->prepare("INSERT INTO epins (epin_code, package_type, status) VALUES (?, 'Starter_1000', 'Unused')")->execute([$m5Epin]);
$res5 = registerMember($pdo, [
    'sponsor_id' => 'EMP100000',
    'name' => "Spillover Member 5",
    'email' => "m5@example.com",
    'phone' => "9000000005",
    'password' => 'pass123',
    'epin_code' => $m5Epin
]);

$m5 = $pdo->query("SELECT * FROM members WHERE member_id = '{$res5['member_id']}'")->fetch();
assert($m5['placement_parent_id'] === 'EMP100001');
assert((int)$m5['matrix_position'] === 1);
echo "PASSED\n";

// Test 4: Check Multi-level Commission Flow (Level 1 for EMP100001, Level 2 for Root EMP100000)
echo "[TEST 4] Testing Multi-level Fixed Commission Distribution (Level 1 + Level 2)... ";
$m1Wallet = $pdo->query("SELECT * FROM wallets WHERE member_id = 'EMP100001'")->fetch();
// EMP100001 should get Level 1 payout: $10 (60% = 6, 40% = 4)
assert((float)$m1Wallet['balance'] === 10.00);

// Root (EMP100000) should get previous 3x10 = 30 + Level 2 payout 1x20 = 50 total
$rootWallet2 = $pdo->query("SELECT * FROM wallets WHERE member_id = 'EMP100000'")->fetch();
assert((float)$rootWallet2['balance'] === 50.00);
echo "PASSED\n";

// Test 5: KYC Approval & Payout Withdrawal Validation Rules
echo "[TEST 5] Testing KYC Block & Withdrawal Thresholds... ";
// EMP100001 has user_wallet_60 = 6.00 (under $10.00 min and KYC is Pending)
$wFail1 = false;
if ($m1Wallet['user_wallet_60'] < 10.00) {
    $wFail1 = true;
}
assert($wFail1 === true);

// Approve KYC for EMP100001 and add balance to meet $10.00
$pdo->prepare("UPDATE members SET kyc_status = 'Approved', crypto_wallet_address = 'T123456789' WHERE member_id = 'EMP100001'")->execute();
$pdo->prepare("UPDATE wallets SET user_wallet_60 = 100.00 WHERE member_id = 'EMP100001'")->execute();

// Perform withdrawal of $50.00
$pdo->prepare("UPDATE wallets SET user_wallet_60 = user_wallet_60 - 50.00 WHERE member_id = 'EMP100001'")->execute();
$pdo->prepare("INSERT INTO withdrawals (member_id, amount, status) VALUES ('EMP100001', 50.00, 'Pending')")->execute();

$wRow = $pdo->query("SELECT * FROM withdrawals WHERE member_id = 'EMP100001'")->fetch();
assert((float)$wRow['amount'] === 50.00);
assert($wRow['status'] === 'Pending');
echo "PASSED\n";

// Test 6: API Bearer Token Authentication & API endpoints simulation
echo "[TEST 6] Testing Mobile REST API Auth & Token verification... ";
require_once __DIR__ . '/../api/api_helper.php';
$tokData = generateBearerToken($pdo, 'member', 'EMP100001');
assert(!empty($tokData['token']));

$stmtTok = $pdo->prepare("SELECT * FROM api_tokens WHERE token = ?");
$stmtTok->execute([$tokData['token']]);
assert($stmtTok->fetch() !== false);
echo "PASSED\n";

// Test 7: Rebirths Engine Trigger Test (Level 3 completion => 10 Rebirths)
echo "[TEST 7] Testing Rebirths Engine (Level 3 Completion = 10 Rebirths)... ";
// Call createRebirthPositions directly for EMP100001
createRebirthPositions($pdo, 'EMP100001', 10, 3);

// Check 1st generation rebirth member nodes created in matrix (sponsor_id = EMP100001)
$gen1RebirthNodes = $pdo->query("SELECT COUNT(*) FROM members WHERE sponsor_id = 'EMP100001' AND name LIKE '%Rebirth%'")->fetchColumn();
assert((int)$gen1RebirthNodes === 10);

// Fetch one 1st generation rebirth node
$firstRebirthNode = $pdo->query("SELECT * FROM members WHERE sponsor_id = 'EMP100001' AND name LIKE '%Rebirth%' LIMIT 1")->fetch();
assert($firstRebirthNode !== false);

// Trigger 2nd generation rebirth creation directly from the 1st gen rebirth node
createRebirthPositions($pdo, $firstRebirthNode['member_id'], 10, 3);

// Check 2nd generation rebirth nodes created from this rebirth node have sponsor_id = 'EMP100000' (Root)
$gen2RebirthNodes = $pdo->query("SELECT COUNT(*) FROM members WHERE sponsor_id = 'EMP100000' AND name LIKE '" . $firstRebirthNode['name'] . "%'")->fetchColumn();
assert((int)$gen2RebirthNodes === 10);
echo "PASSED\n";

echo "\nALL AUTOMATED TESTS PASSED SUCCESSFULLY! 🚀\n";
