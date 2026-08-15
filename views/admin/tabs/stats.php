<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../../api/db.php';
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<div class='alert alert-danger fw-bold m-4 text-center'>غير مصرح لك بالوصول.</div>";
    exit;
}

// Fetch stats directly
$stats = [
    'total_students' => 0,
    'total_materials' => 0,
    'total_homework_submissions' => 0,
    'homework_pending' => 0,
    'homework_graded' => 0,
    'average_rating' => 0
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
    $stats['total_students'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM materials");
    $stats['total_materials'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM homework_submissions");
    $stats['total_homework_submissions'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM homework_submissions WHERE status = 'pending'");
    $stats['homework_pending'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT COUNT(*) FROM homework_submissions WHERE status = 'graded'");
    $stats['homework_graded'] = $stmt->fetchColumn();

    $stmt = $pdo->query("SELECT AVG(score) FROM homework_submissions WHERE status = 'graded'");
    $avg = $stmt->fetchColumn();
    $stats['average_rating'] = $avg ? round($avg, 1) : 0;

    $stmtPlans = $pdo->query("SELECT plan, COUNT(*) as cnt FROM users WHERE role = 'student' GROUP BY plan");
    $planCounts = ['بذرة' => 0, 'نبتة' => 0, 'شجرة' => 0];
    while ($pRow = $stmtPlans->fetch(PDO::FETCH_ASSOC)) {
        $pName = $pRow['plan'] ?: 'بذرة';
        $planCounts[$pName] = (int)$pRow['cnt'];
    }
    $stats['plans'] = $planCounts;
} catch (PDOException $e) {
    echo "<div class='alert alert-danger fw-bold m-4 text-center shadow-sm'>حدث خطأ أثناء جلب الإحصائيات. يرجى مراجعة سجل النظام.</div>";
    exit;
}
?>

<div class="d-flex flex-column gap-5 animate-fade-in pb-5">
    
    <!-- Header Section -->
    <div class="d-flex align-items-center justify-content-between mb-2">
        <div>
            <h1 class="fs-3 fw-black m-0 d-flex align-items-center gap-3" style="color: var(--brand-primary-dark); letter-spacing: -0.5px;">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 45px; height: 45px; background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark));">
                    <i class="fa-solid fa-chart-pie"></i>
                </div>
                مركز الإحصائيات
            </h1>
            <p class="text-secondary fw-bold small m-0 mt-2 ms-5 ps-3 border-end border-3 border-warning opacity-75">نظرة شاملة على أداء المنصة</p>
        </div>
    </div>

    <!-- Main Stats Grid (Glassmorphism & Depth) -->
    <div class="row g-4">
        
        <!-- Students -->
        <div class="col-12 col-md-6 col-lg-3">
            <div class="stat-card p-4 h-100 d-flex flex-column position-relative overflow-hidden group cursor-pointer" onclick="document.querySelector('[data-tab=students]').click()">
                <i class="fa-solid fa-users position-absolute opacity-10 transition group-hover-scale" style="font-size: 8rem; color: var(--brand-primary); bottom: -1.5rem; left: -1.5rem; z-index: 0;"></i>
                
                <div class="d-flex justify-content-between align-items-start position-relative z-1 mb-4">
                    <div class="icon-box-small rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px; background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark));">
                        <i class="fa-solid fa-users" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                </div>
                
                <div class="position-relative z-1 mt-auto">
                    <div class="fs-1 fw-black text-dark mb-1 lh-1 stat-number"><?= $stats['total_students'] ?></div>
                    <div class="fw-bold text-secondary" style="font-size: 0.9rem;">إجمالي الطلاب</div>
                </div>
            </div>
        </div>

        <!-- Materials -->
        <div class="col-12 col-md-6 col-lg-3">
            <div class="stat-card p-4 h-100 d-flex flex-column position-relative overflow-hidden group cursor-pointer" onclick="document.querySelector('[data-tab=subjects]').click()">
                <i class="fa-solid fa-book-open position-absolute opacity-10 transition group-hover-scale" style="font-size: 8rem; color: var(--brand-gold); bottom: -1.5rem; left: -1.5rem; z-index: 0;"></i>
                
                <div class="d-flex justify-content-between align-items-start position-relative z-1 mb-4">
                    <div class="icon-box-small rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px; background: linear-gradient(135deg, #D4AF37, #B5952F);">
                        <i class="fa-solid fa-book" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                </div>
                
                <div class="position-relative z-1 mt-auto">
                    <div class="fs-1 fw-black text-dark mb-1 lh-1 stat-number"><?= $stats['total_materials'] ?></div>
                    <div class="fw-bold text-secondary" style="font-size: 0.9rem;">مواد تعليمية</div>
                </div>
            </div>
        </div>

        <!-- Submissions -->
        <div class="col-12 col-md-6 col-lg-3">
            <div class="stat-card p-4 h-100 d-flex flex-column position-relative overflow-hidden group cursor-pointer" onclick="document.querySelector('[data-tab=homework]').click()">
                <i class="fa-solid fa-file-signature position-absolute opacity-10 transition group-hover-scale" style="font-size: 8rem; color: #5C1328; bottom: -1.5rem; left: -1.5rem; z-index: 0;"></i>
                
                <div class="d-flex justify-content-between align-items-start position-relative z-1 mb-4">
                    <div class="icon-box-small rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px; background: linear-gradient(135deg, #7B1F3F, #5C1328);">
                        <i class="fa-solid fa-file-alt" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                </div>
                
                <div class="position-relative z-1 mt-auto">
                    <div class="fs-1 fw-black text-dark mb-1 lh-1 stat-number"><?= $stats['total_homework_submissions'] ?></div>
                    <div class="fw-bold text-secondary" style="font-size: 0.9rem;">الواجبات المسلمة</div>
                </div>
            </div>
        </div>

        <!-- Rating -->
        <div class="col-12 col-md-6 col-lg-3">
            <div class="stat-card p-4 h-100 d-flex flex-column position-relative overflow-hidden group cursor-pointer" onclick="document.querySelector('[data-tab=homework]').click()">
                <i class="fa-solid fa-star position-absolute opacity-10 transition group-hover-scale" style="font-size: 8rem; color: #9B7060; bottom: -1.5rem; left: -1.5rem; z-index: 0;"></i>
                
                <div class="d-flex justify-content-between align-items-start position-relative z-1 mb-4">
                    <div class="icon-box-small rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px; background: linear-gradient(135deg, #B58674, #9B7060);">
                        <i class="fa-solid fa-star" style="color: white; font-size: 1.25rem;"></i>
                    </div>
                </div>
                
                <div class="position-relative z-1 mt-auto">
                    <div class="fs-1 fw-black text-dark mb-1 lh-1 stat-number">
                        <?= $stats['average_rating'] ?> 
                        <span class="fs-4 text-secondary opacity-50 fw-bold">/ 15</span>
                    </div>
                    <div class="fw-bold text-secondary" style="font-size: 0.9rem;">متوسط الدرجات</div>
                </div>
            </div>
        </div>

    </div>

    <!-- Extra Actionable Stats (Pending / Graded) -->
    <div class="row g-4 mt-2">
        <div class="col-12 col-md-6">
            <div class="action-card p-4 h-100 d-flex align-items-center justify-content-between position-relative overflow-hidden cursor-pointer group" onclick="document.querySelector('[data-tab=homework]').click()">
                <div class="position-absolute bg-warning opacity-10 rounded-circle transition group-hover-scale" style="width: 200px; height: 200px; right: -50px; top: -50px;"></div>
                <div class="position-relative z-1">
                    <div class="small fw-bold mb-2 text-uppercase" style="color: #9B7060; letter-spacing: 1px;">تتطلب انتباهك</div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="fs-1 fw-black text-dark"><?= $stats['homework_pending'] ?></div>
                        <div class="fw-bold text-secondary">واجب بانتظار التقييم</div>
                    </div>
                </div>
                <div class="position-relative z-1 rounded-circle d-flex align-items-center justify-content-center text-warning bg-white shadow-sm transition group-hover-bg-warning group-hover-text-white" style="width: 70px; height: 70px; font-size: 1.8rem; border: 2px solid rgba(212,175,55,0.2);">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="action-card p-4 h-100 d-flex align-items-center justify-content-between position-relative overflow-hidden cursor-pointer group" onclick="document.querySelector('[data-tab=homework]').click()">
                <div class="position-absolute bg-primary opacity-10 rounded-circle transition group-hover-scale" style="width: 200px; height: 200px; right: -50px; top: -50px;"></div>
                <div class="position-relative z-1">
                    <div class="small fw-bold mb-2 text-uppercase" style="color: #9B7060; letter-spacing: 1px;">إنجاز ممتاز</div>
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="fs-1 fw-black text-dark"><?= $stats['homework_graded'] ?></div>
                        <div class="fw-bold text-secondary">واجب تم تقييمه</div>
                    </div>
                </div>
                <div class="position-relative z-1 rounded-circle d-flex align-items-center justify-content-center text-primary bg-white shadow-sm transition group-hover-bg-primary group-hover-text-white" style="width: 70px; height: 70px; font-size: 1.8rem; border: 2px solid rgba(107, 30, 45, 0.2);">
                    <i class="fa-solid fa-check-double"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscription Plans Breakdown Section -->
    <div class="mt-4">
        <h5 class="fw-black text-dark mb-3 d-flex align-items-center gap-2">
            <i class="fa-solid fa-crown text-warning"></i> توزيع الطلاب على خطط الاشتراك
        </h5>
        <div class="row g-4">
            <!-- Seed Plan -->
            <div class="col-12 col-md-4">
                <div class="stat-card p-4 h-100 d-flex align-items-center justify-content-between cursor-pointer group" onclick="document.querySelector('[data-tab=students]').click()">
                    <div>
                        <div class="badge rounded-pill px-3 py-1 mb-2 fw-bold" style="background-color: rgba(155, 112, 96, 0.15); color: #9B7060;">🌱 خطة بذرة</div>
                        <div class="fs-2 fw-black text-dark"><?= $stats['plans']['بذرة'] ?? 0 ?> <span class="fs-6 fw-bold text-secondary">طالب</span></div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 55px; height: 55px; background: rgba(155, 112, 96, 0.1); color: #9B7060; font-size: 1.5rem;">
                        <i class="fa-solid fa-seedling"></i>
                    </div>
                </div>
            </div>

            <!-- Sprout Plan -->
            <div class="col-12 col-md-4">
                <div class="stat-card p-4 h-100 d-flex align-items-center justify-content-between cursor-pointer group" onclick="document.querySelector('[data-tab=students]').click()">
                    <div>
                        <div class="badge rounded-pill px-3 py-1 mb-2 fw-bold" style="background-color: rgba(123, 31, 63, 0.15); color: #7B1F3F;">🌿 خطة نبتة</div>
                        <div class="fs-2 fw-black text-dark"><?= $stats['plans']['نبتة'] ?? 0 ?> <span class="fs-6 fw-bold text-secondary">طالب</span></div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 55px; height: 55px; background: rgba(123, 31, 63, 0.1); color: #7B1F3F; font-size: 1.5rem;">
                        <i class="fa-solid fa-leaf"></i>
                    </div>
                </div>
            </div>

            <!-- Tree Plan -->
            <div class="col-12 col-md-4">
                <div class="stat-card p-4 h-100 d-flex align-items-center justify-content-between cursor-pointer group" onclick="document.querySelector('[data-tab=students]').click()">
                    <div>
                        <div class="badge rounded-pill px-3 py-1 mb-2 fw-bold" style="background-color: rgba(212, 175, 55, 0.15); color: #B5952F;">🌳 خطة شجرة</div>
                        <div class="fs-2 fw-black text-dark"><?= $stats['plans']['شجرة'] ?? 0 ?> <span class="fs-6 fw-bold text-secondary">طالب</span></div>
                    </div>
                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 55px; height: 55px; background: rgba(212, 175, 55, 0.1); color: #B5952F; font-size: 1.5rem;">
                        <i class="fa-solid fa-tree"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Stat Cards */
.stat-card {
    background: rgba(255, 255, 255, 0.6);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 1);
    border-radius: 30px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03), inset 0 2px 5px rgba(255,255,255,0.8);
    transition: all 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.stat-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.08), inset 0 2px 5px rgba(255,255,255,0.8);
}
.stat-glow {
    position: absolute;
    width: 120px; height: 120px;
    top: -40px; right: -40px;
    border-radius: 50%;
    opacity: 0.15;
    filter: blur(30px);
    transition: all 0.4s ease;
    z-index: 0;
}
.stat-card:hover .stat-glow {
    transform: scale(1.5);
    opacity: 0.25;
}

/* Icons */
.icon-box {
    width: 55px; height: 55px;
    border-radius: 18px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
}

/* Action Cards */
.action-card {
    background: #fff;
    border-radius: 30px;
    border: 1px solid rgba(0,0,0,0.03);
    box-shadow: 0 10px 25px rgba(0,0,0,0.02);
    transition: all 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
}

/* Typography Enhancements */
.stat-number {
    font-family: 'Outfit', sans-serif;
    letter-spacing: -2px;
}

/* Group Utilities */
.group:hover .group-hover-bg-primary { background-color: var(--brand-primary) !important; }
.group:hover .group-hover-bg-warning { background-color: var(--brand-gold) !important; }
.group:hover .group-hover-text-white { color: #fff !important; }
.group:hover .group-hover-scale { transform: scale(1.5); }
.transition { transition: all 0.3s ease; }
</style>
