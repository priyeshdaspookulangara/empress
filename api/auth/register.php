<?php
require_once __DIR__ . '/../api_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed. Use POST.'], 405);
}

$input = getJsonInput();
$pdo = getDBConnection();

$res = registerMember($pdo, [
    'sponsor_id' => $input['sponsor_id'] ?? 'EMP100000',
    'name' => $input['name'] ?? '',
    'email' => $input['email'] ?? '',
    'phone' => $input['phone'] ?? '',
    'password' => $input['password'] ?? '',
    'epin_code' => $input['epin_code'] ?? ''
]);

if ($res['success']) {
    $tokenData = generateBearerToken($pdo, 'member', $res['member_id']);
    sendJsonResponse([
        'success' => true,
        'message' => $res['message'],
        'member_id' => $res['member_id'],
        'token' => $tokenData['token'],
        'expires_at' => $tokenData['expires_at']
    ], 201);
} else {
    sendJsonResponse([
        'success' => false,
        'message' => $res['message']
    ], 400);
}
