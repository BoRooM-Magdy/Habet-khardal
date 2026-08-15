<?php
class ProgressController {
    public static function getUserProgress($pdo, $userId) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $userId = $_SESSION['user_id'] ?? 0;
        }
        if (!$userId) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID required']);
            return;
        }

        $stmt = $pdo->prepare("SELECT material_id FROM lesson_progress WHERE user_id = ? AND completed = 1");
        $stmt->execute([$userId]);
        $completedMaterialIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT stars, points, level, streak_days FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userStats = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userStats) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        $currentPoints = (int)($userStats['points'] ?? 0);
        $currentLevel = (int)($userStats['level'] ?? 1);

        // Calculate total possible points in the year
        $stmtTotal = $pdo->query("SELECT SUM(CASE WHEN type = 'audio' THEN 15 WHEN type = 'exam' THEN 25 ELSE 10 END) FROM materials");
        $totalPossible = (int)$stmtTotal->fetchColumn();
        
        // Ensure the bar targets at least 3200 points (320 lessons * ~10 points avg) as a base assumption
        $nextLevelPoints = max($totalPossible, 3200);

        echo json_encode([
            'completed_lessons' => count($completedMaterialIds),
            'completed_material_ids' => $completedMaterialIds,
            'stars' => $userStats['stars'] ?? 0,
            'points' => $currentPoints,
            'level' => $currentLevel,
            'streak' => $userStats['streak_days'] ?? 0,
            'points_in_level' => $currentPoints,
            'next_level_points' => $nextLevelPoints
        ]);
    }

    public static function markLessonComplete($pdo) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $input = json_decode(file_get_contents('php://input'), true);
        $userId = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT);
        $materialId = filter_var($input['material_id'] ?? null, FILTER_VALIDATE_INT);

        if (($_SESSION['role'] ?? '') !== 'admin') {
            $userId = $_SESSION['user_id'] ?? 0;
        }

        if (!$userId || !$materialId) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid User ID and Material ID are required']);
            return;
        }

        try {
            // Fetch material type
            $stmtMat = $pdo->prepare("SELECT type FROM materials WHERE id = ?");
            $stmtMat->execute([$materialId]);
            $materialType = $stmtMat->fetchColumn();

            $pointsToAdd = 10;
            $starsToAdd = 1;
            
            if ($materialType === 'audio') {
                $pointsToAdd = 15;
                $starsToAdd = 2;
            } elseif ($materialType === 'exam') {
                $pointsToAdd = 25;
                $starsToAdd = 3;
            }

            // Check if already completed so we don't add points twice
            $stmtCheck = $pdo->prepare("SELECT completed FROM lesson_progress WHERE user_id = ? AND material_id = ?");
            $stmtCheck->execute([$userId, $materialId]);
            $isAlreadyCompleted = $stmtCheck->fetchColumn();

            $stmt = $pdo->prepare("INSERT INTO lesson_progress (user_id, material_id, completed) VALUES (?, ?, 1) ON CONFLICT(user_id, material_id) DO UPDATE SET completed = 1");
            $stmt->execute([$userId, $materialId]);
            
            if (!$isAlreadyCompleted) {
                // Add points and stars
                $stmtAdd = $pdo->prepare("UPDATE users SET points = points + ?, stars = stars + ? WHERE id = ?");
                $stmtAdd->execute([$pointsToAdd, $starsToAdd, $userId]);
            }

            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Could not mark lesson complete', 'details' => $e->getMessage()]);
        }
    }
}
