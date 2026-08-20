<nav>
    <aside class="sidebar collapsed" id="sidebar">
        <div class="sidebar-inner">
            <header class="sidebar-header">
                <button type="button" class="collapse-btn" id="collapseBtn">
                    <i data-lucide="panel-left"></i>
                </button>
            </header>

            <ul class="nav">
                <li>
                    <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" id="homeBtn">
                        <i data-lucide="house"></i>
                        <span>หนัาหลัก</span>
                    </a>
                </li>
                <li>
                    <a href="academic_years.php" class="<?= basename($_SERVER['PHP_SELF']) == 'academic_years.php' ? 'active' : '' ?>" id="yearsBtn">
                        <i data-lucide="calendar"></i>
                        <span>ปีการศึกษา</span>
                    </a>
                </li>
                <li>
                    <a href="branches.php" class="<?= basename($_SERVER['PHP_SELF']) == 'branches.php' ? 'active' : '' ?>" id="branchesBtn">
                        <i data-lucide="layers"></i>
                        <span>สาขาวิชา</span>
                    </a>
                </li>
                <li>
                    <a href="classrooms.php" class="<?= basename($_SERVER['PHP_SELF']) == 'classrooms.php' ? 'active' : '' ?>" id="classroomsBtn">
                        <i data-lucide="grid"></i>
                        <span>ห้องเรียน</span>
                    </a>
                </li>
                <li>
                    <a href="manage_teachers.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_teachers.php' ? 'active' : '' ?>" id="teachersBtn">
                        <i data-lucide="users"></i>
                        <span>คุณครู</span>
                    </a>
                </li>
                <li>
                    <a href="manage_admins.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_admins.php' ? 'active' : '' ?>" id="adminsBtn">
                        <i data-lucide="shield"></i>
                        <span>แอดมิน</span>
                    </a>
                </li>
                <li>
                    <a href="assignments.php" class="<?= basename($_SERVER['PHP_SELF']) == 'assignments.php' ? 'active' : '' ?>" id="assignmentsBtn">
                        <i data-lucide="clipboard"></i>
                        <span>หัวข้องาน</span>
                    </a>
                </li>
                <li>
                    <a href="groups_view.php" class="<?= basename($_SERVER['PHP_SELF']) == 'groups_view.php' ? 'active' : '' ?>" id="groupsBtn">
                        <i data-lucide="users-round"></i>
                        <span>กลุ่มนักเรียน</span>
                    </a>
                </li>
            </ul>

            <hr class="divider" />

            <div class="logout-box">
                <a href="profile.php" class="logout-btn" style="text-decoration:none; margin-bottom:8px; background:rgba(97,191,237,0.1); color:#1976d2;" id="profileBtn">
                    <i data-lucide="user-cog"></i>
                    <span>แก้ไขข้อมูลตัวเอง</span>
                </a>
                <button type="button" class="logout-btn" id="logoutBtn" name="logout">
                    <i data-lucide="log-out"></i>
                    <span>ออกจากระบบ</span>
                </button>
            </div>
        </div>
    </aside>
</nav>