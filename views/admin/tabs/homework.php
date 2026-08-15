<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../../api/db.php';
}

// Fetch all stages for filters
$stmt_stages = $pdo->query("SELECT id, name FROM stages ORDER BY id ASC");
$stages = $stmt_stages->fetchAll(PDO::FETCH_ASSOC);

// Fetch all subjects for filters
$stmt_subjects = $pdo->query("SELECT id, name, stage_id FROM subjects ORDER BY name ASC");
$subjects = $stmt_subjects->fetchAll(PDO::FETCH_ASSOC);

// Fetch all submissions (Homework + Recitations)
$sql = "
    SELECT hs.id, hs.user_id, hs.student_answer, hs.score, hs.status, hs.created_at, hs.teacher_comment,
           h.title as homework_title, h.type as homework_type, 
           u.child_name, u.level, s.name as subject_name, s.id as subject_id, st.name as stage_name, st.id as stage_id, 
           NULL as audio_path, 'homework' as source_table
    FROM homework_submissions hs
    JOIN homework h ON hs.homework_id = h.id
    JOIN users u ON hs.user_id = u.id
    JOIN subjects s ON h.subject_id = s.id
    JOIN stages st ON u.stage_id = st.id
    
    UNION ALL
    
    SELECT r.id, r.user_id, r.notes as student_answer, r.stars as score, r.status, r.created_at, r.teacher_comment,
           m.title as homework_title, m.type as homework_type, 
           u.child_name, u.level, s.name as subject_name, s.id as subject_id, st.name as stage_name, st.id as stage_id, 
           r.audio_path, 'recitation' as source_table
    FROM recitations r
    JOIN materials m ON r.material_id = m.id
    JOIN users u ON r.user_id = u.id
    JOIN subjects s ON m.subject_id = s.id
    JOIN stages st ON u.stage_id = st.id
    
    ORDER BY 
        CASE WHEN status = 'pending' THEN 0 ELSE 1 END ASC,
        created_at DESC
";
$stmt = $pdo->query($sql);
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate pending
$pending_count = 0;
foreach ($submissions as $sub) {
    if ($sub['status'] === 'pending') $pending_count++;
}
?>

