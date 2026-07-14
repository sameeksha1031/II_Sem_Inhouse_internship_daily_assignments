<?php
// Shared HTML header and dashboard layout structure

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Determine base folder path dynamically
$scriptName = $_SERVER['SCRIPT_NAME'];
$isSubdir = (strpos($scriptName, '/student/') !== false || strpos($scriptName, '/admin/') !== false);
$basePath = $isSubdir ? '../' : '';
$currentFilename = basename($scriptName);

$user = get_logged_in_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . " | Hostel Tracker" : "Hostel Room Request Tracker"; ?></title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom Style CSS -->
    <link href="<?php echo $basePath; ?>css/style.css" rel="stylesheet">
</head>
<body>

<?php if (is_logged_in()): ?>
<div class="dashboard-container">
    <!-- Navigation Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h4 class="m-0 text-white font-weight-bold d-flex align-items-center gap-2">
                <i class="bi bi-building-fill text-indigo-400"></i>
                <span class="fs-5">Hostel Portal</span>
            </h4>
            <div class="small text-muted mt-1">Logged in as: <strong><?php echo sanitize($user['name']); ?></strong></div>
        </div>
        
        <nav class="nav flex-column flex-grow-1 py-3">
            <?php if ($user['role'] === 'student'): ?>
                <!-- Student Menu Options -->
                <a class="nav-link <?php echo $currentFilename === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>student/dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
                <a class="nav-link <?php echo $currentFilename === 'rooms.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>student/rooms.php">
                    <i class="bi bi-door-open-fill"></i> Browse Rooms
                </a>
                <a class="nav-link <?php echo $currentFilename === 'requests.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>student/requests.php">
                    <i class="bi bi-send-fill"></i> My Requests
                </a>
                <a class="nav-link <?php echo $currentFilename === 'profile.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>student/profile.php">
                    <i class="bi bi-person-bounding-box"></i> My Profile
                </a>
            <?php elseif ($user['role'] === 'admin'): ?>
                <!-- Administrator Menu Options -->
                <a class="nav-link <?php echo $currentFilename === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>admin/dashboard.php">
                    <i class="bi bi-grid-1x2-fill"></i> Dashboard
                </a>
                <a class="nav-link <?php echo $currentFilename === 'hostels.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>admin/hostels.php">
                    <i class="bi bi-buildings"></i> Manage Hostels
                </a>
                <a class="nav-link <?php echo $currentFilename === 'rooms.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>admin/rooms.php">
                    <i class="bi bi-door-open"></i> Manage Rooms
                </a>
                <a class="nav-link <?php echo $currentFilename === 'requests.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>admin/requests.php">
                    <i class="bi bi-file-earmark-spreadsheet-fill"></i> Requests Desk
                </a>
                <a class="nav-link <?php echo $currentFilename === 'students.php' ? 'active' : ''; ?>" href="<?php echo $basePath; ?>admin/students.php">
                    <i class="bi bi-people-fill"></i> Student Directory
                </a>
            <?php endif; ?>
        </nav>
        
        <div class="p-3 border-top border-secondary border-opacity-10 mt-auto">
            <a href="<?php echo $basePath; ?>logout.php" class="btn btn-outline-danger w-100 d-flex align-items-center justify-content-center gap-2">
                <i class="bi bi-box-arrow-right"></i> Log Out
            </a>
        </div>
    </aside>
    
    <!-- Main Dashboard Panel -->
    <main class="main-content">
        <!-- Content Header -->
        <div class="content-header">
            <div>
                <h1 class="h3 m-0 fw-bold"><?php echo isset($pageTitle) ? $pageTitle : "Dashboard"; ?></h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb m-0 mt-1 small">
                        <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><?php echo isset($pageTitle) ? $pageTitle : "Dashboard"; ?></li>
                    </ol>
                </nav>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 rounded-pill border">
                    <i class="bi bi-calendar3"></i> <?php echo date('d M Y'); ?>
                </span>
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill border capitalize">
                    <i class="bi bi-shield-lock-fill"></i> <?php echo ucfirst($_SESSION['user_role']); ?>
                </span>
            </div>
        </div>
<?php endif; ?>
