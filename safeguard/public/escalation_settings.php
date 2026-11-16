<?php
require_once '../config/db.php';
require_once '../config/auth.php';

requireAdmin();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$success = '';
$error = '';

// Save settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $error = "Invalid security token. Please refresh and try again.";
    } else {

        $response_minutes = filter_input(
            INPUT_POST,
            'response_minutes',
            FILTER_VALIDATE_INT
        );

        $contact_name = trim($_POST['police_contact_name'] ?? '');
        $police_phone = trim($_POST['police_phone'] ?? '');
        $police_email = trim($_POST['police_email'] ?? '');
        $notification_method = $_POST['notification_method'] ?? 'Demo';
        $auto_enabled = isset($_POST['auto_escalation_enabled']) ? 1 : 0;

        $allowed_methods = ['Demo', 'SMS', 'Email'];

        if (
            !$response_minutes ||
            $response_minutes < 1 ||
            $response_minutes > 60
        ) {
            $error = "Response time must be between 1 and 60 minutes.";

        } elseif (!in_array($notification_method, $allowed_methods, true)) {
            $error = "Please select a valid notification method.";

        } elseif (
            $police_email !== '' &&
            !filter_var($police_email, FILTER_VALIDATE_EMAIL)
        ) {
            $error = "Please enter a valid email address.";

        } elseif (
            $police_phone !== '' &&
            !preg_match('/^[0-9+\-\s()]{7,25}$/', $police_phone)
        ) {
            $error = "Please enter a valid phone number.";

        } else {

            try {
                $stmt = $conn->prepare("
                    INSERT INTO escalation_settings (
                        id,
                        response_minutes,
                        police_contact_name,
                        police_phone,
                        police_email,
                        notification_method,
                        auto_escalation_enabled
                    )
                    VALUES (1, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                        response_minutes = VALUES(response_minutes),
                        police_contact_name = VALUES(police_contact_name),
                        police_phone = VALUES(police_phone),
                        police_email = VALUES(police_email),
                        notification_method = VALUES(notification_method),
                        auto_escalation_enabled = VALUES(auto_escalation_enabled)
                ");

                $stmt->execute([
                    $response_minutes,
                    $contact_name,
                    $police_phone,
                    $police_email,
                    $notification_method,
                    $auto_enabled
                ]);

                $success = "Emergency escalation settings saved successfully.";

            } catch (PDOException $e) {
                error_log("Escalation settings error: " . $e->getMessage());
                $error = "Unable to save settings. Please try again.";
            }
        }
    }
}

// Load current settings
$stmt = $conn->query("
    SELECT *
    FROM escalation_settings
    WHERE id = 1
");

$settings = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$settings) {
    $conn->exec("
        INSERT INTO escalation_settings (id)
        VALUES (1)
    ");

    $stmt = $conn->query("
        SELECT *
        FROM escalation_settings
        WHERE id = 1
    ");

    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Escalation Settings | SafeGuard</title>

    <link rel="stylesheet" href="style.css">

    <style>
        body {
            background: #f3f6fa;
        }

        .settings-container {
            max-width: 950px;
            margin: 35px auto;
            padding: 20px;
        }

        .settings-header {
            background: #10283f;
            color: white;
            padding: 27px;
            border-radius: 13px;
            margin-bottom: 24px;
        }

        .settings-header h1 {
            margin: 0 0 8px;
        }

        .settings-card {
            background: white;
            padding: 28px;
            border-radius: 13px;
            box-shadow: 0 4px 15px rgba(16,40,63,.06);
            margin-bottom: 22px;
        }

        .settings-card h3 {
            color: #10283f;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .settings-field {
            margin-bottom: 19px;
        }

        .settings-field label {
            display: block;
            font-weight: 700;
            margin-bottom: 8px;
            color: #26384a;
        }

        .settings-field input,
        .settings-field select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccd5df;
            border-radius: 7px;
            box-sizing: border-box;
            font-size: 15px;
        }

        .settings-field small {
            display: block;
            color: #718096;
            margin-top: 6px;
        }

        .settings-toggle {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #f7f9fc;
            border-radius: 9px;
        }

        .settings-toggle input {
            width: 19px;
            height: 19px;
        }

        .settings-toggle label {
            font-weight: 700;
            color: #10283f;
        }

        .settings-button {
            border: none;
            background: #d92d3a;
            color: white;
            padding: 13px 23px;
            border-radius: 7px;
            font-weight: 700;
            cursor: pointer;
        }

        .settings-button:hover {
            background: #b91f2c;
        }

        .success-message {
            background: #d1fae5;
            color: #065f46;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .settings-note {
            background: #fff7e6;
            border-left: 4px solid #ed9b25;
            padding: 15px;
            border-radius: 7px;
            color: #754c0e;
            margin-bottom: 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #087f8c;
            font-weight: 700;
            text-decoration: none;
        }
    </style>
</head>

<body>

<div class="settings-container">

    <a href="admin_dashboard.php" class="back-link">
        ← Back to Admin Dashboard
    </a>

    <div class="settings-header">
        <h1>Emergency Escalation Settings</h1>
        <p>
            Configure how SafeGuard handles emergencies when
            administrators do not respond in time.
        </p>
    </div>

    <?php if ($success): ?>
        <div class="success-message">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="settings-note">
        <strong>Important:</strong>
        Demo mode records a simulated notification only.
        SMS and email delivery require a configured service
        and an authorized emergency recipient.
    </div>

    <form method="POST" action="escalation_settings.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>"
        >

        <div class="settings-card">

            <h3>1. Response Time Configuration</h3>

            <div class="settings-field">
                <label for="response_minutes">
                    Administrator Response Time
                </label>

                <select name="response_minutes" id="response_minutes" required>
                    <?php foreach ([1, 2, 3, 5, 10, 15, 30, 60] as $minutes): ?>
                        <option
                            value="<?php echo $minutes; ?>"
                            <?php echo ((int)$settings['response_minutes'] === $minutes) ? 'selected' : ''; ?>
                        >
                            <?php echo $minutes; ?>
                            <?php echo $minutes === 1 ? 'Minute' : 'Minutes'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <small>
                    If an emergency is not acknowledged within this
                    period, SafeGuard will escalate it.
                </small>
            </div>

            <div class="settings-toggle">
                <input
                    type="checkbox"
                    id="auto_escalation_enabled"
                    name="auto_escalation_enabled"
                    value="1"
                    <?php echo !empty($settings['auto_escalation_enabled']) ? 'checked' : ''; ?>
                >

                <label for="auto_escalation_enabled">
                    Enable automatic emergency escalation
                </label>
            </div>

        </div>

        <div class="settings-card">

            <h3>2. Emergency Contact Information</h3>

            <div class="settings-field">
                <label for="police_contact_name">
                    Police Station / Emergency Contact Name
                </label>

                <input
                    type="text"
                    name="police_contact_name"
                    id="police_contact_name"
                    placeholder="Enter authorized contact or station name"
                    value="<?php echo htmlspecialchars($settings['police_contact_name'] ?? ''); ?>"
                >
            </div>

            <div class="settings-field">
                <label for="police_phone">
                    Emergency Contact Phone Number
                </label>

                <input
                    type="tel"
                    name="police_phone"
                    id="police_phone"
                    placeholder="Enter verified contact phone number"
                    value="<?php echo htmlspecialchars($settings['police_phone'] ?? ''); ?>"
                >
            </div>

            <div class="settings-field">
                <label for="police_email">
                    Emergency Contact Email
                </label>

                <input
                    type="email"
                    name="police_email"
                    id="police_email"
                    placeholder="Enter authorized contact email"
                    value="<?php echo htmlspecialchars($settings['police_email'] ?? ''); ?>"
                >
            </div>

        </div>

        <div class="settings-card">

            <h3>3. Notification Configuration</h3>

            <div class="settings-field">
                <label for="notification_method">
                    Notification Method
                </label>

                <select
                    name="notification_method"
                    id="notification_method"
                    required
                >
                    <option value="Demo"
                        <?php echo $settings['notification_method'] === 'Demo' ? 'selected' : ''; ?>>
                        Demonstration Mode
                    </option>

                    <option value="SMS"
                        <?php echo $settings['notification_method'] === 'SMS' ? 'selected' : ''; ?>>
                        SMS Notification
                    </option>

                    <option value="Email"
                        <?php echo $settings['notification_method'] === 'Email' ? 'selected' : ''; ?>>
                        Email Notification
                    </option>
                </select>

                <small>
                    SMS and Email are configuration choices only until
                    their delivery services are integrated.
                </small>
            </div>

        </div>

        <button type="submit" class="settings-button">
            Save Escalation Settings
        </button>

    </form>

</div>

</body>
</html>
