<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../../api/db.php';
}

// Fetch all stages
$stmt_stages = $pdo->query("SELECT * FROM stages ORDER BY id ASC");
$stages = $stmt_stages->fetchAll();

// Fetch all subjects grouped by stage
$stmt_subjects = $pdo->query("SELECT * FROM subjects ORDER BY is_core DESC, name ASC");
$all_subjects = $stmt_subjects->fetchAll();

$subjects_by_stage = [];
foreach ($all_subjects as $sub) {
    $subjects_by_stage[$sub['stage_id']][] = $sub;
}
?>

<div class="animate-fade-in mb-4" id="subjectsApp">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <!-- Title changes based on folder level -->
            <h2 class="fs-4 fw-black text-primary m-0 d-flex align-items-center gap-2">
                <span onclick="subjectsApp.setCurrentStage(null)" class="cursor-pointer hover-warning transition" title="العودة للمراحل الرئيسية">المراحل الدراسية</span>
                <span id="breadcrumbDivider" class="text-secondary opacity-50 mx-1 d-none">/</span>
                <span id="breadcrumbStageName" class="text-warning d-none"></span>
            </h2>
            <p id="subtitleRoot" class="small fw-bold text-secondary mt-1 m-0">إدارة المراحل بنظام الملفات (Folders)</p>
            <p id="subtitleFolder" class="small fw-bold text-secondary mt-1 m-0 d-none">إدارة المواد داخل هذه المرحلة</p>
        </div>
        <div class="d-flex gap-2">
            <!-- Add Stage button (visible only in root) -->
            <button id="btnAddStage" onclick="openStageModal()" class="btn btn-light bg-white border fw-bold text-secondary shadow-sm hover-primary transition d-flex align-items-center gap-2 rounded-3 px-4 py-2">
                <i class="fa-solid fa-folder-plus"></i> إضافة مرحلة
            </button>
            
            <!-- Add Subject button (visible only inside a folder) -->
            <button id="btnAddSubject" onclick="openSubjectModal()" class="btn btn-primary fw-bold text-white shadow-sm hover-bg-darker transition d-flex align-items-center gap-2 rounded-3 px-4 py-2 d-none" style="background-color: #7B1F3F;">
                <i class="fa-solid fa-file-circle-plus"></i> إضافة مادة هنا
            </button>
            
            <!-- Back Button -->
            <button id="btnBack" onclick="subjectsApp.setCurrentStage(null)" class="btn btn-light text-secondary fw-bold border-0 shadow-sm transition d-flex align-items-center gap-2 rounded-3 px-4 py-2 d-none">
                <i class="fa-solid fa-arrow-right"></i> رجوع
            </button>
        </div>
    </div>

    <?php if (empty($stages)): ?>
        <div class="bg-white p-5 rounded-4 border shadow-sm text-center">
            <div class="rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 70px; height: 70px; font-size: 2rem;">
                <i class="fa-solid fa-folder-open"></i>
            </div>
            <h3 class="fw-black text-secondary fs-5 mb-1">لا يوجد مجلدات مراحل</h3>
            <p class="small fw-bold text-secondary opacity-75 mb-0">قم بإنشاء مجلد مرحلة أولاً.</p>
        </div>
    <?php else: ?>
        
        <!-- Root Level: Stages as Folders -->
        <div id="viewRoot" class="row g-5 mt-2">
            <?php foreach ($stages as $stage): 
                $count = isset($subjects_by_stage[$stage['id']]) ? count($subjects_by_stage[$stage['id']]) : 0;
            ?>
            <div class="col-sm-6 col-md-4 col-lg-3">
                <div onclick="subjectsApp.setCurrentStage(<?= $stage['id'] ?>, '<?= htmlspecialchars($stage['name']) ?>')" class="folder-card bg-white p-4 cursor-pointer group h-100 position-relative overflow-hidden d-flex flex-column" style="border-radius: 24px;">
                    
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-primary opacity-0 group-hover-opacity-5 transition z-0"></div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-4 position-relative z-1">
                        <div class="d-flex align-items-center gap-3">
                            <div class="folder-icon d-flex align-items-center justify-content-center text-white shadow-sm transition group-hover-scale" style="background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)); width: 50px; height: 50px; border-radius: 16px;">
                                <i class="fa-solid fa-folder-open fs-5"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button onclick="event.stopPropagation(); editStage(<?= $stage['id'] ?>, '<?= htmlspecialchars(addslashes($stage['name'])) ?>')" class="btn btn-sm btn-light text-secondary bg-white shadow-sm hover-primary rounded-circle d-flex align-items-center justify-content-center p-0 border border-secondary border-opacity-25" style="width: 30px; height: 30px; z-index: 2;" title="تعديل المرحلة">
                                <i class="fa-solid fa-pen" style="font-size: 0.7rem;"></i>
                            </button>
                            <button onclick="event.stopPropagation(); deleteStage(<?= $stage['id'] ?>)" class="btn btn-sm btn-light text-danger bg-white shadow-sm hover-danger rounded-circle d-flex align-items-center justify-content-center p-0 border border-danger border-opacity-25" style="width: 30px; height: 30px; z-index: 2;" title="حذف المرحلة">
                                <i class="fa-solid fa-trash" style="font-size: 0.7rem;"></i>
                            </button>
                            <span class="badge bg-light text-secondary border fw-black px-3 py-2 rounded-pill shadow-sm transition group-hover-bg-primary-light group-hover-border-primary group-hover-text-primary">
                                <?= $count ?> مواد
                            </span>
                        </div>
                    </div>

                    <div class="position-relative z-1 mb-4">
                        <h3 class="fw-black text-dark fs-5 mb-1 transition group-hover-text-primary"><?= htmlspecialchars($stage['name']) ?></h3>
                    </div>

                    <div class="mt-auto pt-3 border-top position-relative z-1 d-flex justify-content-between align-items-center">
                        <p class="small fw-bold text-secondary m-0 transition d-flex align-items-center gap-2">
                            تصفح المواد
                        </p>
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-secondary transition group-hover-bg-primary group-hover-text-white" style="width: 35px; height: 35px;">
                            <i class="fa-solid fa-arrow-left-long" style="font-size: 0.85rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Inside a Folder: Subjects -->
        <?php foreach ($stages as $stage): ?>
        <div id="viewStage_<?= $stage['id'] ?>" class="stage-view d-none">
            <?php if (empty($subjects_by_stage[$stage['id']])): ?>
                <div class="text-center py-5 bg-white rounded-4 border border-dashed shadow-sm">
                    <div class="text-secondary opacity-25 mb-3" style="font-size: 3rem;"><i class="fa-regular fa-folder-open"></i></div>
                    <p class="small fw-bold text-secondary mb-0">هذا المجلد فارغ. لا يوجد مواد هنا.</p>
                </div>
            <?php else: ?>
                <div class="row g-4 mt-2">
                    <?php foreach ($subjects_by_stage[$stage['id']] as $s): 
                        $isCore = $s['is_core'] == 1;
                        $glowColor = $isCore ? 'var(--brand-primary)' : 'var(--brand-gold)';
                        $badgeBg = $isCore ? 'rgba(123, 31, 63, 0.1)' : 'rgba(212, 175, 55, 0.1)';
                        $badgeText = $isCore ? 'var(--brand-primary)' : 'var(--brand-gold)';
                    ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="subject-card bg-white p-4 position-relative overflow-hidden group h-100 d-flex flex-column" style="--card-glow: <?= $glowColor ?>;">
                            
                            <div class="d-flex justify-content-between align-items-start mb-4 position-relative z-1">
                                <div>
                                    <span class="badge rounded-pill mb-3 px-3 py-2 fw-bold" style="background-color: <?= $badgeBg ?>; color: <?= $badgeText ?>; font-size: 0.7rem;">
                                        <?= $isCore ? '<i class="fa-solid fa-tree me-1"></i> مادة أساسية' : '<i class="fa-solid fa-seedling me-1"></i> مادة فرعية' ?>
                                    </span>
                                    <h4 class="fw-black text-dark m-0 fs-5 d-flex align-items-center gap-2">
                                        <?= htmlspecialchars($s['name']) ?>
                                    </h4>
                                </div>
                                <div class="opacity-0 group-hover-opacity-100 transition d-flex gap-2">
                                    <button onclick="editSubject(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>', <?= $s['is_core'] ?>)" class="btn btn-light text-secondary bg-white shadow-sm hover-primary rounded-circle d-flex align-items-center justify-content-center p-0 border border-secondary border-opacity-25" style="width: 35px; height: 35px;" title="تعديل المادة">
                                        <i class="fa-solid fa-pen" style="font-size: 0.8rem;"></i>
                                    </button>
                                    <button onclick="deleteSubject(<?= $s['id'] ?>)" class="btn btn-light text-danger bg-white shadow-sm hover-danger rounded-circle d-flex align-items-center justify-content-center p-0 border border-danger border-opacity-25" style="width: 35px; height: 35px;" title="حذف المادة">
                                        <i class="fa-solid fa-trash" style="font-size: 0.8rem;"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <p class="small fw-bold text-secondary mb-4 flex-grow-1 position-relative z-1" style="font-size: 0.85rem; line-height: 1.6;">
                                <?= htmlspecialchars($s['description'] ?? 'لا يوجد وصف للمادة.') ?>
                            </p>
                            
                            <div class="mt-auto pt-4 border-top border-secondary border-opacity-10 d-flex align-items-center justify-content-between position-relative z-1">
                                <button onclick="window.location.href='/admin?tab=materials&subject_id=<?= $s['id'] ?>'" class="btn p-0 text-decoration-none fw-black d-flex align-items-center gap-2 group-hover-text-primary transition" style="color: var(--brand-gold); font-size: 0.85rem;">
                                    إدارة المحتوى والدروس <i class="fa-solid fa-arrow-left transition group-hover-translate-x-neg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<style>
