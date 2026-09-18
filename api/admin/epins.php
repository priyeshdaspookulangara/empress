<?php
require_once __DIR__ . '/../api_helper.php';

$pdo = getDBConnection();
$tokenRow = authenticateApiUser($pdo, 'admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = getJsonInput();
    $packageType = $input['package_type'] ?? 'Starter_5000';
    $quantity = (int)($input['quantity'] ?? 1);
    $quantity = max(1, min(100, $quantity));

    if (!in_array($packageType, ['Starter_5000', 'Empress_15000'])) {
        $packageType = 'Starter_5000';
    }

    $pdo->beginTransaction();
    try {
        $stmtIns = $pdo->prepare("INSERT INTO epins (epin_code, package_type, status, generated_by_admin_id) VALUES (?, ?, 'Unused', ?)");
        $generatedCodes = [];
        for ($i = 0; $i < $quantity; $i++) {
            $code = generateEpinCode();
            $stmtIns->execute([$code, $packageType, $tokenRow['user_id']]);
            $generatedCodes[] = $code;
        }
        $pdo->commit();
        sendJsonResponse([
            'success' => true,
            'message' => "Generated {$quantity} ePINs for {$packageType}.",
            'epins' => $generatedCodes
        ], 201);
    } catch (Exception $e) {
        $pdo->rollBack();
        sendJsonResponse(['success' => false, 'message' => "Generation failed: " . $e->getMessage()], 500);
    }
}

// GET request
$stmt = $pdo->query("SELECT * FROM epins ORDER BY id DESC LIMIT 100");
$epins = $stmt->fetchAll();

sendJsonResponse([
    'success' => true,
    'data' => $epins
]);
