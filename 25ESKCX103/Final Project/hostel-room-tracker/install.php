<?php
// Automatic Web Installer & Database Configuration Wizard

$configFile = __DIR__ . '/config.php';

// Redirect to index if config.php already exists to prevent re-install
if (file_exists($configFile)) {
    header("Location: index.php");
    exit;
}

$error = '';
$success = '';
$isWritable = is_writable(__DIR__);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isWritable) {
    $db_host = trim($_POST['db_host']);
    $db_user = trim($_POST['db_user']);
    $db_pass = $_POST['db_pass'];
    $db_name = trim($_POST['db_name']);
    
    $admin_name = trim($_POST['admin_name']);
    $admin_email = trim($_POST['admin_email']);
    $admin_pass = $_POST['admin_pass'];
    
    if (empty($db_host) || empty($db_user) || empty($db_name) || empty($admin_name) || empty($admin_email) || empty($admin_pass)) {
        $error = "All fields are required.";
    } elseif (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid administrator email address.";
    } else {
        try {
            // 1. Establish connection to MySQL server
            $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            
            // 2. Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$db_name`");
            
            // 3. Load and execute db_schema.sql
            $schemaFile = __DIR__ . '/db_schema.sql';
            if (!file_exists($schemaFile)) {
                throw new Exception("db_schema.sql file is missing from the directory.");
            }
            
            $sql = file_get_contents($schemaFile);
            
            // Execute the schema SQL
            $pdo->exec($sql);
            
            // 4. Create the admin user
            $hashedPass = password_hash($admin_pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, gender) VALUES (:name, :email, :password, 'admin', 'other')");
            $stmt->execute([
                'name' => $admin_name,
                'email' => $admin_email,
                'password' => $hashedPass
            ]);
            
            // 5. Generate and write config.php file
            $configContent = "<?php\n"
                           . "// Database Credentials Configuration\n"
                           . "define('DB_HOST', " . var_export($db_host, true) . ");\n"
                           . "define('DB_USER', " . var_export($db_user, true) . ");\n"
                           . "define('DB_PASS', " . var_export($db_pass, true) . ");\n"
                           . "define('DB_NAME', " . var_export($db_name, true) . ");\n"
                           . "?>";
            
            if (file_put_contents($configFile, $configContent) === false) {
                throw new Exception("Failed to write config.php. Please check write permissions.");
            }
            
            $success = "Installation completed successfully! You can now log in using the administrator credentials.";
            
        } catch (PDOException $e) {
            $error = "Database Connection Failed: " . $e->getMessage();
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Installer | Hostel Tracker</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="splash-container">

<div class="container" style="max-width: 650px;">
    <div class="text-center mb-4">
        <h2 class="fw-extrabold text-indigo-800 d-inline-flex align-items-center gap-2">
            <i class="bi bi-gear-wide-connected text-indigo-600"></i> Setup Wizard
        </h2>
        <p class="text-muted">Hostel Room Request Tracker Configuration</p>
    </div>

    <div class="card glass-card shadow animated-fade-in">
        <div class="card-header bg-primary text-white py-3" style="border-radius: 1.25rem 1.25rem 0 0; background: var(--primary-gradient) !important;">
            <h4 class="m-0 fs-5 d-flex align-items-center gap-2">
                <i class="bi bi-info-circle"></i> Installation Wizard
            </h4>
        </div>
        <div class="card-body p-4">
            
            <?php if (!$isWritable): ?>
                <div class="alert alert-danger mb-4">
                    <h5 class="alert-heading d-flex align-items-center gap-2"><i class="bi bi-exclamation-triangle-fill"></i> Directory Not Writable</h5>
                    <p class="mb-0">The application folder does not have write permissions. Please grant write access to <code>/Users/arvind/.gemini/antigravity/scratch/hostel-room-tracker</code> so the installer can write the <code>config.php</code> file.</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-octagon-fill"></i> <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success py-3 text-center mb-4">
                    <i class="bi bi-check-circle-fill fs-2 d-block mb-2 text-success"></i>
                    <h5 class="alert-heading">Setup Complete!</h5>
                    <p class="mb-3"><?php echo $success; ?></p>
                    <a href="login.php" class="btn btn-primary-gradient px-4"><i class="bi bi-box-arrow-in-right"></i> Go to Portal Login</a>
                </div>
            <?php else: ?>
                
                <form action="install.php" method="POST" class="needs-validation" novalidate>
                    <!-- DB Section -->
                    <div class="mb-4 pb-3 border-bottom">
                        <h5 class="text-indigo-700 mb-3"><i class="bi bi-database-check"></i> Database Connection Settings</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="db_host" class="form-label">Database Host</label>
                                <input type="text" class="form-control" id="db_host" name="db_host" value="127.0.0.1" required>
                                <div class="invalid-feedback">Database host is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="db_name" class="form-label">Database Name</label>
                                <input type="text" class="form-control" id="db_name" name="db_name" value="hostel_db" required>
                                <div class="invalid-feedback">Database name is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="db_user" class="form-label">MySQL Username</label>
                                <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                                <div class="invalid-feedback">Username is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="db_pass" class="form-label">MySQL Password</label>
                                <input type="password" class="form-control" id="db_pass" name="db_pass" placeholder="Enter password (leave blank if none)">
                            </div>
                        </div>
                    </div>

                    <!-- Admin User Section -->
                    <div class="mb-4">
                        <h5 class="text-indigo-700 mb-3"><i class="bi bi-person-badge-fill"></i> Create Administrator Account</h5>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="admin_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="admin_name" name="admin_name" value="System Warden" required>
                                <div class="invalid-feedback">Administrator name is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="admin_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="admin_email" name="admin_email" placeholder="admin@hostel.com" required>
                                <div class="invalid-feedback">Valid email address is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="admin_pass" class="form-label">Account Password</label>
                                <input type="password" class="form-control" id="admin_pass" name="admin_pass" required minlength="6">
                                <div class="invalid-feedback">Password must be at least 6 characters.</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid mt-4">
                        <button type="submit" class="btn btn-primary-gradient py-2.5 fs-6" <?php echo !$isWritable ? 'disabled' : ''; ?>>
                            <i class="bi bi-check2-circle"></i> Initialize & Install Application
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Simple bootstrap form validator trigger
(function () {
  'use strict'
  var forms = document.querySelectorAll('.needs-validation')
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener('submit', function (event) {
      if (!form.checkValidity()) {
        event.preventDefault()
        event.stopPropagation()
      }
      form.classList.add('was-validated')
    }, false)
  })
})()
</script>
</body>
</html>
