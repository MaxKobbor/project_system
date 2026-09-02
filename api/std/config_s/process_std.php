<?php
session_start();
require_once '../../config/db.php';

// Check if student is logged in
if (!isset($_SESSION['group_id'])) {
    header("Location: ../../login.php");
    exit();
}

$group_id = $_SESSION['group_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Upload Submission
    if ($action === 'upload_submission') {
        $assignment_id = intval($_POST['assignment_id'] ?? 0);

        // Verify group status (must be approved HOD = 1)
        $stmtVerify = $pdo->prepare("SELECT status FROM `groups` WHERE group_id = ?");
        $stmtVerify->execute([$group_id]);
        $groupStatus = $stmtVerify->fetchColumn();

        if ($groupStatus != 1) {
            $_SESSION['msg'] = 'ไม่สามารถส่งงานได้ โครงงานของคุณยังไม่ได้รับการอนุมัติ';
            $_SESSION['msg_type'] = 'error';
            header("Location: ../assignments.php");
            exit();
        }

        // Verify assignment exists and is open
        $stmtAssign = $pdo->prepare("SELECT status FROM `assignments` WHERE assignment_id = ?");
        $stmtAssign->execute([$assignment_id]);
        $assignStatus = $stmtAssign->fetchColumn();

        if ($assignStatus === false || $assignStatus == 0) {
            $_SESSION['msg'] = 'ไม่สามารถส่งงานได้ เนื่องจากระบบปิดรับส่งงานนี้แล้ว';
            $_SESSION['msg_type'] = 'error';
            header("Location: ../assignments.php");
            exit();
        }

        // File upload logic
        if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../../uploads/submissions/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileTmpPath = $_FILES['submission_file']['tmp_name'];
            $originalFileName = $_FILES['submission_file']['name'];
            $fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['pdf'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                $_SESSION['msg'] = 'อนุญาตเฉพาะไฟล์ .pdf เท่านั้น';
                $_SESSION['msg_type'] = 'error';
                header("Location: ../assignments.php");
                exit();
            }

            // Verify MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fileTmpPath);
            finfo_close($finfo);

            if ($mimeType !== 'application/pdf') {
                $_SESSION['msg'] = 'ไฟล์ที่อัปโหลดไม่ใช่ไฟล์ PDF ที่ถูกต้อง';
                $_SESSION['msg_type'] = 'error';
                header("Location: ../assignments.php");
                exit();
            }

            // Check file size (Max 25MB)
            if ($_FILES['submission_file']['size'] > 25 * 1024 * 1024) {
                $_SESSION['msg'] = 'ขนาดไฟล์ต้องไม่เกิน 25MB';
                $_SESSION['msg_type'] = 'error';
                header("Location: ../assignments.php");
                exit();
            }

            // Format: {assignment_id}__{group_id}.{ext}
            // ดึงข้อมูลผู้ใช้ (username) ของกลุ่ม
            $stmtUser = $pdo->prepare("SELECT username FROM `groups` WHERE group_id = ?");
            $stmtUser->execute([$group_id]);
            $username = $stmtUser->fetchColumn();
            if (!$username) {
                $username = 'unknown';
            }
            // ตั้งชื่อไฟล์ใหม่แบบ {username}_{assignment_id}.{ext}
            $newFileName = $username . '_' . $assignment_id . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;


            if (move_uploaded_file($fileTmpPath, $destPath)) {
                // Check if submission already exists for this group & assignment
                $stmtCheck = $pdo->prepare("SELECT submission_id FROM `submissions` WHERE group_id = ? AND assignment_id = ?");
                $stmtCheck->execute([$group_id, $assignment_id]);
                $existingSub = $stmtCheck->fetchColumn();

                $now = date('Y-m-d H:i:s');

                if ($existingSub) {
                    // Update existing submission
                    $stmtUpdate = $pdo->prepare("UPDATE `submissions` 
                                                 SET file_name = ?, submit_date = ?, status_1 = 0, teacher_comment = NULL 
                                                 WHERE submission_id = ?");
                    $stmtUpdate->execute([$newFileName, $now, $existingSub]);
                } else {
                    // Insert new submission
                    $stmtInsert = $pdo->prepare("INSERT INTO `submissions` (group_id, assignment_id, file_name, submit_date, status_1) 
                                                 VALUES (?, ?, ?, ?, 0)");
                    $stmtInsert->execute([$group_id, $assignment_id, $newFileName, $now]);
                }
                // Add notification for teacher
                $stmtGroup = $pdo->prepare("SELECT project_name, teacher_id FROM `groups` WHERE group_id = ?");
                $stmtGroup->execute([$group_id]);
                $groupData = $stmtGroup->fetch(PDO::FETCH_ASSOC);
                if ($groupData && $groupData['teacher_id']) {
                    $teacher_id = $groupData['teacher_id'];
                    $title = "ส่งงานใหม่";
                    $msg = "โครงงาน " . $groupData['project_name'] . " ได้ส่งไฟล์งานใหม่ โปรดตรวจสอบ";

                    $stmtNoti = $pdo->prepare("INSERT INTO `notifications` (type, teacher_id, group_id, title, message, is_read, created_at) VALUES ('teacher', ?, ?, ?, ?, 0, ?)");
                    $stmtNoti->execute([$teacher_id, $group_id, $title, $msg, $now]);
                }

                $_SESSION['msg'] = 'อัปโหลดไฟล์ส่งงานเรียบร้อยแล้ว สถานะรอตรวจ';
                $_SESSION['msg_type'] = 'success';
            } else {
                $_SESSION['msg'] = 'เกิดข้อผิดพลาดในการบันทึกไฟล์ โปรดลองอีกครั้ง';
                $_SESSION['msg_type'] = 'error';
            }
        } else {
            $_SESSION['msg'] = 'กรุณาเลือกไฟล์ที่ต้องการส่ง';
            $_SESSION['msg_type'] = 'error';
        }

        header("Location: ../assignments.php");
        exit();
    }
}
