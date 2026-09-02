<?php
session_start();

// ล้างค่าและทำลาย Session
session_unset();
session_destroy();

// Redirect กลับไปที่หน้าเข้าสู่ระบบ
header("Location: ../../login.php");
exit();
