<?php
// router.php for dev server
$path = $_SERVER["DOCUMENT_ROOT"] . $_SERVER["REQUEST_URI"];
$request_uri = $_SERVER["REQUEST_URI"];

// Block direct access to uploads directory
if (strpos($request_uri, '/api/uploads/') === 0 || strpos($request_uri, '/uploads/') === 0) {
    http_response_code(403);
    echo "<h1>403 Forbidden</h1><p>Direct access to media files is not allowed.</p>";
    return true;
}

if (is_file($path)) {
    return false;
}

if (strpos($request_uri, '/api/') === 0 || strpos($request_uri, '/system/api/') === 0 || strpos($request_uri, '/sw-media/') === 0) {
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';
    require __DIR__ . '/api/index.php';
} else {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    require __DIR__ . '/index.php';
}
return true;
