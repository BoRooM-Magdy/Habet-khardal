<?php
class RecitationController {
    private static function requireAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Admin access required']);
            exit;
        }
    }

    public static function getRecitations($pdo, $userId = null) {
        if ($userId) {
            $stmt = $pdo->prepare("
                SELECT r.*, m.title as material_title, s.name as subject_name 
                FROM recitations r
                LEFT JOIN materials m ON r.material_id = m.id
                LEFT JOIN subjects s ON m.subject_id = s.id
                WHERE r.user_id = ? ORDER BY r.created_at DESC
            ");
            $stmt->execute([$userId]);
        } else {
            // For admin
            $stmt = $pdo->query("
                SELECT r.*, u.child_name, m.title as material_title, s.name as subject_name 
                FROM recitations r
                LEFT JOIN users u ON r.user_id = u.id
                LEFT JOIN materials m ON r.material_id = m.id
                LEFT JOIN subjects s ON m.subject_id = s.id
                ORDER BY r.created_at DESC
            ");
        }
        
        $recitations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($recitations);
    }

    public static function submitRecitation($pdo) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $materialId = filter_input(INPUT_POST, 'material_id', FILTER_VALIDATE_INT);

        if (!$userId && isset($_POST['user_id'])) $userId = $_POST['user_id'];
        if (!$materialId && isset($_POST['material_id'])) $materialId = $_POST['material_id'];

        if (($_SESSION['role'] ?? '') !== 'admin') {
            $userId = $_SESSION['user_id'] ?? 0;
        }

        $audioFile = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $audioFile = $_FILES['file'];
        } elseif (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
            $audioFile = $_FILES['audio'];
        }

        if (!$userId || !$materialId || !$audioFile) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID, Material ID, and Audio File are required']);
            return;
        }

        $uploadDir = __DIR__ . '/../uploads/recitations/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($audioFile['name']));
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['mp3','wav','ogg','webm','m4a','aac'];
        if (!in_array($ext, $allowedExtensions)) {
            http_response_code(400);
            echo json_encode(['error' => 'File type not allowed. Audio files only.']);
            return;
        }
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($audioFile['tmp_name'], $targetFile)) {
            $audioPath = 'uploads/recitations/' . $fileName;

            // Check if already submitted and pending
            $stmtCheck = $pdo->prepare("SELECT id FROM recitations WHERE user_id = ? AND material_id = ?");
            $stmtCheck->execute([$userId, $materialId]);
            if ($stmtCheck->fetchColumn()) {
                // Update existing submission
                $stmt = $pdo->prepare("UPDATE recitations SET audio_path = ?, status = 'pending', stars = 0, notes = NULL, created_at = CURRENT_TIMESTAMP WHERE user_id = ? AND material_id = ?");
                $stmt->execute([$audioPath, $userId, $materialId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO recitations (user_id, material_id, audio_path) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $materialId, $audioPath]);
            }

            echo json_encode(['success' => true, 'path' => $audioPath]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save audio file']);
        }
    }

    public static function reviewRecitation($pdo) {
        self::requireAdmin();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        $stars = filter_var($input['stars'] ?? 0, FILTER_VALIDATE_INT);
        $notes = filter_var($input['notes'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Recitation ID required']);
            return;
        }

        // Update recitation
        $stmt = $pdo->prepare("UPDATE recitations SET status = 'reviewed', stars = ?, notes = ? WHERE id = ?");
        $stmt->execute([$stars, $notes, $id]);

        if ($stars > 0) {
            // Find user id for this recitation
            $stmtUser = $pdo->prepare("SELECT user_id FROM recitations WHERE id = ?");
            $stmtUser->execute([$id]);
            $userId = $stmtUser->fetchColumn();

            if ($userId) {
                $points = $stars * 10;
                $stmtAdd = $pdo->prepare("UPDATE users SET stars = stars + ?, points = points + ? WHERE id = ?");
                $stmtAdd->execute([$stars, $points, $userId]);
            }
        }

        echo json_encode(['success' => true]);
    }

    public static function deleteRecitation($pdo) {
        self::requireAdmin();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid ID is required']);
            return;
        }

        try {
            // Delete associated audio file
            $stmt = $pdo->prepare("SELECT audio_path FROM recitations WHERE id = ?");
            $stmt->execute([$id]);
            $rec = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($rec && !empty($rec['audio_path'])) {
                $filePath = __DIR__ . '/../' . $rec['audio_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            $stmt = $pdo->prepare("DELETE FROM recitations WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete recitation']);
        }
    }
}
