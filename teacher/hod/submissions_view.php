<?php
require_once '../../config/db.php';
require_once '../config_t/auth_check.php';

// Verify HOD access
check_teacher_auth(0);

// Fetch all assignments for dropdown filter
$assignmentStmt = $pdo->query("SELECT assignment_id, assignment_name FROM assignments ORDER BY assignment_id ASC");
$assignments = $assignmentStmt->fetchAll(PDO::FETCH_ASSOC);

$group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;

// Filters
$search = trim($_GET['search'] ?? '');
$assignmentFilter = isset($_GET['assignment_id']) && $_GET['assignment_id'] !== '' ? intval($_GET['assignment_id']) : null;
$statusFilter = isset($_GET['status_1']) && $_GET['status_1'] !== '' ? intval($_GET['status_1']) : null;

$query = "SELECT s.*, g.username AS group_name, g.project_name, t.full_name AS teacher_name, a.assignment_name, c.class_name
          FROM `submissions` s
          JOIN `groups` g ON s.group_id = g.group_id
          LEFT JOIN `assignments` a ON s.assignment_id = a.assignment_id
          LEFT JOIN `teachers` t ON g.teacher_id = t.teacher_id
          LEFT JOIN `classrooms` c ON g.class_id = c.class_id
          WHERE s.group_id = ?";

$params = [$group_id];

if ($search !== '') {
    $query .= " AND (g.username LIKE ? OR g.project_name LIKE ? OR s.file_name LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($assignmentFilter !== null) {
    $query .= " AND s.assignment_id = ?";
    $params[] = $assignmentFilter;
}

if ($statusFilter !== null) {
    $query .= " AND s.status_1 = ?";
    $params[] = $statusFilter;
}

