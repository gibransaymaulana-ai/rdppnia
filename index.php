<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapot Digital Pondok - PP Nurul Iman Al Hasanah</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <span class="logo-icon">R</span>
                <div class="logo-text">
                    <span class="brand">RDP</span>
                    <span class="school">PP Nurul Iman</span>
                </div>
            </div>
            <nav class="nav">
                <ul>
                    <li><a href="#home">Beranda</a></li>
                    <li><a href="#fitur">Fitur</a></li>
                    <li><a href="#kontak">Kontak</a></li>
                </ul>
                <a href="login.php" class="btn btn-login">Login Sistem</a>
            </nav>
        </div>
    </header>

    <main>
        <section id="home" class="hero">
            <div class="container">
                <div class="hero-content">
                    <span class="badge">Sistem Informasi Akademik</span>
                    <h1>Transformasi Digital <span>Rapor Pondok</span> Pesantren</h1>
                    <p>Wujudkan transparansi dan kemudahan pengelolaan nilai santri secara real-time, akurat, dan profesional di PP Nurul Iman Al Hasanah.</p>
                    <div class="hero-btns">
                        <a href="login.php" class="btn btn-primary">Masuk ke Portal</a>
                        <a href="#fitur" class="btn btn-outline">Lihat Fitur</a>
                    </div>
                </div>
                <div class="hero-image">
                    <img src="assets/images/hero.png" alt="Rapot Digital Pondok">
                    <div class="glass-card card-1">
                        <div class="card-icon">📈</div>
                        <div class="card-info">
                            <span>Statistik Nilai</span>
                            <strong>Meningkat 15%</strong>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="fitur" class="features">
            <div class="container">
                <div class="section-title">
                    <h2>Fitur Utama <span>Unggulan</span></h2>
                    <p>Dirancang khusus untuk memenuhi kebutuhan administrasi dan pelaporan nilai di Pondok Pesantren.</p>
                </div>
                <div class="feature-grid">
                    <div class="feature-card">
                        <div class="f-icon">📄</div>
                        <h3>E-Rapor Digital</h3>
                        <p>Cetak rapor otomatis dengan format yang sesuai standar kurikulum pondok dan kementerian.</p>
                    </div>
                    <div class="feature-card">
                        <div class="f-icon">🗓️</div>
                        <h3>Presensi Santri</h3>
                        <p>Pantau kehadiran santri dalam kegiatan belajar mengajar dan kegiatan pondok lainnya.</p>
                    </div>
                    <div class="feature-card">
                        <div class="f-icon">⚖️</div>
                        <h3>Manajemen Ujian</h3>
                        <p>Kelola ujian tulis, lisan, dan praktek dengan pembobotan nilai yang fleksibel.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-brand">
                    <h2>RDP</h2>
                    <p>PP Nurul Iman Al Hasanah</p>
                </div>
                <div class="footer-links">
                    <h4>Navigasi</h4>
                    <ul>
                        <li><a href="#home">Beranda</a></li>
                        <li><a href="#fitur">Fitur</a></li>
                        <li><a href="login.php">Login</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h4>Kontak Kami</h4>
                    <p>Jl. Contoh No. 123, Kabupaten/Kota</p>
                    <p>Email: info@nuruliman.sch.id</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Rapot Digital Pondok. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>