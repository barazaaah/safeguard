
<?php
require_once '../config/db.php';
require_once '../config/auth.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: admin_alert.php");
    exit();
}

// Get alert ID and new status
$alert_id = filter_input(INPUT_POST, 'alert_id', FILTER_VALIDATE_INT);
$status = $_POST['status'] ?? '';

$allowed_statuses = ['Pending', 'Responding', 'Resolved'];

// Validate input
if (!$alert_id || !in_array($status, $allowed_statuses, true)) {
    header("Location: admin_alert.php?error=invalid");
    exit();
}

try {
    // Check that the alert exists
    $check = $conn->prepare(
        "SELECT id FROM alerts WHERE id = ?"
    );
    $check->execute([$alert_id]);

    if (!$check->fetch()) {
        header("Location: admin_alert.php?error=notfound");
        exit();
    }

    // Update alert status
    $stmt = $conn->prepare(
        "UPDATE alerts SET status = ? WHERE id = ?"
    );

    $stmt->execute([$status, $alert_id]);

    header("Location: admin_alert_details.php?id=" . $alert_id . "&success=updated");
    exit();

} catch (PDOException $e) {
    error_log("Alert update error: " . $e->getMessage());
    header("Location: admin_alert.php?error=updatefailed");
    exit();
}
?>