$query .= " ORDER BY s.submit_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>ดูงานที่นักเรียนส่งมา - สำหรับหัวหน้าแผนก (Read-Only)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="header.css?v=<?= time() ?>">
    <link rel="stylesheet" href="nav.css?v=<?= time() ?>">
    <link rel="stylesheet" href="manage.css?v=<?= time() ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .badge-read-only {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="manage-card">
                <div class="manage-card-header">
                    <div class="d-flex align-items-center gap-3">
                        <a href="project_approvals.php" class="back-btn"><i data-lucide="chevron-left" style="width:28px;height:28px;"></i></a>
                        <div>
                            <h2 class="d-inline">ดูการส่งงานของนักเรียน</h2>
                            <span class="badge-read-only ms-2"><i data-lucide="eye" style="width:14px;height:14px;display:inline;"></i> สิทธิ์ดูได้อย่างเดียว (Read-Only)</span>
                        </div>
                    </div>

                    <!-- Search Bar -->
                    <form method="GET" class="search-bar">
                        <input type="hidden" name="group_id" value="<?= $group_id ?>">
                        <?php if ($statusFilter !== null): ?>
                            <input type="hidden" name="status_1" value="<?= $statusFilter ?>">
                        <?php endif; ?>
                        <input type="text" name="search" placeholder="ค้นหาชื่องาน, ไฟล์" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit"><i data-lucide="search" style="width:18px;height:18px;"></i></button>
                    </form>
                </div>

                <div class="manage-card-body">
                    <!-- Filters Dropdown & Filter Pills -->
                    <form method="GET" class="row g-3 mb-3 align-items-center">
                        <input type="hidden" name="group_id" value="<?= $group_id ?>">
                        <div class="col-auto">
                            <label class="fw-bold text-muted" style="font-size:0.9rem;">หัวข้องาน:</label>
                        </div>
                        <div class="col-auto">
                            <select name="assignment_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">-- ทั้งหมดทุกหัวข้องาน --</option>
                                <?php foreach ($assignments as $a): ?>
                                    <option value="<?= $a['assignment_id'] ?>" <?= $assignmentFilter === intval($a['assignment_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($a['assignment_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>

                    <?php if ($search !== ''): ?>
                        <div class="search-result-text mb-3">
                            ผลลัพธ์การค้นหา "<?= htmlspecialchars($search) ?>"
                        </div>
                    <?php endif; ?>

                    <?php if (empty($submissions)): ?>
                        <div class="no-data">
                            <i data-lucide="inbox" style="width:40px;height:40px;"></i>
                            <p>ไม่พบรายการส่งงานของนักเรียน</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($submissions as $sub): ?>
                            <div class="manage-item" style="align-items:flex-start;">
                                <div class="manage-item-info">
                                    <i data-lucide="file-text" class="item-icon" style="width:22px;height:22px; margin-top:2px;"></i>
                                    <div>
                                        <p class="item-name" style="font-size:1.05rem; font-weight:600; margin-bottom:2px;">
                                            กลุ่ม: <?= highlightText($sub['group_name'], $search) ?>
                                            <span class="text-muted fw-normal"> (<?= highlightText($sub['project_name'], $search) ?>)</span>
                                        </p>
                                        <p style="font-size:0.88rem; color:#555; margin-bottom:4px;">
                                            หัวข้องาน: <strong><?= htmlspecialchars($sub['assignment_name'] ?? 'ไม่ระบุ') ?></strong> |
                                            ครูที่ปรึกษา: <?= htmlspecialchars($sub['teacher_name'] ?? 'ไม่ระบุ') ?>
                                        </p>
                                        <p style="font-size:0.83rem; color:#777; margin-bottom:6px;">
                                            <i data-lucide="calendar" style="width:14px;height:14px;display:inline;"></i> วันที่ส่ง: <?= htmlspecialchars($sub['submit_date'] ?? '-') ?> |
                                            <?php if (!empty($sub['file_name'])): ?>
                                                <?php $ext = strtolower(pathinfo($sub['file_name'], PATHINFO_EXTENSION)); ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" onclick="openFileViewer('../../uploads/submissions/<?= htmlspecialchars($sub['file_name']) ?>', '<?= $ext ?>')">
                                                    <i data-lucide="eye" style="width:14px;height:14px; margin-right:2px;"></i> ดูไฟล์งาน
                                                </button>
                                                <a href="../../uploads/submissions/<?= htmlspecialchars($sub['file_name']) ?>" download class="ms-2 fw-bold text-decoration-none">
                                                    (ดาวน์โหลด)
                                                </a>
                                            <?php else: ?>
                                                ไม่มีไฟล์
                                            <?php endif; ?>
                                        </p>

                                        <!-- หมายเหตุคอมเมนต์ตรวจงานจากครูผู้สอน -->
                                        <?php if (!empty($sub['teacher_comment'])): ?>
                                            <div class="p-2 border rounded bg-light mt-2" style="font-size:0.85rem;">
                                                <strong class="text-secondary"><i data-lucide="message-square" style="width:14px;height:14px;display:inline;"></i> หมายเหตุ/คอมเมนต์จากครูผู้สอน:</strong>
                                                <p class="mb-0 mt-1 text-dark"><?= nl2br(htmlspecialchars($sub['teacher_comment'])) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="manage-item-actions flex-column align-items-end gap-1">
                                    <!-- สถานะการตรวจงาน -->
                                    <?php if ($sub['status_1'] == 1): ?>
                                        <span class="badge bg-success p-2" style="font-size:0.85rem;"><i data-lucide="check-circle" style="width:14px;height:14px;display:inline;"></i> ตรวจผ่านแล้ว</span>
                                    <?php elseif ($sub['status_1'] == 2): ?>
                                        <span class="badge bg-danger p-2" style="font-size:0.85rem;"><i data-lucide="x-circle" style="width:14px;height:14px;display:inline;"></i> ตีกลับแก้ไข</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark p-2" style="font-size:0.85rem;"><i data-lucide="clock" style="width:14px;height:14px;display:inline;"></i> รอตรวจงาน</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- ===== Modal ดูไฟล์งาน ===== -->
    <div class="modal-overlay" id="fileViewerModal">
        <div class="modal-box" style="max-width: 90%; width: 1000px; padding: 20px;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">ตัวอย่างไฟล์งาน</h4>
                <button type="button" class="btn-close" onclick="closeFileViewer()"></button>
            </div>
            
            <div id="fileViewerContent" style="background:#f8f9fa; border:1px solid #ddd; border-radius:8px; overflow:hidden;">
                <!-- Iframe or image will be injected here -->
            </div>
            
            <div id="notSupportedMsg" style="display:none; text-align:center; padding: 40px 20px;">
                <i data-lucide="file-warning" style="width:48px;height:48px; color:#f0ad4e; margin-bottom:10px;"></i>
                <h5 class="text-secondary">ไม่สามารถเปิดดูไฟล์ประเภทนี้ในเบราว์เซอร์ได้</h5>
                <p class="text-muted">โปรดดาวน์โหลดไฟล์เพื่อเปิดดูในเครื่องของคุณ</p>
                <!-- Download link injected here -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();

        // File Viewer Functions
        function openFileViewer(fileUrl, ext) {
            const viewerContent = document.getElementById('fileViewerContent');
            const fileViewerModal = document.getElementById('fileViewerModal');
            const notSupportedMsg = document.getElementById('notSupportedMsg');
            
            viewerContent.innerHTML = ''; // clear previous
            notSupportedMsg.style.display = 'none';

            if (['pdf', 'jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                // Supported inline formats
                const iframe = document.createElement('iframe');
                iframe.src = fileUrl;
                iframe.style.width = '100%';
                iframe.style.height = '70vh';
                iframe.style.border = 'none';
                viewerContent.appendChild(iframe);
            } else {
                // Not supported for inline viewing (e.g. docx, xlsx)
                notSupportedMsg.style.display = 'block';
                const link = document.createElement('a');
                link.href = fileUrl;
                link.className = 'btn btn-primary mt-3';
                link.setAttribute('download', '');
                link.innerHTML = '<i data-lucide="download" style="width:16px;height:16px;"></i> ดาวน์โหลดไฟล์แทน';
                notSupportedMsg.appendChild(link);
                lucide.createIcons();
            }
            
            fileViewerModal.classList.add('active');
        }

        function closeFileViewer() {
            document.getElementById('fileViewerModal').classList.remove('active');
            document.getElementById('fileViewerContent').innerHTML = ''; // clear memory
        }

        document.getElementById('fileViewerModal').addEventListener('click', function(e) {
            if (e.target === this) closeFileViewer();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeFileViewer();
            }
        });
    </script>
    <script src="nav.js"></script>
</body>

</html>