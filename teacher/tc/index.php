<?php
require_once '../../config/db.php';
require_once '../config_t/auth_check.php';

// Verify login and role = 1 (TC - ครูผู้สอน)
check_teacher_auth(1);

$teacherId = $_SESSION['teacher_id'];

// Fetch summary counts for TC Dashboard
$counts = [
    'my_groups' => 0,
    'all_groups' => 0,
    'pending_check' => 0,
    'passed_submissions' => 0
];

if (isset($pdo)) {
    // กลุ่มที่ครูเป็นที่ปรึกษา
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE `teacher_id` = ?");
    $stmt->execute([$teacherId]);
    $counts['my_groups'] = $stmt->fetchColumn();

    // กลุ่มทั้งหมด
    $counts['all_groups'] = $pdo->query("SELECT COUNT(*) FROM `groups`")->fetchColumn();

    // งานที่รอตรวจในกลุ่มที่ปรึกษา
    $stmtPending = $pdo->prepare("SELECT COUNT(*) FROM `submissions` s JOIN `groups` g ON s.group_id = g.group_id WHERE g.teacher_id = ? AND (s.status_1 IS NULL OR s.status_1 = 0)");
    $stmtPending->execute([$teacherId]);
    $counts['pending_check'] = $stmtPending->fetchColumn();

    // งานที่อนุมัติผ่านแล้ว
    $stmtPassed = $pdo->prepare("SELECT COUNT(*) FROM `submissions` s JOIN `groups` g ON s.group_id = g.group_id WHERE g.teacher_id = ? AND s.status_1 = 1");
    $stmtPassed->execute([$teacherId]);
    $counts['passed_submissions'] = $stmtPassed->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <link rel="icon" type="image/png" href="../../img/new_logo_svc_temp.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลักครูผู้สอน - ระบบบันทึกและตรวจสอบโครงการ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="header.css?v=<?= time() ?>">
    <link rel="stylesheet" href="nav.css?v=<?= time() ?>">
    <link rel="stylesheet" href="manage.css?v=<?= time() ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="welcome-box mb-4">
                <h2 class="fw-bold mb-1">ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['full_name'] ?? 'ครูผู้สอน') ?></h2>
                <p class="text-muted mb-0">แผงควบคุมระบบสำหรับครูผู้สอนและครูที่ปรึกษาโครงการ</p>
            </div>

            <!-- Stats Grid -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6 col-lg-3">
                    <a href="manage_groups.php" class="stat-card-link">
                        <div class="stat-card">
                            <div class=stat-card-inner>
                                <div class="stat-info">
                                    <h3 class="stat-title">กลุ่มในปรึกษา</h3>
                                    <p class="stat-subtitle">กลุ่มนักเรียนที่ดูแล</p>
                                </div>
                                <div class="stat-number"><?= $counts['my_groups'] ?></div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <a href="inspect_submissions.php" class="stat-card-link">
                        <div class="stat-card">
                            <div class=stat-card-inner>
                                <div class="stat-info">
                                    <h3 class="stat-title">รอตรวจงาน</h3>
                                    <p class="stat-subtitle">งานนักเรียนรอการตรวจ</p>
                                </div>
                                <div class="stat-number"><?= $counts['pending_check'] ?></div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <a href="inspect_submissions.php" class="stat-card-link">
                        <div class="stat-card">
                            <div class=stat-card-inner>
                                <div class="stat-info">
                                    <h3 class="stat-title">ตรวจผ่านแล้ว</h3>
                                    <p class="stat-subtitle">งานที่อนุมัติเรียบร้อย</p>
                                </div>
                                <div class="stat-number"><?= $counts['passed_submissions'] ?></div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <a href="manage_groups.php" class="stat-card-link">
                        <div class="stat-card">
                            <div class=stat-card-inner>
                                <div class="stat-info">
                                    <h3 class="stat-title">กลุ่มนักเรียนทั้งหมด</h3>
                                    <p class="stat-subtitle">ทุกกลุ่มในแผนก</p>
                                </div>
                                <div class="stat-number"><?= $counts['all_groups'] ?></div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Quick Action Cards -->
            <div class="row g-4">
                <div class="col-12 col-md-6">
                    <div class="manage-card p-4">
                        <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                            <i data-lucide="users-round" class="text-primary"></i> จัดการกลุ่มนักเรียน
                        </h4>
                        <p class="text-muted">สร้างกลุ่มนักเรียน กำหนดชื่อโครงงาน และจัดสรรเพิ่มสมาชิกนักเรียนเข้ากลุ่มที่ดูแล</p>
                        <a href="manage_groups.php" class="btn btn-primary" style="background:#61BFED; border:none;">
                            ไปที่หน้าจัดการกลุ่มนักเรียน
                        </a>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="manage-card p-4">
                        <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                            <i data-lucide="clipboard-check" class="text-success"></i> ตรวจและส่งคืนงานนักเรียน
                        </h4>
                        <p class="text-muted">ตรวจไฟล์รายงานโครงงานที่นักเรียนส่งมา อนุมัติงานผ่าน หรือส่งตีกลับงานพร้อมระบุคอมเมนต์ข้อแก้ไข</p>
                        <a href="inspect_submissions.php" class="btn btn-success" style="border:none;">
                            ไปที่หน้าตรวจงานนักเรียน
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();
    </script>
    <script src="nav.js"></script>
</body>

</html>