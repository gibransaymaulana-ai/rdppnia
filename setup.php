<?php
$host = 'localhost';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create DB
$sql = "CREATE DATABASE IF NOT EXISTS rapot_pondok";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error;
}

$conn->select_db('rapot_pondok');

$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL,
        role ENUM('admin', 'panitia', 'walikelas') NOT NULL,
        status ENUM('aktif', 'non-aktif') DEFAULT 'aktif'
    )",
    "CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        walikelas_id INT,
        FOREIGN KEY (walikelas_id) REFERENCES users(id) ON DELETE SET NULL
    )",
    "CREATE TABLE IF NOT EXISTS students (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nis VARCHAR(20) NOT NULL UNIQUE,
        name VARCHAR(100) NOT NULL,
        class_id INT,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
    )",
    "CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        kkm INT DEFAULT 75
    )",
    "CREATE TABLE IF NOT EXISTS teaching_assignments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        panitia_id INT,
        class_id INT,
        subject_id INT,
        FOREIGN KEY (panitia_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS grades (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT,
        subject_id INT,
        score_tulis DECIMAL(5,2) DEFAULT 0,
        score_praktek DECIMAL(5,2) DEFAULT 0,
        score_lisan DECIMAL(5,2) DEFAULT 0,
        semester ENUM('Ganjil', 'Genap') NOT NULL,
        academic_year VARCHAR(20) NOT NULL,
        UNIQUE KEY student_subject_sem_year (student_id, subject_id, semester, academic_year),
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
    )",
    "CREATE TABLE IF NOT EXISTS grade_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        assignment_id INT,
        semester ENUM('Ganjil', 'Genap') NOT NULL,
        academic_year VARCHAR(20) NOT NULL,
        is_submitted TINYINT DEFAULT 0,
        UNIQUE KEY assignment_sem_year (assignment_id, semester, academic_year),
        FOREIGN KEY (assignment_id) REFERENCES teaching_assignments(id) ON DELETE CASCADE
    )"
];

foreach ($tables as $t) {
    if ($conn->query($t) === TRUE) {
        echo "Table created successfully<br>";
    } else {
        echo "Error creating table: " . $conn->error . "<br>";
    }
}

// Insert dummy users if not exists
$checkUser = $conn->query("SELECT * FROM users LIMIT 1");
if ($checkUser->num_rows == 0) {
    $pw = password_hash('password', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, password, name, role) VALUES 
        ('admin', '$pw', 'Admin Proktor', 'admin'),
        ('panitia1', '$pw', 'Ustadz Ahmad', 'panitia'),
        ('walikelas1', '$pw', 'Ustadz Budi', 'walikelas')");
    
    $conn->query("INSERT INTO classes (name, walikelas_id) VALUES ('Kelas 7A', 3)");
    $conn->query("INSERT INTO students (nis, name, class_id) VALUES ('1001', 'Santri Satu', 1), ('1002', 'Santri Dua', 1)");
    $conn->query("INSERT INTO subjects (name) VALUES ('Fiqih'), ('Aqidah'), ('Bahasa Arab')");
    $conn->query("INSERT INTO teaching_assignments (panitia_id, class_id, subject_id) VALUES (2, 1, 1), (2, 1, 2)");
    
    echo "Dummy data inserted. Default password is 'password'<br>";
}

echo "Setup complete. <a href='index.php'>Go to login</a>";
?>
