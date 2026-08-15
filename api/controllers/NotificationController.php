<?php
// api/controllers/NotificationController.php

require_once __DIR__ . '/../core/Response.php';

class NotificationController {

    // Send notification to a single user
    public static function sendToUser($pdo, $userId, $type, $title, $message, $link = null) {
        if (!$userId) return false;
        try {
            $stmt = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)");
            return $stmt->execute([$userId, $type, $title, $message, $link]);
        } catch (Exception $e) {
            return false;
        }
    }

    // Send notification to students (filtered by stage_id / subject_id if provided)
    public static function sendToStudents($pdo, $stageId = null, $subjectId = null, $type = 'info', $title = '', $message = '', $link = null) {
        try {
            $sql = "SELECT DISTINCT u.id FROM users u WHERE u.role = 'student'";
            $params = [];

            if ($stageId) {
                $sql .= " AND u.stage_id = ?";
                $params[] = $stageId;
            }

            if ($subjectId) {
                // Check if core subject
                $stmtSub = $pdo->prepare("SELECT is_core, stage_id FROM subjects WHERE id = ?");
                $stmtSub->execute([$subjectId]);
                $subject = $stmtSub->fetch(PDO::FETCH_ASSOC);

                if ($subject) {
                    if (empty($subject['is_core'])) {
                        // Optional subject: only students assigned in user_subjects
                        $sql .= " AND EXISTS (SELECT 1 FROM user_subjects us WHERE us.user_id = u.id AND us.subject_id = ?)";
                        $params[] = $subjectId;
                    } elseif ($subject['stage_id'] && !$stageId) {
                        $sql .= " AND u.stage_id = ?";
                        $params[] = $subject['stage_id'];
                    }
                }
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $students = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($students)) {
                $ins = $pdo->prepare("INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, 0, CURRENT_TIMESTAMP)");
                foreach ($students as $uid) {
                    $ins->execute([$uid, $type, $title, $message, $link]);
                }
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    // GET /api/notifications
    public static function getNotifications($pdo) {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            Response::error('Unauthorized', 401);
            return;
        }

        try {
            // Unread count
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
            $stmtCount->execute([$userId]);
            $unreadCount = (int) $stmtCount->fetchColumn();

            // Fetch top 30 recent notifications
            $stmtList = $pdo->prepare("SELECT id, type, title, message, link, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY is_read ASC, created_at DESC LIMIT 30");
            $stmtList->execute([$userId]);
            $notifications = $stmtList->fetchAll(PDO::FETCH_ASSOC);

            Response::json([
                'success' => true,
                'unread_count' => $unreadCount,
                'notifications' => $notifications
            ]);
        } catch (Exception $e) {
            Response::error('Failed to load notifications: ' . $e->getMessage(), 500);
        }
    }

    // POST /api/notifications/read
    public static function markAsRead($pdo) {
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            Response::error('Unauthorized', 401);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $markAll = !empty($input['mark_all']);
        $notifId = !empty($input['id']) ? (int) $input['id'] : null;

        try {
            if ($markAll) {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
                $stmt->execute([$userId]);
            } elseif ($notifId) {
                $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
                $stmt->execute([$notifId, $userId]);
            }
            Response::success();
        } catch (Exception $e) {
            Response::error('Failed to mark read: ' . $e->getMessage(), 500);
        }
    }
}
