<?php
session_start();
include 'config/db.php';

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <link rel="icon" type="image/png" href="img/new_logo_svc_temp.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบบันทึกและตรวจสอบโครงการ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="home.css">
</head>

<body>
    <header class="custom-header">
        <div class="d-flex align-items-center gap-3">
            <img src="img/new_logo_svc_temp.png" alt="โลโก้" class="header-logo" onerror="this.src='https://placehold.co/70x70/8a1c14/white?text=LOGO'">
            <div>
                <h1 class="h4 mb-0 fw-bold">ระบบบันทึกและตรวจสอบโครงการ</h1>
                <p class="mb-0">แผนกธุรกิจดิจิทัลและเทคโนโลยีสารสนเทศ</p>
            </div>
        </div>
    </header>

    <main class="main-content container py-5">
        <div class="text-center mb-5">
            <h2 class="mb-2">
                <span class="welcome-title">ยินดีต้อนรับเข้าสู่</span>
            </h2>
            <h3 class="mt-3" style="color: #42b6f5 !important; font-weight: 500;">ระบบบันทึกและตรวจสอบโครงการ</h3>
        </div>

        <div class="row g-4 justify-content-center" style="max-width: 850px; width: 100%;">

            <div class="col-10 col-sm-6">
                <a href="#" class="menu-card" data-bs-toggle="modal" data-bs-target="#adminModal">
                    <img src="img/home/1.png" alt="ผู้ดูแล">
                    <span class="fs-5 fw-medium">ผู้ดูแล</span>
                </a>
            </div>

            <div class="col-10 col-sm-6">
                <a href="#" class="menu-card" data-bs-toggle="modal" data-bs-target="#teacherModal">
                    <img src="img/home/2.png" alt="คุณครู">
                    <span class="fs-5 fw-medium">คุณครู</span>
                </a>
            </div>

            <div class="col-10 col-sm-6">
                <a href="#" class="menu-card" data-bs-toggle="modal" data-bs-target="#stdModal">
                    <img src="img/home/3.png" alt="นักเรียน/นักศึกษา">
                    <span class="fs-5 fw-medium">นักเรียน/นักศึกษา</span>
                </a>
            </div>

            <div class="col-10 col-sm-6">
                <a href="Visit/index.php" class="menu-card">
                    <img src="img/home/4.png" alt="ผู้เยี่ยมชม">
                    <span class="fs-5 fw-medium">ผู้เยี่ยมชม</span>
                </a>
            </div>

        </div>
    </main>

    <div class="modal fade" id="adminModal" tabindex="-1" aria-labelledby="adminModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal-content">
                <div class="modal-header custom-modal-header">
                    <h5 class="modal-title fw-bold" id="adminModalLabel">⚙️ เข้าสู่ระบบสำหรับผู้ดูแล</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" action="admin/config_a/login.php">
                        <input type="hidden" name="role" value="admin">

                        <div class="mb-3">
                            <label for="adminUsername" class="form-label custom-form-label">ชื่อผู้ใช้งาน</label>
                            <input type="text" class="form-control" id="adminUsername" name="username" placeholder="กรอกชื่อผู้ใช้งาน" autocomplete="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="adminPassword" class="form-label custom-form-label">รหัสผ่าน</label>
                            <input type="password" class="form-control" id="adminPassword" name="password" placeholder="กรอกรหัสผ่าน" autocomplete="current-password" required>
                        </div>

                        <div class="modal-footer p-3 border-0">
                            <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" name="login-admin" class="btn btn-submit">เข้าสู่ระบบ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="teacherModal" tabindex="-1" aria-labelledby="teacherModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal-content">
                <div class="modal-header custom-modal-header">
                    <h5 class="modal-title fw-bold" id="teacherModalLabel">👩‍🏫 เข้าสู่ระบบสำหรับครู</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" action="teacher/config_t/login.php">
                        <input type="hidden" name="role" value="teacher">

                        <div class="mb-3">
                            <label for="teacherUsername" class="form-label custom-form-label">ชื่อผู้ใช้งาน</label>
                            <input type="text" class="form-control" id="teacherUsername" name="username" placeholder="กรอกชื่อผู้ใช้งาน" autocomplete="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="teacherPassword" class="form-label custom-form-label">รหัสผ่าน</label>
                            <input type="password" class="form-control" id="teacherPassword" name="password" placeholder="กรอกรหัสผ่าน" autocomplete="current-password" required>
                        </div>

                        <div class="modal-footer p-3 border-0">
                            <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" name="login-teacher" class="btn btn-submit">เข้าสู่ระบบ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="stdModal" tabindex="-1" aria-labelledby="stdModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content custom-modal-content">
                <div class="modal-header custom-modal-header">
                    <h5 class="modal-title fw-bold" id="stdModalLabel">👩‍🎓 เข้าสู่ระบบสำหรับนักเรียน</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" action="std/config_s/login.php">
                        <input type="hidden" name="role" value="std">

                        <div class="mb-3">
                            <label for="stdUsername" class="form-label custom-form-label">ชื่อผู้ใช้งาน</label>
                            <input type="text" class="form-control" id="stdUsername" name="username" placeholder="กรอกชื่อผู้ใช้งาน" autocomplete="username" required>
                        </div>

                        <div class="mb-3">
                            <label for="stdPassword" class="form-label custom-form-label">รหัสผ่าน</label>
                            <input type="password" class="form-control" id="stdPassword" name="password" placeholder="กรอกรหัสผ่าน" autocomplete="current-password" required>
                        </div>

                        <div class="modal-footer p-3 border-0">
                            <button type="button" class="btn btn-cancel" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" name="login-std" class="btn btn-submit">เข้าสู่ระบบ</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="met-0 text-center ">
            <span>&copy; 2026 ระบบบันทึกและตรวจสอบโครงการ ITSVC67</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <?php if (isset($_SESSION['error'])): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'เข้าสู่ระบบไม่สำเร็จ',
            text: '<?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>',
            confirmButtonColor: '#d33',
            confirmButtonText: 'ตกลง'
        });
    </script>
    <?php unset($_SESSION['error']); endif; ?>
</body>

</html>