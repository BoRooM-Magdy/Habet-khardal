<?php
// api/controllers/SettingsController.php

class SettingsController {
    private static function requireAdmin() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: Admin access required']);
            exit;
        }
    }

    public static function getSettings($pdo) {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        echo json_encode(['success' => true, 'settings' => $settings]);
    }

    public static function updateSettings($pdo) {
        self::requireAdmin();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!isset($input['settings']) || !is_array($input['settings'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid settings payload']);
            return;
        }

        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value");
        foreach ($input['settings'] as $key => $value) {
            $stmt->execute([$key, $value]);
        }
        echo json_encode(['success' => true]);
    }
}
