<?php
include_once '../config/db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// === ค้นหา ===
$search = trim($_GET['search'] ?? '');
$query = "SELECT g.*, t.full_name AS teacher_name, c.class_name, c.year_id, b.branch_name, y.year_name AS year
          FROM `groups` g
          LEFT JOIN `teachers` t ON g.teacher_id = t.teacher_id
          LEFT JOIN `classrooms` c ON g.class_id = c.class_id
          LEFT JOIN `branches` b ON c.branch_id = b.branch_id
          LEFT JOIN `academic_years` y ON c.year_id = y.year_id";

if ($search !== '') {
    $stmt = $pdo->prepare($query . " WHERE g.project_name LIKE ? OR g.username LIKE ? OR t.full_name LIKE ? OR g.std_1 LIKE ? OR g.std_2 LIKE ? OR g.std_3 LIKE ? OR g.std_4 LIKE ? ORDER BY g.group_id ASC");
    $stmt->execute(['%' . $search . '%', '%' . $search . '%', '%' . $search . '%', '%' . $search . '%', '%' . $search . '%', '%' . $search . '%', '%' . $search . '%']);
} else {
    $stmt = $pdo->query($query . " ORDER BY g.group_id ASC");
}
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch teachers for dropdown
$teacherStmt = $pdo->query("SELECT teacher_id, full_name FROM teachers WHERE status = 1 AND role = 1 ORDER BY full_name ASC");
$teachers = $teacherStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch classrooms for dropdown (include branch name to differentiate)
$classStmt = $pdo->query("SELECT c.class_id, c.class_name, b.branch_name FROM classrooms c LEFT JOIN branches b ON c.branch_id = b.branch_id ORDER BY b.branch_name ASC, c.class_name ASC");
$classrooms = $classStmt->fetchAll(PDO::FETCH_ASSOC);

// Session message
$message = $_SESSION['msg'] ?? '';
$messageType = $_SESSION['msg_type'] ?? '';
unset($_SESSION['msg'], $_SESSION['msg_type']);

