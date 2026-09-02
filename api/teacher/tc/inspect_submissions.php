<?php
require_once '../../config/db.php';
require_once '../config_t/auth_check.php';

// Verify TC access
check_teacher_auth(1);

$teacher_id = $_SESSION['teacher_id'];

// Fetch assignments for filter
$assignmentStmt = $pdo->query("SELECT assignment_id, assignment_name FROM assignments ORDER BY assignment_id ASC");
$assignments = $assignmentStmt->fetchAll(PDO::FETCH_ASSOC);

// Filters
$search = trim($_GET['search'] ?? '');
$assignmentFilter = isset($_GET['assignment_id']) && $_GET['assignment_id'] !== '' ? intval($_GET['assignment_id']) : null;

// Default status_1 to 0 (รอตรวจ) if not specified
if (!isset($_GET['status_1'])) {
    $statusFilter = 0; // ค่าเริ่มต้น: รอตรวจ
} elseif ($_GET['status_1'] === 'all' || $_GET['status_1'] === '') {
    $statusFilter = null; // ทั้งหมด
} else {
    $statusFilter = intval($_GET['status_1']);
}

$query = "SELECT s.*, g.username AS group_name, g.project_name, a.assignment_name, c.class_name
          FROM `submissions` s
          JOIN `groups` g ON s.group_id = g.group_id
          LEFT JOIN `assignments` a ON s.assignment_id = a.assignment_id
          LEFT JOIN `classrooms` c ON g.class_id = c.class_id
          WHERE g.teacher_id = ?";

$params = [$teacher_id];

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
    // If status filter is 0, we should also include NULL because new submissions might have NULL status_1
    if ($statusFilter === 0) {
        $query .= " AND (s.status_1 = 0 OR s.status_1 IS NULL)";
    } else {
        $query .= " AND s.status_1 = ?";
        $params[] = $statusFilter;
    }
}

$query .= " ORDER BY s.submit_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch counts for filter badges
$countStmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_count,
        COALESCE(SUM(CASE WHEN s.status_1 = 0 OR s.status_1 IS NULL THEN 1 ELSE 0 END), 0) as pending_count,
        COALESCE(SUM(CASE WHEN s.status_1 = 1 THEN 1 ELSE 0 END), 0) as passed_count,
        COALESCE(SUM(CASE WHEN s.status_1 = 2 THEN 1 ELSE 0 END), 0) as returned_count
    FROM `submissions` s
    JOIN `groups` g ON s.group_id = g.group_id
    WHERE g.teacher_id = ?
");
$countStmt->execute([$teacher_id]);
$counts = $countStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_count' => 0, 'pending_count' => 0, 'passed_count' => 0, 'returned_count' => 0];

