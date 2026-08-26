<?php
require_once '../config/db.php';

$group_id = intval($_GET['group_id'] ?? 0);
if ($group_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch group info
$stmt = $pdo->prepare("SELECT g.*, t.full_name AS teacher_name, c.class_name, b.branch_name, c.year_id
                       FROM `groups` g
                       LEFT JOIN `teachers` t ON g.teacher_id = t.teacher_id
                       LEFT JOIN `classrooms` c ON g.class_id = c.class_id
                       LEFT JOIN `branches` b ON c.branch_id = b.branch_id
                       WHERE g.group_id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$group) {
    header("Location: index.php");
    exit();
}

// Verify group is 100% complete
$year_id = $group['year_id'] ?? 0;
$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE year_id = ?");
$totalStmt->execute([$year_id]);
$total_assignments = $totalStmt->fetchColumn();

$passedStmt = $pdo->prepare("SELECT COUNT(*) FROM submissions s 
                              JOIN assignments a ON s.assignment_id = a.assignment_id 
                              WHERE s.group_id = ? AND a.year_id = ? AND s.status_1 = 1");
$passedStmt->execute([$group_id, $year_id]);
$passed_assignments = $passedStmt->fetchColumn();

if ($total_assignments == 0 || $passed_assignments < $total_assignments) {
    header("Location: index.php");
    exit();
}

// Fetch all passed submissions
$subStmt = $pdo->prepare("SELECT s.*, a.assignment_name 
                           FROM submissions s
                           JOIN assignments a ON s.assignment_id = a.assignment_id
                           WHERE s.group_id = ? AND a.year_id = ? AND s.status_1 = 1
                           ORDER BY a.assignment_id ASC");
$subStmt->execute([$group_id, $year_id]);
$submissions = $subStmt->fetchAll(PDO::FETCH_ASSOC);

$members = array_filter([$group['std_1'], $group['std_2'], $group['std_3'], $group['std_4']]);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <link rel="icon" type="image/png" href="../img/new_logo_svc_temp.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($group['project_name']) ?> - เยี่ยมชมโครงงาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f0f2f5;
            min-height: 100vh;
            margin: 0;
        }

        .visit-header {
            background: linear-gradient(180deg, #5eb2ff 0%, #c4e4ff 100%);
            padding: 15px 25px;
            color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .visit-header h1,
        .visit-header p {
            color: #fff;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.15);
        }

        .header-logo {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #fff;
            border: 2px solid #a71e16;
        }

        .detail-main {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 30px 20px;
            box-sizing: border-box;
        }

        .title-bar {
            background: #e8f4fd;
            border-radius: 12px;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            border: 1px solid #d0e8f5;
        }

        .title-bar .back-btn {
            color: #5eb2ff;
            font-size: 1.8rem;
            text-decoration: none;
            font-weight: 700;
            transition: transform 0.2s;
        }

        .title-bar .back-btn:hover {
            transform: translateX(-4px);
        }

        .title-bar h2 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            color: #333;
        }

        .info-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            padding: 28px;
            margin-bottom: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .info-card h3 {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 16px;
        }

        .info-row {
            display: flex;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .info-row .label {
            width: 160px;
            font-weight: 600;
            color: #555;
            flex-shrink: 0;
        }

        .info-row .value {
            color: #333;
        }

        .member-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .member-list li {
            padding: 4px 0;
        }

        .member-list li::before {
            content: "•";
            color: #5eb2ff;
            margin-right: 8px;
            font-weight: bold;
        }

        /* File cards */
        .file-card {
            background: #fff;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 18px 22px;
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: box-shadow 0.2s;
        }

        .file-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }

        .file-card .file-info h5 {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
            font-size: 1.05rem;
        }

        .file-card .file-info p {
            font-size: 0.85rem;
            color: #999;
            margin: 0;
        }

        .btn-view-file {
            background: #5eb2ff;
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 500;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: background 0.2s;
        }

        .btn-view-file:hover {
            background: #3a9eef;
            color: #fff;
        }

        /* File Viewer Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.6);
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
            padding: 20px;
            border-radius: 12px;
            width: 90%;
            max-width: 1000px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transform: translateY(-20px);
            transition: all 0.3s;
        }

        .modal-overlay.active .modal-box {
            transform: translateY(0);
        }

        footer {
            background: #b0b0b0;
            color: #fff;
            text-align: center;
            padding: 10px;
            font-size: 0.85rem;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 100;
        }

        body {
            padding-bottom: 50px;
        }
    </style>
</head>

<body>
    <header class="visit-header">
        <div class="d-flex align-items-center gap-3">
            <img src="../img/new_logo_svc_temp.png" alt="โลโก้" class="header-logo" onerror="this.src='https://placehold.co/70x70/5eb2ff/white?text=LOGO'">
            <div>
                <h1 class="h4 mb-0 fw-bold">ระบบบันทึกและตรวจสอบโครงการ</h1>
                <p class="mb-0">แผนกธุรกิจดิจิทัลและเทคโนโลยีสารสนเทศ</p>
            </div>
        </div>
    </header>

    <div class="detail-main">
        <div class="title-bar">
            <a href="index.php" class="back-btn">&lt;</a>
            <h2><?= htmlspecialchars($group['project_name']) ?></h2>
        </div>

        <!-- Group Info -->
        <div class="info-card">
            <h3><i data-lucide="clipboard-list" style="width:22px;height:22px; color:#5eb2ff; margin-right:6px;"></i> ข้อมูลโครงงาน</h3>

            <div class="info-row">
                <div class="label"><i data-lucide="file-text" style="width:14px;height:14px;"></i> ชื่อโครงงาน</div>
                <div class="value"><?= htmlspecialchars($group['project_name']) ?></div>
            </div>
            <div class="info-row">
                <div class="label"><i data-lucide="user-check" style="width:14px;height:14px;"></i> ครูที่ปรึกษา</div>
                <div class="value"><?= htmlspecialchars($group['teacher_name'] ?? '-') ?></div>
            </div>
            <div class="info-row">
                <div class="label"><i data-lucide="school" style="width:14px;height:14px;"></i> ห้องเรียน / สาขา</div>
                <div class="value"><?= htmlspecialchars($group['class_name'] ?? '-') ?> (<?= htmlspecialchars($group['branch_name'] ?? '-') ?>)</div>
            </div>
            <div class="info-row">
                <div class="label"><i data-lucide="users" style="width:14px;height:14px;"></i> สมาชิก</div>
                <div class="value">
                    <ul class="member-list">
                        <?php foreach ($members as $m): ?>
                            <li><?= htmlspecialchars($m) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Submitted Files -->
        <h4 class="mb-3"><i data-lucide="file-check" style="width:20px;height:20px; color:#28a745;"></i> ไฟล์งานที่ส่งผ่านแล้ว</h4>

        <?php foreach ($submissions as $sub): ?>
            <?php $ext = strtolower(pathinfo($sub['file_name'] ?? '', PATHINFO_EXTENSION)); ?>
            <div class="file-card">
                <div class="file-info">
                    <h5><i data-lucide="file-text" style="width:18px;height:18px; color:#5eb2ff; margin-right:4px;"></i> <?= htmlspecialchars($sub['assignment_name']) ?></h5>
                    <p>ส่งเมื่อ: <?= htmlspecialchars($sub['submit_date'] ?? '-') ?> | ไฟล์: <?= htmlspecialchars($sub['file_name'] ?? '-') ?></p>
                </div>
                <button type="button" class="btn-view-file" onclick="openFileViewer('../uploads/submissions/<?= htmlspecialchars($sub['file_name']) ?>', '<?= $ext ?>')">
                    <i data-lucide="eye" style="width:16px;height:16px;"></i> ดูไฟล์
                </button>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- File Viewer Modal -->
    <div class="modal-overlay" id="fileViewerModal">
        <div class="modal-box">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">ตัวอย่างไฟล์งาน</h4>
                <button type="button" class="btn-close" onclick="closeFileViewer()"></button>
            </div>
            <div id="fileViewerContent" style="background:#f8f9fa; border:1px solid #ddd; border-radius:8px; overflow:hidden;">
            </div>
            <div id="notSupportedMsg" style="display:none; text-align:center; padding: 40px 20px;">
                <i data-lucide="file-warning" style="width:48px;height:48px; color:#f0ad4e; margin-bottom:10px;"></i>
                <h5 class="text-secondary">ไม่สามารถเปิดดูไฟล์ประเภทนี้ในเบราว์เซอร์ได้</h5>
                <p class="text-muted">โปรดติดต่อเจ้าของโครงงานเพื่อขอรับไฟล์</p>
            </div>
        </div>
    </div>

    <footer>
        <div class="met-0 text-center ">
            <span>&copy; 2026 ระบบบันทึกและตรวจสอบโครงการ ITSVC67</span>
        </div>
    </footer>

    <script>
        lucide.createIcons();

        function openFileViewer(fileUrl, ext) {
            const viewerContent = document.getElementById('fileViewerContent');
            const modal = document.getElementById('fileViewerModal');
            const notSupportedMsg = document.getElementById('notSupportedMsg');

            viewerContent.innerHTML = '';
            notSupportedMsg.style.display = 'none';

            if (['pdf', 'jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                const iframe = document.createElement('iframe');
                iframe.src = fileUrl;
                iframe.style.width = '100%';
                iframe.style.height = '70vh';
                iframe.style.border = 'none';
                viewerContent.appendChild(iframe);
            } else {
                notSupportedMsg.style.display = 'block';
            }

            modal.classList.add('active');
        }

        function closeFileViewer() {
            document.getElementById('fileViewerModal').classList.remove('active');
            document.getElementById('fileViewerContent').innerHTML = '';
        }

        document.getElementById('fileViewerModal').addEventListener('click', function(e) {
            if (e.target === this) closeFileViewer();
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeFileViewer();
        });
    </script>
</body>

</html>