<?php
require_once 'includes/auth.php';
checkRole('any');
require_once 'includes/db.php';

// Handle Profile Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile']) && $_SESSION['role'] === 'admin') {
    $nsm = $_POST['nsm'];
    $npsn = $_POST['npsn'];
    $address = $_POST['address'];
    $district = $_POST['district'];
    $city = $_POST['city'];
    
    $stmt = $conn->prepare("UPDATE settings SET nsm=?, npsn=?, address=?, district=?, city=? WHERE id=1");
    $stmt->bind_param("sssss", $nsm, $npsn, $address, $district, $city);
    $stmt->execute();
    header("Location: dashboard.php?success=Profil diperbarui");
    exit;
}

// Handle Logo Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo_file']) && $_SESSION['role'] === 'admin') {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    
    $ext = pathinfo($_FILES["logo_file"]["name"], PATHINFO_EXTENSION);
    $new_filename = "logo_" . time() . "." . $ext;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($_FILES["logo_file"]["tmp_name"], $target_file)) {
        $stmt = $conn->prepare("UPDATE settings SET logo=? WHERE id=1");
        $stmt->bind_param("s", $target_file);
        $stmt->execute();
        header("Location: dashboard.php?success=Logo diperbarui");
        exit;
    }
}

require_once 'includes/header.php';
require_once 'includes/sidebar.php';

$settings = $conn->query("SELECT * FROM settings WHERE id=1")->fetch_assoc();
$is_edit = isset($_GET['edit']) && $_SESSION['role'] === 'admin';
$logo_path = !empty($settings['logo']) && $settings['logo'] !== 'default_logo.png' ? $settings['logo'] : 'https://upload.wikimedia.org/wikipedia/commons/4/4f/Kementerian_Agama_new_logo.png';
?>
<div class="breadcrumb">
    <a href="#">Beranda</a> / <span style="text-transform: capitalize;"><?php echo htmlspecialchars($_SESSION['role']); ?></span> / Dashboard
</div>

