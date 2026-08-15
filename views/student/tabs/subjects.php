<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../../api/db.php';
}

global $user;

$plan = $user['plan'] ?? 'بذرة';
$stage_id = $user['stage_id'] ?? null;

if ($plan === 'شجرة') {
    if ($stage_id) {
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE stage_id = ?");
        $stmt->execute([$stage_id]);
    } else {
        $stmt = $pdo->query("SELECT * FROM subjects");
    }
    $subjects = $stmt->fetchAll();
} elseif ($plan === 'نبتة') {
    if ($stage_id) {
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE is_core = 1 AND stage_id = ? UNION SELECT s.* FROM subjects s JOIN user_subjects us ON s.id = us.subject_id WHERE us.user_id = ?");
        $stmt->execute([$stage_id, $user['id']]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE is_core = 1 UNION SELECT s.* FROM subjects s JOIN user_subjects us ON s.id = us.subject_id WHERE us.user_id = ?");
        $stmt->execute([$user['id']]);
    }
    $subjects = $stmt->fetchAll();
} else {
    // بذرة
    if ($stage_id) {
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE is_core = 1 AND stage_id = ?");
        $stmt->execute([$stage_id]);
    } else {
        $stmt = $pdo->query("SELECT * FROM subjects WHERE is_core = 1");
    }
    $subjects = $stmt->fetchAll();
}
?>

<div class="animate-fade-in mb-4">
    <div class="mb-4">
        <h2 class="fs-4 fw-black text-primary m-0">المواد الدراسية</h2>
        <p class="small fw-bold text-secondary mt-1 m-0">تصفح جميع المواد والدروس المتاحة لك</p>
    </div>

    <div class="row g-4">
        <?php foreach ($subjects as $i => $s): 
            $isPrimary = $i % 2 == 0;
            $bgClass = $isPrimary ? 'bg-primary bg-opacity-10' : 'bg-warning bg-opacity-10';
            $textColor = $isPrimary ? 'text-primary' : 'text-warning';
            $borderColor = $isPrimary ? 'border-primary border-opacity-25' : 'border-warning border-opacity-50';
            $accentColor = $isPrimary ? 'bg-primary' : 'bg-warning';
            $bgStyle = $isPrimary ? 'background-color: rgba(123,31,63,0.02);' : 'background-color: rgba(255,193,7,0.03);';
        ?>
        <div class="col-md-6 col-lg-4">
            <div onclick="switchTab('materials&subject_id=<?= $s['id'] ?>')" class="rounded-4 p-4 cursor-pointer border hover-border-darker transition h-100 d-flex flex-column position-relative overflow-hidden group shadow-sm hover-shadow" style="<?= $bgStyle ?> border-color: rgba(0,0,0,0.02) !important;">
                
                <!-- Colored spine -->
                <div class="position-absolute top-0 end-0 bottom-0 opacity-75 <?= $accentColor ?>" style="width: 6px;"></div>
                
                <div class="flex-grow-1 pe-2">
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <h3 class="fw-black fs-5 text-dark m-0 transition group-hover-text-primary"><?= htmlspecialchars($s['name']) ?></h3>
                        <div class="rounded-3 bg-white d-flex align-items-center justify-content-center shadow-sm border <?= $borderColor ?> <?= $textColor ?> transition group-hover-translate-x-1" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-arrow-left small"></i>
                        </div>
                    </div>
                    
                    <p class="small fw-bold text-secondary mb-4 lh-base" style="font-size: 0.8rem;">
                        <?= htmlspecialchars($s['description'] ?? 'محتوى تعليمي شيق وممتع لتعلم أسس هذه المادة والتفوق فيها.') ?>
                    </p>
                </div>
                
                <div class="mt-auto pe-2 pt-3 border-top d-flex align-items-center justify-content-between" style="border-color: rgba(0,0,0,0.05) !important;">
                    <div class="fw-black <?= $textColor ?> text-uppercase tracking-wider" style="font-size: 0.7rem;">عرض الدروس</div>
                    <div class="d-flex flex-row-reverse" style="margin-left: -5px;">
                        <div class="rounded-circle border border-2 border-white bg-light text-secondary d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.5rem; margin-right: -10px;">
                            <i class="fa-solid fa-file-pdf"></i>
                        </div>
                        <div class="rounded-circle border border-2 border-white bg-light text-secondary d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 0.5rem;">
                            <i class="fa-solid fa-video"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
