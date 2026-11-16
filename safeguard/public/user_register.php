<?php
session_start();
require_once '../config/db.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($fullname === '' || $email === '' ||
        $phone === '' || $password === '') {
        $message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $message = "Password must have at least 8 characters.";
    } else {
        $check = $conn->prepare(
            "SELECT id FROM users WHERE email = ?"
        );
        $check->execute([$email]);

        if ($check->fetch()) {
            $message = "Email already registered.";
        } else {
            $hashedPassword = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            $stmt = $conn->prepare(
                "INSERT INTO users
                (fullname, email, phone, password)
                VALUES (?, ?, ?, ?)"
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
    <title>SafeGuard Registration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="auth-container">
    <h1>SAFEGUARD</h1>
    <h2>CREATE YOUR ACCOUNT</h2>

    <p><?php echo htmlspecialchars($message); ?></p>

    <form method="POST">
        <input type="text" name="fullname"
            placeholder="Full Name" required>

        <input type="email" name="email"
            placeholder="Email Address" required>

        <input type="text" name="phone"
            placeholder="Phone Number" required>

        <input type="password" name="password"
            placeholder="Password (minimum 8 characters)"
            required minlength="8">

        <button type="submit">REGISTER</button>
    </form>

    <p>Already have an account?
        <a href="login.php">LOGIN</a>
        
    </p>
</div>

</body>
</html>
