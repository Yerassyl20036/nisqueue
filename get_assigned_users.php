<?php
require_once 'config.php';

header('Content-Type: application/json');

function getAssignedUsers($pdo) {
    $stmt = $pdo->prepare("SELECT user_id, assigned_table FROM users WHERE status = 'assigned' ORDER BY assigned_table ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$assignedUsers = getAssignedUsers($pdo);
echo json_encode($assignedUsers);
?>