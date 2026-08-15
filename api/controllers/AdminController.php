<?php
class AdminController {
    private static function requireAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Admin access required']);
            exit;
        }
    }

    public static function getStats($pdo) {
        self::requireAdmin();
        $stats = [
            'total_students' => 0,
            'completed_lessons' => 0,
            'active_students' => 0,
            'pending_homeworks' => 0
        ];

        // Total students
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
        $stats['total_students'] = $stmt->fetchColumn() ?: 0;

        // Completed lessons
        $stmt = $pdo->query("SELECT COUNT(*) FROM lesson_progress WHERE completed = 1");
        $stats['completed_lessons'] = $stmt->fetchColumn() ?: 0;

        // Pending homeworks
        $stmt = $pdo->query("SELECT COUNT(*) FROM homework_submissions WHERE status = 'pending'");
        $stats['pending_homeworks'] = $stmt->fetchColumn() ?: 0;

        // Active students (e.g. have points or completed at least 1 lesson)
        $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM lesson_progress WHERE completed = 1");
        $stats['active_students'] = $stmt->fetchColumn() ?: 0;

        $months = [];
        $monthNames = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
        for ($i = 5; $i >= 0; $i--) {
            $ts = strtotime("-$i months");
            $m = date('m', $ts);
            $y = date('Y', $ts);
            $name = $monthNames[(int)$m - 1];
            $months["$y-$m"] = ['m' => $name, 'v' => 0];
        }

        $sql = "
            SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as count 
            FROM users 
            WHERE role = 'student' AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY ym
        ";
        $stmt = $pdo->query($sql);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($months[$row['ym']])) {
                $months[$row['ym']]['v'] = (int)$row['count'];
            }
        }
        
        $sixMonthsAgo = "DATE_SUB(NOW(), INTERVAL 6 MONTH)";
        $ymFormat = "DATE_FORMAT(created_at, '%Y-%m')";

        // Calculate cumulative for growth
        $cumulative = 0;
        // Get total users before 6 months
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student' AND created_at < $sixMonthsAgo");
        $cumulative = (int)$stmt->fetchColumn();

        $stats['growthData'] = [];
        foreach ($months as $k => $v) {
            $cumulative += $v['v'];
            $stats['growthData'][] = ['m' => $v['m'], 'v' => $cumulative];
        }

        // Generate Activity/Revenue Data (completed lessons per month for last 6 months)
        $activityMonths = [];
        for ($i = 5; $i >= 0; $i--) {
            $ts = strtotime("-$i months");
            $m = date('m', $ts);
            $y = date('Y', $ts);
            $name = $monthNames[(int)$m - 1];
            $activityMonths["$y-$m"] = ['m' => $name, 'v' => 0];
        }

        $stmt = $pdo->query("
            SELECT $ymFormat as ym, COUNT(*) as count 
            FROM lesson_progress 
            WHERE completed = 1 AND created_at >= $sixMonthsAgo
            GROUP BY ym
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($activityMonths[$row['ym']])) {
                $activityMonths[$row['ym']]['v'] = (int)$row['count'] * 50;
            }
        }

        $stats['revenueData'] = array_values($activityMonths);

        echo json_encode($stats);
    }

    public static function getStudents($pdo) {
        self::requireAdmin();
        $stmt = $pdo->query("SELECT id, child_name, level, stars, points, plan, created_at, stage_id FROM users WHERE role = 'student' ORDER BY points DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public static function getStudentProfile($pdo) {
        self::requireAdmin();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid ID is required']);
            return;
        }

        // Fetch User Info
        $stmt = $pdo->prepare("SELECT u.id, u.child_name, u.parent_name, u.age, u.gender, u.email, u.stars, u.points, u.level, u.streak_days, u.plan, u.created_at, u.last_active, u.birth_date, u.stage_id, s.name as stage_name FROM users u LEFT JOIN stages s ON u.stage_id = s.id WHERE u.id = ? AND u.role = 'student'");
        $stmt->execute([$id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'Student not found']);
            return;
        }

        // Fetch Progress count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM lesson_progress WHERE user_id = ? AND completed = 1");
        $stmt->execute([$id]);
        $completed_lessons = $stmt->fetchColumn() ?: 0;

        // Fetch Total Materials available for this student's stage
        $total_materials = 0;
        if (!empty($user['stage_id'])) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM materials m JOIN subjects s ON m.subject_id = s.id WHERE s.stage_id = ?");
            $stmt->execute([$user['stage_id']]);
            $total_materials = $stmt->fetchColumn() ?: 0;
        }

        // Fetch Recent Evaluations (Homework)
        $stmt = $pdo->prepare("SELECT hs.id, hs.score, hs.status, hs.created_at, h.title as material_title, 'homework' as type FROM homework_submissions hs LEFT JOIN homework h ON hs.homework_id = h.id WHERE hs.user_id = ? AND hs.status = 'graded' ORDER BY hs.created_at DESC LIMIT 10");
        $stmt->execute([$id]);
        $evaluations_hw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch Exam Submissions
        $stmt = $pdo->prepare("SELECT es.id, es.score, 'graded' as status, es.created_at, e.title as material_title, 'exam' as type FROM exam_submissions es LEFT JOIN exams e ON es.exam_id = e.id WHERE es.user_id = ? ORDER BY es.created_at DESC LIMIT 10");
        $stmt->execute([$id]);
        $evaluations_ex = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $evaluations = array_merge($evaluations_hw, $evaluations_ex);
        usort($evaluations, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        $evaluations = array_slice($evaluations, 0, 10);

        // Fetch user_subjects
        $stmt = $pdo->prepare("SELECT subject_id FROM user_subjects WHERE user_id = ?");
        $stmt->execute([$id]);
        $user_subjects = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'success' => true,
            'user' => $user,
            'completed_lessons' => $completed_lessons,
            'total_materials' => $total_materials,
            'evaluations' => $evaluations,
            'user_subjects' => $user_subjects
        ]);
    }

    public static function deleteStudent($pdo) {
        self::requireAdmin();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid ID is required']);
            return;
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'student'");
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true]);
            } else {
                http_response_code(404);
                echo json_encode(['error' => 'Student not found']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete student']);
        }
    }
    public static function editStudent($pdo) {
        self::requireAdmin();
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $rawName = filter_input(INPUT_POST, 'child_name', FILTER_UNSAFE_RAW);
        $childName = filter_var($rawName, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $stars = filter_input(INPUT_POST, 'stars', FILTER_VALIDATE_INT) ?: 0;
        $points = filter_input(INPUT_POST, 'points', FILTER_VALIDATE_INT) ?: 0;
        $level = filter_input(INPUT_POST, 'level', FILTER_VALIDATE_INT) ?: 1;
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        
        $rawParentName = filter_input(INPUT_POST, 'parent_name', FILTER_UNSAFE_RAW);
        $parentName = filter_var($rawParentName, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $birthDate = filter_input(INPUT_POST, 'birth_date', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $gender = filter_input(INPUT_POST, 'gender', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $plan = filter_input(INPUT_POST, 'plan', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $stageId = filter_input(INPUT_POST, 'stage_id', FILTER_VALIDATE_INT) ?: null;
        
        $secondarySubjects = filter_input(INPUT_POST, 'secondary_subjects', FILTER_UNSAFE_RAW);
        $subjectsArray = [];
        if ($secondarySubjects) {
            $decoded = json_decode($secondarySubjects, true);
            if (is_array($decoded)) {
                $subjectsArray = array_map('intval', $decoded);
            }
        }

        if (!$id || !$childName) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid ID and Name are required']);
            return;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("UPDATE users SET child_name = ?, parent_name = ?, email = ?, birth_date = ?, gender = ?, plan = ?, stage_id = ?, stars = ?, points = ?, level = ? WHERE id = ? AND role = 'student'");
            $stmt->execute([$childName, $parentName, $email, $birthDate, $gender, $plan, $stageId, $stars, $points, $level, $id]);
            
            // Sync user_subjects
            $pdo->prepare("DELETE FROM user_subjects WHERE user_id = ?")->execute([$id]);
            
            if (!empty($subjectsArray)) {
                $insertStmt = $pdo->prepare("INSERT INTO user_subjects (user_id, subject_id) VALUES (?, ?)");
                foreach ($subjectsArray as $subId) {
                    $insertStmt->execute([$id, $subId]);
                }
            }

            $pdo->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update student: ' . $e->getMessage()]);
        }
    }
}
