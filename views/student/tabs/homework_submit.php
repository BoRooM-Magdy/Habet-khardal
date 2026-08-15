<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../../api/db.php';
}

$homework_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'] ?? 0;

if (!$homework_id || !$user_id) {
    echo '<div class="alert alert-danger m-4 text-center fw-bold shadow-sm">طلب غير صالح.</div>';
    return;
}

// Fetch Homework
$stmt = $pdo->prepare("SELECT h.*, s.name as subject_name FROM homework h JOIN subjects s ON h.subject_id = s.id WHERE h.id = ?");
$stmt->execute([$homework_id]);
$homework = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$homework) {
    echo '<div class="alert alert-danger m-4 text-center fw-bold shadow-sm">الواجب غير موجود.</div>';
    return;
}

// Fetch Submission
$stmt = $pdo->prepare("SELECT * FROM homework_submissions WHERE homework_id = ? AND user_id = ?");
$stmt->execute([$homework_id, $user_id]);
$submission = $stmt->fetch(PDO::FETCH_ASSOC);

?>

<div class="animate-fade-in mb-4">
    <!-- Header -->
    <div class="d-flex align-items-center mb-4 gap-3">
        <button onclick="switchTab('materials&subject_id=<?= $homework['subject_id'] ?>')" class="btn btn-light text-secondary rounded-circle shadow-sm border d-flex align-items-center justify-content-center hover-primary transition" style="width:40px; height:40px;">
            <i class="fa-solid fa-arrow-right"></i>
        </button>
        <div>
            <h2 class="fs-4 fw-black text-primary m-0 d-flex align-items-center gap-2">
                <i class="fa-solid fa-pen text-warning"></i>
                <?= htmlspecialchars($homework['title']) ?>
            </h2>
            <p class="small fw-bold text-secondary mt-1 m-0">مادة: <?= htmlspecialchars($homework['subject_name']) ?></p>
        </div>
    </div>

    <!-- Homework Instructions -->
    <div class="card border-0 rounded-4 shadow-sm mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-circle-info text-primary me-2"></i> تفاصيل الواجب</h5>
            <div class="p-3 bg-light rounded-3" style="white-space: pre-wrap;"><?= htmlspecialchars($homework['questions']) ?></div>
        </div>
    </div>

    <?php if ($submission): ?>
        <!-- Already Submitted -->
        <div class="card border-0 rounded-4 shadow-sm border-success">
            <div class="card-body p-4 text-center">
                <div class="rounded-circle bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                    <i class="fa-solid fa-check fs-2"></i>
                </div>
                <h4 class="fw-bold text-success mb-2">تم التسليم بنجاح</h4>
                <p class="text-secondary fw-bold">حالة التقييم: 
                    <?php if ($submission['status'] === 'graded'): ?>
                        <span class="badge bg-success">تم التقييم (<?= $submission['score'] ?>/15)</span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark">قيد المراجعة</span>
                    <?php endif; ?>
                </p>
                
                <hr class="my-4">
                <h5 class="fw-bold text-dark mb-3 text-start">إجابتك:</h5>
                <div class="p-3 bg-light rounded-3 text-start" style="white-space: pre-wrap;">
                    <?php 
                        if (strpos($submission['student_answer'], 'uploads/') === 0) {
                            $ext = pathinfo($submission['student_answer'], PATHINFO_EXTENSION);
                            if (in_array(strtolower($ext), ['webm', 'wav', 'mp3', 'ogg'])) {
                                echo '<audio controls class="w-100"><source src="/api/' . htmlspecialchars($submission['student_answer']) . '">المتصفح لا يدعم مشغل الصوت.</audio>';
                            } else {
                                echo '<a href="/api/' . htmlspecialchars($submission['student_answer']) . '" target="_blank" class="btn btn-outline-primary fw-bold"><i class="fa-solid fa-download me-2"></i> تحميل الملف المرفق</a>';
                            }
                        } else {
                            echo htmlspecialchars($submission['student_answer']);
                        }
                    ?>
                </div>

                <?php if (!empty($submission['teacher_comment'])): ?>
                    <hr class="my-4">
                    <h5 class="fw-bold text-dark mb-3 text-start"><i class="fa-solid fa-comment-dots text-primary me-2"></i> ملاحظات المعلم:</h5>
                    <div class="p-3 bg-white border border-primary border-opacity-25 rounded-3 text-start shadow-sm" style="white-space: pre-wrap; color: var(--brand-primary); font-weight: bold;">
                        <?= htmlspecialchars($submission['teacher_comment']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Submission Form -->
        <div class="card border-0 rounded-4 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold text-dark mb-4"><i class="fa-solid fa-upload text-primary me-2"></i> تسليم الإجابة</h5>
                <form id="homeworkSubmitForm" onsubmit="submitHomeworkForm(event)">
                    <input type="hidden" name="homework_id" value="<?= $homework['id'] ?>">
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">
                    
                    <?php if ($homework['type'] === 'recitation'): ?>
                    <!-- Audio Recorder UI -->
                    <div class="mb-4 p-4 border rounded-4 bg-light text-center">
                        <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-microphone text-danger me-2"></i> تسجيل التسميع الصوتي</h6>
                        <p class="small text-muted mb-3">انقر على زر التسجيل أدناه وابدأ في التسميع، وعند الانتهاء انقر على إيقاف.</p>
                        
                        <div class="d-flex justify-content-center gap-3 mb-3">
                            <button type="button" id="startRecordBtn" class="btn btn-outline-danger fw-bold rounded-pill px-4" onclick="startRecording()">
                                <i class="fa-solid fa-circle me-2"></i> بدء التسجيل
                            </button>
                            <button type="button" id="stopRecordBtn" class="btn btn-danger fw-bold rounded-pill px-4 d-none" onclick="stopRecording()">
                                <i class="fa-solid fa-stop me-2"></i> إيقاف التسجيل
                            </button>
                        </div>
                        
                        <div id="recordingIndicator" class="d-none text-danger fw-bold mb-3 animate-pulse">
                            <i class="fa-solid fa-microphone-lines me-2"></i> جاري التسجيل...
                        </div>

                        <div id="audioPlaybackContainer" class="d-none mt-3">
                            <audio id="audioPlayback" controls class="w-100 mb-2"></audio>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" onclick="deleteRecording()">
                                <i class="fa-solid fa-trash me-1"></i> حذف وإعادة التسجيل
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-secondary">الإجابة النصية (اختياري)</label>
                        <textarea name="text_answer" class="form-control bg-light border-0 rounded-3 p-3" rows="3" placeholder="اكتب إجابتك هنا..."></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold text-secondary">إرفاق ملف من الجهاز (اختياري)</label>
                        <input type="file" name="file" class="form-control bg-light border-0 rounded-3">
                    </div>

                    <button type="submit" id="submitBtn" class="btn btn-primary w-100 fw-bold rounded-pill shadow-sm py-2" style="background-color: var(--brand-primary); border: none;">
                        <i class="fa-solid fa-paper-plane me-2"></i> إرسال الواجب
                    </button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
@keyframes pulse-red {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
}
.animate-pulse {
    animation: pulse-red 1.5s infinite;
}
</style>

<script>
let mediaRecorder;
let audioChunks = [];
let recordedBlob = null;

async function startRecording() {
    try {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error('SecureContextError');
        }
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        
        mediaRecorder.ondataavailable = event => {
            if (event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };

        mediaRecorder.onstop = () => {
            recordedBlob = new Blob(audioChunks, { type: 'audio/webm' });
            const audioUrl = URL.createObjectURL(recordedBlob);
            const audioPlayback = document.getElementById('audioPlayback');
            audioPlayback.src = audioUrl;
            
            document.getElementById('audioPlaybackContainer').classList.remove('d-none');
            
            // Stop all tracks to release microphone
            stream.getTracks().forEach(track => track.stop());
        };

        audioChunks = [];
        mediaRecorder.start();
        
        document.getElementById('startRecordBtn').classList.add('d-none');
        document.getElementById('stopRecordBtn').classList.remove('d-none');
        document.getElementById('recordingIndicator').classList.remove('d-none');
        document.getElementById('audioPlaybackContainer').classList.add('d-none');
        recordedBlob = null;
        
    } catch (err) {
        console.error("Error accessing microphone:", err);
        let msg = "تعذر الوصول إلى الميكروفون. يرجى التأكد من إعطاء الصلاحيات للمتصفح.";
        let details = "";
        
        if (err.message === 'SecureContextError' || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            msg = "تنبيه أمني: المتصفح يمنع الوصول للميكروفون لأن الرابط الحالي غير آمن (HTTP أو IP غير محلي).";
            details = "لحل المشكلة: يجب فتح الموقع عبر الرابط المحلي http://localhost:8000 أو باستخدام اتصال آمن HTTPS.";
        } else if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
            msg = "تم رفض صلاحية استخدام الميكروفون من قِبل المتصفح!";
            details = "لحل المشكلة: انقر على أيقونة القفل 🔒 أو إعدادات الموقع أعلى يسار شريط العنوان، وقم بتغيير إعداد الميكروفون (Microphone) إلى (Allow / سماح)، ثم قم بتحديث الصفحة.";
        } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
            msg = "لم يتم العثور على أي ميكروفون متصل بجهازك!";
            details = "لحل المشكلة: تأكد من توصيل الميكروفون أو سماعة الرأس بجهازك بشكل صحيح ثم حاول مرة أخرى.";
        } else if (err.name === 'NotReadableError' || err.name === 'TrackStartError') {
            msg = "الميكروفون مشغول أو مستخدم حالياً بواسطة تطبيق آخر!";
            details = "لحل المشكلة: قم بإغلاق أي برامج تستخدم الميكروفون حالياً (مثل Zoom أو Discord أو تبويب آخر) وحاول مجدداً.";
        }
        alert(msg + (details ? "\n\n💡 " + details : ""));
    }
}

