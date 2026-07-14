<?php
// Session configuration and user authorization controls

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

/**
 * Checks if a user is logged in.
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Gets currently logged in user info.
 */
function get_logged_in_user() {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'role' => $_SESSION['user_role']
    ];
}

/**
 * Enforces role protection. Redirects unauthorized users to the login page.
 * Handles paths correctly depending on directory structure.
 */
function require_role($role) {
    if (!is_logged_in()) {
        // Find relative path to login
        $script = $_SERVER['SCRIPT_NAME'];
        $redirect = 'login.php';
        if (strpos($script, '/student/') !== false || strpos($script, '/admin/') !== false) {
            $redirect = '../login.php';
        }
        header("Location: $redirect");
        exit;
    }
    
    if ($_SESSION['user_role'] !== $role) {
        // Unauthorized role access
        $redirect = 'login.php';
        if (strpos($_SERVER['SCRIPT_NAME'], '/student/') !== false || strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false) {
            $redirect = '../login.php';
        }
        header("Location: $redirect?error=unauthorized");
        exit;
    }
}

/**
 * Attempts to log in a user.
 */
function login_user($email, $password, $pdo) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_gender'] = $user['gender'];
        return [
            'success' => true,
            'role' => $user['role']
        ];
    }
    return [
        'success' => false,
        'message' => 'Invalid email address or password.'
    ];
}

/**
 * Registers a new student user.
 */
function register_student($name, $email, $password, $gender, $student_id, $phone, $pdo) {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        return [
            'success' => false,
            'message' => 'Email address is already registered.'
        ];
    }

    // Check if student_id already exists if provided
    if (!empty($student_id)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE student_id = :student_id");
        $stmt->execute(['student_id' => $student_id]);
        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'Student ID is already registered.'
            ];
        }
    }

    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, gender, student_id, phone) VALUES (:name, :email, :password, 'student', :gender, :student_id, :phone)");
    try {
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => $hashed_password,
            'gender' => $gender,
            'student_id' => !empty($student_id) ? $student_id : null,
            'phone' => !empty($phone) ? $phone : null
        ]);
        return [
            'success' => true,
            'message' => 'Registration successful! You can now log in.'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Registration failed: ' . $e->getMessage()
        ];
    }
}
?>
