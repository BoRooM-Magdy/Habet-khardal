<?php
// api/index.php

// Enable CORS & Prevent Caching
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure errors are returned as JSON
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
set_exception_handler(function($e) {
    error_log($e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
    exit;
});

// Include database and Security
require_once __DIR__ . '/db.php';

// Global CSRF Protection Middleware
if (in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT', 'DELETE'])) {
    $headers = getallheaders();
    $token = $headers['X-CSRF-TOKEN'] ?? $headers['x-csrf-token'] ?? $_POST['csrf_token'] ?? '';
    
    if (!Security::verifyCSRFToken($token)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token validation failed.']);
        exit;
    }
}

// Simple Router
$requestUri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Parse URL
$parsedUrl = parse_url($requestUri);
$path = $parsedUrl['path'];

// Remove the base path (if running in a subfolder like /api or /sw-media/api)
$basePathSW = '/sw-media/api';
if (strpos($path, $basePathSW) === 0) {
    $path = substr($path, strlen($basePathSW));
} else {
    $basePath = '/api';
    if (strpos($path, $basePath) === 0) {
        $path = substr($path, strlen($basePath));
    }
}
if ($path === '') $path = '/';

// Require routes definition
require_once __DIR__ . '/routes.php';

// Dispatch
dispatch($method, $path, $pdo);
