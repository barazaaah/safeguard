<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <title>SAFEGUARD - Personal Safety System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<nav>
    <h2>SAFEGUARD</h2>

    <div>
    
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="user_dashboard.php">DASHBOARD</a>
            <a href="logout.php">LOGOUT</a>
        <?php else: ?>
            <a href="login.php">LOGIN</a>
            <a href="user_register.php">REGISTER</a>
        <?php endif; ?>
    </div>
</nav>

<section class="dashboard" style="text-align:center; padding:65px 10px;">

    <h1 style="font-size:30px;">
        YOUR SAFETY,OUR PRIORITY.
    </h1>

    <p style="font-size:18px; margin:20px 0;">
        SafeGuard is an emergency SOS and personal safety
        management system designed to help users record
        emergencies, share their location and manage
        trusted emergency contacts.
    </p>

    <?php if (isset($_SESSION['user_id'])): ?>
        <a href="user_dashboard.php" class="sos-button">
            GO TO DASHBOARD
        </a>
    <?php else: ?>
        <a href="user_register.php" class="sos-button">
            GET STARTED
        </a>

        <p style="margin-top:20px;">
            Already registered?
            <a href="login.php">LOGIN HERE</a>
        </p>
    <?php endif; ?>

</section>


<section class="hero">

    <h1>YOUR SAFETY,OUR PRIORITY.</h1>

    <p>
        SafeGuard is an emergency SOS and personal safety
        management system designed to help users report
        emergencies and manage their emergency contacts.
    </p>

    <a href="register.php" class="btn btn-danger">
        GET STARTED
    </a>

    <a href="login.php" class="btn btn-light">
        USER LOGIN
    </a>

</section>

<section class="container">

    <h2>OUR SAFETY SERVICES</h2>

    <p>Explore the safety management features available on SafeGuard.</p>

    <div class="features-grid">

        <div class="feature-card">
            <div class="operation-icon">🚨</div>
            <h3>EMERGENCY SOS</h3>
            <p>Submit an emergency alert with incident details and available location information.</p>
        </div>

        <div class="feature-card">
            <div class="operation-icon">📍</div>
            <h3>LOCATION SHARING</h3>
            <p>Include your GPS coordinates when available with an emergency report.</p>
        </div>

        <div class="feature-card">
            <div class="operation-icon">👥</div>
            <h3>EMERGENCY CONTACTS</h3>
            <p>Manage the contacts you want associated with your emergency alerts.</p>
        </div>

        <div class="feature-card">
            <div class="operation-icon">📊</div>
            <h3>ALERT HISTORY</h3>
            <p>Review your previously submitted emergency reports.</p>
        </div>

    </div>

</section>


<footer style="text-align:center; padding:25px;">
    <p>&copy; <?php echo date("Y"); ?>
        SAFEGUARD PERSONAL SAFETY MANAGEMENT SYSTEM. All rights reserved.
    </p>
</footer>

</body>
</html>
