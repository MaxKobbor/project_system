<?php
require_once '../../config/db.php';
require_once '../config_t/auth_check.php';

// Verify TC access
check_teacher_auth(1);

$teacher_id = $_SESSION['teacher_id'];

// Session message
$message = $_SESSION['msg'] ?? '';
$messageType = $_SESSION['msg_type'] ?? '';
unset($_SESSION['msg'], $_SESSION['msg_type']);

// === ค้นหา ===
$search = trim($_GET['search'] ?? '');

$query = "SELECT g.*, c.class_name, c.year_id, b.branch_name, y.year_name AS year
          FROM `groups` g
          LEFT JOIN `classrooms` c ON g.class_id = c.class_id
          LEFT JOIN `branches` b ON c.branch_id = b.branch_id
          LEFT JOIN `academic_years` y ON c.year_id = y.year_id
          WHERE g.teacher_id = ?";

$params = [$teacher_id];

if ($search !== '') {
    $query .= " AND (g.username LIKE ? OR g.project_name LIKE ? OR g.std_1 LIKE ? OR g.std_2 LIKE ? OR g.std_3 LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$query .= " ORDER BY g.group_id DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch classrooms for dropdown
$classStmt = $pdo->query("SELECT c.class_id, c.class_name, b.branch_name, y.year_name 
                          FROM classrooms c 
                          LEFT JOIN branches b ON c.branch_id = b.branch_id
                          LEFT JOIN academic_years y ON c.year_id = y.year_id
                          ORDER BY y.year_name DESC, c.class_name ASC");
$classrooms = $classStmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>จัดการกลุ่มนักเรียน - ระบบครูผู้สอน</title>
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

        .modal-box {
            max-width: 600px;
            width: 100%;
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
                        <h2>กลุ่มนักเรียนในที่ปรึกษา</h2>
                    </div>

                    <!-- Search Bar -->
                    <form method="GET" class="search-bar">
                        <input type="text" name="search" placeholder="ค้นหาชื่อกลุ่ม, โครงงาน, นักเรียน" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit"><i data-lucide="search" style="width:18px;height:18px;"></i></button>
                    </form>
                </div>

                <div class="manage-card-body">
                    <?php if ($search !== ''): ?>
                        <div class="search-result-text mb-3">
                            ผลลัพธ์การค้นหา "<?= htmlspecialchars($search) ?>"
                        </div>
                    <?php endif; ?>

                    <?php if (empty($groups)): ?>
                        <div class="no-data">
                            <i data-lucide="users" style="width:40px;height:40px;"></i>
                            <p>ยังไม่มีกลุ่มนักเรียนในที่ปรึกษาของคุณ</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($groups as $g): ?>
                            <?php
                            // รวบรวมสมาชิกจาก std_1 - std_3
                            $members = array_filter([$g['std_1'], $g['std_2'], $g['std_3']]);
                            ?>
                            <div class="manage-item">
                                <div class="manage-item-info">
                                    <i data-lucide="users" class="item-icon" style="width:22px;height:22px; margin-top:2px;"></i>
                                    <div>
                                        <p class="item-name" style="font-size:1.1rem; font-weight:600;">
                                            <?= highlightText($g['project_name'] ?? 'ยังไม่มีชื่อโครงงาน', $search) ?>
                                            <?php if ($g['status'] == 1): ?>
                                                <span class="badge bg-success ms-2" style="font-size:0.75rem;">ผ่านการอนุมัติแล้ว</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark ms-2" style="font-size:0.75rem;">รอ HOD อนุมัติ</span>
                                            <?php endif; ?>
                                        </p>
                                        <p style="font-size:0.9rem; color:#555; margin: 2px 0 6px 0;">
                                            ชื่อกลุ่ม (Username): <strong class="text-primary"><?= highlightText($g['username'] ?? '-', $search) ?></strong> | Password: <strong class="text-secondary">***</strong>
                                        </p>

                                        <!-- Badges: ปีการศึกษา / สาขา / ห้อง -->
                                        <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                                            <span class="group-pill"><?= htmlspecialchars($g['year'] ?? 'ปีการศึกษา') ?></span>
                                            <span class="group-pill"><?= htmlspecialchars($g['branch_name'] ?? 'สาขาวิชา') ?></span>
                                            <span class="group-pill"><?= htmlspecialchars($g['class_name'] ?? 'ห้องเรียน') ?></span>
                                        </div>

                                        <!-- สมาชิกกลุ่ม -->
                                        <div style="font-size:0.85rem; color:#666;">
                                            <div class="fw-bold mb-1"><i data-lucide="user" style="width:14px;height:14px; display:inline;"></i> สมาชิก (<?= count($members) ?> คน):</div>
                                            <?php if (empty($members)): ?>
                                                <span class="text-muted">ยังไม่มีข้อมูลนักเรียน</span>
                                            <?php else: ?>
                                                <ul style="margin:0; padding-left:20px; color:#555;">
                                                    <?php foreach ($members as $idx => $m): ?>
                                                        <li><?= highlightText($m, $search) ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="manage-item-actions d-flex flex-column gap-2 align-items-end">
                                    <button type="button" class="btn-edit" style="width: 100px; justify-content: center;"
                                        onclick="openEditModal(<?= $g['group_id'] ?>, '<?= htmlspecialchars($g['username'], ENT_QUOTES) ?>', '', '<?= htmlspecialchars($g['project_name'] ?? '', ENT_QUOTES) ?>', <?= $g['class_id'] ?>, '<?= htmlspecialchars($g['std_1'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($g['std_2'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($g['std_3'] ?? '', ENT_QUOTES) ?>')">
                                        <i data-lucide="square-pen" style="width:16px;height:16px;"></i> แก้ไข
                                    </button>
                                    <a href="submissions_view.php?group_id=<?= $g['group_id'] ?>" class="btn btn-primary btn-sm d-flex justify-content-center align-items-center gap-1" style="background:#61BFED; border:none; width: 100px;">
                                        <i data-lucide="eye" style="width:16px;height:16px;"></i> ดูงาน
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FAB: ปุ่มเพิ่มกลุ่มใหม่ -->
            <button type="button" class="fab-add" onclick="openAddModal()" title="สร้างกลุ่มใหม่">
                <i data-lucide="plus" style="width:28px;height:28px;"></i>
            </button>
        </main>
    </div>

    <!-- ===== Modal เพิ่ม/แก้ไขกลุ่ม ===== -->
    <div class="modal-overlay" id="groupModal">
        <div class="modal-box" style="max-height: 90vh; overflow-y: auto;">
            <h3 id="modalTitle">สร้างกลุ่มนักเรียนใหม่</h3>
            <form action="../config_t/process_tc.php" method="POST" id="groupForm">
                <input type="hidden" name="action" id="formAction" value="add_group">
                <input type="hidden" name="group_id" id="editGroupId">

                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label for="username">ชื่อผู้ใช้ (Username) <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="username" placeholder="ตั้งชื่อกลุ่ม หรือ รหัสนักเรียน" required>
                    </div>
                    <div class="col-md-6 mb-2">
                        <input type="text" name="password" id="password" placeholder="เว้นว่างหากไม่ต้องการเปลี่ยนรหัสผ่าน">
                    </div>
                </div>

                <div class="mb-2">
                    <label for="projectName">ชื่อโครงงาน <span class="text-danger">*</span></label>
                    <input type="text" name="project_name" id="projectName" placeholder="ชื่อโครงงานภาษาไทย/อังกฤษ" required>
                </div>

                <div class="mb-3">
                    <label for="classId">ห้องเรียน <span class="text-danger">*</span></label>
                    <select name="class_id" id="classId" required>
                        <option value="">-- เลือกห้องเรียน --</option>
                        <?php foreach ($classrooms as $c): ?>
                            <option value="<?= $c['class_id'] ?>">
                                <?= htmlspecialchars($c['year_name'] . ' - ' . $c['branch_name'] . ' - ' . $c['class_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <hr>
                <h5 class="mb-3" style="font-size:1rem; font-weight:600; color:#333;">รายชื่อสมาชิกกลุ่ม</h5>

                <div class="mb-2">
                    <label for="std1">ชื่อ-นามสกุล นักเรียนคนที่ 1 (หัวหน้ากลุ่ม) <span class="text-danger">*</span></label>
                    <input type="text" name="std_1" id="std1" placeholder="นาย/นางสาว..." required>
                </div>
                <div class="mb-2">
                    <label for="std2">ชื่อ-นามสกุล นักเรียนคนที่ 2</label>
                    <input type="text" name="std_2" id="std2" placeholder="นาย/นางสาว...">
                </div>
                <div class="mb-2">
                    <label for="std3">ชื่อ-นามสกุล นักเรียนคนที่ 3</label>
                    <input type="text" name="std_3" id="std3" placeholder="นาย/นางสาว...">
                </div>

                <div class="modal-actions mt-4">
                    <button type="button" class="btn-cancel" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save" id="modalSubmitBtn">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();

        // Modal Functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'สร้างกลุ่มนักเรียนใหม่';
            document.getElementById('formAction').value = 'add_group';
            document.getElementById('editGroupId').value = '';

            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('projectName').value = '';
            document.getElementById('classId').value = '';
            document.getElementById('std1').value = '';
            document.getElementById('std2').value = '';
            document.getElementById('std3').value = '';

            document.getElementById('groupModal').classList.add('active');
            document.getElementById('username').focus();
        }

        function openEditModal(id, username, password, projectName, classId, std1, std2, std3) {
            document.getElementById('modalTitle').textContent = 'แก้ไขกลุ่มนักเรียน';
            document.getElementById('formAction').value = 'edit_group';
            document.getElementById('editGroupId').value = id;

            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
            document.getElementById('projectName').value = projectName;
            document.getElementById('classId').value = classId;
            document.getElementById('std1').value = std1;
            document.getElementById('std2').value = std2;
            document.getElementById('std3').value = std3;

            document.getElementById('groupModal').classList.add('active');
            document.getElementById('username').focus();
        }

        function closeModal() {
            document.getElementById('groupModal').classList.remove('active');
        }

        document.getElementById('groupModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
    <script src="nav.js"></script>
</body>

</html>