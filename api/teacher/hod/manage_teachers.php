<?php
require_once '../../config/db.php';
require_once '../config_t/auth_check.php';

// Verify HOD access
check_teacher_auth(0);

// Session message
$message = $_SESSION['msg'] ?? '';
$messageType = $_SESSION['msg_type'] ?? '';
unset($_SESSION['msg'], $_SESSION['msg_type']);

// === ค้นหา ===
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM `teachers` WHERE `full_name` LIKE ? OR `username` LIKE ? ORDER BY `teacher_id` ASC");
    $stmt->execute(['%' . $search . '%', '%' . $search . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM `teachers` ORDER BY `teacher_id` ASC");
}
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Highlight search query
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
    <title>จัดการคุณครู - ระบบสำหรับหัวหน้าแผนก</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="header.css?v=<?= time() ?>">
    <link rel="stylesheet" href="nav.css?v=<?= time() ?>">
    <link rel="stylesheet" href="manage.css?v=<?= time() ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .avatar-upload-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }

        .avatar-wrapper {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f0f4f8;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .avatar-wrapper:hover {
            transform: scale(1.03);
            box-shadow: 0 6px 14px rgba(62, 188, 255, 0.25);
        }

        .avatar-preview {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            display: none;
        }

        .avatar-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .avatar-overlay {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #3EBCFF;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
        }

        .progress-ring {
            position: absolute;
            top: 0;
            left: 0;
            transform: rotate(-90deg);
            pointer-events: none;
        }

        .progress-ring-circle {
            stroke-dasharray: 276.46;
            stroke-dashoffset: 276.46;
            transition: stroke-dashoffset 0.1s linear;
        }

        .progress-text {
            position: absolute;
            font-size: 0.9rem;
            font-weight: 700;
            color: #1976d2;
            background: rgba(255, 255, 255, 0.85);
            padding: 4px 8px;
            border-radius: 12px;
            display: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .upload-hint {
            font-size: 0.8rem;
            color: #777;
            margin-top: 8px;
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

            <!-- Main Card -->
            <div class="manage-card">
                <div class="manage-card-header">
                    <div class="d-flex align-items-center gap-3">
                        <a href="index.php" class="back-btn"><i data-lucide="chevron-left" style="width:28px;height:28px;"></i></a>
                        <h2>จัดการคุณครูในแผนก</h2>
                    </div>

                    <!-- Search Bar -->
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

                    <?php if (empty($teachers)): ?>
                        <div class="no-data">
                            <i data-lucide="inbox" style="width:40px;height:40px;"></i>
                            <p>ไม่พบข้อมูลคุณครู</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($teachers as $t): ?>
                            <div class="manage-item">
                                <div class="manage-item-info">
                                    <?php if (!empty($t['image'])): ?>
                                        <img src="../../uploads/teachers/<?= htmlspecialchars($t['image']) ?>?v=<?= time() ?>" alt="Profile" style="width:44px; height:44px; border-radius:50%; object-fit:cover; border:2px solid #61BFED;" onerror="this.onerror=null; this.parentElement.innerHTML='<div style=\'width:44px; height:44px; border-radius:50%; background:#e3f2fd; color:#1976d2; display:flex; align-items:center; justify-content:center; font-weight:bold;\'><i data-lucide=\'user-check\' style=\'width:22px;height:22px;\'></i></div>'; lucide.createIcons();">
                                    <?php else: ?>
                                        <div style="width:44px; height:44px; border-radius:50%; background:#e3f2fd; color:#1976d2; display:flex; align-items:center; justify-content:center; font-weight:bold;">
                                            <i data-lucide="user-check" style="width:22px;height:22px;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="item-name">
                                            <?= highlightText($t['full_name'], $search) ?>
                                            <span style="font-size:0.85rem; color:#666; font-weight:normal;">(@<?= highlightText($t['username'], $search) ?>)</span>
                                        </p>
                                        <p style="font-size:0.85rem; color:#555; margin: 2px 0 0 0;">
                                            ตำแหน่ง: <?= $t['role'] == 0 ? '<strong style="color:#1976d2;">หัวหน้าแผนก</strong>' : 'ครูผู้สอน' ?>
                                        </p>
                                        <span class="badge-status <?= $t['status'] == 1 ? 'badge-enable' : 'badge-disable' ?>">
                                            <?= $t['status'] == 1 ? 'เปิดใช้งาน (Enable)' : 'ปิดใช้งาน (Disable)' ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="manage-item-actions">
                                    <!-- ปุ่มแก้ไข -->
                                    <button type="button" class="btn-edit"
                                        onclick="openEditModal(<?= $t['teacher_id'] ?>, '<?= htmlspecialchars($t['username'], ENT_QUOTES) ?>', '<?= htmlspecialchars($t['full_name'], ENT_QUOTES) ?>', <?= $t['role'] ?>, '<?= htmlspecialchars($t['image'] ?? '', ENT_QUOTES) ?>')">
                                        <i data-lucide="square-pen" style="width:16px;height:16px;"></i> แก้ไข
                                    </button>

                                    <!-- ปุ่ม Toggle สถานะ (ซ่อนถ้าเป็นตัวเอง) -->
                                    <?php if ($t['teacher_id'] != $_SESSION['teacher_id']): ?>
                                    <form action="../config_t/process_hod.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="teacher_id" value="<?= $t['teacher_id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $t['status'] == 1 ? 0 : 1 ?>">
                                        <button type="submit" name="toggle_teacher_status"
                                            class="btn-toggle <?= $t['status'] == 1 ? 'btn-disable' : 'btn-enable' ?>">
                                            <?= $t['status'] == 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?>
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

            <!-- FAB: ปุ่มเพิ่มครูใหม่ -->
            <button type="button" class="fab-add" onclick="openAddModal()" title="เพิ่มคุณครู">
                <i data-lucide="plus" style="width:28px;height:28px;"></i>
            </button>
        </main>
    </div>

    <!-- ===== Modal เพิ่ม/แก้ไขครู ===== -->
    <div class="modal-overlay" id="teacherModal">
        <div class="modal-box">
            <h3 id="modalTitle">เพิ่มคุณครู</h3>
            <form action="../config_t/process_hod.php" method="POST" enctype="multipart/form-data" id="teacherForm">
                <input type="hidden" name="teacher_id" id="editTeacherId">
                <input type="hidden" name="existing_image" id="existingImage">

                <!-- Avatar Upload with Circular Progress -->
                <div class="avatar-upload-container">
                    <div class="avatar-wrapper" onclick="document.getElementById('image').click()">
                        <img id="imagePreview" src="" alt="Preview" class="avatar-preview">
                        <div id="avatarPlaceholder" class="avatar-placeholder">
                            <i data-lucide="camera" style="width:28px; height:28px; color:#1976d2;"></i>
                            <span style="font-size:0.75rem; color:#666; margin-top:2px;">อัปโหลดรูป</span>
                        </div>

                        <!-- SVG Progress Ring (0-100%) -->
                        <svg class="progress-ring" width="100" height="100" id="progressRing">
                            <circle class="progress-ring-bg" stroke="#e0e0e0" stroke-width="4" fill="transparent" r="44" cx="50" cy="50" />
                            <circle class="progress-ring-circle" stroke="#3EBCFF" stroke-width="4" stroke-linecap="round" fill="transparent" r="44" cx="50" cy="50" />
                        </svg>

                        <!-- Percentage Overlay Text -->
                        <div class="progress-text" id="progressText">0%</div>

                        <div class="avatar-overlay">
                            <i data-lucide="pencil" style="width:18px; height:18px; color:#fff;"></i>
                        </div>
                    </div>
                    <input type="file" name="image" id="image" accept="image/*" style="display:none;" onchange="previewFileWithProgress()">
                    <span class="upload-hint">คลิกเพื่อเลือกรูปภาพโปรไฟล์</span>
                </div>

                <label for="fullName">ชื่อ-นามสกุล <span class="text-danger">*</span></label>
                <input type="text" name="full_name" id="fullName" placeholder="เช่น อ.สมชาย ใจดี" required>

                <label for="username">Username (ชื่อผู้ใช้เข้าระบบ) <span class="text-danger">*</span></label>
                <input type="text" name="username" id="username" placeholder="เช่น teacher01" required>

                <label for="password">Password (รหัสผ่าน)</label>
                <input type="password" name="password" id="password" placeholder="กรอกรหัสผ่าน (เว้นว่างหากไม่ต้องการเปลี่ยน)">

                <label for="role">ตำแหน่ง / สิทธิ์ <span class="text-danger">*</span></label>
                <select name="role" id="role" required>
                    <option value="1">ครูผู้สอน</option>
                    <option value="0">หัวหน้าแผนก</option>
                </select>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save" id="modalSubmitBtn" name="add_teacher">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();

        // SVG Progress Ring Calculations
        const radius = 44;
        const circumference = 2 * Math.PI * radius;

        function setProgress(percent) {
            const circle = document.querySelector('.progress-ring-circle');
            const progressText = document.getElementById('progressText');

            if (!circle || !progressText) return;

            if (percent > 0 && percent < 100) {
                circle.style.display = 'block';
                progressText.style.display = 'block';
            }

            const offset = circumference - (percent / 100 * circumference);
            circle.style.strokeDashoffset = offset;
            progressText.textContent = Math.round(percent) + '%';

            if (percent >= 100) {
                setTimeout(() => {
                    circle.style.display = 'none';
                    progressText.style.display = 'none';
                }, 400);
            }
        }

        // Preview File with Circular Progress
        function previewFileWithProgress() {
            const file = document.getElementById('image').files[0];
            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('avatarPlaceholder');
            const progressText = document.getElementById('progressText');
            const circle = document.querySelector('.progress-ring-circle');

            if (!file) return;

            setProgress(0);
            circle.style.display = 'block';
            progressText.style.display = 'block';

            const reader = new FileReader();

            reader.onprogress = function(e) {
                if (e.lengthComputable) {
                    const percent = (e.loaded / e.total) * 100;
                    setProgress(percent);
                }
            };

            reader.onloadstart = function() {
                setProgress(15);
            };

            reader.onload = function(e) {
                setProgress(100);
                setTimeout(() => {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                }, 250);
            };

            reader.readAsDataURL(file);
        }

        // Modal Functions
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่มคุณครูใหม่';
            document.getElementById('editTeacherId').value = '';
            document.getElementById('existingImage').value = '';
            document.getElementById('image').value = '';

            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('avatarPlaceholder');
            preview.style.display = 'none';
            preview.src = '';
            if (placeholder) placeholder.style.display = 'flex';

            setProgress(0);

            document.getElementById('fullName').value = '';
            document.getElementById('username').value = '';
            document.getElementById('password').value = '';
            document.getElementById('password').required = true;
            document.getElementById('role').value = '1';
            document.getElementById('modalSubmitBtn').name = 'add_teacher';
            document.getElementById('teacherModal').classList.add('active');
            document.getElementById('fullName').focus();
        }

        function openEditModal(id, username, fullName, role, image) {
            document.getElementById('modalTitle').textContent = 'แก้ไขข้อมูลคุณครู';
            document.getElementById('editTeacherId').value = id;
            document.getElementById('existingImage').value = image;
            document.getElementById('image').value = '';

            const preview = document.getElementById('imagePreview');
            const placeholder = document.getElementById('avatarPlaceholder');

            setProgress(0);

            if (image) {
                preview.src = '../../uploads/teachers/' + image;
                preview.style.display = 'block';
                if (placeholder) placeholder.style.display = 'none';
            } else {
                preview.style.display = 'none';
                preview.src = '';
                if (placeholder) placeholder.style.display = 'flex';
            }

            document.getElementById('fullName').value = fullName;
            document.getElementById('username').value = username;
            document.getElementById('password').value = '';
            document.getElementById('password').required = false;
            document.getElementById('role').value = role;
            document.getElementById('modalSubmitBtn').name = 'edit_teacher';
            document.getElementById('teacherModal').classList.add('active');
            document.getElementById('fullName').focus();
        }

        function closeModal() {
            document.getElementById('teacherModal').classList.remove('active');
        }

        document.getElementById('teacherModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
    <script src="nav.js"></script>
</body>

</html>