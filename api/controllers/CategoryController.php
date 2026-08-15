<?php
// api/controllers/CategoryController.php

class CategoryController {
    private static function requireAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Admin access required']);
            exit;
        }
    }

    public static function getStages($pdo) {
        $stmt = $pdo->query("SELECT * FROM stages ORDER BY id ASC");
        $stages = $stmt->fetchAll();
        foreach ($stages as &$stage) {
            $stage['name'] = htmlspecialchars($stage['name'], ENT_QUOTES, 'UTF-8');
            $stage['description'] = htmlspecialchars($stage['description'] ?? '', ENT_QUOTES, 'UTF-8');
        }
        echo json_encode($stages);
    }

    public static function createStage($pdo) {
        self::requireAdmin();
        $input = json_decode(file_get_contents('php://input'), true);
        $name = filter_var($input['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $desc = filter_var($input['description'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (!$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Name required']);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO stages (name, description) VALUES (?, ?)");
        $stmt->execute([$name, $desc]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    }

    public static function editStage($pdo) {
        self::requireAdmin();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        $name = filter_var($input['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (!$id || !$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid ID and Name required']);
            return;
        }

        $stmt = $pdo->prepare("UPDATE stages SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        echo json_encode(['success' => true]);
    }

    public static function deleteStage($pdo) {
        self::requireAdmin();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid ID required']);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM stages WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }

    public static function getSubjects($pdo, $rawStageId) {
        $stageId = filter_var($rawStageId, FILTER_VALIDATE_INT);
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $isStudent = isset($_SESSION['role']) && $_SESSION['role'] === 'student';
        $userId = $_SESSION['user_id'] ?? 0;

        if ($isStudent && $userId) {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM user_subjects WHERE user_id = ?");
            $checkStmt->execute([$userId]);
            $hasSubjects = $checkStmt->fetchColumn() > 0;

            if ($hasSubjects) {
                if ($stageId) {
                    $stmt = $pdo->prepare("SELECT s.* FROM subjects s JOIN user_subjects us ON s.id = us.subject_id WHERE s.stage_id = ? AND us.user_id = ? ORDER BY s.id ASC");
                    $stmt->execute([$stageId, $userId]);
                } else {
                    $stmt = $pdo->prepare("SELECT s.* FROM subjects s JOIN user_subjects us ON s.id = us.subject_id WHERE us.user_id = ? ORDER BY s.id ASC");
                    $stmt->execute([$userId]);
                }
            } else {
                if ($stageId) {
                    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE stage_id = ? ORDER BY id ASC");
                    $stmt->execute([$stageId]);
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE stage_id = (SELECT stage_id FROM users WHERE id = ?) ORDER BY id ASC");
                    $stmt->execute([$userId]);
                }
            }
        } else {
            if ($stageId) {
                $stmt = $pdo->prepare("SELECT * FROM subjects WHERE stage_id = ? ORDER BY id ASC");
                $stmt->execute([$stageId]);
            } else {
                $stmt = $pdo->query("SELECT * FROM subjects ORDER BY id ASC");
            }
        }

        $subjects = $stmt->fetchAll();
        foreach ($subjects as &$sub) {
            $sub['name'] = htmlspecialchars($sub['name'], ENT_QUOTES, 'UTF-8');
            $sub['description'] = htmlspecialchars($sub['description'] ?? '', ENT_QUOTES, 'UTF-8');
        }
        echo json_encode($subjects);
    }

    public static function createSubject($pdo) {
        self::requireAdmin();
        $input = json_decode(file_get_contents('php://input'), true);
        $stageId = filter_var($input['stage_id'] ?? null, FILTER_VALIDATE_INT);
        $name = filter_var($input['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $desc = filter_var($input['description'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $isCore = isset($input['is_core']) ? (bool)$input['is_core'] : true;

        if (!$stageId || !$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid Stage ID and Name required']);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO subjects (stage_id, name, description, is_core) VALUES (?, ?, ?, ?)");
        $stmt->execute([$stageId, $name, $desc, $isCore ? 1 : 0]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    }

    public static function editSubject($pdo) {
        self::requireAdmin();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        $name = filter_var($input['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $isCore = isset($input['is_core']) ? (bool)$input['is_core'] : true;

        if (!$id || !$name) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid ID and Name required']);
            return;
        }

        $stmt = $pdo->prepare("UPDATE subjects SET name = ?, is_core = ? WHERE id = ?");
        $stmt->execute([$name, $isCore ? 1 : 0, $id]);
        echo json_encode(['success' => true]);
    }

    public static function deleteSubject($pdo) {
        self::requireAdmin();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid ID required']);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true]);
    }
}

