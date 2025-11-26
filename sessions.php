<?php
/**
 * AttendEase - Session Management Module
 */
require_once 'db_connect.php';

$message = '';
$error = '';
$conn = getConnection();

// Handle Create Session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $course_id = trim($_POST['course_id'] ?? '');
    $group_id = trim($_POST['group_id'] ?? '');
    $professor = trim($_POST['professor'] ?? '');
    $date = trim($_POST['date'] ?? date('Y-m-d'));

    if (empty($course_id) || empty($group_id) || empty($professor)) {
        $error = 'All fields are required';
    } elseif ($conn) {
        try {
            $stmt = $conn->prepare("SELECT id FROM attendance_sessions WHERE course_id = ? AND group_id = ? AND date = ?");
            $stmt->execute([$course_id, $group_id, $date]);
            if ($stmt->fetch()) {
                $error = 'A session already exists for this course/group/date';
            } else {
                $stmt = $conn->prepare("INSERT INTO attendance_sessions (course_id, group_id, date, opened_by, status) 
                                        VALUES (?, ?, ?, ?, 'open')");
                $stmt->execute([$course_id, $group_id, $date, $professor]);
                $message = "✅ Session created successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Close Session
if (isset($_GET['close'])) {
    $id = $_GET['close'];
    if ($conn) {
        try {
            $stmt = $conn->prepare("UPDATE attendance_sessions SET status='closed', closed_at=NOW() WHERE id=?");
            $stmt->execute([$id]);
            $message = "✅ Session closed successfully!";
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch all sessions
$sessions = [];
if ($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM attendance_sessions ORDER BY date DESC, id DESC");
        $sessions = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Management - AttendEase</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<header class="topbar">
  <div class="container-nav">
    <div class="brand">
      <div class="logo">🎓</div>
      <h1>AttendEase</h1>
    </div>

    <nav class="nav">
      <ul class="navbar">
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="students.php">Students</a></li>
        <li><a href="sessions.php" class="active">Sessions</a></li>
        <li><a href="reports.php">Analytics</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </nav>
  </div>
</header>

<main style="padding:40px 30px; max-width:1400px; margin:0 auto;">

    <h1 style="color:var(--dark-purple); margin-bottom:10px; font-size:32px;">Session Management</h1>
    <p style="color:var(--text-light); margin-bottom:30px;">Create and manage attendance sessions</p>

    <?php if ($message): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:25px;">

        <!-- Create Session Form -->
        <div class="card">
            <h2>Create New Session</h2>

            <form method="POST" action="" class="student-form">
                <input type="hidden" name="create" value="1">

                <label for="course_id">Course Code *</label>
                <input type="text" id="course_id" name="course_id" value="WEB301" placeholder="e.g., WEB301" required>

                <label for="group_id">Group / Section *</label>
                <input type="text" id="group_id" name="group_id" placeholder="e.g., G1, G2" required>

                <label for="professor">Instructor Name *</label>
                <input type="text" id="professor" name="professor" placeholder="Dr. Ahmed Mansouri" required>

                <label for="date">Session Date *</label>
                <input type="date" id="date" name="date" value="<?= date('Y-m-d') ?>" required>

                <button type="submit">➕ Create Session</button>
            </form>
        </div>

        <!-- Sessions List -->
        <div class="card">
            <h2>All Sessions (<?= count($sessions) ?>)</h2>

            <?php if (empty($sessions)): ?>
                <div class="empty">
                    <p>📅 No sessions created yet.</p>
                    <p>Use the form to create your first attendance session!</p>
                </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Course</th>
                            <th>Group</th>
                            <th>Date</th>
                            <th>Instructor</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($sessions as $s): ?>
                        <tr>
                            <td><strong>#<?= $s['id'] ?></strong></td>
                            <td><?= htmlspecialchars($s['course_id']) ?></td>
                            <td><span style="background:var(--bg-light); padding:4px 12px; border-radius:12px;"><?= htmlspecialchars($s['group_id']) ?></span></td>
                            <td><?= date('M d, Y', strtotime($s['date'])) ?></td>
                            <td><?= htmlspecialchars($s['opened_by']) ?></td>

                            <td>
                                <span class="status-badge status-<?= $s['status'] ?>">
                                    <?= $s['status'] === 'open' ? '🟢 Open' : '🔴 Closed' ?>
                                </span>
                            </td>

                            <td><?= date('M d, H:i', strtotime($s['created_at'])) ?></td>

                            <td style="display:flex; gap:8px; flex-wrap:wrap;">
                                <a href="attendance.php?session_id=<?= $s['id'] ?>"
                                   class="btn btn-edit"
                                   style="padding:8px 14px; font-size:13px;">
                                   📋 Take Attendance
                                </a>

                                <?php if ($s['status'] === 'open'): ?>
                                    <a href="sessions.php?close=<?= $s['id'] ?>"
                                       class="btn warn"
                                       onclick="return confirm('Close this session? This cannot be undone.');"
                                       style="padding:8px 14px; font-size:13px;">
                                       🔒 Close
                                    </a>
                                <?php else: ?>
                                    <span style="color:var(--text-light); font-size:13px;">Closed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

    </div>

</main>

<footer class="footer">
  <p>AttendEase Academic Management System © <?= date('Y') ?></p>
</footer>

</body>
</html>