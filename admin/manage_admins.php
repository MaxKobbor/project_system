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
    $stmt = $pdo->prepare("SELECT * FROM `admins` WHERE `full_name` LIKE ? OR `username` LIKE ? ORDER BY `admin_id` ASC");
    $stmt->execute(['%' . $search . '%', '%' . $search . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM `admins` ORDER BY `admin_id` ASC");
}
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>จัดการแอดมิน - ระบบผู้ดูแล</title>
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
                        <h2>แอดมิน</h2>
                    </div>

                    <!-- Search Bar inside header -->
                    <form method="GET" class="search-bar">
                        <input type="text" name="search" placeholder="ค้นหาชื่อ หรือ Username" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit"><i data-lucide="search" style="width:18px;height:18px;"></i></button>
                    </form>
                </div>

                <div class="manage-card-body">
                    <?php if ($search !== ''): ?>
                        <div class="search-result-text">
                            ผลลัพธ์การค้นหา "<?= htmlspecialchars($search) ?>"
                        </div>
                    <?php endif; ?>

                    <?php if (empty($admins)): ?>
                        <div class="no-data">
                            <i data-lucide="inbox" style="width:40px;height:40px;"></i>
                            <p>ไม่พบข้อมูลผู้ดูแลระบบ</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($admins as $a): ?>
                            <div class="manage-item">
                                <div class="manage-item-info">
                                    <i data-lucide="shield-check" class="item-icon" style="width:20px;height:20px;"></i>
                                    <div>
                                        <p class="item-name">
                                            <?= highlightText($a['full_name'], $search) ?>
                                            <span style="font-size:0.85rem; color:#666; font-weight:normal;">(@<?= highlightText($a['username'], $search) ?>)</span>
                                        </p>
                                        <span class="badge-status <?= $a['status'] == 1 ? 'badge-enable' : 'badge-disable' ?>">
                                            <?= $a['status'] == 1 ? 'Enable' : 'Disable' ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="manage-item-actions">
                                    <!-- ปุ่มแก้ไข -->
                                    <button type="button" class="btn-edit"
                                        onclick="openEditModal(<?= $a['admin_id'] ?>, '<?= htmlspecialchars($a['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($a['full_name'], ENT_QUOTES) ?>')">
                                        <i data-lucide="square-pen" style="width:16px;height:16px;"></i> แก้ไข
                                    </button>

                                    <!-- ปุ่ม Toggle สถานะ (ซ่อนถ้าเป็นตัวเอง) -->
                                    <?php if ($a['admin_id'] != $_SESSION['admin_id']): ?>
                                    <form action="config_a/process.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="admin_id" value="<?= $a['admin_id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $a['status'] == 1 ? 0 : 1 ?>">
                                        <button type="submit" name="toggle_admin_status"
                                            class="btn-toggle <?= $a['status'] == 1 ? 'btn-disable' : 'btn-enable' ?>">
                                            <?= $a['status'] == 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?>
                                        </button>
                                    </form>
                                    <?php else: ?>
                                    <button type="button" class="btn-toggle btn-disable" style="opacity: 0.5; cursor: not-allowed;" title="ไม่สามารถปิดการใช้งานตัวเองได้">
                                        ปิดใช้งานไม่ได้
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- FAB: ปุ่มเพิ่มแอดมินใหม่ -->
            <button type="button" class="fab-add" onclick="openAddModal()">
                <i data-lucide="plus" style="width:28px;height:28px;"></i>
            </button>
        </main>
    </div>

    <!-- ===== Modal เพิ่ม/แก้ไขแอดมิน ===== -->
    <div class="modal-overlay" id="adminModal">
        <div class="modal-box">
            <h3 id="modalTitle">เพิ่มผู้ดูแลระบบ</h3>
            <form action="config_a/process.php" method="POST" id="adminForm">
                <input type="hidden" name="admin_id" id="editAdminId">

                <label for="fullName">ชื่อ-นามสกุล</label>
                <input type="text" name="full_name" id="fullName" placeholder="เช่น ผู้ดูแลระบบหลัก" required>

                <label for="username">Username (ชื่อผู้ใช้เข้าระบบ)</label>
                <input type="text" name="username" id="username" placeholder="เช่น admin02" required>

                <label for="password">Password (รหัสผ่าน)</label>
                <input type="password" name="password" id="password" placeholder="กรอกรหัสผ่าน (หากไม่ต้องการเปลี่ยนแก้ไขเว้นว่างไว้)">

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save" id="modalSubmitBtn" name="add_admin">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();

        // Sidebar toggle
        // (Handled by nav.js)

        // Modal functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่มผู้ดูแลระบบ';
            document.getElementById('editAdminId').value = '';
            document.getElementById('fullName').value = '';
            document.getElementById('username').value = '';
            document.getElementById('username').readOnly = false;
            document.getElementById('password').value = '';
            document.getElementById('password').required = true;
            document.getElementById('modalSubmitBtn').name = 'add_admin';
            document.getElementById('adminModal').classList.add('active');
            document.getElementById('fullName').focus();
        }

        function openEditModal(id, username, fullName) {
            document.getElementById('modalTitle').textContent = 'แก้ไขข้อมูลผู้ดูแลระบบ';
            document.getElementById('editAdminId').value = id;
            document.getElementById('fullName').value = fullName;
            document.getElementById('username').value = username;
            document.getElementById('username').readOnly = true;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('modalSubmitBtn').name = 'edit_admin';
            document.getElementById('adminModal').classList.add('active');
            document.getElementById('fullName').focus();
        }

        function closeModal() {
            document.getElementById('adminModal').classList.remove('active');
        }

        // ปิด modal เมื่อกดข้างนอก
        document.getElementById('adminModal').addEventListener('click', function(e) {
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
