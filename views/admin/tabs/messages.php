<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../../api/db.php';
}

$stmt = $pdo->query("SELECT id, name FROM stages ORDER BY id ASC");
$all_stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
/* Custom Scrollbar for Chat */
.chat-scroll::-webkit-scrollbar { width: 6px; }
.chat-scroll::-webkit-scrollbar-track { background: transparent; }
.chat-scroll::-webkit-scrollbar-thumb { background-color: rgba(123,31,63,0.2); border-radius: 10px; }
.chat-scroll:hover::-webkit-scrollbar-thumb { background-color: rgba(123,31,63,0.4); }

/* Hide scrollbar for horizontal pills but allow scrolling */
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

.hover-bg-light:hover { background-color: #f8f9fa !important; }
.transition { transition: all 0.3s ease; }
</style>

<div class="d-flex flex-column h-100 p-3 pb-5 animate-fade-in" id="adminMessagesContainer" style="min-height: calc(100vh - 120px);">
    <div class="d-flex align-items-center justify-content-between mb-4 flex-shrink-0">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow" style="width: 50px; height: 50px; background: linear-gradient(135deg, #8E44AD, var(--brand-primary));">
                <i class="fa-solid fa-comments fs-5"></i>
            </div>
            <div>
                <h2 class="fs-4 fw-black text-dark m-0" style="letter-spacing: -0.5px;">مركز الرسائل والإعلانات</h2>
                <p class="small fw-bold text-secondary mt-1 m-0 opacity-75">تواصل مع الطلاب وأرسل الإعلانات العامة بسهولة</p>
            </div>
        </div>
    </div>
    
    <!-- Chat Container -->
    <div class="chat-wrapper bg-white rounded-5 shadow-lg d-flex overflow-hidden position-relative" style="border: 1px solid rgba(0,0,0,0.03); height: calc(100vh - 180px);">
        
        <!-- Background Ambient -->
        <div class="position-absolute rounded-circle blur-3xl opacity-10 bg-primary" style="width: 300px; height: 300px; top: -100px; right: -100px; pointer-events: none;"></div>

        <!-- Threads Sidebar -->
        <div class="chat-sidebar bg-light d-flex flex-column flex-shrink-0 position-relative z-1" style="width: 380px; border-inline-end: 1px solid rgba(0,0,0,0.05);">
            <!-- Search -->
            <div class="p-3 border-bottom border-secondary border-opacity-10">
                <div class="position-relative">
                    <i class="fa-solid fa-search position-absolute top-50 end-0 translate-middle-y me-3 text-secondary"></i>
                    <input type="text" id="adminChatSearch" placeholder="ابحث عن طالب..." class="form-control rounded-pill ps-3 pe-5 fw-bold text-dark border-0 shadow-sm bg-white">
                </div>
            </div>
            
            <!-- Filters -->
            <div class="p-3 border-bottom border-secondary border-opacity-10 bg-white bg-opacity-50 d-flex flex-column gap-2">
                <!-- Stages Filter -->
                <div class="mb-1 dropdown w-100">
                    <button class="btn btn-light w-100 rounded-pill text-secondary fw-bold d-flex justify-content-between align-items-center px-3 border-0 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background-color: #f8f9fa;">
                        <span id="selectedStageText">كل المراحل</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" class="bi bi-chevron-down" viewBox="0 0 16 16">
                          <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                        </svg>
                    </button>
                    <ul class="dropdown-menu w-100 border-0 shadow-lg rounded-4 py-2" id="adminChatStageDropdown" style="max-height: 250px; overflow-y: auto;">
                        <li><a class="dropdown-item fw-bold py-2 stage-filter-item bg-light text-primary" href="#" data-value="" data-name="كل المراحل">كل المراحل</a></li>
                    </ul>
                </div>
                <!-- Read Filter -->
                <div class="d-flex gap-2 overflow-auto no-scrollbar" id="adminChatReadFilters">
                    <button class="btn btn-primary rounded-pill btn-sm fw-bold px-3 py-1 border-0 chat-read-filter active" data-filter="all">الكل</button>
                    <button class="btn rounded-pill btn-sm fw-bold px-3 py-1 border-0 chat-read-filter" style="background-color: #e9ecef; color: #6c757d;" data-filter="unread">غير مقروء</button>
                    <button class="btn rounded-pill btn-sm fw-bold px-3 py-1 border-0 chat-read-filter" style="background-color: #e9ecef; color: #6c757d;" data-filter="read">مقروء</button>
                </div>
            </div>

            <!-- Threads List -->
            <div class="flex-grow-1 overflow-auto chat-scroll p-2 d-flex flex-column gap-1" id="adminChatThreads">
                <!-- Thread items injected here -->
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="flex-grow-1 d-flex flex-column bg-white">
            <!-- Header -->
            <div class="border-bottom border-secondary border-opacity-10 p-3 d-flex align-items-center justify-content-between d-none" id="adminChatHeader">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;" id="adminChatHeaderIcon"></div>
                    <div>
                        <h3 class="fw-black fs-6 m-0 cursor-pointer text-primary transition" id="adminChatHeaderName"></h3>
                        <p class="small fw-bold text-secondary m-0 mt-1" id="adminChatHeaderSubtitle" style="font-size: 0.7rem;"></p>
                    </div>
                </div>
                <button class="btn btn-light text-secondary rounded-circle d-none d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" id="adminChatInfoBtn" title="عرض ملف الطالب" data-bs-toggle="modal" data-bs-target="#adminStudentInfoModal">
                    <i class="fa-solid fa-user-graduate"></i>
                </button>
            </div>
            
            <!-- Messages List -->
            <div class="flex-grow-1 overflow-auto chat-scroll p-4 position-relative d-none" id="adminChatHistory" style="background-color: #F8F9FA;">
                <div id="adminBroadcastBg" class="position-absolute top-50 start-50 translate-middle d-none" style="pointer-events: none; opacity: 0.05;">
                    <i class="fa-solid fa-bullhorn" style="font-size: 15rem;"></i>
                </div>
                <!-- Messages injected here -->
            </div>
            
            <!-- Empty State -->
            <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center text-center position-relative z-1" id="adminChatEmptyState">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-4" style="width: 120px; height: 120px;">
                    <i class="fa-solid fa-paper-plane text-primary opacity-50" style="font-size: 3rem;"></i>
                </div>
                <h3 class="fs-4 fw-black text-dark mb-2">اختر محادثة للبدء</h3>
                <p class="small fw-bold text-secondary w-75 m-auto lh-base">يمكنك اختيار طالب للرد على استفساراته، أو النقر على "إعلان عام" لإرسال رسالة جماعية لمستوى دراسي بالكامل.</p>
            </div>
            
            <!-- Input Area -->
            <div class="p-4 bg-white border-top border-secondary border-opacity-10 d-none position-relative z-1" id="adminChatInputArea">
                <div id="adminChatProgress" class="progress mb-2 d-none" style="height: 5px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 100%"></div>
                </div>
                <form id="adminChatForm" class="d-flex gap-2 align-items-center m-0">
                    <input type="file" id="adminChatFileInput" class="d-none" accept="image/*,video/*,audio/*">
                    <button type="button" id="adminChatAttachBtn" class="btn btn-link text-secondary hover-primary text-decoration-none p-0 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;" title="إرفاق ملف">
                        <i class="fa-solid fa-paperclip fs-5"></i>
                    </button>
                    <input type="text" id="adminChatInput" placeholder="اكتب رسالتك..." class="form-control rounded-pill px-4 py-3 fw-bold text-dark border-0 shadow-sm flex-grow-1 bg-light focus-ring focus-ring-primary transition" style="font-size: 0.95rem;">
                    <button type="submit" id="adminChatSubmitBtn" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm flex-shrink-0 transition hover-scale" style="width: 50px; height: 50px;">
                        <i class="fa-solid fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Student Info Modal -->
    <div class="modal fade" id="adminStudentInfoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-black text-primary fs-5">بيانات الطالب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="adminStudentInfoBody">
                    <div class="d-flex justify-content-center p-4">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
(function() {
    let threads = [];
    let messages = [];
    
    let searchQuery = '';
    let readFilter = 'all'; // all, unread, read
    let stageFilter = '';
    
    let selectedUserId = null;
    let selectedUserName = '';
    let selectedUserGender = 'boy';
    let isBroadcastMode = false;
    let broadcastStage = null;
    
    let isUploading = false;
    let pollingInterval = null;

    // DOM Elements
    const container = document.getElementById('adminMessagesContainer');
    const searchInput = document.getElementById('adminChatSearch');
    const readFiltersButtons = document.querySelectorAll('.chat-read-filter');
    const stageDropdownEl = document.getElementById('adminChatStageDropdown');
    const selectedStageText = document.getElementById('selectedStageText');
    
    const threadsEl = document.getElementById('adminChatThreads');
    
    const headerEl = document.getElementById('adminChatHeader');
    const headerIconEl = document.getElementById('adminChatHeaderIcon');
    const headerNameEl = document.getElementById('adminChatHeaderName');
    const headerSubtitleEl = document.getElementById('adminChatHeaderSubtitle');
    const headerInfoBtn = document.getElementById('adminChatInfoBtn');
    
    const historyEl = document.getElementById('adminChatHistory');
    const emptyStateEl = document.getElementById('adminChatEmptyState');
    const broadcastBg = document.getElementById('adminBroadcastBg');
    
    const inputAreaEl = document.getElementById('adminChatInputArea');
    const formEl = document.getElementById('adminChatForm');
    const fileInputEl = document.getElementById('adminChatFileInput');
    const attachBtn = document.getElementById('adminChatAttachBtn');
    const msgInput = document.getElementById('adminChatInput');
    const submitBtn = document.getElementById('adminChatSubmitBtn');
    const progressEl = document.getElementById('adminChatProgress');
    
    const infoBody = document.getElementById('adminStudentInfoBody');

    // Init
    initChat();

    function initChat() {
        fetchThreads();
        setupEventListeners();
        
        pollingInterval = setInterval(() => {
            fetchThreads();
            if (selectedUserId && !isBroadcastMode) {
                fetchMessages(selectedUserId, true);
            }
        }, 3000);
        
        // Cleanup observer
        const observer = new MutationObserver((mutations) => {
            if (!document.body.contains(container)) {
                if (pollingInterval) clearInterval(pollingInterval);
                observer.disconnect();
            }
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    function setupEventListeners() {
        searchInput.addEventListener('input', (e) => {
            searchQuery = e.target.value.trim().toLowerCase();
            renderThreads();
        });

        readFiltersButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                readFiltersButtons.forEach(b => {
                    b.classList.remove('btn-primary', 'active');
                    b.classList.add('btn');
                    b.style.backgroundColor = '#e9ecef';
                    b.style.color = '#6c757d';
                });
                const clicked = e.currentTarget;
                clicked.classList.remove('btn');
                clicked.style.backgroundColor = '';
                clicked.style.color = '';
                clicked.classList.add('btn-primary', 'active');
                
                readFilter = clicked.dataset.filter;
                renderThreads();
            });
        });

        formEl.addEventListener('submit', (e) => {
            e.preventDefault();
            sendMessage();
        });

        attachBtn.addEventListener('click', () => fileInputEl.click());
        fileInputEl.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            await uploadFileDirectly(file);
            e.target.value = '';
        });

        headerInfoBtn.addEventListener('click', loadStudentInfo);
    }

    const allAvailableStages = <?= json_encode($all_stages) ?>;

    function renderLevelFilters() {
        if (!stageDropdownEl) return;
        
        // Populate only once
        if (stageDropdownEl.children.length <= 1) {
            // First item (All) needs to be fixed if it doesn't have text-primary
            const firstItem = stageDropdownEl.querySelector('.stage-filter-item');
            if (firstItem) {
                firstItem.classList.remove('active');
                firstItem.classList.add('bg-light', 'text-primary');
            }

            allAvailableStages.forEach(stg => {
                const li = document.createElement('li');
                li.innerHTML = `<a class="dropdown-item fw-bold py-2 stage-filter-item text-secondary" href="#" data-value="${stg.id}" data-name="${stg.name}">${stg.name}</a>`;
                stageDropdownEl.appendChild(li);
            });
            
            // Add click listeners to all items
            const items = stageDropdownEl.querySelectorAll('.stage-filter-item');
            items.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // Remove active from all
                    items.forEach(i => {
                        i.classList.remove('active', 'bg-light', 'text-primary');
                        i.classList.add('text-secondary');
                    });
                    
                    // Add active to clicked
                    item.classList.add('bg-light', 'text-primary');
                    item.classList.remove('text-secondary');
                    
                    stageFilter = item.getAttribute('data-value');
                    selectedStageText.innerText = item.getAttribute('data-name');
                    
                    renderThreads();
                });
            });
        }
    }

    function getFilteredThreads() {
        let result = threads;
        
        if (searchQuery !== '') {
            result = result.filter(t => t.child_name && t.child_name.toLowerCase().includes(searchQuery));
        }
        
        if (readFilter === 'unread') {
            result = result.filter(t => parseInt(t.unread_count) > 0);
        } else if (readFilter === 'read') {
            result = result.filter(t => parseInt(t.unread_count) === 0 && t.last_message);
        }
        
        if (stageFilter !== '') {
            result = result.filter(t => String(t.stage_id) === stageFilter);
        }

        let broadcasts = [];
        if (searchQuery === '') {
            if (stageFilter !== '') {
                const stg = allAvailableStages.find(s => String(s.id) === stageFilter);
                if (stg) broadcasts.push(createBroadcastObj(stg));
            } else {
                allAvailableStages.forEach(stg => {
                    broadcasts.push(createBroadcastObj(stg));
                });
            }
        }

        return [...broadcasts, ...result];
    }

    function createBroadcastObj(stg) {
        return {
            is_broadcast: true,
            user_id: 'broadcast_' + stg.id,
            stage_id: stg.id,
            child_name: 'إعلان عام - ' + stg.name,
            gender: 'broadcast',
            unread_count: 0,
            last_message: 'إرسال رسالة لكل طلاب هذه المرحلة'
        };
    }

    function renderThreads() {
        const filtered = getFilteredThreads();
        threadsEl.innerHTML = '';
        
        if (filtered.length === 0) {
            threadsEl.innerHTML = '<div class="text-center p-4 text-secondary fw-bold small">لا توجد محادثات.</div>';
            return;
        }

        const fragment = document.createDocumentFragment();
        filtered.forEach(thread => {
            const isSelected = selectedUserId === thread.user_id;
            const btn = document.createElement('button');
            
            let bgClass = isSelected ? 'bg-primary bg-opacity-10 border-primary border-opacity-25' : 'bg-white border-transparent hover-bg-light';
            if (thread.is_broadcast && isSelected) bgClass = 'bg-warning bg-opacity-10 border-warning border-opacity-25';
            else if (thread.is_broadcast) bgClass = 'bg-white border-transparent hover-bg-light';

            btn.className = `btn text-end p-3 rounded-4 border w-100 transition d-flex align-items-center gap-3 position-relative ${bgClass}`;
            
            const iconBg = thread.is_broadcast ? 'bg-warning text-white' : 'bg-primary bg-opacity-10 text-primary';
            const iconContent = thread.is_broadcast ? '<i class="fa-solid fa-bullhorn text-white"></i>' : (thread.gender === 'girl' ? '👧' : '👦');
            
            const unreadBadge = (!thread.is_broadcast && parseInt(thread.unread_count) > 0) 
                ? `<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-2 border-white shadow-sm" style="font-size: 0.65rem;">${thread.unread_count}</span>` 
                : '';
            
            const pinIcon = thread.is_broadcast ? '<i class="fa-solid fa-thumbtack position-absolute text-warning opacity-50" style="left: 15px; font-size: 0.7rem;"></i>' : '';
            
            const titleColor = thread.is_broadcast ? 'text-warning' : 'text-dark';
            const subtitleColor = (!thread.is_broadcast && parseInt(thread.unread_count) > 0) ? 'text-primary' : 'text-secondary';
            
            btn.innerHTML = `
                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm position-relative flex-shrink-0 ${iconBg}" style="width: 45px; height: 45px; font-size: 1.2rem;">
                    ${iconContent}
                    ${unreadBadge}
                </div>
                <div class="flex-grow-1 overflow-hidden pe-2">
                    <h4 class="fw-black text-truncate m-0 fs-6 ${titleColor}">${thread.child_name || 'طالب'}</h4>
                    <p class="fw-bold text-truncate m-0 mt-1 ${subtitleColor}" style="font-size: 0.7rem;">${thread.last_message || 'لا توجد رسائل'}</p>
                </div>
                ${pinIcon}
            `;
            
            btn.onclick = () => openThread(thread.user_id, thread.child_name, thread.gender, thread.is_broadcast, thread.stage_id);
            fragment.appendChild(btn);
        });
        
        threadsEl.appendChild(fragment);
    }

    async function fetchThreads() {
        try {
            const res = await fetch('api/messages?admin_threads=1&t=' + new Date().getTime());
            const data = await res.json();
            
            threads = data.threads || [];
            
            renderLevelFilters();
            
            renderThreads();
        } catch (err) {
            console.error(err);
        }
    }

    async function openThread(userId, userName, gender, isBroadcast = false, stageId = null) {
        selectedUserId = userId;
        selectedUserName = userName;
        selectedUserGender = gender;
        isBroadcastMode = isBroadcast;
        broadcastStage = stageId;
        
        renderThreads();
        
        emptyStateEl.classList.add('d-none');
        headerEl.classList.remove('d-none');
        historyEl.classList.remove('d-none');
        inputAreaEl.classList.remove('d-none');
        
        headerNameEl.innerText = userName;
        if (isBroadcastMode) {
            headerIconEl.className = 'rounded-circle d-flex align-items-center justify-content-center shadow-sm bg-warning text-white';
            headerIconEl.innerHTML = '<i class="fa-solid fa-bullhorn"></i>';
            headerNameEl.classList.remove('text-primary');
            headerNameEl.classList.add('text-warning');
            headerSubtitleEl.innerText = 'ستصل هذه الرسالة لكل طلاب المستوى كرسالة خاصة';
            headerInfoBtn.classList.add('d-none');
            
            submitBtn.classList.remove('bg-primary');
            submitBtn.classList.add('bg-warning', 'text-white');
            msgInput.placeholder = 'اكتب إعلانك ليصل للجميع...';
            
            broadcastBg.classList.remove('d-none');
            
            const stg = allAvailableStages.find(s => String(s.id) === String(stageId));
            const stgName = stg ? stg.name : '';
            messages = [{
                id: 'b1',
                sender_type: 'system',
                text: 'هذه مساحة بث إعلانات عامة. أي رسالة ترسلها هنا سيتم إرسالها بشكل منفصل لكل طالب في ' + stgName,
                created_at: new Date().toISOString()
            }];
            renderMessages();
            
        } else {
            headerIconEl.className = 'rounded-circle d-flex align-items-center justify-content-center shadow-sm bg-primary bg-opacity-10 text-primary';
            headerIconEl.innerHTML = gender === 'girl' ? '👧' : '👦';
            headerNameEl.classList.remove('text-warning');
            headerNameEl.classList.add('text-primary');
            headerSubtitleEl.innerText = 'عرض بيانات الطالب';
            headerSubtitleEl.classList.add('cursor-pointer');
            headerSubtitleEl.onclick = loadStudentInfo;
            headerInfoBtn.classList.remove('d-none');
            
            submitBtn.classList.remove('bg-warning');
            submitBtn.classList.add('bg-primary');
            msgInput.placeholder = 'اكتب ردك هنا...';
            
            broadcastBg.classList.add('d-none');
            
            messages = [];
            renderMessages(); // Clear while loading
            await fetchMessages(userId);
            markAsRead(userId);
        }
    }

    async function fetchMessages(userId, isPolling = false) {
        if (isBroadcastMode) return;
        
        try {
            const res = await fetch(`api/messages?admin_thread=1&user_id=${userId}&t=${new Date().getTime()}`);
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
                    }
                }
            }
        } catch (err) {
            console.error('Chat fetch error:', err);
        }
    }

    function escapeHTML(str) {
        if (!str) return '';
        return String(str).replace(/[&<>'"]/g, function(tag) {
            const charsToReplace = { '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' };
            return charsToReplace[tag] || tag;
        });
    }

    function renderMessages() {
        if (!historyEl) return;
        
        const oldMsgs = historyEl.querySelectorAll('.chat-msg-item');
        oldMsgs.forEach(el => el.remove());

        const fragment = document.createDocumentFragment();

        messages.forEach(msg => {
            const wrapper = document.createElement('div');
            
            if (msg.sender_type === 'system') {
                wrapper.className = 'chat-msg-item d-flex justify-content-center mb-4 position-relative z-1';
                wrapper.innerHTML = `
                    <div class="bg-warning bg-opacity-10 border border-warning text-warning rounded-pill px-4 py-2 shadow-sm text-center fw-bold" style="font-size: 0.75rem; max-width: 80%;">
                        <i class="fa-solid fa-circle-info me-1"></i> ${msg.text}
                    </div>
                `;
            } else {
                const isAdmin = msg.sender_type === 'admin';
                wrapper.className = `chat-msg-item d-flex mb-3 position-relative z-1 ${isAdmin ? 'justify-content-end' : 'justify-content-start'}`;

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
                
                const timeClass = isAdmin ? 'text-white-50 text-end' : 'text-secondary text-start';
                const bubbleClass = isAdmin 
                    ? 'bg-primary text-white border-0' 
                    : 'bg-white text-dark border';
                const radiusClass = isAdmin
                    ? 'border-top-left-radius: 0;'
                    : 'border-top-right-radius: 0;';

                wrapper.innerHTML = `
                    <div class="rounded-4 px-4 py-3 shadow-sm ${bubbleClass}" style="max-width: 75%; ${radiusClass} box-shadow: 0 4px 15px rgba(0,0,0,0.05) !important;">
                        ${mediaHtml}
                        ${textHtml}
                        <div class="fw-bold mt-2 ${timeClass}" style="font-size: 0.65rem;">
                            ${formatTime(msg.created_at)}
                        </div>
                    </div>
                `;
            }
            fragment.appendChild(wrapper);
        });

        historyEl.appendChild(fragment);
    }

    function scrollToBottom() {
        if (historyEl) historyEl.scrollTop = historyEl.scrollHeight;
    }

    function formatTime(datetimeStr) {
        if (!datetimeStr) return '';
        const date = new Date(datetimeStr);
        return date.toLocaleTimeString('ar-EG', { hour: '2-digit', minute: '2-digit' });
    }

    async function markAsRead(userId) {
        try {
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('reader_type', 'admin');
            await fetch('api/messages/read', { method: 'POST', body: formData });
            fetchThreads(); // Refresh unread badges
        } catch (err) {
            console.error(err);
        }
    }

    async function sendMessage(mediaUrl = null, mediaType = null) {
        const text = msgInput.value.trim();
        if (text === '' && !mediaUrl) return;
        
        msgInput.value = ''; 
        
        if (isBroadcastMode) {
            // Optimistic UI for broadcast
            messages.push({
                id: Date.now(),
                sender_type: 'admin',
                text: text,
                media_url: mediaUrl,
                media_type: mediaType,
                created_at: new Date().toISOString()
            });
            renderMessages();
            scrollToBottom();

            const formData = new FormData();
            formData.append('stage_id', broadcastStage);
            formData.append('text', text);
            if (mediaUrl) {
                formData.append('media_url', mediaUrl);
                formData.append('media_type', mediaType);
            }

            try {
                const res = await fetch('api/messages/broadcast', { method: 'POST', body: formData });
                const data = await res.json();
                if (!data.success) Swal.fire('خطأ', 'فشل إرسال الإعلان: ' + (data.error || 'خطأ غير معروف'), 'error');
            } catch (err) {
                console.error(err);
                Swal.fire('خطأ', 'فشل الاتصال بالسيرفر', 'error');
            }
            return;
        }
        
        // Normal message
        const formData = new FormData();
        formData.append('user_id', selectedUserId);
        formData.append('sender_type', 'admin');
        formData.append('text', text);
        if (mediaUrl) {
            formData.append('media_url', mediaUrl);
            formData.append('media_type', mediaType);
        }

        try {
            const res = await fetch('api/messages', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                await fetchMessages(selectedUserId);
                fetchThreads();
                scrollToBottom();
            } else {
                Swal.fire('خطأ', 'فشل الإرسال: ' + (data.error || 'خطأ غير معروف'), 'error');
            }
        } catch (err) {
            console.error(err);
            Swal.fire('خطأ', 'فشل الاتصال بالسيرفر', 'error');
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
                Swal.fire('خطأ', data.error || 'فشل رفع الملف', 'error');
            }
        } catch (err) {
            console.error(err);
            Swal.fire('خطأ', 'فشل رفع الملف', 'error');
        } finally {
            isUploading = false;
            progressEl.classList.add('d-none');
        }
    }

    async function loadStudentInfo() {
        if (isBroadcastMode || !selectedUserId) return;
        
        infoBody.innerHTML = '<div class="d-flex justify-content-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">جاري التحميل...</span></div></div>';
        
        try {
            const res = await fetch(`api/students?profile=1&id=${selectedUserId}&t=${new Date().getTime()}`);
            const data = await res.json();
            const student = data.student;
            
            if(student) {
                const genderIcon = student.gender === 'girl' ? '👧' : '👦';
                const joinDate = student.created_at ? student.created_at.split(' ')[0] : '-';
                
                infoBody.innerHTML = `
                    <div class="d-flex align-items-center gap-3 p-3 bg-light rounded-4 mb-4 border border-secondary border-opacity-10">
                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center flex-shrink-0" style="width: 60px; height: 60px; font-size: 1.5rem;">
                            ${genderIcon}
                        </div>
                        <div>
                            <div class="fw-black fs-5 text-dark">${escapeHTML(student.child_name) || 'بدون اسم'}</div>
                            <div class="small fw-bold text-secondary">${escapeHTML(student.stage_name) || ''}</div>
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="bg-white border rounded-3 p-3 shadow-sm text-center">
                                <div class="fw-bold text-secondary mb-1" style="font-size: 0.7rem;">المستوى</div>
                                <div class="fw-black text-primary">مستوى ${student.level || 1}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-white border rounded-3 p-3 shadow-sm text-center">
                                <div class="fw-bold text-secondary mb-1" style="font-size: 0.7rem;">تاريخ الميلاد</div>
                                <div class="fw-black text-primary" style="font-size: 0.9rem;">${student.dob || '-'}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="bg-white border rounded-3 p-3 shadow-sm d-flex justify-content-between align-items-center">
                                <div class="fw-bold text-secondary" style="font-size: 0.8rem;">رقم ولي الأمر</div>
                                <div class="fw-black text-primary" dir="ltr">${student.parent_phone || '-'}</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="bg-white border rounded-3 p-3 shadow-sm d-flex justify-content-between align-items-center">
                                <div class="fw-bold text-secondary" style="font-size: 0.8rem;">تاريخ الانضمام</div>
                                <div class="fw-black text-primary">${joinDate}</div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                infoBody.innerHTML = '<div class="alert alert-danger fw-bold m-0 text-center">لم يتم العثور على بيانات الطالب.</div>';
            }
        } catch (e) {
            console.error(e);
            infoBody.innerHTML = '<div class="alert alert-danger fw-bold m-0 text-center">حدث خطأ أثناء جلب البيانات.</div>';
        }
    }

})();
</script>
