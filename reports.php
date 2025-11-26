<?php
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
    <title>Reports & Statistics</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<header class="topbar">
  <div class="brand">
    <div class="logo">📊</div>
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

<main style="padding:20px; max-width:1200px; margin:0 auto;">

    <h1 style="color:#A6615A; margin-bottom:10px;">Reports & Statistics</h1>
    <p style="color:#666; margin-bottom:25px;">Overview of system statistics</p>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="stats-grid" style="gap:20px; margin-bottom:30px;">
        <div class="card small">
            <h3><?= $stats['total_students'] ?></h3>
            <p>Total Students</p>
        </div>

        <div class="card small">
            <h3><?= $stats['total_sessions'] ?></h3>
            <p>Total Sessions</p>
        </div>

        <div class="card small">
            <h3 style="color:#10B981;"><?= $stats['open_sessions'] ?></h3>
            <p>Open Sessions</p>
        </div>

        <div class="card small">
            <h3 style="color:#6c757d;"><?= $stats['closed_sessions'] ?></h3>
            <p>Closed Sessions</p>
        </div>
    </div>

    <div class="card">
        <h2 style="color:#A6615A;">Students by Group</h2>

        <?php if (empty($stats['groups'])): ?>
            <p style="text-align:center; padding:40px; color:#666;">No data available</p>
        <?php else: ?>
            <div style="max-width:600px; margin:20px auto;">
                <canvas id="groupChart"></canvas>
            </div>

            <table class="table" style="max-width:400px; margin:20px auto;">
                <thead>
                    <tr>
                        <th>Group</th>
                        <th>Number of Students</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats['groups'] as $g): ?>
                        <tr>
                            <td><?= htmlspecialchars($g['group_id']) ?></td>
                            <td><?= $g['count'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</main>

<script>
<?php if (!empty($stats['groups'])): ?>
const ctx = document.getElementById('groupChart').getContext('2d');

new Chart(ctx, {
    type: 'pie',
    data: {
        labels: <?= json_encode(array_column($stats['groups'], 'group_id')) ?>,
        datasets: [{
            label: 'Students per Group',
            data: <?= json_encode(array_column($stats['groups'], 'count')) ?>,
            backgroundColor: [
                '#A6615A', '#10B981', '#06B6D4', '#F59E0B', '#EF4444', '#6366F1'
            ]
        }]
    },
    options: {
        responsive:true,
        plugins:{ legend:{ position:'bottom' } }
    }
});
<?php endif; ?>
</script>

</body>
</html>
