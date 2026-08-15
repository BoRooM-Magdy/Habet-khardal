function switchAuthMode(mode, e) {
    if (e) e.preventDefault();
    const loginForm = document.getElementById('login-form');
    const registerWizard = document.getElementById('register-wizard-container');
    const formTitle = document.getElementById('form-title');
    const errorDiv = document.getElementById('auth-error');

    errorDiv?.classList.add('d-none');
    if (mode === 'register') {
        loginForm?.classList.add('d-none');
        registerWizard?.classList.remove('d-none');
        if (formTitle) formTitle.innerText = 'إنشاء حساب جديد في المدرسة';
        if (typeof goToWizardStep === 'function') {
            goToWizardStep(1);
        }
    } else {
        registerWizard?.classList.add('d-none');
        loginForm?.classList.remove('d-none');
        if (formTitle) formTitle.innerText = 'تسجيل الدخول';
    }
}
window.switchAuthMode = switchAuthMode;

async function localApiCall(endpoint, method, data) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const res = await fetch(`/api/${endpoint}`, {
        method: method,
        headers: { 
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: data ? JSON.stringify(data) : undefined
    });
    const result = await res.json();
    if (!res.ok) throw new Error(result.error || 'حدث خطأ غير متوقع');
    return result;
}

let currentWizardStep = 1;
let selectedPlan = 'بذرة';
let stageSubjects = [];
let maxElectivesAllowed = 0;

function selectWizardPlan(plan, cardEl) {
    selectedPlan = plan;
    document.querySelectorAll('.plan-wizard-card').forEach(el => el.classList.remove('active'));
    cardEl.classList.add('active');
}

async function goToWizardStep(step) {
    const errorDiv = document.getElementById('auth-error');
    errorDiv.classList.add('d-none');

    if (step === 2 && currentWizardStep === 1) {
        // Validate Step 1
        const child_name = document.getElementById('reg-name').value.trim();
        const parent_name = document.getElementById('reg-parent-name').value.trim();
        const birth_date = document.getElementById('reg-birthdate').value;
        const email = document.getElementById('reg-email').value.trim();
        const password = document.getElementById('reg-password').value;
        const confirm_password = document.getElementById('reg-confirm-password').value;

        if (!child_name || !parent_name || !birth_date || !email || !password) {
            errorDiv.textContent = "يرجى ملء جميع البيانات الشخصية وتاريخ الميلاد للمتابعة!";
            errorDiv.classList.remove('d-none');
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'بيانات ناقصة', text: 'يرجى ملء جميع البيانات الشخصية وتحديد تاريخ الميلاد للمتابعة.' });
            }
            return;
        }
        if (password !== confirm_password) {
            errorDiv.textContent = "كلمتا المرور غير متطابقتين!";
            errorDiv.classList.remove('d-none');
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title: 'خطأ', text: 'كلمتا المرور غير متطابقتين!' });
            }
            return;
        }
        if (password.length < 4) {
            errorDiv.textContent = "كلمة المرور قصيرة جداً";
            errorDiv.classList.remove('d-none');
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'warning', title: 'تنبيه', text: 'كلمة المرور يجب أن تكون 4 أحرف على الأقل.' });
            }
            return;
        }
    }

    if (step === 3 || step === 4) {
        document.getElementById('auth-box-container').style.maxWidth = '580px';
        if (step === 3) {
            await loadAndRenderStageSubjects();
        } else if (step === 4) {
            populateInvoiceSummary();
        }
    } else {
        document.getElementById('auth-box-container').style.maxWidth = '520px';
    }

    document.querySelectorAll('.wizard-step').forEach(el => el.classList.add('d-none'));
    document.getElementById(`wizard-step-${step}`).classList.remove('d-none');

    // Update 4 Dots
    for (let i = 1; i <= 4; i++) {
        const dot = document.getElementById(`step-dot-${i}`);
        if (!dot) continue;
        if (i === step) {
            dot.style.background = '#D4AF37';
            dot.style.color = '#1a1a1a';
            dot.style.border = '2px solid #FFFFFF';
        } else if (i < step) {
            dot.style.background = '#6B1E2D';
            dot.style.color = '#FFFFFF';
            dot.style.border = '2px solid #FFFFFF';
        } else {
            dot.style.background = 'rgba(255,255,255,0.3)';
            dot.style.color = '#FFFFFF';
            dot.style.border = '1px solid rgba(255,255,255,0.4)';
        }
    }

    currentWizardStep = step;
}

