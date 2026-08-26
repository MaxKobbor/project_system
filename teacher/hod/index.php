<?php
require_once '../../config/db.php';
require_once '../config_t/auth_check.php';

// Verify login and role = 0 (HOD)
check_teacher_auth(0);

// Fetch summary counts for HOD Dashboard
$counts = [
    'teachers' => 0,
    'pending_projects' => 0,
    'groups' => 0,
    'submissions' => 0
];

if (isset($pdo)) {
    $counts['teachers'] = $pdo->query("SELECT COUNT(*) FROM teachers")->fetchColumn();
    $counts['pending_projects'] = $pdo->query("SELECT COUNT(*) FROM `groups` WHERE `status` = 0")->fetchColumn();
    $counts['groups'] = $pdo->query("SELECT COUNT(*) FROM `groups`")->fetchColumn();
    $counts['submissions'] = $pdo->query("SELECT COUNT(*) FROM submissions")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <link rel="icon" type="image/png" href="../../img/new_logo_svc_temp.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลักหัวหน้าแผนก - ระบบบันทึกและตรวจสอบโครงการ</title>
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
                <h2 class="fw-bold mb-1">ยินดีต้อนรับ, <?= htmlspecialchars($_SESSION['full_name'] ?? 'หัวหน้าแผนก') ?></h2>
                <p class="text-muted mb-0">แผงควบคุมระบบสำหรับหัวหน้าแผนกธุรกิจดิจิทัลและเทคโนโลยีสารสนเทศ</p>
            </div>

            <!-- Stats Grid -->
            <div class="row g-4 mb-4">
                <div class="col-12 col-md-6 col-lg-3">
                    <a href="manage_teachers.php" class="stat-card-link">
                        <div class="stat-card">
                            <div class="stat-card-inner">
                                <div class="stat-info">
                                    <h3 class="stat-title">คุณครูในแผนก</h3>
                                    <p class="stat-subtitle">จัดการและสิทธิ์คุณครู</p>
                                </div>
                                <div class="stat-number"><?= $counts['teachers'] ?></div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <a href="project_approvals.php" class="stat-card-link">
                        <div class="stat-card">
                            <div class="stat-card-inner">
                                <div class="stat-info">
                                    <h3 class="stat-title">รออนุมัติโครงงาน</h3>
                                    <p class="stat-subtitle">โครงงานที่ยังไม่อนุมัติ</p>
                                </div>
                                <div class="stat-number"><?= $counts['pending_projects'] ?></div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <a href="project_approvals.php" class="stat-card-link">
                        <div class="stat-card">
                            <div class="stat-card-inner">
                                <div class="stat-info">
                                    <h3 class="stat-title">กลุ่มนักเรียนทั้งหมด</h3>
                                    <p class="stat-subtitle">กลุ่มนักเรียนในระบบ</p>
                                </div>
                                <div class="stat-number"><?= $counts['groups'] ?></div>
                            </div>
                        </div>
                    </a>
                </div>

                <div class="col-12 col-md-6 col-lg-3">
                    <a href="project_approvals.php" class="stat-card-link">
                        <div class="stat-card">
                            <div class="stat-card-inner">
                                <div class="stat-info">
                                    <h3 class="stat-title">งานที่ส่งแล้ว</h3>
                                    <p class="stat-subtitle">รายงานส่งงานของนักเรียน</p>
                                </div>
                                <div class="stat-number"><?= $counts['submissions'] ?></div>
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
                            <i data-lucide="users" class="text-primary"></i> จัดการคุณครู
                        </h4>
                        <p class="text-muted">เพิ่มบัญชีคุณครูผู้สอน ปรับแต่งสิทธิ์ตำแหน่ง และเปิด/ปิดการใช้งานบัญชีผู้ใช้ในแผนก</p>
                        <a href="manage_teachers.php" class="btn btn-primary" style="background:#61BFED; border:none;">
                            ไปที่หน้าจัดการคุณครู
                        </a>
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="manage-card p-4">
                        <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                            <i data-lucide="check-square" class="text-warning"></i> อนุมัติหัวข้อโครงงาน
                        </h4>
                        <p class="text-muted">ตรวจสอบชื่อโครงงานที่เสนอโดยกลุ่มนักเรียนและคุณครูที่ปรึกษา และดำเนินการกดอนุมัติสิทธิ์</p>
                        <a href="project_approvals.php" class="btn btn-warning text-white" style="border:none;">
                            ไปที่หน้าอนุมัติโครงงาน
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