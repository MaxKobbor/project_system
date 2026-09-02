<?php
session_start();
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/auth_check.php';

// Verify TC access (role = 1)
check_teacher_auth(1);

$teacher_id = $_SESSION['teacher_id'];

// ----------------------------------------------------------------------
// GROUP MANAGEMENT BY TC
// ----------------------------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. สร้างกลุ่มนักเรียนใหม่
    if ($action === 'add_group') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $project_name = trim($_POST['project_name'] ?? '');
        $class_id = intval($_POST['class_id'] ?? 0);
        $std_1 = trim($_POST['std_1'] ?? '');
        $std_2 = trim($_POST['std_2'] ?? '');
        $std_3 = trim($_POST['std_3'] ?? '');

        if ($username !== '' && $password !== '' && $class_id > 0 && $std_1 !== '') {
            // Check if username already exists
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE `username` = ?");
            $stmtCheck->execute([$username]);
            if ($stmtCheck->fetchColumn() > 0) {
                $_SESSION['msg'] = 'ชื่อผู้ใช้นี้ (Username) ถูกใช้งานแล้ว กรุณาใช้ชื่ออื่น';
                $_SESSION['msg_type'] = 'error';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO `groups` (`username`, `password`, `project_name`, `teacher_id`, `class_id`, `std_1`, `std_2`, `std_3`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
                $stmt->execute([$username, $hashedPassword, $project_name, $teacher_id, $class_id, $std_1, $std_2, $std_3]);
                
                $_SESSION['msg'] = 'สร้างกลุ่มนักเรียนเรียบร้อย (รอหัวหน้าแผนกอนุมัติโครงงาน)';
                $_SESSION['msg_type'] = 'success';
            }
        } else {
            $_SESSION['msg'] = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
            $_SESSION['msg_type'] = 'error';
        }
        header("Location: ../tc/manage_groups.php");
        exit();
    }

    // 2. แก้ไขกลุ่มนักเรียน
    if ($action === 'edit_group') {
        $group_id = intval($_POST['group_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $project_name = trim($_POST['project_name'] ?? '');
        $class_id = intval($_POST['class_id'] ?? 0);
        $std_1 = trim($_POST['std_1'] ?? '');
        $std_2 = trim($_POST['std_2'] ?? '');
        $std_3 = trim($_POST['std_3'] ?? '');
        
        // Verify ownership
        $stmtVerify = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE `group_id` = ? AND `teacher_id` = ?");
        $stmtVerify->execute([$group_id, $teacher_id]);
        
        if ($stmtVerify->fetchColumn() > 0) {
            if ($username !== '' && $class_id > 0 && $std_1 !== '') {
                // Check if username already exists for OTHER groups
                $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `groups` WHERE `username` = ? AND `group_id` != ?");
                $stmtCheck->execute([$username, $group_id]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $_SESSION['msg'] = 'ชื่อผู้ใช้นี้ (Username) ถูกใช้งานโดยกลุ่มอื่นแล้ว กรุณาใช้ชื่ออื่น';
                    $_SESSION['msg_type'] = 'error';
                } else {
                    if ($password !== '') {
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE `groups` SET `username` = ?, `password` = ?, `project_name` = ?, `class_id` = ?, `std_1` = ?, `std_2` = ?, `std_3` = ? WHERE `group_id` = ?");
                        $stmt->execute([$username, $hashedPassword, $project_name, $class_id, $std_1, $std_2, $std_3, $group_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE `groups` SET `username` = ?, `project_name` = ?, `class_id` = ?, `std_1` = ?, `std_2` = ?, `std_3` = ? WHERE `group_id` = ?");
                        $stmt->execute([$username, $project_name, $class_id, $std_1, $std_2, $std_3, $group_id]);
                    }
                    
                    $_SESSION['msg'] = 'บันทึกการแก้ไขกลุ่มเรียบร้อย';
                    $_SESSION['msg_type'] = 'success';
                }
            } else {
                $_SESSION['msg'] = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน';
                $_SESSION['msg_type'] = 'error';
            }
        } else {
            $_SESSION['msg'] = 'คุณไม่มีสิทธิ์แก้ไขกลุ่มนี้';
            $_SESSION['msg_type'] = 'error';
        }
        header("Location: ../tc/manage_groups.php");
        exit();
    }

    // 3. ตรวจงาน / ตีกลับงาน (Inspect Submissions)
    if ($action === 'inspect_submission') {
        $submission_id = intval($_POST['submission_id'] ?? 0);
        $status_1 = intval($_POST['status_1'] ?? 0); // 1 = ผ่าน, 2 = ตีกลับ
        $teacher_comment = trim($_POST['teacher_comment'] ?? '');

        // Verify ownership (the submission must belong to a group this teacher advises)
        $stmtVerify = $pdo->prepare("SELECT s.submission_id FROM `submissions` s JOIN `groups` g ON s.group_id = g.group_id WHERE s.submission_id = ? AND g.teacher_id = ?");
        $stmtVerify->execute([$submission_id, $teacher_id]);

        if ($stmtVerify->fetchColumn() > 0) {
            if ($status_1 === 1 || $status_1 === 2) {
                // If returning (2), a comment should ideally be provided, but we just save whatever is there.
                $stmt = $pdo->prepare("UPDATE `submissions` SET `status_1` = ?, `teacher_comment` = ? WHERE `submission_id` = ?");
                $stmt->execute([$status_1, $teacher_comment, $submission_id]);

                // Fetch group_id and assignment_name for notification
                $stmtInfo = $pdo->prepare("SELECT s.group_id, a.assignment_name FROM `submissions` s JOIN `assignments` a ON s.assignment_id = a.assignment_id WHERE s.submission_id = ?");
                $stmtInfo->execute([$submission_id]);
                $info = $stmtInfo->fetch(PDO::FETCH_ASSOC);
                
                if ($info) {
                    $groupIdNoti = $info['group_id'];
                    $assignName = $info['assignment_name'];
                    $now = date('Y-m-d H:i:s');
                    
                    if ($status_1 === 1) {
                        $title = "งานผ่านแล้ว";
                        $msg = "งาน " . $assignName . " ของคุณได้รับการอนุมัติผ่านแล้ว";
                        $_SESSION['msg'] = 'อนุมัติผ่านงานเรียบร้อย';
                    } else {
                        $title = "งานถูกตีกลับ";
                        $msg = "งาน " . $assignName . " ถูกตีกลับ: " . $teacher_comment;
                        $_SESSION['msg'] = 'ส่งตีกลับงานพร้อมข้อเสนอแนะเรียบร้อย';
                    }
                    
                    $stmtNoti = $pdo->prepare("INSERT INTO `notifications` (type, group_id, title, message, is_read, created_at) VALUES ('student', ?, ?, ?, 0, ?)");
                    $stmtNoti->execute([$groupIdNoti, $title, $msg, $now]);
                }
                
                $_SESSION['msg_type'] = 'success';
            } else {
                $_SESSION['msg'] = 'สถานะการตรวจไม่ถูกต้อง';
                $_SESSION['msg_type'] = 'error';
            }
        } else {
            $_SESSION['msg'] = 'คุณไม่มีสิทธิ์ตรวจงานนี้';
            $_SESSION['msg_type'] = 'error';
        }
        header("Location: ../tc/inspect_submissions.php");
        exit();
    }
}
?>