function selectPaymentMethod(method, cardEl) {
    document.querySelectorAll('.payment-method-card').forEach(el => {
        el.style.background = 'transparent';
        el.style.border = '1px solid transparent';
    });
    cardEl.style.background = '#FFFFFF';
    cardEl.style.border = '2px solid #6B1E2D';

    document.getElementById('payment-fields-card').classList.add('d-none');
    document.getElementById('payment-fields-wallet').classList.add('d-none');
    document.getElementById('payment-fields-fawry').classList.add('d-none');

    if (method === 'card') {
        document.getElementById('payment-fields-card').classList.remove('d-none');
    } else if (method === 'wallet') {
        document.getElementById('payment-fields-wallet').classList.remove('d-none');
    } else if (method === 'fawry') {
        document.getElementById('payment-fields-fawry').classList.remove('d-none');
    }
}

function populateInvoiceSummary() {
    const name = document.getElementById('reg-name').value.trim() || 'طالب جديد';
    document.getElementById('invoice-student-name').textContent = name;
    document.getElementById('invoice-plan-badge').textContent = `باقة ${selectedPlan}`;

    let price = '500 ج.م';
    if (selectedPlan === 'نبتة') price = '850 ج.م';
    if (selectedPlan === 'شجرة') price = '1,200 ج.م';
    document.getElementById('invoice-total-price').textContent = price;

    // Calculate selected subjects count
    let count = 0;
    stageSubjects.forEach(s => {
        if (Number(s.is_core) === 1) count++;
    });
    if (selectedPlan === 'شجرة') {
        stageSubjects.forEach(s => {
            if (Number(s.is_core) === 0) count++;
        });
    } else if (selectedPlan === 'نبتة') {
        count += document.querySelectorAll('.subject-elective-chk:checked').length;
    }
    document.getElementById('invoice-subjects-count').textContent = `${count} مادة دراسية`;
}

function getSubjectIcon(name) {
    if (name.includes('طقس')) return '<i class="fa-solid fa-cloud-sun text-warning fs-5"></i>';
    if (name.includes('قداس')) return '<i class="fa-solid fa-church text-warning fs-5"></i>';
    if (name.includes('لحان') || name.includes('ألحان')) return '<i class="fa-solid fa-music text-warning fs-5"></i>';
    if (name.includes('شطرنج')) return '<i class="fa-solid fa-chess text-primary fs-5"></i>';
    if (name.includes('موسيقى')) return '<i class="fa-solid fa-guitar text-primary fs-5"></i>';
    if (name.includes('ترانيم')) return '<i class="fa-solid fa-book-bible text-primary fs-5"></i>';
    return '<i class="fa-solid fa-bookmark text-primary fs-5"></i>';
}

