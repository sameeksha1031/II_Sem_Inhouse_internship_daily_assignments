<?php
// Administrator Dashboard Homepage

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_role('admin');
$user = get_logged_in_user();

// Fetch metrics
// 1. Total Students
$totalStudents = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();

// 2. Total Hostels
$totalHostels = $pdo->query("SELECT COUNT(*) FROM hostels")->fetchColumn();

// 3. Total Rooms
$totalRooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();

// 4. Bed Occupancy Metrics
$bedStats = $pdo->query("SELECT SUM(capacity) as total_beds, SUM(current_occupancy) as occupied_beds FROM rooms")->fetch();
$totalBeds = (int)($bedStats['total_beds'] ?? 0);
$occupiedBeds = (int)($bedStats['occupied_beds'] ?? 0);
$availableBeds = $totalBeds - $occupiedBeds;
$occupancyRate = ($totalBeds > 0) ? ($occupiedBeds / $totalBeds) * 100 : 0;

// 5. Total Pending Requests
$pendingRequestsCount = $pdo->query("SELECT COUNT(*) FROM requests WHERE status = 'Pending'")->fetchColumn();

// Fetch top 5 pending requests
$recentPending = $pdo->query("
    SELECT r.*, u.name as student_name, u.student_id, rm.room_number, h.name as hostel_name 
    FROM requests r 
    JOIN users u ON r.user_id = u.id 
    JOIN rooms rm ON r.room_id = rm.id 
    JOIN hostels h ON rm.hostel_id = h.id 
    WHERE r.status = 'Pending' 
    ORDER BY r.request_date ASC 
    LIMIT 5
")->fetchAll();

// Fetch hostel occupancy breakdown
$hostelBreakdown = $pdo->query("
    SELECT h.name, SUM(r.capacity) as capacity, SUM(r.current_occupancy) as occupancy 
    FROM hostels h 
    LEFT JOIN rooms r ON h.id = r.hostel_id 
    GROUP BY h.id 
    ORDER BY h.name
")->fetchAll();

$pageTitle = "Admin Dashboard";
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="row g-4 animated-fade-in">
    <!-- Welcome card -->
    <div class="col-12">
        <div class="card border-0 rounded-4 shadow-sm p-4 text-white" style="background: var(--secondary-gradient);">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="fw-bold mb-2">Welcome Back, Warden <?php echo sanitize(explode(' ', $user['name'])[0]); ?>!</h2>
                    <p class="mb-0 opacity-80">Oversee hostel assets, manage student registrations, and evaluate room allocation applications.</p>
                </div>
                <div class="col-md-4 text-end d-none d-md-block">
                    <i class="bi bi-shield-check display-3 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Metrics Stats Counters -->
    <div class="col-md-4 col-xl-2">
        <div class="card metric-card glass-card h-100">
            <div class="card-body">
                <div class="metric-icon" style="background: var(--primary-gradient);">
                    <i class="bi bi-people-fill"></i>
                </div>
                <h6 class="text-muted mb-1 small uppercase fw-bold">Students</h6>
                <h3 class="fw-bold m-0 text-indigo-900"><?php echo (int)$totalStudents; ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-xl-2">
        <div class="card metric-card glass-card h-100">
            <div class="card-body">
                <div class="metric-icon" style="background: var(--secondary-gradient);">
                    <i class="bi bi-buildings"></i>
                </div>
                <h6 class="text-muted mb-1 small uppercase fw-bold">Hostels</h6>
                <h3 class="fw-bold m-0 text-purple-900"><?php echo (int)$totalHostels; ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-xl-2">
        <div class="card metric-card glass-card h-100">
            <div class="card-body">
                <div class="metric-icon" style="background: var(--info-gradient);">
                    <i class="bi bi-door-open-fill"></i>
                </div>
                <h6 class="text-muted mb-1 small uppercase fw-bold">Rooms</h6>
                <h3 class="fw-bold m-0 text-cyan-900"><?php echo (int)$totalRooms; ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-xl-2">
        <div class="card metric-card glass-card h-100">
            <div class="card-body">
                <div class="metric-icon" style="background: var(--success-gradient);">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h6 class="text-muted mb-1 small uppercase fw-bold">Beds Free</h6>
                <h3 class="fw-bold m-0 text-success"><?php echo $availableBeds; ?> / <?php echo $totalBeds; ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-xl-4">
        <div class="card metric-card glass-card h-100">
            <div class="card-body">
                <div class="metric-icon bg-warning" style="background: var(--warning-gradient);">
                    <i class="bi bi-bell-fill"></i>
                </div>
                <h6 class="text-muted mb-1 small uppercase fw-bold">Pending Requests</h6>
                <h3 class="fw-bold m-0 text-warning d-flex align-items-center gap-2">
                    <?php echo (int)$pendingRequestsCount; ?>
                    <?php if ($pendingRequestsCount > 0): ?>
                        <span class="spinner-grow spinner-grow-sm text-warning" role="status"></span>
                    <?php endif; ?>
                </h3>
            </div>
        </div>
    </div>

    <!-- Main Content Left & Right Columns -->
    <div class="col-lg-8">
        <!-- Pending Applications Desk -->
        <div class="card border-0 rounded-4 shadow-sm mb-4">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold m-0 text-indigo-900"><i class="bi bi-file-earmark-text text-indigo-600"></i> Urgent Requests Queue</h5>
                <a href="requests.php" class="small text-decoration-none">Requests Desk <i class="bi bi-chevron-right"></i></a>
            </div>
            <div class="card-body p-4 pt-0">
                <?php if (!empty($recentPending)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Room Chosen</th>
                                    <th>Check-in Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPending as $req): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-indigo-900"><?php echo sanitize($req['student_name']); ?></div>
                                            <small class="text-muted">ID: <?php echo sanitize($req['student_id'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?php echo sanitize($req['room_number']); ?></div>
                                            <small class="text-muted"><?php echo sanitize($req['hostel_name']); ?></small>
                                        </td>
                                        <td class="small text-muted"><?php echo date('d M Y', strtotime($req['check_in_date'])); ?></td>
                                        <td class="text-end">
                                            <a href="requests.php?id=<?php echo (int)$req['id']; ?>" class="btn btn-primary btn-sm rounded-pill px-3">
                                                <i class="bi bi-eye-fill"></i> View & Process
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-check2-all display-6 text-success mb-2"></i>
                        <h5>All Caught Up!</h5>
                        <p class="mb-0 small text-muted">No pending room requests require evaluation right now.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Right Column: Occupancy Breakdown Widget -->
    <div class="col-lg-4">
        <div class="card border-0 rounded-4 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold m-0 text-indigo-900"><i class="bi bi-pie-chart text-indigo-600"></i> Hostel Occupancy Rate</h5>
            </div>
            <div class="card-body p-4 pt-0">
                <!-- Overall Bar -->
                <div class="mb-4 pb-3 border-bottom">
                    <div class="d-flex justify-content-between small mb-1 fw-bold">
                        <span>All Buildings Combined</span>
                        <span><?php echo (int)$occupiedBeds; ?> / <?php echo (int)$totalBeds; ?> Beds</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $occupancyRate; ?>%" aria-valuenow="<?php echo $occupiedBeds; ?>" aria-valuemin="0" aria-valuemax="<?php echo $totalBeds; ?>"></div>
                    </div>
                    <div class="text-end small mt-1 text-muted"><?php echo number_format($occupancyRate, 1); ?>% occupied</div>
                </div>

                <!-- Breakdown by Hostel -->
                <h6 class="small text-muted font-weight-bold uppercase mb-3">Occupancy by Building</h6>
                <?php if (!empty($hostelBreakdown)): ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($hostelBreakdown as $h): 
                            $hCapacity = (int)($h['capacity'] ?? 0);
                            $hOccupancy = (int)($h['occupancy'] ?? 0);
                            $percent = ($hCapacity > 0) ? ($hOccupancy / $hCapacity) * 100 : 0;
                            ?>
                            <div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-semibold text-indigo-950"><?php echo sanitize($h['name']); ?></span>
                                    <span class="text-muted"><?php echo $hOccupancy; ?> / <?php echo $hCapacity; ?> Beds</span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $percent; ?>%" aria-valuenow="<?php echo $hOccupancy; ?>" aria-valuemin="0" aria-valuemax="<?php echo $hCapacity; ?>"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small text-center py-3 mb-0">No hostels configured.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
