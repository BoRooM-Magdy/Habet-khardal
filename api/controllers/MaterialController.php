<?php
// api/controllers/MaterialController.php

class MaterialController {
    private static function requireAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Admin access required']);
            exit;
        }
    }

    public static function getMaterials($pdo, $rawSubjectId) {
        $subjectId = filter_var($rawSubjectId, FILTER_VALIDATE_INT);
        if ($subjectId) {
            $stmt = $pdo->prepare("SELECT * FROM materials WHERE subject_id = ? ORDER BY id DESC");
            $stmt->execute([$subjectId]);
        } else {
            $stmt = $pdo->query("SELECT * FROM materials ORDER BY id DESC");
        }
        
        $materials = $stmt->fetchAll();
        foreach ($materials as &$mat) {
            $mat['title'] = htmlspecialchars($mat['title'], ENT_QUOTES, 'UTF-8');
            $mat['type'] = htmlspecialchars($mat['type'], ENT_QUOTES, 'UTF-8');
            $mat['file_path'] = htmlspecialchars($mat['file_path'], ENT_QUOTES, 'UTF-8');
        }
        echo json_encode($materials);
    }

    public static function uploadMaterial($pdo) {
        self::requireAdmin();
        $subjectId = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
        $rawTitle = filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW);
        $title = filter_var($rawTitle, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        $rawType = filter_input(INPUT_POST, 'type', FILTER_UNSAFE_RAW);
        $type = filter_var($rawType, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        $allowedTypes = ['pdf', 'video', 'image', 'exam', 'audio', 'homework'];

        if (!$subjectId || !$title || !in_array($type, $allowedTypes)) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid Subject ID, Title, and allowed Type are required']);
            return;
        }

        $filePath = '';

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', basename($_FILES['file']['name']));
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg','jpeg','jfif','png','gif','webp','svg','mp4','webm','mp3','wav','ogg','pdf','doc','docx','ppt','pptx','zip'];
            if (!in_array($ext, $allowedExtensions)) {
                http_response_code(400);
                echo json_encode(['error' => 'File type not allowed: .' . $ext]);
                return;
            }
            $targetFile = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
                $filePath = 'uploads/' . $fileName;
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save uploaded file']);
                return;
            }
        } elseif ($type !== 'audio' && $type !== 'homework') {
            $errorCode = isset($_FILES['file']) ? $_FILES['file']['error'] : 'No File Sent';
            http_response_code(400);
            echo json_encode(['error' => 'Valid file upload is required for this type. Error code: ' . $errorCode]);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO materials (subject_id, title, type, file_path) VALUES (?, ?, ?, ?)");
        $stmt->execute([$subjectId, $title, $type, $filePath]);
        
        require_once __DIR__ . '/NotificationController.php';
        NotificationController::sendToStudents($pdo, null, $subjectId, 'info', 'مادة جديدة 📚', "تمت إضافة محتوى جديد: " . $title, 'subjects');

        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId(), 'file_path' => $filePath]);
    }

    public static function editMaterial($pdo) {
        self::requireAdmin();
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $rawTitle = filter_input(INPUT_POST, 'title', FILTER_UNSAFE_RAW);
        $title = filter_var($rawTitle, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        
        if (!$id || !$title) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid ID and Title are required']);
            return;
        }

        try {
            $stmt = $pdo->prepare("UPDATE materials SET title = ? WHERE id = ?");
            $stmt->execute([$title, $id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update material']);
        }
    }

    public static function deleteMaterial($pdo) {
        self::requireAdmin();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid ID is required']);
            return;
        }

        try {
            // Delete associated file from disk
            $stmt = $pdo->prepare("SELECT file_path FROM materials WHERE id = ?");
            $stmt->execute([$id]);
            $mat = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($mat && !empty($mat['file_path'])) {
                $filePath = __DIR__ . '/../' . $mat['file_path'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }

            $stmt = $pdo->prepare("DELETE FROM materials WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete material']);
        }
    }
}