function stopRecording() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
        document.getElementById('startRecordBtn').classList.remove('d-none');
        document.getElementById('stopRecordBtn').classList.add('d-none');
        document.getElementById('recordingIndicator').classList.add('d-none');
    }
}

function deleteRecording() {
    recordedBlob = null;
    audioChunks = [];
    document.getElementById('audioPlaybackContainer').classList.add('d-none');
    document.getElementById('audioPlayback').src = '';
}

function submitHomeworkForm(e) {
    e.preventDefault();
    const form = e.target;
    const btn = document.getElementById('submitBtn');
    
    const textAnswer = form.text_answer.value.trim();
    const fileInput = form.file.files[0];
    
    if (!textAnswer && !fileInput && !recordedBlob) {
        Swal.fire({
            icon: 'warning',
            title: 'تنبيه',
            text: 'الرجاء كتابة إجابة، رفع ملف، أو تسجيل صوت.',
            confirmButtonText: 'حسناً'
        });
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> جاري الإرسال...';

    const formData = new FormData(form);
    
    // Append the recorded audio if it exists
    if (recordedBlob) {
        formData.append('file', recordedBlob, 'recitation.webm');
    }

    fetch('/api/homeworks/submit', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'عمل رائع!',
                text: 'تم تسليم الواجب بنجاح!',
                confirmButtonText: 'ممتاز',
                timer: 3000
            }).then(() => {
                switchTab('homework_submit&id=<?= $homework['id'] ?>');
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: data.error || 'حدث خطأ أثناء التسليم',
                confirmButtonText: 'حسناً'
            });
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i> إرسال الواجب';
        }
    })
    .catch(err => {
        console.error(err);
        Swal.fire({
            icon: 'error',
            title: 'خطأ بالاتصال',
            text: 'حدث خطأ في الاتصال بالخادم',
            confirmButtonText: 'حسناً'
        });
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-paper-plane me-2"></i> إرسال الواجب';
    });
}
</script>
