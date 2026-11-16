<?php
require_once '../config/db.php';
require_once '../config/auth.php';

requireAdmin();

$stmt = $conn->query("
    SELECT
        e.id AS log_id,
        e.alert_id,
        e.recipient,
        e.message,
        e.delivery_status,
        e.created_at,
        a.incident_type,
        a.status AS alert_status,
        u.fullname,
        u.phone
    FROM escalation_logs e
    JOIN alerts a ON a.id = e.alert_id
    JOIN users u ON u.id = a.user_id
    ORDER BY e.created_at DESC
");

$escalations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emergency Escalations | SafeGuard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h1>Emergency Escalation History</h1>
    <p>Emergencies automatically escalated after the response deadline.</p>

    <p>
        <a href="admin_dashboard.php">BACK TO DASHBOARD</a>
    </p>

    <?php if (count($escalations) > 0): ?>

    <div style="overflow-x:auto;">
        <table>
            <thead>
                <tr>
                    <th>ALERT ID</th>
                    <th>PERSON</th>
                    <th>PHONE</th>
                    <th>INCIDENT</th>
                    <th>ALERT STATUS</th>
                    <th>NOTIFICATION</th>
                    <th>ESCALATED AT</th>
                    <th>DETAILS</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($escalations as $item): ?>
                <tr>
                    <td>#<?php echo (int)$item['alert_id']; ?></td>

                    <td>
                        <?php echo htmlspecialchars($item['fullname']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($item['phone']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($item['incident_type']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($item['alert_status']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($item['delivery_status']); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($item['created_at']); ?>
                    </td>

                    <td>
                        <a href="admin_alert_details.php?id=<?php echo (int)$item['alert_id']; ?>">
                            VIEW ALERT
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>

        <p>No escalated emergencies have been recorded yet.</p>

    <?php endif; ?>
</div>

</body>
</html>
