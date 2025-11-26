<?php
require_once 'db_connect.php';

// Fetch database stats
$totalStudents = 0;
$openSessions = 0;
$todaysSessions = 0;

$conn = getConnection();
$today = date('Y-m-d');

if ($conn) {
    try {
        $totalStudents = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch()['c'];
        $openSessions = $conn->query("SELECT COUNT(*) AS c FROM attendance_sessions WHERE status='open'")->fetch()['c'];
        $todaysSessions = $conn->query("SELECT COUNT(*) AS c FROM attendance_sessions WHERE date='$today'")->fetch()['c'];
    } catch (PDOException $e) {
        // ignore, show zeroes
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Student Dashboard</title>
  <link rel="stylesheet" href="style.css" />
</head>

<body>

<header class="topbar">
  <div class="brand">
    <div class="logo">📚</div>
    <h1>Student Dashboard</h1>
  </div>

  <nav class="nav">
    <ul class="navbar">
      <li><a href="index.php">Home</a></li>
      <li><a href="students.php">Manage Students</a></li>
      <li><a href="sessions.php">Sessions / Attendance</a></li>
      <li><a href="reports.php">Reports</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>
</header>

<!-- HERO SECTION -->
<div class="hero-container">
  <div class="hero-glass">
    <h1 class="hero-title">Student Management System</h1>
    <p class="hero-subtitle">Manage students, attendance, sessions, and analytics in a unified platform.</p>

    <div class="hero-buttons">
      <a href="attendance.php" class="hero-btn">Take Attendance</a>
      <a href="students.php" class="hero-btn secondary">Manage Students</a>
    </div>
  </div>
</div>

<!-- STATISTICS GRID -->
<section class="stats-grid">

  <div class="stat-glass">
    <h3><?php echo $totalStudents; ?></h3>
    <p>Total Students</p>
  </div>

  <div class="stat-glass">
    <h3><?php echo $openSessions; ?></h3>
    <p>Open Sessions</p>
  </div>

  <div class="stat-glass">
    <h3><?php echo $todaysSessions; ?></h3>
    <p>Today's Sessions</p>
  </div>

</section>

<!-- FEATURE GRID -->
<section class="features-section">
    <h2>System Features</h2>

    <div class="feature-grid">

        <div class="feature-box">
            <h4>📋 Attendance</h4>
            <p>Record presence and participation across 6 sessions.</p>
        </div>

        <div class="feature-box">
            <h4>👥 Student Management</h4>
            <p>Add, edit, update, and delete students from the database.</p>
        </div>

        <div class="feature-box">
            <h4>📊 Reports & Analytics</h4>
            <p>Generate charts and view performance statistics.</p>
        </div>

        <div class="feature-box">
            <h4>🎓 Session Management</h4>
            <p>Create and close attendance sessions for each course.</p>
        </div>

    </div>
</section>

<footer class="footer">
  <p>Student Management System — © <?php echo date('Y'); ?></p>
</footer>

<script src="script.js"></script>

</body>
</html>
