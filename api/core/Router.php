<?php
// api/core/Router.php
require_once __DIR__ . '/Response.php';

class Router {
    private $routes = [];

    public function add($method, $path, $handler) {
        $pattern = preg_replace('/\:([a-zA-Z0-9_]+)/', '(?P<\1>[a-zA-Z0-9_-]+)', $path);
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => '#^' . $pattern . '$#',
            'handler' => $handler
        ];
    }
    
    public function get($path, $handler) { $this->add('GET', $path, $handler); }
    public function post($path, $handler) { $this->add('POST', $path, $handler); }
    public function delete($path, $handler) { $this->add('DELETE', $path, $handler); }

    public function dispatch($pdo) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Strip the base path to get the actual API path
        // e.g. /system/api/auth/login -> /auth/login
        $apiPath = '';
        if (strpos($path, '/api/') !== false) {
            $apiPath = '/' . explode('/api/', $path, 2)[1];
        } else {
            $apiPath = '/' . trim($_GET['path'] ?? '', '/');
        }

        // Normalize path
        $apiPath = '/' . trim($apiPath, '/');

        $isLoggedIn = false;
        $role = null;
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $validUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($validUser) {
                $isLoggedIn = true;
                $role = $validUser['role'];
            } else {
                session_unset();
                session_destroy();
            }
        }

        $publicPaths = [
            '/auth/login', '/auth/register', '/auth/logout',
            '/subjects', '/stages'
        ];
        $isPublic = false;
        foreach ($publicPaths as $pub) {
            if ($apiPath === $pub || (in_array($pub, ['/subjects', '/stages']) && strpos($apiPath, $pub) === 0 && $method === 'GET')) {
                $isPublic = true;
                break;
            }
        }

        if (!$isLoggedIn && !$isPublic) {
            Response::error('Unauthorized. Please login again.', 401);
            return;
        }

        if ($role !== 'admin') {
            $adminPaths = [
                '/admin/', '/stats', '/students', '/settings',
                '/stages/edit', '/stages/create',
                '/subjects/edit', '/subjects/create',
                '/materials/edit',
                '/recitations/review',
                '/homeworks/submissions', '/homeworks/grade', '/homeworks/create',
                '/exams/stats', '/messages/broadcast', '/messages/unread_count'
            ];
            // Check specific paths
            foreach ($adminPaths as $admPath) {
                if (strpos($apiPath, $admPath) === 0 && $admPath !== '/messages/unread_count') {
                    Response::error('Forbidden: Admin access required.', 403);
                    return;
                }
            }
            // Check mutations on categories and materials
            if (in_array($method, ['POST', 'PUT', 'DELETE']) && (
                strpos($apiPath, '/stages') === 0 ||
                strpos($apiPath, '/subjects') === 0 ||
                strpos($apiPath, '/materials') === 0 ||
                strpos($apiPath, '/settings') === 0
            )) {
                Response::error('Forbidden: Admin access required for modifications.', 403);
                return;
            }
            // Check delete/create on homeworks/exams/recitations
            if ($method === 'DELETE' && (strpos($apiPath, '/homeworks') === 0 || strpos($apiPath, '/exams') === 0 || strpos($apiPath, '/recitations') === 0 || strpos($apiPath, '/students') === 0)) {
                Response::error('Forbidden: Admin access required to delete items.', 403);
                return;
            }
            // Block admin parameters if student
            if (isset($_GET['admin']) || isset($_GET['admin_threads']) || isset($_GET['admin_thread'])) {
                Response::error('Forbidden: Admin thread access denied.', 403);
                return;
            }
        }

        foreach ($this->routes as $route) {
            if ($route['method'] === strtoupper($method) && preg_match($route['pattern'], $apiPath, $matches)) {
                // Remove numeric keys
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                // Call the controller action
                call_user_func($route['handler'], $pdo, $params);
                return;
            }
        }
        
        Response::error('Endpoint Not Found', 404);
    }
}
