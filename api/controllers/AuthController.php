<?php
// api/controllers/AuthController.php

class AuthController {

    public static function register($pdo) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $childName = filter_var($input['child_name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $parentName = filter_var($input['parent_name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $age = intval($input['age'] ?? 0);
        $gender = filter_var($input['gender'] ?? 'boy', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $birthDate = filter_var($input['birth_date'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $plan = filter_var($input['plan'] ?? 'بذرة', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $stageId = intval($input['stage_id'] ?? 1);
        
        $email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = trim($input['password'] ?? '');

        if (!$childName || !$parentName || !$age || !$email || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'الرجاء إدخال جميع البيانات بشكل صحيح']);
            return;
        }

        // Strict Email Regex Validation
        if (!preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
            http_response_code(400);
            echo json_encode(['error' => 'البريد الإلكتروني غير صالح. يجب أن يحتوي على @ و .com (أو ما يشابهها)']);
            return;
        }

        // Password Conditions (Min 8 chars, 1 uppercase, 1 lowercase, 1 number)
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            http_response_code(400);
            echo json_encode(['error' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل، وتحتوي على حرف كبير، حرف صغير، ورقم']);
            return;
        }

        // Check if email exists

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'Email is already registered']);
            return;
        }

        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (child_name, parent_name, age, gender, birth_date, stage_id, plan, email, password_hash, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student')");
        if ($stmt->execute([$childName, $parentName, $age, $gender, $birthDate, $stageId, $plan, $email, $hashed])) {
            $userId = $pdo->lastInsertId();

            if (isset($input['subject_ids']) && is_array($input['subject_ids']) && !empty($input['subject_ids'])) {
                $stmtSubject = $pdo->prepare("INSERT INTO user_subjects (user_id, subject_id) VALUES (?, ?)");
                foreach ($input['subject_ids'] as $sid) {
                    $sidInt = intval($sid);
                    if ($sidInt > 0) {
                        try {
                            $stmtSubject->execute([$userId, $sidInt]);
                        } catch (Exception $e) {} // ignore duplicates
                    }
                }
            } else {
                // Default fallback: enroll in all core subjects of stage
                $stmtCore = $pdo->prepare("SELECT id FROM subjects WHERE stage_id = ? AND is_core = 1");
                $stmtCore->execute([$stageId]);
                $coreSubjects = $stmtCore->fetchAll(PDO::FETCH_COLUMN);
                $stmtSubject = $pdo->prepare("INSERT INTO user_subjects (user_id, subject_id) VALUES (?, ?)");
                foreach ($coreSubjects as $sidInt) {
                    try {
                        $stmtSubject->execute([$userId, $sidInt]);
                    } catch (Exception $e) {}
                }
            }

            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $userId;
            $_SESSION['role'] = 'student';
            
            echo json_encode([
                'success' => true, 
                'user' => [
                    'id' => $userId,
                    'child_name' => htmlspecialchars($childName, ENT_QUOTES, 'UTF-8'),
                    'parent_name' => htmlspecialchars($parentName, ENT_QUOTES, 'UTF-8'),
                    'age' => $age,
                    'gender' => $gender,
                    'birth_date' => $birthDate,
                    'stage_id' => $stageId,
                    'plan' => $plan,
                    'email' => $email,
                    'role' => 'student',
                    'stars' => 0,
                    'points' => 0,
                    'level' => 1,
                    'streak_days' => 0
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to register']);
        }
    }

    public static function login($pdo) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Strict Input Validation
        $rawEmail = $input['email'] ?? '';
        $rawPassword = $input['password'] ?? '';

        $email = filter_var($rawEmail, FILTER_VALIDATE_EMAIL);
        $password = trim($rawPassword); // Password hash verification handles the raw string, but trim whitespace

        if (!$email || empty($password)) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid email and password are required']);
            return;
        }

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Success
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            
            echo json_encode([
                'success' => true, 
                'user' => [
                    'id' => $user['id'],
                    'child_name' => htmlspecialchars($user['child_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8'),
                    'parent_name' => htmlspecialchars($user['parent_name'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'age' => $user['age'] ?? 0,
                    'gender' => $user['gender'] ?? 'boy',
                    'birth_date' => $user['birth_date'] ?? '',
                    'stage_id' => $user['stage_id'] ?? 1,
                    'plan' => $user['plan'] ?? 'بذرة',
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'stars' => $user['stars'] ?? 0,
                    'points' => $user['points'] ?? 0,
                    'level' => $user['level'] ?? 1,
                    'streak_days' => $user['streak_days'] ?? 0
                ]
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
    }

    public static function update($pdo) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $updates = [];
        $params = [];

        if (isset($input['plan'])) {
            $updates[] = "plan = ?";
            $params[] = filter_var($input['plan'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }
        if (isset($input['child_name'])) {
            $updates[] = "child_name = ?";
            $params[] = filter_var($input['child_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }
        if (isset($input['parent_name'])) {
            $updates[] = "parent_name = ?";
            $params[] = filter_var($input['parent_name'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }
        if (isset($input['phone'])) {
            $updates[] = "phone = ?";
            $params[] = filter_var($input['phone'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }
        if (isset($input['gender'])) {
            $updates[] = "gender = ?";
            $params[] = filter_var($input['gender'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        }

        if (isset($input['password']) && isset($input['old_password'])) {
            // Verify old password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($input['old_password'], $user['password_hash'])) {
                http_response_code(400);
                echo json_encode(['error' => 'كلمة المرور الحالية غير صحيحة']);
                return;
            }

            $password = trim($input['password']);
            if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
                http_response_code(400);
                echo json_encode(['error' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل، وتحتوي على حرف كبير، حرف صغير، ورقم']);
                return;
            }
            $updates[] = "password_hash = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        if (empty($updates)) {
            echo json_encode(['success' => true]);
            return;
        }

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute($params)) {
            echo json_encode(['success' => true]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update user']);
        }
    }
}

