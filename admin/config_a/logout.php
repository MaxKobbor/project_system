<?php
session_start();

// ล้างค่าและทำลาย Session
session_unset();
session_destroy();

// Redirect กลับไปที่หน้า home (login.php อยู่ที่ root หรือปรับตามความเหมาะสม)
header("Location: ../../login.php");
exit();
