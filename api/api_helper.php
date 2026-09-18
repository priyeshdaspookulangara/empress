<?php
// api/api_helper.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!headers_sent()) {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
}

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'OPTIONS') {
    if (!headers_sent()) {
        http_response_code(200);
    }
    exit();
}

function sendJsonResponse($data, $statusCode = 200) {
    if (!headers_sent()) {
        http_response_code($statusCode);
    }
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit();
}

function getJsonInput() {
    $input = file_get_contents('php://input');
    if (empty($input)) {
        return $_POST;
    }
    $data = json_decode($input, true);
    return is_array($data) ? array_merge($_POST, $data) : $_POST;
}

function generateBearerToken($pdo, $userType, $userId) {
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));

    $stmt = $pdo->prepare("INSERT INTO api_tokens (user_type, user_id, token, expires_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userType, $userId, $token, $expiresAt]);

    return [
        'token' => $token,
        'expires_at' => $expiresAt
    ];
}

function authenticateApiUser($pdo, $requiredType = 'member') {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Authorization Bearer token missing.'
        ], 401);
    }

    $token = trim($matches[1]);

    $stmt = $pdo->prepare("SELECT * FROM api_tokens WHERE token = ? AND expires_at > NOW()");
    $stmt->execute([$token]);
    $tokenRow = $stmt->fetch();

    if (!$tokenRow) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Invalid or expired Bearer token.'
        ], 401);
    }

    if ($requiredType !== null && $tokenRow['user_type'] !== $requiredType) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Unauthorized access for this user role.'
        ], 403);
    }

    return $tokenRow;
}
