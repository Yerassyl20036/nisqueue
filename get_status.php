<?php
require_once 'config.php';

header('Content-Type: application/json');

// Define the getUserStatus function (or include it from another file)
function getUserStatus($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT status, assigned_table FROM users WHERE user_id = :userId ORDER BY created_at DESC LIMIT 1");
    $stmt->execute(['userId' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$userId = $_COOKIE['user_id'] ?? ''; 

try {
    $status = getUserStatus($pdo, $userId); 
    echo json_encode($status);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

?>