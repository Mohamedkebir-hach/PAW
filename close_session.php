<?php
/**
 * Tutorial 3 - Exercise 5
 * close_session.php
 * Close an attendance session
 */

require_once 'db_connect.php';

$message = '';
$error = '';
$session = null;

$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    header('Location: view_sessions.php');
    exit;
}

$conn = getConnection();

// Fetch session data
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM attendance_sessions WHERE id = ?");
        $stmt->execute([$id]);
        $session = $stmt->fetch();
        
        if (!$session) {
            $error = 'Session not found';
        } elseif ($session['status'] === 'closed') {
            $message = 'This session is already closed';
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle closing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $session && $session['status'] === 'open') {
    try {
        $stmt = $conn->prepare("UPDATE attendance_sessions SET status = 'closed', closed_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        
        $message = "✅ Session closed successfully!";
        
        // Refresh session data
        $stmt = $conn->prepare("SELECT * FROM attendance_sessions WHERE id = ?");
        $stmt->execute([$id]);
        $session = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Error closing session: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Close Session</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #A6615A; margin-bottom: 20px; }
        .message { padding: 12px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .session-info { background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .session-info p { margin: 8px 0; }
        .session-info strong { color: #A6615A; }
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 14px; font-weight: 600; }
        .status-open { background: #10B981; color: white; }
        .status-closed { background: #6c757d; color: white; }
        .actions { display: flex; gap: 10px; margin-top: 20px; }
        .btn { padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; text-align: center; flex: 1; }
        .btn-primary { background: #A6615A; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .btn:hover { opacity: 0.8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Close Session</h1>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'already') !== false ? 'warning' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <a href="view_sessions.php" class="btn btn-secondary">Back to Sessions</a>
        <?php elseif ($session): ?>
            <div class="session-info">
                <p><strong>Session ID:</strong> <?php echo htmlspecialchars($session['id']); ?></p>
                <p><strong>Course:</strong> <?php echo htmlspecialchars($session['course_id']); ?></p>
                <p><strong>Group:</strong> <?php echo htmlspecialchars($session['group_id']); ?></p>
                <p><strong>Date:</strong> <?php echo htmlspecialchars($session['date']); ?></p>
                <p><strong>Opened by:</strong> <?php echo htmlspecialchars($session['opened_by']); ?></p>
                <p><strong>Status:</strong> 
                    <span class="status-badge status-<?php echo $session['status']; ?>">
                        <?php echo ucfirst($session['status']); ?>
                    </span>
                </p>
                <?php if ($session['closed_at']): ?>
                    <p><strong>Closed at:</strong> <?php echo htmlspecialchars($session['closed_at']); ?></p>
                <?php endif; ?>
            </div>
            
            <?php if ($session['status'] === 'open'): ?>
                <p style="margin: 20px 0;">Are you sure you want to close this session? This action cannot be undone.</p>
                <form method="POST" action="">
                    <input type="hidden" name="id" value="<?php echo $session['id']; ?>">
                    <div class="actions">
                        <button type="submit" class="btn btn-primary">Close Session</button>
                        <a href="view_sessions.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php else: ?>
                <a href="view_sessions.php" class="btn btn-secondary">Back to Sessions</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>