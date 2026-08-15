<?php require __DIR__ . '/../components/header.php'; ?>

<!-- Admin AppShell (Premium) -->
<div class="d-flex flex-column min-vh-100 position-relative" style="background-color: #F8F5F2;">
    
    <!-- Decorative Ambient Background Gradients -->
    <div class="position-absolute rounded-circle blur-3xl opacity-20" style="width: 50vw; height: 50vw; background: radial-gradient(circle, var(--brand-primary) 0%, transparent 70%); top: -10vw; right: -10vw; z-index: 0; pointer-events: none;"></div>
    <div class="position-absolute rounded-circle blur-3xl opacity-20" style="width: 40vw; height: 40vw; background: radial-gradient(circle, var(--brand-gold) 0%, transparent 70%); bottom: -10vw; left: -10vw; z-index: 0; pointer-events: none;"></div>

    <!-- Floating Glassmorphic Header -->
    <div class="container-fluid pt-3 px-4 position-sticky top-0 z-3" style="margin-top: 10px;">
        <header class="d-flex align-items-center justify-content-between px-4 py-3 rounded-pill shadow-lg border border-white border-opacity-25" style="background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);">
            
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow" style="width: 45px; height: 45px; background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark));">
                    <i class="fa-solid fa-church fs-5"></i>
                </div>
                <div class="d-none d-md-block">
                    <div class="fw-black fs-5 lh-1" style="color: var(--brand-primary-dark); font-family: 'Outfit', sans-serif; letter-spacing: -0.5px;">مدرسة حبة خردل</div>
                    <div class="fw-bold" style="color: var(--brand-gold); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;">لوحة تحكم الإدارة</div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="d-none d-lg-flex gap-1 flex-grow-1 justify-content-center" id="admin-nav">
                <button data-tab="stats" class="admin-tab btn fw-bold d-flex align-items-center gap-2 px-4 py-2 rounded-pill transition">
                    <i class="fa-solid fa-chart-line"></i> الإحصائيات
                </button>
                <button data-tab="students" class="admin-tab btn fw-bold d-flex align-items-center gap-2 px-4 py-2 rounded-pill transition">
                    <i class="fa-solid fa-users"></i> الطلاب
                </button>
                <button data-tab="homework" class="admin-tab btn fw-bold d-flex align-items-center gap-2 px-4 py-2 rounded-pill transition">
                    <i class="fa-solid fa-book-open"></i> الواجبات
                </button>
                <button data-tab="subjects" class="admin-tab btn fw-bold d-flex align-items-center gap-2 px-4 py-2 rounded-pill transition">
                    <i class="fa-solid fa-book"></i> المواد
                </button>
                <button data-tab="messages" class="admin-tab btn fw-bold d-flex align-items-center gap-2 px-4 py-2 rounded-pill transition">
                    <i class="fa-solid fa-comments"></i> الرسائل
                </button>
            </nav>

            <!-- Mobile Menu Toggle -->
            <div class="d-lg-none d-flex justify-content-end">
                <button class="btn btn-light rounded-circle shadow-sm border-0 d-flex align-items-center justify-content-center text-primary" style="width: 45px; height: 45px;" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminMobileNav">
                    <i class="fa-solid fa-bars fs-5"></i>
                </button>
            </div>
            
            <!-- Logout -->
            <button id="logout-btn" class="btn btn-danger rounded-pill d-none d-lg-flex align-items-center gap-2 fw-bold ms-3 shadow-sm transition hover-scale">
                خروج <i class="fa-solid fa-arrow-left" style="font-size: 0.8rem;"></i>
            </button>
        </header>
    </div>

    <!-- Mobile Offcanvas Nav -->
    <div class="offcanvas offcanvas-end text-bg-dark" tabindex="-1" id="adminMobileNav" style="background-color: var(--brand-primary-dark) !important;">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title fw-bold text-warning">القائمة</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <div class="d-flex flex-column gap-2" id="admin-mobile-nav">
                <button data-tab="stats" class="admin-tab-mobile btn btn-dark text-start fw-bold p-3 active" data-bs-dismiss="offcanvas">
                    <i class="fa-solid fa-chart-line text-center" style="width: 25px;"></i> الإحصائيات
                </button>
                <button data-tab="students" class="admin-tab-mobile btn btn-dark text-start fw-bold p-3" data-bs-dismiss="offcanvas">
                    <i class="fa-solid fa-users text-center" style="width: 25px;"></i> الطلاب
                </button>
                <button data-tab="homework" class="admin-tab-mobile btn btn-dark text-start fw-bold p-3" data-bs-dismiss="offcanvas">
                    <i class="fa-solid fa-book-open text-center" style="width: 25px;"></i> الواجبات
                </button>
                <button data-tab="subjects" class="admin-tab-mobile btn btn-dark text-start fw-bold p-3" data-bs-dismiss="offcanvas">
                    <i class="fa-solid fa-book text-center" style="width: 25px;"></i> المواد
                </button>
                <button data-tab="messages" class="admin-tab-mobile btn btn-dark text-start fw-bold p-3" data-bs-dismiss="offcanvas">
                    <i class="fa-solid fa-comments text-center" style="width: 25px;"></i> الرسائل
                </button>
            </div>
            
            <div class="mt-5 border-top border-secondary pt-3">
                <button class="btn btn-outline-danger w-100 text-start fw-bold p-3" onclick="document.getElementById('logout-btn').click()">
                    <i class="fa-solid fa-right-from-bracket text-center" style="width: 25px;"></i> تسجيل الخروج
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-grow-1 p-3 p-md-5 w-100" style="max-width: 1600px; margin: 0 auto;" id="admin-main-content">
        <div class="d-flex justify-content-center align-items-center h-100 mt-5">
            <div class="spinner-pulse" style="background: var(--brand-primary);"></div>
        </div>
    </main>

