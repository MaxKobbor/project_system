<?php
session_start();
include_once __DIR__ . "/../../config/db.php";

// Redirect if not logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../../login.php");
    exit();
}

// ----------------------------------------------------------------------
// BRANCHES MANAGEMENT (จัดการสาขาวิชา)
// ----------------------------------------------------------------------

// เพิ่มสาขาใหม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_branch'])) {
    $name = trim($_POST['branch_name'] ?? '');
    if ($name !== '') {
        $stmt = $pdo->prepare("INSERT INTO `branches` (`branch_name`, `status`) VALUES (?, 0)");
        $stmt->execute([$name]);
        $_SESSION['msg'] = 'เพิ่มสาขาวิชา "' . $name . '" สำเร็จ';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: ../branches.php");
    exit();
}

// แก้ไขสาขา
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_branch'])) {
    $id = intval($_POST['branch_id'] ?? 0);
    $name = trim($_POST['branch_name'] ?? '');
    if ($name !== '' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE `branches` SET `branch_name` = ? WHERE `branch_id` = ?");
        $stmt->execute([$name, $id]);
        $_SESSION['msg'] = 'แก้ไขสาขาวิชาสำเร็จ';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: ../branches.php");
    exit();
}

// เปลี่ยนสถานะสาขา (Toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_branch_status'])) {
    $id = intval($_POST['branch_id'] ?? 0);
    $newStatus = intval($_POST['new_status'] ?? 0);
    $stmt = $pdo->prepare("UPDATE `branches` SET `status` = ? WHERE `branch_id` = ?");
    $stmt->execute([$newStatus, $id]);
    $statusText = $newStatus == 0 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
    $_SESSION['msg'] = $statusText . 'สาขาวิชาสำเร็จ';
    $_SESSION['msg_type'] = 'success';
    header("Location: ../branches.php");
    exit();
}

// ----------------------------------------------------------------------
// ACADEMIC YEARS MANAGEMENT (จัดการปีการศึกษา)
// ----------------------------------------------------------------------

// เพิ่มปีการศึกษา
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_year'])) {
    $year = trim($_POST['year'] ?? '');
    if ($year !== '') {
        $stmt = $pdo->prepare("INSERT INTO `academic_years` (`year_name`, `status`) VALUES (?, 1)");
        $stmt->execute([$year]);
        $_SESSION['msg'] = 'เพิ่มปีการศึกษา "' . $year . '" สำเร็จ';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: ../academic_years.php");
    exit();
}

// แก้ไขปีการศึกษา
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_year'])) {
    $id = intval($_POST['year_id'] ?? 0);
    $year = trim($_POST['year'] ?? '');
    if ($year !== '' && $id > 0) {
        $stmt = $pdo->prepare("UPDATE `academic_years` SET `year_name` = ? WHERE `year_id` = ?");
        $stmt->execute([$year, $id]);
        $_SESSION['msg'] = 'แก้ไขปีการศึกษาสำเร็จ';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: ../academic_years.php");
    exit();
}

// เปลี่ยนสถานะปีการศึกษา (Toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_year_status'])) {
    $id = intval($_POST['year_id'] ?? 0);
    $newStatus = intval($_POST['new_status'] ?? 0);
    $stmt = $pdo->prepare("UPDATE `academic_years` SET `status` = ? WHERE `year_id` = ?");
    $stmt->execute([$newStatus, $id]);
    $statusText = $newStatus == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
    $_SESSION['msg'] = $statusText . 'ปีการศึกษาสำเร็จ';
    $_SESSION['msg_type'] = 'success';
    header("Location: ../academic_years.php");
    exit();
}

// ----------------------------------------------------------------------
// CLASSROOMS MANAGEMENT (จัดการห้องเรียน)
// ----------------------------------------------------------------------

