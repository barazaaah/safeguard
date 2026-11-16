<?php
require_once "../config/db.php";
session_start();

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];

    if ($fullname === "" || $email === "" || $phone === "" || strlen($password) < 8) {
        $message = "Complete all fields. Password must have at least 8 characters.";
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO users (fullname, email, phone, password)
                 VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([$fullname, $email, $phone, $hashedPassword]);

            header("Location: login.php?registered=1");
            exit;

        } catch (PDOException $e) {
            $message = "Registration failed. This email may already be registered.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register | SafeGuard</title>
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
    <h2>CREATE ACCOUNT</h2>

    <p class="error"><?= htmlspecialchars($message) ?></p>

    <form method="POST">
        <input type="text" name="fullname" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email Address" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <input type="password" name="password" placeholder="Password (minimum 8 characters)" minlength="8" required>

        <button type="submit">REGISTER</button>
    </form>

    <p>Already registered? <a href="login.php">LOGIN</a></p>
</div>

</body>
</html>