</div>

<style>
.blur-3xl { filter: blur(80px); }
.opacity-20 { opacity: 0.2; }

.admin-tab {
    border: none;
    background: transparent;
    color: rgba(107, 30, 45, 0.7) !important;
}
.admin-tab:hover {
    background: rgba(107, 30, 45, 0.05);
    color: var(--brand-primary-dark) !important;
    transform: translateY(-1px);
}
.admin-tab.active {
    background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark)) !important;
    color: white !important;
    box-shadow: 0 10px 20px rgba(107, 30, 45, 0.3);
    transform: translateY(-2px);
}
.hover-scale:hover {
    transform: scale(1.05);
}
.admin-tab-mobile {
    border: none;
    border-radius: 10px;
    background: transparent;
}
.admin-tab-mobile.active {
    background: rgba(255,255,255,0.1) !important;
    color: var(--brand-gold) !important;
}
.transition { transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1); }
</style>

<script>
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

document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.admin-tab, .admin-tab-mobile');
    const mainContent = document.getElementById('admin-main-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', async (e) => {
            e.preventDefault();
            
            const tabName = tab.getAttribute('data-tab');
            
            // Update Browser URL to reflect current tab
            history.pushState(null, '', `?tab=${tabName}`);
            
            // UI Update - Reset all tabs
            document.querySelectorAll('.admin-tab').forEach(t => {
                t.classList.remove('active');
            });
            document.querySelectorAll('.admin-tab-mobile').forEach(t => t.classList.remove('active'));
            
            // Activate current tab everywhere
            document.querySelectorAll(`[data-tab="${tabName}"]`).forEach(t => {
                t.classList.add('active');
            });
            
            // Cleanup any modals moved to body from previous tab
            document.querySelectorAll('body > .modal, body > .modal-backdrop').forEach(m => m.remove());
            
            // Load Content
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
                const urlParams = new URLSearchParams(window.location.search);
                let additionalParams = '';
                if(tabName === urlParams.get('tab')) {
                    for(const [key, value] of urlParams.entries()) {
                        if(key !== 'tab') additionalParams += `&${key}=${value}`;
                    }
                }
                const response = await fetch(`api/admin/tab?name=${tabName}${additionalParams}&t=${new Date().getTime()}`);
                if (!response.ok) throw new Error('فشل تحميل محتوى القسم');
                const html = await response.text();
                mainContent.innerHTML = html;
                
                // Execute inline scripts injected via innerHTML
                Array.from(mainContent.querySelectorAll("script")).forEach(oldScript => {
                    const newScript = document.createElement("script");
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
                
            } catch (err) {
                mainContent.innerHTML = `<div class="alert alert-danger m-4 text-center fw-bold shadow-sm">${err.message}</div>`;
            }
        });
    });

    // Handle Logout
    document.getElementById('logout-btn').addEventListener('click', async () => {
        await apiCall('auth/logout', 'POST');
        window.location.reload();
    });

    // Load initial tab
    const urlParams = new URLSearchParams(window.location.search);
    const initialTabName = urlParams.get('tab') || 'stats';
    const initialTab = document.querySelector(`.admin-tab[data-tab="${initialTabName}"]`);
    
    if (initialTab) {
        initialTab.click();
    } else {
        // Manual load for hidden tabs like 'materials'
        // Cleanup any modals moved to body from previous tab
        document.querySelectorAll('body > .modal, body > .modal-backdrop').forEach(m => m.remove());
        
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
        let additionalParams = '';
        for(const [key, value] of urlParams.entries()) {
            if(key !== 'tab') additionalParams += `&${key}=${value}`;
        }
        fetch(`api/admin/tab?name=${initialTabName}${additionalParams}&t=${new Date().getTime()}`)
            .then(res => {
                if(!res.ok) throw new Error('فشل تحميل محتوى القسم');
                return res.text();
            })
            .then(html => {
                mainContent.innerHTML = html;
                Array.from(mainContent.querySelectorAll("script")).forEach(oldScript => {
                    const newScript = document.createElement("script");
                    Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                    newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                    oldScript.parentNode.replaceChild(newScript, oldScript);
                });
            })
            .catch(err => {
                mainContent.innerHTML = `<div class="alert alert-danger m-4 text-center fw-bold shadow-sm">${err.message}</div>`;
            });
    }
});
</script>

<?php require __DIR__ . '/../components/footer.php'; ?>
