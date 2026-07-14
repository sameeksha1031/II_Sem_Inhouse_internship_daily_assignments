<?php
// Student Dashboard Homepage

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_role('student');
$user = get_logged_in_user();

$pageTitle = "Student Dashboard";
require_once dirname(__DIR__) . '/includes/header.php';

// 1. Fetch active allocation if it exists
$stmt = $pdo->prepare("
    SELECT a.*, r.room_number, r.room_type, r.price, h.name as hostel_name 
    FROM allocations a 
    JOIN rooms r ON a.room_id = r.id 
    JOIN hostels h ON r.hostel_id = h.id 
    WHERE a.user_id = :user_id AND a.status = 'Active' 
    LIMIT 1
");
$stmt->execute(['user_id' => $user['id']]);
$allocation = $stmt->fetch();

// 2. Fetch requests statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='Pending' THEN 1 ELSE 0 END) as pending FROM requests WHERE user_id = :user_id");
$stmt->execute(['user_id' => $user['id']]);
$reqStats = $stmt->fetch();

// 3. Fetch latest requests (up to 3)
$stmt = $pdo->prepare("
    SELECT r.*, rm.room_number, h.name as hostel_name 
    FROM requests r 
    JOIN rooms rm ON r.room_id = rm.id 
    JOIN hostels h ON rm.hostel_id = h.id 
    WHERE r.user_id = :user_id 
    ORDER BY r.request_date DESC 
    LIMIT 3
");
$stmt->execute(['user_id' => $user['id']]);
$recentRequests = $stmt->fetchAll();

// 4. Get Student Information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $user['id']]);
$studentDetails = $stmt->fetch();
?>

<div class="row g-4 animated-fade-in">
    <!-- Welcome message card -->
    <div class="col-12">
        <div class="card border-0 rounded-4 shadow-sm p-4 text-white" style="background: var(--primary-gradient);">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-2">Welcome Back, <?php echo sanitize($user['name']); ?>!</h2>
                    <p class="mb-0 opacity-80">Track your room requests, browse available campus rooms, and manage your current residency details here.</p>
                </div>
                <div class="col-md-4 text-end d-none d-md-block">
                    <i class="bi bi-journal-check display-3 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Metrics row -->
    <div class="col-md-4">
        <div class="card metric-card glass-card h-100">
            <div class="card-body">
                <div class="metric-icon" style="background: var(--primary-gradient);">
                    <i class="bi bi-door-open-fill"></i>
                </div>
                <h6 class="text-muted mb-1">Your Assigned Room</h6>
                <h3 class="fw-bold m-0 text-indigo-900">
                    <?php echo $allocation ? 'Room ' . sanitize($allocation['room_number']) : 'Not Assigned'; ?>
                </h3>
                <small class="text-muted mt-2 d-block">
                    <?php echo $allocation ? sanitize($allocation['hostel_name']) : 'No active residency found.'; ?>
                </small>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card metric-card glass-card h-100">
            <div class="card-body">
                <div class="metric-icon" style="background: var(--warning-gradient);">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <h6 class="text-muted mb-1">Pending Request(s)</h6>
                <h3 class="fw-bold m-0 text-warning">
                    <?php echo (int)($reqStats['pending'] ?? 0); ?>
                </h3>
                <small class="text-muted mt-2 d-block">Submitted requests waiting review.</small>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card metric-card glass-card h-100">
            <div class="card-body">
                <div class="metric-icon" style="background: var(--secondary-gradient);">
                    <i class="bi bi-send-check"></i>
                </div>
                <h6 class="text-muted mb-1">Total Requests</h6>
                <h3 class="fw-bold m-0 text-purple-900">
                    <?php echo (int)($reqStats['total'] ?? 0); ?>
                </h3>
                <small class="text-muted mt-2 d-block">All requests submitted in total.</small>
            </div>
        </div>
    </div>

    <!-- Main columns -->
    <div class="col-lg-8">
        <!-- Active Allocation Details -->
        <div class="card border-0 rounded-4 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold m-0 text-indigo-900"><i class="bi bi-house-door-fill text-indigo-600"></i> Current Room Residency Details</h5>
            </div>
            <div class="card-body p-4 pt-0">
                <?php if ($allocation): ?>
                    <div class="row align-items-center">
                        <div class="col-md-7">
                            <h4 class="fw-bold text-indigo-700 m-0"><?php echo sanitize($allocation['hostel_name']); ?></h4>
                            <div class="text-muted mt-1 mb-3">Room number: <strong class="text-dark"><?php echo sanitize($allocation['room_number']); ?></strong> | Type: <strong><?php echo sanitize($allocation['room_type']); ?></strong></div>
                            
                            <ul class="list-unstyled mb-0 d-flex flex-column gap-2 small">
                                <li><i class="bi bi-calendar-event text-muted me-2"></i> Allocation Date: <strong><?php echo date('d M Y', strtotime($allocation['allocation_date'])); ?></strong></li>
                                <li><i class="bi bi-clock-history text-muted me-2"></i> Stay Duration: <strong><?php echo sanitize($allocation['duration_months']); ?> Months</strong></li>
                                <li><i class="bi bi-cash-stack text-muted me-2"></i> Monthly Price: <strong class="text-indigo-600"><?php echo format_currency($allocation['price']); ?></strong></li>
                            </ul>
                        </div>
                        <div class="col-md-5 text-end d-none d-md-block">
                            <div class="p-3 bg-light rounded-4 d-inline-block text-center border">
                                <span class="d-block text-uppercase small text-muted font-weight-bold">Hostel Bed status</span>
                                <span class="badge bg-success px-4 py-2 mt-2 fs-6 rounded-pill"><i class="bi bi-check-circle-fill"></i> Active</span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="d-inline-flex align-items-center justify-content-center bg-light p-3 rounded-circle mb-3 border">
                            <i class="bi bi-door-closed display-6 text-muted"></i>
                        </div>
                        <h5>No Room Assigned Yet</h5>
                        <p class="text-muted px-4">You currently do not have any active room assigned to you. Go to the room catalog and submit a request to the admin.</p>
                        <a href="rooms.php" class="btn btn-primary-gradient px-4"><i class="bi bi-door-open-fill"></i> Browse Rooms</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Requests -->
        <div class="card border-0 rounded-4 shadow-sm">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0 text-indigo-900"><i class="bi bi-send-fill text-indigo-600"></i> Recent Requests History</h5>
                <a href="requests.php" class="small text-decoration-none">View All <i class="bi bi-chevron-right"></i></a>
            </div>
            <div class="card-body p-4 pt-0">
                <?php if (!empty($recentRequests)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Hostel / Room</th>
                                    <th>Request Date</th>
                                    <th>Check-in Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentRequests as $req): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo sanitize($req['hostel_name']); ?></div>
                                            <small class="text-muted">Room: <?php echo sanitize($req['room_number']); ?> (<?php echo sanitize($req['duration_months']); ?> mos)</small>
                                        </td>
                                        <td class="small text-muted"><?php echo date('d M Y', strtotime($req['request_date'])); ?></td>
                                        <td class="small text-muted"><?php echo date('d M Y', strtotime($req['check_in_date'])); ?></td>
                                        <td><?php echo get_status_badge($req['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-send-x fs-3 d-block mb-2"></i>
                        <p class="mb-0">No room requests found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick profile view sidebar widget -->
    <div class="col-lg-4">
        <div class="card border-0 rounded-4 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold m-0 text-indigo-900"><i class="bi bi-person-lines-fill text-indigo-600"></i> My Profile Card</h5>
            </div>
            <div class="card-body p-4 pt-0">
                <div class="text-center py-3 border-bottom mb-3">
                    <div class="d-inline-flex align-items-center justify-content-center bg-indigo-100 text-indigo-800 fs-1 fw-bold rounded-circle mb-3 shadow-sm border border-white" style="width: 80px; height: 80px; background-color: #e0e7ff;">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <h5 class="fw-bold m-0"><?php echo sanitize($studentDetails['name']); ?></h5>
                    <small class="text-muted">ID: <?php echo sanitize($studentDetails['student_id'] ?? 'N/A'); ?></small>
                </div>
                
                <ul class="list-unstyled mb-4 d-flex flex-column gap-3 small">
                    <li class="d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-envelope me-2"></i> Email:</span>
                        <strong class="text-end text-break ms-2"><?php echo sanitize($studentDetails['email']); ?></strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-telephone me-2"></i> Phone:</span>
                        <strong><?php echo sanitize($studentDetails['phone'] ?? 'Not Set'); ?></strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-gender-ambiguous me-2"></i> Gender:</span>
                        <strong class="capitalize"><?php echo sanitize($studentDetails['gender']); ?></strong>
                    </li>
                    <li class="d-flex justify-content-between">
                        <span class="text-muted"><i class="bi bi-clock me-2"></i> Joined:</span>
                        <strong><?php echo date('d M Y', strtotime($studentDetails['created_at'])); ?></strong>
                    </li>
                </ul>
                
                <a href="profile.php" class="btn btn-outline-primary btn-sm w-100 py-2"><i class="bi bi-pencil-square"></i> Edit Contact Info</a>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
