<?php
// views/student/tabs/account.php
global $user;
if (!$user) {
    require_once __DIR__ . '/../../../api/db.php';
    $userId = $_SESSION['user_id'] ?? 0;
    $stmt = $pdo->prepare("SELECT u.*, s.name as stage_name FROM users u LEFT JOIN stages s ON u.stage_id = s.id WHERE u.id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

$plan = trim($user['plan'] ?? 'بذرة');
$planBadgeClass = 'bg-success-subtle text-success border-success';
$planName = 'بذرة';
if (strpos($plan, 'شجرة') !== false) {
    $planBadgeClass = 'bg-warning-subtle text-dark border-warning';
    $planName = 'شجرة';
} elseif (strpos($plan, 'نبتة') !== false) {
    $planBadgeClass = 'bg-primary-subtle text-primary border-primary';
    $planName = 'نبتة';
}

$isGirl = ($user['gender'] ?? 'boy') === 'girl';
$studentName = htmlspecialchars($user['child_name'] ?: 'الطالب');
$parentName = htmlspecialchars($user['parent_name'] ?: 'غير محدد');
$userEmail = htmlspecialchars($user['email'] ?? ($user['username'] ?? 'حساب الطالب'));
$stageName = htmlspecialchars($user['stage_name'] ?? 'المرحلة الدراسية');
$phone = htmlspecialchars($user['phone'] ?? '');
?>

<div class="animate-fade-in pb-5">
    <!-- Top Header & Actions -->
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
        <div>
            <h2 class="fs-4 fw-black text-dark m-0">إعدادات الملف الشخصي</h2>
            <p class="small text-secondary fw-semibold mt-1 mb-0">إدارة البيانات الأكاديمية ومعلومات الحساب وباقة الاشتراك</p>
        </div>
        <button onclick="apiCall('auth/logout', 'POST').then(() => window.location.href = '?')" class="btn btn-outline-danger btn-sm fw-bold rounded-pill px-4 py-2 d-flex align-items-center gap-2 transition">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>تسجيل الخروج</span>
        </button>
    </div>

    <div class="row g-4">
        <!-- Right Column: Profile Overview & Academic Card -->
        <div class="col-lg-5">
            <!-- Executive Profile Card -->
            <div class="card border rounded-4 shadow-sm bg-white p-4 mb-4" style="border-color: rgba(0,0,0,0.08) !important;">
                <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold border" 
                         style="width: 64px; height: 64px; font-size: 1.8rem; background: #f8fafc; color: var(--brand-primary); border-color: rgba(123,31,63,0.15) !important;">
                        <?= $isGirl ? '👧' : '👦' ?>
                    </div>
                    <div class="overflow-hidden">
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                            <h3 class="fw-black fs-5 text-dark m-0 text-truncate"><?= $studentName ?></h3>
                            <span class="badge border rounded-pill px-2 py-1 small fw-bold <?= $planBadgeClass ?>" style="font-size: 0.72rem;">
                                باقة <?= $planName ?>
                            </span>
                        </div>
                        <div class="small text-muted text-truncate" style="font-size: 0.82rem;">
                            <?= $userEmail ?>
                        </div>
                    </div>
                </div>

                <!-- Academic Metadata -->
                <div class="d-flex flex-column gap-3 mb-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <span class="text-secondary fw-semibold">المرحلة الدراسية</span>
                        <span class="fw-bold text-dark"><?= $stageName ?></span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between small">
                        <span class="text-secondary fw-semibold">ولي الأمر</span>
                        <span class="fw-bold text-dark"><?= $parentName ?></span>
                    </div>
                    <div class="d-flex align-items-center justify-content-between small">
                        <span class="text-secondary fw-semibold">رقم الهاتف</span>
                        <span class="fw-bold text-dark" dir="ltr"><?= $phone ?: 'غير مسجل' ?></span>
                    </div>
                </div>

                <!-- 4 Key Stats Grid -->
                <div class="row g-2">
                    <div class="col-6">
                        <div class="p-3 rounded-3 text-center border bg-light">
                            <div class="small text-secondary fw-bold mb-1" style="font-size: 0.75rem;">النقاط الأكاديمية</div>
                            <div class="fs-5 fw-black text-dark"><?= number_format((int)($user['points'] ?? 0)) ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3 text-center border bg-light">
                            <div class="small text-secondary fw-bold mb-1" style="font-size: 0.75rem;">النجوم الذهبية</div>
                            <div class="fs-5 fw-black text-warning"><?= number_format((int)($user['stars'] ?? 0)) ?> ★</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3 text-center border bg-light">
                            <div class="small text-secondary fw-bold mb-1" style="font-size: 0.75rem;">المستوى الحالي</div>
                            <div class="fs-5 fw-black text-primary">المستوى <?= (int)($user['level'] ?? 1) ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded-3 text-center border bg-light">
                            <div class="small text-secondary fw-bold mb-1" style="font-size: 0.75rem;">الاستمرارية</div>
                            <div class="fs-5 fw-black text-dark"><?= (int)($user['streak_days'] ?? 1) ?> يوم</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subscription Upgrade Card -->
            <div class="card border rounded-4 shadow-sm bg-white p-4" style="border-color: rgba(0,0,0,0.08) !important;">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="fw-black text-dark fs-6">اشتراك المنصة التعليمية</span>
                    <span class="badge bg-success-subtle text-success border border-success rounded-pill px-2 py-1 small fw-bold">نشط</span>
                </div>
                <p class="small text-secondary fw-semibold mb-3 lh-base" style="font-size: 0.82rem;">
                    أنت مسجل حالياً في <strong>باقة <?= $planName ?></strong>. يمكنك الترقية للباقات الأعلى للوصول الشامل للدروس التفاعلية والتسميع الصوتي والمتابعة الفورية مع المعلم.
                </p>
                <button onclick="switchTab('chat')" class="btn btn-light border btn-sm fw-bold rounded-pill w-100 py-2 text-primary hover-primary transition">
                    <span>طلب ترقية أو استفسار عن الاشتراك</span>
                </button>
            </div>
        </div>

        <!-- Left Column: Edit Profile Form -->
        <div class="col-lg-7">
            <div class="card border rounded-4 shadow-sm bg-white p-4 p-md-5 h-100" style="border-color: rgba(0,0,0,0.08) !important;">
                <div class="d-flex align-items-center gap-2 mb-4 pb-3 border-bottom">
                    <i class="fa-solid fa-user-pen text-primary fs-5"></i>
                    <h3 class="fw-black fs-5 text-dark m-0">تعديل البيانات الشخصية</h3>
                </div>

                <form id="accountProfileForm" onsubmit="handleProfileUpdate(event)">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">اسم الطالب <span class="text-danger">*</span></label>
                            <input type="text" id="profChildName" class="form-control" value="<?= $studentName ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">اسم ولي الأمر <span class="text-danger">*</span></label>
                            <input type="text" id="profParentName" class="form-control" value="<?= $parentName ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">رقم هاتف ولي الأمر</label>
                            <input type="text" id="profPhone" class="form-control" value="<?= $phone ?>" placeholder="مثال: 01xxxxxxxxx">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">جنس الطالب</label>
                            <select id="profGender" class="form-select">
                                <option value="boy" <?= !$isGirl ? 'selected' : '' ?>>ولد 👦</option>
                                <option value="girl" <?= $isGirl ? 'selected' : '' ?>>بنت 👧</option>
                            </select>
                        </div>

                        <div class="col-12 mt-4 pt-3 border-top">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-solid fa-lock text-secondary"></i>
                                <span class="fw-black text-dark small">تغيير كلمة المرور (اختياري)</span>
                            </div>
                            <p class="small text-muted mb-3" style="font-size: 0.8rem;">اترك الحقلين فارغين إذا كنت لا ترغب في تعديل كلمة المرور الحالية.</p>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">كلمة المرور الحالية</label>
                            <input type="password" id="profOldPassword" class="form-control" placeholder="أدخل كلمة المرور الحالية">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold text-dark small">كلمة المرور الجديدة</label>
                            <input type="password" id="profNewPassword" class="form-control" placeholder="8 أحرف على الأقل، حرف كبير وصغير ورقم">
                        </div>

                        <div class="col-12 text-end mt-4 pt-2">
                            <button type="submit" id="profSubmitBtn" class="btn btn-primary fw-bold px-4 py-2 rounded-pill shadow-sm transition">
                                <i class="fa-solid fa-check me-2"></i>
                                <span>حفظ التعديلات</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
async function handleProfileUpdate(e) {
    e.preventDefault();
    const btn = document.getElementById('profSubmitBtn');
    const oldText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> جاري الحفظ...';

    const payload = {
        child_name: document.getElementById('profChildName').value.trim(),
        parent_name: document.getElementById('profParentName').value.trim(),
        phone: document.getElementById('profPhone').value.trim(),
        gender: document.getElementById('profGender').value
    };

    const oldPass = document.getElementById('profOldPassword').value;
    const newPass = document.getElementById('profNewPassword').value;
    if (newPass) {
        if (!oldPass) {
            alert('الرجاء إدخال كلمة المرور الحالية لتغيير كلمة المرور');
            btn.disabled = false;
            btn.innerHTML = oldText;
            return;
        }
        payload.old_password = oldPass;
        payload.password = newPass;
    }

    try {
        const res = await apiCall('auth/update', 'POST', payload);
        if (res && res.success) {
            alert('تم حفظ التعديلات بنجاح!');
            document.getElementById('profOldPassword').value = '';
            document.getElementById('profNewPassword').value = '';
            switchTab('account');
        }
    } catch (err) {
        alert(err.message || 'حدث خطأ أثناء حفظ التعديلات');
    } finally {
        btn.disabled = false;
        btn.innerHTML = oldText;
    }
}
</script>
