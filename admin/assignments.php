<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../config/db.php';
session_start();;

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Session message
$message = $_SESSION['msg'] ?? '';
$messageType = $_SESSION['msg_type'] ?? '';
unset($_SESSION['msg'], $_SESSION['msg_type']);

// ดึงข้อมูลปีการศึกษาเพื่อใช้งานใน Dropdown
$yearStmt = $pdo->query("SELECT `year_id`, `year_name` FROM `academic_years` WHERE `status` = 1 ORDER BY `year_name` DESC");
$years = $yearStmt->fetchAll(PDO::FETCH_ASSOC);

// === ค้นหา ===
$search = trim($_GET['search'] ?? '');
$baseSql = "SELECT a.*, y.year_name AS year FROM `assignments` a LEFT JOIN `academic_years` y ON a.year_id = y.year_id";
if ($search !== '') {
    $stmt = $pdo->prepare($baseSql . " WHERE a.`assignment_name` LIKE ? ORDER BY a.`assignment_id` ASC");
    $stmt->execute(['%' . $search . '%']);
} else {
    $stmt = $pdo->query($baseSql . " ORDER BY a.`assignment_id` ASC");
}
$assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>จัดการหัวข้องาน - ระบบผู้ดูแล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="manage.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <!-- Alert Message -->
            <?php if ($message): ?>
                <div class="alert-msg alert-<?= $messageType ?>">
                    <i data-lucide="<?= $messageType === 'success' ? 'check-circle' : 'alert-circle' ?>" style="width:18px;height:18px;"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Main Card -->
            <div class="manage-card">
                <div class="manage-card-header">
                    <div class="d-flex align-items-center gap-3">
                        <a href="index.php" class="back-btn"><i data-lucide="chevron-left" style="width:28px;height:28px;"></i></a>
                        <h2>หัวข้องาน</h2>
                    </div>

                    <!-- Search Bar inside header -->
                    <form method="GET" class="search-bar">
                        <input type="text" name="search" placeholder="ค้นหาชื่องาน" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit"><i data-lucide="search" style="width:18px;height:18px;"></i></button>
                    </form>
                </div>

                <div class="manage-card-body">
                    <?php if ($search !== ''): ?>
                        <div class="search-result-text">
                            ผลลัพธ์การค้นหา "<?= htmlspecialchars($search) ?>"
                        </div>
                    <?php endif; ?>

                    <?php if (empty($assignments)): ?>
                        <div class="no-data">
                            <i data-lucide="inbox" style="width:40px;height:40px;"></i>
                            <p>ไม่พบข้อมูลหัวข้องาน</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($assignments as $asg): ?>
                            <div class="manage-item">
                                <div class="manage-item-info">
                                    <i data-lucide="clipboard" class="item-icon" style="width:20px;height:20px;"></i>
                                    <div>
                                        <p class="item-name">
                                            <?= highlightText($asg['assignment_name'], $search) ?>
                                            <span style="font-size:0.85rem; color:#1976d2; font-weight:500;">(<?= htmlspecialchars($asg['year'] ?? 'ยังไม่ได้ระบุปี') ?>)</span>
                                        </p>
                                        <p style="font-size:0.85rem; color:#555; margin: 2px 0 0 0;">
                                            ระยะเวลาส่ง:
                                            <strong><?= !empty($asg['start_date']) ? date('d/m/Y', strtotime($asg['start_date'])) : '-' ?></strong>
                                            ถึง
                                            <strong><?= date('d/m/Y', strtotime($asg['deadline_date'])) ?></strong>
                                            เวลา <strong><?= date('H:i', strtotime($asg['deadline_time'])) ?> น.</strong>
                                        </p>
                                        <span class="badge-status <?= $asg['status'] == 1 ? 'badge-enable' : 'badge-disable' ?>">
                                            <?= $asg['status'] == 1 ? 'Enable' : 'Disable' ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="manage-item-actions">
                                    <!-- ปุ่มแก้ไข -->
                                    <button type="button" class="btn-edit"
                                        onclick="openEditModal(<?= $asg['assignment_id'] ?>, '<?= htmlspecialchars($asg['assignment_name'], ENT_QUOTES) ?>', <?= $asg['year_id'] ?? 0 ?>, '<?= $asg['start_date'] ?? '' ?>', '<?= $asg['deadline_date'] ?>', '<?= $asg['deadline_time'] ?>')">
                                        <i data-lucide="square-pen" style="width:16px;height:16px;"></i> แก้ไข
                                    </button>

                                    <!-- ปุ่ม Toggle สถานะ -->
                                    <form action="config_a/process.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="assignment_id" value="<?= $asg['assignment_id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $asg['status'] == 1 ? 0 : 1 ?>">
                                        <button type="submit" name="toggle_assignment_status"
                                            class="btn-toggle <?= $asg['status'] == 1 ? 'btn-disable' : 'btn-enable' ?>">
                                            <?= $asg['status'] == 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FAB: ปุ่มเพิ่มหัวข้องานใหม่ -->
            <button type="button" class="fab-add" onclick="openAddModal()">
                <i data-lucide="plus" style="width:28px;height:28px;"></i>
            </button>
        </main>
    </div>

    <!-- ===== Modal เพิ่ม/แก้ไขหัวข้องาน ===== -->
    <div class="modal-overlay" id="assignmentModal">
        <div class="modal-box">
            <h3 id="modalTitle">เพิ่มหัวข้องาน</h3>
            <form action="config_a/process.php" method="POST" id="assignmentForm">
                <input type="hidden" name="assignment_id" id="editAssignmentId">

                <label for="assignmentName">ชื่องาน / หัวข้องาน</label>
                <input type="text" name="assignment_name" id="assignmentName" placeholder="เช่น บทที่ 1 บทนำ" required>

                <label for="yearSelect">ปีการศึกษา</label>
                <select name="year_id" id="yearSelect" required>
                    <option value="" disabled selected>เลือกปีการศึกษา</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y['year_id'] ?>"><?= htmlspecialchars($y['year_name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="row g-2">
                    <div class="col-6">
                        <label for="startDate">วันที่เริ่มต้นส่ง</label>
                        <input type="date" name="start_date" id="startDate" required>
                    </div>
                    <div class="col-6">
                        <label for="deadlineDate">วันที่สิ้นสุด (กำหนดส่ง)</label>
                        <input type="date" name="deadline_date" id="deadlineDate" required>
                    </div>
                </div>

                <label for="deadlineTime">เวลากำหนดส่ง</label>
                <input type="time" name="deadline_time" id="deadlineTime" required>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save" id="modalSubmitBtn" name="add_assignment">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();

        $(document).ready(function() {
            $('#yearSelect').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#assignmentModal'),
                width: '100%'
            });
        });

        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่มหัวข้องาน';
            document.getElementById('editAssignmentId').value = '';
            document.getElementById('assignmentName').value = '';
            $('#yearSelect').val('').trigger('change');
            document.getElementById('startDate').value = '';
            document.getElementById('deadlineDate').value = '';
            document.getElementById('deadlineTime').value = '23:59';
            document.getElementById('modalSubmitBtn').name = 'add_assignment';
            document.getElementById('assignmentModal').classList.add('active');
            document.getElementById('assignmentName').focus();
        }

        function openEditModal(id, name, yearId, startDate, date, time) {
            document.getElementById('modalTitle').textContent = 'แก้ไขหัวข้องาน';
            document.getElementById('editAssignmentId').value = id;
            document.getElementById('assignmentName').value = name;
            $('#yearSelect').val(yearId).trigger('change');
            document.getElementById('startDate').value = startDate;
            document.getElementById('deadlineDate').value = date;
            document.getElementById('deadlineTime').value = time;
            document.getElementById('modalSubmitBtn').name = 'edit_assignment';
            document.getElementById('assignmentModal').classList.add('active');
            document.getElementById('assignmentName').focus();
        }

        function closeModal() {
            document.getElementById('assignmentModal').classList.remove('active');
        }

        // ปิด modal เมื่อกดข้างนอก
        document.getElementById('assignmentModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // ปิด modal ด้วย Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
    <script src="nav.js"></script>
</body>

</html>