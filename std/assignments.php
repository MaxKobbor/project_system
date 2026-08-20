<?php
require_once '../config/db.php';
session_start();

// Check if student is logged in
if (!isset($_SESSION['group_id'])) {
    header("Location: ../login.php");
    exit();
}

$group_id = $_SESSION['group_id'];

// Get group info
$stmt = $pdo->prepare("SELECT g.status, c.year_id 
                       FROM `groups` g 
                       LEFT JOIN classrooms c ON g.class_id = c.class_id 
                       WHERE g.group_id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

$group_status = $group['status'];
$year_id = $group['year_id'] ?? 11;

// Get all assignments for this year
$assignStmt = $pdo->prepare("
    SELECT a.*, s.submission_id, s.file_name, s.submit_date, s.status_1, s.teacher_comment
    FROM `assignments` a
    LEFT JOIN `submissions` s ON a.assignment_id = s.assignment_id AND s.group_id = ?
    WHERE a.year_id = ?
    ORDER BY a.assignment_id ASC
");
$assignStmt->execute([$group_id, $year_id]);
$assignments = $assignStmt->fetchAll(PDO::FETCH_ASSOC);

// Session message
$message = $_SESSION['msg'] ?? '';
$messageType = $_SESSION['msg_type'] ?? '';
unset($_SESSION['msg'], $_SESSION['msg_type']);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <link rel="icon" type="image/png" href="../img/new_logo_svc_temp.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ส่งงาน - ระบบนักเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="nav.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .legend-container {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }
        .legend-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 15px;
            border-radius: 20px;
            border: 1px solid #ccc;
            background: #fff;
            font-size: 0.9rem;
            color: #555;
            font-weight: 500;
        }
        .legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        
        /* Colors based on mockup */
        .color-passed { background-color: #5cc85c; }
        .color-late { background-color: #f68b33; }
        .color-missing { background-color: #e71d36; }
        .color-pending { background-color: #e01e84; }
        .color-returned { background-color: #f9c70c; }
        
        .border-passed { border-left-color: #5cc85c !important; }
        .border-late { border-left-color: #f68b33 !important; }
        .border-missing { border-left-color: #e71d36 !important; }
        .border-pending { border-left-color: #e01e84 !important; }
        .border-returned { border-left-color: #f9c70c !important; }

        .legend-pill.passed { border-color: #5cc85c; color: #5cc85c; }
        .legend-pill.late { border-color: #f68b33; color: #f68b33; }
        .legend-pill.missing { border-color: #e71d36; color: #e71d36; }
        .legend-pill.pending { border-color: #e01e84; color: #e01e84; }
        .legend-pill.returned { border-color: #f9c70c; color: #f9c70c; }

        .assignment-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            padding: 20px 24px;
            margin-bottom: 20px;
            border-left: 8px solid #ccc; /* default */
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-right: 1px solid #eaeaea;
            border-top: 1px solid #eaeaea;
            border-bottom: 1px solid #eaeaea;
        }

        .assignment-info h4 {
            font-size: 1.15rem;
            font-weight: 600;
            color: #555;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        
        .assignment-info h4 .asterisk {
            color: #e71d36;
            margin-right: 5px;
            font-size: 1.3rem;
            line-height: 1;
        }

        .assignment-detail {
            font-size: 0.9rem;
            color: #999;
            margin-bottom: 4px;
        }
        
        .file-display {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #777;
            font-size: 0.95rem;
            margin: 6px 0;
        }
        .file-display a {
            color: #777;
            text-decoration: none;
            font-weight: 500;
        }
        
        .btn-send-work {
            background-color: #5bc0de;
            color: white;
            border: none;
            padding: 8px 24px;
            border-radius: 20px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.2s;
            box-shadow: 0 2px 4px rgba(91,192,222,0.3);
        }
        .btn-send-work:hover {
            background-color: #31b0d5;
            color: white;
        }

        /* Modal specific styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 1000;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal-box {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transform: translateY(-20px);
            transition: all 0.3s;
        }
        .modal-overlay.active .modal-box {
            transform: translateY(0);
        }
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .btn-cancel {
            background: #f8f9fa;
            border: 1px solid #ddd;
            padding: 8px 16px;
            border-radius: 6px;
        }
        .btn-save {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
        }
        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #fafafa;
            margin-top: 15px;
        }
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            margin-bottom: 10px;
        }
        .file-input-wrapper input[type=file] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
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
                <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($group_status == 0): ?>
                <div class="alert alert-warning d-flex align-items-center mb-4">
                    <i data-lucide="alert-triangle" class="me-3" style="width:24px;height:24px;"></i>
                    <div>
                        <h5 class="alert-heading mb-1 fw-bold">ยังไม่สามารถส่งงานได้</h5>
                        โครงงานของคุณยังไม่ได้รับการอนุมัติจากหัวหน้าแผนก กรุณารอการอนุมัติก่อนถึงจะสามารถอัปโหลดไฟล์ส่งงานได้
                    </div>
                </div>
            <?php endif; ?>

            <!-- Legends -->
            <div class="legend-container">
                <div class="legend-pill passed">
                    <span class="legend-dot color-passed"></span> ผ่านแล้ว
                </div>
                <div class="legend-pill late">
                    <span class="legend-dot color-late"></span> ส่งช้า
                </div>
                <div class="legend-pill missing">
                    <span class="legend-dot color-missing"></span> ยังไม่ส่ง
                </div>
                <div class="legend-pill pending">
                    <span class="legend-dot color-pending"></span> รอตรวจ
                </div>
                <div class="legend-pill returned">
                    <span class="legend-dot color-returned"></span> ตีกลับ
                </div>
            </div>

            <?php if (empty($assignments)): ?>
                <div class="text-center text-muted my-5">
                    <i data-lucide="folder-open" style="width:48px;height:48px; opacity:0.5; margin-bottom:10px;"></i>
                    <h5>ยังไม่มีหัวข้องานในปีการศึกษานี้</h5>
                </div>
            <?php else: ?>
                <?php foreach ($assignments as $a): ?>
                    <?php
                        $is_locked = ($group_status == 0 || $a['status'] == 0);
                        $has_submission = !empty($a['submission_id']);
                        $status_1 = $a['status_1'];
                        
                        // Check if submission is late
                        $is_late = false;
                        if ($has_submission && $a['deadline_date'] != null) {
                            $deadline_str = $a['deadline_date'] . ' ' . ($a['deadline_time'] ?? '23:59:59');
                            if (strtotime($a['submit_date']) > strtotime($deadline_str)) {
                                $is_late = true;
                            }
                        } else if (!$has_submission && $a['deadline_date'] != null) {
                            $deadline_str = $a['deadline_date'] . ' ' . ($a['deadline_time'] ?? '23:59:59');
                            if (time() > strtotime($deadline_str)) {
                                $is_late = true;
                            }
                        }

                        // Determine Border Class
                        $border_class = 'border-missing'; // default
                        if ($has_submission) {
                            if ($status_1 == 1) {
                                $border_class = 'border-passed';
                            } elseif ($status_1 == 2) {
                                $border_class = 'border-returned';
                            } else {
                                $border_class = 'border-pending';
                            }
                        }
                        // If it's late, we override color to late (orange) if it's not passed yet? 
                        // The mockup shows separate late state. Let's say if late and not passed, show orange.
                        if ($is_late && $status_1 != 1) {
                            $border_class = 'border-late';
                        }
                    ?>
                    
                    <div class="assignment-card <?= $border_class ?>">
                        <div class="assignment-info flex-grow-1">
                            <h4><span class="asterisk">*</span> <?= htmlspecialchars($a['assignment_name']) ?></h4>
                            <div class="assignment-detail">
                                กำหนดส่ง : <?= htmlspecialchars($a['deadline_date'] ?? '-') ?> <?= htmlspecialchars($a['deadline_time'] ?? '') ?>
                                <?php if ($a['status'] == 0): ?>
                                    <span class="text-danger ms-2">(ปิดรับส่งงาน)</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="file-display">
                                <i data-lucide="file-text" style="width:16px;height:16px; color:#aaa;"></i>
                                <?php if ($has_submission && !empty($a['file_name'])): ?>
                                    <a href="../uploads/submissions/<?= htmlspecialchars($a['file_name']) ?>" target="_blank">
                                        <?= htmlspecialchars($a['file_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span>ชื่อไฟล์</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="assignment-detail">
                                ส่งเมื่อ : <?= $has_submission ? htmlspecialchars($a['submit_date']) : '-' ?>
                            </div>

                            <?php if ($status_1 == 2 && !empty($a['teacher_comment'])): ?>
                                <div class="mt-2 p-2" style="background:#fffcf0; border-left:3px solid #f9c70c; font-size:0.85rem; color:#555;">
                                    <strong>คอมเมนต์แก้ไข:</strong> <?= nl2br(htmlspecialchars($a['teacher_comment'])) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="assignment-action ms-3">
                            <?php if ($status_1 != 1 && !$is_locked): ?>
                                <button type="button" class="btn-send-work" onclick="openUploadModal(<?= $a['assignment_id'] ?>, '<?= htmlspecialchars($a['assignment_name'], ENT_QUOTES) ?>')">
                                    <i data-lucide="send" style="width:16px;height:16px;"></i> ส่งงาน
                                </button>
                            <?php else: ?>
                                <button type="button" class="btn-send-work" style="opacity:0.5; cursor:not-allowed;" disabled>
                                    <i data-lucide="send" style="width:16px;height:16px;"></i> ส่งงาน
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </main>
    </div>

    <!-- Upload Modal -->
    <div class="modal-overlay" id="uploadModal">
        <div class="modal-box">
            <h3 id="modalTitle">ส่งงาน: <span id="modalAssignName" class="text-primary"></span></h3>
            <form action="config_s/process_std.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload_submission">
                <input type="hidden" name="assignment_id" id="modalAssignId">
                
                <div class="upload-area">
                    <div class="file-input-wrapper">
                        <button type="button" class="btn btn-outline-primary"><i data-lucide="upload" style="width:16px;height:16px;"></i> เลือกไฟล์</button>
                        <input type="file" name="submission_file" required onchange="document.getElementById('fileNameDisplay').textContent = this.files[0].name">
                    </div>
                    <div id="fileNameDisplay" class="text-muted mt-2" style="font-size:0.9rem;">ยังไม่ได้เลือกไฟล์...</div>
                </div>
                
                <div class="modal-actions mt-4">
                    <button type="button" class="btn-cancel" onclick="closeModal()">ยกเลิก</button>
                    <button type="submit" class="btn-save">อัปโหลดส่งงาน</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();

        function openUploadModal(assignId, assignName) {
            document.getElementById('modalAssignId').value = assignId;
            document.getElementById('modalAssignName').textContent = assignName;
            document.getElementById('fileNameDisplay').textContent = 'ยังไม่ได้เลือกไฟล์...';
            document.getElementById('uploadModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('uploadModal').classList.remove('active');
        }

        document.getElementById('uploadModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
    <script src="nav.js"></script>
</body>

</html>
