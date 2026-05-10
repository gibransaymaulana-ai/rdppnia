<?php
session_start();
if (!isset($_SESSION['user_id'])) die("Unauthorized");
$is_admin = $_SESSION['role'] === 'admin';
$is_walikelas = $_SESSION['role'] === 'walikelas' || (isset($_SESSION['is_walikelas']) && $_SESSION['is_walikelas']);
if (!$is_admin && !$is_walikelas) die("Unauthorized role");

require_once 'includes/db.php';

$settings = $conn->query("SELECT * FROM settings WHERE id=1")->fetch_assoc();

$student_id = $_GET['student_id'] ?? 0;
$semester = $_GET['semester'] ?? $settings['active_semester'];
$academic_year = $_GET['academic_year'] ?? $settings['active_year'];

$stmt = $conn->prepare("SELECT s.*, c.name as class_name, c.walikelas_id, u.name as walikelas_name FROM students s JOIN classes c ON s.class_id = c.id LEFT JOIN users u ON c.walikelas_id = u.id WHERE s.id = ?");
if (!$stmt) die("SQL Error (student): " . $conn->error);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) die("Santri tidak ditemukan");

// Improved Grade Fetching: Sync with all grades for this period
$grades_query = "
    SELECT sub.name as subject_name, sub.kkm, 
           sub.has_tulis, sub.has_praktek, sub.has_lisan,
           g.score_tulis, g.score_praktek, g.score_lisan,
           sub.id as subject_id
    FROM subjects sub
    LEFT JOIN grades g ON sub.id = g.subject_id AND g.student_id = ? AND g.semester = ? AND g.academic_year = ?
    WHERE g.id IS NOT NULL 
       OR sub.id IN (SELECT subject_id FROM class_subjects WHERE class_id = ? AND semester = ? AND academic_year = ?)
    ORDER BY sub.id ASC
";
$stmt = $conn->prepare($grades_query);
if (!$stmt) die("SQL Error (grades): " . $conn->error);
$stmt->bind_param("isssis", $student_id, $semester, $academic_year, $student['class_id'], $semester, $academic_year);
$stmt->execute();
$grades_res = $stmt->get_result();
$grades_data = [];
while ($g = $grades_res->fetch_assoc()) {
    $grades_data[] = $g;
}

// Fetch Notes
$stmt = $conn->prepare("SELECT * FROM walikelas_notes WHERE student_id = ? AND semester = ? AND academic_year = ?");
if (!$stmt) die("SQL Error (notes): " . $conn->error);
$stmt->bind_param("iss", $student_id, $semester, $academic_year);
$stmt->execute();
$notes = $stmt->get_result()->fetch_assoc();

function getResultText($score, $kkm) {
    if ($score === null || $score === '') return '-';
    if ($score >= 90) return 'Sangat Baik (ممتاز)';
    if ($score >= 75) return 'Baik (جيد جدا)';
    if ($score >= 60) return 'Cukup (جيد)';
    return 'Kurang (مقبول)';
}

function getArabicSubject($name) {
    $map = [
        'Jaziriyah' => 'جزرياه',
        'Fiqih' => 'الفقه (الرياض البديعة)',
        'Bahasa Inggris' => 'اللغة إنجليزية',
        'Adab Alam' => 'آداب العالم',
        'Al-Qur\'an' => 'القرآن',
        'Sholat Jenazah' => 'ممارسة الصلاة الجنازة'
    ];
    return $map[$name] ?? $name;
}

