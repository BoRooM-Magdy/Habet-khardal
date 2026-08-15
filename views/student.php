<?php 
require_once __DIR__ . '/../api/db.php';

// Fetch fresh user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id'] ?? 0]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || ($user['role'] !== 'student' && $user['role'] !== 'admin')) {
    header("Location: ?");
    exit;
}

$tab = filter_input(INPUT_GET, 'tab', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'dashboard';
if (isset($_GET['id'])) {
    $tab .= '&id=' . intval($_GET['id']);
}
if (isset($_GET['subject_id'])) {
    $tab .= '&subject_id=' . intval($_GET['subject_id']);
}

require __DIR__ . '/../components/header.php'; 
?>

<div class="d-flex flex-column min-vh-100 bg-light">
    <!-- TopNav -->
    <header class="bg-white d-flex align-items-center px-4 sticky-top shadow-sm" style="height: 70px; z-index: 1040;">
        <!-- Right: Logo -->
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark));">
                <i class="fa-solid fa-church fs-5"></i>
            </div>
            <div>
                <div class="fw-bold fs-5 text-dark" style="font-family: 'Outfit', sans-serif;">مدرسة حبة خردل</div>
                <div class="fw-semibold text-muted" style="font-size: 0.75rem; color: var(--brand-gold) !important;">المنصة التعليمية</div>
            </div>
        </div>

        <!-- Center: Mobile Menu Toggle -->
        <div class="flex-grow-1 d-md-none d-flex justify-content-end">
             <button onclick="toggleSidebar()" class="btn btn-light text-primary border-0"><i class="fa-solid fa-bars fs-5"></i></button>
        </div>

        <!-- Center: Main nav (Desktop) -->
        <nav class="d-none d-md-flex align-items-center gap-2 flex-grow-1 justify-content-center">
            <button onclick="switchTab('dashboard')" class="nav-top-btn btn fw-bold" data-tab="dashboard">الرئيسية</button>
            <button onclick="switchTab('subjects')" class="nav-top-btn btn fw-bold" data-tab="subjects">المواد</button>
            <button onclick="switchTab('achievements')" class="nav-top-btn btn fw-bold" data-tab="achievements">الإنجازات</button>
        </nav>

        <!-- Left: Actions -->
        <div class="d-none d-md-flex align-items-center gap-3 ms-auto">
            <button onclick="switchTab('notifications')" class="btn btn-light position-relative rounded-circle p-2 text-muted hover-primary transition">
                <i class="fa-solid fa-bell fs-5"></i>
                <span id="notifCountBadge" class="position-absolute top-0 start-0 translate-middle badge rounded-pill bg-danger border border-light d-none" style="font-size: 0.6rem;">0</span>
            </button>
            <button onclick="switchTab('chat')" class="btn btn-light rounded-circle p-2 text-muted hover-primary transition">
                <i class="fa-solid fa-message fs-5"></i>
            </button>
            <div class="vr mx-1"></div>
            <div class="d-flex align-items-center gap-2 p-1 rounded-pill bg-white border shadow-sm px-2 cursor-pointer transition hover-shadow" onclick="switchTab('account')">
                <div class="fs-4"><?= $user['gender'] === 'girl' ? '👧' : '👦' ?></div>
                <div class="text-end me-2">
                    <div class="fw-bold text-dark" style="font-size: 0.85rem;"><?= htmlspecialchars($user['child_name'] ?: 'الطالب') ?></div>
                    <div class="fw-semibold" style="font-size: 0.7rem; color: var(--brand-gold);">المستوى <?= $user['level'] ?: 1 ?></div>
                </div>
            </div>
        </div>
    </header>

    <!-- Body Wrapper -->
    <div class="d-flex flex-grow-1 overflow-hidden" style="height: calc(100vh - 70px);">
        
        <!-- Sidebar -->
        <aside id="sidebar" class="bg-white d-flex flex-column border-end flex-shrink-0 transition-transform h-100 z-3" style="width: 260px; overflow-y: auto; position: relative;">
            <div class="p-3">
                <!-- Section 1 -->
                <div class="mb-4">
                    <div class="fw-bold mb-2 text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">التعلم</div>
                    <div class="d-flex flex-column gap-1">
                        <button onclick="switchTab('dashboard')" class="sidebar-btn btn text-start fw-bold d-flex align-items-center gap-3 w-100" data-tab="dashboard">
                            <i class="fa-solid fa-house text-center" style="width: 20px;"></i> الرئيسية
                        </button>
                        <button onclick="switchTab('subjects')" class="sidebar-btn btn text-start fw-bold d-flex align-items-center gap-3 w-100" data-tab="subjects">
                            <i class="fa-solid fa-book-open text-center" style="width: 20px;"></i> المواد الدراسية
                        </button>
                        <button onclick="switchTab('schedule')" class="sidebar-btn btn text-start fw-bold d-flex align-items-center gap-3 w-100" data-tab="schedule">
                            <i class="fa-solid fa-list-check text-center" style="width: 20px;"></i> خطة الأسبوع
                        </button>
                    </div>
                </div>

                <!-- Section 2 -->
                <div class="mb-4">
                    <div class="fw-bold mb-2 text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">الإنجازات</div>
                    <div class="d-flex flex-column gap-1">
                        <button onclick="switchTab('achievements')" class="sidebar-btn btn text-start fw-bold d-flex align-items-center gap-3 w-100" data-tab="achievements">
                            <i class="fa-solid fa-trophy text-center" style="width: 20px;"></i> الإنجازات والمكافآت
                        </button>
                    </div>
                </div>

                <!-- Section 3 -->
                <div class="mb-4">
                    <div class="fw-bold mb-2 text-muted text-uppercase" style="font-size: 0.75rem; letter-spacing: 1px;">التواصل</div>
                    <div class="d-flex flex-column gap-1">
                        <button onclick="switchTab('chat')" class="sidebar-btn btn text-start fw-bold d-flex align-items-center gap-3 w-100" data-tab="chat">
                            <i class="fa-solid fa-message text-center" style="width: 20px;"></i> التواصل مع المعلم
                        </button>
                        <button onclick="switchTab('notifications')" class="sidebar-btn btn text-start fw-bold d-flex align-items-center gap-3 w-100" data-tab="notifications">
                            <i class="fa-solid fa-bell text-center" style="width: 20px;"></i> الإشعارات
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="mt-auto p-3 border-top">
                <button onclick="apiCall('auth/logout', 'POST').then(() => window.location.href = '?')" class="btn btn-light text-danger fw-bold text-start d-flex align-items-center gap-3 w-100 hover-danger">
                    <i class="fa-solid fa-right-from-bracket text-center" style="width: 20px;"></i> تسجيل الخروج
                </button>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-grow-1 overflow-auto bg-light p-3 p-md-4" id="mainContent">
            <div class="d-flex justify-content-center align-items-center h-100">
                <div class="spinner-pulse"></div>
            </div>
        </main>
    </div>
