<?php
session_start();
include "../../config/db.php";

// ตรวจสอบว่าเป็นการส่งแบบ POST และมีการกดปุ่ม login-admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login-admin'])) {

    // รับค่าและตัดช่องว่างส่วนเกิน
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // ตรวจสอบว่ากรอกข้อมูลครบถ้วนหรือไม่ก่อน Query
    if (!empty($username) && !empty($password) && isset($pdo)) {

        $stmt = $pdo->prepare("SELECT admin_id, full_name, username, password, status FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $rs = $stmt->fetch(PDO::FETCH_ASSOC);

        // 1. เช็คว่าเจอผู้ใช้ไหม 
        // 2. ใช้ password_verify เช็ครหัสผ่านแบบ Hash
        // 3. ตรวจสอบ status (ปรับ 'active' ตามค่าในระบบของคุณ เช่น 1 หรือ 'active')
        if ($rs && password_verify($password, $rs['password'])) {

            if ((int)$rs['status'] === 0) { // ถ้า status เป็น 0 ให้ระงับทันที
                $_SESSION['error'] = "บัญชีของคุณถูกระงับการใช้งาน";
                header("Location: ../../login.php");
                exit();
            }

            // ป้องกัน Session Fixation
            session_regenerate_id(true);

            $_SESSION['admin_id']  = $rs['admin_id'];
            $_SESSION['full_name'] = $rs['full_name'];
            $_SESSION['username']  = $rs['username'];
            $_SESSION['status']    = $rs['status'];

            header("Location: ../index.php");
            exit();
        }
    }

    // กรณีรหัสผ่านผิด หรือไม่พบ Username
    $_SESSION['error'] = "ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง";
    header("Location: ../../login.php");
    exit();
}
