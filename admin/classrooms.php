<?php
include_once '../config/db.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

// Session message
$message = $_SESSION['msg'] ?? '';
$messageType = $_SESSION['msg_type'] ?? '';
unset($_SESSION['msg'], $_SESSION['msg_type']);

// === ค้นหา ===
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT c.*, b.branch_name, y.year_name AS year FROM `classrooms` c 
        LEFT JOIN `branches` b ON c.branch_id = b.branch_id 
        LEFT JOIN `academic_years` y ON c.year_id = y.year_id 
        WHERE c.class_name LIKE ? ORDER BY c.class_id ASC");
    $stmt->execute(['%' . $search . '%']);
} else {
    $stmt = $pdo->query("SELECT c.*, b.branch_name, y.year_name AS year FROM `classrooms` c 
        LEFT JOIN `branches` b ON c.branch_id = b.branch_id 
        LEFT JOIN `academic_years` y ON c.year_id = y.year_id 
        ORDER BY c.class_id ASC");
}
$classrooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch options for branch and year dropdowns
$branchStmt = $pdo->query("SELECT `branch_id`, `branch_name` FROM `branches` WHERE `status` = 0 ORDER BY `branch_name` ASC");
$branches = $branchStmt->fetchAll(PDO::FETCH_ASSOC);

$yearStmt = $pdo->query("SELECT `year_id`, `year_name` AS year FROM `academic_years` WHERE `status` = 1 ORDER BY `year_name` DESC");
$years = $yearStmt->fetchAll(PDO::FETCH_ASSOC);

// ฟังก์ชัน Highlight คำค้นหา
function highlightText($text, $search) {
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
    <title>จัดการห้องเรียน - ระบบผู้ดูแล</title>
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
                        <h2>ห้องเรียน</h2>
                    </div>

                    <!-- Search Bar inside header -->
                    <form method="GET" class="search-bar">
                        <input type="text" name="search" placeholder="ค้นหาชื่อห้องเรียน" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit"><i data-lucide="search" style="width:18px;height:18px;"></i></button>
                    </form>
                </div>
                <div class="manage-card-body">
                    <?php if ($search !== ''): ?>
                        <div class="search-result-text">
                            ผลลัพธ์การค้นหา "<?= htmlspecialchars($search) ?>"
                        </div>
                    <?php endif; ?>

                    <?php if (empty($classrooms)): ?>
                        <div class="no-data">
                            <i data-lucide="inbox" style="width:40px;height:40px;"></i>
                            <p>ไม่พบข้อมูลห้องเรียน</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($classrooms as $c): ?>
                            <div class="manage-item">
                                <div class="manage-item-info">
                                    <i data-lucide="grid" class="item-icon" style="width:18px;height:18px;"></i>
                                    <div>
                                        <p class="item-name"><?= highlightText($c['class_name'], $search) ?></p>
                                        <p class="item-subtitle" style="font-size:0.85rem; color:#666;">
                                            สาขา: <?= htmlspecialchars($c['branch_name']) ?> | ปี: <?= htmlspecialchars($c['year']) ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="manage-item-actions">
                                    <!-- ปุ่มแก้ไข -->
                                    <button type="button" class="btn-edit" onclick="openEditModal(<?= $c['class_id'] ?>, '<?= htmlspecialchars($c['class_name'], ENT_QUOTES) ?>', <?= $c['branch_id'] ?>, <?= $c['year_id'] ?>)">
                                        <i data-lucide="square-pen" style="width:16px;height:16px;"></i> แก้ไข
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FAB: ปุ่มเพิ่มห้องเรียนใหม่ -->
            <button type="button" class="fab-add" onclick="openAddModal()">
                <i data-lucide="plus" style="width:28px;height:28px;"></i>
            </button>
        </main>
    </div>

    <!-- ===== Modal เพิ่ม/แก้ไขห้องเรียน ===== -->
    <div class="modal-overlay" id="classroomModal">
        <div class="modal-box">
            <h3 id="modalTitle">เพิ่มห้องเรียน</h3>
            <form action="config_a/process.php" method="POST" id="classroomForm">
                <input type="hidden" name="class_id" id="editClassId">

                <label for="className">ชื่อห้องเรียน</label>
                <input type="text" name="class_name" id="className" placeholder="เช่น ปวช.3/1" required>

                <label for="branchSelect">สาขาวิชา</label>
                <select name="branch_id" id="branchSelect" required>
                    <option value="" disabled>เลือกสาขา</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['branch_id'] ?>"><?= htmlspecialchars($b['branch_name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="yearSelect">ปีการศึกษา</label>
                <select name="year_id" id="yearSelect" required>
                    <option value="" disabled>เลือกปีการศึกษา</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y['year_id'] ?>"><?= htmlspecialchars($y['year']) ?></option>
                    <?php endforeach; ?>
                </select>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save" id="modalSubmitBtn" name="add_classroom">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();

        $(document).ready(function() {
            $('#branchSelect, #yearSelect').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#classroomModal'),
                width: '100%'
            });
        });

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่มห้องเรียน';
            document.getElementById('editClassId').value = '';
            document.getElementById('className').value = '';
            $('#branchSelect').val('').trigger('change');
            $('#yearSelect').val('').trigger('change');
            document.getElementById('modalSubmitBtn').name = 'add_classroom';
            document.getElementById('classroomModal').classList.add('active');
            document.getElementById('className').focus();
        }

        function openEditModal(id, name, branchId, yearId) {
            document.getElementById('modalTitle').textContent = 'แก้ไขห้องเรียน';
            document.getElementById('editClassId').value = id;
            document.getElementById('className').value = name;
            $('#branchSelect').val(branchId).trigger('change');
            $('#yearSelect').val(yearId).trigger('change');
            document.getElementById('modalSubmitBtn').name = 'edit_classroom';
            document.getElementById('classroomModal').classList.add('active');
            document.getElementById('className').focus();
        }

        function closeModal() {
            document.getElementById('classroomModal').classList.remove('active');
        }

        document.getElementById('classroomModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>