</div>

<style>
/* Custom overrides for student panel */
.sidebar-btn {
    color: #4b5563;
    border-radius: 10px;
    transition: all 0.2s ease;
    border: none;
    background: transparent;
}
.sidebar-btn:hover {
    background: rgba(107, 30, 45, 0.08);
    color: var(--brand-primary);
}
.sidebar-btn.active {
    background: rgba(107, 30, 45, 0.1);
    color: var(--brand-primary);
    box-shadow: inset 4px 0 0 var(--brand-primary);
}

.nav-top-btn {
    color: #6b7280;
    border: none;
    background: transparent;
    border-radius: 8px;
    padding: 0.5rem 1rem;
}
.nav-top-btn:hover {
    background: rgba(107, 30, 45, 0.05);
    color: var(--brand-primary);
}
.nav-top-btn.active {
    color: var(--brand-primary);
    background: rgba(107, 30, 45, 0.1);
}

/* Mobile sidebar logic */
@media (max-width: 768px) {
    #sidebar {
        position: absolute;
        left: 0;
        top: 0;
        transform: translateX(100%);
        box-shadow: -5px 0 15px rgba(0,0,0,0.1);
    }
    #sidebar.show {
        transform: translateX(0);
    }
}
</style>

<script>
let currentTab = '<?= $tab ?>';

