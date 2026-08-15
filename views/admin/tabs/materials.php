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

// Fetch Materials
$stmt = $pdo->prepare("SELECT * FROM materials WHERE subject_id = ? ORDER BY created_at DESC");
$stmt->execute([$subject_id]);
$materials = $stmt->fetchAll();

// Fetch Exams
$stmt = $pdo->prepare("SELECT * FROM exams WHERE subject_id = ? ORDER BY created_at DESC");
$stmt->execute([$subject_id]);
$exams = $stmt->fetchAll();

// Fetch Homework
$stmt = $pdo->prepare("SELECT * FROM homework WHERE subject_id = ? ORDER BY created_at DESC");
$stmt->execute([$subject_id]);
$homeworks = $stmt->fetchAll();
?>

<div class="animate-fade-in mb-4" id="materialsApp">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <button onclick="window.location.href='/admin?tab=subjects'" class="btn btn-sm btn-light text-secondary rounded-circle shadow-sm border mb-2" style="width:35px; height:35px;"><i class="fa-solid fa-arrow-right"></i></button>
            <h2 class="fs-4 fw-black text-primary m-0 d-flex align-items-center gap-2">
                <i class="fa-solid fa-folder-open text-warning"></i>
                محتوى المادة: <?= htmlspecialchars($subjectName) ?>
            </h2>
            <p class="small fw-bold text-secondary mt-1 m-0">إدارة الدروس، الاختبارات، والواجبات الخاصة بهذه المادة</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Add Content Forms -->
        <div class="col-lg-4">
            <div class="bg-white rounded-4 border shadow-sm p-4 mb-4" style="border-color: rgba(123,31,63,0.1) !important;">
                <h6 class="fw-black text-dark border-bottom pb-3 mb-4" style="border-color: rgba(123,31,63,0.1) !important;"><i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i> إضافة درس/ملف جديد</h6>
                <form id="addMaterialForm" onsubmit="uploadMaterial(event)">
                    <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">عنوان الدرس</label>
                        <input type="text" name="title" required class="form-control bg-light border rounded-3 px-3 py-2 fw-bold text-dark focus-ring focus-ring-primary">
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary mb-2">نوع الملف</label>
                        <div class="d-flex gap-2 w-100" role="group" aria-label="نوع الملف">
                            <input type="radio" class="btn-check" name="type" id="type_video" value="video" autocomplete="off" checked>
                            <label class="btn btn-outline-danger fw-bold rounded-3 flex-fill d-flex flex-column align-items-center py-2 gap-1 transition" for="type_video">
                                <i class="fa-solid fa-play fs-5"></i> فيديو
                            </label>

                            <input type="radio" class="btn-check" name="type" id="type_pdf" value="pdf" autocomplete="off">
                            <label class="btn btn-outline-danger fw-bold rounded-3 flex-fill d-flex flex-column align-items-center py-2 gap-1 transition" for="type_pdf">
                                <i class="fa-solid fa-file-pdf fs-5"></i> PDF
                            </label>

                            <input type="radio" class="btn-check" name="type" id="type_image" value="image" autocomplete="off">
                            <label class="btn btn-outline-danger fw-bold rounded-3 flex-fill d-flex flex-column align-items-center py-2 gap-1 transition" for="type_image">
                                <i class="fa-solid fa-image fs-5"></i> صورة
                            </label>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold text-secondary">اختر الملف</label>
                        <input type="file" name="file" required class="form-control bg-light border rounded-3 fw-bold text-dark focus-ring focus-ring-primary p-2">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-bold shadow-sm d-flex justify-content-center align-items-center gap-2 transition hover-bg-darker">
                        <i class="fa-solid fa-upload"></i> رفع وحفظ
                    </button>
                </form>
            </div>

            <!-- Add Exam Button -->
            <button onclick="openExamModal()" class="btn w-100 rounded-4 py-3 fw-black shadow-sm mb-3 d-flex justify-content-center align-items-center gap-2 transition hover-scale" style="background-color: rgba(220, 53, 69, 0.1); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.2);">
                <i class="fa-solid fa-spell-check fs-5"></i> إضافة اختبار (MCQ) جديد
            </button>
            
            <!-- Add Homework Button -->
            <button onclick="openHomeworkModal()" class="btn text-dark w-100 rounded-4 py-3 fw-black shadow-sm d-flex justify-content-center align-items-center gap-2 transition hover-scale" style="background-color: rgba(13, 202, 240, 0.1); border: 1px solid rgba(13, 202, 240, 0.2);">
                <i class="fa-solid fa-clipboard-list fs-5"></i> إضافة واجب / طلب تسميع
            </button>
        </div>

        <!-- Content Lists -->
        <div class="col-lg-8">
            <ul class="nav nav-pills mb-4 gap-2 bg-white p-2 rounded-pill shadow-sm border" style="border-color: rgba(123,31,63,0.1) !important;" id="contentTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active rounded-pill fw-bold px-4 transition" data-bs-toggle="pill" data-bs-target="#tab-materials" type="button">الدروس والملفات (<?= count($materials) ?>)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-bold px-4 transition" data-bs-toggle="pill" data-bs-target="#tab-exams" type="button">الاختبارات (<?= count($exams) ?>)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link rounded-pill fw-bold px-4 transition" data-bs-toggle="pill" data-bs-target="#tab-homeworks" type="button">الواجبات والتسميع (<?= count($homeworks) ?>)</button>
                </li>
            </ul>

            <div class="tab-content" id="contentTabsContent">
                
                <!-- Materials Tab -->
                <div class="tab-pane fade show active" id="tab-materials">
                    <div class="row g-3">
                        <?php if (empty($materials)): ?>
                            <div class="col-12 text-center py-5">
                                <h6 class="text-secondary fw-bold">لا يوجد دروس أو ملفات حالياً.</h6>
                            </div>
                        <?php else: ?>
                            <?php foreach ($materials as $m): ?>
                            <div class="col-md-6">
                                <div class="bg-white rounded-4 border shadow-sm p-3 d-flex align-items-center gap-3 transition hover-scale" style="border-color: rgba(123,31,63,0.05) !important;">
                                    <div class="fs-2 <?= $m['type'] === 'video' ? 'text-primary' : ($m['type'] === 'pdf' ? 'text-danger' : 'text-success') ?>">
                                        <?php if($m['type'] === 'video') echo '<i class="fa-solid fa-circle-play"></i>'; ?>
                                        <?php if($m['type'] === 'pdf') echo '<i class="fa-solid fa-file-pdf"></i>'; ?>
                                        <?php if($m['type'] === 'image') echo '<i class="fa-solid fa-image"></i>'; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold text-dark mb-1"><?= htmlspecialchars($m['title']) ?></h6>
                                        <div class="small fw-bold text-secondary"><?= date('Y-m-d', strtotime($m['created_at'])) ?></div>
                                    </div>
                                    <button onclick="deleteContent(<?= $m['id'] ?>, 'material')" class="btn btn-sm btn-light text-danger rounded-circle border shadow-sm transition hover-danger" style="width:35px; height:35px;"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Exams Tab -->
                <div class="tab-pane fade" id="tab-exams">
                    <div class="row g-3">
                        <?php if (empty($exams)): ?>
                            <div class="col-12 text-center py-5">
                                <h6 class="text-secondary fw-bold">لا يوجد اختبارات حالياً.</h6>
                            </div>
                        <?php else: ?>
                            <?php foreach ($exams as $e): ?>
                            <div class="col-12">
                                <div class="bg-white rounded-4 border border-danger border-opacity-25 shadow-sm p-3 d-flex align-items-center gap-3 transition hover-scale">
                                    <div class="fs-2 text-danger opacity-75"><i class="fa-solid fa-spell-check"></i></div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-black text-danger mb-1"><?= htmlspecialchars($e['title']) ?></h6>
                                        <div class="small fw-bold text-secondary"><?= date('Y-m-d', strtotime($e['created_at'])) ?></div>
                                    </div>
                                    <button onclick="deleteContent(<?= $e['id'] ?>, 'exam')" class="btn btn-sm btn-light text-danger rounded-circle border shadow-sm transition hover-danger" style="width:35px; height:35px;"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Homeworks Tab -->
                <div class="tab-pane fade" id="tab-homeworks">
                    <div class="row g-3">
                        <?php if (empty($homeworks)): ?>
                            <div class="col-12 text-center py-5">
                                <h6 class="text-secondary fw-bold">لا يوجد واجبات حالياً.</h6>
                            </div>
                        <?php else: ?>
                            <?php foreach ($homeworks as $h): ?>
                            <div class="col-12">
                                <div class="bg-white rounded-4 border border-info border-opacity-25 shadow-sm p-3 d-flex align-items-center gap-3 transition hover-scale">
                                    <div class="fs-2 text-info opacity-75"><i class="fa-solid fa-clipboard-list text-dark"></i></div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-black text-dark mb-1"><?= htmlspecialchars($h['title']) ?></h6>
                                        <div class="small text-secondary fw-bold">
                                            <?= $h['type'] === 'recitation' ? 'طلب تسميع 🎙️' : ($h['type'] === 'manual' ? 'رفع واجب 📤' : 'أسئلة 📝') ?>
                                        </div>
                                    </div>
                                    <button onclick="deleteContent(<?= $h['id'] ?>, 'homework')" class="btn btn-sm btn-light text-danger rounded-circle border shadow-sm transition hover-danger" style="width:35px; height:35px;"><i class="fa-solid fa-trash"></i></button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Exam Modal -->