<?php if(isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <?php echo htmlspecialchars($_GET['success']); ?>
    </div>
<?php endif; ?>

<div class="dashboard-grid" style="display: grid; grid-template-columns: 350px 1fr; gap: 24px; align-items: start;">
    <!-- Profile Sidebar Card -->
    <div class="card profile-card" style="text-align: center; padding-top: 40px;">
        <div class="logo-wrapper" style="width: 140px; height: 140px; margin: 0 auto 24px; background: #f8fafc; border-radius: 30px; display: flex; align-items: center; justify-content: center; overflow: hidden; border: 4px solid #fff; box-shadow: var(--shadow-md);">
            <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo Pondok" style="max-width: 100px; max-height: 100px; object-fit: contain;">
        </div>
        
        <h2 style="font-size: 20px; font-weight: 700; color: var(--text-dark); margin-bottom: 4px;">PP NURUL IMAN AL HASANAH</h2>
        <p style="color: var(--primary); font-weight: 700; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; margin-bottom: 32px; background: rgba(59, 40, 204, 0.05); display: inline-block; padding: 4px 16px; border-radius: 100px;">
            <?php echo htmlspecialchars($_SESSION['role']); ?>
        </p>
        
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <div style="padding: 0 24px 24px;">
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <form method="POST" enctype="multipart/form-data" id="logoForm">
                    <input type="file" name="logo_file" id="logo_file" style="display:none;" accept="image/*" onchange="document.getElementById('logoForm').submit();">
                    <button type="button" class="btn btn-primary" style="width: 100%;" onclick="document.getElementById('logo_file').click();">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                        Ganti Logo
                    </button>
                </form>
                <?php if(!$is_edit): ?>
                    <a href="?edit=1" class="btn btn-outline" style="width: 100%;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                        Edit Profil
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="padding: 16px 24px; background: #fffbeb; border-top: 1px solid #fde68a; color: #92400e; font-size: 12px; font-weight: 500;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            Gunakan logo ukuran maks 200x200 px untuk performa optimal.
        </div>
    </div>

    <!-- Info Detail Card -->
    <div class="card-stack" style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card" style="padding: 24px; background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white; border: none;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h4 style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.6); margin-bottom: 8px;">Status Akademik Aktif</h4>
                    <h2 style="font-size: 28px; font-weight: 700; margin-bottom: 4px;"><?php echo htmlspecialchars($_SESSION['semester']); ?></h2>
                    <p style="color: rgba(255,255,255,0.8);"><?php echo htmlspecialchars($_SESSION['academic_year']); ?></p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 11px; background: rgba(255,255,255,0.1); padding: 4px 12px; border-radius: 100px; margin-bottom: 15px; display: inline-block;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" style="vertical-align: middle; margin-right: 4px;"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg>
                        TERKONFIGURASI
                    </div>
                    <div style="font-size: 32px; font-weight: 800; color: #10b981;">
                        <?php 
                        // Count subjects mapped for THIS semester
                        $stmt = $conn->prepare("SELECT COUNT(DISTINCT subject_id) as total FROM class_subjects WHERE semester = ? AND academic_year = ?");
                        $stmt->bind_param("ss", $_SESSION['semester'], $_SESSION['academic_year']);
                        $stmt->execute();
                        echo $stmt->get_result()->fetch_assoc()['total'];
                        ?>
                        <span style="font-size: 14px; font-weight: 400; color: rgba(255,255,255,0.6); margin-left: 4px;">Mapel Aktif</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card" style="padding: 0;">
            <div class="card-header" style="padding: 20px 24px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 18px; font-weight: 700;">Detail Profil Lembaga</h3>
                <?php if ($is_edit): ?>
                    <span class="badge badge-warning">Mode Edit</span>
                <?php endif; ?>
            </div>
            
            <div class="card-body">
                <?php if ($is_edit): ?>
                <form method="POST" style="padding: 24px;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group"><label>NSM</label><input type="text" name="nsm" class="form-control" value="<?php echo htmlspecialchars($settings['nsm'] ?? ''); ?>" required></div>
                        <div class="form-group"><label>NPSN</label><input type="text" name="npsn" class="form-control" value="<?php echo htmlspecialchars($settings['npsn'] ?? ''); ?>" required></div>
                    </div>
                    <div class="form-group"><label>Alamat Lengkap</label><input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($settings['address'] ?? ''); ?>" required></div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group"><label>Kecamatan</label><input type="text" name="district" class="form-control" value="<?php echo htmlspecialchars($settings['district'] ?? ''); ?>" required></div>
                        <div class="form-group"><label>Kabupaten/Kota</label><input type="text" name="city" class="form-control" value="<?php echo htmlspecialchars($settings['city'] ?? ''); ?>" required></div>
                    </div>
                    <div style="margin-top: 10px; display: flex; gap: 12px;">
                        <button type="submit" name="save_profile" class="btn btn-primary">Simpan Perubahan</button>
                        <a href="dashboard.php" class="btn btn-outline">Batal</a>
                    </div>
                </form>
                <?php else: ?>
                <table class="table-detail">
                    <tbody>
                        <tr><td>Nama Lembaga</td><td><strong>PP NURUL IMAN AL HASANAH</strong></td></tr>
                        <tr><td>NSM</td><td><?php echo htmlspecialchars($settings['nsm'] ?? ''); ?></td></tr>
                        <tr><td>NPSN</td><td><?php echo htmlspecialchars($settings['npsn'] ?? ''); ?></td></tr>
                        <tr><td>Alamat</td><td><?php echo htmlspecialchars($settings['address'] ?? ''); ?></td></tr>
                        <tr><td>Kecamatan</td><td><?php echo htmlspecialchars($settings['district'] ?? ''); ?></td></tr>
                        <tr><td>Kabupaten/Kota</td><td><?php echo htmlspecialchars($settings['city'] ?? ''); ?></td></tr>
                        <tr><td>Pengasuh Pondok</td><td><?php echo htmlspecialchars($settings['headmaster_name'] ?? '-'); ?></td></tr>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<?php require_once 'includes/footer.php'; ?>