async function loadAndRenderStageSubjects() {
    const errorDiv = document.getElementById('auth-error');
    try {
        if (!stageSubjects.length) {
            const res = await localApiCall('subjects?stage_id=1', 'GET');
            stageSubjects = Array.isArray(res) ? res : (res.subjects || []);
        }
    } catch (err) {
        errorDiv.textContent = "تعذر تحميل قائمة المواد: " + err.message;
        errorDiv.classList.remove('d-none');
        return;
    }

    const coreList = document.getElementById('core-subjects-list');
    const electiveList = document.getElementById('elective-subjects-list');
    const badgeEl = document.getElementById('selected-plan-badge');
    const instructionEl = document.getElementById('subjects-instruction-text');
    const counterBadge = document.getElementById('electives-counter-badge');

    coreList.innerHTML = '';
    electiveList.innerHTML = '';

    badgeEl.textContent = `باقة ${selectedPlan}`;

    const coreSubjects = stageSubjects.filter(s => Number(s.is_core) === 1);
    const electiveSubjects = stageSubjects.filter(s => Number(s.is_core) === 0);

    // Render Core Subjects (60-30-10 Brand Identity)
    coreSubjects.forEach(s => {
        coreList.innerHTML += `
            <label class="subject-wizard-card selected">
                <div class="d-flex align-items-center gap-3">
                    <div class="custom-luxury-chk"><i class="fa-solid fa-check small"></i></div>
                    ${getSubjectIcon(s.name)}
                    <span class="fw-bold text-dark small">${s.name}</span>
                </div>
                <span class="badge rounded-pill px-2 py-1 small" style="background: rgba(107, 30, 45, 0.1); color: #6B1E2D; border: 1px solid rgba(107, 30, 45, 0.25);"><i class="fa-solid fa-star small me-1"></i> أساسية</span>
            </label>
        `;
    });

    if (selectedPlan === 'بذرة') {
        maxElectivesAllowed = 0;
        instructionEl.textContent = "باقة (بذرة) تمنحك المواد الأساسية للمستوى الأول. يمكنك الترقية لإضافة المواد الفرعية.";
        counterBadge.textContent = "غير متاح في بذرة";
        electiveSubjects.forEach(s => {
            electiveList.innerHTML += `
                <label class="subject-wizard-card locked">
                    <div class="d-flex align-items-center gap-3">
                        <div class="custom-luxury-chk"></div>
                        ${getSubjectIcon(s.name)}
                        <span class="text-secondary small fw-semibold">${s.name}</span>
                    </div>
                    <span class="badge rounded-pill px-2 py-1 small" style="background: rgba(0,0,0,0.06); color: #666; border: 1px solid rgba(0,0,0,0.1);"><i class="fa-solid fa-lock small me-1"></i> تطلب نبتة أو شجرة</span>
                </label>
            `;
        });
    } else if (selectedPlan === 'نبتة') {
        maxElectivesAllowed = Math.ceil(electiveSubjects.length / 2);
        instructionEl.textContent = `باقة (نبتة) تتيح لك المواد الأساسية + اختيار حتى (${maxElectivesAllowed}) مواد فرعية للمستوى الأول.`;
        counterBadge.textContent = `0 من ${maxElectivesAllowed} مختار`;

        electiveSubjects.forEach(s => {
            electiveList.innerHTML += `
                <label class="subject-wizard-card elective-item" id="el-item-${s.id}">
                    <div class="d-flex align-items-center gap-3">
                        <input type="checkbox" class="d-none subject-elective-chk" value="${s.id}" onchange="handleElectiveChange()">
                        <div class="custom-luxury-chk"><i class="fa-solid fa-check small"></i></div>
                        ${getSubjectIcon(s.name)}
                        <span class="fw-bold text-dark small">${s.name}</span>
                    </div>
                    <span class="badge rounded-pill px-2 py-1 small" style="background: rgba(107, 30, 45, 0.08); color: #6B1E2D; border: 1px solid rgba(107, 30, 45, 0.2);"><i class="fa-solid fa-plus small me-1"></i> فرعية</span>
                </label>
            `;
        });
    } else {
        maxElectivesAllowed = electiveSubjects.length;
        instructionEl.textContent = "باقة (شجرة) الشاملة تمنحك جميع المواد الأساسية والفرعية والأنشطة للمستوى الأول.";
        counterBadge.textContent = "كل المواد مشمولة";

        electiveSubjects.forEach(s => {
            electiveList.innerHTML += `
                <label class="subject-wizard-card selected">
                    <div class="d-flex align-items-center gap-3">
                        <div class="custom-luxury-chk"><i class="fa-solid fa-check small"></i></div>
                        ${getSubjectIcon(s.name)}
                        <span class="fw-bold text-dark small">${s.name}</span>
                    </div>
                    <span class="badge rounded-pill px-2 py-1 small" style="background: rgba(107, 30, 45, 0.12); color: #6B1E2D; border: 1px solid rgba(107, 30, 45, 0.3);"><i class="fa-solid fa-check small me-1"></i> مشمول بالشجرة</span>
                </label>
            `;
        });
    }
}

function handleElectiveChange() {
    const chks = document.querySelectorAll('.subject-elective-chk');
    const checked = Array.from(chks).filter(c => c.checked);
    const count = checked.length;

    document.getElementById('electives-counter-badge').textContent = `${count} من ${maxElectivesAllowed} مختار`;

    chks.forEach(chk => {
        const itemEl = document.getElementById(`el-item-${chk.value}`);
        if (chk.checked) {
            itemEl?.classList.add('selected');
        } else {
            itemEl?.classList.remove('selected');
        }

        if (count >= maxElectivesAllowed && !chk.checked) {
            chk.disabled = true;
            itemEl?.classList.add('locked');
        } else {
            chk.disabled = false;
            itemEl?.classList.remove('locked');
        }
    });
}