<div class="modal fade" id="examModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0" style="padding: 2rem;">
                <h5 class="modal-title fw-black text-danger fs-4"><i class="fa-solid fa-spell-check"></i> إضافة اختبار جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">عنوان الاختبار</label>
                    <input type="text" id="examTitle" class="form-control bg-light border rounded-3 px-3 py-2 fw-bold text-dark focus-ring focus-ring-danger" placeholder="مثال: اختبار الشهر الأول">
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-primary m-0">الأسئلة (MCQ)</h6>
                    <button type="button" onclick="addQuestion()" class="btn btn-sm text-primary bg-primary bg-opacity-10 rounded-pill fw-bold px-3 transition hover-bg-darker"><i class="fa-solid fa-plus"></i> إضافة سؤال</button>
                </div>
                <div id="questionsContainer"></div>
            </div>
            <div class="modal-footer border-top-0 pt-0" style="padding: 1.5rem;">
                <button type="button" onclick="saveExam()" class="btn btn-danger w-100 rounded-3 py-2 fw-bold shadow-sm transition">حفظ الاختبار</button>
            </div>
        </div>
    </div>
</div>

<!-- Homework Modal -->
<div class="modal fade" id="homeworkModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0" style="padding: 2rem;">
                <h5 class="modal-title fw-black text-info fs-4"><i class="fa-solid fa-clipboard-list"></i> إضافة واجب / تسميع</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 pt-2">
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">العنوان</label>
                    <input type="text" id="hwTitle" class="form-control bg-light border rounded-3 px-3 py-2 fw-bold text-dark focus-ring focus-ring-info">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">النوع</label>
                    <select id="hwType" class="form-select bg-light border rounded-3 px-3 py-2 fw-bold text-dark focus-ring focus-ring-info">
                        <option value="recitation">طلب تسميع (يرفع الطالب تسجيل صوتي/فيديو)</option>
                        <option value="manual">ملف واجب (يرفع الطالب صورة/PDF)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-dark">المطلوب (التفاصيل)</label>
                    <textarea id="hwContent" class="form-control bg-light border rounded-3 px-3 py-2 fw-bold text-dark focus-ring focus-ring-info" rows="3" placeholder="اكتب المطلوب من الطالب بوضوح..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0" style="padding: 1.5rem;">
                <button type="button" onclick="saveHomework()" class="btn btn-info text-dark w-100 rounded-3 py-2 fw-black shadow-sm transition">نشر الواجب</button>
            </div>
        </div>
    </div>
