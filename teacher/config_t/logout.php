<?php
session_start();
include "../../config/db.php";

// ล้างค่าและทำลาย Session
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();

    // Redirect กลับไปที่หน้า home
    header("Location: ../../login.php");
    exit();
}
