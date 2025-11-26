<?php
/**
 * attendance.php
 * Attendance UI for a selected session (Tutorial 3)
 * - Requires: GET param session_id
 * - Saves via save_attendance.php (expects JSON POST)
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
        // Students list
        $stmt = $conn->query("SELECT id, fullname, matricule, group_id FROM students ORDER BY fullname ASC");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Attendance records for this session (if any)
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
    <title>Attendance Session<?php if ($sessionRow) echo ' — ' . htmlspecialchars($sessionRow['course_id']); ?></title>
    <link rel="stylesheet" href="style.css" />
    <!-- jQuery + Chart.js (CDN) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Small page-specific tweaks */
        .page-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
        .page-title { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; gap:12px; flex-wrap:wrap; }
        .page-title h2 { margin:0; color:#A6615A; }
        .session-meta { color:#555; font-size:14px; }
        .attendance-table th, .attendance-table td { padding:8px; font-size:13px; }
        .controls-bar { margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; }
        .btn-sm { padding:8px 12px; border-radius:6px; border:none; background:#A6615A; color:#fff; cursor:pointer; }
        .btn-sm.secondary { background:#555; }
        .save-indicator { font-size:13px; color:#333; margin-left:8px; }
        .message.error { padding:10px; background:#f8d7da; color:#721c24; border-radius:6px; margin-bottom:15px; }
    </style>
</head>
<body>

<!-- TOP HEADER (same as index & students) -->
<header class="topbar">
  <div class="brand" style="display:flex;align-items:center;gap:10px;">
    <!-- local screenshot image path you uploaded (will be transformed if needed) -->
    <div class="logo">📚</div>
    <h1 style="margin:0;font-size:18px;">Student Dashboard</h1>
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

<main class="page-container">

    <?php if ($sessionError): ?>
        <div class="message error">
            <?= htmlspecialchars($sessionError) ?><br>
            <a href="sessions.php" style="color:#721c24; text-decoration:underline;">Go back to Sessions</a>
        </div>
    <?php else: ?>

    <div class="page-title">
        <div>
            <h2>Attendance Session</h2>
            <div class="session-meta">
                Course: <strong><?= htmlspecialchars($sessionRow['course_id']) ?></strong> ·
                Group: <strong><?= htmlspecialchars($sessionRow['group_id']) ?></strong> ·
                Date: <strong><?= htmlspecialchars($sessionRow['date']) ?></strong> ·
                Session ID: <strong><?= (int)$sessionRow['id'] ?></strong>
            </div>
        </div>

        <div style="display:flex;align-items:center;">
            <a href="sessions.php" class="btn-sm secondary" style="text-decoration:none; margin-right:8px;">← Back to Sessions</a>
            <button id="saveAll" class="btn-sm">Save All</button>
            <div id="saveStatus" class="save-indicator" aria-live="polite"></div>
        </div>
    </div>

    <!-- Attendance table -->
    <div style="overflow-x:auto;">
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
                    <th>Message</th>
                </tr>
            </thead>
            <tbody id="attendanceBody">
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="18" style="text-align:center; padding:40px; color:#666;">
                            No students in database. <a href="students.php">Add students first</a>
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
                        <td><?= htmlspecialchars($last) ?></td>
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

    <!-- Controls + Report -->
    <div class="controls-bar">
        <button id="showReport" class="btn-sm">Show Report</button>
        <button id="highlightExcellent" class="btn-sm">Highlight Excellent Students</button>
        <button id="resetColors" class="btn-sm secondary">Reset Colors</button>
    </div>

    <div id="reportSection" class="report-panel" style="display:none; margin-top:18px;">
        <h3>Attendance Report</h3>
        <p>
            Total students: <span id="reportTotal">0</span> ·
            Present (≥1): <span id="reportPresent">0</span> ·
            Participated (≥1): <span id="reportParticipated">0</span>
        </p>
        <div style="max-width:700px;"><canvas id="reportChart" height="120"></canvas></div>
    </div>

    <?php endif; // end session ok ?>

</main>

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

        // message logic
        let message = '';
        if (abs >= 5) message = 'Excluded — too many absences — You need to participate more';
        else if (abs >= 3) message = 'Warning — attendance low — You need to participate more';
        else if (par >= 4) message = 'Good attendance — Excellent participation';
        else message = 'Good attendance — Work on participation';

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
            // Just save locally
            saveRowLocal(row);
            if (indicatorEl) indicatorEl.textContent = 'Saved locally';
            return Promise.resolve({local:true});
        }
        const payload = rowToPayload(row);
        // show saving indicator if provided
        if (indicatorEl) indicatorEl.textContent = 'Saving...';
        return fetch('save_attendance.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        }).then(r => r.json())
          .then(json => {
              if (indicatorEl) indicatorEl.textContent = (json && json.success) ? 'Saved' : 'Error';
              // also persist locally as mirror
              saveRowLocal(row);
              return json;
          }).catch(err => {
              if (indicatorEl) indicatorEl.textContent = 'Error';
              // fallback: save locally
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
                // debounce server save per row
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
            indicator.textContent = 'Saving...';
            let promises = [];
            rows.forEach(r => promises.push(saveRowToServer(r)));
            Promise.all(promises).then(() => {
                indicator.textContent = 'All saved';
                setTimeout(()=> indicator.textContent = '', 2000);
            });
        });

        document.getElementById('showReport')?.addEventListener('click', function(){
            const rows = document.querySelectorAll('#attendanceBody tr[data-student-id]');
            const total = rows.length;
            let present = 0;
            let participated = 0;
            rows.forEach(r => {
                const absText = r.querySelector('.absences-count').textContent;
                const abs = parseInt(absText) || 0;
                const partText = r.querySelector('.participation-count').textContent;
                const part = parseInt(partText) || 0;
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
                    labels: ['Total', 'Present (≥1)', 'Participated (≥1)'],
                    datasets: [{ label: 'Counts', data: [total, present, participated], backgroundColor: ['#A6615A', '#10B981', '#06B6D4'] }]
                },
                options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
            });
        });

        document.getElementById('highlightExcellent')?.addEventListener('click', function(){
            document.querySelectorAll('#attendanceBody tr[data-student-id]').forEach(row=>{
                const absText = row.querySelector('.absences-count').textContent;
                const abs = parseInt(absText) || 0;
                if (abs < 3) row.classList.add('animate-glow');
            });
        });

        document.getElementById('resetColors')?.addEventListener('click', function(){
            document.querySelectorAll('#attendanceBody tr').forEach(row => {
                row.classList.remove('animate-glow','hover-highlight','row-red','row-yellow','row-green');
                updateRow(row);
            });
        });

        // jQuery hover & click interactions (keeps your previous UX)
        $('#attendanceBody').on('mouseenter', 'tr[data-student-id]', function(){ $(this).addClass('hover-highlight'); })
                            .on('mouseleave','tr[data-student-id]', function(){ $(this).removeClass('hover-highlight'); })
                            .on('click','tr[data-student-id]', function(e){
                                if ($(e.target).is('input')) return;
                                const last = $(this).find('td').eq(0).text();
                                const first = $(this).find('td').eq(1).text();
                                const abs = $(this).find('.absences-count').text();
                                alert('Student: ' + last + ' ' + first + '\n' + abs);
                            });
    }

    // initialize on DOM ready
    document.addEventListener('DOMContentLoaded', init);
})();
</script>

</body>
</html>
