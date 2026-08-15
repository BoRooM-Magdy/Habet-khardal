<?php
global $user;
?>
<div class="animate-fade-in mb-4">
    <div class="mb-4">
        <h2 class="fs-4 fw-black text-primary m-0">الإنجازات والمكافآت</h2>
        <p class="small fw-bold text-secondary mt-1 m-0">تتبع مستواك ونقاطك ونجومك التي حصدتها</p>
    </div>

    <!-- Stats Banner -->
    <div class="rounded-4 p-4 p-md-5 mb-5 text-white position-relative overflow-hidden shadow" style="background: linear-gradient(135deg, #7B1F3F 0%, #4A1124 100%);">
        <div class="position-absolute bg-white opacity-10 rounded-circle blur-xl" style="width: 200px; height: 200px; right: -50px; top: -50px;"></div>
        <div class="position-absolute bg-warning opacity-25 rounded-circle blur-lg" style="width: 150px; height: 150px; left: -30px; bottom: -30px;"></div>
        
        <div class="row align-items-center position-relative z-1">
            <div class="col-md-6 text-center text-md-start mb-4 mb-md-0">
                <h3 class="fs-2 fw-black text-warning mb-2 lh-sm">أنت بطل! <span class="d-inline-block">🏆</span></h3>
                <p class="small fw-bold opacity-75 m-0" style="font-size: 0.9rem;">لقد حققت تقدماً مذهلاً هذا الأسبوع. استمر في التألق.</p>
            </div>

            <div class="col-md-6">
                <div class="d-flex justify-content-center justify-content-md-end gap-3 gap-md-5">
                    <div class="text-center">
                        <div class="rounded-4 bg-white bg-opacity-10 border border-white border-opacity-25 d-flex align-items-center justify-content-center text-warning mx-auto mb-2" style="width: 60px; height: 60px; font-size: 1.5rem;">
                            <i class="fa-solid fa-star"></i>
                        </div>
                        <div class="fs-4 fw-black lh-sm"><?= number_format($user['stars'] ?? 0) ?></div>
                        <div class="fw-bold text-white opacity-75" style="font-size: 0.65rem;">نجوم</div>
                    </div>
                    <div class="text-center">
                        <div class="rounded-4 bg-white bg-opacity-10 border border-white border-opacity-25 d-flex align-items-center justify-content-center mx-auto mb-2" style="width: 60px; height: 60px; font-size: 1.5rem; color: #22C55E;">
                            <i class="fa-solid fa-fire"></i>
                        </div>
                        <div class="fs-4 fw-black lh-sm"><?= number_format($user['streak_days'] ?? 0) ?></div>
                        <div class="fw-bold text-white opacity-75" style="font-size: 0.65rem;">أيام حماس</div>
                    </div>
                    <div class="text-center">
                        <div class="rounded-4 bg-white bg-opacity-10 border border-white border-opacity-25 d-flex align-items-center justify-content-center text-white mx-auto mb-2" style="width: 60px; height: 60px; font-size: 1.5rem;">
                            <i class="fa-solid fa-gem"></i>
                        </div>
                        <div class="fs-4 fw-black lh-sm"><?= number_format($user['points'] ?? 0) ?></div>
                        <div class="fw-bold text-white opacity-75" style="font-size: 0.65rem;">نقاط</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Badges Grid -->
    <div>
        <h3 class="fs-5 fw-black text-dark mb-4">الأوسمة التي حصلت عليها</h3>
        <div class="row g-4">
            <!-- Badge 1 -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="bg-white rounded-4 border p-4 text-center shadow-sm position-relative overflow-hidden h-100 border-warning border-opacity-50">
                    <div class="position-absolute top-0 end-0 start-0 bg-warning" style="height: 4px;"></div>
                    <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="fa-solid fa-award"></i>
                    </div>
                    <h4 class="fw-black text-primary fs-6 mb-1">المركز الأول</h4>
                    <p class="fw-bold text-secondary opacity-75 m-0" style="font-size: 0.7rem;">تفوقت في الاختبار الأخير</p>
                </div>
            </div>
            
            <!-- Badge 2 (Locked) -->
            <div class="col-6 col-md-4 col-lg-3">
                <div class="bg-white rounded-4 border p-4 text-center shadow-sm position-relative overflow-hidden h-100 opacity-50" style="filter: grayscale(100%); cursor: not-allowed; border-color: rgba(123,31,63,0.1) !important;">
                    <div class="rounded-circle bg-light text-secondary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <i class="fa-solid fa-book-open-reader"></i>
                    </div>
                    <h4 class="fw-black text-secondary fs-6 mb-1">دودة الكتب</h4>
                    <p class="fw-bold text-secondary opacity-75 m-0" style="font-size: 0.7rem;">اقرأ 5 ملخصات لفتح الوسام</p>
                    <div class="position-absolute top-0 end-0 p-3 text-secondary opacity-50" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>
            </div>
            
            <!-- More badges can be added here following the same structure -->
        </div>
    </div>
</div>
