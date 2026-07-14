<?php
// Student Profile Management Console

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_role('student');
$user = get_logged_in_user();

$error = '';
$success = '';

// Process Profile Information Update (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'update_profile') {
        $name = trim($_POST['name']);
        $student_id = trim($_POST['student_id']);
        $phone = trim($_POST['phone']);
        $gender = $_POST['gender'];
        
        if (empty($name) || empty($gender)) {
            $error = "Name and Gender are required fields.";
        } else {
            // Update user record
            $stmt = $pdo->prepare("UPDATE users SET name = :name, student_id = :student_id, phone = :phone, gender = :gender WHERE id = :id");
            try {
                $stmt->execute([
                    'name' => $name,
                    'student_id' => !empty($student_id) ? $student_id : null,
                    'phone' => !empty($phone) ? $phone : null,
                    'gender' => $gender,
                    'id' => $user['id']
                ]);
                
                // Update active session name
                $_SESSION['user_name'] = $name;
                $_SESSION['user_gender'] = $gender;
                
                $success = "Your profile information has been updated successfully.";
            } catch (PDOException $e) {
                $error = "Failed to update profile: " . $e->getMessage();
            }
        }
    }
    
    // Process Password Reset (POST)
    elseif ($action === 'update_password') {
        $current_pass = $_POST['current_pass'];
        $new_pass = $_POST['new_pass'];
        $confirm_pass = $_POST['confirm_pass'];
        
        if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
            $error = "Please fill in all password fields.";
        } elseif ($new_pass !== $confirm_pass) {
            $error = "New password and password confirmation do not match.";
        } elseif (strlen($new_pass) < 6) {
            $error = "New password must be at least 6 characters long.";
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $user['id']]);
            $db_pass = $stmt->fetchColumn();
            
            if (!password_verify($current_pass, $db_pass)) {
                $error = "The current password you entered is incorrect.";
            } else {
                // Save new password
                $new_hashed = password_hash($new_pass, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                try {
                    $stmt->execute(['password' => $new_hashed, 'id' => $user['id']]);
                    $success = "Your password has been changed successfully.";
                } catch (PDOException $e) {
                    $error = "Failed to update password: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch current details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user['id']]);
$student = $stmt->fetch();

$pageTitle = "My Profile Settings";
require_once dirname(__DIR__) . '/includes/header.php';
?>

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

<div class="row g-4 animated-fade-in">
    <!-- Left Column: Update Personal Info -->
    <div class="col-lg-7">
        <div class="card border-0 rounded-4 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold m-0 text-indigo-900"><i class="bi bi-person-gear text-indigo-600"></i> Edit Profile Information</h5>
            </div>
            <div class="card-body p-4 pt-0">
                <form action="profile.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo sanitize($student['name']); ?>" required>
                            <div class="invalid-feedback">Please enter your name.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">Email Address <span class="small text-muted">(Read Only)</span></label>
                            <input type="email" class="form-control bg-light" id="email" value="<?php echo sanitize($student['email']); ?>" readonly disabled>
                        </div>

                        <div class="col-md-6">
                            <label for="student_id" class="form-label">Student ID</label>
                            <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo sanitize($student['student_id'] ?? ''); ?>" placeholder="STD202611">
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo sanitize($student['phone'] ?? ''); ?>" placeholder="9876543210">
                        </div>

                        <div class="col-md-6">
                            <label for="gender" class="form-label">Gender</label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="male" <?php echo $student['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo $student['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo $student['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <div class="invalid-feedback">Please select your gender.</div>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary-gradient py-2">
                            <i class="bi bi-save"></i> Save Profile Details
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Change Password -->
    <div class="col-lg-5">
        <div class="card border-0 rounded-4 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold m-0 text-indigo-900"><i class="bi bi-shield-lock-fill text-indigo-600"></i> Change Account Password</h5>
            </div>
            <div class="card-body p-4 pt-0">
                <form action="profile.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="action" value="update_password">

                    <div class="mb-3">
                        <label for="current_pass" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_pass" name="current_pass" required>
                        <div class="invalid-feedback">Please enter your current password.</div>
                    </div>

                    <div class="mb-3">
                        <label for="new_pass" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_pass" name="new_pass" required minlength="6" placeholder="At least 6 characters">
                        <div class="invalid-feedback">Please specify a new password.</div>
                    </div>

                    <div class="mb-3">
                        <label for="confirm_pass" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_pass" name="confirm_pass" required>
                        <div class="invalid-feedback">Please confirm your new password.</div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-outline-indigo py-2 text-white" style="background: var(--secondary-gradient); border:none;">
                            <i class="bi bi-key-fill"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
