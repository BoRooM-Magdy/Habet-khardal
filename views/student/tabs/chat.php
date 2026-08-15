<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../../api/db.php';
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($user) || !$user) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'] ?? 0]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (!$user) {
    echo "<div class='alert alert-danger fw-bold m-4'>الرجاء تسجيل الدخول.</div>";
    exit;
}
?>

<div class="d-flex flex-column h-100 p-3 pb-5 animate-fade-in" id="studentChatContainer" style="min-height: calc(100vh - 120px);">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-shrink-0">
        <div>
            <h2 class="fs-4 fw-black text-primary m-0">التواصل مع المعلم</h2>
            <p class="small fw-bold text-secondary mt-1 m-0">تحدث مباشرة مع معلميك واستفسر عن دروسك</p>
        </div>
    </div>

    <!-- Chat Box -->
    <div class="bg-white rounded-4 border shadow-sm d-flex flex-column flex-grow-1 overflow-hidden position-relative" style="border-color: rgba(123,31,63,0.1) !important;">
        <!-- Chat Header -->
        <div class="border-bottom p-3 d-flex align-items-center gap-3 flex-shrink-0 bg-primary bg-opacity-10">
            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                <i class="fa-solid fa-church"></i>
            </div>
            <div>
                <div class="fw-black text-primary fs-6 lh-1">إدارة المدرسة (المعلم)</div>
                <div class="small fw-bold text-success d-flex align-items-center gap-1 mt-1" style="font-size: 0.75rem;">
                    <div class="rounded-circle bg-success" style="width: 6px; height: 6px;"></div> متصل الآن
                </div>
            </div>
        </div>

        <!-- Chat Messages -->
        <div class="flex-grow-1 overflow-auto p-4 d-flex flex-column gap-3" id="studentChatHistory" style="background-color: #F8F9FA;">
            <div class="text-center fw-bold text-secondary my-3 text-uppercase" style="font-size: 0.7rem; letter-spacing: 1px;">سجل المحادثة</div>
            <!-- Messages will be injected here via JS -->
            <div id="studentChatEmptyState" class="d-none">
                <div class="d-flex justify-content-start">
                    <div class="bg-white border rounded-4 px-4 py-3 shadow-sm" style="border-top-right-radius: 0; max-width: 80%;">
                        <p class="small fw-bold text-dark m-0 lh-base">أهلاً بك يا <?= htmlspecialchars($user['child_name'] ?? '') ?>! أنا هنا للإجابة على أي استفسار يخص المواد أو الواجبات. كيف يمكنني مساعدتك اليوم؟</p>
                        <div class="fw-bold text-secondary mt-2 text-start" style="font-size: 0.65rem;">الآن</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Input -->
        <div class="p-3 bg-white border-top flex-shrink-0">
            <!-- Upload Progress -->
            <div id="studentChatProgress" class="progress mb-2 d-none" style="height: 5px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 100%"></div>
            </div>

            <form id="studentChatForm" class="d-flex gap-2 align-items-center m-0">
                <input type="file" id="studentChatFileInput" class="d-none" accept="image/*,video/*,audio/*">
                
                <button type="button" id="studentChatAttachBtn" class="btn btn-link text-secondary hover-primary text-decoration-none p-0 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;" title="إرفاق ملف">
                    <i class="fa-solid fa-paperclip fs-5"></i>
                </button>
                
                <!-- Voice Record Button -->
                <button type="button" id="studentChatRecordBtn" class="btn btn-link text-secondary hover-primary text-decoration-none p-0 d-flex align-items-center justify-content-center rounded-circle flex-shrink-0 transition" style="width: 40px; height: 40px;" title="تسجيل صوتي">
                    <i class="fa-solid fa-microphone fs-5"></i>
                </button>

                <input type="text" id="studentChatInput" placeholder="اكتب رسالتك هنا..." class="form-control rounded-pill px-4 fw-bold text-dark border-0 shadow-none flex-grow-1" style="background-color: #F8F9FA;">
                
                <button type="submit" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm flex-shrink-0" style="width: 45px; height: 45px;">
                    <i class="fa-solid fa-paper-plane"></i>
                </button>
            </form>
        </div>
    </div>
