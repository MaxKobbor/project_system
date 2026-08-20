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
    $stmt = $pdo->prepare("SELECT * FROM `academic_years` WHERE `year_name` LIKE ? ORDER BY `year_name` DESC");
    $stmt->execute(['%' . $search . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM `academic_years` ORDER BY `year_name` DESC");
}
$academic_years = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>จัดการปีการศึกษา - ระบบผู้ดูแล</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="nav.css">
    <link rel="stylesheet" href="manage.css">
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
                        <h2>ปีการศึกษา</h2>
                    </div>

                    <!-- Search Bar inside header -->
                    <form method="GET" class="search-bar">
                        <input type="text" name="search" placeholder="กรอกคำค้นหา" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit"><i data-lucide="search" style="width:18px;height:18px;"></i></button>
                    </form>
                </div>

                <div class="manage-card-body">
                    <?php if ($search !== ''): ?>
                        <div class="search-result-text">
                            ผลลัพธ์การค้นหา "<?= htmlspecialchars($search) ?>"
                        </div>
                    <?php endif; ?>

                    <?php if (empty($academic_years)): ?>
                        <div class="no-data">
                            <i data-lucide="inbox" style="width:40px;height:40px;"></i>
                            <p>ไม่พบข้อมูลปีการศึกษา</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($academic_years as $ay): ?>
                            <div class="manage-item">
                                <div class="manage-item-info">
                                    <i data-lucide="calendar" class="item-icon" style="width:18px;height:18px;"></i>
                                    <div>
                                        <p class="item-name"><?= highlightText($ay['year_name'], $search) ?></p>
                                        <span class="badge-status <?= $ay['status'] == 1 ? 'badge-enable' : 'badge-disable' ?>">
                                            <?= $ay['status'] == 1 ? 'Enable' : 'Disable' ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="manage-item-actions">
                                    <!-- ปุ่มแก้ไข -->
                                    <button type="button" class="btn-edit"
                                        onclick="openEditModal(<?= $ay['year_id'] ?>, '<?= htmlspecialchars($ay['year_name'], ENT_QUOTES) ?>')">
                                        <i data-lucide="square-pen" style="width:16px;height:16px;"></i> แก้ไข
                                    </button>

                                    <!-- ปุ่ม Toggle สถานะ -->
                                    <form action="config_a/process.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="year_id" value="<?= $ay['year_id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $ay['status'] == 1 ? 0 : 1 ?>">
                                        <button type="submit" name="toggle_year_status"
                                            class="btn-toggle <?= $ay['status'] == 1 ? 'btn-disable' : 'btn-enable' ?>">
                                            <?= $ay['status'] == 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FAB: ปุ่มเพิ่มปีการศึกษาใหม่ -->
            <button type="button" class="fab-add" onclick="openAddModal()">
                <i data-lucide="plus" style="width:28px;height:28px;"></i>
            </button>
        </main>
    </div>

    <!-- ===== Modal เพิ่ม/แก้ไขปีการศึกษา ===== -->
    <div class="modal-overlay" id="yearModal">
        <div class="modal-box">
            <h3 id="modalTitle">เพิ่มปีการศึกษา</h3>
            <form action="config_a/process.php" method="POST" id="yearForm">
                <input type="hidden" name="year_id" id="editYearId">

                <label for="yearValue">ปีการศึกษา (พ.ศ.)</label>
                <input type="text" name="year" id="yearValue" placeholder="เช่น พ.ศ. 2568" required>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save" id="modalSubmitBtn" name="add_year">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();

        // Sidebar toggle handled by nav.js

        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่มปีการศึกษา';
            document.getElementById('editYearId').value = '';
            document.getElementById('yearValue').value = '';
            document.getElementById('modalSubmitBtn').name = 'add_year';
            document.getElementById('yearModal').classList.add('active');
            document.getElementById('yearValue').focus();
        }

        function openEditModal(id, year) {
            document.getElementById('modalTitle').textContent = 'แก้ไขปีการศึกษา';
            document.getElementById('editYearId').value = id;
            document.getElementById('yearValue').value = year;
            document.getElementById('modalSubmitBtn').name = 'edit_year';
            document.getElementById('yearModal').classList.add('active');
            document.getElementById('yearValue').focus();
        }

        function closeModal() {
            document.getElementById('yearModal').classList.remove('active');
        }

        // ปิด modal เมื่อกดข้างนอก
        document.getElementById('yearModal').addEventListener('click', function(e) {
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