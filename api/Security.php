<?php
class Security {
    /**
     * Sanitize input (XSS prevention)
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitize($value);
            }
            return $data;
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate CSRF Token
     */
    public static function generateCSRFToken() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF Token
     */
    public static function verifyCSRFToken($token) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Output CSRF input field
     */
    public static function csrfField() {
        $token = self::generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Get secret key for media tokens
     */
    public static function getSecretKey() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['media_secret'])) {
            $_SESSION['media_secret'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['media_secret'];
    }

    /**
     * Generate HMAC token for streaming media
     */
    public static function generateMediaToken($materialId, $userId) {
        return hash_hmac('sha256', $materialId . '_' . $userId, self::getSecretKey());
    }

    /**
     * Verify HMAC token for streaming media
     */
    public static function verifyMediaToken($materialId, $userId, $token) {
        if (empty($token)) return false;
        $expected = self::generateMediaToken($materialId, $userId);
        return hash_equals($expected, $token);
    }
}
