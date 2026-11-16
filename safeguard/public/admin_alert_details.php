
<?php
require_once '../config/auth.php';
requireAdmin();

require_once '../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$alertId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$alertId || $alertId < 1) {
    die("Invalid emergency alert ID.");
}

$message = '';
$error = '';

// Update alert status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = "Invalid request. Please refresh the page and try again.";
    } else {

        $newStatus = $_POST['status'] ?? '';

        $allowedStatuses = [
            'Pending',
            'Responding',
            'Resolved'
        ];

        if (!in_array($newStatus, $allowedStatuses, true)) {
            $error = "Please select a valid emergency status.";
        } else {

            $update = $conn->prepare(
                "UPDATE alerts SET status = ? WHERE id = ?"
            );

            $update->execute([$newStatus, $alertId]);

            if ($update->rowCount() > 0) {
                $message = "Emergency status updated successfully.";
            } else {
                $message = "The alert status is already set to that value.";
            }
        }
    }
}

// Get complete emergency details
$stmt = $conn->prepare(
    "SELECT
        a.id,
        a.incident_type,
        a.description,
        a.location,
        a.latitude,
        a.longitude,
        a.status,
        a.created_at,
        u.fullname,
        u.email,
        u.phone
     FROM alerts a
     JOIN users u ON a.user_id = u.id
     WHERE a.id = ?"
);

$stmt->execute([$alertId]);
$alert = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$alert) {
    die("Emergency alert not found.");
}

$hasCoordinates =
    $alert['latitude'] !== null &&
    $alert['longitude'] !== null &&
    is_numeric($alert['latitude']) &&
    is_numeric($alert['longitude']);

$mapUrl = $hasCoordinates
    ? "https://www.google.com/maps?q=" .
        rawurlencode($alert['latitude'] . ',' . $alert['longitude'])
    : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Emergency Details | SafeGuard</title>

    <link rel="stylesheet" href="style.css">
</head>

<body>

<nav>
    <div class="logo">SAFEGUARD ADMIN</div>

    <div class="navigation">
        <a href="admin_dashboard.php">DASHBOARD</a>
        <a href="admin_alert.php">ALERTS</a>
        <a href="admin_users.php">USERS</a>
        <a href="admin_reports.php">REPORTS</a>
        <a href="logout.php">LOGOUT</a>
    </div>
</nav>

<div class="container">

    <h1>EMERGENCY ALERT DETAILS</h1>

    <p>Review the information submitted by the user.</p>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <?= e($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="card">

        <h2>ALERT INFORMATION</h2>

        <div class="detail-row">
            <strong>ALERT ID:</strong>
            <span>#<?= e($alert['id']) ?></span>
        </div>

        <div class="detail-row">
            <strong>INCIDENT TYPE:</strong>
            <span><?= e($alert['incident_type']) ?></span>
        </div>

        <div class="detail-row">
            <strong>DESCRIPTION:</strong>
            <span><?= nl2br(e($alert['description'])) ?></span>
        </div>

        <div class="detail-row">
            <strong>DATE REPORTED:</strong>
            <span><?= e($alert['created_at']) ?></span>
        </div>

        <div class="detail-row">
            <strong>CURRENT STATUS:</strong>
            <span>
                <span class="status status-<?= strtolower(e($alert['status'])) ?>">
                    <?= e($alert['status']) ?>
                </span>
            </span>
        </div>

    </div>

    <div class="card">

        <h2>USER INFORMATION</h2>

        <div class="detail-row">
            <strong>FULL NAME:</strong>
            <span><?= e($alert['fullname']) ?></span>
        </div>

        <div class="detail-row">
            <strong>EMAIL:</strong>
            <span><?= e($alert['email']) ?></span>
        </div>

        <div class="detail-row">
            <strong>PHONE NUMBER:</strong>
            <span><?= e($alert['phone']) ?></span>
        </div>

    </div>

    <div class="card">

        <h2>EMERGENCY LOCATION</h2>

        <p>
            <strong>Reported Address:</strong>
            <?= e($alert['location']) ?>
        </p>

        <?php if ($hasCoordinates): ?>

            <p>
                <strong>Latitude:</strong>
                <?= e($alert['latitude']) ?>
            </p>

            <p>
                <strong>Longitude:</strong>
                <?= e($alert['longitude']) ?>
            </p>

            <a href="<?= e($mapUrl) ?>"
               target="_blank"
               rel="noopener"
               class="btn btn-primary">
                Open Location in Google Maps
            </a>

        <?php else: ?>

            <div class="alert alert-warning">
                GPS coordinates were not provided for this emergency.
            </div>

        <?php endif; ?>

    </div>

    <div class="card">

        <h2>UPDATE EMERGENCY STATUS</h2>

        <form method="POST"
              action="admin_alert_details.php?id=<?= e($alert['id']) ?>">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= e($_SESSION['csrf_token']) ?>">

            <label for="status">SELECT NEW STATUS:</label>

            <select name="status" id="status" required>

                <option value="Pending"
                    <?= $alert['status'] === 'Pending' ? 'selected' : '' ?>>
                    PENDING
                </option>

                <option value="Responding"
                    <?= $alert['status'] === 'Responding' ? 'selected' : '' ?>>
                    RESPONDING
                </option>

                <option value="Resolved"
                    <?= $alert['status'] === 'Resolved' ? 'selected' : '' ?>>
                    RESOLVED
                </option>

            </select>

            <button type="submit">
                SAVE STATUS UPDATE
            </button>

        </form>


            <?php if (
                $alert['status'] === 'Pending' &&
                $alert['status'] === 'Waiting'
            ): ?>

            <form action="acknowledge_alert.php" method="POST">

                <input type="hidden" name="alert_id"
                    value="<?php echo (int)$alert['id']; ?>">

                <button type="submit" class="btn btn-primary">
                    Acknowledge Emergency
                </button>

            </form>

            <?php endif; ?>


    </div>

    <a href="admin_alert.php" class="btn btn-secondary">
        BACK TO ALL EMERGENCY ALERTS
    </a>

</div>

<footer>
    <p>SafeGuard Emergency SOS and Personal Safety Management System</p>
    <p>Administrator Control Panel</p>
</footer>

</body>
</html>
