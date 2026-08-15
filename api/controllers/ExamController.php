<?php
// api/controllers/ExamController.php

class ExamController {
    private static function requireAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Admin access required']);
            exit;
        }
    }

    public static function createExam($pdo) {
        self::requireAdmin();
        $subjectId = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
        $rawTitle = filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW);
        $title = filter_var($rawTitle, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $questionsJson = $_POST['questions'] ?? '';
        
        if (!$subjectId || !$title || !$questionsJson) {
            http_response_code(400);
            echo json_encode(['error' => 'Subject ID, Title, and Questions are required']);
            return;
        }
        
        $questions = json_decode($questionsJson, true);
        if (!is_array($questions)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid questions format']);
            return;
        }

        $uploadDir = __DIR__ . '/../uploads/exams/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        foreach ($questions as $qIndex => &$q) {
            $qFileKey = "qfile_" . $qIndex;
            if (isset($_FILES[$qFileKey]) && $_FILES[$qFileKey]['error'] === UPLOAD_ERR_OK) {
                $ext = pathinfo($_FILES[$qFileKey]['name'], PATHINFO_EXTENSION);
                $allowedExtensions = ['jpg','jpeg','png','gif','webp','mp4','webm','mp3','wav','ogg','pdf'];
                if (!in_array(strtolower($ext), $allowedExtensions)) continue;
                $fileName = 'q_' . time() . '_' . $qIndex . '.' . $ext;
                if (move_uploaded_file($_FILES[$qFileKey]['tmp_name'], $uploadDir . $fileName)) {
                    $q['promptFileUrl'] = 'uploads/exams/' . $fileName;
                }
            }
            
            if (isset($q['options']) && is_array($q['options'])) {
                foreach ($q['options'] as $oIndex => &$opt) {
                    $oFileKey = "ofile_" . $qIndex . "_" . $oIndex;
                    if (isset($_FILES[$oFileKey]) && $_FILES[$oFileKey]['error'] === UPLOAD_ERR_OK) {
                        $ext = pathinfo($_FILES[$oFileKey]['name'], PATHINFO_EXTENSION);
                        $allowedExtensions = ['jpg','jpeg','png','gif','webp','mp4','webm','mp3','wav','ogg','pdf'];
                        if (!in_array(strtolower($ext), $allowedExtensions)) continue;
                        $fileName = 'o_' . time() . '_' . $qIndex . '_' . $oIndex . '.' . $ext;
                        if (move_uploaded_file($_FILES[$oFileKey]['tmp_name'], $uploadDir . $fileName)) {
                            $opt['fileUrl'] = 'uploads/exams/' . $fileName;
                        }
                    }
                }
            }
        }

        $finalQuestionsJson = json_encode($questions, JSON_UNESCAPED_UNICODE);

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO exams (subject_id, title, questions) VALUES (?, ?, ?)");
            $stmt->execute([$subjectId, $title, $finalQuestionsJson]);
            $examId = $pdo->lastInsertId();

            $stmtMat = $pdo->prepare("INSERT INTO materials (subject_id, title, type, file_path) VALUES (?, ?, 'exam', ?)");
            $stmtMat->execute([$subjectId, $title, 'exam_' . $examId]);

            require_once __DIR__ . '/NotificationController.php';
            NotificationController::sendToStudents($pdo, null, $subjectId, 'info', 'اختبار جديد 📝', "تمت إضافة اختبار جديد: " . $title, 'subjects');

            $pdo->commit();
            echo json_encode(['success' => true, 'exam_id' => $examId]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create exam']);
        }
    }

    public static function getExam($pdo, $id = null) {
        if (!$id) {
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        }
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid ID is required']);
            return;
        }

        $stmt = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
        $stmt->execute([$id]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($exam) {
            echo json_encode($exam, JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Exam not found']);
        }
    }

    public static function deleteExam($pdo) {
        self::requireAdmin();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid ID is required']);
            return;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM exams WHERE id = ?");
            $stmt->execute([$id]);
            
            // Delete from materials as well (if exam was added to materials)
            $stmtMat = $pdo->prepare("DELETE FROM materials WHERE type = 'exam' AND file_path = ?");
            $stmtMat->execute(['exam_' . $id]);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete exam']);
        }
    }

    public static function submitExam($pdo) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT);
        $examId = filter_var($input['exam_id'] ?? null, FILTER_VALIDATE_INT);
        $answers = $input['answers'] ?? [];

        if (($_SESSION['role'] ?? '') !== 'admin') {
            $userId = $_SESSION['user_id'] ?? 0;
        }

        if (!$userId || !$examId) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID and Exam ID required']);
            return;
        }

        // Fetch Exam to grade server-side securely
        $stmtExam = $pdo->prepare("SELECT * FROM exams WHERE id = ?");
        $stmtExam->execute([$examId]);
        $exam = $stmtExam->fetch(PDO::FETCH_ASSOC);

        if (!$exam) {
            http_response_code(404);
            echo json_encode(['error' => 'Exam not found']);
            return;
        }

        $questionsData = json_decode($exam['questions'], true);
        $correctAnswersCount = 0;
        if (is_array($questionsData) && is_array($answers)) {
            foreach ($questionsData as $idx => $q) {
                if (isset($answers[$idx]) && isset($q['correct']) && $answers[$idx] == $q['correct']) {
                    $correctAnswersCount++;
                }
            }
            $score = count($questionsData) > 0 ? round(($correctAnswersCount / count($questionsData)) * 100) : 0;
        } else {
            $score = 0;
        }

        $answersJson = json_encode($answers, JSON_UNESCAPED_UNICODE);

        // Delete previous submission if any, or just insert new
        $stmtDel = $pdo->prepare("DELETE FROM exam_submissions WHERE user_id = ? AND exam_id = ?");
        $stmtDel->execute([$userId, $examId]);

        $stmt = $pdo->prepare("INSERT INTO exam_submissions (user_id, exam_id, score, answers) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $examId, $score, $answersJson]);

        $points = $score;
        $stars = ($score >= 90) ? 1 : 0;
        $stmtUser = $pdo->prepare("UPDATE users SET points = points + ?, stars = stars + ? WHERE id = ?");
        $stmtUser->execute([$points, $stars, $userId]);

        echo json_encode(['success' => true, 'score' => $score]);
    }

    public static function getExamStats($pdo) {
        self::requireAdmin();
        // Returns all exams with their submissions and participants
        $stmt = $pdo->query("
            SELECT e.id, e.title, e.questions, s.name as subject_name, st.name as stage_name
            FROM exams e
            JOIN subjects s ON e.subject_id = s.id
            JOIN stages st ON s.stage_id = st.id
            ORDER BY e.created_at DESC
        ");
        $exams = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stats = [];
        foreach ($exams as $exam) {
            $examId = $exam['id'];
            
            // Get all students enrolled in this exam's stage
            // Note: we can filter by role='student' and user's stage matching the exam's subject's stage
            $stmtUsers = $pdo->prepare("
                SELECT u.id, u.child_name, u.email,
                       (SELECT score FROM exam_submissions WHERE user_id = u.id AND exam_id = ?) as score,
                       (SELECT answers FROM exam_submissions WHERE user_id = u.id AND exam_id = ?) as answers
                FROM users u
                JOIN subjects s ON s.stage_id = u.stage_id
                WHERE s.id = (SELECT subject_id FROM exams WHERE id = ?) AND u.role = 'student'
                GROUP BY u.id
            ");
            $stmtUsers->execute([$examId, $examId, $examId]);
            $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

            // Parse answers JSON
            foreach ($users as &$u) {
                if ($u['answers']) {
                    $u['answers'] = json_decode($u['answers'], true);
                }
            }

            $exam['students'] = $users;
            $stats[] = $exam;
        }

        echo json_encode($stats);
    }
}

