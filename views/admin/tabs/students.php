<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../../api/db.php';
}

$stmt = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC");
$students = $stmt->fetchAll();

// Get stages and subjects for the modal
$stages = $pdo->query("SELECT * FROM stages")->fetchAll();
$secondary_subjects = $pdo->query("SELECT * FROM subjects WHERE is_core = 0")->fetchAll();

// Map stage IDs to names for easy lookup
$stage_map = [];
foreach ($stages as $stg) {
    $stage_map[$stg['id']] = $stg['name'];
}
?>

<div class="animate-fade-in mb-4" id="studentsApp">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h2 class="fs-4 fw-black text-primary m-0">إدارة الطلاب</h2>
            <p class="small fw-bold text-secondary mt-1 m-0">إدارة شاملة لبيانات وخطط ومواد الطلاب</p>
        </div>
        <button onclick="openStudentModal()" class="btn btn-primary shadow-sm rounded-3 px-4 py-2 fw-bold d-flex align-items-center gap-2 transition hover-bg-darker">
            <i class="fa-solid fa-plus"></i> إضافة طالب
        </button>
    </div>

    <!-- Table Container -->
    <div class="bg-white rounded-4 border shadow-sm overflow-hidden" style="border-color: rgba(123,31,63,0.1) !important;">
        
        <!-- Search & Filter Bar -->
        <div class="p-3 border-bottom d-flex align-items-center gap-3 flex-wrap" style="background-color: rgba(123,31,63,0.02); border-color: rgba(123,31,63,0.05) !important;">
            <div class="position-relative flex-grow-1" style="max-width: 350px;">
                <i class="fa-solid fa-search position-absolute top-50 end-0 translate-middle-y me-3 text-secondary"></i>
                <input type="text" id="searchInput" oninput="filterTable()" placeholder="البحث عن طالب (اسم، إيميل)..." class="form-control rounded-pill ps-3 pe-5 py-2 fw-bold text-dark border-1 shadow-sm">
            </div>
            <div class="dropdown">
                <button class="btn bg-white border border-secondary border-opacity-25 rounded-pill px-4 py-2 fw-bold text-secondary shadow-sm d-flex align-items-center gap-2 transition hover-bg-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="stageFilterText">جميع المراحل</span>
                    <i class="fa-solid fa-chevron-down small opacity-50 ms-2"></i>
                </button>
                <ul class="dropdown-menu border-0 shadow-lg rounded-4 p-2 text-end">
                    <li><a class="dropdown-item fw-bold text-secondary rounded-3 transition mb-1" href="#" onclick="setStageFilter('', 'جميع المراحل', event)">جميع المراحل</a></li>
                    <?php foreach ($stages as $stg): ?>
                        <li><a class="dropdown-item fw-bold text-secondary rounded-3 transition mb-1" href="#" onclick="setStageFilter('<?= $stg['id'] ?>', '<?= htmlspecialchars($stg['name']) ?>', event)"><?= htmlspecialchars($stg['name']) ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <input type="hidden" id="stageFilter" value="">
            </div>

            <div class="dropdown">
                <button class="btn bg-white border border-secondary border-opacity-25 rounded-pill px-4 py-2 fw-bold text-secondary shadow-sm d-flex align-items-center gap-2 transition hover-bg-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span id="planFilterText">جميع الخطط</span>
                    <i class="fa-solid fa-chevron-down small opacity-50 ms-2"></i>
                </button>
                <ul class="dropdown-menu border-0 shadow-lg rounded-4 p-2 text-end">
                    <li><a class="dropdown-item fw-bold text-secondary rounded-3 transition mb-1" href="#" onclick="setPlanFilter('', 'جميع الخطط', event)">جميع الخطط</a></li>
                    <li><a class="dropdown-item fw-bold text-secondary rounded-3 transition mb-1" href="#" onclick="setPlanFilter('بذرة', 'خطة بذرة', event)">خطة بذرة</a></li>
                    <li><a class="dropdown-item fw-bold text-secondary rounded-3 transition mb-1" href="#" onclick="setPlanFilter('نبتة', 'خطة نبتة', event)">خطة نبتة</a></li>
                    <li><a class="dropdown-item fw-bold text-secondary rounded-3 transition mb-1" href="#" onclick="setPlanFilter('شجرة', 'خطة شجرة', event)">خطة شجرة</a></li>
                </ul>
                <input type="hidden" id="planFilter" value="">
            </div>
        </div>

        <!-- Desktop Table -->
        <div class="table-responsive" style="max-height: 55vh; overflow-y: auto;">
            <table class="table table-hover align-middle m-0" id="studentsTable">
                <thead style="background-color: #f8f9fa; position: sticky; top: 0; z-index: 10;">
                    <tr class="text-secondary small text-uppercase">
                        <th class="p-3 fw-black text-center" style="width: 50px;">#</th>
                        <th class="p-3 fw-black">اسم الطالب</th>
                        <th class="p-3 fw-black text-center">الخطة</th>
                        <th class="p-3 fw-black text-center">المرحلة</th>
                        <th class="p-3 fw-black text-center">النقاط</th>
                        <th class="p-3 fw-black text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="border-top-0" id="studentsTbody">
                    <?php if (empty($students)): ?>
                        <tr id="emptyRow">
                            <td colspan="6" class="p-5 text-center text-secondary fw-bold">لا يوجد طلاب مسجلين حتى الآن.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $index => $student): ?>
                        <tr class="student-row transition" data-id="<?= $student['id'] ?>" data-name="<?= htmlspecialchars($student['child_name']) ?>" data-email="<?= htmlspecialchars($student['email']) ?>" data-plan="<?= htmlspecialchars($student['plan'] ?? 'بذرة') ?>" data-stage="<?= htmlspecialchars($student['stage_id'] ?? '') ?>">
                            <td class="p-3 text-center fw-bold text-secondary small"><?= $student['id'] ?></td>
                            <td class="p-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary flex-shrink-0" style="width: 40px; height: 40px; font-size: 1.2rem;">
                                        <?= $student['gender'] === 'girl' ? '👧' : '👦' ?>
                                    </div>
                                    <div>
                                        <div class="fw-black text-dark"><?= htmlspecialchars($student['child_name']) ?></div>
                                        <div class="small fw-bold text-secondary" dir="ltr" style="font-size: 0.75rem;"><?= htmlspecialchars($student['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3 text-center">
                                <?php 
                                    $planStyles = [
                                        'بذرة' => 'background-color: rgba(155, 112, 96, 0.1); color: #9B7060; border: 1px solid rgba(155, 112, 96, 0.25);', // Bronze
                                        'نبتة' => 'background-color: rgba(123, 31, 63, 0.1); color: #7B1F3F; border: 1px solid rgba(123, 31, 63, 0.25);', // Wine Red
                                        'شجرة' => 'background-color: rgba(212, 175, 55, 0.1); color: #B5952F; border: 1px solid rgba(212, 175, 55, 0.25);', // Gold
                                    ];
                                    $pStyle = $planStyles[$student['plan'] ?? 'بذرة'] ?? $planStyles['بذرة'];
                                ?>
                                <span class="badge rounded-pill fw-bold px-3 py-1 shadow-sm" style="<?= $pStyle ?>"><?= htmlspecialchars($student['plan'] ?? 'بذرة') ?></span>
                            </td>
                            <td class="p-3 text-center">
                                <span class="badge rounded-pill bg-light text-secondary border fw-bold px-3 py-1"><?= htmlspecialchars($stage_map[$student['stage_id']] ?? 'غير محدد') ?></span>
                            </td>
                            <td class="p-3 text-center fw-black text-primary">
                                <?= number_format($student['points']) ?> <i class="fa-solid fa-star text-warning small"></i>
                            </td>
                            <td class="p-3 text-center">
                                <div class="d-flex justify-content-center gap-2">
                                    <button onclick="viewStudentProfile(<?= $student['id'] ?>)" class="btn btn-sm btn-primary text-white border-0 shadow-sm rounded-circle hover-scale transition d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="عرض البروفايل">
                                        <i class="fa-solid fa-eye small"></i>
                                    </button>
                                    <button onclick="editStudent(<?= $student['id'] ?>)" class="btn btn-sm text-secondary bg-white border shadow-sm rounded-circle hover-primary transition d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="تعديل">
                                        <i class="fa-solid fa-pen small"></i>
                                    </button>
                                    <button onclick="deleteStudent(<?= $student['id'] ?>)" class="btn btn-sm text-secondary bg-white border shadow-sm rounded-circle hover-danger transition d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" title="حذف">
                                        <i class="fa-solid fa-trash small"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
/* Table Row Premium Hover */
.student-row {
    transition: all 0.3s ease;
}
.student-row:hover {
    background-color: #fff !important;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    z-index: 2;
    position: relative;
}
.student-row td {
    border-bottom: 1px solid rgba(0,0,0,0.03);
}
/* Dropdown Item Premium Hover */
.dropdown-menu .dropdown-item:hover, .dropdown-menu .dropdown-item:focus {
    background-color: rgba(123, 31, 63, 0.08) !important;
    color: var(--brand-primary) !important;
}
</style>

<!-- Student Modal -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0" style="background-color: rgba(123,31,63,0.02); padding: 1.5rem; border-top-left-radius: 1rem; border-top-right-radius: 1rem;">
                <h5 class="modal-title fw-black text-primary fs-5" id="modalTitle">تعديل بيانات الطالب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="studentForm" onsubmit="saveStudent(event)">
                    <input type="hidden" id="student_id" name="id">
                    
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary mb-1">اسم الطالب</label>
                            <input type="text" id="child_name" name="child_name" required class="form-control bg-light border-0 rounded-3 px-3 py-2 fw-bold text-dark focus-ring focus-ring-primary transition">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary mb-1">ولي الأمر</label>
                            <input type="text" id="parent_name" name="parent_name" class="form-control bg-light border-0 rounded-3 px-3 py-2 fw-bold text-dark focus-ring focus-ring-primary transition">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary mb-1">البريد الإلكتروني</label>
                            <input type="email" id="email" name="email" required class="form-control bg-light border-0 rounded-3 px-3 py-2 fw-bold text-dark focus-ring focus-ring-primary transition">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary mb-2">المرحلة الدراسية</label>
                            <div class="d-flex flex-wrap gap-2 w-100" role="group">
                                <input type="radio" class="btn-check" name="stage_id" id="stage_none" value="" checked>
                                <label class="btn btn-sm btn-outline-secondary fw-bold rounded-3 flex-grow-1" for="stage_none">غير محدد</label>
                                <?php foreach ($stages as $stg): ?>
                                    <input type="radio" class="btn-check" name="stage_id" id="stage_<?= $stg['id'] ?>" value="<?= $stg['id'] ?>">
                                    <label class="btn btn-sm btn-outline-primary fw-bold rounded-3 flex-grow-1" for="stage_<?= $stg['id'] ?>"><?= htmlspecialchars($stg['name']) ?></label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary mb-1">تاريخ الميلاد</label>
                            <input type="date" id="birth_date" name="birth_date" class="form-control bg-light border-0 rounded-3 px-3 py-2 fw-bold text-dark focus-ring focus-ring-primary transition">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary mb-2">النوع</label>
                            <div class="d-flex gap-2 w-100" role="group">
                                <input type="radio" class="btn-check" name="gender" id="gender_boy" value="boy" checked>
                                <label class="btn btn-sm btn-outline-primary fw-bold rounded-3 flex-grow-1" for="gender_boy">ولد 👦</label>
                                <input type="radio" class="btn-check" name="gender" id="gender_girl" value="girl">
                                <label class="btn btn-sm btn-outline-primary fw-bold rounded-3 flex-grow-1" for="gender_girl">بنت 👧</label>
                            </div>
                        </div>
                    </div>

                    <div class="p-3 mb-4 rounded-3 border" style="background-color: rgba(123,31,63,0.02); border-color: rgba(123,31,63,0.1) !important;">
                        <h6 class="fw-black text-primary mb-3 pb-2 border-bottom" style="border-color: rgba(123,31,63,0.1) !important;">نظام التحفيز والتقدم</h6>
                        <div class="row g-3">
                            <div class="col-4">
                                <label class="form-label small fw-bold text-secondary mb-1" style="font-size: 0.75rem;">المستوى</label>
                                <input type="number" id="level" name="level" min="1" class="form-control border rounded-3 px-3 py-1 fw-bold text-center">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold text-secondary mb-1" style="font-size: 0.75rem;">النقاط</label>
                                <input type="number" id="points" name="points" min="0" class="form-control border rounded-3 px-3 py-1 fw-bold text-center">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold text-secondary mb-1" style="font-size: 0.75rem;">النجوم</label>
                                <input type="number" id="stars" name="stars" min="0" class="form-control border rounded-3 px-3 py-1 fw-bold text-center">
                            </div>
                        </div>
                    </div>

                    <div class="p-3 mb-2 rounded-3 border border-warning border-opacity-50" style="background-color: rgba(255,193,7,0.05);">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h6 class="fw-black text-warning m-0">خطة الاشتراك</h6>
                            <div class="d-flex gap-2" role="group">
                                <input type="radio" class="btn-check" name="plan" id="plan_seed" value="بذرة" onchange="toggleSecondarySubjects()" checked>
                                <label class="btn btn-sm btn-outline-warning text-dark fw-bold rounded-3" for="plan_seed">بذرة</label>
                                
                                <input type="radio" class="btn-check" name="plan" id="plan_sprout" value="نبتة" onchange="toggleSecondarySubjects()">
                                <label class="btn btn-sm btn-outline-warning text-dark fw-bold rounded-3" for="plan_sprout">نبتة</label>
                                
                                <input type="radio" class="btn-check" name="plan" id="plan_tree" value="شجرة" onchange="toggleSecondarySubjects()">
                                <label class="btn btn-sm btn-outline-warning text-dark fw-bold rounded-3" for="plan_tree">شجرة</label>
                            </div>
                        </div>
                        <p class="small text-secondary fw-bold mb-3" style="font-size: 0.75rem;">حدد المواد الفرعية المتاحة للطالب (يتم تفعيل هذا الخيار في خطة نبتة فقط):</p>
                        
                        <div id="secondary_subjects_container" class="row g-2 opacity-50" style="pointer-events: none; transition: opacity 0.3s;">
                            <?php foreach ($secondary_subjects as $ss): ?>
                                <div class="col-md-4 col-6">
                                    <label class="d-flex align-items-center gap-2 p-2 rounded-3 border bg-white cursor-pointer hover-border-warning transition">
                                        <input type="checkbox" name="secondary_subjects[]" value="<?= $ss['id'] ?>" class="form-check-input mt-0 subject-checkbox">
                                        <span class="small fw-bold text-dark"><?= htmlspecialchars($ss['name']) ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-top-0 pt-0" style="background-color: #f8f9fa; border-bottom-left-radius: 1rem; border-bottom-right-radius: 1rem;">
                <button type="button" class="btn btn-light text-secondary fw-bold rounded-3 px-4" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" onclick="document.getElementById('studentForm').requestSubmit()" class="btn btn-primary fw-bold rounded-3 px-4 d-flex align-items-center gap-2 shadow-sm transition">
                    <i class="fa-solid fa-save"></i> حفظ التعديلات
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Profile Modal -->
<div class="modal fade" id="studentProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow-lg" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
            <div class="modal-header border-bottom-0 pb-0" style="padding: 2rem;">
                <h5 class="modal-title fw-black text-dark fs-4 d-flex align-items-center gap-3">
                    <span class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-user-graduate"></i>
                    </span>
                    ملف الطالب الشامل
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <div id="profileLoading" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <div id="profileContent" class="d-none">
                    
                    <!-- Basic Info Cards -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 border bg-white shadow-sm d-flex gap-3 align-items-center h-100">
                                <div class="fs-1 text-primary"><i class="fa-solid fa-id-badge"></i></div>
                                <div>
                                    <h5 class="fw-black text-dark mb-2" id="p_child_name">---</h5>
                                    <div class="fs-6 fw-bold text-secondary mb-2"><i class="fa-solid fa-envelope me-1"></i> <span id="p_email">---</span></div>
                                    <div class="fs-6 fw-bold text-secondary"><i class="fa-solid fa-calendar me-1"></i> انضم في: <span id="p_created_at" dir="ltr">---</span></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 rounded-4 border bg-white shadow-sm d-flex gap-3 align-items-center h-100">
                                <div class="fs-1 text-warning"><i class="fa-solid fa-crown"></i></div>
                                <div>
                                    <h5 class="fw-black text-dark mb-2">الخطة: <span id="p_plan" class="badge bg-warning text-dark px-3 rounded-pill fs-6">---</span></h5>
                                    <div class="fs-6 fw-bold text-secondary mb-2"><i class="fa-solid fa-user-tie me-1"></i> ولي الأمر: <span id="p_parent_name">---</span></div>
                                    <div class="fs-6 fw-bold text-secondary"><i class="fa-solid fa-school me-1"></i> المرحلة: <span id="p_stage">---</span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Section -->
                    <div class="p-4 rounded-4 border bg-light mb-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-black text-dark m-0"><i class="fa-solid fa-chart-line text-primary me-2"></i> مستوى الإنجاز العام</h5>
                            <span class="badge bg-primary rounded-pill px-4 py-2 fw-bold fs-6" id="p_progress_text">0%</span>
                        </div>
                        <div class="progress rounded-pill bg-white border mb-3" style="height: 25px;">
                            <div id="p_progress_bar" class="progress-bar bg-primary progress-bar-striped progress-bar-animated fs-6 fw-bold" role="progressbar" style="width: 0%"></div>
                        </div>
                        <p class="fs-6 text-secondary fw-bold mb-0">شاهد <span id="p_completed_lessons" class="text-dark fw-black fs-5 mx-1">0</span> من أصل <span id="p_total_materials" class="text-dark fw-black fs-5 mx-1">0</span> مادة/درس في هذه المرحلة.</p>
                    </div>

                    <!-- Evaluations Table -->
                    <h5 class="fw-black text-dark mb-4 mt-4"><i class="fa-solid fa-clipboard-check text-success me-2"></i> درجات التقييمات والاختبارات</h5>
                    <div class="table-responsive rounded-4 border shadow-sm">
                        <table class="table table-hover table-striped bg-white m-0 text-center align-middle fs-6">
                            <thead class="table-light">
                                <tr class="text-secondary fw-bold fs-6">
                                    <th class="p-3">التاريخ</th>
                                    <th class="p-3">نوع التقييم</th>
                                    <th class="p-3">عنوان الدرس/الاختبار</th>
                                    <th class="p-3">الدرجة</th>
                                </tr>
                            </thead>
                            <tbody id="p_evaluations_body">
                                <!-- Populated via JS -->
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0" style="padding: 1.5rem;">
                <button type="button" class="btn btn-secondary fw-bold rounded-3 px-4 shadow-sm" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<script>
let studentModalInstance = null;

function setStageFilter(val, label, event) {
    event.preventDefault();
    document.getElementById('stageFilter').value = val;
    document.getElementById('stageFilterText').innerText = label;
    filterTable();
}

function setPlanFilter(val, label, event) {
    event.preventDefault();
    document.getElementById('planFilter').value = val;
    document.getElementById('planFilterText').innerText = label;
    filterTable();
}

function getStudentModal() {
    const modalEl = document.getElementById('studentModal');
    if (modalEl && modalEl.parentNode.tagName !== 'BODY') {
        document.body.appendChild(modalEl);
    }
    return bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
}

function filterTable() {
    const search = document.getElementById('searchInput').value.toLowerCase();
    const plan = document.getElementById('planFilter').value;
    const stage = document.getElementById('stageFilter').value;
    const rows = document.querySelectorAll('.student-row');
    
    let visibleCount = 0;
    rows.forEach(row => {
        const name = row.dataset.name.toLowerCase();
        const email = row.dataset.email.toLowerCase();
        const rowPlan = row.dataset.plan;
        const rowStage = row.dataset.stage;
        
        const matchesSearch = name.includes(search) || email.includes(search);
        const matchesPlan = plan === "" || rowPlan === plan;
        const matchesStage = stage === "" || rowStage === stage;
        
        if (matchesSearch && matchesPlan && matchesStage) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    const emptyRow = document.getElementById('emptyRow');
    if (emptyRow) {
        if (visibleCount === 0 && rows.length > 0) {
            if (!document.getElementById('noResultsRow')) {
                const tr = document.createElement('tr');
                tr.id = 'noResultsRow';
                tr.innerHTML = '<td colspan="6" class="p-5 text-center text-secondary fw-bold">لا يوجد نتائج متطابقة للبحث.</td>';
                document.getElementById('studentsTbody').appendChild(tr);
            }
        } else {
            const noRes = document.getElementById('noResultsRow');
            if (noRes) noRes.remove();
        }
    }
}

function openStudentModal() {
    document.getElementById('studentForm').reset();
    document.getElementById('student_id').value = '';
    document.getElementById('modalTitle').innerText = 'إضافة طالب جديد';
    
    // Clear checkboxes
    document.querySelectorAll('.subject-checkbox').forEach(cb => cb.checked = false);
    toggleSecondarySubjects();

    getStudentModal().show();
}

function closeStudentModal() {
    getStudentModal().hide();
}

function toggleSecondarySubjects() {
    const planRadio = document.querySelector('input[name="plan"]:checked');
    const plan = planRadio ? planRadio.value : 'بذرة';
    const container = document.getElementById('secondary_subjects_container');
    const checkboxes = document.querySelectorAll('.subject-checkbox');
    
    if (plan === 'شجرة') {
        container.style.opacity = '1';
        container.style.pointerEvents = 'auto';
        checkboxes.forEach(cb => { cb.checked = true; cb.disabled = false; });
    } else if (plan === 'نبتة') {
        container.style.opacity = '1';
        container.style.pointerEvents = 'auto';
        checkboxes.forEach(cb => { cb.disabled = false; });
    } else {
        container.style.opacity = '0.5';
        container.style.pointerEvents = 'none';
        checkboxes.forEach(cb => { cb.checked = false; cb.disabled = false; });
    }
}

async function editStudent(id) {
    try {
        const response = await fetch('/api/students?profile=1&id=' + id);
        const data = await response.json();
        
        if (data.success && data.user) {
            document.getElementById('student_id').value = data.user.id;
            document.getElementById('child_name').value = data.user.child_name;
            document.getElementById('parent_name').value = data.user.parent_name || '';
            document.getElementById('email').value = data.user.email;
            
            if (data.user.stage_id) {
                const stageRadio = document.querySelector(`input[name="stage_id"][value="${data.user.stage_id}"]`);
                if (stageRadio) stageRadio.checked = true;
            } else {
                document.getElementById('stage_none').checked = true;
            }
            
            document.getElementById('birth_date').value = data.user.birth_date || '';
            
            const genderRadio = document.querySelector(`input[name="gender"][value="${data.user.gender}"]`);
            if (genderRadio) genderRadio.checked = true;
            
            document.getElementById('level').value = data.user.level;
            document.getElementById('points').value = data.user.points;
            document.getElementById('stars').value = data.user.stars;
            
            const planRadio = document.querySelector(`input[name="plan"][value="${data.user.plan || 'بذرة'}"]`);
            if (planRadio) planRadio.checked = true;
            
            document.getElementById('modalTitle').innerText = 'تعديل بيانات: ' + data.user.child_name;

            toggleSecondarySubjects();
            
            if (data.user_subjects) {
                document.querySelectorAll('.subject-checkbox').forEach(cb => {
                    cb.checked = data.user_subjects.includes(parseInt(cb.value)) || data.user_subjects.includes(cb.value);
                });
            }
            
            getStudentModal().show();
        } else {
            Swal.fire('خطأ', 'فشل في جلب بيانات الطالب', 'error');
        }
    } catch (error) {
        Swal.fire('خطأ', 'حدث خطأ أثناء جلب بيانات الطالب', 'error');
    }
}

async function saveStudent(e) {
    e.preventDefault();
    const form = document.getElementById('studentForm');
    const formData = new FormData(form);
    
    const checkboxes = document.querySelectorAll('.subject-checkbox:checked');
    const selectedSubjects = Array.from(checkboxes).map(cb => cb.value);
    formData.append('secondary_subjects', JSON.stringify(selectedSubjects));

    const url = formData.get('id') ? '/api/students/edit' : '/api/auth/register'; 
    
    try {
        const response = await fetch('/api/students/edit', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            closeStudentModal();
            Swal.fire({
                title: 'نجاح!',
                text: 'تم الحفظ بنجاح',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire('خطأ', result.error || "حدث خطأ أثناء الحفظ", 'error');
        }
    } catch (err) {
        Swal.fire('خطأ', "حدث خطأ في الاتصال بالخادم", 'error');
    }
}

async function deleteStudent(id) {
    const result = await Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "سيتم حذف الطالب نهائياً مع كل درجاته وواجباته!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذف!',
        cancelButtonText: 'إلغاء'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch('/api/students?id=' + id, { method: 'DELETE' });
            const res = await response.json();
            if (res.success) {
                Swal.fire({
                    title: 'تم الحذف!',
                    text: 'تم حذف الطالب بنجاح.',
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

function getProfileModal() {
    const pModalEl = document.getElementById('studentProfileModal');
    if (pModalEl && pModalEl.parentNode.tagName !== 'BODY') {
        document.body.appendChild(pModalEl);
    }
    return bootstrap.Modal.getInstance(pModalEl) || new bootstrap.Modal(pModalEl);
}

async function viewStudentProfile(id) {
    const modal = getProfileModal();
    modal.show();
    document.getElementById('profileContent').classList.add('d-none');
    document.getElementById('profileLoading').classList.remove('d-none');

    try {
        const response = await fetch('/api/students?profile=1&id=' + id);
        const data = await response.json();
        
        if (data.success && data.user) {
            document.getElementById('p_child_name').innerText = data.user.child_name;
            document.getElementById('p_email').innerText = data.user.email;
            document.getElementById('p_created_at').innerText = new Date(data.user.created_at).toLocaleDateString('ar-EG');
            document.getElementById('p_plan').innerText = data.user.plan || 'بذرة';
            document.getElementById('p_parent_name').innerText = data.user.parent_name || 'غير محدد';
            document.getElementById('p_stage').innerText = data.user.stage_name || 'غير محدد';

            let completed = parseInt(data.completed_lessons) || 0;
            let total = parseInt(data.total_materials) || 0;
            let percent = total > 0 ? Math.round((completed / total) * 100) : 0;
            
            document.getElementById('p_completed_lessons').innerText = completed;
            document.getElementById('p_total_materials').innerText = total;
            document.getElementById('p_progress_text').innerText = percent + '%';
            document.getElementById('p_progress_bar').style.width = percent + '%';

            const evalBody = document.getElementById('p_evaluations_body');
            evalBody.innerHTML = '';
            
            if (data.evaluations && data.evaluations.length > 0) {
                data.evaluations.forEach(ev => {
                    let typeBadge = ev.type === 'exam' 
                        ? '<span class="badge bg-danger rounded-pill px-3">اختبار</span>'
                        : '<span class="badge bg-info text-dark rounded-pill px-3">واجب/تسميع</span>';
                    
                    let dateStr = new Date(ev.created_at).toLocaleDateString('ar-EG');
                    
                    evalBody.innerHTML += `
                        <tr>
                            <td class="p-3 fw-bold text-secondary fs-6">${dateStr}</td>
                            <td class="p-3">${typeBadge}</td>
                            <td class="p-3 fw-bold text-dark fs-6">${ev.material_title || '---'}</td>
                            <td class="p-3 fw-black ${Number(ev.score) > 50 ? 'text-success' : 'text-danger'} fs-5">${ev.score || 0} %</td>
                        </tr>
                    `;
                });
            } else {
                evalBody.innerHTML = '<tr><td colspan="4" class="p-5 text-secondary fw-bold fs-5">لا توجد تقييمات أو درجات بعد.</td></tr>';
            }

            document.getElementById('profileLoading').classList.add('d-none');
            document.getElementById('profileContent').classList.remove('d-none');
            document.getElementById('profileContent').classList.add('animate-fade-in');

        } else {
            Swal.fire('خطأ', 'فشل في جلب بيانات الطالب', 'error');
            getProfileModal().hide();
        }
    } catch (error) {
        Swal.fire('خطأ', 'حدث خطأ أثناء جلب بيانات الطالب', 'error');
        getProfileModal().hide();
    }
}
</script>
