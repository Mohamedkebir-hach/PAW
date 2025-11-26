<?php
require_once 'db_connect.php';

// Fetch dashboard statistics
$totalStudents = 0;
$activeSessions = 0;
$todaysSessions = 0;
$avgAttendance = 0;

$conn = getConnection();
$today = date('Y-m-d');

if ($conn) {
    try {
        $totalStudents = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch()['c'];
        $activeSessions = $conn->query("SELECT COUNT(*) AS c FROM attendance_sessions WHERE status='open'")->fetch()['c'];
        $todaysSessions = $conn->query("SELECT COUNT(*) AS c FROM attendance_sessions WHERE date='$today'")->fetch()['c'];
        
        // Calculate average attendance percentage
        $result = $conn->query("
            SELECT 
                COALESCE(AVG(CASE WHEN status='present' THEN 100 ELSE 0 END), 0) as avg_pct
            FROM attendance_records
        ")->fetch();
        $avgAttendance = round($result['avg_pct'] ?? 0);
    } catch (PDOException $e) {
        // Silent fail, show zeros
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>AttendEase - Academic Attendance Management</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>

<!-- NAVIGATION HEADER -->
<header class="topbar">
  <div class="container-nav">
    <div class="brand">
      <div class="logo">🎓</div>
      <h1>AttendEase</h1>
    </div>

    <nav class="nav">
      <ul class="navbar">
        <li><a href="index.php" class="active">Dashboard</a></li>
        <li><a href="students.php">Students</a></li>
        <li><a href="sessions.php">Sessions</a></li>
        <li><a href="reports.php">Analytics</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </nav>
  </div>
</header>

<!-- HERO BANNER -->
<div class="hero-container">
  <div class="hero-glass">
    <h1 class="hero-title">Welcome to AttendEase</h1>
    <p class="hero-subtitle">
      Streamline your academic attendance tracking with our comprehensive management system. 
      Monitor student presence, track participation, and generate insightful reports—all in one place.
    </p>

    <div class="hero-buttons">
      <a href="attendance.php" class="hero-btn">📋 Record Attendance</a>
      <a href="students.php" class="hero-btn secondary">👥 Manage Students</a>
    </div>
  </div>
</div>

<!-- LIVE STATISTICS -->
<section class="stats-grid">
  <div class="stat-glass">
    <h3><?php echo $totalStudents; ?></h3>
    <p>Enrolled Students</p>
  </div>

  <div class="stat-glass">
    <h3><?php echo $activeSessions; ?></h3>
    <p>Active Sessions</p>
  </div>

  <div class="stat-glass">
    <h3><?php echo $todaysSessions; ?></h3>
    <p>Today's Classes</p>
  </div>

  <div class="stat-glass">
    <h3><?php echo $avgAttendance; ?>%</h3>
    <p>Avg Attendance</p>
  </div>
</section>

<!-- SYSTEM CAPABILITIES -->
<section class="features-section">
    <h2>Platform Capabilities</h2>

    <div class="feature-grid">
        <div class="feature-box">
            <h4>📊 Real-Time Tracking</h4>
            <p>Monitor attendance and participation across multiple sessions with instant updates and notifications.</p>
        </div>

        <div class="feature-box">
            <h4>👨‍🎓 Student Registry</h4>
            <p>Comprehensive student database with profile management, group assignments, and enrollment tracking.</p>
        </div>

        <div class="feature-box">
            <h4>📈 Analytics Dashboard</h4>
            <p>Visual insights through charts and reports to identify trends and improve academic outcomes.</p>
        </div>

        <div class="feature-box">
            <h4>🔐 Session Control</h4>
            <p>Create, manage, and close attendance sessions with full audit trails and historical data.</p>
        </div>

        <div class="feature-box">
            <h4>⚡ Quick Actions</h4>
            <p>Streamlined interface for rapid data entry and batch operations to save valuable time.</p>
        </div>

        <div class="feature-box">
            <h4>📱 Responsive Design</h4>
            <p>Access the system from any device with a modern, mobile-friendly interface.</p>
        </div>
    </div>
</section>

<!-- QUICK ACCESS PANEL -->
<section class="features-section">
    <h2>Quick Access</h2>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        
        <div class="card" style="text-align: center; padding: 40px;">
            <div style="font-size: 48px; margin-bottom: 15px;">📝</div>
            <h3 style="color: var(--dark-purple); margin-bottom: 12px;">New Session</h3>
            <p style="color: var(--text-light); margin-bottom: 20px;">Create a new attendance session</p>
            <a href="sessions.php" class="btn">Get Started</a>
        </div>

        <div class="card" style="text-align: center; padding: 40px;">
            <div style="font-size: 48px; margin-bottom: 15px;">👤</div>
            <h3 style="color: var(--dark-purple); margin-bottom: 12px;">Add Student</h3>
            <p style="color: var(--text-light); margin-bottom: 20px;">Register a new student</p>
            <a href="students.php" class="btn">Add Now</a>
        </div>

        <div class="card" style="text-align: center; padding: 40px;">
            <div style="font-size: 48px; margin-bottom: 15px;">📊</div>
            <h3 style="color: var(--dark-purple); margin-bottom: 12px;">View Reports</h3>
            <p style="color: var(--text-light); margin-bottom: 20px;">Analyze attendance data</p>
            <a href="reports.php" class="btn">View Analytics</a>
        </div>

    </div>
</section>

<footer class="footer">
  <p>AttendEase Academic Management System © <?php echo date('Y'); ?> — Empowering Education Through Technology</p>
</footer>

</body>
</html>