// เพิ่มห้องเรียน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_classroom'])) {
    $name = trim($_POST['class_name'] ?? '');
    $branchId = intval($_POST['branch_id'] ?? 0);
    $yearId = intval($_POST['year_id'] ?? 0);
    if ($name !== '' && $branchId > 0 && $yearId > 0) {
        $stmt = $pdo->prepare("INSERT INTO `classrooms` (`class_name`, `branch_id`, `year_id`) VALUES (?, ?, ?)");
        $stmt->execute([$name, $branchId, $yearId]);
        $_SESSION['msg'] = 'เพิ่มห้องเรียน "' . $name . '" สำเร็จ';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: ../classrooms.php");
    exit();
}

// แก้ไขห้องเรียน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_classroom'])) {
    $id = intval($_POST['class_id'] ?? 0);
    $name = trim($_POST['class_name'] ?? '');
    $branchId = intval($_POST['branch_id'] ?? 0);
    $yearId = intval($_POST['year_id'] ?? 0);
    if ($id > 0 && $name !== '' && $branchId > 0 && $yearId > 0) {
        $stmt = $pdo->prepare("UPDATE `classrooms` SET `class_name` = ?, `branch_id` = ?, `year_id` = ? WHERE `class_id` = ?");
        $stmt->execute([$name, $branchId, $yearId, $id]);
        $_SESSION['msg'] = 'แก้ไขห้องเรียนสำเร็จ';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: ../classrooms.php");
    exit();
}

// ----------------------------------------------------------------------
// TEACHERS MANAGEMENT (จัดการคุณครู)
// ----------------------------------------------------------------------

// เพิ่มครู
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $role = intval($_POST['role'] ?? 1);

    if ($username !== '' && $password !== '' && $fullName !== '') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        // 1. เพิ่มข้อมูลครูก่อนเพื่อเอา teacher_id
        $stmt = $pdo->prepare("INSERT INTO `teachers` (`username`, `password`, `full_name`, `image`, `role`, `status`) VALUES (?, ?, ?, '', ?, 1)");
        $stmt->execute([$username, $hashedPassword, $fullName, $role]);
        $teacherId = $pdo->lastInsertId();

        // 2. ถ้ามีการเลือกอัปโหลดรูปภาพ
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && $_FILES['image']['size'] > 0) {
            $uploadDir = __DIR__ . '/../../uploads/teachers/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
            
            // MIME check
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
            finfo_close($finfo);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (in_array($ext, $allowedExts) && in_array($mimeType, $allowedMimes) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
                $imageName = 'teacher_' . $teacherId . '.' . $ext;
                $targetFilePath = $uploadDir . $imageName;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
                    // อัปเดตชื่อรูปภาพในตาราง teachers
                    $stmtImg = $pdo->prepare("UPDATE `teachers` SET `image` = ? WHERE `teacher_id` = ?");
                    $stmtImg->execute([$imageName, $teacherId]);
                }
            }
        }

        $_SESSION['msg'] = 'เพิ่มคุณครู "' . $fullName . '" สำเร็จ';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: ../manage_teachers.php");
    exit();
}

// แก้ไขข้อมูลครู
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_teacher'])) {
    $id = intval($_POST['teacher_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');
    $role = intval($_POST['role'] ?? 1);
    $imageName = trim($_POST['existing_image'] ?? '');

    // เช็คกรณีมีการอัปโหลดรูปภาพใหม่
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK && $id > 0) {
        $uploadDir = __DIR__ . '/../../uploads/teachers/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

        // MIME check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $_FILES['image']['tmp_name']);
        finfo_close($finfo);
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

        if (in_array($ext, $allowedExts) && in_array($mimeType, $allowedMimes) && $_FILES['image']['size'] <= 5 * 1024 * 1024) {
            $newImageName = 'teacher_' . $id . '.' . $ext;

            // ลบไฟล์รูปเดิมหากมีและชื่อไฟล์ต่างกันหรือส่วนขยายต่างกัน
            if ($imageName !== '' && file_exists($uploadDir . $imageName)) {
                unlink($uploadDir . $imageName);
            }

            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $newImageName)) {
                $imageName = $newImageName;
            }
        }
    }

    if ($id > 0 && $username !== '' && $fullName !== '') {
        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE `teachers` SET `username` = ?, `password` = ?, `full_name` = ?, `image` = ?, `role` = ? WHERE `teacher_id` = ?");
            $stmt->execute([$username, $hashedPassword, $fullName, $imageName, $role, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE `teachers` SET `username` = ?, `full_name` = ?, `image` = ?, `role` = ? WHERE `teacher_id` = ?");
            $stmt->execute([$username, $fullName, $imageName, $role, $id]);
        }
        $_SESSION['msg'] = 'แก้ไขข้อมูลคุณครูสำเร็จ';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: ../manage_teachers.php");
    exit();
}

// เปลี่ยนสถานะครู (Toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_teacher_status'])) {
    $id = intval($_POST['teacher_id'] ?? 0);
    $newStatus = intval($_POST['new_status'] ?? 0);
    $stmt = $pdo->prepare("UPDATE `teachers` SET `status` = ? WHERE `teacher_id` = ?");
    $stmt->execute([$newStatus, $id]);
    $statusText = $newStatus == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
    $_SESSION['msg'] = $statusText . 'คุณครูสำเร็จ';
    $_SESSION['msg_type'] = 'success';
    header("Location: ../manage_teachers.php");
    exit();
}

