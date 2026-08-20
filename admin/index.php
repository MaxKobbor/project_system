<?php
include_once '../config/db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Fetch counts
$counts = [
    'teachers' => 0,
    'groups' => 0,
    'admins' => 0,
    'branches' => 0,
    'classrooms' => 0,
    'assignments' => 0,
    'years' => 0,
    'students' => 0
];

try {
    $counts['teachers'] = $pdo->query("SELECT COUNT(*) FROM `teachers`")->fetchColumn();
    $counts['groups'] = $pdo->query("SELECT COUNT(*) FROM `groups`")->fetchColumn();
    $counts['admins'] = $pdo->query("SELECT COUNT(*) FROM `admins`")->fetchColumn();
    $counts['branches'] = $pdo->query("SELECT COUNT(*) FROM `branches`")->fetchColumn();
    $counts['classrooms'] = $pdo->query("SELECT COUNT(*) FROM `classrooms`")->fetchColumn();
    $counts['assignments'] = $pdo->query("SELECT COUNT(*) FROM `assignments`")->fetchColumn();
    $counts['years'] = $pdo->query("SELECT COUNT(*) FROM `academic_years`")->fetchColumn();
    $counts['students'] = $pdo->query("SELECT SUM(
        (std_1 IS NOT NULL AND std_1 != '') + 
        (std_2 IS NOT NULL AND std_2 != '') + 
        (std_3 IS NOT NULL AND std_3 != '') + 
        (std_4 IS NOT NULL AND std_4 != '')
    ) FROM `groups`")->fetchColumn() ?? 0;
} catch (PDOException $e) {
    // Keep zeros if error
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/png" href="../img/new_logo_svc_temp.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบผู้ดูแล - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="nav.css" />
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="mx-auto w-100" style="padding-top: 1rem;">
                <div class="row g-4">
                    <!-- แถวที่ 1: ครู, แอดมิน, ปีการศึกษา -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="manage_teachers.php" class="stat-card-link">
                            <div class="stat-card">
                                <div class="stat-card-inner">
                                    <div class="stat-info">
                                        <h3 class="stat-title">คุณครู</h3>
                                        <p class="stat-subtitle">คุณครูทั้งหมดในแผนก</p>
                                    </div>
                                    <div class="stat-number"><?= $counts['teachers'] ?></div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="manage_admins.php" class="stat-card-link">
                            <div class="stat-card">
                                <div class="stat-card-inner">
                                    <div class="stat-info">
                                        <h3 class="stat-title">แอดมิน</h3>
                                        <p class="stat-subtitle">แอดมินทั้งหมด</p>
                                    </div>
                                    <div class="stat-number"><?= $counts['admins'] ?></div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="academic_years.php" class="stat-card-link">
                            <div class="stat-card">
                                <div class="stat-card-inner">
                                    <div class="stat-info">
                                        <h3 class="stat-title">ปีการศึกษา</h3>
                                        <p class="stat-subtitle">ปีการศึกษาทั้งหมด</p>
                                    </div>
                                    <div class="stat-number"><?= $counts['years'] ?></div>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- แถวที่ 2: สาขาวิชา, ห้องเรียน, หัวข้องาน -->
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="branches.php" class="stat-card-link">
                            <div class="stat-card">
                                <div class="stat-card-inner">
                                    <div class="stat-info">
                                        <h3 class="stat-title">สาขาวิชา</h3>
                                        <p class="stat-subtitle">สาขาวิชาทั้งหมดในแผนก</p>
                                    </div>
                                    <div class="stat-number"><?= $counts['branches'] ?></div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="classrooms.php" class="stat-card-link">
                            <div class="stat-card">
                                <div class="stat-card-inner">
                                    <div class="stat-info">
                                        <h3 class="stat-title">ห้องเรียน</h3>
                                        <p class="stat-subtitle">ห้องเรียนทั้งหมดในแผนก</p>
                                    </div>
                                    <div class="stat-number"><?= $counts['classrooms'] ?></div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="assignments.php" class="stat-card-link">
                            <div class="stat-card">
                                <div class="stat-card-inner">
                                    <div class="stat-info">
                                        <h3 class="stat-title">หัวข้องาน</h3>
                                        <p class="stat-subtitle">หัวข้องานทั้งหมด</p>
                                    </div>
                                    <div class="stat-number"><?= $counts['assignments'] ?></div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- แถวที่ 3: กลุ่มนักเรียน, นักเรียน (จัดให้อยู่กึ่งกลาง) -->
                <div class="row g-4 justify-content-center mt-0">
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="groups_view.php" class="stat-card-link">
                            <div class="stat-card">
                                <div class="stat-card-inner">
                                    <div class="stat-info">
                                        <h3 class="stat-title">กลุ่มนักเรียน</h3>
                                        <p class="stat-subtitle">กลุ่มนักเรียนทั้งหมดในแผนก</p>
                                    </div>
                                    <div class="stat-number"><?= $counts['groups'] ?></div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-12 col-md-6 col-lg-4">
                        <a href="groups_view.php" class="stat-card-link">
                            <div class="stat-card">
                                <div class="stat-card-inner">
                                    <div class="stat-info">
                                        <h3 class="stat-title">นักเรียน</h3>
                                        <p class="stat-subtitle">นักเรียนทั้งหมดในแผนก</p>
                                    </div>
                                    <div class="stat-number"><?= $counts['students'] ?? 0 ?></div>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="nav.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>

</html>