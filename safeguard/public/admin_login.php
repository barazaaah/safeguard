<?php
session_start();
require_once '../config/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE email = ? AND role = 'admin'"
    );

    $stmt->execute([$email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {

        session_regenerate_id(true);

        $_SESSION['user_id'] = $admin['id'];
        $_SESSION['fullname'] = $admin['fullname'];
        $_SESSION['role'] = 'admin';

        header("Location: admin_dashboard.php");
        exit();

    } else {
        $message = "Invalid administrator email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SafeGuard Admin Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-container">

    <h1>SAFEGUARD</h1>
    <h2>ADMINISTRATOR LOGIN</h2>

    <p style="color:red;">
        <?php echo htmlspecialchars($message); ?>
    </p>

    <?php if (isset($_GET['registered'])): ?>
        <p>Administrator account created. Please log in.</p>
    <?php endif; ?>

    <form method="POST">

        <input type="email" name="email"
            placeholder="Administrator Email" required>

        <input type="password" name="password"
            placeholder="Password" required>

        <button type="submit">ADMIN LOGIN</button>

    </form>

    <p>
        <a href="index.php">Return to SafeGuard Home</a>
    </p>

</div>

</body>
</html>
