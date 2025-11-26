<?php
/**
 * AttendEase - Student Management Module
 * Comprehensive CRUD operations for student records
 */
require_once 'db_connect.php';

$message = '';
$error = '';
$editStudent = null;

$conn = getConnection();

// Handle Edit Request
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    if ($conn) {
        try {
            $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$id]);
            $editStudent = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle Delete Request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if ($conn) {
        try {
            $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$id]);
            $message = "✅ Student record deleted successfully!";
        } catch (PDOException $e) {
            $error = "Delete error: " . $e->getMessage();
        }
    }
}

// Handle Form Submission (Add or Update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $matricule = trim($_POST['matricule'] ?? '');
    $group_id = trim($_POST['group_id'] ?? '');
    $id = $_POST['id'] ?? null;

    if (empty($fullname) || empty($matricule) || empty($group_id)) {
        $error = 'All fields are required';
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $fullname)) {
        $error = 'Full name must contain only letters';
    } elseif (!preg_match('/^\d+$/', $matricule)) {
        $error = 'Matricule must contain only numbers';
    } else {
        if ($conn) {
            try {
                if ($id) {
                    // Update
                    $stmt = $conn->prepare("SELECT id FROM students WHERE matricule = ? AND id != ?");
                    $stmt->execute([$matricule, $id]);
                    if ($stmt->fetch()) {
                        $error = 'This matricule is already used by another student';
                    } else {
                        $stmt = $conn->prepare("UPDATE students SET fullname=?, matricule=?, group_id=? WHERE id=?");
                        $stmt->execute([$fullname, $matricule, $group_id, $id]);
                        $message = "✅ Student record updated successfully!";
                        $editStudent = null;
                    }
                } else {
                    // Insert
                    $stmt = $conn->prepare("SELECT id FROM students WHERE matricule = ?");
                    $stmt->execute([$matricule]);
                    if ($stmt->fetch()) {
                        $error = 'A student with this matricule already exists';
                    } else {
                        $stmt = $conn->prepare("INSERT INTO students(fullname, matricule, group_id) VALUES (?, ?, ?)");
                        $stmt->execute([$fullname, $matricule, $group_id]);
                        $message = "✅ New student added successfully!";
                    }
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Database connection failed";
        }
    }
}

// Fetch all students
$students = [];
if ($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM students ORDER BY id DESC");
        $students = $stmt->fetchAll();
    } catch (PDOException $e) {
        $error = "Error fetching students: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Student Management - AttendEase</title>
    <link rel="stylesheet" href="style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>

<header class="topbar">
  <div class="container-nav">
    <div class="brand">
      <div class="logo">👥</div>
      <h1>AttendEase</h1>
    </div>

    <nav class="nav">
      <ul class="navbar">
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="students.php" class="active">Students</a></li>
        <li><a href="sessions.php">Sessions</a></li>
        <li><a href="reports.php">Analytics</a></li>
        <li><a href="logout.php">Logout</a></li>
      </ul>
    </nav>
  </div>
</header>

<main style="padding:40px 30px; max-width:1400px; margin:0 auto;">

    <h1 style="color:var(--dark-purple); margin-bottom:10px; font-size: 32px;">Student Registry</h1>
    <p style="color:var(--text-light); margin-bottom:30px;">Manage student enrollments and information</p>

    <?php if ($message): ?>
        <div class="message success"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div style="display:grid; grid-template-columns:1fr 2fr; gap:25px;">

        <!-- Add / Edit Form -->
        <div class="card">
            <h2><?php echo $editStudent ? 'Edit Student Record' : 'Register New Student'; ?></h2>

            <form id="studentForm" method="POST" action="" class="student-form" novalidate>
                <?php if ($editStudent): ?>
                    <input type="hidden" name="id" value="<?php echo $editStudent['id']; ?>">
                <?php endif; ?>

                <label for="fullname">Full Name *</label>
                <input type="text" id="fullname" name="fullname"
                       value="<?php echo htmlspecialchars($editStudent['fullname'] ?? ''); ?>" 
                       placeholder="Enter full name"
                       required>

                <label for="matricule">Matricule Number *</label>
                <input type="text" id="matricule" name="matricule"
                       value="<?php echo htmlspecialchars($editStudent['matricule'] ?? ''); ?>" 
                       placeholder="Enter matricule (numbers only)"
                       required>

                <label for="group_id">Group / Section *</label>
                <input type="text" id="group_id" name="group_id"
                       value="<?php echo htmlspecialchars($editStudent['group_id'] ?? ''); ?>" 
                       placeholder="e.g., G1, G2"
                       required>

                <button type="submit">
                    <?php echo $editStudent ? '💾 Update Student' : '➕ Add Student'; ?>
                </button>

                <?php if ($editStudent): ?>
                    <a href="students.php" class="btn outline" style="margin-top:10px; display:block; text-align:center;">
                        Cancel Edit
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Student List -->
        <div class="card">
            <h2>Enrolled Students (<?php echo count($students); ?>)</h2>

            <?php if (empty($students)): ?>
                <div class="empty">
                    <p>📋 No students enrolled yet.</p>
                    <p>Use the form on the left to register your first student!</p>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Matricule</th>
                                <th>Group</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo $student['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($student['fullname']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['matricule']); ?></td>
                                    <td><span style="background:var(--bg-light); padding:4px 12px; border-radius:12px;"><?php echo htmlspecialchars($student['group_id']); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($student['created_at'])); ?></td>

                                    <td>
                                        <a href="students.php?edit=<?php echo $student['id']; ?>" class="btn btn-edit">✏️ Edit</a>
                                        <a href="students.php?delete=<?php echo $student['id']; ?>"
                                           onclick="return confirm('Are you sure you want to delete this student?');"
                                           class="btn warn">🗑️ Delete</a>
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
  <p>AttendEase Academic Management System © <?php echo date('Y'); ?></p>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('studentForm');
    const fullname = document.getElementById('fullname');
    const matricule = document.getElementById('matricule');
    const group_id = document.getElementById('group_id');

    function showError(field, message) {
        let errorDiv = field.nextElementSibling;
        if (!errorDiv || !errorDiv.classList.contains('field-error')) {
            errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.style.cssText = 'color:#EF4444; font-size:13px; margin-top:5px;';
            field.parentNode.insertBefore(errorDiv, field.nextSibling);
        }
        errorDiv.textContent = message;
        field.style.borderColor = message ? '#EF4444' : 'var(--border)';
    }

    function validateName(value) {
        if (!value.trim()) return 'Full name is required';
        if (!/^[A-Za-z\s]+$/.test(value)) return 'Name must contain only letters';
        return '';
    }

    function validateMatricule(value) {
        if (!value.trim()) return 'Matricule is required';
        if (!/^\d+$/.test(value)) return 'Matricule must contain only numbers';
        return '';
    }

    function validateGroup(value) {
        if (!value.trim()) return 'Group is required';
        return '';
    }

    fullname.addEventListener('blur', () => showError(fullname, validateName(fullname.value)));
    matricule.addEventListener('blur', () => showError(matricule, validateMatricule(matricule.value)));
    group_id.addEventListener('blur', () => showError(group_id, validateGroup(group_id.value)));

    form.addEventListener('submit', function(e) {
        const nameErr = validateName(fullname.value);
        const matErr  = validateMatricule(matricule.value);
        const grpErr  = validateGroup(group_id.value);

        showError(fullname, nameErr);
        showError(matricule, matErr);
        showError(group_id, grpErr);

        if (nameErr || matErr || grpErr) {
            e.preventDefault();
        }
    });
});
</script>

</body>
</html>
