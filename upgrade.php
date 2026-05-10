<?php
$conn = new mysqli('localhost', 'root', '', 'rapot_pondok');
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);

// Create settings table
$conn->query("CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    active_semester ENUM('Ganjil', 'Genap') NOT NULL DEFAULT 'Ganjil',
    active_year VARCHAR(20) NOT NULL DEFAULT '2025/2026',
    headmaster_name VARCHAR(100) NOT NULL DEFAULT 'Kyai Ahmad'
)");
$checkSet = $conn->query("SELECT * FROM settings");
if ($checkSet->num_rows == 0) {
    $conn->query("INSERT INTO settings (id, active_semester, active_year) VALUES (1, 'Ganjil', '2025/2026')");
}

// 1. Upgrade Users Table
$conn->query("ALTER TABLE users ADD COLUMN status ENUM('aktif', 'non-aktif') DEFAULT 'aktif' AFTER role");
$conn->query("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'panitia', 'walikelas') NOT NULL");
$conn->query("UPDATE users SET role = 'panitia' WHERE role = 'guru'"); // Migrate existing gurus

// 2. Upgrade Subjects Table
$conn->query("ALTER TABLE subjects ADD COLUMN kkm INT DEFAULT 75");

// 3. Upgrade Teaching Assignments
// Check if guru_id exists before renaming
$checkCol = $conn->query("SHOW COLUMNS FROM teaching_assignments LIKE 'guru_id'");
if ($checkCol->num_rows > 0) {
    $conn->query("ALTER TABLE teaching_assignments CHANGE COLUMN guru_id panitia_id INT");
}

// 4. Upgrade Grades Table
// Remove old columns if they exist and add new ones
$conn->query("ALTER TABLE grades DROP COLUMN score");
$conn->query("ALTER TABLE grades DROP COLUMN nilai_pengetahuan");
$conn->query("ALTER TABLE grades DROP COLUMN nilai_keterampilan");
$conn->query("ALTER TABLE grades ADD COLUMN score_tulis DECIMAL(5,2) DEFAULT 0");
$conn->query("ALTER TABLE grades ADD COLUMN score_praktek DECIMAL(5,2) DEFAULT 0");
$conn->query("ALTER TABLE grades ADD COLUMN score_lisan DECIMAL(5,2) DEFAULT 0");

// 5. Create grade submissions table
$conn->query("CREATE TABLE IF NOT EXISTS grade_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT,
    semester ENUM('Ganjil', 'Genap') NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    is_submitted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (assignment_id) REFERENCES teaching_assignments(id) ON DELETE CASCADE,
    UNIQUE KEY assignment_sem_year (assignment_id, semester, academic_year)
)");

// 6. Create walikelas notes table
$conn->query("CREATE TABLE IF NOT EXISTS walikelas_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    semester ENUM('Ganjil', 'Genap') NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    sakit INT DEFAULT 0,
    izin INT DEFAULT 0,
    alpa INT DEFAULT 0,
    catatan TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY student_sem_year (student_id, semester, academic_year)
)");

echo "Database upgraded successfully to Panitia Ujian & Multi-Exam System!";
?>
