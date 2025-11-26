/* ---------- LocalStorage Key ---------- */
const STORAGE_KEY = 'awp_students_tp3_v1';

/* ---------- Get / Save Students ---------- */
function getStudents() {
  try { return JSON.parse(localStorage.getItem(STORAGE_KEY)) || []; }
  catch(e) { console.error('parse error', e); return []; }
}

function saveStudents(arr) {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(arr));
}

/* ---------- Demo Seed ---------- */
function seedDemoStudents() {
  const existing = getStudents();
  if (existing.length > 0) return;

  const makeSessions = (pArr, paArr) => {
    const sessions = [];
    for (let i=0; i<6; i++) {
      sessions.push({ present: !!pArr[i], participated: !!paArr[i] });
    }
    return sessions;
  };

  const students = [
    { id:'1001', lastname:'Ahmed', firstname:'Sara',
      email:'sara@example.com', course:'AWP',
      date:new Date().toLocaleDateString(),
      sessions: makeSessions([0,0,1,1,0,0],[0,0,1,1,0,0]) },

    { id:'1002', lastname:'Yacine', firstname:'Ali',
      email:'ali@example.com', course:'AWP',
      date:new Date().toLocaleDateString(),
      sessions: makeSessions([1,1,1,1,1,1],[1,1,1,1,1,0]) },

    { id:'1003', lastname:'Houcine', firstname:'Rania',
      email:'rania@example.com', course:'AWP',
      date:new Date().toLocaleDateString(),
      sessions: makeSessions([1,1,0,1,0,0],[1,1,0,1,0,0]) }
  ];

  saveStudents(students);
}

/* ---------- Helpers ---------- */
function escapeHtml(str='') {
  return String(str).replace(/[&<>"]/g, m => (
    {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]
  ));
}

/* ---------- Render Attendance Table ---------- */
function renderAttendanceTable() {
  const tbody = document.getElementById('attendanceBody');
  if (!tbody) return;

  tbody.innerHTML = '';
  const students = getStudents();

  if (students.length === 0) {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td colspan="18" style="padding:18px;text-align:center;color:#666;">
        No students. Use "Add Student" or "Seed demo students".
      </td>`;
    tbody.appendChild(tr);
    return;
  }

  students.forEach((s, idx) => {
    const absences = s.sessions.reduce((acc, sess) => acc + (sess.present ? 0 : 1), 0);
    const participations = s.sessions.reduce((acc, sess) => acc + (sess.participated ? 1 : 0), 0);

    let messageText = '';
    if (absences >= 5) messageText = 'Excluded — too many absences — You need to participate more';
    else if (absences >= 3) messageText = 'Warning — attendance low — You need to participate more';
    else if (participations >= 4) messageText = 'Good attendance — Excellent participation';
    else messageText = 'Good attendance — Work on participation';

    const tr = document.createElement('tr');

    if (absences >= 5) tr.className = 'row-red';
    else if (absences >= 3) tr.className = 'row-yellow';
    else tr.className = 'row-green';

    let inner = `
      <td>${escapeHtml(s.lastname)}</td>
      <td>${escapeHtml(s.firstname)}</td>`;

    s.sessions.forEach((sess, si) => {
      inner += `
        <td><input type="checkbox" data-action="toggle-present"
             data-index="${idx}" data-session="${si}"
             ${sess.present ? 'checked' : ''}></td>
        <td><input type="checkbox" data-action="toggle-part"
             data-index="${idx}" data-session="${si}"
             ${sess.participated ? 'checked' : ''}></td>`;
    });

    inner += `
      <td>${absences} Abs</td>
      <td>${participations} Par</td>
      <td class="message-cell">${escapeHtml(messageText)}</td>
    `;

    tr.innerHTML = inner;
    tbody.appendChild(tr);
  });
}

/* ---------- Checkbox Interaction ---------- */
function initAttendanceInteractions() {
  const tbody = document.getElementById('attendanceBody');
  if (!tbody) return;

  tbody.addEventListener('change', function(e) {
    const input = e.target;
    const action = input.dataset.action;
    if (!action) return;

    const idx = Number(input.dataset.index);
    const sessionIndex = Number(input.dataset.session);
    const students = getStudents();

    if (!students[idx]) return;

    if (action === 'toggle-present') {
      students[idx].sessions[sessionIndex].present = input.checked;
    } else if (action === 'toggle-part') {
      students[idx].sessions[sessionIndex].participated = input.checked;
    }

    saveStudents(students);
    renderAttendanceTable();
  });
}

/* ---------- Report ---------- */
let chartInstance = null;

function computeReportCounts() {
  const students = getStudents();
  return {
    total: students.length,
    presentStudents: students.filter(s => s.sessions.some(sess => sess.present)).length,
    participatedStudents: students.filter(s => s.sessions.some(sess => sess.participated)).length
  };
}

/* ---------- Init ---------- */
document.addEventListener('DOMContentLoaded', function() {
  seedDemoStudents();
  renderAttendanceTable();
  initAttendanceInteractions();
});
