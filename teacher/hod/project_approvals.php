<?php
require_once '../../config/db.php';
require_once '../config_t/auth_check.php';

// Verify HOD access
check_teacher_auth(0);

// Session message
$message = $_SESSION['msg'] ?? '';
$messageType = $_SESSION['msg_type'] ?? '';
unset($_SESSION['msg'], $_SESSION['msg_type']);

// === Filters & Search ===
$search = trim($_GET['search'] ?? '');
$yearFilter = isset($_GET['year_id']) && $_GET['year_id'] !== '' ? intval($_GET['year_id']) : null;
$branchFilter = isset($_GET['branch_id']) && $_GET['branch_id'] !== '' ? intval($_GET['branch_id']) : null;
$classFilter = isset($_GET['class_id']) && $_GET['class_id'] !== '' ? intval($_GET['class_id']) : null;
$statusFilter = isset($_GET['status']) && $_GET['status'] !== '' ? intval($_GET['status']) : null;

// Fetch filter options
$years = $pdo->query("SELECT * FROM academic_years ORDER BY year_name DESC")->fetchAll(PDO::FETCH_ASSOC);
$branches = $pdo->query("SELECT * FROM branches ORDER BY branch_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$rooms = $pdo->query("SELECT * FROM classrooms ORDER BY class_name ASC")->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT g.*, t.full_name AS teacher_name, c.class_name, b.branch_name, y.year_name AS year
          FROM `groups` g
          LEFT JOIN `teachers` t ON g.teacher_id = t.teacher_id
          LEFT JOIN `classrooms` c ON g.class_id = c.class_id
          LEFT JOIN `branches` b ON c.branch_id = b.branch_id
          LEFT JOIN `academic_years` y ON c.year_id = y.year_id
          WHERE 1=1";

$params = [];

if ($yearFilter !== null) {
    $query .= " AND c.year_id = ?";
    $params[] = $yearFilter;
}
if ($branchFilter !== null) {
    $query .= " AND c.branch_id = ?";
    $params[] = $branchFilter;
}
if ($classFilter !== null) {
    $query .= " AND g.class_id = ?";
    $params[] = $classFilter;
}

if ($search !== '') {
    $query .= " AND (g.username LIKE ? OR g.project_name LIKE ? OR t.full_name LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($statusFilter !== null) {
    $query .= " AND g.status = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY g.status ASC, g.group_id ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

function highlightText($text, $search)
{
    if ($search === '') return htmlspecialchars($text);
    $escaped = htmlspecialchars($text);
    $escapedSearch = htmlspecialchars($search);
    return str_ireplace($escapedSearch, '<span class="highlight">' . $escapedSearch . '</span>', $escaped);
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <link rel="icon" type="image/png" href="../../img/new_logo_svc_temp.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อนุมัติโครงงาน - ระบบสำหรับหัวหน้าแผนก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../admin/header.css">
    <link rel="stylesheet" href="../../admin/nav.css">
    <link rel="stylesheet" href="../../admin/manage.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .group-pill {
            background: #e8e8e8;
            border-radius: 12px;
            padding: 2px 14px;
            font-size: 0.78rem;
            color: #666;
            display: inline-block;
            min-width: 80px;
            text-align: center;
        }

        .filter-btn {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.88rem;
            text-decoration: none;
            color: #555;
            background: #f0f0f0;
            transition: all 0.2s;
        }

        .filter-btn.active {
            background: #61BFED;
            color: #fff;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <!-- Alert Message -->
            <?php if ($message): ?>
                <div class="alert-msg alert-<?= $messageType === 'success' ? 'success' : 'error' ?>">
                    <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width:18px;height:18px;"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="manage-card">
                <div class="manage-card-header">
                    <div class="d-flex align-items-center gap-3">
                        <a href="index.php" class="back-btn"><i data-lucide="chevron-left" style="width:28px;height:28px;"></i></a>
                        <h2>อนุมัติโครงงาน</h2>
                    </div>


                </div>

                <div class="manage-card-body">
                    <!-- Dropdown Filters -->
                    <form method="GET" class="row g-2 mb-4 align-items-center bg-light p-3 rounded" style="border: 1px solid #dee2e6;">
                        <div class="col-12 col-md-auto">
                            <div class="input-group input-group-sm">
                                <input type="text" name="search" class="form-control" placeholder="ค้นหากลุ่ม, โครงงาน, ครู" value="<?= htmlspecialchars($search) ?>">
                                <button class="btn btn-outline-secondary" type="submit"><i data-lucide="search" style="width:14px;height:14px;"></i></button>
                            </div>
                        </div>
                        <div class="col-12 col-md-auto">
                            <select name="year_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">-- ปีการศึกษา --</option>
                                <?php foreach ($years as $y): ?>
                                    <option value="<?= $y['year_id'] ?>" <?= $yearFilter === intval($y['year_id']) ? 'selected' : '' ?>><?= htmlspecialchars($y['year_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-auto">
                            <select name="branch_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">-- สาขา --</option>
                                <?php foreach ($branches as $b): ?>
                                    <option value="<?= $b['branch_id'] ?>" <?= $branchFilter === intval($b['branch_id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['branch_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-auto">
                            <select name="class_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">-- ห้อง --</option>
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?= $r['class_id'] ?>" <?= $classFilter === intval($r['class_id']) ? 'selected' : '' ?>><?= htmlspecialchars($r['class_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-auto">
                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">-- ทุกสถานะ --</option>
                                <option value="0" <?= $statusFilter === 0 ? 'selected' : '' ?>>รออนุมัติ</option>
                                <option value="1" <?= $statusFilter === 1 ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-auto ms-auto">
                            <a href="project_approvals.php" class="btn btn-sm btn-outline-secondary">ล้างตัวกรอง</a>
                        </div>
                    </form>



                    <?php if ($search !== ''): ?>
                        <div class="search-result-text mb-3">
                            ผลลัพธ์การค้นหา "<?= htmlspecialchars($search) ?>"
                        </div>
                    <?php endif; ?>

                    <?php if (empty($groups)): ?>
                        <div class="no-data">
                            <i data-lucide="inbox" style="width:40px;height:40px;"></i>
                            <p>ไม่พบข้อมูลโครงงานนักเรียน</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($groups as $g): ?>
                            <?php
                            // รวบรวมสมาชิกจาก std_1 - std_3
                            $members = [];
                            for ($i = 1; $i <= 3; $i++) {
                                if (!empty($g['std_' . $i])) {
                                    $members[] = $g['std_' . $i];
                                }
                            }
                            ?>
                            <div class="manage-item">
                                <div class="manage-item-info">
                                    <i data-lucide="file-text" class="item-icon" style="width:22px;height:22px; margin-top:2px;"></i>
                                    <div>
                                        <p class="item-name" style="font-size:1.1rem; font-weight:600;">
                                            <?= highlightText($g['project_name'] ?? 'ยังไม่มีชื่อโครงงาน', $search) ?>
                                            <?php if ($g['status'] == 1): ?>
                                                <span class="badge bg-success ms-2" style="font-size:0.75rem;">อนุมัติแล้ว</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark ms-2" style="font-size:0.75rem;">รออนุมัติ</span>
                                            <?php endif; ?>
                                        </p>
                                        <p style="font-size:0.9rem; color:#555; margin: 2px 0 6px 0;">
                                            กลุ่ม: <strong><?= highlightText($g['username'] ?? '-', $search) ?></strong> | ครูที่ปรึกษา: <strong><?= highlightText($g['teacher_name'] ?? 'ยังไม่ระบุ', $search) ?></strong>
                                        </p>

                                        <!-- Badges: ปีการศึกษา / สาขา / ห้อง -->
                                        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                            <span class="group-pill"><?= htmlspecialchars($g['year'] ?? 'ปีการศึกษา') ?></span>
                                            <span class="group-pill"><?= htmlspecialchars($g['branch_name'] ?? 'สาขาวิชา') ?></span>
                                            <span class="group-pill"><?= htmlspecialchars($g['class_name'] ?? 'ห้องเรียน') ?></span>
                                        </div>

                                        <!-- สมาชิกกลุ่ม -->
                                        <div style="font-size:0.82rem; color:#666;">
                                            <i data-lucide="users" style="width:14px;height:14px; display:inline;"></i> สมาชิก:
                                            <?php if (empty($members)): ?>
                                                <span>ยังไม่มีสมาชิก</span>
                                            <?php else: ?>
                                                <span><?= implode(', ', array_map('htmlspecialchars', $members)) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="manage-item-actions d-flex gap-2">
                                    <a href="submissions_view.php?group_id=<?= $g['group_id'] ?>" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-1">
                                        <i data-lucide="eye" style="width:16px;height:16px;"></i> ดูงาน
                                    </a>
                                    <form action="../config_t/process_hod.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="group_id" value="<?= $g['group_id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $g['status'] == 1 ? 0 : 1 ?>">
                                        <?php if ($g['status'] == 1): ?>
                                            <button type="submit" name="toggle_project_status" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" onclick="return confirm('คุณต้องการยกเลิกการอนุมัติโครงงานนี้ใช่หรือไม่?')">
                                                <i data-lucide="rotate-ccw" style="width:16px;height:16px;"></i> ยกเลิกการอนุมัติ
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" name="toggle_project_status" class="btn btn-success btn-sm px-3 d-flex align-items-center gap-1">
                                                <i data-lucide="check-circle" style="width:16px;height:16px;"></i> อนุมัติโครงงาน
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
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