</div>

<script>
(function() {
    const userId = <?= $user['id'] ?? 0 ?>;
    let messages = [];
    let pollingInterval = null;
    let isUploading = false;
    let isRecording = false;
    let mediaRecorder = null;
    let audioChunks = [];

    const historyEl = document.getElementById('studentChatHistory');
    const emptyStateEl = document.getElementById('studentChatEmptyState');
    const formEl = document.getElementById('studentChatForm');
    const inputEl = document.getElementById('studentChatInput');
    const fileInputEl = document.getElementById('studentChatFileInput');
    const attachBtn = document.getElementById('studentChatAttachBtn');
    const recordBtn = document.getElementById('studentChatRecordBtn');
    const progressEl = document.getElementById('studentChatProgress');

    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, function(tag) {
            const charsToReplace = { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' };
            return charsToReplace[tag] || tag;
        });
    }

    function formatTime(datetimeStr) {
        if (!datetimeStr) return '';
        const date = new Date(datetimeStr);
        return date.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
    }

    function renderMessages() {
        if (!historyEl) return;
        
        // Clear old messages (keep the header and empty state)
        const oldMsgs = historyEl.querySelectorAll('.chat-msg-item');
        oldMsgs.forEach(el => el.remove());

        if (messages.length === 0) {
            emptyStateEl.classList.remove('d-none');
            return;
        } else {
            emptyStateEl.classList.add('d-none');
        }

        const fragment = document.createDocumentFragment();

        messages.forEach(msg => {
            const isStudent = msg.sender_type === 'student';
            const wrapper = document.createElement('div');
            wrapper.className = `chat-msg-item d-flex mb-3 ${isStudent ? 'justify-content-end' : 'justify-content-start'}`;

            let mediaHtml = '';
            const safeMediaUrl = escapeHTML(msg.media_url);
            if (msg.media_type === 'image' && msg.media_url) {
                mediaHtml = `<img src="${safeMediaUrl}" class="img-fluid rounded-3 mb-2 w-100" style="max-height: 200px; object-fit: cover;">`;
            } else if (msg.media_type === 'video' && msg.media_url) {
                mediaHtml = `<video src="${safeMediaUrl}" controls class="w-100 rounded-3 mb-2" style="max-height: 200px;"></video>`;
            } else if (msg.media_type === 'audio' && msg.media_url) {
                mediaHtml = `<audio src="${safeMediaUrl}" controls class="w-100 mb-2" style="height: 40px;"></audio>`;
            }

            const textHtml = msg.text ? `<p class="small fw-bold lh-base m-0" style="white-space: pre-wrap;">${escapeHTML(msg.text)}</p>` : '';
            
            const timeClass = isStudent ? 'text-white-50 text-end' : 'text-secondary text-start';
            const bubbleClass = isStudent 
                ? 'bg-primary text-white border-0' 
                : 'bg-white text-dark border';
            const radiusClass = isStudent
                ? 'border-top-left-radius: 0;'
                : 'border-top-right-radius: 0;';

            wrapper.innerHTML = `
                <div class="rounded-4 px-4 py-3 shadow-sm ${bubbleClass}" style="max-width: 85%; ${radiusClass}">
                    ${mediaHtml}
                    ${textHtml}
                    <div class="fw-bold mt-2 ${timeClass}" style="font-size: 0.65rem;">
                        ${formatTime(msg.created_at)}
                    </div>
                </div>
            `;
            fragment.appendChild(wrapper);
        });

        historyEl.appendChild(fragment);
    }

    function scrollToBottom() {
        if (historyEl) {
            historyEl.scrollTop = historyEl.scrollHeight;
        }
    }

    async function fetchMessages(isPolling = false) {
        try {
            const res = await fetch(`api/messages?user_id=${userId}&t=${new Date().getTime()}`);
            const data = await res.json();
            
            if (!isPolling || messages.length !== data.messages.length) {
                messages = data.messages || [];
                renderMessages();
                if (!isPolling) {
                    setTimeout(scrollToBottom, 50);
                } else {
                    const isAtBottom = historyEl.scrollHeight - historyEl.scrollTop <= historyEl.clientHeight + 100;
                    if(isAtBottom) {
                        setTimeout(scrollToBottom, 50);
                        markAsRead();
                    }
                }
            }
        } catch (err) {
            console.error('Chat fetch error:', err);
        }
    }

    async function markAsRead() {
        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('reader_type', 'student');
            await fetch('api/messages/read', { method: 'POST', body: formData });
        } catch (err) {
            console.error(err);
        }
    }

    async function sendMessage(mediaUrl = null, mediaType = null) {
        const text = inputEl.value.trim();
        if (text === '' && !mediaUrl) return;
        
        const formData = new FormData();
        formData.append('user_id', userId);
        formData.append('sender_type', 'student');
        formData.append('text', text);
        if (mediaUrl) {
            formData.append('media_url', mediaUrl);
            formData.append('media_type', mediaType);
        }

        inputEl.value = ''; 
        
        try {
            const res = await fetch('api/messages', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                await fetchMessages();
                scrollToBottom();
            } else {
                alert('فشل الإرسال: ' + (data.message || data.error || 'خطأ غير معروف'));
            }
        } catch (err) {
            console.error(err);
            alert('فشل الاتصال بالسيرفر');
        }
    }

    async function uploadFileDirectly(file) {
        progressEl.classList.remove('d-none');
        isUploading = true;
        
        const formData = new FormData();
        formData.append('media', file);

        try {
            const res = await fetch('api/messages/upload', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                let type = 'image';
                if (file.type.startsWith('video/')) type = 'video';
                if (file.type.startsWith('audio/')) type = 'audio';
                await sendMessage(data.url, type);
            } else {
                alert(data.error || 'فشل رفع الملف');
            }
        } catch (err) {
            console.error(err);
            alert('فشل رفع الملف');
        } finally {
            isUploading = false;
            progressEl.classList.add('d-none');
        }
    }

    async function toggleRecording() {
        if (isRecording) {
            if (mediaRecorder) mediaRecorder.stop();
            isRecording = false;
            recordBtn.classList.remove('text-danger', 'bg-danger', 'bg-opacity-10');
            recordBtn.classList.add('text-secondary');
            recordBtn.innerHTML = '<i class="fa-solid fa-microphone fs-5"></i>';
        } else {
            try {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('SecureContextError');
                }
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                mediaRecorder = new MediaRecorder(stream);
                audioChunks = [];
                
                mediaRecorder.ondataavailable = e => {
                    if (e.data.size > 0) audioChunks.push(e.data);
                };
                
                mediaRecorder.onstop = async () => {
                    const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                    stream.getTracks().forEach(track => track.stop());
                    const file = new File([audioBlob], 'voice_message.webm', { type: 'audio/webm' });
                    await uploadFileDirectly(file);
                };
                
                mediaRecorder.start();
                isRecording = true;
                recordBtn.classList.remove('text-secondary');
                recordBtn.classList.add('text-danger', 'bg-danger', 'bg-opacity-10');
                recordBtn.innerHTML = '<i class="fa-solid fa-stop fs-5"></i>';
            } catch (err) {
                console.error("Error accessing microphone in chat:", err);
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
    }

    // Event Listeners
    if (formEl) {
        formEl.addEventListener('submit', (e) => {
            e.preventDefault();
            sendMessage();
        });
    }

    if (attachBtn && fileInputEl) {
        attachBtn.addEventListener('click', () => fileInputEl.click());
        fileInputEl.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            await uploadFileDirectly(file);
            e.target.value = '';
        });
    }

    if (recordBtn) {
        recordBtn.addEventListener('click', toggleRecording);
    }

    // Init
    fetchMessages();
    markAsRead();
    pollingInterval = setInterval(() => fetchMessages(true), 3000);

    // Cleanup on detached/switched
    const container = document.getElementById('studentChatContainer');
    if (container) {
        const observer = new MutationObserver((mutations) => {
            if (!document.body.contains(container)) {
                if (pollingInterval) clearInterval(pollingInterval);
                observer.disconnect();
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

})();
</script>
