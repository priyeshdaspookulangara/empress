<?php
require_once __DIR__ . '/../api_helper.php';

$pdo = getDBConnection();
$tokenRow = authenticateApiUser($pdo, 'member');
$memberId = $tokenRow['user_id'];

$matrixTree = getMemberMatrixTree($pdo, $memberId);
$downlineList = getMemberDownline6Levels($pdo, $memberId);
$totalRebirths = getMemberTotalRebirths($pdo, $memberId);

sendJsonResponse([
    'success' => true,
    'data' => [
        'matrix_tree' => $matrixTree,
        'downline_6_levels' => $downlineList,
        'total_rebirths_earned' => $totalRebirths
    ]
]);