<div class="animate-fade-in mb-4">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fs-4 fw-black text-primary m-0">متابعة الواجبات والتسميع</h2>
            <p class="small fw-bold text-secondary mt-1 m-0">قم بمراجعة وتقييم إجابات الطلاب</p>
        </div>
    </div>

    <!-- Alert for Pending -->
    <?php if ($pending_count > 0): ?>
    <div class="rounded-4 p-3 mb-4 d-flex align-items-center justify-content-between border border-warning border-opacity-50 shadow-sm" style="background-color: rgba(255,193,7,0.05);">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle bg-warning bg-opacity-25 text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width: 45px; height: 45px; font-size: 1.2rem;">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div>
                <h4 class="fw-black text-dark m-0 fs-6">تنبيه تقييم</h4>
                <p class="small fw-bold text-warning m-0 mt-1">يوجد <?= $pending_count ?> واجبات بانتظار التقييم.</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filters Row -->
    <div class="row g-3 mb-4">
        
        <!-- Stage Filter (Custom Dropdown) -->
        <div class="col-12 col-md-4">
            <div class="dropdown w-100">
                <button class="btn btn-light w-100 rounded-pill text-secondary fw-bold d-flex justify-content-between align-items-center px-4 py-2 border-0 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #f8f9fa;">
                    <span><i class="fa-solid fa-layer-group text-primary me-2"></i> <span id="selectedStageText">كل المستويات</span></span>
                    <i class="fa-solid fa-chevron-down small"></i>
                </button>
                <ul class="dropdown-menu w-100 border-0 shadow-lg rounded-4 py-2" id="stageDropdownMenu" style="max-height: 250px; overflow-y: auto;">
                    <li><a class="dropdown-item fw-bold py-2 px-4 stage-item active bg-light text-primary" href="#" onclick="selectStage('all', 'كل المستويات', event)">كل المستويات</a></li>
                    <?php foreach ($stages as $stage): ?>
                        <li><a class="dropdown-item fw-bold py-2 px-4 stage-item text-secondary transition" href="#" onclick="selectStage(<?= $stage['id'] ?>, '<?= htmlspecialchars(addslashes($stage['name'])) ?>', event)"><?= htmlspecialchars($stage['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- Subject Filter (Custom Dropdown) -->
        <div class="col-12 col-md-4" id="subjectFilterCol">
            <div class="dropdown w-100">
                <button class="btn btn-light w-100 rounded-pill text-secondary fw-bold d-flex justify-content-between align-items-center px-4 py-2 border-0 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #f8f9fa;">
                    <span><i class="fa-solid fa-book text-gold me-2" style="color: var(--brand-gold);"></i> <span id="selectedSubjectText">كل المواد</span></span>
                    <i class="fa-solid fa-chevron-down small"></i>
                </button>
                <ul class="dropdown-menu w-100 border-0 shadow-lg rounded-4 py-2" id="subjectDropdownMenu" style="max-height: 250px; overflow-y: auto;">
                    <li><a class="dropdown-item fw-bold py-2 px-4 subject-item active bg-light" style="color: var(--brand-gold);" href="#" onclick="selectSubject('all', 'كل المواد', event)">كل المواد</a></li>
                    <?php foreach ($subjects as $subject): ?>
                        <li><a class="dropdown-item fw-bold py-2 px-4 subject-item text-secondary transition" data-stage="<?= $subject['stage_id'] ?>" href="#" onclick="selectSubject(<?= $subject['id'] ?>, '<?= htmlspecialchars(addslashes($subject['name'])) ?>', event)"><?= htmlspecialchars($subject['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
    </div>

    <!-- Status Filters (Pills) -->
    <div class="d-flex align-items-center gap-2 pb-2 mb-4 hide-scrollbar filter-container" id="statusFilters" style="overflow-x: auto;">
        <span class="small fw-bold text-secondary me-2 text-nowrap"><i class="fa-solid fa-filter"></i> الحالة:</span>
        <button onclick="filterByStatus('all')" class="btn filter-btn status-btn active rounded-pill fw-bold px-3 py-1 shadow-sm" data-status="all" style="white-space: nowrap; font-size: 0.85rem; border: 2px solid #6c757d; color: #6c757d; background-color: transparent;">
            الكل
        </button>
        <button onclick="filterByStatus('pending')" class="btn filter-btn status-btn rounded-pill fw-bold px-3 py-1 shadow-sm" data-status="pending" style="white-space: nowrap; font-size: 0.85rem; border: 2px solid rgba(108,117,125,0.3); color: #6c757d; background-color: transparent;">
            بانتظار التقييم
        </button>
        <button onclick="filterByStatus('graded')" class="btn filter-btn status-btn rounded-pill fw-bold px-3 py-1 shadow-sm" data-status="graded" style="white-space: nowrap; font-size: 0.85rem; border: 2px solid rgba(108,117,125,0.3); color: #6c757d; background-color: transparent;">
            تم التقييم
        </button>
    </div>

    <!-- Submissions Grid -->
    <div class="row g-4" id="submissionsGrid">
        <?php if (empty($submissions)): ?>
            <div class="col-12">
                <div class="bg-white p-5 rounded-4 border shadow-sm text-center">
                    <div class="rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <h3 class="fw-black text-dark fs-5 mb-1">لا يوجد تسليمات</h3>
                    <p class="small fw-bold text-secondary m-0">لم يقم أي طالب بتسليم واجبات حتى الآن.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($submissions as $sub): 
                $isPending = ($sub['status'] === 'pending');
            ?>
            <div class="col-md-6 col-lg-4 submission-card" data-stage="<?= $sub['stage_id'] ?>" data-subject="<?= $sub['subject_id'] ?>">
                <div class="bg-white rounded-4 border p-4 shadow-sm h-100 d-flex flex-column position-relative overflow-hidden transition" style="border-color: <?= $isPending ? 'rgba(255,193,7,0.3)' : 'rgba(123,31,63,0.1)' ?> !important;">
                    
                    <?php 
                        $isAudio = (!empty($sub['audio_path']) || (isset($sub['student_answer']) && in_array(strtolower(pathinfo($sub['student_answer'], PATHINFO_EXTENSION)), ['webm', 'wav', 'mp3', 'ogg'])));
                    ?>
                    
                    <?php if ($isPending): ?>
                        <div class="position-absolute top-0 end-0 bottom-0 opacity-75" style="width: 4px; background-color: #ffc107;"></div>
                    <?php else: ?>
                        <div class="position-absolute top-0 end-0 bottom-0 opacity-75" style="width: 4px; background-color: var(--brand-gold);"></div>
                    <?php endif; ?>
                    
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-start mb-3 pe-2">
                        <div>
                            <span class="badge rounded-2 border mb-2 px-2 py-1" style="font-size: 0.65rem; color: var(--brand-primary); background-color: rgba(123,31,63,0.1); border-color: rgba(123,31,63,0.25) !important;">
                                <?= htmlspecialchars($sub['stage_name']) ?> - <?= htmlspecialchars($sub['subject_name']) ?>
                            </span>
                            <h3 class="fw-black text-dark m-0 fs-6 lh-sm mb-2"><?= htmlspecialchars($sub['homework_title']) ?></h3>
                            <?php if($isAudio): ?>
                                <span class="badge px-2 py-1" style="font-size: 0.7rem; color: #dc3545; background-color: rgba(220,53,69,0.1); border: 1px solid rgba(220,53,69,0.25);"><i class="fa-solid fa-microphone me-1"></i> تسميع صوتي</span>
                            <?php else: ?>
                                <span class="badge px-2 py-1" style="font-size: 0.7rem; color: #6c757d; background-color: rgba(108,117,125,0.1); border: 1px solid rgba(108,117,125,0.25);"><i class="fa-solid fa-file-alt me-1"></i> واجب كتابي / ملف</span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <?php if ($isPending): ?>
                                <span class="badge text-dark px-2 py-1" style="background-color: #ffc107;"><i class="fa-solid fa-hourglass-half me-1"></i> بانتظار التقييم</span>
                            <?php else: ?>
                                <span class="badge px-2 py-1" style="background-color: var(--brand-gold); color: white;"><i class="fa-solid fa-check me-1"></i> تم التقييم (<?= $sub['score'] ?>/15)</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Student Info -->
                    <div class="d-flex align-items-center gap-2 mb-3 bg-light rounded-3 p-2">
                        <div class="rounded-circle bg-secondary bg-opacity-10 d-flex align-items-center justify-content-center text-primary" style="width:35px; height:35px;">
                            <i class="fa-solid fa-user-graduate"></i>
                        </div>
                        <div>
                            <p class="m-0 fw-bold text-dark fs-6"><?= htmlspecialchars($sub['child_name']) ?></p>
                            <p class="m-0 text-muted small fw-bold">المستوى <?= htmlspecialchars($sub['level']) ?></p>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="d-flex align-items-center text-secondary small fw-bold mb-3 mt-auto">
                        <i class="fa-regular fa-clock me-1"></i> 
                        <?php 
                            $date = new DateTime($sub['created_at']);
                            echo $date->format('Y-m-d h:i A');
                        ?>
                    </div>
                    
                    <!-- Action -->
                    <?php if ($isPending): ?>
                        <button onclick='openGradeModal(<?= json_encode($sub, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="btn w-100 fw-bold rounded-pill text-dark shadow-sm" style="background-color: #ffc107; border-color: #ffc107;">
                            <i class="fa-solid fa-pen-to-square me-1"></i> تقييم الآن
                        </button>
                    <?php else: ?>
                        <button onclick='openGradeModal(<?= json_encode($sub, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="btn w-100 fw-bold rounded-pill shadow-sm" style="color: var(--brand-gold); border: 1px solid var(--brand-gold); background-color: transparent;">
                            <i class="fa-solid fa-eye me-1"></i> عرض وتعديل التقييم
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.filter-btn { background: transparent; }
.filter-btn.active { background-color: var(--brand-primary) !important; color: white !important; border-color: var(--brand-primary) !important; }
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
let currentStage = 'all';
let currentSubject = 'all';
let currentStatus = 'all';

function selectStage(stageId, stageName, event) {
    if (event) event.preventDefault();
    
    currentStage = stageId;
    currentSubject = 'all'; // reset subject filter
    
    // Update button text
    document.getElementById('selectedStageText').innerText = stageName;
    document.getElementById('selectedSubjectText').innerText = 'كل المواد';
    
    // Update active class in dropdown
    document.querySelectorAll('.stage-item').forEach(el => {
        el.classList.remove('active', 'bg-light', 'text-primary');
        el.classList.add('text-secondary');
    });
    if (event) {
        event.currentTarget.classList.add('active', 'bg-light', 'text-primary');
        event.currentTarget.classList.remove('text-secondary');
    }

    // Show/Hide Subject Filter Dropdown
    const subjectCol = document.getElementById('subjectFilterCol');
    subjectCol.style.display = 'block'; // Always visible now
    
    // Show subjects belonging to this stage only (or all if 'all' is selected)
    document.querySelectorAll('.subject-item').forEach(el => {
        // Reset subject dropdown active classes
        el.classList.remove('active', 'bg-light');
        el.style.color = '';
        if(!el.classList.contains('text-secondary')) el.classList.add('text-secondary');
        
        if (el.dataset.stage == undefined) {
            // "All subjects" option
            el.style.display = 'block';
            el.classList.add('active', 'bg-light');
            el.classList.remove('text-secondary');
            el.style.color = 'var(--brand-gold)';
        } else if (stageId === 'all' || el.dataset.stage == stageId) {
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    });

    applyFilters();
}

function selectSubject(subjectId, subjectName, event) {
    if (event) event.preventDefault();
    
    currentSubject = subjectId;
    
    // Update button text
    document.getElementById('selectedSubjectText').innerText = subjectName;
    
    // Update active class in dropdown
    document.querySelectorAll('.subject-item').forEach(el => {
        if (el.style.display === 'none') return;
        el.classList.remove('active', 'bg-light');
        el.style.color = '';
        if(!el.classList.contains('text-secondary')) el.classList.add('text-secondary');
    });
    if (event) {
        event.currentTarget.classList.add('active', 'bg-light');
        event.currentTarget.classList.remove('text-secondary');
        event.currentTarget.style.color = 'var(--brand-gold)';
    }

    applyFilters();
}

function filterByStatus(statusVal) {
    currentStatus = statusVal;
    
    // Update active class on status buttons
    document.querySelectorAll('.status-btn').forEach(btn => {
        if (btn.dataset.status === statusVal) {
            btn.classList.add('active');
            btn.style.backgroundColor = '#6c757d';
            btn.style.color = 'white';
            btn.style.borderColor = '#6c757d';
        } else {
            btn.classList.remove('active');
            btn.style.backgroundColor = 'transparent';
            btn.style.color = '#6c757d';
            btn.style.borderColor = 'rgba(108,117,125,0.3)';
        }
    });

    applyFilters();
}

function applyFilters() {
    const cards = document.querySelectorAll('.submission-card');
    cards.forEach(card => {
        const matchStage = (currentStage === 'all' || card.dataset.stage == currentStage);
        const matchSubject = (currentSubject === 'all' || card.dataset.subject == currentSubject);
        
        let matchStatus = true;
        if (currentStatus === 'pending') {
            matchStatus = card.querySelector('.fa-bell') !== null || card.innerHTML.includes('بانتظار التقييم');
        } else if (currentStatus === 'graded') {
            matchStatus = card.innerHTML.includes('تم التقييم');
        }
        
        if (matchStage && matchSubject && matchStatus) {
            card.style.display = 'block';
            card.classList.add('animate-fade-in');
        } else {
            card.style.display = 'none';
        }
    });
}

function escapeHTML(str) {
    if (!str) return '';
    return String(str).replace(/[&<>'"]/g, function(tag) {
        const charsToReplace = { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' };
        return charsToReplace[tag] || tag;
    });
}

function openGradeModal(sub) {
    let answerHtml = '';
    
    // Check if it's a file upload (homework)
    if (sub.student_answer && sub.student_answer.startsWith('uploads/')) {
        let ext = sub.student_answer.split('.').pop().toLowerCase();
        if (['webm', 'wav', 'mp3', 'ogg'].includes(ext)) {
            answerHtml = `<audio controls class="w-100 mb-3"><source src="/api/${sub.student_answer}">المتصفح لا يدعم الصوت.</audio>`;
        } else {
            answerHtml = `<a href="/api/${sub.student_answer}" target="_blank" class="btn btn-outline-primary mb-3"><i class="fa-solid fa-download"></i> تحميل الملف المرفق</a>`;
        }
    } 
    // Check if it's an audio path (recitation)
    else if (sub.audio_path) {
        answerHtml = `<audio controls class="w-100 mb-3"><source src="/api/${sub.audio_path}">المتصفح لا يدعم الصوت.</audio>`;
        if (sub.student_answer) {
             answerHtml += `<div class="p-3 bg-light rounded text-start mb-3" style="white-space:pre-wrap;">${escapeHTML(sub.student_answer)}</div>`;
        }
    }
    // Normal text answer
    else {
        answerHtml = `<div class="p-3 bg-light rounded text-start mb-3 border" style="white-space:pre-wrap; min-height: 100px;">${sub.student_answer ? escapeHTML(sub.student_answer) : '<span class="text-muted">لم يكتب الطالب نصاً.</span>'}</div>`;
    }

    Swal.fire({
        title: 'تقييم إجابة الطالب',
        html: `
            <div class="text-start">
                <p class="mb-1 fw-bold text-primary">${escapeHTML(sub.child_name)}</p>
                <p class="small text-muted mb-3">${escapeHTML(sub.homework_title)} - ${escapeHTML(sub.subject_name)}</p>
                
                <h6 class="fw-bold"><i class="fa-solid fa-reply text-secondary me-2"></i> إجابة الطالب:</h6>
                ${answerHtml}
                
                <hr>
                <div class="form-group mb-3">
                    <label class="fw-bold mb-2">تعليق المعلم (اختياري، ليراه الطالب)</label>
                    <textarea id="teacherComment" class="form-control" rows="2" placeholder="اكتب تعليقك أو تصحيحك هنا...">${sub.teacher_comment ? escapeHTML(sub.teacher_comment) : ''}</textarea>
                </div>
                <div class="form-group">
                    <label class="fw-bold mb-2">الدرجة (من 15)</label>
                    <input type="number" id="gradeScore" class="form-control form-control-lg text-center fw-bold" min="0" max="15" value="${sub.score || ''}" placeholder="أدخل الدرجة">
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-save me-1"></i> حفظ التقييم',
        cancelButtonText: 'إلغاء',
        preConfirm: () => {
            const score = document.getElementById('gradeScore').value;
            const comment = document.getElementById('teacherComment').value;
            if (score === '' || score < 0 || score > 15) {
                Swal.showValidationMessage('الرجاء إدخال درجة صحيحة من 0 إلى 15');
            }
            return { score: score, comment: comment };
        }
    }).then((result) => {
        if (result.isConfirmed) {
            submitGrade(sub.id, result.value.score, result.value.comment, sub.source_table);
        }
    });
}

function submitGrade(id, score, comment, sourceTable) {
    Swal.fire({
        title: 'جاري الحفظ...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch('/api/homeworks/grade', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            id: id,
            score: score,
            comment: comment,
            source_table: sourceTable
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'تم التقييم بنجاح',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                // Refresh tab to show updated list
                switchTab('homework');
            });
        } else {
            Swal.fire('خطأ', data.error || 'حدث خطأ أثناء الحفظ', 'error');
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire('خطأ', 'تعذر الاتصال بالخادم', 'error');
    });
}
</script>
