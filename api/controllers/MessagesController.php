<?php
require_once __DIR__ . '/../core/Response.php';

class MessagesController {
    private static function requireAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            Response::error('Forbidden: Admin access required', 403);
            exit;
        }
    }
    
    public static function getStudentMessages($pdo, $userId) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $userId = $_SESSION['user_id'] ?? 0;
        }
        if (!$userId) {
            Response::error('User ID required', 400);
            return;
        }

        $stmt = $pdo->prepare("
            SELECT m.*, u.child_name, u.gender, u.last_active,
                   r.text as reply_to_text, r.sender_type as reply_to_sender, r.media_type as reply_to_media_type
            FROM messages m
            JOIN users u ON m.user_id = u.id
            LEFT JOIN messages r ON m.reply_to_id = r.id
            WHERE m.user_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$userId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::json(['messages' => $messages]);
    }

    // Get all message threads for admin (grouped by user)
    public static function getAdminThreads($pdo) {
        self::requireAdmin();
        $stmt = $pdo->query("
            SELECT 
                u.id as user_id, u.child_name, u.gender, u.stage_id, u.level, u.last_active,
                m.text as last_message,
                m.created_at as last_message_time,
                COALESCE(unread.unread_count, 0) as unread_count
            FROM users u
            LEFT JOIN (
                SELECT user_id, MAX(created_at) as max_created_at
                FROM messages
                GROUP BY user_id
            ) latest_msg ON latest_msg.user_id = u.id
            LEFT JOIN messages m ON m.user_id = latest_msg.user_id AND m.created_at = latest_msg.max_created_at
            LEFT JOIN (
                SELECT user_id, COUNT(*) as unread_count
                FROM messages
                WHERE sender_type = 'student' AND is_read = 0
                GROUP BY user_id
            ) unread ON unread.user_id = u.id
            WHERE u.role = 'student'
            ORDER BY 
                CASE WHEN unread.unread_count > 0 THEN 1 ELSE 2 END,
                m.created_at DESC, 
                u.created_at DESC
        ");
        $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::json(['threads' => $threads]);
    }
    
    // Get a specific thread for admin
    public static function getAdminThreadMessages($pdo, $userId) {
        self::requireAdmin();
        $stmt = $pdo->prepare("
            SELECT m.*, u.child_name, u.gender, u.last_active,
                   r.text as reply_to_text, r.sender_type as reply_to_sender, r.media_type as reply_to_media_type
            FROM messages m
            JOIN users u ON m.user_id = u.id
            LEFT JOIN messages r ON m.reply_to_id = r.id
            WHERE m.user_id = ?
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$userId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        Response::json(['messages' => $messages]);
    }

    // Send a message
    public static function sendMessage($pdo, $userId, $senderType, $text, $replyToId = null, $mediaUrl = null, $mediaType = null) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $userId = $_SESSION['user_id'] ?? 0;
            $senderType = 'student';
        }
        if (!$userId) {
            Response::error('User ID required', 400);
            return;
        }
        if (empty(trim($text)) && empty($mediaUrl)) {
            Response::error('Message cannot be empty');
        }

        $stmt = $pdo->prepare("INSERT INTO messages (user_id, sender_type, text, reply_to_id, media_url, media_type) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $senderType, $text, $replyToId, $mediaUrl, $mediaType]);
        
        Response::success();
    }

    public static function uploadMedia($pdo) {
        if (!isset($_FILES['media'])) {
            Response::error('No media file provided');
        }

        $file = $_FILES['media'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!$ext && strpos($file['type'], 'audio') !== false) $ext = 'webm';
        if (!$ext && strpos($file['type'], 'image') !== false) $ext = 'jpg';
        
        $filename = uniqid() . '.' . $ext;
        $allowedExtensions = ['jpg','jpeg','png','gif','webp','mp3','wav','ogg','webm','m4a','mp4'];
        if (!in_array(strtolower($ext), $allowedExtensions)) {
            Response::error('File type not allowed', 400);
        }
        $targetDir = __DIR__ . '/../../api/uploads/';
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        
        $targetFile = $targetDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            Response::success(['url' => 'api/uploads/' . $filename]);
        } else {
            Response::error('Failed to upload media', 500);
        }
    }

    // Broadcast a message to all students in a stage
    public static function broadcastMessage($pdo, $stage_id, $text) {
        self::requireAdmin();
        if (empty(trim($text))) {
            Response::error('Message text cannot be empty');
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'student' AND stage_id = ?");
        $stmt->execute([$stage_id]);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($students)) {
            Response::error('لا يوجد طلاب مسجلين في هذه المرحلة بعد.', 404);
        }

        $values = [];
        $params = [];
        foreach ($students as $student) {
            $values[] = "(?, 'admin', ?)";
            $params[] = $student['id'];
            $params[] = $text;
        }

        $sql = "INSERT INTO messages (user_id, sender_type, text) VALUES " . implode(", ", $values);
        $insertStmt = $pdo->prepare($sql);
        $insertStmt->execute($params);

        require_once __DIR__ . '/NotificationController.php';
        NotificationController::sendToStudents($pdo, $stage_id, null, 'info', 'إعلان من الإدارة 📢', $text, 'chat');

        Response::success(['message' => 'Broadcast sent to ' . count($students) . ' students']);
    }

    // Mark thread as read
    public static function markAsRead($pdo, $userId, $readerType) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $userId = $_SESSION['user_id'] ?? 0;
            $readerType = 'student';
        }
        if (!$userId) {
            Response::error('User ID required', 400);
            return;
        }
        $senderToMark = $readerType === 'admin' ? 'student' : 'admin';
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE user_id = ? AND sender_type = ? AND is_read = 0");
        $stmt->execute([$userId, $senderToMark]);
        
        Response::success();
    }

    // Get unread count
    public static function getUnreadCount($pdo, $userId, $role) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $role = 'student';
            $userId = $_SESSION['user_id'] ?? 0;
        }
        if ($role === 'admin') {
            $stmt = $pdo->query("SELECT COUNT(*) FROM messages WHERE sender_type = 'student' AND is_read = 0");
            $count = $stmt->fetchColumn();
        } else {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE user_id = ? AND sender_type = 'admin' AND is_read = 0");
            $stmt->execute([$userId]);
            $count = $stmt->fetchColumn();
        }
        Response::json(['unread_count' => (int)$count]);
    }
}
