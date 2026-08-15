<?php
// api/routes.php

require_once __DIR__ . '/core/Router.php';
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/CategoryController.php';
require_once __DIR__ . '/controllers/MaterialController.php';
require_once __DIR__ . '/controllers/AdminController.php';
require_once __DIR__ . '/controllers/HomeworkController.php';
require_once __DIR__ . '/controllers/ProgressController.php';
require_once __DIR__ . '/controllers/ExamController.php';
require_once __DIR__ . '/controllers/MessagesController.php';
require_once __DIR__ . '/controllers/SettingsController.php';
require_once __DIR__ . '/controllers/RecitationController.php';
require_once __DIR__ . '/controllers/MediaProxyController.php';
require_once __DIR__ . '/controllers/NotificationController.php';

function dispatch($method, $path, $pdo) {
    $router = new Router();

    // -- Media Proxy --
    $router->get('/media/stream', function($pdo) { MediaProxyController::stream($pdo); });

    // -- Auth --
    $router->post('/auth/login', ['AuthController', 'login']);
    $router->post('/auth/register', ['AuthController', 'register']);
    $router->post('/auth/update', ['AuthController', 'update']);
    $router->post('/auth/logout', function($pdo) {
        session_unset();
        session_destroy();
        Response::success();
    });
    $router->post('/auth/ping', function($pdo) {
        $userId = $_SESSION['user_id'] ?? null;
        if ($userId) {
            $stmt = $pdo->prepare("UPDATE users SET last_active = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$userId]);
            Response::success();
        } else {
            Response::error('Unauthorized', 401);
        }
    });

    // -- Stages --
    $router->get('/stages', ['CategoryController', 'getStages']);
    $router->post('/stages/edit', ['CategoryController', 'editStage']);
    $router->post('/stages', ['CategoryController', 'createStage']);
    $router->delete('/stages', ['CategoryController', 'deleteStage']);

    // -- Subjects --
    $router->get('/subjects', function($pdo) { CategoryController::getSubjects($pdo, $_GET['stage_id'] ?? null); });
    $router->post('/subjects/edit', ['CategoryController', 'editSubject']);
    $router->post('/subjects', ['CategoryController', 'createSubject']);
    $router->delete('/subjects', ['CategoryController', 'deleteSubject']);

    // -- Materials --
    $router->get('/materials', function($pdo) { MaterialController::getMaterials($pdo, $_GET['subject_id'] ?? null); });
    $router->post('/materials/edit', ['MaterialController', 'editMaterial']);
    $router->post('/materials', ['MaterialController', 'uploadMaterial']);
    $router->delete('/materials', ['MaterialController', 'deleteMaterial']);

    // -- Student Tab (Special) --
    $router->get('/student/tab', function($pdo) {
        $tabName = filter_input(INPUT_GET, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'dashboard';
        
        // Populate $user for the views
        global $user;
        $stmt = $pdo->prepare("SELECT u.*, s.name as stage_name FROM users u LEFT JOIN stages s ON u.stage_id = s.id WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id'] ?? 0]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Decode name if it contained query params that were escaped
        $tabName = htmlspecialchars_decode($tabName);
        $tabNameBase = explode('&', $tabName)[0];
        
        $tabPath = __DIR__ . '/../views/student/tabs/' . basename($tabNameBase) . '.php';
        if (file_exists($tabPath)) {
            require $tabPath;
        } else {
            Response::error("Tab not found.", 404);
        }
    });
    
    // -- Admin Tab (Special) --
    $router->get('/admin/tab', function($pdo) {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            Response::error('Forbidden: Admin access required.', 403);
            return;
        }
        $tabName = filter_input(INPUT_GET, 'name', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 'stats';
        
        // Decode name if it contained query params
        $tabName = htmlspecialchars_decode($tabName);
        $tabNameBase = explode('&', $tabName)[0];
        
        $tabPath = __DIR__ . '/../views/admin/tabs/' . basename($tabNameBase) . '.php';
        if (file_exists($tabPath)) {
            require $tabPath;
        } else {
            Response::error("Tab not found.", 404);
        }
    });

    // -- Exams --
    $router->get('/exams/stats', ['ExamController', 'getExamStats']);
    // Fallback for exams logic since it relies on $action dynamically
    $router->get('/exams', function($pdo) {
        $action = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');
        $action = explode('/', $action)[2] ?? '';
        ExamController::getExam($pdo, $action);
    });
    $router->post('/exams/submit', ['ExamController', 'submitExam']);
    $router->post('/exams', ['ExamController', 'createExam']);

    // -- Stats --
    $router->get('/stats', ['AdminController', 'getStats']);

    // -- Students --
    $router->get('/students', function($pdo) {
        if (isset($_GET['profile']) && isset($_GET['id'])) {
            AdminController::getStudentProfile($pdo);
        } else {
            AdminController::getStudents($pdo);
        }
    });
    $router->post('/students/edit', ['AdminController', 'editStudent']);
    $router->delete('/students', ['AdminController', 'deleteStudent']);

    // -- Homeworks --
    $router->get('/homeworks/submissions', ['HomeworkController', 'getAllSubmissions']);
    $router->get('/homeworks', function($pdo) {
        if (isset($_GET['admin'])) {
            HomeworkController::getAllHomeworks($pdo);
        } else {
            HomeworkController::getHomeworks($pdo, $_GET['user_id'] ?? null);
        }
    });
    $router->post('/homeworks/submit', ['HomeworkController', 'submitHomework']);
    $router->post('/homeworks/grade', ['HomeworkController', 'gradeHomework']);
    $router->post('/homeworks/create', ['HomeworkController', 'createHomework']);
    $router->delete('/homeworks', ['HomeworkController', 'deleteHomework']);

    $router->delete('/exams', ['ExamController', 'deleteExam']);

    // -- Recitations --
    $router->get('/recitations', function($pdo) { RecitationController::getRecitations($pdo, $_GET['user_id'] ?? null); });
    $router->post('/recitations/review', ['RecitationController', 'reviewRecitation']);
    $router->post('/recitations/submit', ['RecitationController', 'submitRecitation']);
    $router->post('/recitations', ['RecitationController', 'submitRecitation']);
    $router->delete('/recitations', ['RecitationController', 'deleteRecitation']);

    // -- Progress --
    $router->get('/progress', function($pdo) { ProgressController::getUserProgress($pdo, $_GET['user_id'] ?? null); });
    $router->post('/progress', ['ProgressController', 'markLessonComplete']);

    // -- Messages --
    $router->get('/messages/unread_count', function($pdo) {
        MessagesController::getUnreadCount($pdo, $_GET['user_id'] ?? null, $_GET['role'] ?? 'student');
    });
    $router->get('/messages', function($pdo) {
        if (isset($_GET['admin_threads'])) {
            MessagesController::getAdminThreads($pdo);
        } elseif (isset($_GET['admin_thread']) && isset($_GET['user_id'])) {
            MessagesController::getAdminThreadMessages($pdo, $_GET['user_id']);
        } elseif (isset($_GET['user_id'])) {
            MessagesController::getStudentMessages($pdo, $_GET['user_id']);
        }
    });
    $router->post('/messages/read', function($pdo) {
        MessagesController::markAsRead($pdo, $_POST['user_id'] ?? null, $_POST['reader_type'] ?? null);
    });
    $router->post('/messages/broadcast', function($pdo) {
        MessagesController::broadcastMessage($pdo, $_POST['stage_id'] ?? null, $_POST['text'] ?? '');
    });
    $router->post('/messages/upload', ['MessagesController', 'uploadMedia']);
    $router->post('/messages', function($pdo) {
        MessagesController::sendMessage(
            $pdo,
            $_POST['user_id'] ?? null,
            $_POST['sender_type'] ?? null,
            $_POST['text'] ?? '',
            $_POST['reply_to_id'] ?? null,
            $_POST['media_url'] ?? null,
            $_POST['media_type'] ?? null
        );
    });

    // -- Notifications --
    $router->get('/notifications', ['NotificationController', 'getNotifications']);
    $router->post('/notifications/read', ['NotificationController', 'markAsRead']);

    // -- Settings --
    $router->get('/settings', ['SettingsController', 'getSettings']);
    $router->post('/settings', ['SettingsController', 'updateSettings']);

    // Dispatch the request
    $router->dispatch($pdo);
}