// Define apiCall if not loaded yet
async function apiCall(endpoint, method = 'GET', data = null) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const options = {
        method: method,
        headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        }
    };
    if (data && method !== 'GET') options.body = JSON.stringify(data);
    
    const res = await fetch(`api/${endpoint}`, options);
    const result = await res.json();
    if (!res.ok) throw new Error(result.error || 'حدث خطأ');
    return result;
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('show');
}

async function switchTab(tabName) {
    currentTab = tabName;
    
    // Update Browser URL to reflect current tab
    history.pushState(null, '', `?tab=${tabName}`);
    
    // Update active classes
    document.querySelectorAll('.sidebar-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabName);
    });
    document.querySelectorAll('.nav-top-btn').forEach(btn => {
        btn.classList.toggle('active', btn.getAttribute('data-tab') === tabName);
    });
    
    // Close mobile sidebar
    if (window.innerWidth < 768) {
        document.getElementById('sidebar').classList.remove('show');
    }

    const mainContent = document.getElementById('mainContent');
    const skeletonHTML = `
        <div class="skeleton-dashboard">
            <div class="skeleton-box s-header"></div>
            <div class="s-row">
                <div class="skeleton-box s-card"></div>
                <div class="skeleton-box s-card"></div>
                <div class="skeleton-box s-card"></div>
            </div>
            <div class="skeleton-box s-header" style="height: 300px;"></div>
        </div>
    `;
    mainContent.innerHTML = skeletonHTML;
    
    try {
        const response = await fetch(`api/student/tab?name=${tabName}&t=${new Date().getTime()}`);
        if (!response.ok) throw new Error('الصفحة غير موجودة أو قيد التطوير');
        
        // Wait for fetch, then parse HTML
        const html = await response.text();
        mainContent.innerHTML = html;
        
        // Execute inline scripts injected via innerHTML
        Array.from(mainContent.querySelectorAll("script")).forEach(oldScript => {
            const newScript = document.createElement("script");
            Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
            newScript.appendChild(document.createTextNode(oldScript.innerHTML));
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
        
        // Initialize secure media (if any) reliably
        if (typeof initSecureMedia === 'function') {
            initSecureMedia();
        }
        
    } catch (err) {
        mainContent.innerHTML = `<div class="alert alert-danger m-4 text-center fw-bold shadow-sm">${err.message}</div>`;
    }
}

function initSecureMedia() {
    const mediaElements = document.querySelectorAll('.secure-media');
    if (mediaElements.length === 0) return;

    if ('serviceWorker' in navigator) {
        if (navigator.serviceWorker.controller) {
            mediaElements.forEach(function(el) {
                if (el.dataset.src) {
                    el.src = el.dataset.src;
                    if (typeof el.load === 'function') el.load();
                }
            });
        } else {
            navigator.serviceWorker.register('/sw.js', { scope: '/' })
                .then(function(reg) {
                    return navigator.serviceWorker.ready;
                })
                .then(function(registration) {
                    if (!navigator.serviceWorker.controller) {
                        window.location.reload();
                        return;
                    }
                    mediaElements.forEach(function(el) {
                        if (el.dataset.src) {
                            el.src = el.dataset.src;
                            if (typeof el.load === 'function') el.load();
                        }
                    });
                });
        }
    } else {
        // Fallback for browsers without SW
        mediaElements.forEach(function(el) {
            if (el.dataset.src) {
                el.src = el.dataset.src.replace('/sw-media/', '/api/media/');
                if (typeof el.load === 'function') el.load();
            }
        });
    }
}

function updateNotificationsBadge() {
    apiCall('notifications', 'GET').then(res => {
        const badge = document.getElementById('notifCountBadge');
        if (!badge) return;
        if (res && res.unread_count > 0) {
            badge.textContent = res.unread_count;
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }).catch(() => {});
}

document.addEventListener('DOMContentLoaded', () => {
    switchTab(currentTab);
    updateNotificationsBadge();
    setInterval(updateNotificationsBadge, 30000);
});
</script>

<?php require __DIR__ . '/../components/footer.php'; ?>
