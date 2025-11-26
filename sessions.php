<?php
/**
 * Unified Session Management (Tutorial 3 - Exercise 5)
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
    <title>Session Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- TOP HEADER (same as index & students) -->
<header class="topbar">
  <div class="brand">
    <div class="logo">🎓</div>
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

<main style="padding:20px; max-width:1400px; margin:0 auto;">

    <!-- PAGE TITLE -->
    <h1 style="color:#A6615A; margin-bottom:10px;">Session Management</h1>
    <p style="color:#666; margin-bottom:25px;">Create, view, and close attendance sessions (Tutorial 3)</p>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="message success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:20px;">

        <!-- Create Session Form -->
        <div class="card">
            <h2>Create New Session</h2>

            <form method="POST" action="" class="student-form">
                <input type="hidden" name="create" value="1">

                <label for="course_id">Course ID *</label>
                <input type="text" id="course_id" name="course_id" value="AWP" required>

                <label for="group_id">Group ID *</label>
                <input type="text" id="group_id" name="group_id" required>

                <label for="professor">Professor Name *</label>
                <input type="text" id="professor" name="professor" required>

                <label for="date">Date *</label>
                <input type="date" id="date" name="date" value="<?= date('Y-m-d') ?>" required>

                <button type="submit">Create Session</button>
            </form>
        </div>

        <!-- Sessions List -->
        <div class="card">
            <h2>All Sessions (<?= count($sessions) ?>)</h2>

            <?php if (empty($sessions)): ?>
                <div class="empty"><p>No sessions yet. Create a session to begin.</p></div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Course</th>
                            <th>Group</th>
                            <th>Date</th>
                            <th>Opened By</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($sessions as $s): ?>
                        <tr>
                            <td><?= $s['id'] ?></td>
                            <td><?= htmlspecialchars($s['course_id']) ?></td>
                            <td><?= htmlspecialchars($s['group_id']) ?></td>
                            <td><?= htmlspecialchars($s['date']) ?></td>
                            <td><?= htmlspecialchars($s['opened_by']) ?></td>

                            <td>
                                <span class="status-badge status-<?= $s['status'] ?>">
                                    <?= ucfirst($s['status']) ?>
                                </span>
                            </td>

                            <td><?= date('Y-m-d H:i', strtotime($s['created_at'])) ?></td>

                            <td style="display:flex; gap:8px;">

                                <!-- Take Attendance -->
                                <a href="attendance.php?session_id=<?= $s['id'] ?>"
                                   class="btn"
                                   style="padding:6px 10px;">
                                   Take Attendance
                                </a>

                                <!-- Close Session -->
                                <?php if ($s['status'] === 'open'): ?>
                                    <a href="sessions.php?close=<?= $s['id'] ?>"
                                       class="btn warn"
                                       onclick="return confirm('Close this session?');"
                                       style="padding:6px 10px;">
                                       Close
                                    </a>
                                <?php else: ?>
                                    <span style="color:#999;">Closed</span>
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

</body>
</html>