/* Avant-Garde Folders */
.folder-card {
    border: 1px solid rgba(0,0,0,0.04);
    box-shadow: 0 10px 25px rgba(0,0,0,0.02);
    transition: all 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.folder-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}
.folder-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}
.folder-icon {
    width: 55px; height: 55px;
    border-radius: 16px;
}

/* Subject Cards (Glass/Border effect) */
.subject-card {
    border-radius: 24px;
    border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 10px 25px rgba(0,0,0,0.02);
    transition: all 0.4s cubic-bezier(0.2, 0.8, 0.2, 1);
}
.subject-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    border-radius: 24px;
    border: 2px solid transparent;
    transition: all 0.4s;
    pointer-events: none;
    z-index: 0;
}
.subject-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.06);
}
.subject-card:hover::after {
    border-color: var(--card-glow);
    box-shadow: inset 0 0 20px rgba(0,0,0,0.02);
}

/* Group Utilities */
.group:hover .group-hover-bg-primary { background-color: var(--brand-primary) !important; }
.group:hover .group-hover-bg-warning { background-color: var(--brand-gold) !important; }
.group:hover .group-hover-text-white { color: #fff !important; }
.group:hover .group-hover-scale { transform: scale(1.2); }
.group:hover .group-hover-translate-x-neg { transform: translateX(-5px); }
.group:hover .group-hover-translate-y { transform: translateY(-3px); }
.group:hover .group-hover-opacity-100 { opacity: 1 !important; }
.group:hover .group-hover-translate-x-0 { transform: translateX(0) !important; }
.transition { transition: all 0.3s ease; }
</style>

<!-- Modal: Add Stage -->
<div class="modal fade" id="addStageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0" style="background-color: rgba(123,31,63,0.02); padding: 1.5rem; border-top-left-radius: 1rem; border-top-right-radius: 1rem;">
                <h5 class="modal-title fw-black text-primary fs-5">إضافة مرحلة دراسية (مجلد جديد)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addStageForm" onsubmit="saveStage(event)">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">اسم المرحلة</label>
                        <input type="text" id="stageName" required placeholder="مثال: أولى ابتدائي" class="form-control bg-light border rounded-3 px-3 py-2 fw-bold text-dark focus-ring focus-ring-primary transition">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">وصف المرحلة (اختياري)</label>
                        <textarea id="stageDesc" rows="3" class="form-control bg-light border rounded-3 px-3 py-2 fw-bold text-dark focus-ring focus-ring-primary transition"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0" style="background-color: #f8f9fa; border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem;">
                <button type="button" class="btn btn-light text-secondary fw-bold rounded-3 px-4" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" onclick="document.getElementById('addStageForm').requestSubmit()" class="btn btn-primary fw-bold rounded-3 px-4 transition shadow-sm">إنشاء المجلد</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Subject -->
<div class="modal fade" id="addSubjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0" style="background-color: rgba(123,31,63,0.02); padding: 1.5rem; border-top-left-radius: 1rem; border-top-right-radius: 1rem;">
                <h5 class="modal-title fw-black text-primary fs-5">إضافة مادة جديدة في المجلد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="addSubjectForm" onsubmit="saveSubject(event)">
                    <input type="hidden" id="current_stage_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">اسم المادة</label>
                        <input type="text" id="subjectName" required placeholder="مثال: ألحان" class="form-control bg-light border rounded-3 px-3 py-2 fw-bold text-dark focus-ring focus-ring-primary transition">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-1">وصف المادة (اختياري)</label>
                        <textarea id="subjectDesc" rows="3" class="form-control bg-light border rounded-3 px-3 py-2 fw-bold text-dark focus-ring focus-ring-primary transition"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary mb-2">نوع المادة والتصنيف</label>
                        <div class="d-flex gap-4">
                            <label class="d-flex align-items-center gap-2 cursor-pointer">
                                <input type="radio" name="is_core" value="1" checked class="form-check-input mt-0 border-secondary focus-ring focus-ring-primary">
                                <span class="small fw-bold text-dark">مادة أساسية (بذرة/شجرة)</span>
                            </label>
                            <label class="d-flex align-items-center gap-2 cursor-pointer">
                                <input type="radio" name="is_core" value="0" class="form-check-input mt-0 border-secondary focus-ring focus-ring-warning">
                                <span class="small fw-bold text-dark">مادة فرعية (شجرة/نبتة)</span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0" style="background-color: #f8f9fa; border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem;">
                <button type="button" class="btn btn-light text-secondary fw-bold rounded-3 px-4" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" onclick="document.getElementById('addSubjectForm').requestSubmit()" class="btn btn-primary fw-bold rounded-3 px-4 transition shadow-sm" style="background-color: #7B1F3F;">حفظ المادة</button>
            </div>
        </div>
    </div>
</div>

<script>
// Modals are instantiated on demand when opened.

const subjectsApp = {
    currentStageId: null,
    
    setCurrentStage: function(id, name = '') {
        this.currentStageId = id;
        
        // Hide all stage views
        document.querySelectorAll('.stage-view').forEach(el => el.classList.add('d-none'));
        
        if (id === null) {
            // Show root
            document.getElementById('viewRoot').classList.remove('d-none');
            
            // UI Toggles
            document.getElementById('breadcrumbDivider').classList.add('d-none');
            document.getElementById('breadcrumbStageName').classList.add('d-none');
            document.getElementById('subtitleRoot').classList.remove('d-none');
            document.getElementById('subtitleFolder').classList.add('d-none');
            
            document.getElementById('btnAddStage').classList.remove('d-none');
            document.getElementById('btnAddSubject').classList.add('d-none');
            document.getElementById('btnBack').classList.add('d-none');
        } else {
            // Show specific stage
            const stageView = document.getElementById('viewStage_' + id);
            if (stageView) stageView.classList.remove('d-none');
            document.getElementById('viewRoot').classList.add('d-none');
            
            // UI Toggles
            document.getElementById('breadcrumbDivider').classList.remove('d-none');
            const nameEl = document.getElementById('breadcrumbStageName');
            nameEl.innerText = name;
            nameEl.classList.remove('d-none');
            
            document.getElementById('subtitleRoot').classList.add('d-none');
            document.getElementById('subtitleFolder').classList.remove('d-none');
            
            document.getElementById('btnAddStage').classList.add('d-none');
            document.getElementById('btnAddSubject').classList.remove('d-none');
            document.getElementById('btnBack').classList.remove('d-none');
        }
    }
};

function openStageModal() {
    document.getElementById('addStageForm').reset();
    const modalEl = document.getElementById('addStageModal');
    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modalInstance.show();
}

async function saveStage(e) {
    e.preventDefault();
    const form = document.getElementById('addStageForm');
    const submitBtn = document.querySelector('button[onclick="document.getElementById(\'addStageForm\').requestSubmit()"]');
    if (submitBtn) {
        submitBtn.classList.add('disabled');
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> جارٍ الحفظ...';
    }

    const name = document.getElementById('stageName').value;
    const desc = document.getElementById('stageDesc').value;

    try {
        const res = await fetch('/api/stages', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name, description: desc })
        });
        const data = await res.json();
        if(data.success) {
            Swal.fire({
                title: 'نجاح!',
                text: 'تمت الإضافة بنجاح',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('خطأ', data.error || "خطأ أثناء الإضافة", 'error');
        }
    } catch(err) {
        Swal.fire('خطأ', "حدث خطأ في الاتصال", 'error');
    }
}

