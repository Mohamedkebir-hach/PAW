<?php
require_once 'db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$data = json_decode(file_get_contents('php://input'), true);

$sessionId    = isset($data['session_id']) ? (int)$data['session_id'] : 0;
$studentId    = isset($data['student_id']) ? (int)$data['student_id'] : 0;
$status       = isset($data['status']) && $data['status'] === 'present' ? 'present' : 'absent';
$participated = !empty($data['participated']) ? 1 : 0;

if (!$sessionId || !$studentId) {
    echo json_encode(['success' => false, 'error' => 'Missing session_id or student_id']);
    exit;
}

$conn = getConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit;
}

try {
    // Check if record exists
    $check = $conn->prepare("SELECT id FROM attendance_records WHERE session_id = ? AND student_id = ?");
    $check->execute([$sessionId, $studentId]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Update
        $upd = $conn->prepare("
            UPDATE attendance_records
            SET status = ?, participated = ?, recorded_at = NOW()
            WHERE session_id = ? AND student_id = ?
        ");
        $upd->execute([$status, $participated, $sessionId, $studentId]);
    } else {
        // Insert
        $ins = $conn->prepare("
            INSERT INTO attendance_records (session_id, student_id, status, participated, recorded_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $ins->execute([$sessionId, $studentId, $status, $participated]);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
