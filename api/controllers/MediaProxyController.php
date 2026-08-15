<?php
// api/controllers/MediaProxyController.php

class MediaProxyController {

    public static function stream($pdo) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (preg_match('/(IDM|Internet Download Manager|curl|wget|python-requests|Postman)/i', $userAgent)) {
            http_response_code(403);
            echo "<h1>403 Forbidden</h1><p>Direct downloading is strictly prohibited by Security System.</p>";
            return;
        }

        $isFromSW = isset($_SERVER['HTTP_X_ANTI_IDM']) && $_SERVER['HTTP_X_ANTI_IDM'] === 'true';
        $secFetchDest = $_SERVER['HTTP_SEC_FETCH_DEST'] ?? '';
        
        // Block if user typed URL directly in browser address bar (sec-fetch-dest: document) unless from Service Worker
        if (!$isFromSW && $secFetchDest === 'document') {
            http_response_code(403);
            echo "<h1>403 Forbidden</h1><p>Direct access to media links is not allowed.</p>";
            return;
        }

        // Ensure user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $userId = $_SESSION['user_id'];
        $role = $_SESSION['role'] ?? 'student';
        $materialId = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $token = $_GET['token'] ?? '';

        require_once __DIR__ . '/../Security.php';
        if (!Security::verifyMediaToken($materialId, $userId, $token)) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Invalid or expired media token']);
            return;
        }

        if ($materialId <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid material ID']);
            return;
        }

        // Fetch material details
        $stmt = $pdo->prepare("SELECT m.file_path, m.type, m.subject_id FROM materials m WHERE m.id = ?");
        $stmt->execute([$materialId]);
        $material = $stmt->fetch();

        if (!$material || empty($material['file_path'])) {
            http_response_code(404);
            echo json_encode(['error' => 'Material not found']);
            return;
        }

        // Prevent path traversal attacks
        if (strpos($material['file_path'], '..') !== false || strpos($material['file_path'], "\0") !== false) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file path traversal attempt']);
            return;
        }

        // If the student, check if they have access to the subject based on their plan
        if ($role === 'student') {
            $stmtUser = $pdo->prepare("SELECT plan, stage_id FROM users WHERE id = ?");
            $stmtUser->execute([$userId]);
            $user = $stmtUser->fetch();
            $plan = $user['plan'] ?? 'بذرة';
            
            $stmtSubj = $pdo->prepare("SELECT is_core FROM subjects WHERE id = ?");
            $stmtSubj->execute([$material['subject_id']]);
            $is_core = $stmtSubj->fetchColumn();

            $hasAccess = false;
            
            if ($plan === 'شجرة') {
                $hasAccess = true;
            } elseif ($plan === 'نبتة') {
                if ($is_core) {
                    $hasAccess = true;
                } else {
                    $stmtAccess = $pdo->prepare("SELECT 1 FROM user_subjects WHERE user_id = ? AND subject_id = ?");
                    $stmtAccess->execute([$userId, $material['subject_id']]);
                    if ($stmtAccess->fetch()) $hasAccess = true;
                }
            } else {
                // بذرة
                if ($is_core) {
                    $hasAccess = true;
                }
            }

            if (!$hasAccess) {
                header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
                http_response_code(403);
                echo json_encode(['error' => 'Access denied to this subject']);
                return;
            }
        }

        $filePath = __DIR__ . '/../' . ltrim($material['file_path'], '/');

        if (!file_exists($filePath)) {
            http_response_code(404);
            echo json_encode(['error' => 'File physically missing on server: ' . basename($filePath)]);
            return;
        }

        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers for successful streaming
        $mimeType = self::getMimeType($filePath);
        $fileSize = filesize($filePath);
        
        header_remove("Cache-Control");
        header_remove("Pragma");
        header("Cache-Control: private, max-age=3600");

        // Set headers
        header("Content-Type: " . $mimeType);
        
        header("Content-Disposition: inline; filename=\"stream_data.bin\"");
        header("Accept-Ranges: bytes");

        if (isset($_SERVER['HTTP_RANGE'])) {
            list($a, $range) = explode("=", $_SERVER['HTTP_RANGE'], 2);
            list($range) = explode(",", $range, 2);
            list($range, $range_end) = explode("-", $range);
            $range = intval($range);
            if (!$range_end) {
                $range_end = $fileSize - 1;
            } else {
                $range_end = intval($range_end);
            }

            $new_length = $range_end - $range + 1;
            header("HTTP/1.1 206 Partial Content");
            header("Content-Length: $new_length");
            header("Content-Range: bytes $range-$range_end/$fileSize");

            $fp = fopen($filePath, 'rb');
            fseek($fp, $range);
            
            // Output in chunks
            $chunkSize = 1024 * 1024; // 1MB chunks
            while (!feof($fp) && ($p = ftell($fp)) <= $range_end) {
                if ($p + $chunkSize > $range_end) {
                    $chunkSize = $range_end - $p + 1;
                }
                echo fread($fp, $chunkSize);
                flush();
            }
            fclose($fp);
            exit;
        } else {
            header("Content-Length: " . $fileSize);
            readfile($filePath);
            exit;
        }
    }

    private static function getMimeType($file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mimes = [
            'mp4' => 'video/mp4', // Must be video/mp4 for browsers to play it reliably
            'webm' => 'video/webm',
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jfif' => 'image/jpeg',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ];
        return $mimes[$ext] ?? 'application/octet-stream';
    }
}
