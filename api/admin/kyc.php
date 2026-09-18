<?php
require_once __DIR__ . '/../api_helper.php';

$pdo = getDBConnection();
authenticateApiUser($pdo, 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();
    $memberId = trim($input['member_id'] ?? '');
    $action = $input['action'] ?? '';

    if (empty($memberId) || !in_array($action, ['Approve', 'Reject'])) {
        sendJsonResponse(['success' => false, 'message' => 'member_id and valid action (Approve/Reject) required.'], 400);
    }

    $newStatus = ($action === 'Approve') ? 'Approved' : 'Rejected';
    $stmt = $pdo->prepare("UPDATE members SET kyc_status = ? WHERE member_id = ?");
    $stmt->execute([$newStatus, $memberId]);

    sendJsonResponse(['success' => true, 'message' => "KYC status for {$memberId} updated to {$newStatus}."]);
}

// GET request
$stmt = $pdo->query("SELECT * FROM members WHERE kyc_status IN ('Submitted', 'Pending', 'Rejected', 'Approved') ORDER BY FIELD(kyc_status, 'Submitted', 'Pending', 'Rejected', 'Approved'), id DESC");
$kycList = $stmt->fetchAll();

sendJsonResponse([
    'success' => true,
    'data' => $kycList
]);
