<?php
// views/student/tabs/notifications.php
require_once __DIR__ . '/../../../api/db.php';

$userId = $_SESSION['user_id'] ?? 0;
$stmtNotifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY is_read ASC, created_at DESC LIMIT 40");
$stmtNotifs->execute([$userId]);
$notifications = $stmtNotifs->fetchAll(PDO::FETCH_ASSOC);

// Count unread
$unreadCount = 0;
foreach ($notifications as $n) {
    if (!$n['is_read']) $unreadCount++;
}
?>
<div class="animate-fade-in mb-4">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h2 class="fs-4 fw-black text-primary m-0 d-flex align-items-center gap-2">
                <i class="fa-solid fa-bell text-warning"></i>
                <span>الإشعارات</span>
                <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger rounded-pill fs-7"><?= $unreadCount ?> جديد</span>
                <?php endif; ?>
            </h2>
            <p class="small fw-bold text-secondary mt-1 m-0">تنبيهات الواجبات والتقييمات وإعلانات المدرسة</p>
        </div>
        <?php if (!empty($notifications)): ?>
        <button onclick="markAllNotificationsAsRead()" class="btn btn-light border shadow-sm text-primary hover-primary text-decoration-none px-3 py-2 fw-bold d-flex align-items-center gap-2 rounded-pill transition" style="font-size: 0.85rem;">
            <span>تحديد الكل كمقروء</span>
            <i class="fa-solid fa-check-double text-warning"></i>
        </button>
        <?php endif; ?>
    </div>

    <div class="bg-white rounded-4 border shadow-sm overflow-hidden" style="border-color: rgba(123,31,63,0.1) !important;">
        <?php if (empty($notifications)): ?>
            <div class="p-5 text-center my-4">
                <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px;">
                    <i class="fa-regular fa-bell-slash fs-2 text-muted"></i>
                </div>
                <h5 class="fw-bold text-dark mb-1">لا توجد إشعارات حتى الآن</h5>
                <p class="small text-muted m-0">ستظهر هنا كافة الإشعارات والتقييمات والتنبيهات الجديدة أولاً بأول.</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notif): 
                $isRead = (int)$notif['is_read'] === 1;
                $notifId = (int)$notif['id'];
                $link = $notif['link'] ?: '';

                // Choose icon based on type / title
                $icon = 'fa-bell';
                $color = 'primary';
                if (strpos($notif['title'], 'واجب') !== false || $notif['type'] === 'homework') {
                    $icon = 'fa-file-signature';
                    $color = 'primary';
                } elseif (strpos($notif['title'], 'تقييم') !== false || $notif['type'] === 'success') {
                    $icon = 'fa-trophy';
                    $color = 'warning';
                } elseif (strpos($notif['title'], 'إعلان') !== false || $notif['type'] === 'warning') {
                    $icon = 'fa-bullhorn';
                    $color = 'danger';
                } elseif (strpos($notif['title'], 'مادة') !== false || strpos($notif['title'], 'محتوى') !== false) {
                    $icon = 'fa-book-open';
                    $color = 'info';
                } elseif (strpos($notif['title'], 'اختبار') !== false) {
                    $icon = 'fa-file-circle-check';
                    $color = 'primary';
                }
            ?>
                <div id="notif-card-<?= $notifId ?>" 
                     onclick="handleNotificationClick(<?= $notifId ?>, '<?= htmlspecialchars($link, ENT_QUOTES) ?>')" 
                     class="p-4 d-flex gap-3 hover-bg-light transition cursor-pointer position-relative border-bottom <?= !$isRead ? 'unread-notification-card' : 'opacity-75' ?>" 
                     style="<?= !$isRead ? 'background-color: rgba(123,31,63,0.03);' : '' ?> border-color: rgba(0,0,0,0.05) !important;">
                    
                    <?php if (!$isRead): ?>
                        <div class="position-absolute top-0 bottom-0 end-0 bg-primary notif-unread-bar" style="width: 4px;"></div>
                    <?php endif; ?>

                    <div class="rounded-circle bg-white shadow-sm border d-flex align-items-center justify-content-center text-<?= $color ?> flex-shrink-0" style="width: 50px; height: 50px; font-size: 1.25rem; border-color: rgba(123,31,63,0.1) !important;">
                        <i class="fa-solid <?= $icon ?>"></i>
                    </div>

                    <div class="flex-grow-1 ps-2">
                        <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                            <h4 class="fw-black text-dark m-0" style="font-size: 0.95rem;"><?= htmlspecialchars($notif['title']) ?></h4>
                            <?php if (!$isRead): ?>
                                <span class="badge bg-primary-subtle text-primary rounded-pill small px-2">جديد</span>
                            <?php endif; ?>
                        </div>
                        <p class="small fw-bold text-secondary mb-2 lh-base" style="font-size: 0.82rem;"><?= nl2br(htmlspecialchars($notif['message'])) ?></p>
                        <div class="fw-bold text-secondary opacity-50 d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                            <i class="fa-regular fa-clock"></i> 
                            <?= date('Y/m/d h:i A', strtotime($notif['created_at'])) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function markAllNotificationsAsRead() {
    apiCall('notifications/read', 'POST', { mark_all: 1 })
        .then(res => {
            if (res && res.success) {
                // Remove unread bars and styling visually
                document.querySelectorAll('.notif-unread-bar').forEach(el => el.remove());
                document.querySelectorAll('.unread-notification-card').forEach(card => {
                    card.classList.remove('unread-notification-card');
                    card.classList.add('opacity-75');
                    card.style.backgroundColor = '';
                    const newBadge = card.querySelector('.bg-primary-subtle');
                    if (newBadge) newBadge.remove();
                });
                // Hide topbar badge
                const topBadge = document.getElementById('notifCountBadge');
                if (topBadge) topBadge.classList.add('d-none');
                
                // Refresh badge count
                if (typeof updateNotificationsBadge === 'function') updateNotificationsBadge();
            }
        }).catch(() => {});
}

function handleNotificationClick(notifId, targetTab) {
    apiCall('notifications/read', 'POST', { id: notifId })
        .then(() => {
            const card = document.getElementById('notif-card-' + notifId);
            if (card) {
                card.classList.remove('unread-notification-card');
                card.classList.add('opacity-75');
                card.style.backgroundColor = '';
                const bar = card.querySelector('.notif-unread-bar');
                if (bar) bar.remove();
                const newBadge = card.querySelector('.bg-primary-subtle');
                if (newBadge) newBadge.remove();
            }
            if (typeof updateNotificationsBadge === 'function') updateNotificationsBadge();
            if (targetTab && typeof switchTab === 'function') {
                switchTab(targetTab);
            }
        }).catch(() => {
            if (targetTab && typeof switchTab === 'function') switchTab(targetTab);
        });
}
</script>