async function submitFinalRegistration() {
    const errorDiv = document.getElementById('auth-error');
    errorDiv.classList.add('d-none');

    const child_name = document.getElementById('reg-name').value.trim();
    const parent_name = document.getElementById('reg-parent-name').value.trim();
    const age = document.getElementById('reg-age').value;
    const birth_date = document.getElementById('reg-birthdate').value;
    const gender = document.querySelector('input[name="reg-gender"]:checked').value;
    const email = document.getElementById('reg-email').value.trim();
    const password = document.getElementById('reg-password').value;

    // Gather selected subjects (All core + chosen electives)
    const subject_ids = [];
    stageSubjects.forEach(s => {
        if (Number(s.is_core) === 1) {
            subject_ids.push(s.id);
        }
    });

    if (selectedPlan === 'شجرة') {
        stageSubjects.forEach(s => {
            if (Number(s.is_core) === 0) subject_ids.push(s.id);
        });
    } else if (selectedPlan === 'نبتة') {
        document.querySelectorAll('.subject-elective-chk:checked').forEach(c => {
            subject_ids.push(Number(c.value));
        });
    }

    const btn = document.getElementById('reg-final-btn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري تأكيد الدفع وتفعيل الاشتراك...';
    btn.disabled = true;

    try {
        const data = await localApiCall('auth/register', 'POST', {
            child_name,
            parent_name,
            age,
            birth_date,
            gender,
            email,
            password,
            plan: selectedPlan,
            subject_ids: subject_ids
        });

        if (data.success) {
            window.location.reload();
        }
    } catch (error) {
        errorDiv.textContent = error.message;
        errorDiv.classList.remove('d-none');
        btn.innerHTML = originalText;
        btn.disabled = false;
    }
}

function calculateAgeFromDate(dateStr) {
    if (!dateStr) {
        document.getElementById('reg-age').value = '';
        return;
    }
    const birthDate = new Date(dateStr);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const m = today.getMonth() - birthDate.getMonth();
    if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    document.getElementById('reg-age').value = age >= 0 ? age : 0;
}

function togglePassword(inputId, iconElement) {
    const input = document.getElementById(inputId);
    if (input.type === "password") {
        input.type = "text";
        iconElement.classList.remove('fa-eye');
        iconElement.classList.add('fa-eye-slash');
    } else {
        input.type = "password";
        iconElement.classList.remove('fa-eye-slash');
        iconElement.classList.add('fa-eye');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const birthInput = document.getElementById('reg-birthdate');
    if (birthInput) {
        birthInput.addEventListener('change', (e) => calculateAgeFromDate(e.target.value));
        birthInput.addEventListener('input', (e) => calculateAgeFromDate(e.target.value));
    }

    const loginForm = document.getElementById('login-form');
    const errorDiv = document.getElementById('auth-error');

    const showRegBtn = document.getElementById('show-register');
    if (showRegBtn) {
        showRegBtn.addEventListener('click', (e) => switchAuthMode('register', e));
    }

    const showLoginBtn = document.getElementById('show-login');
    if (showLoginBtn) {
        showLoginBtn.addEventListener('click', (e) => switchAuthMode('login', e));
    }

    if (loginForm) {
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            errorDiv.classList.add('d-none');
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const btn = loginForm.querySelector('button');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> جاري الدخول...';
            btn.disabled = true;

            try {
                const data = await localApiCall('auth/login', 'POST', { email, password });
                if (data.success) window.location.reload();
            } catch (error) {
                errorDiv.textContent = error.message;
                errorDiv.classList.remove('d-none');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    }

    if (typeof flatpickr !== 'undefined') {
        flatpickr("#reg-birthdate", {
            locale: "ar",
            dateFormat: "Y-m-d",
            disableMobile: "true",
            allowInput: false,
            onChange: function(selectedDates, dateStr) {
                if (typeof calculateAgeFromDate === 'function') {
                    calculateAgeFromDate(dateStr);
                }
            }
        });
    }
});
