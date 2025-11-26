<?php
/**
 * Tutorial 3 - Exercise 5
 * create_session.php
 * Create a new attendance session
 */

require_once 'db_connect.php';

$message = '';
$error = '';
$sessionId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $course_id = trim($_POST['course_id'] ?? '');
    $group_id = trim($_POST['group_id'] ?? '');
    $professor_id = trim($_POST['professor_id'] ?? '');
    $date = trim($_POST['date'] ?? date('Y-m-d'));

    // Basic validation
    if (empty($course_id) || empty($group_id) || empty($professor_id)) {
        $error = 'All fields are required';
    } else {
        $conn = getConnection();
        if ($conn) {
            try {
                // Check if a session already exists for this course/group/date
                $stmt = $conn->prepare("SELECT id FROM attendance_sessions WHERE course_id = ? AND group_id = ? AND date = ?");
                $stmt->execute([$course_id, $group_id, $date]);

                if ($stmt->fetch()) {
                    $error = 'A session for this course, group, and date already exists';
                } else {
                    // Insert new session
                    $stmt = $conn->prepare("
                        INSERT INTO attendance_sessions (course_id, group_id, date, opened_by, status)
                        VALUES (?, ?, ?, ?, 'open')
                    ");
                    $stmt->execute([$course_id, $group_id, $date, $professor_id]);

                    $sessionId = $conn->lastInsertId();

                    // ⭐⭐⭐ THE FIX: REDIRECT DIRECTLY TO ATTENDANCE PAGE ⭐⭐⭐
                    header("Location: attendance.php?session_id=$sessionId");
                    exit;
                }

            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Database connection failed.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Attendance Session</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container {
            max-width: 600px; margin: 0 auto; background: white; padding: 30px;
            border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #A6615A; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        input[type="text"], input[type="date"] {
            width: 100%; padding: 10px; border: 1px solid #ddd;
            border-radius: 5px; font-size: 14px;
        }
        button {
            background: #A6615A; color: white; padding: 12px 24px;
            border: none; border-radius: 5px; cursor: pointer; font-size: 16px;
            width: 100%;
        }
        button:hover { background: #8e524c; }
        .message { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .error {
            background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
        }
        .links { margin-top: 20px; text-align: center; }
        .links a { color: #A6615A; text-decoration: none; margin: 0 10px; }
    </style>
</head>
<body>
<div class="container">
    <h1>Create Attendance Session</h1>
    <p style="color: #666; margin-bottom: 20px;">Tutorial 3 - Exercise 5</p>

    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="form-group">
            <label for="course_id">Course ID *</label>
            <input type="text" id="course_id" name="course_id"
                   value="<?php echo htmlspecialchars($_POST['course_id'] ?? 'AWP'); ?>"
                   placeholder="e.g., AWP" required>
        </div>

        <div class="form-group">
            <label for="group_id">Group ID *</label>
            <input type="text" id="group_id" name="group_id"
                   value="<?php echo htmlspecialchars($_POST['group_id'] ?? ''); ?>"
                   placeholder="e.g., G1, G2" required>
        </div>

        <div class="form-group">
            <label for="professor_id">Professor Name *</label>
            <input type="text" id="professor_id" name="professor_id"
                   value="<?php echo htmlspecialchars($_POST['professor_id'] ?? ''); ?>"
                   placeholder="e.g., Prof. Benali" required>
        </div>

        <div class="form-group">
            <label for="date">Date *</label>
            <input type="date" id="date" name="date"
                   value="<?php echo htmlspecialchars($_POST['date'] ?? date('Y-m-d')); ?>" required>
        </div>

        <button type="submit">Create Session</button>
    </form>

    <div class="links">
        <a href="view_sessions.php">View All Sessions</a> |
        <a href="list_students.php">View Students</a> |
        <a href="index.php">Dashboard</a>
    </div>
</div>
</body>
</html>
