<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

// Function to check if the user's latest entry is finished
function isLatestEntryFinished($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :userId ORDER BY created_at DESC LIMIT 1");
    $stmt->execute(['userId' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user && $user['status'] == 'finished';
}

function getUserStatus($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT status, assigned_table FROM users WHERE user_id = :userId ORDER BY created_at DESC LIMIT 1");
    $stmt->execute(['userId' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get the next sequence number for today's date
function getNextSequenceId($pdo) {
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT MAX(user_seq_id) as max_seq FROM users WHERE date_id = :today");
    $stmt->execute(['today' => $today]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return ($result['max_seq'] ?? 0) + 1;
}

// Function to add a new user to the queue
function addNewUser($pdo) {
    $seqId = getNextSequenceId($pdo);
    $today = date('Y-m-d');
    $userId = date('md') . '-' . str_pad($seqId, 3, '0', STR_PAD_LEFT); // e.g., 0424-001

    // Check the length of $userId
    if (strlen($userId) > 12) {
        throw new Exception("Generated user ID is too long: " . $userId);
    }

    $stmt = $pdo->prepare("INSERT INTO users (user_id, user_seq_id, date_id, status) VALUES (:userId, :seqId, :today, 'waiting')");
    $stmt->execute(['userId' => $userId, 'seqId' => $seqId, 'today' => $today]);
    return $userId;
}

// Cookie lifetime set to 15 minutes
$cookieLifetime = 15 * 60;

$userId = ''; // Initialize the user ID variable
// Check if the user has an existing cookie
if (isset($_COOKIE['user_id'])) {
    $userId = $_COOKIE['user_id'];

    if (isLatestEntryFinished($pdo, $userId)) {
        // If the latest entry for this user is 'finished', re-queue the user
        $userId = addNewUser($pdo);
    }

    // Refresh the cookie expiration time
    setcookie('user_id', $userId, time() + $cookieLifetime, "/");
} else {
    // No cookie, create a new user
    $userId = addNewUser($pdo);
    setcookie('user_id', $userId, time() + $cookieLifetime, "/");
}
$userStatus = getUserStatus($pdo, $userId);

if ($userStatus) {
    if ($userStatus['assigned_table']) {
        $statusMessage = 'Сізге тағайындалған үстел ' . htmlspecialchars($userStatus['assigned_table']) . '.';
    } else {
        $statusMessage = 'Нөміріңіз теледидар экранында пайда болғанша күтіңіз.';
    }
} else {
    // Default message if no status is found
    $statusMessage = 'Кезекке қосылу үшін QR кодын сканерлеңіз немесе мәліметтеріңізді енгізіңіз.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Электронды кезек</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7f7f7;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
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
        .info {
            background: #e1f5fe;
            border-left: 5px solid #03a9f4;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="section">
            <h1>НЗМ-ге құжаттарды тапсыру үшін электронды кезек</h1>
        </div>
        <div class="highlight" id="user-id-display">
    		Сіздің кезектегі номеріңіз: <?php echo htmlspecialchars(substr($userId, -3)); ?>
		</div>

        <div class="highlight" id="status-display">
            <?php echo $statusMessage; ?>
        </div>
        <div class="section info">
            <p><strong>Инструкция:</strong></p>
            <p>Нөміріңіз смартфонда және теледидар экранында көрсетілгенше күтіңіз. Нөмір экранда көрсетілсе, тағайындалған үстелге өтіңіз.</p>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const statusContainer = document.querySelector('#status-display'); // Corrected selector
        const userId = '<?php echo $userId; ?>';

        function updateStatus() {
            fetch('get_status.php?user_id=' + encodeURIComponent(userId))
                .then(response => response.json())
                .then(data => {
                    if (data.assigned_table) {
                        statusContainer.textContent = 'Мына үстелге келіңіз ' + data.assigned_table + '.';
                    } else {
                        statusContainer.textContent = 'Нөміріңіз смартфонда және теледидар экранында көрсетілгенше күтіңіз.';
                    }
                })
                .catch(error => {
                    console.error('Error fetching status:', error);
                    statusContainer.textContent = 'There was an error updating the status. Please check manually. ' + error.toString();
                });
        }

        setInterval(updateStatus, 1000); // Update the status every second
        updateStatus(); // Also fetch the status immediately when the page loads
    });
</script>

</body>
</html>
