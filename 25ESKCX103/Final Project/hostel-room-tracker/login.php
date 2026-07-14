<?php
// Unified Login and Registration Portal

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect to installer if config is missing
if (!file_exists(__DIR__ . '/config.php')) {
    header("Location: install.php");
    exit;
}

// Redirect if already logged in
if (is_logged_in()) {
    $user = get_logged_in_user();
    header("Location: " . ($user['role'] === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit;
}

$error = '';
$success = '';
$activeTab = 'student-login'; // Default tab

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // 1. Process Student Login
    if ($action === 'student_login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $activeTab = 'student-login';
        
        if (empty($email) || empty($password)) {
            $error = "Please fill in all credentials.";
        } else {
            $res = login_user($email, $password, $pdo);
            if ($res['success']) {
                header("Location: student/dashboard.php");
                exit;
            } else {
                $error = $res['message'];
            }
        }
    }
    
    // 2. Process Admin Login
    elseif ($action === 'admin_login') {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $activeTab = 'admin-login';
        
        if (empty($email) || empty($password)) {
            $error = "Please fill in all credentials.";
        } else {
            $res = login_user($email, $password, $pdo);
            if ($res['success']) {
                if ($res['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                    exit;
                } else {
                    $error = "Access denied: Account is not an administrator.";
                    // Log user out if role mismatch occurred
                    $_SESSION = [];
                    session_destroy();
                }
            } else {
                $error = $res['message'];
            }
        }
    }
    
    // 3. Process Student Registration
    elseif ($action === 'student_register') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $student_id = trim($_POST['student_id']);
        $gender = $_POST['gender'] ?? '';
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $activeTab = 'student-register';
        
        if (empty($name) || empty($email) || empty($gender) || empty($password)) {
            $error = "Name, Email, Gender and Password are required fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email formatting.";
        } elseif (strlen($password) < 6) {
            $error = "Password must be at least 6 characters long.";
        } else {
            $res = register_student($name, $email, $password, $gender, $student_id, $phone, $pdo);
            if ($res['success']) {
                $success = $res['message'];
                $activeTab = 'student-login'; // Switch back to login after success
            } else {
                $error = $res['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Access | Hostel Tracker</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="splash-container">

<div class="container" style="max-width: 550px;">
    <!-- Logo/Brand header -->
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-white shadow-sm p-3 rounded-circle mb-3" style="width: 72px; height: 72px;">
            <i class="bi bi-building-fill text-indigo-600 fs-1"></i>
        </div>
        <h2 class="fw-extrabold text-indigo-900 m-0">Hostel Request Tracker</h2>
        <p class="text-muted">Manage, request, and allocate campus hostel rooms</p>
    </div>

    <!-- Feedback alerts -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo sanitize($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?php echo sanitize($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Card layout with glass effect -->
    <div class="card glass-card shadow-lg animated-fade-in">
        <div class="card-header p-0" style="border-radius: 1.25rem 1.25rem 0 0; overflow: hidden; background: none; border-bottom: 1px solid rgba(0,0,0,0.05);">
            <ul class="nav nav-tabs nav-fill border-0 m-0 bg-light bg-opacity-50">
                <li class="nav-item">
                    <a class="nav-link py-3 border-0 rounded-0 text-dark fw-semibold <?php echo $activeTab === 'student-login' ? 'active bg-white' : ''; ?>" 
                       href="#" data-auth-tab="student-login">
                       <i class="bi bi-person-fill"></i> Student Login
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-3 border-0 rounded-0 text-dark fw-semibold <?php echo $activeTab === 'student-register' ? 'active bg-white' : ''; ?>" 
                       href="#" data-auth-tab="student-register">
                       <i class="bi bi-person-plus-fill"></i> Student Signup
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link py-3 border-0 rounded-0 text-dark fw-semibold <?php echo $activeTab === 'admin-login' ? 'active bg-white' : ''; ?>" 
                       href="#" data-auth-tab="admin-login">
                       <i class="bi bi-shield-lock-fill"></i> Warden Access
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body p-4">
            
            <!-- PANEL 1: STUDENT LOGIN -->
            <div id="student-login" class="auth-panel <?php echo $activeTab === 'student-login' ? 'active' : ''; ?>">
                <h4 class="fw-bold mb-3 text-indigo-800">Student Portal Log In</h4>
                <form action="login.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="student_login">
                    <div class="mb-3">
                        <label for="student_email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="student_email" name="email" placeholder="student@university.edu" required>
                            <div class="invalid-feedback">Please enter a valid student email.</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="student_pass" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="student_pass" name="password" required>
                            <div class="invalid-feedback">Password is required.</div>
                        </div>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary-gradient py-2.5">
                            <i class="bi bi-box-arrow-in-right"></i> Access Dashboard
                        </button>
                    </div>
                </form>
            </div>

            <!-- PANEL 2: STUDENT REGISTRATION -->
            <div id="student-register" class="auth-panel <?php echo $activeTab === 'student-register' ? 'active' : ''; ?>">
                <h4 class="fw-bold mb-3 text-indigo-800">Create Student Account</h4>
                <form action="login.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="student_register">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="reg_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="reg_name" name="name" placeholder="John Doe" required>
                            <div class="invalid-feedback">Please enter your name.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="reg_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="reg_email" name="email" placeholder="john@university.edu" required>
                            <div class="invalid-feedback">Please provide a valid email.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="reg_student_id" class="form-label">Student ID</label>
                            <input type="text" class="form-control" id="reg_student_id" name="student_id" placeholder="STD202611" required>
                            <div class="invalid-feedback">Please provide your student ID.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="reg_gender" class="form-label">Gender</label>
                            <select class="form-select" id="reg_gender" name="gender" required>
                                <option value="" disabled selected>Select gender...</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                            <div class="invalid-feedback">Please select your gender.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="reg_phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="reg_phone" name="phone" placeholder="9876543210">
                        </div>
                        <div class="col-md-6">
                            <label for="reg_pass" class="form-label">Password</label>
                            <input type="password" class="form-control" id="reg_pass" name="password" required minlength="6" placeholder="Min 6 characters">
                            <div class="invalid-feedback">Password must be at least 6 characters.</div>
                        </div>
                    </div>
                    
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-secondary-gradient py-2.5">
                            <i class="bi bi-person-check-fill"></i> Register Account
                        </button>
                    </div>
                </form>
            </div>

            <!-- PANEL 3: ADMIN LOGIN -->
            <div id="admin-login" class="auth-panel <?php echo $activeTab === 'admin-login' ? 'active' : ''; ?>">
                <h4 class="fw-bold mb-3 text-purple-800 d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock"></i> Administration Portal
                </h4>
                <form action="login.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="admin_login">
                    <div class="mb-3">
                        <label for="admin_email" class="form-label">Warden Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                            <input type="email" class="form-control" id="admin_email" name="email" placeholder="warden@hostel.com" required>
                            <div class="invalid-feedback">Please enter a valid administrator email.</div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="admin_pass_input" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                            <input type="password" class="form-control" id="admin_pass_input" name="password" required>
                            <div class="invalid-feedback">Password is required.</div>
                        </div>
                    </div>
                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-secondary-gradient py-2.5" style="background: var(--secondary-gradient) !important;">
                            <i class="bi bi-shield-fill-check"></i> Administrator Log In
                        </button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
