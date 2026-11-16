<?php
session_start();
require_once '../config/db.php';

$message = "";

// Change this code to your own private invitation code.
$adminCode = "SAFEGUARD2026";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $invitation = $_POST['admin_code'] ?? '';

    if ($fullname === '' || $email === '' ||
        $phone === '' || $password === '') {

        $message = "All fields are required.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Enter a valid email address.";

    } elseif (strlen($password) < 8) {

        $message = "Password must have at least 8 characters.";

    } elseif (!hash_equals($adminCode, $invitation)) {

        $message = "Invalid administrator invitation code.";

    } else {

        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );

        $check->execute([$email]);

        if ($check->fetch()) {

            $message = "This email is already registered.";

        } else {

            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users (fullname, email, phone, password, role)
                 VALUES (?, ?, ?, ?, 'admin')"
            );

            $stmt->execute([
                $fullname,
                $email,
                $phone,
                $hashedPassword
            ]);

            header("Location: login.php?registered=1");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>SafeGuard Administrator Registration</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<div class="auth-container">

    <h1>SafeGuard</h1>

    <h2>Administrator Registration</h2>

    <p style="color:#d62828;">
        Restricted access: Authorized administrators only.
    </p>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST">

        <input type="text" name="fullname"
            placeholder="Administrator Full Name" required>

        <input type="email" name="email"
            placeholder="Administrator Email" required>

        <input type="text" name="phone"
            placeholder="Phone Number" required>

        <input type="password" name="password"
            placeholder="Create Password" minlength="8" required>

        <input type="password" name="admin_code"
            placeholder="Administrator Invitation Code" required>

        <button type="submit">
            Create Administrator Account
        </button>

    </form>

    <p>
        Already an administrator?
        <a href="login.php">Login here</a>
    </p>

</div>

</body>
</html>
