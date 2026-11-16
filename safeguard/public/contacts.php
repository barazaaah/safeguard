<?php
require_once '../config/auth.php';
requireLogin();
require_once '../config/db.php';

$userId = $_SESSION['user_id'];
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['contact_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $relationship = trim($_POST['relationship'] ?? '');

    if ($name !== '' && $phone !== '') {
        $stmt = $conn->prepare(
            "INSERT INTO emergency_contacts
            (user_id, contact_name, phone, relationship)
            VALUES (?, ?, ?, ?)"
        );

        $stmt->execute([
            $userId, $name, $phone, $relationship
        ]);

        $message = "Emergency contact added successfully.";
    } else {
        $message = "Please enter the contact name and phone number.";
    }
}

if (isset($_GET['delete'])) {
    $contactId = (int) $_GET['delete'];

    $stmt = $conn->prepare(
        "DELETE FROM emergency_contacts
         WHERE id = ? AND user_id = ?"
    );

    $stmt->execute([$contactId, $userId]);

    header("Location: contacts.php");
    exit();
}

$stmt = $conn->prepare(
    "SELECT * FROM emergency_contacts
     WHERE user_id = ? ORDER BY id DESC"
);
$stmt->execute([$userId]);
$contacts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Emergency Contacts</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <h2>SAFEGUARD</h2>
    <a href="index.php">HOME</a>
    <a href="send_sos.php">SEND SOS</a>
    <a href="user_dashboard.php">DASHBOARD</a>
    <a href="contacts.php">CONTACTS</a>
    <a href="logout.php">LOGOUT</a>
</nav>

<main class="dashboard">
    <h1>EMERGENCY CONTACTS</h1>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST">
        <input type="text" name="contact_name"
            placeholder="Contact Full Name" required>

        <input type="text" name="phone"
            placeholder="Phone Number" required>

        <input type="text" name="relationship"
            placeholder="Relationship">

        <button type="submit">ADD CONTACT</button>
    </form>

    <h2>YOUR TRUSTED CONTACTS</h2>

    <table>
        <tr>
            <th>NAME</th>
            <th>PHONE</th>
            <th>RELATIONSHIP</th>
            <th>ACTION</th>
        </tr>

        <?php foreach ($contacts as $contact): ?>
        <tr>
            <td><?php echo htmlspecialchars($contact['contact_name']); ?></td>
            <td><?php echo htmlspecialchars($contact['phone']); ?></td>
            <td><?php echo htmlspecialchars($contact['relationship']); ?></td>
            <td>
                <a href="contacts.php?delete=<?php echo (int)$contact['id']; ?>"
                   onclick="return confirm('Delete this contact?')">
                    DELETE
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</main>

</body>
</html>