// ----------------------------------------------------------------------
// ADMINS MANAGEMENT (จัดการแอดมิน)
// ----------------------------------------------------------------------

// เพิ่มแอดมิน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin'])) {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');

    if ($username !== '' && $password !== '' && $fullName !== '') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO `admins` (`username`, `password`, `full_name`, `status`) VALUES (?, ?, ?, 1)");
        $stmt->execute([$username, $hashedPassword, $fullName]);
        $_SESSION['msg'] = 'เพิ่มผู้ดูแลระบบ "' . $fullName . '" สำเร็จ';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: ../manage_admins.php");
    exit();
}

// แก้ไขแอดมิน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_admin'])) {
    $id = intval($_POST['admin_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $fullName = trim($_POST['full_name'] ?? '');

    if ($id > 0 && $username !== '' && $fullName !== '') {
        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE `admins` SET `username` = ?, `password` = ?, `full_name` = ? WHERE `admin_id` = ?");
            $stmt->execute([$username, $hashedPassword, $fullName, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE `admins` SET `username` = ?, `full_name` = ? WHERE `admin_id` = ?");
            $stmt->execute([$username, $fullName, $id]);
        }
        $_SESSION['msg'] = 'แก้ไขข้อมูลผู้ดูแลระบบสำเร็จ';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: ../manage_admins.php");
    exit();
}

// เปลี่ยนสถานะแอดมิน (Toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_admin_status'])) {
    $id = intval($_POST['admin_id'] ?? 0);
    $newStatus = intval($_POST['new_status'] ?? 0);
    $stmt = $pdo->prepare("UPDATE `admins` SET `status` = ? WHERE `admin_id` = ?");
    $stmt->execute([$newStatus, $id]);
    $statusText = $newStatus == 1 ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
    $_SESSION['msg'] = $statusText . 'ผู้ดูแลระบบสำเร็จ';
    $_SESSION['msg_type'] = 'success';
    header("Location: ../manage_admins.php");
    exit();
}

// ----------------------------------------------------------------------
// ASSIGNMENTS MANAGEMENT (จัดการหัวข้องาน)
// ----------------------------------------------------------------------

// เพิ่มหัวข้องาน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment'])) {
    $name = trim($_POST['assignment_name'] ?? '');
    $yearId = intval($_POST['year_id'] ?? 0);
    $startDate = $_POST['start_date'] ?? null;
    $deadlineDate = $_POST['deadline_date'] ?? '';
    $deadlineTime = $_POST['deadline_time'] ?? '';

    if ($name !== '' && $yearId > 0 && $deadlineDate !== '' && $deadlineTime !== '') {
        $stmt = $pdo->prepare("INSERT INTO `assignments` (`assignment_name`, `year_id`, `start_date`, `deadline_date`, `deadline_time`, `status`) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$name, $yearId, $startDate ?: null, $deadlineDate, $deadlineTime]);
        $_SESSION['msg'] = 'เพิ่มหัวข้องาน "' . $name . '" สำเร็จ';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: ../assignments.php");
    exit();
}

// แก้ไขหัวข้องาน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_assignment'])) {
    $id = intval($_POST['assignment_id'] ?? 0);
    $name = trim($_POST['assignment_name'] ?? '');
    $yearId = intval($_POST['year_id'] ?? 0);
    $startDate = $_POST['start_date'] ?? null;
    $deadlineDate = $_POST['deadline_date'] ?? '';
    $deadlineTime = $_POST['deadline_time'] ?? '';

    if ($id > 0 && $name !== '' && $yearId > 0 && $deadlineDate !== '' && $deadlineTime !== '') {
        $stmt = $pdo->prepare("UPDATE `assignments` SET `assignment_name` = ?, `year_id` = ?, `start_date` = ?, `deadline_date` = ?, `deadline_time` = ? WHERE `assignment_id` = ?");
        $stmt->execute([$name, $yearId, $startDate ?: null, $deadlineDate, $deadlineTime, $id]);
        $_SESSION['msg'] = 'แก้ไขหัวข้องานสำเร็จ';
        $_SESSION['msg_type'] = 'success';
    }
    header("Location: ../assignments.php");
    exit();
}

// เปลี่ยนสถานะหัวข้องาน (Toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_assignment_status'])) {
    $id = intval($_POST['assignment_id'] ?? 0);
    $newStatus = intval($_POST['new_status'] ?? 0);
    $stmt = $pdo->prepare("UPDATE `assignments` SET `status` = ? WHERE `assignment_id` = ?");
    $stmt->execute([$newStatus, $id]);
    $statusText = $newStatus == 1 ? 'เปิดรับส่งงาน' : 'ปิดรับส่งงาน';
    $_SESSION['msg'] = $statusText . 'สำเร็จ';
    $_SESSION['msg_type'] = 'success';
    header("Location: ../assignments.php");
    exit();
}

// ----------------------------------------------------------------------
// GROUPS MANAGEMENT (จัดการกลุ่มนักเรียน)
// ----------------------------------------------------------------------

// แก้ไขกลุ่มนักเรียน
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_group') {
    $id = intval($_POST['group_id'] ?? 0);
    $projectName = trim($_POST['project_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $teacherId = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
    $classId = !empty($_POST['class_id']) ? intval($_POST['class_id']) : null;
    $status = isset($_POST['status']) ? 1 : 0;
    
    $std1 = trim($_POST['std_1'] ?? '');
    $std2 = trim($_POST['std_2'] ?? '');
    $std3 = trim($_POST['std_3'] ?? '');
    $std4 = trim($_POST['std_4'] ?? '');

    if ($id > 0 && $projectName !== '' && $username !== '') {
        if ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE `groups` SET `project_name` = ?, `username` = ?, `password` = ?, `teacher_id` = ?, `class_id` = ?, `status` = ?, `std_1` = ?, `std_2` = ?, `std_3` = ?, `std_4` = ? WHERE `group_id` = ?");
            $stmt->execute([$projectName, $username, $hashedPassword, $teacherId, $classId, $status, $std1 ?: null, $std2 ?: null, $std3 ?: null, $std4 ?: null, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE `groups` SET `project_name` = ?, `username` = ?, `teacher_id` = ?, `class_id` = ?, `status` = ?, `std_1` = ?, `std_2` = ?, `std_3` = ?, `std_4` = ? WHERE `group_id` = ?");
            $stmt->execute([$projectName, $username, $teacherId, $classId, $status, $std1 ?: null, $std2 ?: null, $std3 ?: null, $std4 ?: null, $id]);
        }

        $_SESSION['msg'] = 'แก้ไขกลุ่มนักเรียนและรายละเอียดโครงงานสำเร็จ';
        $_SESSION['msg_type'] = 'success';
    } else {
        $_SESSION['msg'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        $_SESSION['msg_type'] = 'danger';
    }
    header("Location: ../groups_view.php");
    exit();
}
