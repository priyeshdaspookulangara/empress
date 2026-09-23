<?php
require_once __DIR__ . '/../api_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Method not allowed. Use POST.'], 405);
}

$input = getJsonInput();
$pdo = getDBConnection();

$loginType = strtolower(trim($input['user_type'] ?? 'member'));

if ($loginType === 'admin') {
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';

    if (empty($username) || empty($password)) {
        sendJsonResponse(['success' => false, 'message' => 'Username and password required.'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $tokenData = generateBearerToken($pdo, 'admin', (string)$admin['id']);
        sendJsonResponse([
            'success' => true,
            'user_type' => 'admin',
            'user' => [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'role' => $admin['role'] ?? 'admin'
            ],
            'token' => $tokenData['token'],
            'expires_at' => $tokenData['expires_at']
        ]);
    } else {
        sendJsonResponse(['success' => false, 'message' => 'Invalid admin credentials.'], 401);
    }

} else {
    // Member Login
    $memberId = strtoupper(trim($input['member_id'] ?? ''));
    $password = $input['password'] ?? '';

    if (empty($memberId) || empty($password)) {
        sendJsonResponse(['success' => false, 'message' => 'Member ID and password required.'], 400);
    }

    $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch();

    if ($member && password_verify($password, $member['password'])) {
        if ($member['status'] !== 'Active') {
            sendJsonResponse(['success' => false, 'message' => 'Account is inactive.'], 403);
        }

        $tokenData = generateBearerToken($pdo, 'member', $member['member_id']);
        sendJsonResponse([
            'success' => true,
            'user_type' => 'member',
            'user' => [
                'member_id' => $member['member_id'],
                'name' => $member['name'],
                'email' => $member['email'],
                'phone' => $member['phone'],
                'package_type' => $member['package_type'],
                'kyc_status' => $member['kyc_status']
            ],
            'token' => $tokenData['token'],
            'expires_at' => $tokenData['expires_at']
        ]);
    } else {
        sendJsonResponse(['success' => false, 'message' => 'Invalid Member ID or password.'], 401);
    }
}
