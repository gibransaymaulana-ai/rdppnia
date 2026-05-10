<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'rapot_pondok');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($conn->connect_error) {
        $error = "Database belum terhubung: " . $conn->connect_error;
    } else {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        $academic_year = $_POST['academic_year'] ?? '';
        $semester = $_POST['semester'] ?? '';

        // Fetch user including status and type
        $sql = "SELECT id, password, name, role, status, panitia_type FROM users WHERE username = ?";
        $stmt = $conn->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    // Check if account is active
                    if ($row['status'] !== 'aktif') {
                        $error = 'Akun Anda dinonaktifkan. Silakan hubungi admin.';
                    } else {
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['name'] = $row['name'];
                        $_SESSION['role'] = $row['role'];
                        $_SESSION['panitia_type'] = $row['panitia_type'];
                        $_SESSION['academic_year'] = $academic_year;
                        $_SESSION['semester'] = $semester;
                        
                        // Check if user is a Walikelas
                        $wk_check = $conn->prepare("SELECT id FROM classes WHERE walikelas_id = ? LIMIT 1");
                        $wk_check->bind_param("i", $row['id']);
                        $wk_check->execute();
                        $_SESSION['is_walikelas'] = ($wk_check->get_result()->num_rows > 0);
                        
                        header("Location: dashboard.php");
                        exit;
                    }
                } else {
                    $error = 'Password salah!';
                }
            } else {
                $error = 'Username tidak ditemukan!';
            }
            $stmt->close();
        } else {
            $error = "Kesalahan Query: " . $conn->error;
        }
    }
}

// Default Settings
$default_year = date('Y') . '/' . (date('Y') + 1);
$default_sem = 'Ganjil';

if (!$conn->connect_error) {
    $res = $conn->query("SHOW TABLES LIKE 'settings'");
    if ($res->num_rows > 0) {
        $set = $conn->query("SELECT active_year, active_semester FROM settings LIMIT 1");
        if ($set && $r = $set->fetch_assoc()) {
            $default_year = $r['active_year'];
            $default_sem = $r['active_semester'];
        }
    }
}

// Academic Year List - Sync with Settings (Current + 5 years)
$years = [];
$current = (int)date('Y');
for($i = $current - 2; $i <= $current + 5; $i++) {
    $years[] = $i . '/' . ($i+1);
}
if (!in_array($default_year, $years)) $years[] = $default_year;
sort($years);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Portal - Rapot Digital Pondok</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #321fdb;
            --primary-dark: #2a1b9e;
            --secondary: #5b9324;
            --secondary-light: #8cc63f;
            --text-dark: #3c4b64;
            --text-muted: #8a93a2;
            --bg-light: #ebedef;
            --white: #ffffff;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }

        body {
            min-height: 100vh;
            background: var(--bg-light);
            display: flex;
        }

        .login-container {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Side Branding */
        .brand-side {
            flex: 1.2;
            background: var(--secondary);
            background-image: linear-gradient(135deg, #5b9324 0%, #8cc63f 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            padding: 60px;
            position: relative;
            overflow: hidden;
        }

        .brand-side::before {
            content: '';
            position: absolute;
            width: 150%;
            height: 150%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            top: -25%;
            left: -25%;
        }

        .brand-content {
            position: relative;
            z-index: 10;
            text-align: center;
            max-width: 500px;
        }

        .logo-box {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 32px;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .brand-content h1 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 16px;
            line-height: 1.2;
        }

        .brand-content p {
            font-size: 18px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 40px;
        }

        .illustration {
            width: 100%;
            max-width: 400px;
            filter: drop-shadow(0 20px 40px rgba(0,0,0,0.2));
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        /* Form Side */
        .form-side {
            flex: 1;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
        }

        .form-wrapper {
            max-width: 400px;
            width: 100%;
            margin: 0 auto;
        }

        .form-header {
            margin-bottom: 40px;
        }

        .form-header h2 {
            font-size: 32px;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .form-header p {
            color: var(--text-muted);
            font-size: 16px;
        }

        .field-group {
            margin-bottom: 24px;
        }

        .field-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .input-control {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.2s ease;
            background: #fff;
            color: var(--text-dark);
        }

        .input-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(59, 40, 204, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .login-button {
            width: 100%;
            padding: 16px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 16px;
            box-shadow: 0 10px 15px -3px rgba(59, 40, 204, 0.3);
        }

        .login-button:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(59, 40, 204, 0.4);
        }

        .alert-box {
            background: #fff1f2;
            border: 1px solid #ffe4e6;
            color: #be123c;
            padding: 16px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 32px;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--primary);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .brand-side { display: none; }
            .form-side { padding: 40px 24px; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="brand-side">
            <div class="brand-content">
                <div class="logo-box">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path></svg>
                </div>
                <h1>Rapot Digital Pondok</h1>
                <p>Selamat Datang di Portal Akademik Digital PP Nurul Iman Al Hasanah. Kelola data santri dengan lebih efisien.</p>
                <img src="assets/images/hero.png" alt="Illustration" class="illustration">
            </div>
        </div>

        <div class="form-side">
            <div class="form-wrapper">
                <a href="index.php" class="back-link">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    Kembali ke Beranda
                </a>

                <div class="form-header">
                    <h2>Portal Login</h2>
                    <p>Silakan masuk untuk mengakses sistem</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert-box">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" autocomplete="off">
                    <div class="form-row">
                        <div class="field-group">
                            <label>Tahun Ajaran</label>
                            <select name="academic_year" class="input-control">
                                <?php foreach($years as $y): ?>
                                    <option value="<?= $y ?>" <?= $y == $default_year ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field-group">
                            <label>Semester</label>
                            <select name="semester" class="input-control">
                                <option value="Ganjil" <?= $default_sem == 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
                                <option value="Genap" <?= $default_sem == 'Genap' ? 'selected' : '' ?>>Genap</option>
                            </select>
                        </div>
                    </div>

                    <div class="field-group">
                        <label>Username</label>
                        <input type="text" name="username" class="input-control" placeholder="Masukkan username" required autocomplete="off">
                    </div>

                    <div class="field-group">
                        <label>Password</label>
                        <input type="password" name="password" class="input-control" placeholder="Masukkan password" required autocomplete="new-password">
                    </div>

                    <button type="submit" class="login-button">Masuk ke Sistem</button>
                </form>

                <div style="margin-top: 32px; text-align: center;">
                    <a href="setup.php" style="color: var(--text-muted); text-decoration: none; font-size: 14px;">Bantuan Konfigurasi</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>