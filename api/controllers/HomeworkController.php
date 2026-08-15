<?php
class HomeworkController {
    private static function requireAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Admin access required']);
            exit;
        }
    }

    public static function createHomework($pdo) {
        self::requireAdmin();
        $input = json_decode(file_get_contents('php://input'), true);
        $subjectId = filter_var($input['subject_id'] ?? null, FILTER_VALIDATE_INT);
        $title = htmlspecialchars(strip_tags($input['title'] ?? ''));
        $type = htmlspecialchars(strip_tags($input['type'] ?? 'mcq')); // mcq, manual, recitation
        $content = $input['content'] ?? ''; // JSON for MCQ, or text instruction for manual/recitation

        if (!$subjectId || !$title) {
            http_response_code(400);
            echo json_encode(['error' => 'Subject ID and Title are required']);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO homework (subject_id, title, type, questions) VALUES (?, ?, ?, ?)");
        $stmt->execute([$subjectId, $title, $type, is_array($content) ? json_encode($content) : $content]);
        
        require_once __DIR__ . '/NotificationController.php';
        NotificationController::sendToStudents($pdo, null, $subjectId, 'info', 'واجب جديد ✍️', "تمت إضافة واجب جديد: " . $title, 'subjects');

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    }

    public static function deleteHomework($pdo) {
        self::requireAdmin();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid ID is required']);
            return;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM homework WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete homework']);
        }
    }

    public static function getAllHomeworks($pdo) {
        self::requireAdmin();
        $stmt = $pdo->query("
            SELECT h.*, s.name as subject_name, st.name as stage_name
            FROM homework h
            JOIN subjects s ON h.subject_id = s.id
            JOIN stages st ON s.stage_id = st.id
            ORDER BY h.created_at DESC
        ");
        $homeworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($homeworks);
    }

    public static function getHomeworks($pdo, $userId) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $userId = $_SESSION['user_id'] ?? 0;
        }
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID required']);
            return;
        }
        
        // Get all homework for subjects the user is enrolled in or stage subjects
        $stmt = $pdo->prepare("
            SELECT h.*, s.name as subject_name, hs.score, hs.status, hs.student_answer, hs.id as submission_id
            FROM homework h
            JOIN subjects s ON h.subject_id = s.id
            JOIN users u ON u.id = ?
            LEFT JOIN homework_submissions hs ON h.id = hs.homework_id AND hs.user_id = u.id
            WHERE s.stage_id = u.stage_id OR s.id IN (SELECT subject_id FROM user_subjects WHERE user_id = u.id)
            ORDER BY h.created_at DESC
        ");
        $stmt->execute([$userId]);
        $homeworks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parse content JSON if mcq
        foreach ($homeworks as &$hw) {
            if ($hw['type'] === 'mcq') {
                $hw['questions'] = json_decode($hw['questions'] ?? '', true);
            }
        }
        
        echo json_encode($homeworks);
    }

    public static function getAllSubmissions($pdo) {
        self::requireAdmin();
        $stmt = $pdo->query("
            SELECT hs.id, hs.user_id, hs.homework_id, hs.student_answer, hs.score, hs.status, hs.created_at, h.title as homework_title, h.type as homework_type, u.child_name, u.level, s.name as subject_name, NULL as audio_path, 'homework' as source_table
            FROM homework_submissions hs
            JOIN homework h ON hs.homework_id = h.id
            JOIN users u ON hs.user_id = u.id
            JOIN subjects s ON h.subject_id = s.id
            UNION ALL
            SELECT r.id, r.user_id, r.material_id as homework_id, r.notes as student_answer, r.stars as score, r.status, r.created_at, m.title as homework_title, m.type as homework_type, u.child_name, u.level, s.name as subject_name, r.audio_path, 'recitation' as source_table
            FROM recitations r
            JOIN materials m ON r.material_id = m.id
            JOIN users u ON r.user_id = u.id
            JOIN subjects s ON m.subject_id = s.id
            ORDER BY created_at DESC
        ");
        $submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($submissions);
    }

    public static function submitHomework($pdo) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $homeworkId = filter_input(INPUT_POST, 'homework_id', FILTER_VALIDATE_INT);
        
        if (!$userId && isset($_POST['user_id'])) $userId = $_POST['user_id'];
        if (!$homeworkId && isset($_POST['homework_id'])) $homeworkId = $_POST['homework_id'];
        
        // Handle json input fallback
        if (!$userId || !$homeworkId) {
            $input = json_decode(file_get_contents('php://input'), true);
            $userId = $input['user_id'] ?? $userId;
            $homeworkId = $input['homework_id'] ?? $homeworkId;
        }

        if (($_SESSION['role'] ?? '') !== 'admin') {
            $userId = $_SESSION['user_id'] ?? 0;
        }

        if (!$userId || !$homeworkId) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid User ID and Homework ID are required']);
            return;
        }

        $stmtHw = $pdo->prepare("SELECT * FROM homework WHERE id = ?");
        $stmtHw->execute([$homeworkId]);
        $homework = $stmtHw->fetch(PDO::FETCH_ASSOC);

        if (!$homework) {
            http_response_code(404);
            echo json_encode(['error' => 'Homework not found']);
            return;
        }

        $studentAnswer = '';
        $score = 0;
        $status = 'pending';

        if ($homework['type'] === 'mcq') {
            $input = $input ?? json_decode(file_get_contents('php://input'), true);
            $studentAnswer = json_encode($input['answers'] ?? []);
            
            $questions = json_decode($homework['questions'], true);
            $correctCount = 0;
            $total = count($questions);
            $answers = $input['answers'] ?? [];
            foreach ($questions as $idx => $q) {
                if (isset($answers[$idx]) && $answers[$idx] == $q['correct']) {
                    $correctCount++;
                }
            }
            $score = $total > 0 ? round(($correctCount / $total) * 15) : 0;
            $status = 'graded';
            
            self::rewardPoints($pdo, $userId, $score);
        } else if ($homework['type'] === 'recitation' || $homework['type'] === 'manual') {
            if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
                $uploadedFile = $_FILES['file'];
                $uploadDir = __DIR__ . '/../uploads/submissions/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($uploadedFile['name']));
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg','jpeg','png','gif','webp','mp3','wav','ogg','webm','mp4','pdf'];
                if (!in_array($ext, $allowedExtensions)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'File type not allowed']);
                    return;
                }
                $targetFile = $uploadDir . $fileName;

                if (move_uploaded_file($uploadedFile['tmp_name'], $targetFile)) {
                    $studentAnswer = 'uploads/submissions/' . $fileName;
                }
            } else {
                $inputData = json_decode(file_get_contents('php://input'), true) ?: [];
                $studentAnswer = $_POST['text_answer'] ?? ($inputData['text_answer'] ?? '');
            }
        }

        // Check if already submitted
        $stmtCheck = $pdo->prepare("SELECT id FROM homework_submissions WHERE user_id = ? AND homework_id = ?");
        $stmtCheck->execute([$userId, $homeworkId]);
        if ($stmtCheck->fetchColumn()) {
            http_response_code(400);
            echo json_encode(['error' => 'Already submitted']);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO homework_submissions (user_id, homework_id, student_answer, score, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $homeworkId, $studentAnswer, $score, $status]);
        
        self::checkLevelUp($pdo, $userId);

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'score' => $score]);
    }

    public static function gradeHomework($pdo) {
        self::requireAdmin();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        $score = filter_var($input['score'] ?? 0, FILTER_VALIDATE_INT);
        $comment = $input['comment'] ?? null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Submission ID required']);
            return;
        }

        $sourceTable = $input['source_table'] ?? 'homework';

        if ($sourceTable === 'recitation') {
            $stmt = $pdo->prepare("UPDATE recitations SET status = 'reviewed', stars = ?, teacher_comment = ? WHERE id = ?");
            $stmt->execute([$score, $comment, $id]);
            $stmtUser = $pdo->prepare("SELECT user_id FROM recitations WHERE id = ?");
        } else {
            $stmt = $pdo->prepare("UPDATE homework_submissions SET status = 'graded', score = ?, teacher_comment = ? WHERE id = ?");
            $stmt->execute([$score, $comment, $id]);
            $stmtUser = $pdo->prepare("SELECT user_id FROM homework_submissions WHERE id = ?");
        }
        
        $stmtUser->execute([$id]);
        $userId = $stmtUser->fetchColumn();

        if ($userId) {
            self::rewardPoints($pdo, $userId, $score);
            require_once __DIR__ . '/NotificationController.php';
            NotificationController::sendToUser($pdo, $userId, 'success', 'تم تقييم واجبك! 🏆', "حصلت على $score / 10 في الواجب. استمر يا بطل!", 'achievements');
        }

        echo json_encode(['success' => true]);
    }

    private static function rewardPoints($pdo, $userId, $score) {
        if ($score > 0) {
            $points = $score * 5; 
            $stars = ($score >= 10) ? 1 : 0;
            $stmtAdd = $pdo->prepare("UPDATE users SET stars = stars + ?, points = points + ? WHERE id = ?");
            $stmtAdd->execute([$stars, $points, $userId]);
        }
    }

    private static function checkLevelUp($pdo, $userId) {
        // Count total completed lessons + graded homeworks
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lesson_progress WHERE user_id = ? AND completed = 1");
        $stmt->execute([$userId]);
        $lessons = (int)$stmt->fetchColumn();
        
        $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM homework_submissions WHERE user_id = ?");
        $stmt2->execute([$userId]);
        $hw = (int)$stmt2->fetchColumn();
        
        $count = $lessons + $hw;

        if ($count >= 320) {
            $newLevel = floor($count / 320) + 1;
            $stmtLevel = $pdo->prepare("SELECT level FROM users WHERE id = ?");
            $stmtLevel->execute([$userId]);
            $currentLevel = (int)$stmtLevel->fetchColumn();

            if ($newLevel > $currentLevel) {
                $stmtUpdate = $pdo->prepare("UPDATE users SET level = ? WHERE id = ?");
                $stmtUpdate->execute([$newLevel, $userId]);
            }
        }
    }
}
