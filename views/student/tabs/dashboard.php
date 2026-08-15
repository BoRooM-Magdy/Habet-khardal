<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../../api/db.php';
}

$userId = $_SESSION['user_id'] ?? 0;

if (!$userId) {
    echo "<div class='alert alert-danger'>غير مصرح لك بالوصول.</div>";
    exit;
}

try {
    // Get user data
    $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmtUser->execute([$userId]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);

    // Get progress stats
    $stmtProg = $pdo->prepare("SELECT COUNT(*) FROM lesson_progress WHERE user_id = ? AND completed = 1");
    $stmtProg->execute([$userId]);
    $completedLessons = $stmtProg->fetchColumn();

    $plan = $user['plan'] ?? 'بذرة';
    $stageId = $user['stage_id'] ?: 1;

    // Fetch subjects strictly according to the student's Subscription Plan
    if ($plan === 'شجرة') {
        $stmtSubjects = $pdo->prepare("SELECT id, name FROM subjects WHERE stage_id = ? LIMIT 6");
        $stmtSubjects->execute([$stageId]);
    } elseif ($plan === 'نبتة') {
        $stmtSubjects = $pdo->prepare("SELECT id, name FROM subjects WHERE (is_core = 1 AND stage_id = ?) OR id IN (SELECT subject_id FROM user_subjects WHERE user_id = ?) LIMIT 6");
        $stmtSubjects->execute([$stageId, $userId]);
    } else {
        // بذرة (Core Subjects Only)
        $stmtSubjects = $pdo->prepare("SELECT id, name FROM subjects WHERE is_core = 1 AND stage_id = ? LIMIT 6");
        $stmtSubjects->execute([$stageId]);
    }
    $subjects = $stmtSubjects->fetchAll(PDO::FETCH_ASSOC);




    // Get notifications
    $stmtNotifs = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmtNotifs->execute([$userId]);
    $notifications = $stmtNotifs->fetchAll(PDO::FETCH_ASSOC);

    // SQL condition for plan access
    $planSubjQuery = ($plan === 'شجرة') 
        ? "s.stage_id = ?" 
        : (($plan === 'نبتة') 
            ? "(s.is_core = 1 AND s.stage_id = ?) OR s.id IN (SELECT subject_id FROM user_subjects WHERE user_id = ?)" 
            : "s.is_core = 1 AND s.stage_id = ?");

    // Get Pending Tasks restricted by subscription plan
    if ($plan === 'نبتة') {
        $stmtHW = $pdo->prepare("
            SELECT h.id, h.title, 'homework' as type 
            FROM homework h
            JOIN subjects s ON h.subject_id = s.id
            LEFT JOIN homework_submissions hs ON h.id = hs.homework_id AND hs.user_id = ?
            WHERE ($planSubjQuery) AND hs.id IS NULL
        ");
        $stmtHW->execute([$userId, $stageId, $userId]);
    } else {
        $stmtHW = $pdo->prepare("
            SELECT h.id, h.title, 'homework' as type 
            FROM homework h
            JOIN subjects s ON h.subject_id = s.id
            LEFT JOIN homework_submissions hs ON h.id = hs.homework_id AND hs.user_id = ?
            WHERE ($planSubjQuery) AND hs.id IS NULL
        ");
        $stmtHW->execute([$userId, $stageId]);
    }
    $pendingHW = $stmtHW->fetchAll(PDO::FETCH_ASSOC);

    if ($plan === 'نبتة') {
        $stmtMat = $pdo->prepare("
            SELECT m.id, m.title, 'material' as type 
            FROM materials m
            JOIN subjects s ON m.subject_id = s.id
            LEFT JOIN lesson_progress lp ON m.id = lp.material_id AND lp.user_id = ?
            WHERE ($planSubjQuery) AND (lp.completed = 0 OR lp.id IS NULL)
        ");
        $stmtMat->execute([$userId, $stageId, $userId]);
    } else {
        $stmtMat = $pdo->prepare("
            SELECT m.id, m.title, 'material' as type 
            FROM materials m
            JOIN subjects s ON m.subject_id = s.id
            LEFT JOIN lesson_progress lp ON m.id = lp.material_id AND lp.user_id = ?
            WHERE ($planSubjQuery) AND (lp.completed = 0 OR lp.id IS NULL)
        ");
        $stmtMat->execute([$userId, $stageId]);
    }
    $pendingMat = $stmtMat->fetchAll(PDO::FETCH_ASSOC);
    
    $pendingTasks = array_merge($pendingHW, $pendingMat);

    // Assistant Message Logic
    $assistantMessage = "أهلاً بك! لقد لاحظت أن مستواك يتقدم بامتياز.";
    if (count($pendingTasks) > 0) {
        $assistantMessage .= " لديك " . count($pendingTasks) . " مهام بانتظارك اليوم، هل أنت مستعد؟";
    } else {
        $assistantMessage .= " لقد أنهيت جميع مهامك اليوم، عمل رائع!";
    }

} catch (PDOException $e) {
    echo "<div class='alert alert-danger fw-bold m-4 text-center'>حدث خطأ في جلب البيانات.</div>";
    exit;
}

?>

<style>
/* Avant-Garde Dashboard Overrides */
.welcome-banner {
    background: linear-gradient(135deg, var(--brand-primary-dark) 0%, var(--brand-primary) 100%);
    position: relative;
    overflow: hidden;
    border-radius: 30px;
    box-shadow: 0 20px 40px rgba(107, 30, 45, 0.2);
}
.welcome-banner::before {
    content: '';
    position: absolute;
    top: -50%; left: -50%; width: 200%; height: 200%;
    background: radial-gradient(circle, rgba(212,175,55,0.15) 0%, transparent 60%);
    animation: slowRotate 20s linear infinite;
    pointer-events: none;
}
@keyframes slowRotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.glass-capsule {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 20px;
    transition: transform 0.3s, background 0.3s;
}
.glass-capsule:hover {
    transform: translateY(-3px);
    background: rgba(255, 255, 255, 0.15);
}
.subject-card {
    background: #fff;
    border-radius: 24px;
    border: 1px solid rgba(107, 30, 45, 0.05);
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}
.subject-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(107, 30, 45, 0.12);
    border-color: rgba(212,175,55,0.3);
}
.subject-card .icon-wrapper {
    width: 45px; height: 45px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(107, 30, 45, 0.05);
    color: var(--brand-primary);
    transition: all 0.3s;
}
.subject-card:hover .icon-wrapper {
    background: var(--brand-primary);
    color: #fff;
    transform: scale(1.1) rotate(-5deg);
}
.task-timeline {
    border-right: 2px dashed rgba(107, 30, 45, 0.15);
    padding-right: 20px;
    margin-right: 15px;
}
.task-item {
    position: relative;
    transition: all 0.3s;
}
.task-dot {
    position: absolute;
    right: -31px; /* Center on the dashed line */
    top: 50%;
    transform: translateY(-50%);
    width: 20px; height: 20px;
    border-radius: 50%;
    background: #fff;
    border: 3px solid var(--brand-primary);
    z-index: 2;
    transition: all 0.3s;
}
.task-item.active .task-dot {
    background: var(--brand-primary);
    box-shadow: 0 0 0 5px rgba(107, 30, 45, 0.2);
    animation: pulseGlow 2s infinite;
}
.task-item.completed .task-dot {
    background: var(--brand-gold);
    border-color: var(--brand-gold);
}
@keyframes pulseGlow {
    0% { box-shadow: 0 0 0 0 rgba(107, 30, 45, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(107, 30, 45, 0); }
    100% { box-shadow: 0 0 0 0 rgba(107, 30, 45, 0); }
}
.assistant-card {
    background: #fff;
    border-radius: 30px;
    border: 1px solid rgba(212,175,55,0.2);
    box-shadow: 0 15px 35px rgba(0,0,0,0.05);
    position: relative;
    overflow: hidden;
}
.assistant-glow {
    position: absolute;
    top: -50px; right: -50px;
    width: 150px; height: 150px;
    background: radial-gradient(circle, rgba(212,175,55,0.2) 0%, transparent 70%);
    border-radius: 50%;
    z-index: 0;
}
</style>

<div class="container-fluid py-4 animate-fade-in" style="max-width: 1200px;">
    
    <!-- Welcome Banner (Avant-Garde) -->
    <div class="welcome-banner mb-5 p-4 p-md-5">
        <div class="row align-items-center position-relative z-1">
            <div class="col-lg-7 text-center text-md-start mb-4 mb-lg-0">
                <div class="d-flex flex-row-reverse align-items-center justify-content-center justify-content-md-start gap-3 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-lg" style="width: 60px; height: 60px; background: rgba(255,255,255,0.2); backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.3); font-size: 2rem;">
                        <?= $user['gender'] === 'girl' ? '👧' : '👦' ?>
                    </div>
                    <div>
                        <h2 class="fw-black text-white mb-0" style="letter-spacing: -0.5px; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">
                            أهلاً بك يا بطل، <span style="color: var(--brand-gold);"><?= htmlspecialchars($user['child_name'] ?: 'الطالب') ?></span>!
                        </h2>
                        <p class="text-white opacity-75 fw-semibold m-0 mt-1">دعنا نواصل رحلة التعلم والتفوق اليوم.</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="d-flex align-items-center justify-content-center justify-content-lg-end gap-3 flex-wrap">
                    <div class="glass-capsule px-4 py-2 text-center">
                        <div class="fs-4 fw-black" style="color: var(--brand-gold);"><?= $user['stars'] ?></div>
                        <div class="text-white opacity-75 small fw-bold">نجمة 🌟</div>
                    </div>
                    <div class="glass-capsule px-4 py-2 text-center">
                        <div class="fs-4 fw-black" style="color: var(--brand-gold);"><?= $user['streak_days'] ?></div>
                        <div class="text-white opacity-75 small fw-bold">أيام 🔥</div>
                    </div>
                    <div class="glass-capsule px-4 py-2 text-center">
                        <div class="fs-4 fw-black" style="color: var(--brand-gold);"><?= $completedLessons ?></div>
                        <div class="text-white opacity-75 small fw-bold">دروس 📚</div>
                    </div>
                    <?php
                    $planIcons = ['بذرة' => '🌱', 'نبتة' => '🌿', 'شجرة' => '🌳'];
                    $pIcon = $planIcons[$plan] ?? '🌱';
                    ?>
                    <div class="glass-capsule px-4 py-2 text-center cursor-pointer hover-scale transition" onclick="showSubscriptionModal()" title="انقر لمعرفة تفاصيل خطة اشتراكك">
                        <div class="fs-4 fw-black" style="color: var(--brand-gold);"><?= htmlspecialchars($plan) ?> <?= $pIcon ?></div>
                        <div class="text-white opacity-75 small fw-bold">الاشتراك الحالي</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interactive Subscription Modal Structure -->
    <div class="modal fade" id="subscriptionInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
                <div class="modal-header text-white border-0 py-3 px-4" style="background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark));">
                    <h5 class="modal-title fw-black d-flex align-items-center gap-2 m-0">
                        <i class="fa-solid fa-crown text-warning"></i> نظام خطط الاشتراكات المدرسية
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4 bg-light">
                    <div class="text-center mb-4">
                        <span class="badge rounded-pill bg-warning text-dark px-4 py-2 fs-6 fw-black shadow-sm">
                            اشتراكك الحالي: خطة <?= htmlspecialchars($plan) ?> <?= $pIcon ?>
                        </span>
                    </div>

                    <div class="d-flex flex-column gap-3">
                        <!-- Seed -->
                        <div class="p-3 rounded-4 bg-white border <?= $plan === 'بذرة' ? 'border-warning border-2 shadow-sm' : 'border-light' ?>">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="fw-black text-dark m-0">🌱 خطة بذرة (Seed)</h6>
                                <?php if ($plan === 'بذرة'): ?><span class="badge bg-primary small">باقتك الحالية</span><?php endif; ?>
                            </div>
                            <p class="small text-secondary m-0">تتيح الوصول لكافة <strong>المواد الأساسية</strong> المقررة للمرحلة الدراسية.</p>
                        </div>

                        <!-- Sprout -->
                        <div class="p-3 rounded-4 bg-white border <?= $plan === 'نبتة' ? 'border-warning border-2 shadow-sm' : 'border-light' ?>">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="fw-black text-dark m-0">🌿 خطة نبتة (Sprout)</h6>
                                <?php if ($plan === 'نبتة'): ?><span class="badge bg-primary small">باقتك الحالية</span><?php endif; ?>
                            </div>
                            <p class="small text-secondary m-0">تتيح الوصول للمواد الأساسية + <strong>مواد اختيارية إضافية</strong> محددة من قبل إدارة المدرسة.</p>
                        </div>

                        <!-- Tree -->
                        <div class="p-3 rounded-4 bg-white border <?= $plan === 'شجرة' ? 'border-warning border-2 shadow-sm' : 'border-light' ?>">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <h6 class="fw-black text-dark m-0">🌳 خطة شجرة (Tree)</h6>
                                <?php if ($plan === 'شجرة'): ?><span class="badge bg-primary small">باقتك الحالية</span><?php endif; ?>
                            </div>
                            <p class="small text-secondary m-0">الباقة الشاملة العُليا؛ تفتح لك <strong>جميع المواد الدراسية والأنشطة</strong> بدون استثناء.</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3 bg-white justify-content-center">
                    <button type="button" class="btn btn-outline-secondary fw-bold rounded-pill px-4" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row g-5">
        
        <!-- Right Column -->
        <div class="col-lg-8 d-flex flex-column gap-5">
            
            <!-- Subjects Section -->
            <div>
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <h3 class="fw-black fs-4 m-0" style="color: var(--brand-primary-dark);">
                        موادي الدراسية
                    </h3>
                    <button class="btn btn-link fw-bold text-decoration-none px-0" style="color: var(--brand-gold);" onclick="switchTab('subjects')">
                        عرض الكل <i class="fa-solid fa-arrow-left ms-1 small"></i>
                    </button>
                </div>
                
                <?php if (empty($subjects)): ?>
                    <div class="p-5 text-center bg-white rounded-4 shadow-sm border" style="border-color: rgba(107,30,45,0.1);">
                        <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; background: rgba(107,30,45,0.05); color: var(--brand-primary);">
                            <i class="fa-solid fa-ghost fs-2"></i>
                        </div>
                        <div class="fw-bold fs-5 text-secondary">لا توجد مواد مسجلة حتى الآن</div>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($subjects as $s): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="subject-card p-3 cursor-pointer d-flex flex-column h-100" onclick="switchTab('materials&subject_id=<?= $s['id'] ?>')">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="icon-wrapper">
                                        <i class="fa-solid fa-book"></i>
                                    </div>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; background: rgba(212,175,55,0.1); color: var(--brand-gold);">
                                        <i class="fa-solid fa-arrow-left small"></i>
                                    </div>
                                </div>
                                <div class="mt-auto">
                                    <h5 class="fw-black mb-1" style="color: var(--brand-primary-dark);"><?= htmlspecialchars($s['name']) ?></h5>
                                    <p class="small text-muted fw-bold m-0 d-flex align-items-center gap-1">
                                        <i class="fa-solid fa-play-circle" style="color: var(--brand-gold);"></i> متابعة التعلم
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tasks / Journey (Gamified) -->
            <div>
                <h3 class="fw-black fs-4 mb-4" style="color: var(--brand-primary-dark);">
                    رحلتي اليوم
                </h3>
                
                <div class="bg-white rounded-4 p-4 shadow-sm border" style="border-color: rgba(107,30,45,0.1);">
                    <?php if (empty($pendingTasks)): ?>
                        <div class="text-center py-4">
                            <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px; background: rgba(212,175,55,0.1); color: var(--brand-gold);">
                                <i class="fa-solid fa-trophy fs-3"></i>
                            </div>
                            <h5 class="fw-bold text-dark">لقد أنهيت كل مهامك!</h5>
                            <p class="text-secondary small">أنت طالب مميز، استمر في تفوقك.</p>
                        </div>
                    <?php else: ?>
                        <div class="task-timeline">
                            <?php foreach ($pendingTasks as $index => $task): 
                                $isActive = ($index === 0); // First task is active
                            ?>
                                <?php if ($isActive): ?>
                                    <div class="task-item active d-flex align-items-center gap-3 py-3 cursor-pointer" data-task-id="<?= $task['type'] . '_' . $task['id'] ?>" onclick="toggleTask(this, '<?= $task['type'] ?>', <?= $task['id'] ?>)">
                                        <div class="task-dot"></div>
                                        <div class="task-text fw-black flex-grow-1" style="color: var(--brand-primary-dark);"><?= htmlspecialchars($task['title']) ?></div>
                                        <span class="badge rounded-pill px-3 py-2 text-white shadow-sm" style="background: var(--brand-primary);">الآن</span>
                                    </div>
                                <?php else: ?>
                                    <div class="task-item d-flex align-items-center gap-3 py-3 cursor-pointer" data-task-id="<?= $task['type'] . '_' . $task['id'] ?>" onclick="toggleTask(this, '<?= $task['type'] ?>', <?= $task['id'] ?>)">
                                        <div class="task-dot" style="border-color: #ddd;"></div>
                                        <div class="task-text fw-bold text-dark flex-grow-1"><?= htmlspecialchars($task['title']) ?></div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Left Column -->
        <div class="col-lg-4 d-flex flex-column gap-5">
            
            <!-- School Assistant (Atmospheric Design) -->
            <div class="assistant-card p-4 text-center">
                <div class="assistant-glow"></div>
                <div class="position-relative z-1">
                    <div class="mx-auto mb-3 position-relative" style="width: 80px; height: 80px;">
                        <div class="position-absolute w-100 h-100 rounded-circle" style="background: var(--brand-primary); opacity: 0.1; transform: scale(1.2); animation: pulseGlow 3s infinite;"></div>
                        <div class="position-absolute w-100 h-100 rounded-circle d-flex align-items-center justify-content-center text-white fs-2 shadow-lg" style="background: linear-gradient(135deg, var(--brand-primary) 0%, var(--brand-primary-dark) 100%);">
                            <i class="fa-solid fa-church"></i>
                        </div>
                    </div>
                    <h4 class="fw-black fs-5 mb-2" style="color: var(--brand-primary-dark);">مدرسة حبة خردل</h4>
                    <p class="small fw-bold text-secondary m-0 lh-lg">
                        <?= htmlspecialchars($assistantMessage) ?>
                    </p>
                </div>
            </div>

            <!-- Notifications -->
            <div class="bg-white rounded-4 p-4 shadow-sm border" style="border-color: rgba(107,30,45,0.1);">
                <h4 class="fw-black fs-5 mb-4" style="color: var(--brand-primary-dark);">أحدث الإشعارات</h4>
                
                <div class="d-flex flex-column gap-4">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center text-muted small fw-bold py-3">لا توجد إشعارات جديدة.</div>
                    <?php else: ?>
                        <?php foreach ($notifications as $notif): 
                            $icon = 'fa-bell';
                            $iconColor = 'var(--brand-primary)';
                            $iconBg = 'rgba(107, 30, 45, 0.1)';
                            
                            if ($notif['type'] == 'warning') {
                                $icon = 'fa-fire';
                                $iconColor = 'var(--brand-gold)';
                                $iconBg = 'rgba(212,175,55,0.1)';
                            } elseif ($notif['type'] == 'success') {
                                $icon = 'fa-medal';
                            }
                        ?>
                        <div class="d-flex gap-3 align-items-start">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px; background: <?= $iconBg ?>; color: <?= $iconColor ?>;">
                                <i class="fa-solid <?= $icon ?>"></i>
                            </div>
                            <div>
                                <div class="fw-bold fs-6" style="color: var(--brand-primary-dark);"><?= htmlspecialchars($notif['title']) ?></div>
                                <div class="small fw-bold text-secondary mt-1 lh-base"><?= htmlspecialchars($notif['message']) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>
</div>

<script>
function toggleTask(el, type, id) {
    const isCompleted = el.classList.contains('completed');
    const taskId = el.getAttribute('data-task-id');
    
    if (isCompleted) {
        // Demote to normal
        el.className = 'task-item d-flex align-items-center gap-3 py-3 cursor-pointer';
        el.querySelector('.task-text').className = 'task-text fw-bold text-dark flex-grow-1';
        el.querySelector('.task-dot').style.borderColor = '#ddd';
        const icon = el.querySelector('i.fa-check-circle');
        if (icon) icon.remove();
        
        // Remove from localStorage
        let completedTasks = JSON.parse(localStorage.getItem('completed_dashboard_tasks') || '[]');
        completedTasks = completedTasks.filter(t => t !== taskId);
        localStorage.setItem('completed_dashboard_tasks', JSON.stringify(completedTasks));
        
    } else {
        if (type === 'homework') {
            Swal.fire({
                title: 'تنبيه الواجب',
                text: 'لكي يتم حفظ هذا الواجب، يجب عليك الذهاب لصفحة الواجبات وتسليمه فعلياً (رفع ملف أو تسجيل صوتي). هل تريد الذهاب للواجبات الآن؟',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'نعم، اذهب للواجبات',
                cancelButtonText: 'لا، فقط علمها كمكتملة هنا'
            }).then((result) => {
                if (result.isConfirmed) {
                    switchTab('homework');
                } else {
                    markAsDone(el, taskId);
                }
            });
        } else {
            // It's a material, we can just mark it
            markAsDone(el, taskId);
            
            // Optionally, we could send an AJAX to mark it read in the DB
            fetch('/api/index.php?action=mark_progress', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ material_id: id })
            }).catch(e => console.error(e));
        }
    }
}

function markAsDone(el, taskId) {
    el.className = 'task-item completed d-flex align-items-center gap-3 py-3 cursor-pointer';
    el.querySelector('.task-text').className = 'task-text fw-bold text-secondary text-decoration-line-through flex-grow-1';
    el.querySelector('.task-dot').style.borderColor = ''; // Let css handle it
    
    // Add check icon if not exists
    if (!el.querySelector('i.fa-check-circle')) {
        const icon = document.createElement('i');
        icon.className = 'fa-solid fa-check-circle fs-5';
        icon.style.color = 'var(--brand-gold)';
        el.appendChild(icon);
    }
    
    // Remove badge if it was active
    const badge = el.querySelector('.badge');
    if (badge) badge.remove();
    
    // Save to localStorage
    let completedTasks = JSON.parse(localStorage.getItem('completed_dashboard_tasks') || '[]');
    if (!completedTasks.includes(taskId)) {
        completedTasks.push(taskId);
        localStorage.setItem('completed_dashboard_tasks', JSON.stringify(completedTasks));
    }
}

// Apply completed states from localStorage immediately (since this might be injected via AJAX)
(function initDashboardTasks() {
    let completedTasks = JSON.parse(localStorage.getItem('completed_dashboard_tasks') || '[]');
    document.querySelectorAll('.task-item').forEach(el => {
        const taskId = el.getAttribute('data-task-id');
        if (completedTasks.includes(taskId)) {
            markAsDone(el, taskId);
        }
})();

function showSubscriptionModal() {
    const modalEl = document.getElementById('subscriptionInfoModal');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }
}
</script>
