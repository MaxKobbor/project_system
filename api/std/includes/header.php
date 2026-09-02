<?php
// Fetch notifications for student
$noti_group_id = $_SESSION['group_id'] ?? 0;
$stmtNoti = $pdo->prepare("SELECT * FROM `notifications` WHERE type = 'student' AND group_id = ? ORDER BY created_at DESC LIMIT 20");
$stmtNoti->execute([$noti_group_id]);
$notifications = $stmtNoti->fetchAll(PDO::FETCH_ASSOC);

$unread_count = 0;
foreach ($notifications as $n) {
    if ($n['is_read'] == 0) $unread_count++;
}
?>
<style>
    .noti-panel {
        display: none;
        position: fixed;
        top: 0;
        right: 0;
        width: 420px;
        height: 100vh;
        background: #fff;
        box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
        z-index: 2000;
        flex-direction: column;
        animation: slideIn 0.25s ease-out;
    }

    .noti-panel.active {
        display: flex;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
        }

        to {
            transform: translateX(0);
        }
    }

    .noti-panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 20px;
        border-bottom: 1px solid #eee;
        background: #f8f9fa;
    }

    .noti-panel-header h5 {
        margin: 0;
        font-weight: 700;
        font-size: 1.2rem;
        color: #333;
    }

    .noti-panel-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .noti-panel-actions button {
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
    }

    .noti-panel-actions .btn-read-all {
        color: #5eb2ff;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .noti-panel-actions .btn-delete-all {
        color: #e74c3c;
    }

    .noti-panel-actions .btn-close-noti {
        color: #999;
        font-size: 1.2rem;
    }

    .noti-panel-body {
        flex: 1;
        overflow-y: auto;
        padding: 0;
    }

    .noti-item-card {
        padding: 16px 20px;
        border-bottom: 1px solid #f0f0f0;
        cursor: pointer;
        transition: background 0.15s;
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .noti-item-card:hover {
        background: #f8f9fa;
    }

    .noti-item-card.unread {
        background: #e8f4fd;
        border-left: 4px solid #5eb2ff;
    }

    .noti-item-card.unread:hover {
        background: #d9edf7;
    }

    .noti-dot {
        width: 10px;
        height: 10px;
        background: #e74c3c;
        border-radius: 50%;
        flex-shrink: 0;
        margin-top: 6px;
    }

    .noti-item-title {
        font-weight: 600;
        color: #333;
        font-size: 0.95rem;
        margin-bottom: 2px;
    }

    .noti-item-msg {
        color: #666;
        font-size: 0.88rem;
        margin-bottom: 4px;
        line-height: 1.4;
    }

    .noti-item-time {
        color: #aaa;
        font-size: 0.78rem;
    }

    .noti-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background: rgba(0, 0, 0, 0.3);
        z-index: 1999;
    }

    .noti-overlay.active {
        display: block;
    }

    .noti-badge {
        position: absolute;
        top: -2px;
        right: -6px;
        background: #e74c3c;
        color: #fff;
        border-radius: 50%;
        min-width: 20px;
        height: 20px;
        font-size: 0.72rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px solid #5eb2ff;
    }

    .noti-bell-btn {
        background: rgba(255, 255, 255, 0.2);
        border: none;
        border-radius: 50%;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        position: relative;
        transition: background 0.2s;
        color: #fff;
    }

    .noti-bell-btn:hover {
        background: rgba(255, 255, 255, 0.35);
    }

    .noti-empty {
        text-align: center;
        padding: 60px 20px;
        color: #aaa;
    }

    .noti-empty i {
        opacity: 0.4;
        margin-bottom: 10px;
    }
</style>

<header class="custom-header d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-3 w-100">
        <button class="mobile-menu-btn d-none" id="mobileMenuBtn">
            <i data-lucide="menu"></i>
        </button>
        <img src="../img/new_logo_svc_temp.png" alt="โลโก้" class="header-logo" onerror="this.src='https://placehold.co/70x70/5eb2ff/white?text=LOGO'">
        <div>
            <h1 class="h4 mb-0 fw-bold">ระบบบันทึกและตรวจสอบโครงการ</h1>
            <p class="mb-0">แผนกธุรกิจดิจิทัลและเทคโนโลยีสารสนเทศ (สำหรับนักเรียน)</p>
        </div>
    </div>

    <button class="noti-bell-btn" onclick="toggleNotiPanel()">
        <i data-lucide="bell" style="width:22px;height:22px;"></i>
        <?php if ($unread_count > 0): ?>
            <span class="noti-badge"><?= $unread_count ?></span>
        <?php endif; ?>
    </button>
</header>

<!-- Notification Overlay -->
<div class="noti-overlay" id="notiOverlay" onclick="toggleNotiPanel()"></div>

<!-- Notification Side Panel -->
<div class="noti-panel" id="notiPanel">
    <div class="noti-panel-header">
        <h5>การแจ้งเตือน</h5>
        <div class="noti-panel-actions">
            <button class="btn-read-all" onclick="markAllRead()">อ่านทั้งหมด</button>
            <button class="btn-close-noti" onclick="toggleNotiPanel()"><i data-lucide="x" style="width:20px;height:20px;"></i></button>
        </div>
    </div>
    <div class="noti-panel-body">
        <?php if (empty($notifications)): ?>
            <div class="noti-empty">
                <i data-lucide="bell-off" style="width:48px;height:48px;"></i>
                <p>ยังไม่มีการแจ้งเตือน</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <div class="noti-item-card <?= $n['is_read'] == 0 ? 'unread' : '' ?>" onclick="markRead(<?= $n['notification_id'] ?>)">
                    <?php if ($n['is_read'] == 0): ?>
                        <div class="noti-dot"></div>
                    <?php endif; ?>
                    <div>
                        <div class="noti-item-title"><?= htmlspecialchars($n['title']) ?> :</div>
                        <div class="noti-item-msg"><?= htmlspecialchars($n['message']) ?></div>
                        <div class="noti-item-time"><?= date('d/m/Y H.i', strtotime($n['created_at'])) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleNotiPanel() {
        document.getElementById('notiPanel').classList.toggle('active');
        document.getElementById('notiOverlay').classList.toggle('active');
    }

    function markRead(id) {
        fetch('ajax/mark_read.php?id=' + id)
            .then(res => res.json())
            .then(data => {
                if (data.success) location.reload();
            });
    }

    function markAllRead() {
        fetch('ajax/mark_read.php?all=1&type=student')
            .then(res => res.json())
            .then(data => {
                if (data.success) location.reload();
            });
    }
</script>