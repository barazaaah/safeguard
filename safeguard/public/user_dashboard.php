<?php
require_once '../config/auth.php';
requireLogin();
require_once '../config/db.php';

$userId = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT COUNT(*) FROM alerts WHERE user_id = ?"
);
$stmt->execute([$userId]);
$totalAlerts = $stmt->fetchColumn();

$stmt = $conn->prepare(
    "SELECT COUNT(*) FROM emergency_contacts WHERE user_id = ?"
);
$stmt->execute([$userId]);
$totalContacts = $stmt->fetchColumn();

$stmt = $conn->prepare(
    "SELECT * FROM alerts
     WHERE user_id = ?
     ORDER BY created_at DESC LIMIT 5"
);
$stmt->execute([$userId]);
$alerts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>SafeGuard Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <h2>SAFEGUARD</h2>
    <a href="index.php">HOME</a>
    <a href="user_dashboard.php">DASHBOARD</a>
    <a href="contacts.php">EMERGENCY CONTACTS</a>
    <a href="alert_history.php">ALERT HISTORY</a>
    <a href="logout.php">LOGOUT</a>
</nav>

<main class="dashboard">
    <h1>Welcome,
        <?php echo htmlspecialchars($_SESSION['fullname']); ?>
    </h1>

    <div class="stats">
        <div class="stat-card">
            <h3>TOTAL SOS ALERTS</h3>
            <p><?php echo $totalAlerts; ?></p>
        </div>

        <div class="stat-card">
            <h3>EMERGENCY CONTACTS</h3>
            <p><?php echo $totalContacts; ?></p>
        </div>
    </div>

    <div class="sos-section">
        <h2>EMERGENCY ASSISTANCE</h2>
        <p>Use this feature to record and submit an emergency alert.</p>

        <a class="sos-button" href="send_sos.php">
            SEND SOS
        </a>
    </div>

    <h2>RECENT EMERGENCY ALERTS</h2>

    <table>
        <tr>
            <th>INCIDENT</th>
            <th>STATUS</th>
            <th>DATE</th>
        </tr>

        <?php foreach ($alerts as $alert): ?>
        <tr>
            <td>
                <?php echo htmlspecialchars($alert['incident_type']); ?>
            </td>
            <td>
                <?php echo htmlspecialchars($alert['status']); ?>
            </td>
            <td>
                <?php echo htmlspecialchars($alert['created_at']); ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</main>

</body>
</html>
