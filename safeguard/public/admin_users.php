<?php
require_once '../config/auth.php';
requireAdmin();
require_once '../config/db.php';


$stmt = $conn->query(
    "SELECT id, fullname, email, phone, role
     FROM users
     ORDER BY id DESC"
);

$users = $stmt->fetchAll();


?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users - SafeGuard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<nav>
    <h2>SAFEGUARD ADMIN</h2>

    <div>
        <a href="admin_dashboard.php">DASHBOARD</a>
        <a href="admin_users.php">USERS</a>
        <a href="admin_alert_details.php">ALERTS</a>
        <a href="logout.php">LOGOUT</a>
    </div>
</nav>

<main class="dashboard">

    <h1>REGISTERED ACCOUNTS</h1>

    <p>View all users and administrator accounts registered in SafeGuard.</p>

    <div class="table-container">
    <table>

        <tr>
            <th>ID</th>
            <th>FULL NAME</th>
            <th>EMAIL</th>
            <th>PHONE</th>
            <th>ROLE</th>
            <th>REGISTRATION DATE</th>
        </tr>

        <?php foreach ($users as $user): ?>

        <tr>
            <td><?php echo (int)$user['id']; ?></td>

            <td>
                <?php echo htmlspecialchars($user['fullname']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($user['email']); ?>
            </td>

            <td>
                <?php echo htmlspecialchars($user['phone']); ?>
            </td>

            <td>
                <strong>
                    <?php echo htmlspecialchars(strtoupper($user['role'])); ?>
                </strong>
            </td>

            <td>
                <?php echo htmlspecialchars($user['id']); ?>
            </td>
        </tr>

        <?php endforeach; ?>

    </table>
    </div>

</main>

</body>
</html>
