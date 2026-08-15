<?php require __DIR__ . '/../components/header.php'; ?>

<!-- Flatpickr for beautiful Date UI -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<link rel="stylesheet" href="/assets/css/auth.css">

<div class="d-flex align-items-center justify-content-center min-vh-100 p-3 p-md-4" style="background: url('/assets/images/login-bg.png') center/cover no-repeat; position: relative;">
    <div style="position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(0, 0, 0, 0.35);"></div>
    
    <div class="w-100 transition" id="auth-box-container" style="max-width: 520px; z-index: 1;">
        
        <!-- Header Logo -->
        <div class="text-center mb-3 animate-fade-in">
            <div class="mx-auto mb-2 d-flex align-items-center justify-content-center shadow" style="width: 65px; height: 65px; background: rgba(255,255,255,0.22); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.45); color: var(--brand-primary); border-radius: 50%; font-size: 1.8rem;">
                <i class="fa-solid fa-church text-primary"></i>
            </div>
            <h2 class="fw-bold mb-1 text-white fs-3" style="font-family: 'Outfit', sans-serif; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">مدرسة حبة خردل</h2>
            <p class="fw-semibold text-light small mb-0" style="text-shadow: 0 1px 2px rgba(0,0,0,0.5);">المنصة التعليمية الشاملة</p>
        </div>

        <!-- Auth Panel (Pure Glassmorphism) -->
        <div class="p-4 p-md-5 position-relative animate-fade-in shadow-lg" style="background: rgba(255, 255, 255, 0.16); backdrop-filter: blur(25px); -webkit-backdrop-filter: blur(25px); border: 1.5px solid rgba(255, 255, 255, 0.45); border-radius: 35px;">
            
            <h4 class="mb-4 text-center fw-bold text-white fs-4" id="form-title" style="text-shadow: 0 1px 3px rgba(0,0,0,0.3);">تسجيل الدخول</h4>
            
            <div id="auth-error" class="alert alert-danger d-none fw-bold text-center border-0 shadow-sm rounded-pill py-2 small" role="alert"></div>

            <!-- Login Form -->
            <form id="login-form">
                <div class="mb-3">
                    <label class="form-label fw-bold text-white ms-2">البريد الإلكتروني</label>
                    <input type="email" id="email" class="form-control form-control-lg rounded-pill" style="background: rgba(255,255,255,0.85); border: none; padding-right: 1.5rem; padding-left: 1.5rem;" placeholder="student@example.com" required>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold text-white ms-2">كلمة المرور</label>
                    <div class="position-relative">
                        <input type="password" id="password" class="form-control form-control-lg rounded-pill pe-5" style="background: rgba(255,255,255,0.85); border: none; text-align: left; padding-left: 1.5rem;" dir="ltr" placeholder="••••••••" required>
                        <i class="fa-solid fa-eye position-absolute top-50 end-0 translate-middle-y me-4 text-muted hover-warning" style="cursor: pointer; transition: 0.3s;" onclick="togglePassword('password', this)"></i>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-lg w-100 rounded-pill d-flex align-items-center justify-content-center gap-2 fw-bold text-white mt-2" style="background: var(--brand-primary); border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 4px 15px rgba(107, 30, 45, 0.5); transition: all 0.3s;">
                    تسجيل الدخول <i class="fa-solid fa-arrow-left"></i>
                </button>
                <div class="text-center mt-4">
                    <button type="button" id="show-register" onclick="switchAuthMode('register', event); return false;" class="btn btn-outline-light rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center justify-content-center gap-2 shadow-sm" style="border: 1.5px solid rgba(255,255,255,0.45); background: rgba(255,255,255,0.14); cursor: pointer; color: #fff; width: 100%;">
                        <i class="fa-solid fa-user-plus text-warning"></i>
                        <span>ليس لديك حساب؟ أنشئ حساباً جديداً</span>
                    </button>
                </div>
            </form>

            <!-- Multi-Step Register Form Wizard -->
            <div id="register-wizard-container" class="d-none">
                
                <!-- Steps Progress Indicator -->
                <div class="d-flex align-items-center justify-content-between mb-4 px-1">
                    <div class="text-center flex-grow-1">
                        <div id="step-dot-1" class="rounded-circle mx-auto d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 30px; height: 30px; background: #D4AF37; color: #1a1a1a; border: 2px solid #FFFFFF; font-size: 0.8rem;">1</div>
                        <span class="small text-white fw-bold d-block mt-1" style="font-size: 0.68rem;">البيانات</span>
                    </div>
                    <div class="border-top border-2 border-light border-opacity-50 flex-grow-1" style="margin-top: -16px;"></div>
                    <div class="text-center flex-grow-1">
                        <div id="step-dot-2" class="rounded-circle mx-auto d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 30px; height: 30px; background: rgba(255,255,255,0.3); color: #fff; font-size: 0.8rem;">2</div>
                        <span class="small text-white fw-bold d-block mt-1" style="font-size: 0.68rem;">الباقة</span>
                    </div>
                    <div class="border-top border-2 border-light border-opacity-50 flex-grow-1" style="margin-top: -16px;"></div>
                    <div class="text-center flex-grow-1">
                        <div id="step-dot-3" class="rounded-circle mx-auto d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 30px; height: 30px; background: rgba(255,255,255,0.3); color: #fff; font-size: 0.8rem;">3</div>
                        <span class="small text-white fw-bold d-block mt-1" style="font-size: 0.68rem;">المواد</span>
                    </div>
                    <div class="border-top border-2 border-light border-opacity-50 flex-grow-1" style="margin-top: -16px;"></div>
                    <div class="text-center flex-grow-1">
                        <div id="step-dot-4" class="rounded-circle mx-auto d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 30px; height: 30px; background: rgba(255,255,255,0.3); color: #fff; font-size: 0.8rem;">4</div>
                        <span class="small text-white fw-bold d-block mt-1" style="font-size: 0.68rem;">الدفع</span>
                    </div>
                </div>

                <!-- STEP 1: Personal Data Form -->
                <div id="wizard-step-1" class="wizard-step">
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold text-white ms-2" style="font-size: 0.85rem;">اسم الطالب</label>
                            <input type="text" id="reg-name" class="form-control rounded-pill" style="background: rgba(255,255,255,0.85); border: none;" placeholder="الاسم الثلاثي" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold text-white ms-2" style="font-size: 0.85rem;">ولي الأمر</label>
                            <input type="text" id="reg-parent-name" class="form-control rounded-pill" style="background: rgba(255,255,255,0.85); border: none;" placeholder="اسم ولي الأمر" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-4">
                            <label class="form-label fw-bold text-white ms-2" style="font-size: 0.85rem;">السن</label>
                            <input type="number" id="reg-age" class="form-control rounded-pill text-center fw-bold" style="background: rgba(255,255,255,0.25); border: 1px solid rgba(255,255,255,0.4); color: #D4AF37; cursor: not-allowed;" placeholder="تلقائي" readonly required>
                        </div>
                        <div class="col-8">
                            <label class="form-label fw-bold text-white ms-2" style="font-size: 0.85rem;"><i class="fa-solid fa-calendar-days text-warning me-1"></i> تاريخ الميلاد</label>
                            <input type="text" id="reg-birthdate" class="form-control rounded-pill px-4 fw-bold" style="background: rgba(255,255,255,0.95); border: 2px solid rgba(212, 175, 55, 0.5); color: #6B1E2D; cursor: pointer; box-shadow: 0 4px 15px rgba(0,0,0,0.12);" placeholder="اختر تاريخ الميلاد..." readonly required>
                        </div>
                    </div>

                    <div class="gender-toggle-container position-relative mb-3 bg-white bg-opacity-25 p-1 rounded-pill border border-white border-opacity-25 d-flex" style="z-index: 1;">
                        <input type="radio" class="d-none" name="reg-gender" id="gender-boy" value="boy" checked>
                        <input type="radio" class="d-none" name="reg-gender" id="gender-girl" value="girl">
                        
                        <div class="gender-slider position-absolute bg-white rounded-pill shadow-sm"></div>
                        
                        <label class="flex-grow-1 text-center fw-bold py-1 m-0 gender-label" for="gender-boy">ولد</label>
                        <label class="flex-grow-1 text-center fw-bold py-1 m-0 gender-label" for="gender-girl">بنت</label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold text-white ms-2" style="font-size: 0.85rem;">البريد الإلكتروني</label>
                        <input type="email" id="reg-email" class="form-control rounded-pill" style="background: rgba(255,255,255,0.85); border: none;" placeholder="student@example.com" required>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-6">
                            <label class="form-label fw-bold text-white ms-2" style="font-size: 0.85rem;">كلمة المرور</label>
                            <div class="position-relative">
                                <input type="password" id="reg-password" class="form-control rounded-pill pe-5" style="background: rgba(255,255,255,0.85); border: none; text-align: left;" dir="ltr" placeholder="••••" required>
                                <i class="fa-solid fa-eye position-absolute top-50 end-0 translate-middle-y me-3 text-muted" style="cursor: pointer;" onclick="togglePassword('reg-password', this)"></i>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold text-white ms-2" style="font-size: 0.85rem;">تأكيد المرور</label>
                            <div class="position-relative">
                                <input type="password" id="reg-confirm-password" class="form-control rounded-pill pe-5" style="background: rgba(255,255,255,0.85); border: none; text-align: left;" dir="ltr" placeholder="••••" required>
                                <i class="fa-solid fa-eye position-absolute top-50 end-0 translate-middle-y me-3 text-muted" style="cursor: pointer;" onclick="togglePassword('reg-confirm-password', this)"></i>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" onclick="goToWizardStep(2)" class="btn btn-lg w-100 rounded-pill d-flex align-items-center justify-content-center gap-2 fw-bold text-dark mt-2" style="background: var(--brand-gold); color: #1a1a1a !important; border: none; box-shadow: 0 4px 15px rgba(212, 175, 55, 0.35);">
                        <span>التالي: اختيار باقة الاشتراك</span> <i class="fa-solid fa-arrow-left"></i>
                    </button>
                    <div class="text-center mt-4">
                        <button type="button" id="show-login" onclick="switchAuthMode('login', event); return false;" class="btn btn-outline-light rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center justify-content-center gap-2 shadow-sm" style="border: 1.5px solid rgba(255,255,255,0.45); background: rgba(255,255,255,0.14); cursor: pointer; color: #fff; width: 100%;">
                            <i class="fa-solid fa-right-to-bracket text-warning"></i>
                            <span>لديك حساب بالفعل؟ سجل دخولك</span>
                        </button>
                    </div>
                </div>

                <!-- STEP 2: Plan Selection -->
                <div id="wizard-step-2" class="wizard-step d-none">
                    <p class="text-white text-center small fw-bold mb-3">اختر الباقة الدراسية المناسبة لك في المستوى الأول:</p>

                    <div class="d-flex flex-column gap-3 mb-4">
                        <!-- Seed Plan -->
                        <div class="plan-wizard-card active" onclick="selectWizardPlan('بذرة', this)">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-black fs-5 d-flex align-items-center gap-2"><i class="fa-solid fa-seedling" style="color: var(--brand-gold);"></i> <span>باقة بذرة</span></span>
                                <span class="badge rounded-pill px-3 py-1" style="background: rgba(255,255,255,0.9); color: var(--brand-primary);">المواد الأساسية فقط</span>
                            </div>
                            <p class="small m-0 text-light opacity-75" style="font-size: 0.82rem;">
                                الحصول على جميع المواد الدراسية الأساسية للمستوى الأول.
                            </p>
                        </div>

                        <!-- Plant Plan -->
                        <div class="plan-wizard-card" onclick="selectWizardPlan('نبتة', this)">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-black fs-5 d-flex align-items-center gap-2"><i class="fa-solid fa-leaf" style="color: var(--brand-gold);"></i> <span>باقة نبتة</span></span>
                                <span class="badge rounded-pill px-3 py-1" style="background: rgba(212,175,55,0.25); color: #fff; border: 1px solid rgba(212,175,55,0.5);">أساسية + نصف الفرعية</span>
                            </div>
                            <p class="small m-0 text-light opacity-75" style="font-size: 0.82rem;">
                                المواد الأساسية كاملة بالإضافة لاختيار نصف المواد الفرعية والأنشطة الإضافية.
                            </p>
                        </div>

                        <!-- Tree Plan -->
                        <div class="plan-wizard-card" onclick="selectWizardPlan('شجرة', this)">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="fw-black fs-5 d-flex align-items-center gap-2"><i class="fa-solid fa-tree" style="color: var(--brand-gold);"></i> <span>باقة شجرة</span></span>
                                <span class="badge rounded-pill px-3 py-1" style="background: rgba(107,30,45,0.35); color: #fff; border: 1px solid rgba(107,30,45,0.6);">الشاملة بالكامل</span>
                            </div>
                            <p class="small m-0 text-light opacity-75" style="font-size: 0.82rem;">
                                وصول شامل لجميع المواد الأساسية وجميع المواد الفرعية والأنشطة للمستوى الأول.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" onclick="goToWizardStep(1)" class="btn btn-outline-light rounded-pill px-4 fw-bold">
                            <i class="fa-solid fa-arrow-right me-1"></i> السابق
                        </button>
                        <button type="button" onclick="goToWizardStep(3)" class="btn rounded-pill flex-grow-1 fw-bold text-dark" style="background: var(--brand-gold); color: #1a1a1a !important;">
                            <span>التالي: اختيار المواد</span> <i class="fa-solid fa-arrow-left ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 3: Dynamic Subject Picker (60-30-10 Brand Identity) -->
                <div id="wizard-step-3" class="wizard-step d-none">
                    <div class="text-center mb-3">
                        <span id="selected-plan-badge" class="badge px-3 py-1 rounded-pill small fw-bold mb-1" style="background: rgba(255,255,255,0.25); color: #FFFFFF; border: 1px solid rgba(255,255,255,0.4);">باقة بذرة</span>
                        <p id="subjects-instruction-text" class="small text-white fw-semibold mb-0" style="font-size: 0.84rem; line-height: 1.4;">جاري تحميل مواد المستوى الأول...</p>
                    </div>

                    <div class="subjects-grid-scroll mb-4">
                        <!-- Core Subjects Section -->
                        <div class="mb-3">
                            <h6 class="fw-bold text-primary small mb-2 d-flex align-items-center gap-2" style="color: var(--brand-primary) !important;">
                                <i class="fa-solid fa-layer-group text-primary"></i>
                                <span>المواد الأساسية (إجبارية للمستوى الأول)</span>
                            </h6>
                            <div id="core-subjects-list" class="d-flex flex-column gap-2">
                                <!-- Dynamically loaded -->
                            </div>
                        </div>

                        <!-- Elective Subjects Section -->
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <h6 class="fw-bold text-primary small m-0 d-flex align-items-center gap-2" style="color: var(--brand-primary) !important;">
                                    <i class="fa-solid fa-shapes text-primary"></i>
                                    <span>المواد الفرعية والأنشطة</span>
                                </h6>
                                <span id="electives-counter-badge" class="badge rounded-pill px-3 py-1 small fw-bold" style="background: var(--brand-primary); color: #fff;">0 مختار</span>
                            </div>
                            <div id="elective-subjects-list" class="d-flex flex-column gap-2">
                                <!-- Dynamically loaded -->
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" onclick="goToWizardStep(2)" class="btn btn-outline-light rounded-pill px-3 fw-bold">
                            <i class="fa-solid fa-arrow-right me-1"></i> السابق
                        </button>
                        <button type="button" onclick="goToWizardStep(4)" class="btn btn-gold-luxury rounded-pill flex-grow-1 d-flex align-items-center justify-content-center gap-2">
                            <span>التالي: الفاتورة والدفع</span> <i class="fa-solid fa-arrow-left"></i>
                        </button>
                    </div>
                </div>

                <!-- STEP 4: Payment Gateway & Checkout -->
                <div id="wizard-step-4" class="wizard-step d-none">
                    <div class="text-center mb-3">
                        <span class="badge bg-primary text-white px-3 py-1 rounded-pill small fw-bold mb-1"><i class="fa-solid fa-file-invoice-dollar me-1"></i> الخطوة الأخيرة</span>
                        <p class="small text-white fw-semibold mb-0" style="font-size: 0.82rem;">مراجعة ملخص الباقة وإتمام عملية الدفع وتفعيل الاشتراك</p>
                    </div>

                    <!-- Invoice Card -->
                    <div class="p-3 mb-3 rounded-4 shadow-sm" style="background: #FFFFFF; border: 2px solid var(--brand-primary);">
                        <div class="d-flex align-items-center justify-content-between border-bottom pb-2 mb-2">
                            <span class="fw-black text-dark small">ملخص فاتورة الاشتراك</span>
                            <span id="invoice-plan-badge" class="badge bg-primary text-white small">باقة بذرة</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between small text-secondary mb-1">
                            <span>اسم الطالب:</span>
                            <span id="invoice-student-name" class="fw-bold text-dark">-</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between small text-secondary mb-1">
                            <span>المستوى الدراسي:</span>
                            <span class="fw-bold text-dark">المستوى الأول</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between small text-secondary mb-2">
                            <span>عدد المواد المحددة:</span>
                            <span id="invoice-subjects-count" class="fw-bold text-primary">0 مادة</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                            <span class="fw-black text-dark">الإجمالي المطلوب سداده:</span>
                            <span id="invoice-total-price" class="fw-black fs-5 text-dark" style="color: var(--brand-primary) !important;">500 ج.م</span>
                        </div>
                    </div>

                    <!-- Payment Method Selector -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-white small ms-1 mb-2">اختر طريقة الدفع المفضلة:</label>
                        <div class="row g-2 mb-3">
                            <div class="col-4">
                                <label class="payment-method-card active d-flex flex-column align-items-center justify-content-center p-2 rounded-3 text-center cursor-pointer" onclick="selectPaymentMethod('card', this)">
                                    <i class="fa-solid fa-credit-card fs-5 mb-1 text-primary"></i>
                                    <span class="small fw-bold" style="font-size: 0.72rem;">بطاقة بنكية</span>
                                </label>
                            </div>
                            <div class="col-4">
                                <label class="payment-method-card d-flex flex-column align-items-center justify-content-center p-2 rounded-3 text-center cursor-pointer" onclick="selectPaymentMethod('wallet', this)">
                                    <i class="fa-solid fa-wallet fs-5 mb-1 text-primary"></i>
                                    <span class="small fw-bold" style="font-size: 0.72rem;">محفظة / InstaPay</span>
                                </label>
                            </div>
                            <div class="col-4">
                                <label class="payment-method-card d-flex flex-column align-items-center justify-content-center p-2 rounded-3 text-center cursor-pointer" onclick="selectPaymentMethod('fawry', this)">
                                    <i class="fa-solid fa-building-columns fs-5 mb-1 text-primary"></i>
                                    <span class="small fw-bold" style="font-size: 0.72rem;">فوري Fawry</span>
                                </label>
                            </div>
                        </div>

                        <!-- Card Fields -->
                        <div id="payment-fields-card">
                            <div class="mb-2">
                                <input type="text" class="form-control form-control-sm rounded-pill px-3" style="background: rgba(255,255,255,0.9); border: none;" placeholder="رقم البطاقة (4532 •••• •••• ••••)" dir="ltr">
                            </div>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-sm rounded-pill px-3 text-center" style="background: rgba(255,255,255,0.9); border: none;" placeholder="MM/YY" dir="ltr">
                                </div>
                                <div class="col-6">
                                    <input type="text" class="form-control form-control-sm rounded-pill px-3 text-center" style="background: rgba(255,255,255,0.9); border: none;" placeholder="CVV" dir="ltr">
                                </div>
                            </div>
                        </div>

                        <!-- Wallet Fields -->
                        <div id="payment-fields-wallet" class="d-none">
                            <input type="text" class="form-control form-control-sm rounded-pill px-3" style="background: rgba(255,255,255,0.9); border: none;" placeholder="رقم المحفظة أو عنوان InstaPay (مثلاً 010xxxxxxxx)" dir="ltr">
                        </div>

                        <!-- Fawry Fields -->
                        <div id="payment-fields-fawry" class="d-none text-center p-2 rounded-3 bg-white bg-opacity-75">
                            <span class="small text-dark fw-bold d-block">كود الدفع عبر فوري:</span>
                            <span class="fs-4 fw-black text-primary letter-spacing-2">948-271-035</span>
                            <span class="small text-muted d-block" style="font-size: 0.72rem;">صالح لمدة 48 ساعة</span>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" onclick="goToWizardStep(3)" class="btn btn-outline-light rounded-pill px-3 fw-bold">
                            <i class="fa-solid fa-arrow-right me-1"></i> السابق
                        </button>
                        <button type="button" onclick="submitFinalRegistration()" id="reg-final-btn" class="btn rounded-pill flex-grow-1 fw-bold text-white d-flex align-items-center justify-content-center gap-2" style="background: var(--brand-primary); border: 1px solid rgba(255,255,255,0.3); box-shadow: 0 4px 15px rgba(107,30,45,0.6);">
                            <span>دفع الفاتورة وتفعيل الحساب 🚀</span> <i class="fa-solid fa-lock"></i>
                        </button>
                    </div>
                </div>

            </div>
        </div>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/ar.js"></script>
<script src="/assets/js/auth.js"></script>
