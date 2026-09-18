<?php
require_once __DIR__ . '/../api_helper.php';

$pdo = getDBConnection();
authenticateApiUser($pdo, 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $input = getJsonInput();
    $memberId = trim($input['member_id'] ?? $_GET['member_id'] ?? '');

    if (empty($memberId)) {
        sendJsonResponse(['success' => false, 'message' => 'member_id required.'], 400);
    }

    $res = deleteMemberSafely($pdo, $memberId);
    if ($res['success']) {
        sendJsonResponse(['success' => true, 'message' => $res['message']]);
    } else {
        sendJsonResponse(['success' => false, 'message' => $res['message']], 400);
    }
}

// GET request
$search = trim($_GET['search'] ?? '');
$sql = "SELECT m.*, w.balance, w.user_wallet_60, w.company_wallet_40
        FROM members m
        LEFT JOIN wallets w ON m.member_id = w.member_id";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE m.member_id LIKE ? OR m.name LIKE ? OR m.email LIKE ? OR m.phone LIKE ?";
    $term = "%$search%";
    $params = [$term, $term, $term, $term];
}

$sql .= " ORDER BY m.id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

sendJsonResponse([
    'success' => true,
    'data' => $members
]);
