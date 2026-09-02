<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/auth_check.php';

// Verify HOD access
check_teacher_auth(0);

// ----------------------------------------------------------------------
// TEACHER MANAGEMENT BY HOD
// ----------------------------------------------------------------------

// 1. เพิ่มคุณครูใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $role = intval($_POST['role'] ?? 1);

    if ($username !== '' && $password !== '' && $fullName !== '') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        // เพิ่มข้อมูลครูก่อนเพื่อรับ teacher_id
        $stmt = $pdo->prepare("INSERT INTO `teachers` (`username`, `password`, `full_name`, `image`, `role`, `status`) VALUES (?, ?, ?, '', ?, 1)");
        $stmt->execute([$username, $hashedPassword, $fullName, $role]);
        $teacherId = $pdo->lastInsertId();

        // จัดการอัปโหลดรูปโปรไฟล์
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && $_FILES['image']['size'] > 0) {
            $uploadDir = __DIR__ . '/../../uploads/teachers/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (in_array($ext, $allowedExts) && in_array($mimeType, $allowedMimes) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
                $imageName = 'teacher_' . $teacherId . '.' . $ext;
                $targetFilePath = $uploadDir . $imageName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
                    $stmtImg = $pdo->prepare("UPDATE `teachers` SET `image` = ? WHERE `teacher_id` = ?");
                    $stmtImg->execute([$imageName, $teacherId]);
                }
            }
        }

        $_SESSION['msg'] = 'เพิ่มคุณครู "' . $fullName . '" สำเร็จ';
        $_SESSION['msg_type'] = 'success';
    } else {
        $_SESSION['msg'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $_SESSION['msg_type'] = 'error';
    }

    header("Location: ../hod/manage_teachers.php");
    exit();
}

// 2. ปิด/เปิด การใช้งานบัญชีครู (Toggle status)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_teacher_status'])) {
    $id = intval($_POST['teacher_id'] ?? 0);
    $newStatus = intval($_POST['new_status'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE `teachers` SET `status` = ? WHERE `teacher_id` = ?");
        $stmt->execute([$newStatus, $id]);

        $statusText = $newStatus == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
        $_SESSION['msg'] = $statusText . 'บัญชีคุณครูเรียบร้อย';
        $_SESSION['msg_type'] = 'success';
    }

    header("Location: ../hod/manage_teachers.php");
    exit();
}

// 3. แก้ไขข้อมูลครู
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_teacher'])) {
    $id = intval($_POST['teacher_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $role = intval($_POST['role'] ?? 1);
    $existingImage = trim($_POST['existing_image'] ?? '');

    if ($id > 0 && $username !== '' && $fullName !== '') {
        $imageName = $existingImage;

        // อัปโหลดรูปภาพใหม่ถ้ามีการเลือกไฟล์
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && $_FILES['image']['size'] > 0) {
            $uploadDir = __DIR__ . '/../../uploads/teachers/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (in_array($ext, $allowedExts) && in_array($mimeType, $allowedMimes) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
                $newImageName = 'teacher_' . $id . '.' . $ext;

                if ($existingImage !== '' && file_exists($uploadDir . $existingImage)) {
                    unlink($uploadDir . $existingImage);
                }

                if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newImageName)) {
                    $imageName = $newImageName;
                }
            }
        }

        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE `teachers` SET `username` = ?, `password` = ?, `full_name` = ?, `image` = ?, `role` = ? WHERE `teacher_id` = ?");
            $stmt->execute([$username, $hashedPassword, $fullName, $imageName, $role, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE `teachers` SET `username` = ?, `full_name` = ?, `image` = ?, `role` = ? WHERE `teacher_id` = ?");
            $stmt->execute([$username, $fullName, $imageName, $role, $id]);
        }

        $_SESSION['msg'] = 'แก้ไขข้อมูลคุณครูเรียบร้อย';
        $_SESSION['msg_type'] = 'success';
    }

    header("Location: ../hod/manage_teachers.php");
    exit();
}

// ----------------------------------------------------------------------
// PROJECT APPROVALS BY HOD (อนุมัติโครงงาน)
// ----------------------------------------------------------------------

// 4. อนุมัติ / ปรับสถานะโครงงาน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_project_status'])) {
    $groupId = intval($_POST['group_id'] ?? 0);
    $newStatus = intval($_POST['new_status'] ?? 0);

    if ($groupId > 0) {
        $stmt = $pdo->prepare("UPDATE `groups` SET `status` = ? WHERE `group_id` = ?");
        $stmt->execute([$newStatus, $groupId]);

        $statusText = $newStatus == 1 ? 'อนุมัติโครงงานเรียบร้อย' : 'ปรับสถานะเป็นรออนุมัติเรียบร้อย';
        $_SESSION['msg'] = $statusText;
        $_SESSION['msg_type'] = 'success';
    }

    header("Location: ../hod/project_approvals.php");
    exit();
}
?>
