<?php
/**
 * AttendEase - Attendance Recording Module
 */

require_once 'db_connect.php';

$conn = getConnection();
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

$students = [];
$attendanceMap = [];
$sessionRow = null;
$sessionError = '';

// Validate session
if (!$sessionId) {
    $sessionError = 'No session selected. Please choose a session from Sessions page.';
} elseif ($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM attendance_sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $sessionRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$sessionRow) {
            $sessionError = 'Selected session does not exist.';
        }
    } catch (PDOException $e) {
        $sessionError = 'Error loading session: ' . $e->getMessage();
    }
} else {
    $sessionError = 'Database connection failed.';
}

// Load students and attendance records if session ok
if (!$sessionError && $conn) {
    try {
        $stmt = $conn->query("SELECT id, fullname, matricule, group_id FROM students ORDER BY fullname ASC");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("SELECT student_id, status, participated FROM attendance_records WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $attendanceMap[$r['student_id']] = $r;
        }
    } catch (PDOException $e) {
        $sessionError = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Record Attendance<?php if ($sessionRow) echo ' — ' . htmlspecialchars($sessionRow['course_id']); ?> - AttendEase</title>
    <link rel="stylesheet" href="style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .page-container { padding: 40px 30px; max-width: 1400px; margin: 0 auto; }
        .page-title { display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; gap:15px; flex-wrap:wrap; }
        .page-title h2 { margin:0; color:var(--dark-purple); font-size:32px; }
        .session-meta { color:var(--text-light); font-size:15px; margin-top:8px; }
        .session-meta strong { color:var(--dark-purple); }
        .attendance-table th, .attendance-table td { padding:10px 12px; font-size:14px; }
        .controls-bar { margin-top:20px; display:flex; gap:12px; flex-wrap:wrap; }
        .btn-sm { padding:10px 18px; border-radius:8px; border:none; background:var(--primary-purple); color:#fff; cursor:pointer; font-weight:600; }
        .btn-sm.secondary { background:var(--text-light); }
        .btn-sm:hover { opacity:0.9; transform:translateY(-2px); }
        .save-indicator { font-size:14px; color:var(--success); margin-left:10px; font-weight:600; }
        .message.error { padding:15px 20px; background:#FEE2E2; color:#991B1B; border-radius:10px; margin-bottom:20px; border-left:4px solid #EF4444; }
        .report-panel { background:var(--bg-light); padding:25px; border-radius:12px; margin-top:25px; }
        .report-panel h3 { color:var(--dark-purple); margin-bottom:15px; }
    </style>
</head>
<body>

<header class="topbar">
  <div class="container-nav">
    <div class="brand">
      <div class="logo">📋</div>
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

<main class="page-container">

    <?php if ($sessionError): ?>
        <div class="message error">
            <?= htmlspecialchars($sessionError) ?><br>
            <a href="sessions.php" style="color:#991B1B; text-decoration:underline; font-weight:600;">← Back to Sessions</a>
        </div>
    <?php else: ?>

    <div class="page-title">
        <div>
            <h2>📋 Attendance Recording</h2>
            <div class="session-meta">
                Course: <strong><?= htmlspecialchars($sessionRow['course_id']) ?></strong> ·
                Group: <strong><?= htmlspecialchars($sessionRow['group_id']) ?></strong> ·
                Date: <strong><?= date('M d, Y', strtotime($sessionRow['date'])) ?></strong> ·
                Session: <strong>#<?= (int)$sessionRow['id'] ?></strong>
            </div>
        </div>

        <div style="display:flex;align-items:center; gap:10px;">
            <a href="sessions.php" class="btn-sm secondary" style="text-decoration:none;">← Back</a>
            <button id="saveAll" class="btn-sm">💾 Save All</button>
            <div id="saveStatus" class="save-indicator" aria-live="polite"></div>
        </div>
    </div>

    <div class="card" style="overflow-x:auto;">
        <table class="attendance-table table">
            <thead>
                <tr>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <?php for ($i=1;$i<=6;$i++): ?>
                        <th>S<?= $i ?> P</th>
                        <th>S<?= $i ?> Pa</th>
                    <?php endfor; ?>
                    <th>Absences</th>
                    <th>Participation</th>
                    <th>Status Message</th>
                </tr>
            </thead>
            <tbody id="attendanceBody">
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="18" style="text-align:center; padding:50px; color:var(--text-light);">
                            No students in database. <a href="students.php" style="color:var(--primary-purple);">Add students first</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $student): 
                        $nameParts = explode(' ', $student['fullname'], 2);
                        $last = $nameParts[0] ?? '';
                        $first = $nameParts[1] ?? '';
                        $rec = $attendanceMap[$student['id']] ?? null;
                        $presentFromDb = $rec && $rec['status'] === 'present';
                        $partFromDb = $rec && (int)$rec['participated'] === 1;
                    ?>
                    <tr data-student-id="<?= (int)$student['id'] ?>" data-matricule="<?= htmlspecialchars($student['matricule']) ?>">
                        <td><strong><?= htmlspecialchars($last) ?></strong></td>
                        <td><?= htmlspecialchars($first) ?></td>

                        <?php for ($j=0;$j<6;$j++): ?>
                            <td>
                                <input type="checkbox" class="present-check" data-session="<?= $j ?>"
                                    <?= $presentFromDb ? 'checked' : '' ?>>
                            </td>
                            <td>
                                <input type="checkbox" class="participated-check" data-session="<?= $j ?>"
                                    <?= $partFromDb ? 'checked' : '' ?>>
                            </td>
                        <?php endfor; ?>

                        <td class="absences-count">0 Abs</td>
                        <td class="participation-count">0 Par</td>
                        <td class="message-cell"></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="controls-bar">
        <button id="showReport" class="btn-sm">📊 Show Report</button>
        <button id="highlightExcellent" class="btn-sm">⭐ Highlight Excellent</button>
        <button id="resetColors" class="btn-sm secondary">🔄 Reset Colors</button>
    </div>

    <div id="reportSection" class="report-panel" style="display:none;">
        <h3>Attendance Summary</h3>
        <p style="color:var(--text-light); font-size:15px;">
            Total students: <strong id="reportTotal">0</strong> ·
            Present (≥1): <strong id="reportPresent">0</strong> ·
            Participated (≥1): <strong id="reportParticipated">0</strong>
        </p>
        <div style="max-width:700px; margin-top:20px;"><canvas id="reportChart" height="120"></canvas></div>
    </div>

    <?php endif; ?>

</main>

<footer class="footer">
  <p>AttendEase Academic Management System © <?= date('Y') ?></p>
</footer>

<script>
(function(){
    const SESSION_ID = <?= $sessionId && !$sessionError ? (int)$sessionId : 'null' ?>;
    const STORAGE_KEY = 'attendance_data_session_' + (SESSION_ID || 'local');

    function loadAttendanceLocal() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {}; }
        catch(e){ return {}; }
    }
    function saveAttendanceLocal(data) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    }

    function updateRow(row) {
        const presentChecks = row.querySelectorAll('.present-check');
        const partChecks = row.querySelectorAll('.participated-check');
        let abs = 0, par = 0;
        presentChecks.forEach(ch => { if (!ch.checked) abs++; });
        partChecks.forEach(ch => { if (ch.checked) par++; });

        row.querySelector('.absences-count').textContent = abs + ' Abs';
        row.querySelector('.participation-count').textContent = par + ' Par';

        let message = '';
        if (abs >= 5) message = 'Excluded — too many absences';
        else if (abs >= 3) message = 'Warning — attendance low';
        else if (par >= 4) message = 'Excellent participation ⭐';
        else message = 'Good attendance';

        row.querySelector('.message-cell').textContent = message;

        row.classList.remove('row-green','row-yellow','row-red');
        if (abs >= 5) row.classList.add('row-red');
        else if (abs >= 3) row.classList.add('row-yellow');
        else row.classList.add('row-green');
    }

    function rowToPayload(row) {
        const studentId = row.dataset.studentId;
        const matricule = row.dataset.matricule;
        const presentChecks = row.querySelectorAll('.present-check');
        const partChecks = row.querySelectorAll('.participated-check');

        const sessions = [];
        for (let i=0;i<6;i++){
            sessions.push({
                present: !!presentChecks[i].checked,
                participated: !!partChecks[i].checked
            });
        }

        const anyPresent = sessions.some(s => s.present);
        const anyParticipated = sessions.some(s => s.participated);

        return {
            session_id: SESSION_ID,
            student_id: parseInt(studentId,10) || null,
            matricule: matricule,
            status: anyPresent ? 'present' : 'absent',
            participated: anyParticipated ? 1 : 0,
            sessions: sessions
        };
    }

    function saveRowToServer(row, indicatorEl=null) {
        if (!SESSION_ID) {
            saveRowLocal(row);
            if (indicatorEl) indicatorEl.textContent = '💾 Saved locally';
            return Promise.resolve({local:true});
        }
        const payload = rowToPayload(row);
        if (indicatorEl) indicatorEl.textContent = '⏳ Saving...';
        return fetch('save_attendance.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        }).then(r => r.json())
          .then(json => {
              if (indicatorEl) indicatorEl.textContent = (json && json.success) ? '✅ Saved' : '❌ Error';
              saveRowLocal(row);
              return json;
          }).catch(err => {
              if (indicatorEl) indicatorEl.textContent = '❌ Error';
              saveRowLocal(row);
              return {error:true};
          });
    }

    function saveRowLocal(row) {
        const data = loadAttendanceLocal();
        const matricule = row.dataset.matricule;
        data[matricule] = rowToPayload(row);
        saveAttendanceLocal(data);
    }

    function bindRowEvents(row) {
        row.querySelectorAll('.present-check, .participated-check').forEach(ch => {
            ch.addEventListener('change', function(){
                updateRow(row);
                if (row._saveTimer) clearTimeout(row._saveTimer);
                row._saveTimer = setTimeout(()=> {
                    saveRowToServer(row, document.getElementById('saveStatus'));
                }, 350);
            });
        });
    }

    function init() {
        document.querySelectorAll('#attendanceBody tr[data-student-id]').forEach(row => {
            updateRow(row);
            bindRowEvents(row);
        });

        document.getElementById('saveAll')?.addEventListener('click', function(){
            const rows = document.querySelectorAll('#attendanceBody tr[data-student-id]');
            const indicator = document.getElementById('saveStatus');
            indicator.textContent = '⏳ Saving all...';
            let promises = [];
            rows.forEach(r => promises.push(saveRowToServer(r)));
            Promise.all(promises).then(() => {
                indicator.textContent = '✅ All saved';
                setTimeout(()=> indicator.textContent = '', 3000);
            });
        });

        document.getElementById('showReport')?.addEventListener('click', function(){
            const rows = document.querySelectorAll('#attendanceBody tr[data-student-id]');
            const total = rows.length;
            let present = 0, participated = 0;
            rows.forEach(r => {
                const abs = parseInt(r.querySelector('.absences-count').textContent) || 0;
                const part = parseInt(r.querySelector('.participation-count').textContent) || 0;
                if (abs < 6) present++;
                if (part > 0) participated++;
            });
            document.getElementById('reportTotal').textContent = total;
            document.getElementById('reportPresent').textContent = present;
            document.getElementById('reportParticipated').textContent = participated;
            document.getElementById('reportSection').style.display = 'block';

            const ctx = document.getElementById('reportChart').getContext('2d');
            if (window._attendanceChart) window._attendanceChart.destroy();
            window._attendanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Total Students', 'Present (≥1)', 'Participated (≥1)'],
                    datasets: [{ 
                        label: 'Count', 
                        data: [total, present, participated], 
                        backgroundColor: ['#6D28D9', '#10B981', '#06B6D4'] 
                    }]
                },
                options: { 
                    responsive:true, 
                    plugins:{legend:{display:false}}, 
                    scales:{y:{beginAtZero:true}} 
                }
            });
        });

        document.getElementById('highlightExcellent')?.addEventListener('click', function(){
            document.querySelectorAll('#attendanceBody tr[data-student-id]').forEach(row=>{
                const abs = parseInt(row.querySelector('.absences-count').textContent) || 0;
                if (abs < 3) {
                    row.style.boxShadow = '0 0 15px rgba(109, 40, 217, 0.5)';
                    row.style.transform = 'scale(1.02)';
                }
            });
        });

        document.getElementById('resetColors')?.addEventListener('click', function(){
            document.querySelectorAll('#attendanceBody tr').forEach(row => {
                row.style.boxShadow = '';
                row.style.transform = '';
                row.classList.remove('row-red','row-yellow','row-green');
                if (row.dataset.studentId) updateRow(row);
            });
        });

        $('#attendanceBody').on('mouseenter', 'tr[data-student-id]', function(){ 
            $(this).css('background', 'var(--bg-light)'); 
        }).on('mouseleave','tr[data-student-id]', function(){ 
            $(this).css('background', ''); 
        });
    }

    document.addEventListener('DOMContentLoaded', init);
})();
</script>

</body>
</html>