function getCharacterPredicate($grade) {
    if (!$grade) $grade = 'B';
    return strtoupper($grade);
}
?>
<!DOCTYPE html>
<html lang="id" dir="ltr">
<head>
    <meta charset="UTF-8">
    <title>Rapor - <?php echo htmlspecialchars($student['name']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&family=Inter:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @page { 
            size: A4; 
            margin: 0;
        }
        body { 
            font-family: 'Inter', sans-serif; 
            color: #000; 
            background: #fff; 
            margin: 0; 
            padding: 0;
            font-size: 11px;
            line-height: 1.2;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .outer-border {
            width: 190mm;
            height: 275mm;
            margin: 8mm auto;
            border: 2px solid #000 !important; /* Thick outer line */
            padding: 3px;
            box-sizing: border-box;
            background: #fff;
        }
        .container {
            width: 100%;
            height: 100%;
            border: 1px solid #000 !important; /* Thin inner line */
            padding: 8mm 10mm;
            box-sizing: border-box;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .content-inner {
            width: 90%;
            margin: 0 auto;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        /* Watermark */
        .watermark {
            position: absolute;
            top: 55%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 320px;
            opacity: 0.12;
            z-index: 0;
            pointer-events: none;
        }
        .content-wrapper {
            position: relative;
            z-index: 1;
        }
        .header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 8px;
            position: relative;
        }
        .header-logo {
            width: 70px;
            height: auto;
            flex-shrink: 0;
            mix-blend-mode: multiply;
        }
        .header-text {
            flex-grow: 1;
            text-align: center;
            padding-right: 70px; 
        }
        .header-text h1 {
            font-family: 'Amiri', serif;
            font-size: 22px;
            margin: 0;
            color: #000;
            line-height: 1.1;
        }
        .header-text p {
            margin: 2px 0 0;
            font-size: 10px;
            font-weight: normal;
        }

        .title-section {
            text-align: center;
            margin-bottom: 5px;
        }
        .title-section h2 {
            font-family: 'Amiri', serif;
            font-size: 18px;
            margin: 0;
        }
        .title-section p {
            margin: 0;
            font-weight: bold;
            font-size: 11px;
        }

        .student-info {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 8px;
        }
        .info-table {
            border-collapse: collapse;
        }
        .info-table td {
            padding: 0 5px;
            font-weight: bold;
            font-size: 10px;
        }
        .info-table .label-ar {
            font-family: 'Amiri', serif;
            font-size: 13px;
            text-align: right;
            width: 100px;
        }

        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .main-table th, .main-table td {
            border: 1px solid #000;
            padding: 3px;
        }
        .main-table th {
            background: #fff;
            text-align: center;
            font-family: 'Amiri', serif;
            font-size: 12px;
        }
        .main-table td {
            text-align: center;
            height: 18px;
            font-size: 10px;
        }
        .main-table .subject-col {
            text-align: left;
            padding-left: 8px;
            width: 40%;
        }
        .main-table .subject-ar {
            font-family: 'Amiri', serif;
            font-size: 13px;
            float: right;
        }
        .main-table .group-row {
            background: #f5f5f5;
            text-align: left !important;
            font-weight: bold;
            padding-left: 8px;
            font-size: 9px;
            height: 16px;
        }

        .bottom-sections {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
            margin-bottom: 8px;
        }
        .sub-table {
            width: 100%;
            border-collapse: collapse;
        }
        .sub-table th, .sub-table td {
            border: 1px solid #000;
            padding: 5px;
            text-align: center;
            vertical-align: middle;
        }
        .sub-table th {
            font-family: 'Amiri', serif;
            font-size: 13px;
            background: #fcfcfc;
        }
        .sub-table td {
            font-size: 10px;
            height: 16px;
        }
        .sub-table td.label-col {
            text-align: left;
            font-size: 10px;
            width: 65%;
        }
        .sub-table td.label-col span {
            float: right;
            font-family: 'Amiri', serif;
            font-size: 11px;
        }

        .notes-section {
            border: 1px solid #000;
            padding: 4px;
            margin-bottom: 5px;
            min-height: 30px;
            font-size: 10px;
        }
        .notes-section strong {
            font-family: 'Amiri', serif;
            font-size: 12px;
        }

        .footer-container {
            margin-top: auto;
            padding-top: 10px;
            z-index: 10;
            position: relative;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .footer-table td {
            vertical-align: top;
            text-align: center;
            border: none;
            padding: 0;
        }
        .sig-title-ar {
            font-family: 'Amiri', serif;
            font-size: 14px;
            font-weight: bold;
            margin: 0;
            line-height: 1.1;
        }
        .sig-desc-id {
            font-size: 9px;
            margin: 1px 0;
            display: block;
        }
        .sig-role {
            font-size: 9px;
            margin: 0;
            font-weight: normal;
        }
        .sig-space {
            height: 80px;
        }
        .sig-name {
            font-weight: bold;
            text-decoration: underline;
            font-size: 11px;
            display: inline-block;
        }
        .sig-date {
            font-size: 9px;
            margin-top: 2px;
            display: block;
            font-weight: normal;
        }

        @media print {
            .no-print { display: none; }
            .container { border: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="padding: 10px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #000; color: #fff; border: none; border-radius: 4px; font-weight: bold;">Cetak Rapor</button>
    </div>

    <div class="outer-border">
        <div class="container">
            <img src="<?php echo htmlspecialchars($settings['logo']); ?>" class="watermark" alt="Watermark">
            
            <div class="content-inner">
                <div class="content-wrapper">
                    <div class="header">
                <img src="<?php echo htmlspecialchars($settings['logo']); ?>" class="header-logo" alt="Logo">
                <div class="header-text">
                    <h1>المعهد نور الإيمان الحسنة السلفي والحديث</h1>
                    <p>Jl. Raya Leuwiliang-Karacak KM.03 No. 11 Kp.Geledug Ds.Barengkok Kec.Leuwiliang.Bogor 16640</p>
                </div>
            </div>

            <div class="title-section">
                <h2>تقرير نتائج الإمتحان</h2>
                <p>(LAPORAN HASIL UJIAN)</p>
            </div>

            <div class="student-info">
                <table class="info-table">
                    <tr>
                        <td style="text-align: right;"><strong><?php echo htmlspecialchars($student['name']); ?></strong></td>
                        <td style="padding: 0 5px;">:</td>
                        <td class="label-ar">اسم</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><strong><?php echo htmlspecialchars($student['class_name']); ?></strong></td>
                        <td style="padding: 0 5px;">:</td>
                        <td class="label-ar">قسم</td>
                    </tr>
                    <tr>
                        <td style="text-align: right;"><strong><?php echo $semester == 'Ganjil' ? 'I (Semester I)' : 'II (Semester II)'; ?></strong></td>
                        <td style="padding: 0 5px;">:</td>
                        <td class="label-ar">فصل دراسي</td>
                    </tr>
                </table>
            </div>

            <table class="main-table">
                <thead>
                    <tr>
                        <th width="30">رقم<br>No</th>
                        <th>المواضيع<br>Mata Pelajaran</th>
                        <th width="45">الحد<br>KKM</th>
                        <th width="45">علامة<br>Nilai</th>
                        <th width="140">نتائج التعلم<br>Hasil Belajar</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Ujian Tulis -->
                    <tr>
                        <td colspan="5" class="group-row">
                            <span style="float: right; font-family: 'Amiri', serif;">امتحان التحريري</span>
                            (Ujian Tulis)
                        </td>
                    </tr>
                    <?php 
                    $i = 1;
                    $total_score = 0;
                    $count = 0;
                    foreach ($grades_data as $g) {
                        if ($g['has_tulis']) {
                            $total_score += $g['score_tulis'];
                            $count++;
                    ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td class="subject-col">
                            <span class="subject-ar"><?php echo getArabicSubject($g['subject_name']); ?></span>
                            <?php echo htmlspecialchars($g['subject_name']); ?>
                        </td>
                        <td><?php echo $g['kkm']; ?></td>
                        <td><?php echo $g['score_tulis'] !== null ? $g['score_tulis'] : '-'; ?></td>
                        <td><?php echo getResultText($g['score_tulis'], $g['kkm']); ?></td>
                    </tr>
                    <?php 
                        }
                    }
                    ?>

                    <!-- Ujian Lisan -->
                    <tr>
                        <td colspan="5" class="group-row">
                            <span style="float: right; font-family: 'Amiri', serif;">امتحان الشفوي</span>
                            (Ujian Lisan)
                        </td>
                    </tr>
                    <?php 
                    $i = 1;
                    foreach ($grades_data as $g) {
                        if ($g['has_lisan']) {
                            $total_score += $g['score_lisan'];
                            $count++;
                    ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td class="subject-col">
                            <span class="subject-ar"><?php echo getArabicSubject($g['subject_name']); ?></span>
                            <?php echo htmlspecialchars($g['subject_name']); ?>
                        </td>
                        <td><?php echo $g['kkm']; ?></td>
                        <td><?php echo $g['score_lisan'] !== null ? $g['score_lisan'] : '-'; ?></td>
                        <td><?php echo getResultText($g['score_lisan'], $g['kkm']); ?></td>
                    </tr>
                    <?php 
                        }
                    }
                    ?>

                    <!-- Ujian Praktik -->
                    <tr>
                        <td colspan="5" class="group-row">
                            <span style="float: right; font-family: 'Amiri', serif;">إمتحان الممارسي</span>
                            (Ujian Praktik)
                        </td>
                    </tr>
                    <?php 
                    $i = 1;
                    foreach ($grades_data as $g) {
                        if ($g['has_praktek']) {
                            $total_score += $g['score_praktek'];
                            $count++;
                    ?>
                    <tr>
                        <td><?php echo $i++; ?></td>
                        <td class="subject-col">
                            <span class="subject-ar"><?php echo getArabicSubject($g['subject_name']); ?></span>
                            <?php echo htmlspecialchars($g['subject_name']); ?>
                        </td>
                        <td><?php echo $g['kkm']; ?></td>
                        <td><?php echo $g['score_praktek'] !== null ? $g['score_praktek'] : '-'; ?></td>
                        <td><?php echo getResultText($g['score_praktek'], $g['kkm']); ?></td>
                    </tr>
                    <?php 
                        }
                    }
                    ?>

                    <tr>
                        <td colspan="3" style="text-align: right; font-weight: bold; padding-right: 20px;">
                            <span style="float: right; font-family: 'Amiri', serif; margin-left: 10px;">مجموع النتائج</span>
                            (Jumlah Nilai)
                        </td>
                        <td style="font-weight: bold;"><?php echo $total_score; ?></td>
                        <td></td>
                    </tr>
                </tbody>
            </table>

            <div class="bottom-sections">
                <!-- Activities -->
                <div>
                    <table class="sub-table">
                        <thead>
                            <tr>
                                <th>أنشطة<br>(Kegiatan)</th>
                                <th>نتيجة<br>(Nilai)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td class="label-col">Balagan <span>تعليم</span></td><td><?php echo getCharacterPredicate($notes['balagan'] ?? 'B'); ?></td></tr>
                            <tr><td class="label-col">Jama'ah <span>جماعة</span></td><td><?php echo getCharacterPredicate($notes['jamaah'] ?? 'B'); ?></td></tr>
                            <tr><td class="label-col">Riyadhoh <span>رياضة</span></td><td><?php echo getCharacterPredicate($notes['riyadhoh'] ?? 'B'); ?></td></tr>
                            <tr><td class="label-col">Muhadoroh <span>محاضرة</span></td><td><?php echo getCharacterPredicate($notes['muhadoroh'] ?? 'B'); ?></td></tr>
                            <tr><td class="label-col">Barjanzi <span>بارزنجي</span></td><td><?php echo getCharacterPredicate($notes['barjanzi'] ?? 'B'); ?></td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Behavior -->
                <div>
                    <table class="sub-table">
                        <thead>
                            <tr>
                                <th>معاملة<br>(Perilaku)</th>
                                <th>نتيجة<br>(Nilai)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td class="label-col">Kedisiplinan <span>تنظيمية</span></td><td><?php echo getCharacterPredicate($notes['kedisiplinan'] ?? 'B'); ?></td></tr>
                            <tr><td class="label-col">Kerajinan <span>تنشيطة</span></td><td><?php echo getCharacterPredicate($notes['kerajinan'] ?? 'B'); ?></td></tr>
                            <tr><td class="label-col">Kerapihan <span>تهذيبية</span></td><td><?php echo getCharacterPredicate($notes['kerapihan'] ?? 'B'); ?></td></tr>
                            <tr><td class="label-col">Kebersihan <span>تنظيفية</span></td><td><?php echo getCharacterPredicate($notes['kebersihan'] ?? 'B'); ?></td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Attendance -->
                <div>
                    <table class="sub-table">
                        <thead>
                            <tr>
                                <th>غياب<br>(Kehadiran)</th>
                                <th>مرات<br>(Kali)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td class="label-col">Sakit <span>مريض</span></td><td><?php echo $notes['sakit'] ?? 0; ?></td></tr>
                            <tr><td class="label-col">Izin <span>اذن</span></td><td><?php echo $notes['izin'] ?? 0; ?></td></tr>
                            <tr><td class="label-col">Pulang <span>رجوع</span></td><td><?php echo $notes['pulang'] ?? 0; ?></td></tr>
                            <tr><td class="label-col">Alpa <span>غائب</span></td><td><?php echo $notes['alpa'] ?? 0; ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="notes-section">
                <strong>تقارير (Catatan) :</strong><br>
                <?php echo nl2br(htmlspecialchars($notes['catatan'] ?? '')); ?>
            </div>

            <div class="footer-container">
                <table class="footer-table">
                    <tr>
                        <!-- Wali Santri (Kiri) -->
                        <td>
                            <p class="sig-title-ar">ولي الطالب</p>
                            <span class="sig-desc-id">(Wali Santri)</span>
                            <div class="sig-space"></div>
                            <span class="sig-name">..........................</span>
                        </td>

                        <!-- Wali Kelas (Tengah) -->
                        <td>
                            <p class="sig-title-ar">مطالعات</p>
                            <span class="sig-desc-id">(Mengetahui)</span>
                            <p class="sig-role">ضباط الصف (Wali Kelas)</p>
                            <div class="sig-space"></div>
                            <span class="sig-name"><?php echo htmlspecialchars($student['walikelas_name']); ?></span>
                        </td>

                        <!-- Pengasuh Pesantren (Kanan) -->
                        <td>
                            <p class="sig-title-ar">مربية المعهد</p>
                            <span class="sig-desc-id">(Pengasuh Pesantren)</span>
                            <div class="sig-space"></div>
                            <span class="sig-name"><?php echo htmlspecialchars($settings['headmaster_name'] ?? 'Umi Ustz Hj Ani S.Pd.I'); ?></span>
                            <span class="sig-date">
                                <?php echo htmlspecialchars($settings['print_place']); ?>, 
                                <?php echo !empty($settings['print_date']) ? date('d F Y', strtotime($settings['print_date'])) : date('d F Y'); ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
    <script>
        window.onload = function() {
            window.print();
            // Optional: Close window after printing
            window.onafterprint = function() {
                window.close();
            };
        };
    </script>
</body>
</html>