</div>

<script>
let subjectId = <?= $subject_id ?>;
let questions = [];

async function uploadMaterial(e) {
    e.preventDefault();
    const form = document.getElementById('addMaterialForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جاري الرفع...';
    
    try {
        const response = await fetch('/api/materials', {
            method: 'POST',
            body: formData
        });
        const res = await response.json();
        if(res.success) {
            Swal.fire({
                title: 'تم الرفع!',
                text: 'تم رفع الملف بنجاح وإضافته للمادة.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            }).then(() => location.reload());
        } else {
            Swal.fire('خطأ', res.error || "فشل الرفع", 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-upload"></i> رفع وحفظ';
        }
    } catch(err) {
        Swal.fire('خطأ', 'حدث خطأ في الاتصال بالخادم', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-upload"></i> رفع وحفظ';
    }
}

async function deleteContent(id, type) {
    const result = await Swal.fire({
        title: 'هل أنت متأكد؟',
        text: "لا يمكن التراجع عن هذه الخطوة وسيتم مسح كل درجات الطلاب المرتبطة بهذا المحتوى!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'نعم، احذفه!',
        cancelButtonText: 'إلغاء'
    });
    
    if(result.isConfirmed) {
        // Build endpoint based on type
        let endpoint = '';
        if(type === 'material') endpoint = '/api/materials?id=' + id;
        else if(type === 'exam') endpoint = '/api/exams?id=' + id;
        else if(type === 'homework') endpoint = '/api/homeworks?id=' + id;
        
        try {
            const res = await fetch(endpoint, { method: 'DELETE' });
            const data = await res.json();
            if(data.success) {
                Swal.fire({icon: 'success', title: 'تم החذف', timer: 1500, showConfirmButton: false}).then(() => location.reload());
            } else {
                Swal.fire('خطأ', data.error || 'فشل الحذف', 'error');
            }
        } catch(err) {
            Swal.fire('خطأ', 'حدث خطأ في الاتصال', 'error');
        }
    }
}

let examModalInstance = null;
let hwModalInstance = null;

function getExamModal() {
    const modalEl = document.getElementById('examModal');
    if (modalEl && modalEl.parentNode.tagName !== 'BODY') {
        document.body.appendChild(modalEl);
    }
    return bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
}

function getHomeworkModal() {
    const modalEl = document.getElementById('homeworkModal');
    if (modalEl && modalEl.parentNode.tagName !== 'BODY') {
        document.body.appendChild(modalEl);
    }
    return bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
}

function openExamModal() {
    document.getElementById('examTitle').value = '';
    questions = [];
    addQuestion(); // start with one empty question
    getExamModal().show();
}

function openHomeworkModal() {
    document.getElementById('hwTitle').value = '';
    document.getElementById('hwContent').value = '';
    getHomeworkModal().show();
}

function addQuestion() {
    questions.push({ q: '', options: ['', '', '', ''], correct: 0, fileObj: null, audioBlob: null });
    renderQuestions();
}

let mediaRecorder = null;
let audioChunks = [];
let currentlyRecordingIdx = null;

function triggerQuestionImage(i) {
    document.getElementById(`qfile_input_${i}`).click();
}

function handleQuestionImage(i, input) {
    if(input.files && input.files[0]) {
        questions[i].fileObj = input.files[0];
        questions[i].audioBlob = null;
        renderQuestions();
    }
}

async function toggleRecordAudio(i) {
    if(currentlyRecordingIdx === i && mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        currentlyRecordingIdx = null;
        return;
    }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];
        mediaRecorder.ondataavailable = e => { if (e.data.size > 0) audioChunks.push(e.data); };
        mediaRecorder.onstop = () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
            questions[i].audioBlob = audioBlob;
            questions[i].fileObj = null;
            renderQuestions();
        };
        mediaRecorder.start();
        currentlyRecordingIdx = i;
        renderQuestions();
    } catch(err) {
        Swal.fire('خطأ', 'تعذر الوصول للمايكروفون', 'error');
    }
}

function clearMedia(i) {
    questions[i].fileObj = null;
    questions[i].audioBlob = null;
    renderQuestions();
}

function renderQuestions() {
    const container = document.getElementById('questionsContainer');
    container.innerHTML = '';
    questions.forEach((q, i) => {
        let optionsHtml = '';
        q.options.forEach((opt, oIdx) => {
            optionsHtml += `
                <div class="col-6 mb-2">
                    <div class="d-flex align-items-center bg-white border rounded-3 p-1">
                        <div class="px-2">
                            <input class="form-check-input mt-0 custom-radio" type="radio" name="correct_${i}" ${q.correct === oIdx ? 'checked' : ''} onchange="questions[${i}].correct = ${oIdx}">
                        </div>
                        <input type="text" class="form-control border-0 bg-transparent fw-bold text-dark" placeholder="اختيار ${oIdx+1}" value="${opt}" oninput="questions[${i}].options[${oIdx}] = this.value">
                    </div>
                </div>
            `;
        });
        let mediaHtml = '';
        if (q.fileObj) {
            mediaHtml = `<span class="badge bg-primary p-2"><i class="fa-solid fa-image"></i> ${q.fileObj.name}</span>`;
        } else if (q.audioBlob) {
            mediaHtml = `<span class="badge bg-danger p-2"><i class="fa-solid fa-microphone"></i> تسجيل صوتي</span>`;
        }
        
        let recordBtnClass = (currentlyRecordingIdx === i) ? 'btn-danger' : 'btn-outline-danger';
        let recordBtnText = (currentlyRecordingIdx === i) ? '<i class="fa-solid fa-stop"></i>' : '<i class="fa-solid fa-microphone"></i>';

        container.innerHTML += `
        <div class="card bg-light border-0 rounded-4 mb-3 p-3 position-relative">
            <button type="button" onclick="removeQuestion(${i})" class="btn btn-sm btn-light text-danger rounded-circle position-absolute border shadow-sm" style="top: -10px; right: -10px; width: 30px; height: 30px; z-index: 5;"><i class="fa-solid fa-times"></i></button>
            <div class="d-flex gap-2 mb-2">
                <input type="text" class="form-control bg-white border-0 rounded-3 px-3 py-2 fw-bold text-dark shadow-sm flex-grow-1" placeholder="نص السؤال..." value="${q.q}" oninput="questions[${i}].q = this.value">
                <input type="file" id="qfile_input_${i}" class="d-none" accept="image/*" onchange="handleQuestionImage(${i}, this)">
                <button type="button" class="btn btn-outline-primary rounded-3 shadow-sm" onclick="triggerQuestionImage(${i})" title="إرفاق صورة"><i class="fa-solid fa-image"></i></button>
                <button type="button" class="btn ${recordBtnClass} rounded-3 shadow-sm" onclick="toggleRecordAudio(${i})" title="تسجيل صوت">${recordBtnText}</button>
            </div>
            ${mediaHtml ? `<div class="mb-3">${mediaHtml} <button type="button" class="btn btn-sm text-danger" onclick="clearMedia(${i})"><i class="fa-solid fa-times"></i> إزالة</button></div>` : ''}
            <div class="row g-2">
                ${optionsHtml}
            </div>
        </div>`;
    });
}

function removeQuestion(i) {
    questions.splice(i, 1);
    renderQuestions();
}

async function saveExam() {
    const title = document.getElementById('examTitle').value;
    if(!title || questions.length === 0) {
        return Swal.fire('تنبيه', 'يجب إدخال عنوان الاختبار وسؤال واحد على الأقل.', 'warning');
    }
    
    // validate all questions have text/media and options
    for(let i=0; i<questions.length; i++) {
        if(!questions[i].q.trim() && !questions[i].fileObj && !questions[i].audioBlob) return Swal.fire('تنبيه', `السؤال رقم ${i+1} فارغ.`, 'warning');
        for(let j=0; j<4; j++) {
            if(!questions[i].options[j].trim()) return Swal.fire('تنبيه', `أحد الاختيارات في السؤال رقم ${i+1} فارغ.`, 'warning');
        }
    }
    
    const formData = new FormData();
    formData.append('subject_id', subjectId);
    formData.append('title', title);
    
    const cleanQs = questions.map(q => ({ q: q.q, options: q.options, correct: q.correct }));
    formData.append('questions', JSON.stringify(cleanQs));
    
    questions.forEach((q, i) => {
        if (q.fileObj) formData.append(`qfile_${i}`, q.fileObj);
        else if (q.audioBlob) formData.append(`qfile_${i}`, q.audioBlob, `audio_${i}.webm`);
    });
    
    try {
        const res = await fetch('/api/exams', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if(data.success) {
            getExamModal().hide();
            Swal.fire({icon: 'success', title: 'تم الحفظ!', timer: 1500, showConfirmButton: false}).then(()=>location.reload());
        } else {
            Swal.fire('خطأ', data.error || 'فشل في حفظ الاختبار', 'error');
        }
    } catch(err) { Swal.fire('خطأ', 'حدث خطأ في الاتصال', 'error'); }
}

async function saveHomework() {
    const title = document.getElementById('hwTitle').value;
    const type = document.getElementById('hwType').value;
    const content = document.getElementById('hwContent').value;
    
    if(!title || !content) return Swal.fire('تنبيه', 'يرجى ملء العنوان والمطلوب', 'warning');
    
    try {
        const res = await fetch('/api/homeworks/create', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ subject_id: subjectId, title: title, type: type, content: content })
        });
        const data = await res.json();
        if(data.success) {
            getHomeworkModal().hide();
            Swal.fire({icon: 'success', title: 'تم النشر!', timer: 1500, showConfirmButton: false}).then(()=>location.reload());
        } else {
            Swal.fire('خطأ', data.error || 'فشل في نشر الواجب', 'error');
        }
    } catch(err) { Swal.fire('خطأ', 'حدث خطأ في الاتصال', 'error'); }
}
</script>

<style>
/* Custom Radio Buttons inside Exam Builder */
.custom-radio { width: 1.2rem; height: 1.2rem; cursor: pointer; }
.custom-radio:checked { background-color: var(--bs-danger); border-color: var(--bs-danger); }

/* Custom Nav Pills Color */
.nav-pills .nav-link.active, .nav-pills .show>.nav-link {
    background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)) !important;
    color: white !important;
    box-shadow: 0 5px 15px rgba(123, 31, 63, 0.3);
}
.nav-pills .nav-link {
    color: var(--brand-primary) !important;
}
.nav-pills .nav-link:hover {
    background-color: rgba(123, 31, 63, 0.05);
}
</style>
