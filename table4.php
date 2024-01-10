<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

function getCurrentAssignedUser($pdo, $tableId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE assigned_table = :tableId AND status = 'assigned' LIMIT 1");
    $stmt->execute(['tableId' => $tableId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function assignUserToTable($pdo, $userId, $tableId) {
    $stmt = $pdo->prepare("UPDATE users SET assigned_table = :tableId, status = 'assigned' WHERE user_id = :userId");
    $stmt->execute(['tableId' => $tableId, 'userId' => $userId]);
}

function finishUser($pdo, $userId) {
    $stmt = $pdo->prepare("UPDATE users SET status = 'finished', finished_at = CURRENT_TIMESTAMP WHERE user_id = :userId");
    $stmt->execute(['userId' => $userId]);
}

$tableId = 4; // This is for table 1

if (isset($_POST['assign'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE status = 'waiting' ORDER BY created_at ASC LIMIT 1");
    $stmt->execute();
    $nextUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($nextUser) {
        assignUserToTable($pdo, $nextUser['user_id'], $tableId);
    }
}

if (isset($_POST['finish'])) {
    $currentUser = getCurrentAssignedUser($pdo, $tableId);
    if ($currentUser) {
        finishUser($pdo, $currentUser['user_id']);
    }
}

$currentUser = getCurrentAssignedUser($pdo, $tableId); // Fetch the currently assigned user to display
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Үстел номері 4</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 0;
        }
        .container {
            background-color: #ffffff;
            padding: 2em;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 80%;
            max-width: 600px;
        }
        .section {
            margin-bottom: 1em;
            padding: 1em;
            background: #e1f5fe;
            border-left: 5px solid #03a9f4;
            text-align: left;
        }
        .highlight {
            font-size: 1.5em;
            background-color: #e9e9e9;
            color: #5b5b5b;
            border-radius: 5px;
            margin: 0.5em 0;
            padding: 1em;
            border-left: 5px solid #ff9800;
            text-align: center;
        }
        button {
            padding: 10px 20px;
            font-size: 1em;
            border-radius: 5px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="section">
            <h1>Үстел номері 4</h1>
        </div>
        <div class="highlight">
            <?php if ($currentUser): ?>
                Келу керек : <?php echo isset($currentUser['user_id']) ? htmlspecialchars(substr($currentUser['user_id'], -3)) : 'N/A'; ?>
            <?php else: ?>
                Қазіргі уақытта 4-Үстелге тағайындалған пайдаланушылар жоқ.
            <?php endif; ?>
        </div>
        <form method="post" class="section">
            <button type="submit" name="assign">Келесі</button>
        </form>
        <?php if ($currentUser): ?>
            <form method="post" class="section">
                <button type="submit" name="finish">Сәтті аяқтау</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
