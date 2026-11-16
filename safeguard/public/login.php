<?php
require_once "../config/db.php";
session_start();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {

        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: user_dashboard.php");
        }

        exit;

    } else {
        $message = "Incorrect email or password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login | SafeGuard</title>
    <link rel="stylesheet" href="style.css">

    <style>
 
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    height: 100vh;
    background-image: url('images/slight side.jpeg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    display: flex;
    justify-content: center;
    align-items: center;
  }

  
  .login-container {
    background-color: rgba(255, 255, 255, 0.9);
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 8px 16px rgba(0,0,0,0.3);
    max-width: 400px;
    width: 90%;
    text-align: center;
  }

  
  h3 {
    margin-bottom: 20px;
    color: #2c3e50;
    font-weight: 600;
  }

  
  .emblem {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
  }

  .emblem img {
    max-width: 100px;
    height: auto;
  }

  
  form {
    display: flex;
    flex-direction: column;
    align-items: stretch;
  }

  label {
    margin-top: 10px;
    margin-bottom: 5px;
    font-weight: 600;
    color: #34495e;
  }

  input[type="email"],
  input[type="password"] {
    padding: 10px;
    border: 1px solid #bdc3c7;
    border-radius: 5px;
    font-size: 1em;
    transition: border-color 0.3s;
  }

  input[type="email"]:focus,
  input[type="password"]:focus {
    border-color: #2980b9;
    outline: none;
  }

  
  button {
    margin-top: 20px;
    padding: 12px;
    background-color: #2980b9;
    color: #fff;
    border: none;
    border-radius: 5px;
    font-size: 1em;
    cursor: pointer;
    transition: background-color 0.3s;
  }

  button:hover {
    background-color: #3498db;
  }

  
  .links {
    margin-top: 20px;
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
  }

  .links a {
    margin: 5px 10px;
    text-decoration: none;
    color: #2980b9;
    font-weight: 600;
    transition: color 0.3s;
  }

  .links a:hover {
    color: #3498db;
  }

 
  p.create-account {
    margin-top: 15px;
    font-size: 0.9em;
    color: #555;
  }
</style>
</head>
<body>

<div class="auth-container">
    <h1>SAFEGUARD</h1>
    <h2>LOGIN TO YOUR ACCOUNT</h2>

    <?php if (isset($_GET['registered'])): ?>
        <p class="success">Registration successful! Please log in.</p>
    <?php endif; ?>

    <p class="error"><?= htmlspecialchars($message) ?></p>

    <form method="POST">
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="password" name="password" placeholder="Password" required>

        <button type="submit">LOGIN</button>
    </form>

    <p>Don't have an account? <a href="register.php">REGISTER</a> <a href="index.php">HOME</a></p>
</div>

</body>
</html>