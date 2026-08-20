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
                    <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>">
                        <i data-lucide="house"></i>
                        <span>หน้าหลัก</span>
                    </a>
                </li>
                <li>
                    <a href="manage_teachers.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_teachers.php' ? 'active' : '' ?>">
                        <i data-lucide="users"></i>
                        <span>จัดการคุณครู</span>
                    </a>
                </li>
                <li>
                    <a href="project_approvals.php" class="<?= basename($_SERVER['PHP_SELF']) == 'project_approvals.php' ? 'active' : '' ?>">
                        <i data-lucide="check-square"></i>
                        <span>อนุมัติโครงงาน</span>
                    </a>
                </li>
            </ul>

            <hr class="divider" />

            <div class="logout-box">
                <a href="profile.php" class="logout-btn" style="text-decoration:none; margin-bottom:8px; background:rgba(97,191,237,0.1); color:#1976d2;" id="profileBtn">
                    <i data-lucide="user-cog"></i>
                    <span>แก้ไขข้อมูลตัวเอง</span>
                </a>
                <a href="../config_t/logout.php?logout=1" class="logout-btn" id="logoutBtn" style="text-decoration:none;">
                    <i data-lucide="log-out"></i>
                    <span>ออกจากระบบ</span>
                </a>
            </div>
        </div>
    </aside>
</nav>