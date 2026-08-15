<?php
// api/core/Response.php

class Response {
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data);
        exit; // Enforce stopping execution after response
    }

    public static function error($message, $statusCode = 400) {
        self::json(['error' => $message], $statusCode);
    }

    public static function success($data = []) {
        $response = ['success' => true];
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        self::json($response);
    }
}