function openSubjectModal() {
    document.getElementById('addSubjectForm').reset();
    document.getElementById('current_stage_id').value = subjectsApp.currentStageId;
    const modalEl = document.getElementById('addSubjectModal');
    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
    modalInstance.show();
}

async function saveSubject(e) {
    e.preventDefault();
    const stage_id = document.getElementById('current_stage_id').value;
    const name = document.getElementById('subjectName').value;
    const desc = document.getElementById('subjectDesc').value;
    const is_core = document.querySelector('input[name="is_core"]:checked').value;

    try {
        const res = await fetch('/api/subjects', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ stage_id, name, description: desc, is_core })
        });
        const data = await res.json();
        if(data.success) {
            Swal.fire({
                title: 'نجاح!',
                text: 'تمت إضافة المادة بنجاح',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('خطأ', data.error || "خطأ أثناء إضافة المادة", 'error');
        }
    } catch(err) {
        Swal.fire('خطأ', "حدث خطأ في الاتصال", 'error');
    }
}

function editSubject(id, name, isCore) {
    Swal.fire({
        title: 'تعديل مادة',
        html: `
            <input id="swal-input-name" class="swal2-input fw-bold" placeholder="اسم المادة" value="${name}">
            <select id="swal-input-core" class="swal2-select fw-bold">
                <option value="1" ${isCore ? 'selected' : ''}>مادة أساسية</option>
                <option value="0" ${!isCore ? 'selected' : ''}>مادة فرعية</option>
            </select>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'حفظ',
        cancelButtonText: 'إلغاء',
        preConfirm: () => {
            return {
                id: id,
                name: document.getElementById('swal-input-name').value,
                is_core: document.getElementById('swal-input-core').value
            }
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await fetch('/api/subjects/edit', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(result.value)
                });
                const data = await res.json();
                if(data.success) {
                    Swal.fire({title: 'نجاح!', text: 'تم تعديل المادة بنجاح', icon: 'success', timer: 1500, showConfirmButton: false}).then(() => location.reload());
                } else {
                    Swal.fire('خطأ', data.error || "خطأ أثناء التعديل", 'error');
                }
            } catch(err) {
                Swal.fire('خطأ', "حدث خطأ في الاتصال", 'error');
            }
        }
    });
}

async function deleteSubject(id) {
    const result = await Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "سيتم حذف المادة ولن تتمكن من التراجع!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذف!',
        cancelButtonText: 'إلغاء'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch('/api/subjects?id=' + id, { method: 'DELETE' });
            const res = await response.json();
            if (res.success) {
                Swal.fire({
                    title: 'تم الحذف!',
                    text: 'تم حذف المادة بنجاح.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('خطأ', res.error || "خطأ أثناء الحذف", 'error');
            }
        } catch (err) {
            Swal.fire('خطأ', "حدث خطأ أثناء الحذف", 'error');
        }
    }
}

function editStage(id, name) {
    Swal.fire({
        title: 'تعديل المرحلة',
        input: 'text',
        inputLabel: 'اسم المرحلة',
        inputValue: name,
        showCancelButton: true,
        confirmButtonText: 'حفظ',
        cancelButtonText: 'إلغاء',
        inputValidator: (value) => {
            if (!value) {
                return 'يجب إدخال اسم المرحلة'
            }
        }
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await fetch('/api/stages/edit', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({id: id, name: result.value})
                });
                const data = await res.json();
                if(data.success) {
                    Swal.fire({title: 'نجاح!', text: 'تم تعديل المرحلة بنجاح', icon: 'success', timer: 1500, showConfirmButton: false}).then(() => location.reload());
                } else {
                    Swal.fire('خطأ', data.error || "خطأ أثناء التعديل", 'error');
                }
            } catch(err) {
                Swal.fire('خطأ', "حدث خطأ في الاتصال", 'error');
            }
        }
    });
}

async function deleteStage(id) {
    const result = await Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "سيتم حذف المرحلة ولن تتمكن من التراجع!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذف!',
        cancelButtonText: 'إلغاء'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch('/api/stages?id=' + id, { method: 'DELETE' });
            const res = await response.json();
            if (res.success) {
                Swal.fire({
                    title: 'تم الحذف!',
                    text: 'تم حذف المرحلة بنجاح.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('خطأ', res.error || "خطأ أثناء الحذف", 'error');
            }
        } catch (err) {
            Swal.fire('خطأ', "حدث خطأ أثناء الحذف", 'error');
        }
    }
}
</script>
