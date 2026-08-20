<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if teacher is logged in and matches the required role.
 * 
 * @param int|null $requiredRole 0 for HOD, 1 for TC, or null for any logged-in teacher
 */
function check_teacher_auth($requiredRole = null) {
    if (!isset($_SESSION['teacher_id']) || empty($_SESSION['teacher_id'])) {
        header("Location: ../../login.php");
        exit();
    }

    if (isset($_SESSION['status']) && $_SESSION['status'] != 1) {
        session_unset();
        session_destroy();
        header("Location: ../../login.php?error=" . urlencode("บัญชีของคุณถูกปิดใช้งาน"));
        exit();
    }

    if ($requiredRole !== null && isset($_SESSION['role'])) {
        if ($_SESSION['role'] != $requiredRole) {
            // Determine base directory relative to application root
            if ($_SESSION['role'] == 0) {
                header("Location: ../hod/index.php");
            } else {
                header("Location: ../tc/index.php");
            }
            exit();
        }
    }
}
?>
