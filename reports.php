<?php
/**
 * AttendEase - Analytics & Reports Module
 */
require_once 'db_connect.php';

$conn = getConnection();

$stats = [
    'total_students' => 0,
    'total_sessions' => 0,
    'open_sessions'  => 0,
    'closed_sessions'=> 0,
    'groups'         => []
];

$error = '';

if ($conn) {
    try {
        $stats['total_students'] = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch()['c'];
        $stats['total_sessions'] = $conn->query("SELECT COUNT(*) AS c FROM attendance_sessions")->fetch()['c'];
        $stats['open_sessions']  = $conn->query("SELECT COUNT(*) AS c FROM attendance_sessions WHERE status='open'")->fetch()['c'];
        $stats['closed_sessions']= $conn->query("SELECT COUNT(*) AS c FROM attendance_sessions WHERE status='closed'")->fetch()['c'];

        $stmt = $conn->query("SELECT group_id, COUNT(*) AS count FROM students GROUP BY group_id ORDER BY group_id");
        $stats['groups'] = $stmt->fetchAll();

    } catch (PDOException $e) {
        $error = "Error loading statistics: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics & Reports - AttendEase</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<header class="topbar">
  <div class="container-nav">
    <div class="brand">
      <div class="logo">📊</div>
      <h1>AttendEase</h1>
    </div>

    <nav class="nav">
      <ul class="navbar">
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="students.php">Students</a></li>
        <li><a href="sessions.php">Sessions</a></li>
        <li><a href="reports.php" class="active">Analytics</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </nav>
  </div>
</header>

<main style="padding:40px 30px; max-width:1200px; margin:0 auto;">

    <h1 style="color:var(--dark-purple); margin-bottom:10px; font-size:32px;">Analytics & Reports</h1>
    <p style="color:var(--text-light); margin-bottom:30px;">System statistics and insights</p>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="stats-grid" style="gap:20px; margin-bottom:40px;">
        <div class="stat-glass">
            <h3><?= $stats['total_students'] ?></h3>
            <p>Total Students</p>
        </div>

        <div class="stat-glass">
            <h3><?= $stats['total_sessions'] ?></h3>
            <p>Total Sessions</p>
        </div>

        <div class="stat-glass">
            <h3 style="color:var(--success);"><?= $stats['open_sessions'] ?></h3>
            <p>Active Sessions</p>
        </div>

        <div class="stat-glass">
            <h3 style="color:var(--text-light);"><?= $stats['closed_sessions'] ?></h3>
            <p>Completed</p>
        </div>
    </div>

    <div class="card">
        <h2 style="color:var(--dark-purple);">Student Distribution by Group</h2>

        <?php if (empty($stats['groups'])): ?>
            <div class="empty">
                <p>📊 No data available yet.</p>
                <p>Add students to see group distribution.</p>
            </div>
        <?php else: ?>
            <div style="max-width:600px; margin:30px auto;">
                <canvas id="groupChart"></canvas>
            </div>

            <table class="table" style="max-width:500px; margin:30px auto;">
                <thead>
                    <tr>
                        <th>Group / Section</th>
                        <th>Number of Students</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total = array_sum(array_column($stats['groups'], 'count'));
                    foreach ($stats['groups'] as $g): 
                        $percentage = $total > 0 ? round(($g['count'] / $total) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($g['group_id']) ?></strong></td>
                            <td><?= $g['count'] ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="flex:1; background:var(--bg-light); height:8px; border-radius:4px; overflow:hidden;">
                                        <div style="background:var(--primary-purple); height:100%; width:<?= $percentage ?>%;"></div>
                                    </div>
                                    <span><?= $percentage ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</main>

<footer class="footer">
  <p>AttendEase Academic Management System © <?= date('Y') ?></p>
</footer>

<script>
<?php if (!empty($stats['groups'])): ?>
const ctx = document.getElementById('groupChart').getContext('2d');

const colors = [
    '#6D28D9', '#8B5CF6', '#A78BFA', '#C4B5FD', '#DDD6FE', '#EDE9FE'
];

new Chart(ctx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($stats['groups'], 'group_id')) ?>,
        datasets: [{
            label: 'Students per Group',
            data: <?= json_encode(array_column($stats['groups'], 'count')) ?>,
            backgroundColor: colors,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 15,
                    font: {
                        size: 14,
                        family: 'Inter'
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = ((context.parsed / total) * 100).toFixed(1);
                        return context.label + ': ' + context.parsed + ' students (' + percentage + '%)';
                    }
                }
            }
        }
    }
});
<?php endif; ?>
</script>

</body>
</html>