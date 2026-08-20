<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['group_id'])) {
    header("Location: ../login.php");
    exit();
}

$group_id = $_SESSION['group_id'];

// Get group data
$stmt = $pdo->prepare("SELECT * FROM `groups` WHERE group_id = ?");
$stmt->execute([$group_id]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $project_name = trim($_POST['project_name']);

    if ($group['status'] == 1) {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE `groups` SET password = ? WHERE group_id = ?");
            if ($updateStmt->execute([$hashedPassword, $group_id])) {
                $success = "อัปเดตรหัสผ่านเรียบร้อยแล้ว";
                $stmt->execute([$group_id]);
                $group = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
            }
        } else {
            $error = "กรุณากรอกรหัสผ่าน";
        }
    } else {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE `groups` SET project_name = ?, password = ? WHERE group_id = ?");
            $params = [$project_name, $hashedPassword, $group_id];
        } else {
            $updateStmt = $pdo->prepare("UPDATE `groups` SET project_name = ? WHERE group_id = ?");
            $params = [$project_name, $group_id];
        }

        if ($updateStmt->execute($params)) {
            $_SESSION['project_name'] = $project_name;
            $success = "บันทึกข้อมูลเรียบร้อยแล้ว";
            $stmt->execute([$group_id]);
            $group = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $error = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <link rel="icon" type="image/png" href="../img/new_logo_svc_temp.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลกลุ่ม / แก้ไข - ระบบนักเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="header.css">
    <link rel="stylesheet" href="nav.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .profile-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }

        .form-label {
            font-weight: 600;
            color: #333;
        }

        .btn-save {
            background: #5eb2ff;
            color: #fff;
            font-weight: 600;
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            transition: all 0.2s;
        }

        .btn-save:hover {
            background: #4a9eeb;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="profile-card">
                <h4 class="fw-bold mb-4"><i data-lucide="user" style="width:24px;height:24px; color:#5eb2ff; margin-right:8px;"></i> ข้อมูลกลุ่มโครงงาน</h4>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success d-flex align-items-center"><i data-lucide="check-circle" class="me-2"></i> <?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center"><i data-lucide="alert-circle" class="me-2"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <?php if ($group['status'] == 1): ?>
                    <div class="alert alert-warning d-flex align-items-center">
                        <i data-lucide="info" class="me-2"></i> โครงงานของคุณได้รับการอนุมัติแล้ว สามารถแก้ไขได้เฉพาะรหัสผ่านเท่านั้น หากต้องการแก้ไขข้อมูลอื่นกรุณาติดต่อครูที่ปรึกษา
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">ชื่อผู้ใช้งาน (Username) <small class="text-muted fw-normal">- ไม่สามารถแก้ไขได้</small></label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($group['username']) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">รหัสผ่าน (Password) </label>
                        <input type="text" class="form-control" name="password" placeholder="ปล่อยว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">ชื่อโครงงาน</label>
                        <input type="text" class="form-control <?= $group['status'] == 1 ? 'bg-light' : '' ?>" name="project_name" value="<?= htmlspecialchars($group['project_name']) ?>" <?= $group['status'] == 1 ? 'readonly' : 'required' ?>>
                    </div>

                    <h5 class="fw-bold mb-3 border-bottom pb-2">สมาชิกในกลุ่ม</h5>

                    <div class="mb-3">
                        <label class="form-label">สมาชิกคนที่ 1</label>
                        <input type="text" class="form-control bg-light" name="std_1" value="<?= htmlspecialchars($group['std_1']) ?>" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">สมาชิกคนที่ 2</label>
                        <input type="text" class="form-control bg-light" name="std_2" value="<?= htmlspecialchars($group['std_2']) ?>" readonly>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">สมาชิกคนที่ 3</label>
                        <input type="text" class="form-control bg-light" name="std_3" value="<?= htmlspecialchars($group['std_3']) ?>" readonly>
                    </div>

                    <div class="text-end mt-4">
                        <button type="submit" class="btn-save"><i data-lucide="save" class="me-1" style="width:18px;height:18px;"></i> บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        lucide.createIcons();
    </script>
    <script src="nav.js"></script>
</body>

</html>