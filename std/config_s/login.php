<?php
session_start();
include "../../config/db.php";

// std login
if (isset($_POST['login-std'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (isset($pdo)) {
        $stmt = $pdo->prepare("SELECT group_id, project_name, username, password, std_1, std_2, std_3, std_4, status FROM `groups` WHERE username = ?");
        $stmt->execute([$username]);
        $rs = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($rs && password_verify($password, $rs['password'])) {
            session_regenerate_id(true);

            $_SESSION['group_id']     = $rs['group_id'];
            $_SESSION['project_name'] = $rs['project_name'];
            $_SESSION['username']     = $rs['username'];
            $_SESSION['std_1']        = $rs['std_1'];
            $_SESSION['std_2']        = $rs['std_2'];
            $_SESSION['std_3']        = $rs['std_3'];
            $_SESSION['std_4']        = $rs['std_4'];
            $_SESSION['status']       = $rs['status'];

            $names = array_filter([$rs['std_1'], $rs['std_2'], $rs['std_3'], $rs['std_4']]);
            $_SESSION['full_name']    = implode(', ', $names);

            header("Location: ../index.php");
            exit();
        }
    }

    $_SESSION['error'] = "ชื่อผู้ใช้งานหรือรหัสผ่านไม่ถูกต้อง";
    header("Location: ../../login.php");
    exit();
}
