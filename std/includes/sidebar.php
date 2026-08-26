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
                        <span>หน้าหลัก (Dashboard)</span>
                    </a>
                </li>
                <li>
                    <a href="assignments.php" class="<?= basename($_SERVER['PHP_SELF']) == 'assignments.php' ? 'active' : '' ?>">
                        <i data-lucide="file-up"></i>
                        <span>ส่งงานโครงงาน</span>
                    </a>
                </li>
            </ul>

            <hr class="divider" />

            <div class="logout-box">
                <a href="profile.php" class="logout-btn" style="text-decoration:none; margin-bottom:8px; background:rgba(97,191,237,0.1); color:#1976d2;" id="profileBtn">
                    <i data-lucide="user-cog"></i>
                    <span>แก้ไขข้อมูลตัวเอง</span>
                </a>
                <a href="config_s/logout.php?logout=1" class="logout-btn" id="logoutBtn" style="text-decoration:none;">
                    <i data-lucide="log-out"></i>
                    <span>ออกจากระบบ</span>
                </a>
            </div>
        </div>
    </aside>
</nav>