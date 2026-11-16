<?php
require_once '../config/auth.php';
requireLogin();
require_once '../config/db.php';

$userId = $_SESSION['user_id'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $incident = trim($_POST['incident_type'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');

    $latitude = (
        $_POST['latitude'] ?? ''
    ) !== '' ? (float) $_POST['latitude'] : null;

    $longitude = (
        $_POST['longitude'] ?? ''
    ) !== '' ? (float) $_POST['longitude'] : null;

    $allowedTypes = [
        'Medical Emergency',
        'Accident',
        'Personal Threat',
        'Fire',
        'Other'
    ];

    if (!in_array($incident, $allowedTypes, true)) {
        $message = "Please select a valid emergency type.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO alerts
            (user_id, incident_type, description,
             location, latitude, longitude, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')"
        );

        $stmt->execute([
            $userId,
            $incident,
            $description,
            $location,
            $latitude,
            $longitude
        ]);

        $alertId = $conn->lastInsertId();

        $stmt = $conn->prepare(
            "SELECT id FROM emergency_contacts
             WHERE user_id = ?"
        );
        $stmt->execute([$userId]);

        $contacts = $stmt->fetchAll();

        foreach ($contacts as $contact) {
            $notification = $conn->prepare(
                "INSERT INTO notifications
                (alert_id, contact_id, message)
                VALUES (?, ?, ?)"
            );

            $notification->execute([
                $alertId,
                $contact['id'],
                "Emergency alert submitted. Incident: " . $incident
            ]);
        }

        header("Location: alert_history.php?sent=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Emergency SOS</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <h2>SafeGuard</h2>
    <a href="user_dashboard.php">Dashboard</a>
    <a href="contacts.php">Contacts</a>
    <a href="logout.php">Logout</a>
</nav>

<main class="dashboard">
    <h1>Emergency SOS</h1>

    <p>
        Submit an emergency alert with your incident
        details and location.
    </p>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST" id="sosForm">

        <label>Emergency Type</label>

        <select name="incident_type" required>
            <option value="">Select Emergency</option>
            <option>Medical Emergency</option>
            <option>Accident</option>
            <option>Personal Threat</option>
            <option>Fire</option>
            <option>Other</option>
        </select>

        <label>Description</label>

        <textarea name="description"
            placeholder="Describe the emergency"></textarea>

        <label>Location</label>

        <input type="text" name="location"
            id="location" placeholder="Enter your location">

        <input type="hidden" name="latitude" id="latitude">
        <input type="hidden" name="longitude" id="longitude">

        <button type="button" onclick="getLocation()">
            Capture My Location
        </button>

        <p id="locationStatus"></p>

        <button type="submit" class="sos-button">
            SUBMIT SOS ALERT
        </button>

    </form>
</main>

<script>
function getLocation() {
    const status = document.getElementById("locationStatus");

    if (!navigator.geolocation) {
        status.textContent = "Geolocation is not supported.";
        return;
    }

    status.textContent = "Getting your location...";

    navigator.geolocation.getCurrentPosition(
        function(position) {
            document.getElementById("latitude").value =
                position.coords.latitude;

            document.getElementById("longitude").value =
                position.coords.longitude;

            status.textContent =
                "Location captured successfully.";
        },
        function(error) {
            status.textContent =
                "Unable to get location. Please enter it manually.";
        }
    );
}
</script>

</body>
</html>
