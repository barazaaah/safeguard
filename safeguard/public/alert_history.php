<?php
require_once '../config/auth.php';
requireLogin();
require_once '../config/db.php';

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT * FROM alerts
     WHERE user_id = ?
     ORDER BY created_at DESC"
);
$stmt->execute([$userId]);

$alerts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>ALERT HISTORY</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <h2>SAFEGUARD</h2>
    <a href="user_dashboard.php">DASHBOARD</a>
    <a href="contacts.php">CONTACTS</a>
    <a href="admin_alert.php">ALERT HISTORY</a>
    <a href="logout.php">LOGOUT</a>
</nav>

<main class="dashboard">

    <h1>EMERGENCY ALERT HISTORY</h1>

    <?php if (isset($_GET['sent'])): ?>
        <p>Your SOS alert has been saved successfully.</p>
    <?php endif; ?>

    <table>
        <tr>
            <th>INCIDENT</th>
            <th>DESCRIPTION</th>
            <th>LOCATION</th>
            <th>STATUS</th>
            <th>DATE</th>
        </tr>

        <?php foreach ($alerts as $alert): ?>
        <tr>
            <td><?php echo htmlspecialchars($alert['incident_type']); ?></td>
            <td><?php echo htmlspecialchars($alert['description'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($alert['location'] ?? ''); ?></td>
            <td><?php echo htmlspecialchars($alert['status']); ?></td>
            <td><?php echo htmlspecialchars($alert['created_at']); ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

</main>
</body>
</html>
