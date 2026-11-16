<?php
session_start();
require_once '../config/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare(
        "SELECT * FROM users WHERE email = ?"
    );
    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if ($user && password_verify(
        $password,
        $user['password']
    )) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }
        exit();
    } else {
        $message = "Incorrect email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SafeGuard Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-container">
    <h1>SAFEGUARD</h1>
    <h2>LOGIN TO YOUR ACCOUNT</h2>

    <?php if (isset($_GET['registered'])): ?>
        <p>Registration successful. Please log in.</p>
    <?php endif; ?>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST">
        <input type="email" name="email"
            placeholder="Email Address" required>

        <input type="password" name="password"
            placeholder="Password" required>

        <button type="submit">LOGIN</button>
    </form>

    <p>Don't have an account?
        <a href="register.php">REGISTER</a>
    </p>
</div>

</body>
</html>
