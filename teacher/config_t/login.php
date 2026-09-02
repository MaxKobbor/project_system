<?php
session_start();
include "../../config/db.php";
// teacher login
if (isset($_POST['login-teacher'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT teacher_id, full_name, username, password, status, role FROM teachers WHERE username = ?");
        $stmt->execute([$username]);
        $rs = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rs && password_verify($password, $rs['password'])) {
            if ($rs['status'] != 1) {
                $_SESSION['error'] = "บัญชีนี้ถูกปิดการใช้งาน กรุณาติดต่อหัวหน้าแผนก";
                header("Location: ../../login.php");
                exit();
            }

            session_regenerate_id(true);

            $_SESSION['teacher_id']  = $rs['teacher_id'];
            $_SESSION['full_name']   = $rs['full_name'];
            $_SESSION['username']    = $rs['username'];
            $_SESSION['image']       = $rs['image'] ?? '';
            $_SESSION['status']      = $rs['status'];
            $_SESSION['role']        = $rs['role'];

            if ($rs['role'] == 0) {
                header("Location: ../hod/index.php");
                exit();
            } else {
                header("Location: ../tc/index.php");
                exit();
            }
        }
    }

    $_SESSION['error'] = "ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง";
    header("Location: ../../login.php");
    exit();
}
