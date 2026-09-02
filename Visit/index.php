<?php
require_once '../config/db.php';

// Search
$search = trim($_GET['search'] ?? '');

// Find groups where ALL assignments for their year are submitted AND passed (status_1 = 1)
// Logic: total assignments for the year == total passed submissions for that group
$query = "
    SELECT g.group_id, g.project_name, g.username, g.std_1, g.std_2, g.std_3, g.std_4,
           t.full_name AS teacher_name, c.class_name, b.branch_name, c.year_id,
           (SELECT COUNT(*) FROM assignments a WHERE a.year_id = c.year_id) AS total_assignments,
           (SELECT COUNT(*) FROM submissions s 
            JOIN assignments a ON s.assignment_id = a.assignment_id 
            WHERE s.group_id = g.group_id AND a.year_id = c.year_id AND s.status_1 = 1) AS passed_assignments
    FROM `groups` g
    LEFT JOIN `teachers` t ON g.teacher_id = t.teacher_id
    LEFT JOIN `classrooms` c ON g.class_id = c.class_id
    LEFT JOIN `branches` b ON c.branch_id = b.branch_id
    WHERE g.status = 1
    HAVING total_assignments > 0 AND total_assignments = passed_assignments
";

$params = [];

if ($search !== '') {
    $query = "
        SELECT g.group_id, g.project_name, g.username, g.std_1, g.std_2, g.std_3, g.std_4,
               t.full_name AS teacher_name, c.class_name, b.branch_name, c.year_id,
               (SELECT COUNT(*) FROM assignments a WHERE a.year_id = c.year_id) AS total_assignments,
               (SELECT COUNT(*) FROM submissions s 
                JOIN assignments a ON s.assignment_id = a.assignment_id 
                WHERE s.group_id = g.group_id AND a.year_id = c.year_id AND s.status_1 = 1) AS passed_assignments
        FROM `groups` g
        LEFT JOIN `teachers` t ON g.teacher_id = t.teacher_id
        LEFT JOIN `classrooms` c ON g.class_id = c.class_id
        LEFT JOIN `branches` b ON c.branch_id = b.branch_id
        WHERE g.status = 1
          AND (g.project_name LIKE ? OR g.std_1 LIKE ? OR g.std_2 LIKE ? OR g.std_3 LIKE ? OR g.username LIKE ?)
        HAVING total_assignments > 0 AND total_assignments = passed_assignments
    ";
    $params = ["%$search%", "%$search%", "%$search%", "%$search%", "%$search%"];
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <link rel="icon" type="image/png" href="../img/new_logo_svc_temp.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เยี่ยมชมโครงงาน - ระบบบันทึกและตรวจสอบโครงการ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css.css">
    <link rel="stylesheet" href="../home.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background-color: #f0f2f5;
            min-height: 100vh;
            margin: 0;
        }

        /* Header */
        .visit-header {
            background: linear-gradient(180deg, #5eb2ff 0%, #c4e4ff 100%);
            padding: 15px 25px;
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
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

        .search-box {
            display: flex;
            align-items: center;
            background: #fff;
            border-radius: 30px;
            padding: 6px 16px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            min-width: 280px;
        }

        .search-box input {
            border: none;
            outline: none;
            flex: 1;
            font-size: 0.95rem;
            font-family: 'Sarabun', sans-serif;
            background: transparent;
        }

        .search-box button {
            background: #5eb2ff;
            border: none;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s;
        }

        .search-box button:hover {
            background: #3a9eef;
        }

        /* Main content */
        .visit-main {
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            padding: 30px 20px;
            box-sizing: border-box;
        }

        /* Title bar */
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
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
        }

        /* Project cards */
        .project-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e0e0e0;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: box-shadow 0.2s, transform 0.15s;
        }

        .project-card:hover {
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        .project-card h4 {
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .project-meta {
            font-size: 0.9rem;
            color: #777;
            margin-bottom: 4px;
        }

        .project-meta strong {
            color: #555;
        }

        .btn-detail {
            background: #5eb2ff;
            color: #fff;
            border: none;
            padding: 8px 24px;
            border-radius: 20px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            float: right;
            transition: background 0.2s;
        }

        .btn-detail:hover {
            background: #3a9eef;
            color: #fff;
        }

        /* Footer */
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

        /* Add padding to body so content doesn't get hidden behind fixed footer */
        body {
            padding-bottom: 50px;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <header class="visit-header">
        <div class="d-flex align-items-center gap-3">
            <img src="../img/new_logo_svc_temp.png" alt="โลโก้" class="header-logo" onerror="this.src='https://placehold.co/70x70/5eb2ff/white?text=LOGO'">
            <div>
                <h1 class="h4 mb-0 fw-bold">ระบบบันทึกและตรวจสอบโครงการ</h1>
                <p class="mb-0">แผนกธุรกิจดิจิทัลและเทคโนโลยีสารสนเทศ</p>
            </div>
        </div>
        <form method="GET" action="index.php" class="search-box">
            <input type="text" name="search" placeholder="กรอกคำค้นหา" value="<?= htmlspecialchars($search) ?>">
            <button type="submit"><i data-lucide="search" style="width:18px;height:18px;"></i></button>
        </form>
    </header>

    <!-- Main -->
    <div class="visit-main">
        <div class="title-bar">
            <a href="../login.php" class="back-btn">&lt;</a>
            <h2>ตัวอย่างโครงงาน</h2>
        </div>

        <?php if ($search !== ''): ?>
            <p class="text-muted mb-3">ผลการค้นหา "<?= htmlspecialchars($search) ?>" — พบ <?= count($projects) ?> โครงงาน</p>
        <?php endif; ?>

        <?php if (empty($projects)): ?>
            <div class="text-center text-muted my-5 py-5">
                <i data-lucide="folder-open" style="width:56px;height:56px; opacity:0.4; margin-bottom:12px;"></i>
                <h4>ยังไม่มีโครงงานที่เสร็จสมบูรณ์ 100%</h4>
                <p>โครงงานจะแสดงที่นี่เมื่อนักเรียนส่งงานครบทุกหัวข้อและผ่านการตรวจจากครูแล้ว</p>
            </div>
        <?php else: ?>
            <?php foreach ($projects as $p): ?>
                <div class="project-card">
                    <h4><i data-lucide="book-open" style="width:20px;height:20px; color:#5eb2ff; margin-right:6px;"></i> <?= htmlspecialchars($p['project_name']) ?></h4>

                    <div class="project-meta">
                        <i data-lucide="user-check" style="width:14px;height:14px; display:inline;"></i>
                        <strong>ครูที่ปรึกษา:</strong> <?= htmlspecialchars($p['teacher_name'] ?? '-') ?>
                    </div>
                    <div class="project-meta">
                        <i data-lucide="users" style="width:14px;height:14px; display:inline;"></i>
                        <strong>สมาชิก:</strong>
                        <?php
                        $members = array_filter([$p['std_1'], $p['std_2'], $p['std_3'], $p['std_4']]);
                        echo htmlspecialchars(implode(', ', $members));
                        ?>
                    </div>
                    <div class="project-meta">
                        <i data-lucide="school" style="width:14px;height:14px; display:inline;"></i>
                        <strong>ห้อง:</strong> <?= htmlspecialchars($p['class_name'] ?? '-') ?> (<?= htmlspecialchars($p['branch_name'] ?? '-') ?>)
                    </div>
                    <a href="detail.php?group_id=<?= $p['group_id'] ?>" class="btn-detail">
                        รายละเอียดเพิ่มเติม <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                    </a>
                    <div style="clear:both;"></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer>
        <div class="met-0 text-center ">
            <span>&copy; 2026 ระบบบันทึกและตรวจสอบโครงการ ITSVC67</span>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>