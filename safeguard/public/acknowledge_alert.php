
<?php

require_once '../config/db.php';
require_once '../includes/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: alerts.php");
    exit();
}

$alert_id = filter_input(INPUT_POST, 'alert_id', FILTER_VALIDATE_INT);

if (!$alert_id) {
    header("Location: alerts.php?error=invalid");
    exit();
}

try {
    $stmt = $conn->prepare("
        UPDATE alerts
        SET status = 'Responding',
            acknowledged_at = NOW()
        WHERE id = ?
          AND status = 'Pending'
          AND escalation_status = 'Waiting'
    ");

    $stmt->execute([$alert_id]);

    if ($stmt->rowCount() > 0) {
        header("Location: admin_alert_details.php?id=$alert_id&success=acknowledged");
    } else {
        header("Location: admin_alert_details.php?id=$alert_id&error=alreadyhandled");
    }

    exit();

} catch (PDOException $e) {
    error_log("Acknowledgement error: " . $e->getMessage());
    header("Location: admin_alert_details.php?id=$alert_id&error=updatefailed");
    exit();
}
