<?php
// display.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';

date_default_timezone_set('Asia/Almaty'); // Timezone for Nur-Sultan, Kazakhstan
$current_time = date('d.m.Y H:i'); // Format: DD.MM.YYYY HH:MM


function getAssignedUsers($pdo) {
    $stmt = $pdo->prepare("SELECT user_id, assigned_table FROM users WHERE status = 'assigned' ORDER BY assigned_table ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$assignedUsers = getAssignedUsers($pdo);
$assignedTables = array_column($assignedUsers, 'user_id', 'assigned_table');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Кезек</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #ffffff;
            padding: 1em;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .current-time {
            font-size: 1.2em;
            color: #333;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1em;
            padding: 1em;
            max-width: 600px;
            margin: auto;
        }
        .table {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 1em;
            text-align: center;
        }
        .table h2 {
            margin: 0;
            color: #333;
        }
        .user-id {
            font-size: 1.5em;
            color: #007bff;
            margin-top: 0.5em;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Қазіргі күй</h1>
        <div class="current-time"><?php echo $current_time; ?></div>
    </div>
    <div class="grid">
        <?php for ($table = 1; $table <= 5; $table++): ?>
            <div class="table" id="table-<?php echo $table; ?>">
                <h2>Үстел номері <?php echo $table; ?></h2>
                <div class="user-id">
                    <?php echo isset($assignedTables[$table]) ? htmlspecialchars($assignedTables[$table]) : 'Бос'; ?>
                </div>
            </div>
        <?php endfor; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function fetchStatus() {
                var xhr = new XMLHttpRequest();
                xhr.onreadystatechange = function() {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        var response = JSON.parse(xhr.responseText);
                        for (var i = 1; i <= 5; i++) {
                            var userId = response.find(function(user) {
                                return user.assigned_table == i;
                            });
                            var userDisplay = document.querySelector('#table-' + i + ' .user-id');
                            userDisplay.textContent = userId ? userId.user_id : 'Бос';
                        }
                    }
                };
                xhr.open('GET', 'get_assigned_users.php', true);
                xhr.send();
            }

            setInterval(fetchStatus, 1000); // Fetch status every second
            fetchStatus(); // Also fetch status immediately when the page loads
        });
    </script>
</body>
</html>


