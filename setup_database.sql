-- Tutorial 3 - Exercise 4 & 5
-- Database Setup Script
-- Run this in phpMyAdmin or MySQL command line

CREATE DATABASE IF NOT EXISTS student_dashboard 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE student_dashboard;

-- Students table (Exercise 4)
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(255) NOT NULL,
    matricule VARCHAR(50) NOT NULL UNIQUE,
    group_id VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_matricule (matricule),
    INDEX idx_group (group_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance sessions table (Exercise 5)
CREATE TABLE IF NOT EXISTS attendance_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id VARCHAR(50) NOT NULL,
    group_id VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    opened_by VARCHAR(100) NOT NULL,
    status ENUM('open', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    INDEX idx_course (course_id),
    INDEX idx_group (group_id),
    INDEX idx_date (date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance records table (to track individual student attendance)
CREATE TABLE IF NOT EXISTS attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('present', 'absent') DEFAULT 'absent',
    participated BOOLEAN DEFAULT FALSE,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_session (session_id),
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO students (fullname, matricule, group_id) VALUES
('Boucenna Lyna', '1001', 'G1'),
('Belhinous Hiba', '1002', 'G1'),
('Aida Moufouki', '1003', 'G2'),
('kebir Karim', '1004', 'G1'),
('Messouaf Imane', '1005', 'G2');

-- Insert sample sessions
INSERT INTO attendance_sessions (course_id, group_id, date, opened_by, status) VALUES
('AWP', 'G1', '2025-01-15', 'Prof. Benali', 'closed'),
('AWP', 'G1', '2025-01-22', 'Prof. Benali', 'closed'),
('AWP', 'G2', '2025-01-16', 'Prof. Benali', 'open');