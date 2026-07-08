<?php
/**
 * Registration API Endpoint
 */

// Define as API request for connection error handling
$is_api_request = true;
require_once 'config.php';

header('Content-Type: application/json');

// Only allow POST request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only POST requests are permitted.'
    ]);
    exit;
}

// Extract and sanitize input data
$fullname = isset($_POST['fullname']) ? trim($_POST['fullname']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirmPassword']) ? $_POST['confirmPassword'] : '';

// Server-side validation
if (empty($fullname) || empty($email) || empty($username) || empty($password) || empty($confirmPassword)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'All fields are required.'
    ]);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please provide a valid email address.'
    ]);
    exit;
}

// Validate username length and format
if (strlen($username) < 3 || strlen($username) > 30 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Username must be 3-30 characters long and contain only letters, numbers, and underscores.'
    ]);
    exit;
}

// Validate password length
if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 8 characters long.'
    ]);
    exit;
}

// Validate password match
if ($password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Passwords do not match.'
    ]);
    exit;
}

try {
    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Email is already registered.'
        ]);
        exit;
    }

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
    $stmt->execute(['username' => $username]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Username is already taken.'
        ]);
        exit;
    }

    // Hash password using BCRYPT
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Insert user into DB
    $insertStmt = $pdo->prepare("INSERT INTO users (fullname, email, username, password) VALUES (:fullname, :email, :username, :password)");
    $result = $insertStmt->execute([
        'fullname' => $fullname,
        'email' => $email,
        'username' => $username,
        'password' => $hashedPassword
    ]);

    if ($result) {
        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Registration completed successfully! Welcome aboard.'
        ]);
    } else {
        throw new Exception("Execution failed during query insert.");
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'A database error occurred: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An unexpected error occurred. Please try again.'
    ]);
}
