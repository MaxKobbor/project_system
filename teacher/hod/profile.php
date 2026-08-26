<?php
require_once '../../config/db.php';
require_once '../config_t/auth_check.php';

// Verify HOD access
check_teacher_auth(0);

$teacher_id = $_SESSION['teacher_id'];

// Get teacher data
$stmt = $pdo->prepare("SELECT * FROM `teachers` WHERE teacher_id = ?");
$stmt->execute([$teacher_id]);
$teacher = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'];

    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE `teachers` SET full_name = ?, password = ? WHERE teacher_id = ?");
        if ($updateStmt->execute([$full_name, $hashed_password, $teacher_id])) {
            $success = "บันทึกข้อมูลเรียบร้อยแล้ว";
            $stmt->execute([$teacher_id]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['full_name'] = $full_name;
        } else {
            $error = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    } else {
        $updateStmt = $pdo->prepare("UPDATE `teachers` SET full_name = ? WHERE teacher_id = ?");
        if ($updateStmt->execute([$full_name, $teacher_id])) {
            $success = "บันทึกข้อมูลเรียบร้อยแล้ว";
            $stmt->execute([$teacher_id]);
            $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['full_name'] = $full_name;
        } else {
            $error = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลส่วนตัว - หัวหน้าแผนก</title>
    <link rel="icon" type="image/png" href="../../img/new_logo_svc_temp.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="header.css?v=<?= time() ?>">
    <link rel="stylesheet" href="nav.css?v=<?= time() ?>">
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
            background: #61BFED;
            color: #fff;
            font-weight: 600;
            padding: 10px 24px;
            border-radius: 8px;
            border: none;
            transition: all 0.2s;
        }

        .btn-save:hover {
            background: #4dafe0;
        }
    </style>
</head>

<body>
    <?php include 'includes/header.php'; ?>

    <div class="app-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="main-content">
            <div class="d-flex align-items-center gap-3 mb-4">
                <a href="index.php" class="btn btn-light rounded-circle p-2"><i data-lucide="chevron-left"></i></a>
                <h2 class="mb-0">ข้อมูลส่วนตัว (หัวหน้าแผนก)</h2>
            </div>

            <div class="profile-card">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success d-flex align-items-center"><i data-lucide="check-circle" class="me-2"></i> <?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center"><i data-lucide="alert-circle" class="me-2"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">ชื่อผู้ใช้งาน (Username) <small class="text-muted fw-normal">- ไม่สามารถแก้ไขได้</small></label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($teacher['username']) ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">เปลี่ยนรหัสผ่าน (Password)</label>
                        <input type="password" class="form-control" name="password" placeholder="ปล่อยว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน">
                    </div>

                    <div class="mb-4">
                        <label class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($teacher['full_name']) ?>" required>
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