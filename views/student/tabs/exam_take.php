<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../../../api/db.php';
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    die("Invalid Exam ID");
}

$stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
$stmt->execute([$id]);
$exam = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$exam) {
    die("Exam not found");
}

$questions = json_decode($exam['questions'], true);
$userId = $_SESSION['user_id'];
?>
<div class="animate-fade-in">
    <style>
        .exam-take-wrapper {
            font-family: 'Cairo', sans-serif;
        }
        .exam-take-wrapper .fw-black { font-weight: 900; }
        .exam-header {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark));
            color: white;
            padding: 3rem 1rem;
            border-bottom-left-radius: 3rem;
            border-bottom-right-radius: 3rem;
            box-shadow: 0 10px 30px rgba(107, 30, 45, 0.2);
            margin-bottom: -3rem;
            position: relative;
            z-index: 0;
        }
        .question-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 1.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            padding: 2rem;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }
        .question-card:hover {
            transform: translateY(-5px);
        }
        .option-label {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
        }
        .option-label:hover {
            border-color: var(--brand-gold-light);
            background: rgba(197, 160, 89, 0.05);
        }
        .option-input:checked + .option-label {
            border-color: var(--brand-gold);
            background: rgba(197, 160, 89, 0.1);
            color: var(--brand-primary-dark);
            box-shadow: 0 5px 15px rgba(197, 160, 89, 0.2);
        }
        .media-container {
            background: #f8f9fa;
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e9ecef;
            text-align: center;
        }
        .media-container img {
            max-height: 300px;
            object-fit: contain;
            border-radius: 0.5rem;
        }
        .media-container audio {
            width: 100%;
            max-width: 400px;
        }
    </style>
    <div class="exam-header text-center">
        <div class="container">
            <button onclick="switchTab('materials&subject_id=<?= $exam['subject_id'] ?>')" class="btn btn-light rounded-circle shadow-sm position-absolute" style="top: 1rem; right: 1rem; width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-arrow-right text-primary"></i>
            </button>
            <h1 class="fw-black mb-2"><i class="fa-solid fa-clipboard-question text-warning"></i> <?= htmlspecialchars($exam['title']) ?></h1>
            <p class="opacity-75 m-0 fw-bold">ركز جيداً، وتأكد من إجابتك قبل التسليم!</p>
        </div>
    </div>
<div class="container mt-5 pt-5 pb-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <form id="examForm" onsubmit="submitExam(event)">
                
                <?php foreach($questions as $i => $q): ?>
                <div class="question-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="fw-black text-dark m-0">السؤال <?= $i + 1 ?></h4>
                        <span class="badge bg-light text-secondary border border-secondary">درجة واحدة</span>
                    </div>
                    
                    <p class="fs-5 fw-bold text-dark mb-4"><?= nl2br(htmlspecialchars($q['q'])) ?></p>
                    
                    <!-- Media Render -->
                    <?php if(!empty($q['promptFileUrl'])): ?>
                    <div class="media-container shadow-sm">
                        <?php 
                        $ext = strtolower(pathinfo($q['promptFileUrl'], PATHINFO_EXTENSION));
                        if(in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])):
                        ?>
                            <img src="/<?= htmlspecialchars($q['promptFileUrl']) ?>" alt="صورة السؤال" class="img-fluid">
                        <?php else: ?>
                            <div class="d-flex flex-column align-items-center gap-2">
                                <span class="badge bg-danger rounded-pill px-3 py-2 fw-bold"><i class="fa-solid fa-microphone"></i> تسجيل صوتي للسؤال</span>
                                <audio controls src="/<?= htmlspecialchars($q['promptFileUrl']) ?>"></audio>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <?php foreach($q['options'] as $oIdx => $opt): ?>
                        <div class="col-12">
                            <input type="radio" class="d-none option-input" name="q_<?= $i ?>" id="q_<?= $i ?>_opt_<?= $oIdx ?>" value="<?= $oIdx ?>" required>
                            <label class="option-label" for="q_<?= $i ?>_opt_<?= $oIdx ?>">
                                <div class="d-flex align-items-center justify-content-center bg-light rounded-circle ms-3 text-secondary fw-bold" style="width: 35px; height: 35px; border: 1px solid #dee2e6;">
                                    <?= ['أ','ب','ج','د'][$oIdx] ?>
                                </div>
                                <?= htmlspecialchars($opt) ?>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>

                <button type="submit" class="btn w-100 rounded-pill py-3 fw-black fs-5 shadow-lg" style="background: var(--brand-gold); color: white; border: none; transition: transform 0.2s;">
                    <i class="fa-solid fa-paper-plane ms-2"></i> تسليم الاختبار
                </button>
            </form>
        </div>
    </div>
</div>
</div>

<script>
    const questionsData = <?= json_encode($questions) ?>;
    const examId = <?= $id ?>;
    const userId = <?= $userId ?>;

    async function submitExam(e) {
        e.preventDefault();
        
        // Calculate Score
        const form = document.getElementById('examForm');
        const formData = new FormData(form);
        let correctAnswersCount = 0;
        let studentAnswers = [];

        for(let i=0; i<questionsData.length; i++) {
            const selectedOpt = parseInt(formData.get('q_' + i));
            studentAnswers.push(selectedOpt);
            
            if(selectedOpt === questionsData[i].correct) {
                correctAnswersCount++;
            }
        }

        const scorePercentage = Math.round((correctAnswersCount / questionsData.length) * 100);

        Swal.fire({
            title: 'جاري التصحيح...',
            text: 'يتم الآن تقييم إجاباتك',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        try {
            const res = await fetch('/api/exams/submit', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: userId,
                    exam_id: examId,
                    score: scorePercentage,
                    answers: studentAnswers
                })
            });
            const data = await res.json();
            
            if(data.success) {
                Swal.fire({
                    icon: scorePercentage >= 50 ? 'success' : 'warning',
                    title: 'النتيجة: ' + scorePercentage + '%',
                    text: `لقد أجبت بشكل صحيح على ${correctAnswersCount} من أصل ${questionsData.length} أسئلة!`,
                    confirmButtonText: 'العودة للمواد',
                    confirmButtonColor: '#6b1e2d'
                }).then(() => {
                    switchTab('materials&subject_id=<?= $exam['subject_id'] ?>');
                });
            } else {
                Swal.fire('خطأ', data.error || 'فشل التسليم', 'error');
            }
        } catch(err) {
            Swal.fire('خطأ', 'حدث خطأ أثناء الاتصال', 'error');
        }
    }
</script>
