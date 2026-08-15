<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../../api/db.php';
}

$user_id = $_SESSION['user_id'] ?? 0;

// Get user stage
$stmtUser = $pdo->prepare("SELECT stage_id FROM users WHERE id = ?");
$stmtUser->execute([$user_id]);
$stage_id = $stmtUser->fetchColumn();

// Fetch latest materials
$stmtMaterials = $pdo->prepare("
    SELECT m.id, m.title, m.type, m.created_at, m.subject_id, s.name as subject_name, 'material' as source
    FROM materials m
    JOIN subjects s ON m.subject_id = s.id
    WHERE s.stage_id = ? OR s.id IN (SELECT subject_id FROM user_subjects WHERE user_id = ?)
    ORDER BY m.created_at DESC LIMIT 15
");
$stmtMaterials->execute([$stage_id, $user_id]);
$materials = $stmtMaterials->fetchAll(PDO::FETCH_ASSOC);

// Fetch latest homework
$stmtHomework = $pdo->prepare("
    SELECT h.id, h.title, h.type, h.created_at, h.subject_id, s.name as subject_name, 'homework' as source
    FROM homework h
    JOIN subjects s ON h.subject_id = s.id
    WHERE s.stage_id = ? OR s.id IN (SELECT subject_id FROM user_subjects WHERE user_id = ?)
    ORDER BY h.created_at DESC LIMIT 15
");
$stmtHomework->execute([$stage_id, $user_id]);
$homeworks = $stmtHomework->fetchAll(PDO::FETCH_ASSOC);

// Merge and sort descending by date
$items = array_merge($materials, $homeworks);
usort($items, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$items = array_slice($items, 0, 15);
?>

<div class="animate-fade-in mb-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fs-4 fw-black text-primary m-0"><i class="fa-solid fa-list-check text-warning me-2"></i>خطة الأسبوع</h2>
            <p class="small fw-bold text-secondary mt-1 m-0">أحدث الدروس والواجبات المضافة هذا الأسبوع</p>
        </div>
    </div>

    <?php if (empty($items)): ?>
        <div class="bg-white rounded-4 border shadow-sm p-5 text-center">
            <div class="rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                <i class="fa-solid fa-mug-hot"></i>
            </div>
            <h3 class="fw-black text-dark fs-5 mb-1">الخطة فارغة</h3>
            <p class="small fw-bold text-secondary m-0">لم يتم إضافة أي دروس أو واجبات جديدة مؤخراً.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($items as $item): 
                // Determine icon, colors, and links based on source and type
                $icon = 'fa-file';
                $colorStyle = 'color: var(--brand-primary);';
                $bgStyle = 'background-color: rgba(123,31,63,0.1);';
                $btnStyle = 'color: var(--brand-primary); background-color: rgba(123,31,63,0.1); border: none;';
                $link = '';
                $actionText = '';
                
                if ($item['source'] === 'material') {
                    if ($item['type'] === 'video') {
                        $icon = 'fa-play';
                        $colorStyle = 'color: #dc3545;';
                        $bgStyle = 'background-color: rgba(220,53,69,0.1);';
                        $btnStyle = 'color: #dc3545; background-color: rgba(220,53,69,0.1); border: none;';
                        $link = "switchTab('player&id={$item['id']}')";
                        $actionText = 'مشاهدة الفيديو';
                    } else if ($item['type'] === 'pdf') {
                        $icon = 'fa-file-pdf';
                        $colorStyle = 'color: #0dcaf0;';
                        $bgStyle = 'background-color: rgba(13,202,240,0.1);';
                        $btnStyle = 'color: #0dcaf0; background-color: rgba(13,202,240,0.1); border: none;';
                        $link = "switchTab('player&id={$item['id']}')";
                        $actionText = 'تصفح الملف';
                    } else {
                        $icon = 'fa-image';
                        $colorStyle = 'color: var(--brand-primary);';
                        $bgStyle = 'background-color: rgba(123,31,63,0.1);';
                        $btnStyle = 'color: var(--brand-primary); background-color: rgba(123,31,63,0.1); border: none;';
                        $link = "switchTab('player&id={$item['id']}')";
                        $actionText = 'عرض المحتوى';
                    }
                } else if ($item['source'] === 'homework') {
                    if ($item['type'] === 'recitation') {
                        $icon = 'fa-microphone';
                        $colorStyle = 'color: #198754;';
                        $bgStyle = 'background-color: rgba(25,135,84,0.1);';
                        $btnStyle = 'color: #198754; background-color: rgba(25,135,84,0.1); border: none;';
                        $actionText = 'تسليم التسميع';
                    } else {
                        $icon = 'fa-pen';
                        $colorStyle = 'color: #198754;';
                        $bgStyle = 'background-color: rgba(25,135,84,0.1);';
                        $btnStyle = 'color: #198754; background-color: rgba(25,135,84,0.1); border: none;';
                        $actionText = 'حل الواجب';
                    }
                    $link = "switchTab('homework_submit&id={$item['id']}')";
                }
                
                $dateObj = new DateTime($item['created_at']);
                $dateStr = $dateObj->format('Y/m/d h:i A');
            ?>
            <div class="col-12 col-md-6 col-lg-4">
                <div class="bg-white rounded-4 border p-3 shadow-sm h-100 d-flex flex-column hover-shadow transition" style="border-color: rgba(123,31,63,0.1) !important;">
                    <div class="d-flex align-items-start gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 45px; height: 45px; <?= $bgStyle ?> <?= $colorStyle ?>">
                            <i class="fa-solid <?= $icon ?> fs-5"></i>
                        </div>
                        <div>
                            <span class="badge rounded-2 bg-light text-secondary border mb-2 px-2 py-1" style="font-size: 0.65rem;">
                                <?= htmlspecialchars($item['subject_name']) ?>
                            </span>
                            <h3 class="fw-bold text-dark m-0 fs-6 lh-sm" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars($item['title']) ?>
                            </h3>
                        </div>
                    </div>
                    
                    <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between" style="border-color: rgba(0,0,0,0.05) !important;">
                        <small class="text-muted fw-bold" style="font-size: 0.7rem;">
                            <i class="fa-regular fa-clock me-1"></i> <?= $dateStr ?>
                        </small>
                        <button onclick="<?= $link ?>" class="btn btn-sm fw-bold px-3 rounded-pill hover-bg-light transition" style="<?= $btnStyle ?>">
                            <?= $actionText ?> <i class="fa-solid fa-arrow-left ms-1 small"></i>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