// Session message
$message = $_SESSION['msg'] ?? '';
$messageType = $_SESSION['msg_type'] ?? '';
unset($_SESSION['msg'], $_SESSION['msg_type']);

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
    <link rel="icon" type="image/png" href="../../img/new_logo_svc_temp.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตรวจงานนักเรียน - ระบบครูผู้สอน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="header.css?v=<?= time() ?>">
    <link rel="stylesheet" href="nav.css?v=<?= time() ?>">
    <link rel="stylesheet" href="manage.css?v=<?= time() ?>">
    <script src="https://unpkg.com/lucide@latest"></script>
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
                        <h2>ตรวจและส่งคืนงานนักเรียน</h2>
                    </div>

                    <!-- Search Bar -->
                    <form method="GET" class="search-bar">
                        <?php if ($assignmentFilter !== null): ?>
                            <input type="hidden" name="assignment_id" value="<?= $assignmentFilter ?>">
                        <?php endif; ?>
                        <?php if ($statusFilter !== null): ?>
                            <input type="hidden" name="status_1" value="<?= $statusFilter ?>">
                        <?php else: ?>
                            <input type="hidden" name="status_1" value="all">
                        <?php endif; ?>
                        <input type="text" name="search" placeholder="ค้นหากลุ่ม, โครงงาน, ไฟล์" value="<?= htmlspecialchars($search) ?>">
                        <button type="submit"><i data-lucide="search" style="width:18px;height:18px;"></i></button>
                    </form>
                </div>

                <div class="manage-card-body">
                    <!-- Filters Dropdown & Status Filter Pills -->
                    <div class="row mb-3 align-items-center">
                        <div class="col-md-6 mb-2 mb-md-0">
                            <form method="GET" class="d-flex align-items-center gap-2">
                                <label class="fw-bold text-muted text-nowrap" style="font-size:0.9rem;">หัวข้องาน:</label>
                                <?php if ($search !== ''): ?>
                                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                                <?php endif; ?>
                                <?php if ($statusFilter !== null): ?>
                                    <input type="hidden" name="status_1" value="<?= $statusFilter ?>">
                                <?php else: ?>
                                    <input type="hidden" name="status_1" value="all">
                                <?php endif; ?>
                                <select name="assignment_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                                    <option value="">-- ทั้งหมดทุกหัวข้องาน --</option>
                                    <?php foreach ($assignments as $a): ?>
                                        <option value="<?= $a['assignment_id'] ?>" <?= $assignmentFilter === intval($a['assignment_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($a['assignment_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </div>
                        <div class="col-md-6 d-flex justify-content-md-end gap-2 flex-wrap filter-nav">
                            <a href="inspect_submissions.php?<?= http_build_query(array_merge($_GET, ['status_1' => 'all'])) ?>" class="filter-btn <?= $statusFilter === null ? 'active' : '' ?>">
                                <i data-lucide="layers" style="width:15px;height:15px;"></i> ทั้งหมด
                                <span class="filter-badge"><?= intval($counts['total_count']) ?></span>
                            </a>
                            <a href="inspect_submissions.php?<?= http_build_query(array_merge($_GET, ['status_1' => '0'])) ?>" class="filter-btn btn-pending <?= $statusFilter === 0 ? 'active' : '' ?>">
                                <i data-lucide="clock" style="width:15px;height:15px;"></i> รอตรวจ
                                <span class="filter-badge"><?= intval($counts['pending_count']) ?></span>
                            </a>
                            <a href="inspect_submissions.php?<?= http_build_query(array_merge($_GET, ['status_1' => '1'])) ?>" class="filter-btn btn-passed <?= $statusFilter === 1 ? 'active' : '' ?>">
                                <i data-lucide="check-circle" style="width:15px;height:15px;"></i> ผ่านแล้ว
                                <span class="filter-badge"><?= intval($counts['passed_count']) ?></span>
                            </a>
                            <a href="inspect_submissions.php?<?= http_build_query(array_merge($_GET, ['status_1' => '2'])) ?>" class="filter-btn btn-returned <?= $statusFilter === 2 ? 'active' : '' ?>">
                                <i data-lucide="x-circle" style="width:15px;height:15px;"></i> ตีกลับแก้
                                <span class="filter-badge"><?= intval($counts['returned_count']) ?></span>
                            </a>
                        </div>
                    </div>

                    <?php if ($search !== ''): ?>
                        <div class="search-result-text mb-3">
                            ผลลัพธ์การค้นหา "<?= htmlspecialchars($search) ?>"
                        </div>
                    <?php endif; ?>

                    <?php if (empty($submissions)): ?>
                        <div class="no-data">
                            <i data-lucide="inbox" style="width:40px;height:40px;"></i>
                            <p>ไม่มีงานนักเรียนในกลุ่มที่ปรึกษาของคุณ</p>
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
                                            ห้อง: <?= htmlspecialchars($sub['class_name'] ?? '-') ?>
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

                                        <?php if (!empty($sub['teacher_comment'])): ?>
                                            <div class="p-2 border rounded bg-light mt-2" style="font-size:0.85rem;">
                                                <strong class="text-secondary"><i data-lucide="message-square" style="width:14px;height:14px;display:inline;"></i> คอมเมนต์ล่าสุดของคุณ:</strong>
                                                <p class="mb-0 mt-1 text-dark"><?= nl2br(htmlspecialchars($sub['teacher_comment'])) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="manage-item-actions flex-column align-items-end gap-2">
                                    <?php if ($sub['status_1'] == 1): ?>
                                        <span class="badge bg-success p-2 mb-1" style="font-size:0.85rem;"><i data-lucide="check-circle" style="width:14px;height:14px;display:inline;"></i> ตรวจผ่านแล้ว</span>
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                            data-sub-id="<?= $sub['submission_id'] ?>"
                                            data-group-name="<?= htmlspecialchars($sub['group_name'] ?? '') ?>"
                                            data-assignment-name="<?= htmlspecialchars($sub['assignment_name'] ?? '') ?>"
                                            data-status="<?= $sub['status_1'] ?? 0 ?>"
                                            data-comment="<?= htmlspecialchars($sub['teacher_comment'] ?? '') ?>"
                                            onclick="openInspectModalFromBtn(this)">แก้ไขผลตรวจ</button>
                                    <?php elseif ($sub['status_1'] == 2): ?>
                                        <span class="badge bg-danger p-2 mb-1" style="font-size:0.85rem;"><i data-lucide="x-circle" style="width:14px;height:14px;display:inline;"></i> ตีกลับแก้ไข</span>
                                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                            data-sub-id="<?= $sub['submission_id'] ?>"
                                            data-group-name="<?= htmlspecialchars($sub['group_name'] ?? '') ?>"
                                            data-assignment-name="<?= htmlspecialchars($sub['assignment_name'] ?? '') ?>"
                                            data-status="<?= $sub['status_1'] ?? 0 ?>"
                                            data-comment="<?= htmlspecialchars($sub['teacher_comment'] ?? '') ?>"
                                            onclick="openInspectModalFromBtn(this)">แก้ไขผลตรวจ</button>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark p-2 mb-1" style="font-size:0.85rem;"><i data-lucide="clock" style="width:14px;height:14px;display:inline;"></i> รอตรวจงาน</span>
                                        <button type="button" class="btn-inspect"
                                            data-sub-id="<?= $sub['submission_id'] ?>"
                                            data-group-name="<?= htmlspecialchars($sub['group_name'] ?? '') ?>"
                                            data-assignment-name="<?= htmlspecialchars($sub['assignment_name'] ?? '') ?>"
                                            data-status="0"
                                            data-comment=""
                                            onclick="openInspectModalFromBtn(this)">
                                            <i data-lucide="file-check-2" style="width:16px;height:16px;"></i> ตรวจงาน
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- ===== Modal ตรวจงาน ===== -->
    <div class="modal-overlay" id="inspectModal">
        <div class="modal-box">
            <h3 id="modalTitle">ตรวจงานนักเรียน</h3>
            <div class="mb-3" style="font-size:0.9rem;">
                <div>กลุ่ม: <strong id="modalGroupName" class="text-primary"></strong></div>
                <div>หัวข้อ: <strong id="modalAssignmentName"></strong></div>
            </div>
            
            <form action="../config_t/process_tc.php" method="POST">
                <input type="hidden" name="action" value="inspect_submission">
                <input type="hidden" name="submission_id" id="inspectSubmissionId">

                <label class="mb-2 fw-bold">ผลการตรวจงาน <span class="text-danger">*</span></label>
                <div class="d-flex gap-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status_1" id="statusPass" value="1" required onchange="toggleCommentRequire(false)">
                        <label class="form-check-label text-success fw-bold" for="statusPass">
                            ผ่าน (อนุมัติงาน)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status_1" id="statusReturn" value="2" required onchange="toggleCommentRequire(true)">
                        <label class="form-check-label text-danger fw-bold" for="statusReturn">
                            ตีกลับ (ต้องแก้ไข)
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="teacherComment" class="fw-bold">คอมเมนต์ / ข้อเสนอแนะ <span id="commentReqStar" class="text-danger" style="display:none;">*</span></label>
                    <textarea name="teacher_comment" id="teacherComment" class="form-control" rows="4" placeholder="ระบุสิ่งที่ต้องแก้ไข หรือข้อเสนอแนะเพิ่มเติม..."></textarea>
                    <small class="text-muted d-block mt-1">หากเลือกตีกลับงาน ควรระบุคอมเมนต์เพื่อให้นักเรียนทราบจุดที่ต้องแก้ไข</small>
                </div>

                <div class="modal-actions mt-4">
                    <button type="button" class="btn-cancel" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save">บันทึกผลการตรวจ</button>
                </div>
            </form>
        </div>
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

        function toggleCommentRequire(isRequired) {
            const star = document.getElementById('commentReqStar');
            const textarea = document.getElementById('teacherComment');
            if (isRequired) {
                star.style.display = 'inline';
                textarea.required = true;
            } else {
                star.style.display = 'none';
                textarea.required = false;
            }
        }

        function openInspectModalFromBtn(btn) {
            const subId = btn.dataset.subId;
            const groupName = btn.dataset.groupName;
            const assignmentName = btn.dataset.assignmentName;
            const status = parseInt(btn.dataset.status) || 0;
            const comment = btn.dataset.comment || '';
            openInspectModal(subId, groupName, assignmentName, status, comment);
        }

        function openInspectModal(subId, groupName, assignmentName, status, comment) {
            document.getElementById('inspectSubmissionId').value = subId;
            document.getElementById('modalGroupName').textContent = groupName;
            document.getElementById('modalAssignmentName').textContent = assignmentName;
            document.getElementById('teacherComment').value = comment || '';

            if (status == 1) {
                document.getElementById('statusPass').checked = true;
                toggleCommentRequire(false);
            } else if (status == 2) {
                document.getElementById('statusReturn').checked = true;
                toggleCommentRequire(true);
            } else {
                document.getElementById('statusPass').checked = false;
                document.getElementById('statusReturn').checked = false;
                toggleCommentRequire(false);
            }
            
            document.getElementById('inspectModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('inspectModal').classList.remove('active');
        }

        document.getElementById('inspectModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeFileViewer();
            }
        });

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
    </script>
    <script src="nav.js"></script>
</body>

</html>
