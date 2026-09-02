<?php
require_once '../config/db.php';
session_start();

// Check if student is logged in
if (!isset($_SESSION['group_id'])) {
    header("Location: ../login.php");
    exit();
}

$group_id = $_SESSION['group_id'];

// Fetch group details including teacher
$stmt = $pdo->prepare("SELECT g.*, t.full_name AS teacher_name, c.class_name, b.branch_name, y.year_name AS year, c.year_id
                       FROM `groups` g
                       LEFT JOIN `teachers` t ON g.teacher_id = t.teacher_id
                       LEFT JOIN `classrooms` c ON g.class_id = c.class_id
                       LEFT JOIN `branches` b ON c.branch_id = b.branch_id
                       LEFT JOIN `academic_years` y ON c.year_id = y.year_id
                       WHERE g.group_id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch recent submissions
$subStmt = $pdo->prepare("SELECT s.*, a.assignment_name 
                          FROM `submissions` s
                          JOIN `assignments` a ON s.assignment_id = a.assignment_id
                          WHERE s.group_id = ?
                          ORDER BY s.submit_date DESC LIMIT 5");
$subStmt->execute([$group_id]);
$submissions = $subStmt->fetchAll(PDO::FETCH_ASSOC);

// Resolve academic year
$year_id = $group['year_id'] ?? null;
if (empty($year_id)) {
    $activeYearStmt = $pdo->query("SELECT year_id FROM `academic_years` WHERE status = 1 ORDER BY year_name DESC LIMIT 1");
    $year_id = $activeYearStmt ? ($activeYearStmt->fetchColumn() ?: 0) : 0;
}

// Check if any assignments are open but not submitted
$assignStmt = $pdo->prepare("SELECT a.* 
                             FROM `assignments` a
                             LEFT JOIN `submissions` s ON a.assignment_id = s.assignment_id AND s.group_id = ?
                             WHERE a.status = 1 AND a.year_id = ? AND s.submission_id IS NULL");
$assignStmt->execute([$group_id, $year_id]);
$pending_assignments = $assignStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Progress
$totalAssignStmt = $pdo->prepare("SELECT COUNT(*) FROM `assignments` WHERE year_id = ?");
$totalAssignStmt->execute([$year_id]);
$total_assignments = $totalAssignStmt->fetchColumn();

$completedAssignStmt = $pdo->prepare("SELECT COUNT(*) FROM `submissions` s 
                                      JOIN `assignments` a ON s.assignment_id = a.assignment_id 
                                      WHERE s.group_id = ? AND a.year_id = ? AND s.status_1 = 1");
$completedAssignStmt->execute([$group_id, $year_id]);
$completed_assignments = $completedAssignStmt->fetchColumn();

$progress_percent = $total_assignments > 0 ? round(($completed_assignments / $total_assignments) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <link rel="icon" type="image/png" href="../img/new_logo_svc_temp.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>นักเรียน - หน้าหลัก (Dashboard)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="nav.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .dashboard-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 20px;
        }
        .info-label {
            font-size: 0.9rem;
            color: #777;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <h2 class="mb-4">ข้อมูลโครงงานของคุณ</h2>

            <div class="row">
                <div class="col-md-8">
                    <div class="dashboard-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h4 class="fw-bold text-primary mb-0">
                                <?= htmlspecialchars($group['project_name'] ?? 'ยังไม่ได้กำหนดชื่อโครงงาน') ?>
                            </h4>
                            <?php if ($group['status'] == 1): ?>
                                <span class="status-badge status-approved"><i data-lucide="check-circle" style="width:16px;height:16px; margin-right:4px; display:inline;"></i> อนุมัติแล้ว</span>
                            <?php else: ?>
                                <span class="status-badge status-pending"><i data-lucide="clock" style="width:16px;height:16px; margin-right:4px; display:inline;"></i> รอหัวหน้าแผนกอนุมัติ</span>
                            <?php endif; ?>
                        </div>

                        <div class="row mt-4">
                            <div class="col-sm-6">
                                <div class="info-label">ครูที่ปรึกษาโครงงาน</div>
                                <div class="info-value">
                                    <i data-lucide="user-check" style="width:18px;height:18px; color:#5eb2ff;"></i> 
                                    <?= htmlspecialchars($group['teacher_name'] ?? 'ยังไม่ระบุ') ?>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="info-label">ข้อมูลกลุ่ม/ห้องเรียน</div>
                                <div class="info-value">
                                    <?= htmlspecialchars($group['class_name'] ?? '-') ?> (<?= htmlspecialchars($group['branch_name'] ?? '-') ?>)
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="info-label">สมาชิกในกลุ่ม</div>
                            <div class="info-value" style="font-weight:500;">
                                <ul class="mb-0 ps-3">
                                    <?php for ($i=1; $i<=4; $i++): ?>
                                        <?php if (!empty($group['std_'.$i])): ?>
                                            <li><?= htmlspecialchars($group['std_'.$i]) ?></li>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </ul>
                            </div>
                        </div>

                    </div>

                    <!-- ประวัติการส่งงานล่าสุด -->
                    <div class="dashboard-card mb-4 mt-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0 text-dark">
                                <i data-lucide="history" style="width:20px;height:20px; margin-right:6px; color:#5eb2ff;"></i> 
                                ประวัติการส่งงานล่าสุด
                            </h5>
                        </div>
                        <?php if (empty($submissions)): ?>
                            <div class="alert alert-secondary text-center rounded-3 border-0 mb-0">
                                ยังไม่มีประวัติการส่งงาน
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="border-radius: 8px; overflow: hidden;">
                                    <thead style="background-color: #f8f9fa;">
                                        <tr>
                                            <th class="border-0 text-secondary" style="font-weight:600; padding: 12px 16px;">หัวข้องาน</th>
                                            <th class="border-0 text-secondary" style="font-weight:600; padding: 12px 16px;">วันที่ส่ง</th>
                                            <th class="border-0 text-secondary" style="font-weight:600; padding: 12px 16px;">ไฟล์ที่ส่ง</th>
                                            <th class="border-0 text-secondary" style="font-weight:600; padding: 12px 16px;">สถานะ</th>
                                            <th class="border-0 text-secondary" style="font-weight:600; padding: 12px 16px;">หมายเหตุ</th>
                                        </tr>
                                    </thead>
                                    <tbody class="border-top-0">
                                        <?php foreach ($submissions as $sub): ?>
                                            <tr style="border-bottom: 1px solid #f0f0f0;">
                                                <td class="fw-bold text-dark" style="padding: 16px;"><?= htmlspecialchars($sub['assignment_name'] ?? 'ไม่ทราบ') ?></td>
                                                <td class="text-muted" style="padding: 16px; font-size:0.9rem;"><?= htmlspecialchars($sub['submit_date'] ?? '-') ?></td>
                                                <td style="padding: 16px;">
                                                    <a href="../uploads/submissions/<?= htmlspecialchars($sub['file_name']) ?>" target="_blank" class="btn btn-sm text-nowrap" style="background-color:rgba(94,178,255,0.1); color:#5eb2ff; font-weight:500;">
                                                        <i data-lucide="file-text" style="width:16px;height:16px; margin-right:4px;"></i> ไฟล์งาน
                                                    </a>
                                                </td>
                                                <td style="padding: 16px;" class="text-nowrap">
                                                    <?php if ($sub['status_1'] == 1): ?>
                                                        <span class="badge" style="background-color:rgba(25,135,84,0.1); color:#198754; padding:8px 12px; font-weight:500;"><i data-lucide="check-circle" style="width:14px;height:14px; margin-right:4px;"></i>ผ่านแล้ว</span>
                                                    <?php elseif ($sub['status_1'] == 2): ?>
                                                        <span class="badge" style="background-color:rgba(220,53,69,0.1); color:#dc3545; padding:8px 12px; font-weight:500;"><i data-lucide="x-circle" style="width:14px;height:14px; margin-right:4px;"></i>แก้ไข</span>
                                                    <?php else: ?>
                                                        <span class="badge" style="background-color:rgba(255,193,7,0.15); color:#d39e00; padding:8px 12px; font-weight:500;"><i data-lucide="clock" style="width:14px;height:14px; margin-right:4px;"></i>รอตรวจ</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="max-width: 200px; font-size:0.85rem; padding: 16px; color:#6c757d;">
                                                    <?= nl2br(htmlspecialchars($sub['teacher_comment'] ?? '-')) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <div class="col-md-4">
                    <!-- Progress Bar Section -->
                    <div class="dashboard-card text-center mb-4">
                        <h5 class="fw-bold mb-4 text-dark">ความคืบหน้าการส่งงาน</h5>
                        
                        <div class="d-flex justify-content-center align-items-center mb-3">
                            <div class="position-relative" style="width: 150px; height: 150px;">
                                <svg viewBox="0 0 36 36" style="width: 100%; height: 100%; transform: rotate(-90deg);">
                                    <!-- Background Circle -->
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                                          fill="none" stroke="#e9ecef" stroke-width="3.5" stroke-dasharray="100, 100" />
                                    <!-- Progress Circle -->
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" 
                                          fill="none" stroke="#5eb2ff" stroke-width="3.5" stroke-dasharray="<?= $progress_percent ?>, 100" 
                                          stroke-linecap="round" style="transition: stroke-dasharray 1s ease-out;" />
                                </svg>
                                
                                <!-- Inner Text -->
                                <div class="position-absolute top-50 start-50 translate-middle text-center w-100">
                                    <div class="fw-bold text-dark" style="font-size: 1.8rem; line-height: 1;"><?= $completed_assignments ?>/<?= $total_assignments ?></div>
                                    <div class="text-muted" style="font-size: 0.85rem; font-weight: 600;"><?= $progress_percent ?>%</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-muted" style="font-size: 0.95rem;">
                            อนุมัติผ่านแล้ว <span class="fw-bold text-primary"><?= $completed_assignments ?></span> จาก <span class="fw-bold text-dark"><?= $total_assignments ?></span> งาน
                        </div>
                    </div>
                    <div class="dashboard-card bg-light border-0">
                        <h5 class="fw-bold mb-3"><i data-lucide="bell" style="width:20px;height:20px; margin-right:4px;"></i> แจ้งเตือนการส่งงาน</h5>
                        
                        <?php if ($group['status'] == 0): ?>
                            <div class="alert alert-warning p-2" style="font-size:0.9rem;">
                                โครงงานของคุณยังไม่ได้รับการอนุมัติ จะยังไม่สามารถส่งงานได้
                            </div>
                        <?php else: ?>
                            <?php if (count($pending_assignments) > 0): ?>
                                <ul class="list-group list-group-flush mb-3">
                                    <?php foreach ($pending_assignments as $pa): ?>
                                        <li class="list-group-item bg-transparent px-0 border-bottom border-secondary-subtle">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1 text-danger fw-bold"><?= htmlspecialchars($pa['assignment_name']) ?></h6>
                                            </div>
                                            <p class="mb-1" style="font-size:0.85rem;">กำหนดส่ง: <?= htmlspecialchars($pa['deadline_date'] ?? 'ไม่ระบุ') ?> <?= htmlspecialchars($pa['deadline_time'] ?? '') ?></p>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="assignments.php" class="btn btn-sm btn-primary w-100">ไปที่หน้าส่งงาน</a>
                            <?php else: ?>
                                <p class="text-success fw-bold" style="font-size:0.9rem;"><i data-lucide="check-circle-2"></i> ไม่มีงานค้างส่งในขณะนี้</p>
                                <a href="assignments.php" class="btn btn-sm btn-outline-secondary w-100">ดูประวัติการส่งงาน</a>
                            <?php endif; ?>
                        <?php endif; ?>
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