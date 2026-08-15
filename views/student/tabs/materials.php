<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../../api/db.php';
}

$subject_id = filter_input(INPUT_GET, 'subject_id', FILTER_VALIDATE_INT);
if (!$subject_id) {
    echo '<div class="alert alert-danger m-4 text-center fw-bold shadow-sm">مادة غير صالحة.</div>';
    return;
}

// Fetch subject name
$stmt = $pdo->prepare("SELECT name FROM subjects WHERE id = ?");
$stmt->execute([$subject_id]);
$subjectName = $stmt->fetchColumn();

if (!$subjectName) {
    echo '<div class="alert alert-danger m-4 text-center fw-bold shadow-sm">المادة غير موجودة.</div>';
    return;
}

// Fetch Materials (Lessons)
$stmt = $pdo->prepare("SELECT * FROM materials WHERE subject_id = ? ORDER BY created_at DESC");
$stmt->execute([$subject_id]);
$materials = $stmt->fetchAll();

// Fetch Exams
$stmt = $pdo->prepare("SELECT * FROM exams WHERE subject_id = ? ORDER BY created_at DESC");
$stmt->execute([$subject_id]);
$exams = $stmt->fetchAll();

// Fetch Homework with submission status
$user_id = $_SESSION['user_id'] ?? 0;
$stmt = $pdo->prepare("
    SELECT h.*, hs.status, hs.score 
    FROM homework h 
    LEFT JOIN homework_submissions hs ON h.id = hs.homework_id AND hs.user_id = ?
    WHERE h.subject_id = ? 
    ORDER BY h.created_at DESC
");
$stmt->execute([$user_id, $subject_id]);
$homeworks = $stmt->fetchAll();
?>

<div class="animate-fade-in mb-4">
    <!-- Header -->
    <div class="d-flex align-items-center mb-4 gap-3">
        <button onclick="switchTab('subjects')" class="btn btn-light text-secondary rounded-circle shadow-sm border d-flex align-items-center justify-content-center hover-primary transition" style="width:40px; height:40px;">
            <i class="fa-solid fa-arrow-right"></i>
        </button>
        <div>
            <h2 class="fs-4 fw-black text-primary m-0 d-flex align-items-center gap-2">
                <i class="fa-solid fa-book-open text-warning"></i>
                <?= htmlspecialchars($subjectName) ?>
            </h2>
        </div>
    </div>

    <!-- Custom Tabs (Brand Colors Only) -->
    <style>
        .custom-nav-pills {
            background-color: #fff;
            border-radius: 50px;
            padding: 5px;
            box-shadow: 0 4px 15px rgba(123, 31, 63, 0.05);
            border: 1px solid rgba(123, 31, 63, 0.1);
            display: flex;
            gap: 5px;
            flex-wrap: nowrap;
            overflow-x: auto;
        }
        .custom-nav-pills .nav-link {
            border-radius: 50px;
            color: var(--brand-primary);
            font-weight: 800;
            padding: 10px 24px;
            transition: all 0.3s ease;
            white-space: nowrap;
            border: none;
            background: transparent;
        }
        .custom-nav-pills .nav-link.active {
            background-color: var(--brand-primary);
            color: #fff;
            box-shadow: 0 4px 10px rgba(123, 31, 63, 0.3);
        }
        .custom-nav-pills .nav-link:hover:not(.active) {
            background-color: rgba(123, 31, 63, 0.05);
        }
        /* Custom scrollbar for mobile */
        .custom-nav-pills::-webkit-scrollbar {
            height: 0px;
        }
        
        .brand-icon-box {
            background-color: rgba(123, 31, 63, 0.05);
            color: var(--brand-primary);
        }
    </style>

    <ul class="nav custom-nav-pills mb-4" id="subjectTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="lessons-tab" data-bs-toggle="tab" data-bs-target="#lessons-pane" type="button" role="tab">
                الدروس والملفات (<?= count($materials) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="exams-tab" data-bs-toggle="tab" data-bs-target="#exams-pane" type="button" role="tab">
                الاختبارات (<?= count($exams) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="homework-tab" data-bs-toggle="tab" data-bs-target="#homework-pane" type="button" role="tab">
                الواجبات والتسميع (<?= count($homeworks) ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content" id="subjectTabsContent">
        <!-- 1. Lessons Tab -->
        <div class="tab-pane fade show active" id="lessons-pane" role="tabpanel" tabindex="0">
            <?php if (empty($materials)): ?>
                <div class="p-5 rounded-4 bg-white border border-opacity-10 text-center shadow-sm">
                    <i class="fa-solid fa-folder-open fs-1 text-muted opacity-25 mb-3"></i>
                    <h5 class="fw-bold text-secondary">لا يوجد محتوى حالياً</h5>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($materials as $m): 
                        $icon = 'fa-file-alt';
                        if ($m['type'] === 'video') $icon = 'fa-play';
                        if ($m['type'] === 'pdf') $icon = 'fa-file-pdf';
                        if ($m['type'] === 'image') $icon = 'fa-image';
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card border-0 rounded-4 shadow-sm h-100 overflow-hidden hover-shadow transition">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center brand-icon-box" style="width: 50px; height: 50px; min-width: 50px;">
                                        <i class="fa-solid <?= $icon ?> fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="fw-bold fs-6 text-dark m-0 mb-1 lh-base"><?= htmlspecialchars($m['title']) ?></h5>
                                        <span class="small fw-bold" style="color: var(--brand-gold);">
                                            <?= date('Y/m/d', strtotime($m['created_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mt-auto pt-3 border-top" style="border-color: rgba(123, 31, 63, 0.1) !important;">
                                    <?php if ($m['type'] === 'video'): ?>
                                        <button onclick="switchTab('player&id=<?= $m['id'] ?>')" class="btn btn-sm w-100 fw-bold rounded-pill shadow-sm text-white" style="background-color: var(--brand-primary);">
                                            <i class="fa-solid fa-play me-2"></i> مشاهدة الفيديو
                                        </button>
                                    <?php else: ?>
                                        <button onclick="switchTab('player&id=<?= $m['id'] ?>')" class="btn btn-sm w-100 fw-bold rounded-pill" style="border: 1px solid var(--brand-primary); color: var(--brand-primary);">
                                            <i class="fa-solid fa-shield-halved me-2"></i> عرض المحتوى المحمي
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 2. Exams Tab -->
        <div class="tab-pane fade" id="exams-pane" role="tabpanel" tabindex="0">
            <?php if (empty($exams)): ?>
                <div class="p-5 rounded-4 bg-white border border-opacity-10 text-center shadow-sm">
                    <i class="fa-solid fa-clipboard-question fs-1 text-muted opacity-25 mb-3"></i>
                    <h5 class="fw-bold text-secondary">لا توجد اختبارات</h5>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($exams as $exam): ?>
                    <div class="col-12 col-md-6">
                        <div class="p-3 bg-white border rounded-3 shadow-sm d-flex justify-content-between align-items-center hover-shadow transition" style="border-color: rgba(123, 31, 63, 0.1) !important;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle brand-icon-box d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fa-solid fa-spell-check"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold m-0 text-dark"><?= htmlspecialchars($exam['title']) ?></h6>
                                    <small class="fw-bold" style="color: var(--brand-gold);">أضف بتاريخ: <?= date('Y/m/d', strtotime($exam['created_at'])) ?></small>
                                </div>
                            </div>
                            <button onclick="switchTab('exam_take&id=<?= $exam['id'] ?>')" class="btn btn-sm text-white fw-bold px-3 rounded-pill shadow-sm" style="background-color: var(--brand-primary);">بدء الاختبار</button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 3. Homeworks Tab -->
        <div class="tab-pane fade" id="homework-pane" role="tabpanel" tabindex="0">
            <?php if (empty($homeworks)): ?>
                <div class="p-5 rounded-4 bg-white border border-opacity-10 text-center shadow-sm">
                    <i class="fa-solid fa-clipboard-check fs-1 text-muted opacity-25 mb-3"></i>
                    <h5 class="fw-bold text-secondary">لا توجد واجبات</h5>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($homeworks as $hw): ?>
                    <div class="col-12 col-md-6">
                        <div class="p-3 bg-white border rounded-3 shadow-sm d-flex justify-content-between align-items-center hover-shadow transition cursor-pointer" style="border-color: rgba(123, 31, 63, 0.1) !important;">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle brand-icon-box d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fa-solid fa-pen"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold m-0 text-dark"><?= htmlspecialchars($hw['title']) ?></h6>
                                    <small class="fw-bold" style="color: var(--brand-gold);">النوع: <?= htmlspecialchars($hw['type']) ?></small>
                                </div>
                            </div>
                            <?php if ($hw['status'] === 'graded'): ?>
                                <button onclick="switchTab('homework_submit&id=<?= $hw['id'] ?>')" class="btn btn-sm btn-success fw-bold px-3 rounded-pill shadow-sm"><i class="fa-solid fa-check-double me-1"></i> تم التقييم: <?= $hw['score'] ?>/15</button>
                            <?php elseif ($hw['status'] === 'pending'): ?>
                                <button onclick="switchTab('homework_submit&id=<?= $hw['id'] ?>')" class="btn btn-sm btn-warning text-dark fw-bold px-3 rounded-pill shadow-sm"><i class="fa-solid fa-hourglass-half me-1"></i> قيد المراجعة</button>
                            <?php else: ?>
                                <button onclick="switchTab('homework_submit&id=<?= $hw['id'] ?>')" class="btn btn-sm fw-bold px-3 rounded-pill" style="border: 1px solid var(--brand-primary); color: var(--brand-primary);"><i class="fa-solid fa-paper-plane me-1"></i> تسليم الواجب</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Manually handle tab switching since HTML is dynamically injected
document.querySelectorAll('#subjectTabs .nav-link').forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs and panes
        document.querySelectorAll('#subjectTabs .nav-link').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(p => {
            p.classList.remove('show', 'active');
        });
        
        // Add active class to clicked tab
        this.classList.add('active');
        
        // Add active class to target pane
        const targetId = this.getAttribute('data-bs-target');
        const targetPane = document.querySelector(targetId);
        if (targetPane) {
            targetPane.classList.add('show', 'active');
        }
    });
});
</script>

</div>
