<?php
session_start();
include "../../config/db.php";

// ----------------------------------------------------------------------
// EDIT TEACHER (แก้ไขข้อมูลครู + ลบรูปเก่าอัตโนมัติ)
// ----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit-teacher'])) {
    
    $teacher_id = $_POST['teacher_id'] ?? '';
    $full_name  = trim($_POST['full_name'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $role       = $_POST['role'] ?? '';
    $status     = $_POST['status'] ?? '1';

    if (empty($teacher_id) || empty($full_name) || empty($username)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลสำคัญให้ครบถ้วน";
        header("Location: ../index.php");
        exit();
    }

    if (isset($pdo)) {
        try {
            // 1. ดึงข้อมูลครูคนเดิมมาก่อนเพื่อเอาชื่อรูปภาพเก่า
            $stmt_old = $pdo->prepare("SELECT image FROM teachers WHERE teacher_id = ?");
            $stmt_old->execute([$teacher_id]);
            $teacher = $stmt_old->fetch(PDO::FETCH_ASSOC);

            if (!$teacher) {
                $_SESSION['error'] = "ไม่พบข้อมูลครูที่ต้องการแก้ไข";
                header("Location: ../index.php");
                exit();
            }

            $old_image_name = $teacher['image'];
            $new_image_name = $old_image_name; // ค่าตั้งต้นให้อ้างอิงชื่อรูปเดิมไว้ก่อน
            $is_new_image_uploaded = false;

            // 2. จัดการรูปภาพใหม่ (ถ้ามีการอัปโหลดเข้ามา)
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp  = $_FILES['image']['tmp_name'];
                $file_name = $_FILES['image']['name'];
                $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed   = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($ext, $allowed)) {
                    // ใส่ time() ต่อท้ายเพื่อป้องกัน Browser Cache รูปเก่า
                    $new_image_name = 'teacher_' . $teacher_id . '_' . time() . '.' . $ext;
                    $upload_path    = "../../uploads/teachers/" . $new_image_name;

                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $is_new_image_uploaded = true;
                    } else {
                        $_SESSION['error'] = "ไม่สามารถย้ายไฟล์รูปภาพใหม่ได้";
                        header("Location: ../index.php");
                        exit();
                    }
                } else {
                    $_SESSION['error'] = "อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG, WEBP) เท่านั้น";
                    header("Location: ../index.php");
                    exit();
                }
            }

            // 3. อัปเดตข้อมูลลง Database
            if (!empty($password)) {
                // ถ้ามีการกรอกรหัสผ่านใหม่เข้ามาด้วย
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE teachers SET full_name = ?, username = ?, password = ?, role = ?, image = ?, status = ? WHERE teacher_id = ?";
                $params = [$full_name, $username, $hashed_password, $role, $new_image_name, $status, $teacher_id];
            } else {
                // ถ้าไม่ได้เปลี่ยนรหัสผ่าน (ใช้รหัสผ่านเดิม)
                $sql = "UPDATE teachers SET full_name = ?, username = ?, role = ?, image = ?, status = ? WHERE teacher_id = ?";
                $params = [$full_name, $username, $role, $new_image_name, $status, $teacher_id];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            // 4. ลบไฟล์รูปภาพเก่าออกจากเซิร์ฟเวอร์ (ถ้ามีการเปลี่ยนรูปสำเร็จ และรูปเก่าไม่ใช่ default.png)
            if ($is_new_image_uploaded && !empty($old_image_name) && $old_image_name !== 'default.png') {
                $old_file_path = "../../uploads/teachers/" . basename($old_image_name);
                if (file_exists($old_file_path)) {
                    unlink($old_file_path); // สั่งลบไฟล์เก่าทิ้งทันที
                }
            }

            $_SESSION['success'] = "แก้ไขข้อมูลครูเรียบร้อยแล้ว";
            header("Location: ../index.php");
            exit();

        } catch (PDOException $e) {
            // ถ้า Update DB ล้มเหลว แต่ย้ายไฟล์ใหม่ไปแล้ว ให้ลบไฟล์ใหม่ทิ้งเพื่อไม่ให้เป็นขยะ
            if ($is_new_image_uploaded && file_exists("../../uploads/teachers/" . $new_image_name)) {
                unlink("../../uploads/teachers/" . $new_image_name);
            }
            $_SESSION['error'] = "เกิดข้อผิดพลาดของระบบฐานข้อมูล";
            header("Location: ../index.php");
            exit();
        }
    }
}

?>