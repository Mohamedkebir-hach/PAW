<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - AttendEase</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<header class="topbar">
  <div class="container-nav">
    <div class="brand">
        <div class="logo">❤️</div>
        <h1>AttendEase</h1>
    </div>
</div>
>

    <nav class="nav">
      <ul class="navbar">
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="students.php">Students</a></li>
        <li><a href="sessions.php">Sessions</a></li>
        <li><a href="reports.php">Analytics</a></li>
        <li><a href="logout.php" class="active">Logout</a></li>
      </ul>
    </nav>
  </div>
</header>

<div class="hero-container" style="min-height:70vh;">
    <div class="hero-glass" style="max-width: 600px;">
        <h2 style="color: white; margin: 0 0 18px 0; font-size:36px;">Hope you enjoyed our app ❤️</h2>
        <p style="color: rgba(255,255,255,0.95); margin-bottom: 35px; font-size:18px;">
            Thank you for using AttendEase. Your session has been terminated securely.
        </p>
        <a href="index.php" class="hero-btn" style="display:inline-block;">
            ← Return to Dashboard
        </a>
    </div>
</div>

<footer class="footer">
    <p>AttendEase Management System © <?php echo date('Y'); ?></p>
</footer>

</body>
</html>