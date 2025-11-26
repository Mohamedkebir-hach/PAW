-- ================================================
-- AttendEase Database Schema
-- Academic Attendance Management System v2.0
-- ================================================

CREATE DATABASE IF NOT EXISTS attendease_db 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE attendease_db;

-- ================================================
-- STUDENTS TABLE
-- Stores student information and enrollment data
-- ================================================
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    matricule VARCHAR(50) NOT NULL UNIQUE,
    group_id VARCHAR(50) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    enrollment_date DATE DEFAULT (curdate()),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_matricule (matricule),
    INDEX idx_group (group_id),
    INDEX idx_active (is_active),
    INDEX idx_fullname (fullname)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- ATTENDANCE SESSIONS TABLE
-- Manages class sessions and attendance periods
-- ================================================
CREATE TABLE IF NOT EXISTS attendance_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id VARCHAR(50) NOT NULL,
    course_name VARCHAR(255) DEFAULT NULL,
    group_id VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    time_slot VARCHAR(50) DEFAULT NULL,
    opened_by VARCHAR(100) NOT NULL,
    status ENUM('open', 'closed', 'cancelled') DEFAULT 'open',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_course (course_id),
    INDEX idx_group (group_id),
    INDEX idx_date (date),
    INDEX idx_status (status),
    INDEX idx_course_group_date (course_id, group_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- ATTENDANCE RECORDS TABLE
-- Tracks individual student attendance per session
-- ================================================
CREATE TABLE IF NOT EXISTS attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'absent',
    participated BOOLEAN DEFAULT FALSE,
    participation_score INT DEFAULT 0,
    remarks TEXT DEFAULT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    
    UNIQUE KEY unique_session_student (session_id, student_id),
    INDEX idx_session (session_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================
-- SAMPLE DATA
-- Initial test data for system demonstration
-- ================================================

-- Insert sample students
INSERT INTO students (fullname, matricule, group_id, email) VALUES
('Amira Benali', '211001', 'G1', 'amira.benali@university.dz'),
('Yacine Mahmoudi', '211002', 'G1', 'yacine.mahmoudi@university.dz'),
('Salma Khelifa', '211003', 'G2', 'salma.khelifa@university.dz'),
('Rayan Boudiaf', '211004', 'G1', 'rayan.boudiaf@university.dz'),
('Nour Saidi', '211005', 'G2', 'nour.saidi@university.dz'),
('Karim Meziane', '211006', 'G1', 'karim.meziane@university.dz'),
('Lina Cherif', '211007', 'G2', 'lina.cherif@university.dz');

-- Insert sample sessions
INSERT INTO attendance_sessions (course_id, course_name, group_id, date, time_slot, opened_by, status) VALUES
('WEB301', 'Advanced Web Development', 'G1', '2025-01-20', '08:00-10:00', 'Dr. Ahmed Mansouri', 'closed'),
('WEB301', 'Advanced Web Development', 'G1', '2025-01-27', '08:00-10:00', 'Dr. Ahmed Mansouri', 'closed'),
('WEB301', 'Advanced Web Development', 'G2', '2025-01-21', '10:00-12:00', 'Dr. Ahmed Mansouri', 'open'),
('DB302', 'Database Management', 'G1', '2025-01-22', '14:00-16:00', 'Prof. Fatima Zohra', 'closed');

-- Insert sample attendance records
INSERT INTO attendance_records (session_id, student_id, status, participated, participation_score) VALUES
(1, 1, 'present', TRUE, 5),
(1, 2, 'present', TRUE, 4),
(1, 4, 'absent', FALSE, 0),
(1, 6, 'present', FALSE, 2),
(2, 1, 'present', TRUE, 5),
(2, 2, 'late', TRUE, 3),
(2, 4, 'present', TRUE, 4),
(2, 6, 'present', FALSE, 1);

-- ================================================
-- VIEWS FOR REPORTING
-- ================================================
CREATE OR REPLACE VIEW v_attendance_summary AS
SELECT 
    s.id,
    s.fullname,
    s.matricule,
    s.group_id,
    COUNT(ar.id) as total_sessions,
    SUM(CASE WHEN ar.status = 'present' THEN 1 ELSE 0 END) as present_count,
    SUM(CASE WHEN ar.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
    SUM(CASE WHEN ar.participated = TRUE THEN 1 ELSE 0 END) as participation_count,
    ROUND(AVG(CASE WHEN ar.status = 'present' THEN 100 ELSE 0 END), 2) as attendance_rate
FROM students s
LEFT JOIN attendance_records ar ON s.id = ar.student_id
GROUP BY s.id, s.fullname, s.matricule, s.group_id;

-- ================================================
-- SYSTEM INFO
-- ================================================
SELECT 'AttendEase Database Created Successfully!' as Status;
SELECT COUNT(*) as 'Total Students' FROM students;
SELECT COUNT(*) as 'Total Sessions' FROM attendance_sessions;
SELECT COUNT(*) as 'Total Records' FROM attendance_records;
