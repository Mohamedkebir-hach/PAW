<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<header class="topbar">
    <div class="brand">
        <div class="logo">🚪</div>
        <h1>Student Dashboard</h1>
    </div>

    <nav class="nav">
        <ul class="navbar">
            <li><a href="index.php">Home</a></li>
            <li><a href="students.php">Manage Students</a></li>
            <li><a href="sessions.php">Sessions</a></li>
            <li><a href="reports.php">Reports</a></li>
            <li><a href="logout.php" class="active">Logout</a></li>
        </ul>
    </nav>
</header>

<div class="hero-container">
    <div class="hero-glass" style="max-width: 500px;">
        <div style="font-size: 64px; margin-bottom: 20px;">👋</div>
        <h2 style="color: white; margin: 0 0 15px 0;">Logged Out</h2>
        <p style="color: rgba(255,255,255,0.9); margin-bottom: 30px;">
            You have been successfully logged out.
        </p>
        <a href="index.php" class="hero-btn">
            ← Return to Dashboard
        </a>
    </div>
</div>

<footer class="footer">
    <p>Student Management System — © <?php echo date('Y'); ?></p>
</footer>

</body>
</html>