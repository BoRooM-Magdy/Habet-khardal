<?php
session_start();
require_once __DIR__ . '/api/db.php';


$isLoggedIn = false;
$role = null;

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $validUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($validUser) {
        $isLoggedIn = true;
        $role = $validUser['role'];
        $_SESSION['role'] = $role; // Ensure role is synced
    } else {
        // User was deleted from DB but session remains -> Force Logout
        session_unset();
        session_destroy();
    }
}
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Base path extraction (handles if running in a subdirectory like /School/WEB/system)
$base_path = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
if ($base_path === '/') $base_path = '';
$path = substr($request_uri, strlen($base_path));
if ($path === '' || $path === '/') {
    $path = '/login';
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = $protocol . "://" . $host . $base_path;

// Redirects based on auth
if (!$isLoggedIn && $path !== '/login') {
    header("Location: $base_url/login");
    exit;
}
if ($isLoggedIn && $path === '/login') {
    if ($role === 'admin') {
        header("Location: $base_url/admin");
    } else {
        header("Location: $base_url/student");
    }
    exit;
}

// Route handler
switch ($path) {
    case '/login':
        require __DIR__ . '/views/login.php';
        break;
    case '/player':
        $id = $_GET['id'] ?? 0;
        header("Location: $base_url/student?tab=player&id=$id");
        exit;
    case '/exam_take':
        $id = $_GET['id'] ?? 0;
        header("Location: $base_url/student?tab=exam_take&id=$id");
        exit;
    case '/student':
        if ($role !== 'student' && $role !== 'admin') {
            http_response_code(403);
            die("Access Denied");
        }
        require __DIR__ . '/views/student.php';
        break;
    case '/admin':
        if ($role !== 'admin') {
            http_response_code(403);
            die("Access Denied");
        }
        require __DIR__ . '/views/admin.php';
        break;
    case '/admin/tab':
        if ($role !== 'admin') {
            http_response_code(403);
            die("Access Denied");
        }
        $tabName = $_GET['name'] ?? 'stats';
        $tabFile = __DIR__ . '/views/admin/tabs/' . basename($tabName) . '.php';
        if (file_exists($tabFile)) {
            require $tabFile;
        } else {
            echo "<div class='alert alert-warning'>عذراً، محتوى هذا القسم غير متوفر حالياً.</div>";
        }
        break;
    case '/student/tab':
        if ($role !== 'student' && $role !== 'admin') {
            http_response_code(403);
            die("Access Denied");
        }
        $tabName = $_GET['name'] ?? 'dashboard';
        $tabFile = __DIR__ . '/views/student/tabs/' . basename($tabName) . '.php';
        if (file_exists($tabFile)) {
            require $tabFile;
        } else {
            echo "<div class='alert alert-warning text-center mt-5'>هذه الصفحة غير موجودة أو قيد التطوير.</div>";
        }
        break;
    default:
        http_response_code(404);
        require __DIR__ . '/views/404.php';
        break;
}
