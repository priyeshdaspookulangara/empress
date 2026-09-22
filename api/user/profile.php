<?php
require_once __DIR__ . '/../api_helper.php';

$pdo = getDBConnection();
$tokenRow = authenticateApiUser($pdo, 'member');
$memberId = $tokenRow['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();

    $addressLine = trim($input['address_line'] ?? '');
    $place = trim($input['place'] ?? '');
    $city = trim($input['city'] ?? '');
    $pincode = trim($input['pincode'] ?? '');
    $state = trim($input['state'] ?? '');
    $panNumber = strtoupper(trim($input['pan_number'] ?? ''));
    $aadhaarNumber = trim($input['aadhaar_number'] ?? '');
    $cryptoWallet = trim($input['crypto_wallet_address'] ?? '');
    $walletNetwork = trim($input['wallet_network'] ?? 'USDT (TRC20)');

    if (empty($addressLine) || empty($city) || empty($state) || empty($pincode) || empty($cryptoWallet)) {
        sendJsonResponse(['success' => false, 'message' => 'Address fields and crypto_wallet_address are required.'], 400);
    }

    $stmt = $pdo->prepare("
        UPDATE members
        SET address_line = ?, place = ?, city = ?, pincode = ?, state = ?,
            pan_number = ?, aadhaar_number = ?, crypto_wallet_address = ?,
            wallet_network = ?, kyc_status = 'Submitted'
        WHERE member_id = ?
    ");
    $stmt->execute([
        $addressLine, $place, $city, $pincode, $state,
        $panNumber, $aadhaarNumber, $cryptoWallet,
        $walletNetwork, $memberId
    ]);

    sendJsonResponse(['success' => true, 'message' => 'Profile & KYC details updated successfully.']);
}

// GET request: Fetch full profile
$stmt = $pdo->prepare("
    SELECT member_id, sponsor_id, placement_parent_id, matrix_position, name, email, phone, package_type, status,
           address_line, place, city, pincode, state, pan_number, aadhaar_number, bank_name, bank_account_number, ifsc_code, crypto_wallet_address, wallet_network, kyc_status, created_at
    FROM members WHERE member_id = ?
");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

sendJsonResponse([
    'success' => true,
    'data' => $member
]);
