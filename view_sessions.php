<?php
require_once 'db_connect.php';

$conn = getConnection();
$sessions = [];
$error = '';

if ($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM attendance_sessions ORDER BY date DESC, id DESC");
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error fetching sessions: " . $e->getMessage();
    }
} else {
    $error = "Database connection failed.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Sessions</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }

        h1 { color: #A6615A; margin-bottom: 20px; }

        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #A6615A; color: white; font-weight: bold; }
        tr:nth-child(even) { background: #f9f9f9; }

        .status-badge { padding: 4px 12px; border-radius: 12px; color: white; font-weight: bold; font-size: 13px; }
        .status-open { background: #10B981; }
        .status-closed { background: #6c757d; }

        .btn { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 14px; display: inline-block; }
        .btn-view { background: #06B6D4; color: white; }
        .btn-close { background: #A6615A; color: white; }
        .btn:hover { opacity: .8; }

        .btn-disabled { background: #ccc; color: #777; cursor: not-allowed; }

        .header-actions { display: flex; justify-content: space-between; align-items: center; }
        .btn-primary { background: #A6615A; color: white; padding: 10px 20px; }
        .links a { color: #A6615A; text-decoration: none; margin-right: 15px; }
    </style>
</head>
<body>

<div class="container">

    <div class="header-actions">
        <h1>Attendance Sessions</h1>
        <a href="create_session.php" class="btn btn-primary">+ Create New Session</a>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (empty($sessions)): ?>
        <p>No sessions found. Create one first.</p>
    <?php else: ?>

        <table>
            <thead>
            <tr>
                <th>ID</th>
                <th>Course</th>
                <th>Group</th>
                <th>Date</th>
                <th>Opened By</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($sessions as $session): ?>
                <tr>
                    <td><?= htmlspecialchars($session['id']); ?></td>
                    <td><?= htmlspecialchars($session['course_id']); ?></td>
                    <td><?= htmlspecialchars($session['group_id']); ?></td>
                    <td><?= htmlspecialchars($session['date']); ?></td>
                    <td><?= htmlspecialchars($session['opened_by']); ?></td>

                    <td>
                        <span class="status-badge status-<?= $session['status']; ?>">
                            <?= ucfirst($session['status']); ?>
                        </span>
                    </td>

                    <td>
                        <?php if ($session['status'] === 'open'): ?>
                            <a href="attendance.php?session_id=<?= $session['id'] ?>" class="btn btn-view">
                                Take Attendance
                            </a>
                            <a href="close_session.php?id=<?= $session['id'] ?>" class="btn btn-close">
                                Close
                            </a>
                        <?php else: ?>
                            <a href="attendance.php?session_id=<?= $session['id'] ?>" class="btn btn-view">
                                View
                            </a>
                            <span class="btn btn-disabled">Closed</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>

    <div class="links">
        <a href="list_students.php">View Students</a> |
        <a href="index.php">Back to Dashboard</a>
    </div>

</div>

</body>
</html>
