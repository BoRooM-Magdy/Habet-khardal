<?php
// api/db.php
require_once __DIR__ . '/Security.php';

$_GET = Security::sanitize($_GET);
$_POST = Security::sanitize($_POST);
$_REQUEST = Security::sanitize($_REQUEST);

$host = '127.0.0.1';
$db   = 'school_platform';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    header('Content-Type: application/json', true, 500);
    echo json_encode([
        "error" => "Database connection failed",
        "details" => "Please ensure MySQL server is running."
    ]);
    exit;
}