// ฟังก์ชัน Highlight คำค้นหา
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
    <link rel="icon" type="image/png" href="../img/new_logo_svc_temp.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>กลุ่มนักเรียน/นักศึกษา - ระบบผู้ดูแล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="manage.css">
    <!-- Select2 CSS & jQuery for searchable select dropdowns -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .group-pill {
            background: #e8e8e8;
            border-radius: 12px;
            padding: 2px 14px;
            font-size: 0.78rem;
            color: #666;
            display: inline-block;
            min-width: 90px;
            text-align: center;
        }

        .btn-view-work {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 20px;
            border: none;
            border-radius: 8px;
            background: #61BFED;
            color: #fff;
            font-family: inherit;
            font-size: 0.9rem;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-view-work:hover {
            background: #3EBCFF;
            color: #fff;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <!-- Main Card -->
            <div class="manage-card">
                <div class="manage-card-header">
                    <div class="d-flex align-items-center gap-3">
                        <a href="index.php" class="back-btn"><i data-lucide="chevron-left" style="width:28px;height:28px;"></i></a>
                        <h2>กลุ่มนักเรียน/นักศึกษา</h2>
                    </div>

                    <!-- Search Bar inside header -->
                    <form method="GET" class="search-bar">
                        <input type="text" name="search" placeholder="กรอกคำค้นหา" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit"><i data-lucide="search" style="width:18px;height:18px;"></i></button>
                    </form>
                </div>

                <div class="manage-card-body">
                    <!-- Alert Message -->
                    <?php if ($message): ?>
                        <div class="alert-msg alert-<?= $messageType ?>">
                            <i data-lucide="<?= $messageType == 'success' ? 'check-circle' : 'alert-circle' ?>" style="width:20px;height:20px;"></i>
                            <span><?= htmlspecialchars($message) ?></span>
                            <button type="button" class="btn-close" style="background:none; border:none; margin-left:auto; cursor:pointer;" onclick="this.parentElement.style.display='none'">
                                <i data-lucide="x" style="width:18px;height:18px; color:inherit;"></i>
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($search !== ''): ?>
                        <div class="search-result-text">
                            ผลลัพธ์การค้นหา "<?= htmlspecialchars($search) ?>"
                        </div>
                    <?php endif; ?>

                    <?php if (empty($groups)): ?>
                        <div class="no-data">
                            <i data-lucide="inbox" style="width:40px;height:40px;"></i>
                            <p>ไม่พบข้อมูลกลุ่มนักเรียน/นักศึกษา</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($groups as $g): ?>
                            <?php
                            // ดึงรายชื่อสมาชิกในกลุ่มจากฟิลด์ std_1 ถึง std_4
                            $members = array_filter([$g['std_1'], $g['std_2'], $g['std_3'], $g['std_4']]);
                            ?>
                            <div class="manage-item">
                                <div class="manage-item-info">
                                    <i data-lucide="play" class="item-icon" style="width:18px;height:18px; margin-top:2px;"></i>
                                    <div>
                                        <p class="item-name" style="font-size:1.1rem; font-weight:600;">
                                            <?= highlightText($g['project_name'], $search) ?>
                                            <?php if ($g['status'] == 1): ?>
                                                <i data-lucide="check-circle-2" style="width:16px;height:16px; color:#3EBCFF; display:inline-block; vertical-align:middle; margin-left:4px;"></i>
                                            <?php endif; ?>
                                        </p>
                                        <p style="font-size:0.9rem; color:#777; margin: 2px 0 8px 0;">
                                            บัญชีผู้ใช้: <strong><?= htmlspecialchars($g['username']) ?></strong> | รหัสผ่าน: <strong class="text-secondary">***</strong>
                                        </p>

                                        <!-- Pill Badges ปีการศึกษา / สาขาวิชา / ห้อง -->
                                        <div class="d-flex flex-wrap gap-2 align-items-center">
                                            <span class="group-pill"><?= htmlspecialchars($g['year'] ?? 'ปีการศึกษา') ?></span>
                                            <span class="group-pill"><?= htmlspecialchars($g['branch_name'] ?? 'สาขาวิชา') ?></span>
                                            <?php
                                            // ถ้าชื่อห้องเรียนเริ่มด้วยชื่อสาขา ให้ตัดหรือแสดงเฉพาะส่วนท้าย
                                            $className = $g['class_name'] ?? 'ห้อง';
                                            if (!empty($g['branch_name']) && strpos($className, $g['branch_name']) === 0) {
                                                $shortClass = trim(substr($className, strlen($g['branch_name'])));
                                                if ($g['branch_name'] === 'เทคโนโลยีสารสนเทศ') {
                                                    $className = 'IT ' . $shortClass;
                                                } elseif ($g['branch_name'] === 'ธุรกิจดิจิทัล') {
                                                    $className = 'DB ' . $shortClass;
                                                } else {
                                                    $className = $shortClass;
                                                }
                                            }
                                            ?>
                                            <span class="group-pill"><?= htmlspecialchars($className) ?></span>
                                        </div>

                                        <!-- รายชื่อสมาชิกในกลุ่ม -->
                                        <div style="margin-top: 10px;">
                                            <p style="font-size:0.8rem; font-weight:600; color:#666; margin-bottom:4px;">
                                                <i data-lucide="users" style="width:14px;height:14px; display:inline; margin-right:2px;"></i> สมาชิกในกลุ่ม (<?= count($members) ?>/3 คน):
                                            </p>
                                            <?php if (empty($members)): ?>
                                                <span style="font-size:0.8rem; color:#999;">ยังไม่มีสมาชิก</span>
                                            <?php else: ?>
                                                <ul style="margin:0; padding-left:20px; font-size:0.85rem; color:#555;">
                                                    <?php foreach ($members as $m): ?>
                                                        <li><?= highlightText($m, $search) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="manage-item-actions flex-column align-items-end gap-2">
                                    <!-- ปุ่มแก้ไข -->
                                    <button type="button" class="btn-edit editBtn" style="width:110px; justify-content:center;"
                                        data-id="<?= $g['group_id'] ?>"
                                        data-project="<?= htmlspecialchars($g['project_name']) ?>"
                                        data-username="<?= htmlspecialchars($g['username'] ?? '') ?>"
                                        data-password=""
                                        data-teacher="<?= $g['teacher_id'] ?>"
                                        data-class="<?= $g['class_id'] ?>"
                                        data-status="<?= $g['status'] ?>"
                                        data-std1="<?= htmlspecialchars($g['std_1'] ?? '') ?>"
                                        data-std2="<?= htmlspecialchars($g['std_2'] ?? '') ?>"
                                        data-std3="<?= htmlspecialchars($g['std_3'] ?? '') ?>"
                                        data-std4="<?= htmlspecialchars($g['std_4'] ?? '') ?>">
                                        <i data-lucide="square-pen" style="width:16px;height:16px;"></i> แก้ไข
                                    </button>
                                    <!-- ปุ่มดูงาน -->
                                    <a href="submissions_view.php?group_id=<?= $g['group_id'] ?>" class="btn-view-work" style="width:110px; text-align:center;">
                                        ดูงาน
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Edit Group Modal -->
    <div class="modal fade" id="editGroupModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="config_a/process.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">แก้ไขกลุ่มนักเรียน</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit_group">
                        <input type="hidden" name="group_id" id="edit_group_id">

                        <div class="mb-3">
                            <label class="form-label fw-bold">ชื่อโครงงาน <span class="text-danger">*</span></label>
                            <input type="text" class="form-control input-custom" name="project_name" id="edit_project_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">ชื่อผู้ใช้ (Username) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control input-custom" name="username" id="edit_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">รหัสผ่าน (Password) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control input-custom" name="password" id="edit_password" placeholder="เว้นว่างหากไม่ต้องการเปลี่ยนรหัสผ่าน">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">ครูที่ปรึกษา</label>
                            <select class="form-select input-custom" name="teacher_id" id="edit_teacher_id">
                                <option value="">ไม่มีครูที่ปรึกษา</option>
                                <?php foreach ($teachers as $t): ?>
                                    <option value="<?= $t['teacher_id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">ห้องเรียน</label>
                            <select class="form-select input-custom" name="class_id" id="edit_class_id">
                                <option value="">ไม่มีห้องเรียน</option>
                                <?php foreach ($classrooms as $c): ?>
                                    <option value="<?= $c['class_id'] ?>"><?= htmlspecialchars($c['class_name']) ?> (<?= htmlspecialchars($c['branch_name'] ?? '-') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">สมาชิกในกลุ่ม (สูงสุด 4 คน)</label>
                            <div class="border rounded p-2 bg-light">
                                <?php for ($i = 1; $i <= 4; $i++): ?>
                                    <div class="mb-2">
                                        <label class="form-label text-muted small mb-1">สมาชิกคนที่ <?= $i ?><?= $i == 4 ? ' (กรณีพิเศษ)' : '' ?></label>
                                        <input type="text" class="form-control input-custom" name="std_<?= $i ?>" id="edit_std_<?= $i ?>" placeholder="ชื่อ-นามสกุล นักเรียนคนที่ <?= $i ?>">
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">สถานะกลุ่ม</label>
                            <div class="form-check form-switch" style="padding-left: 2.5em;">
                                <input class="form-check-input" type="checkbox" name="status" value="1" id="edit_status" style="width: 2.5em; height: 1.25em; cursor: pointer;">
                                <label class="form-check-label" for="edit_status" style="margin-left: 0.5em; cursor: pointer;">อนุมัติโครงงาน</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary" style="background:#61BFED; border:none;">บันทึกการเปลี่ยนแปลง</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();

        $(document).ready(function() {
            // Initialize Select2 with Bootstrap 5 theme on modal dropdowns
            $('#editGroupModal select').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#editGroupModal'),
                width: '100%'
            });
        });

        // Edit Group Modal Data Population
        document.querySelectorAll('.editBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                var groupId = this.dataset.id;
                document.getElementById('edit_group_id').value = groupId;
                document.getElementById('edit_project_name').value = this.dataset.project;
                document.getElementById('edit_username').value = this.dataset.username;
                document.getElementById('edit_password').value = this.dataset.password;

                $('#edit_teacher_id').val(this.dataset.teacher).trigger('change');
                $('#edit_class_id').val(this.dataset.class).trigger('change');
                document.getElementById('edit_status').checked = (this.dataset.status === '1');

                document.getElementById('edit_std_1').value = this.dataset.std1;
                document.getElementById('edit_std_2').value = this.dataset.std2;
                document.getElementById('edit_std_3').value = this.dataset.std3;
                document.getElementById('edit_std_4').value = this.dataset.std4;

                var editModal = new bootstrap.Modal(document.getElementById('editGroupModal'));
                editModal.show();
            });
        });
    </script>
    <script src="nav.js"></script>
</body>

</html>