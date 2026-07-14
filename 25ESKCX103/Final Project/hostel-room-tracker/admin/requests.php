<?php
// Administrator Request Processing Desk

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_role('admin');
$user = get_logged_in_user();

$error = '';
$success = '';

// Handle Request Approval (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'approve_request') {
    $request_id = (int)$_POST['request_id'];
    $admin_remarks = trim($_POST['admin_remarks']);

    if (empty($request_id)) {
        $error = "Invalid Request ID.";
    } else {
        try {
            // Begin Transaction
            $pdo->beginTransaction();

            // 1. Fetch request details & lock row for update
            $stmt = $pdo->prepare("SELECT * FROM requests WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $request_id]);
            $request = $stmt->fetch();

            if (!$request) {
                throw new Exception("Request not found.");
            }
            if ($request['status'] !== 'Pending') {
                throw new Exception("This request has already been processed (Current Status: " . $request['status'] . ").");
            }

            // 2. Fetch room details & lock row
            $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $request['room_id']]);
            $room = $stmt->fetch();

            if (!$room) {
                throw new Exception("The requested room no longer exists.");
            }
            if ($room['status'] === 'Maintenance') {
                throw new Exception("This room is currently under maintenance and cannot be allocated.");
            }
            if ($room['current_occupancy'] >= $room['capacity']) {
                throw new Exception("This room is already fully occupied.");
            }

            // 3. Check if student already has another active allocation
            $stmt = $pdo->prepare("SELECT id FROM allocations WHERE user_id = :user_id AND status = 'Active'");
            $stmt->execute(['user_id' => $request['user_id']]);
            if ($stmt->fetch()) {
                throw new Exception("This student already has an active room allocation.");
            }

            // 4. Update request status to Approved
            $stmt = $pdo->prepare("UPDATE requests SET status = 'Approved', admin_remarks = :remarks WHERE id = :id");
            $stmt->execute([
                'remarks' => !empty($admin_remarks) ? $admin_remarks : null,
                'id' => $request_id
            ]);

            // 5. Create allocation record
            $stmt = $pdo->prepare("
                INSERT INTO allocations (user_id, room_id, request_id, duration_months, status) 
                VALUES (:user_id, :room_id, :request_id, :duration, 'Active')
            ");
            $stmt->execute([
                'user_id' => $request['user_id'],
                'room_id' => $request['room_id'],
                'request_id' => $request_id,
                'duration' => $request['duration_months']
            ]);

            // 6. Update room occupancy
            $new_occupancy = $room['current_occupancy'] + 1;
            $new_status = ($new_occupancy >= $room['capacity']) ? 'Full' : 'Available';
            
            $stmt = $pdo->prepare("UPDATE rooms SET current_occupancy = :occ, status = :status WHERE id = :id");
            $stmt->execute([
                'occ' => $new_occupancy,
                'status' => $new_status,
                'id' => $room['id']
            ]);

            // Commit Transaction
            $pdo->commit();
            $success = "Room request approved successfully. Room occupancy updated.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Approval Failed: " . $e->getMessage();
        }
    }
}

// Handle Request Rejection (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject_request') {
    $request_id = (int)$_POST['request_id'];
    $admin_remarks = trim($_POST['admin_remarks']);

    if (empty($request_id)) {
        $error = "Invalid Request ID.";
    } else {
        $stmt = $pdo->prepare("SELECT id, status FROM requests WHERE id = :id");
        $stmt->execute(['id' => $request_id]);
        $request = $stmt->fetch();

        if (!$request) {
            $error = "Request not found.";
        } elseif ($request['status'] !== 'Pending') {
            $error = "This request has already been processed.";
        } else {
            // Update request status to Rejected
            $stmt = $pdo->prepare("UPDATE requests SET status = 'Rejected', admin_remarks = :remarks WHERE id = :id");
            try {
                $stmt->execute([
                    'remarks' => !empty($admin_remarks) ? $admin_remarks : null,
                    'id' => $request_id
                ]);
                $success = "Room request has been rejected.";
            } catch (PDOException $e) {
                $error = "Failed to reject request: " . $e->getMessage();
            }
        }
    }
}

// Fetch single request details if 'id' parameter is set (Deep inspection)
$inspectedRequest = null;
if (isset($_GET['id'])) {
    $inspect_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as student_name, u.email as student_email, u.student_id, u.gender as student_gender, u.phone as student_phone,
               rm.room_number, rm.room_type, rm.capacity, rm.current_occupancy, rm.price, h.name as hostel_name 
        FROM requests r 
        JOIN users u ON r.user_id = u.id 
        JOIN rooms rm ON r.room_id = rm.id 
        JOIN hostels h ON rm.hostel_id = h.id 
        WHERE r.id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $inspect_id]);
    $inspectedRequest = $stmt->fetch();
}

// Filter configuration
$filterStatus = $_GET['filter_status'] ?? 'all';
$allowedStatuses = ['Pending', 'Approved', 'Rejected', 'Cancelled'];

$query = "
    SELECT r.*, u.name as student_name, rm.room_number, h.name as hostel_name 
    FROM requests r 
    JOIN users u ON r.user_id = u.id 
    JOIN rooms rm ON r.room_id = rm.id 
    JOIN hostels h ON rm.hostel_id = h.id
";

if (in_array($filterStatus, $allowedStatuses)) {
    $query .= " WHERE r.status = :status";
}
$query .= " ORDER BY r.request_date DESC";

$stmt = $pdo->prepare($query);
if (in_array($filterStatus, $allowedStatuses)) {
    $stmt->execute(['status' => $filterStatus]);
} else {
    $stmt->execute();
}
$requestsList = $stmt->fetchAll();

$pageTitle = "Process Room Requests";
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

<!-- Inspection View Column if active -->
<?php if ($inspectedRequest): ?>
    <div class="card border-0 rounded-4 shadow-sm mb-4 animated-fade-in border border-indigo-200">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold m-0 text-indigo-900"><i class="bi bi-search text-indigo-600"></i> Evaluating Request #<?php echo (int)$inspectedRequest['id']; ?></h5>
            <a href="requests.php" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="bi bi-arrow-left"></i> Back to list</a>
        </div>
        <div class="card-body p-4 pt-0">
            <div class="row g-4">
                <!-- Student Card -->
                <div class="col-md-6 border-end-md">
                    <h6 class="small text-muted font-weight-bold uppercase mb-3"><i class="bi bi-person-fill"></i> Student Profile Summary</h6>
                    <table class="table table-sm table-borderless small">
                        <tr>
                            <td class="text-muted w-35">Full Name:</td>
                            <td class="fw-bold text-dark"><?php echo sanitize($inspectedRequest['student_name']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Student ID:</td>
                            <td class="fw-bold"><?php echo sanitize($inspectedRequest['student_id'] ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Email:</td>
                            <td><?php echo sanitize($inspectedRequest['student_email']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Phone:</td>
                            <td><?php echo sanitize($inspectedRequest['student_phone'] ?? 'Not Provided'); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Gender:</td>
                            <td class="capitalize"><?php echo sanitize($inspectedRequest['student_gender']); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Room Details -->
                <div class="col-md-6">
                    <h6 class="small text-muted font-weight-bold uppercase mb-3"><i class="bi bi-door-open-fill"></i> Requested Room Details</h6>
                    <table class="table table-sm table-borderless small">
                        <tr>
                            <td class="text-muted w-35">Hostel Name:</td>
                            <td class="fw-bold text-indigo-900"><?php echo sanitize($inspectedRequest['hostel_name']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Room Number:</td>
                            <td class="fw-bold"><span class="badge bg-indigo-100 text-indigo-800 px-2 py-1 rounded">Room <?php echo sanitize($inspectedRequest['room_number']); ?></span></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Comfort Type:</td>
                            <td><?php echo sanitize($inspectedRequest['room_type']); ?></td>
                        </tr>
                        <tr>
                            <td class="text-muted">Vacancy Status:</td>
                            <td><?php echo sanitize($inspectedRequest['current_occupancy']); ?> / <?php echo sanitize($inspectedRequest['capacity']); ?> Beds Occupied</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Monthly Fee:</td>
                            <td class="fw-bold text-indigo-600"><?php echo format_currency($inspectedRequest['price']); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Request Parameters -->
                <div class="col-12 bg-light p-3 rounded border">
                    <div class="row text-center g-3 small">
                        <div class="col-6 col-md-3">
                            <span class="text-muted d-block">Requested Check-in</span>
                            <strong class="text-dark fs-6"><?php echo date('d M Y', strtotime($inspectedRequest['check_in_date'])); ?></strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted d-block">Stay Duration</span>
                            <strong class="text-dark fs-6"><?php echo sanitize($inspectedRequest['duration_months']); ?> Months</strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted d-block">Date of Application</span>
                            <strong class="text-dark"><?php echo date('d M Y, h:i A', strtotime($inspectedRequest['request_date'])); ?></strong>
                        </div>
                        <div class="col-6 col-md-3">
                            <span class="text-muted d-block">Current Status</span>
                            <strong class="d-block mt-1"><?php echo get_status_badge($inspectedRequest['status']); ?></strong>
                        </div>
                    </div>
                    <?php if (!empty($inspectedRequest['student_remarks'])): ?>
                        <div class="mt-3 pt-3 border-top small">
                            <span class="text-muted fw-bold d-block mb-1">Student's remarks:</span>
                            <div class="bg-white p-2.5 rounded border text-muted-600 italic">"<?php echo sanitize($inspectedRequest['student_remarks']); ?>"</div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Evaluation Panel Controls -->
                <?php if ($inspectedRequest['status'] === 'Pending'): ?>
                    <div class="col-12 d-flex justify-content-end gap-3 mt-2">
                        <button class="btn btn-outline-danger px-4 rounded-pill" 
                                data-bs-toggle="modal" 
                                data-bs-target="#rejectRequestModal"
                                data-request-id="<?php echo (int)$inspectedRequest['id']; ?>"
                                data-student-name="<?php echo sanitize($inspectedRequest['student_name']); ?>"
                                data-room-number="<?php echo sanitize($inspectedRequest['room_number']); ?>"
                                data-hostel-name="<?php echo sanitize($inspectedRequest['hostel_name']); ?>">
                            <i class="bi bi-x-circle-fill"></i> Reject Application
                        </button>
                        <button class="btn btn-primary-gradient px-4 rounded-pill" 
                                data-bs-toggle="modal" 
                                data-bs-target="#approveRequestModal"
                                data-request-id="<?php echo (int)$inspectedRequest['id']; ?>"
                                data-student-name="<?php echo sanitize($inspectedRequest['student_name']); ?>"
                                data-room-number="<?php echo sanitize($inspectedRequest['room_number']); ?>"
                                data-hostel-name="<?php echo sanitize($inspectedRequest['hostel_name']); ?>">
                            <i class="bi bi-check-circle-fill"></i> Approve & Allocate Bed
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Global Requests Queue -->
<div class="card border-0 rounded-4 shadow-sm animated-fade-in">
    <div class="card-header bg-white py-3 border-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <h5 class="fw-bold m-0 text-indigo-900"><i class="bi bi-file-earmark-spreadsheet-fill text-indigo-600"></i> Requests Queue</h5>
        
        <!-- Filter Tabs -->
        <div class="btn-group rounded-pill overflow-hidden border p-1 bg-light">
            <a href="requests.php?filter_status=all" class="btn btn-sm px-3 rounded-pill <?php echo $filterStatus === 'all' ? 'btn-primary shadow-sm' : 'btn-light'; ?>">All</a>
            <a href="requests.php?filter_status=Pending" class="btn btn-sm px-3 rounded-pill <?php echo $filterStatus === 'Pending' ? 'btn-primary shadow-sm' : 'btn-light'; ?>">Pending</a>
            <a href="requests.php?filter_status=Approved" class="btn btn-sm px-3 rounded-pill <?php echo $filterStatus === 'Approved' ? 'btn-primary shadow-sm' : 'btn-light'; ?>">Approved</a>
            <a href="requests.php?filter_status=Rejected" class="btn btn-sm px-3 rounded-pill <?php echo $filterStatus === 'Rejected' ? 'btn-primary shadow-sm' : 'btn-light'; ?>">Rejected</a>
            <a href="requests.php?filter_status=Cancelled" class="btn btn-sm px-3 rounded-pill <?php echo $filterStatus === 'Cancelled' ? 'btn-primary shadow-sm' : 'btn-light'; ?>">Cancelled</a>
        </div>
    </div>
    
    <div class="card-body p-4 pt-0">
        <?php if (!empty($requestsList)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Student</th>
                            <th>Requested Room</th>
                            <th>Application Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requestsList as $req): ?>
                            <tr>
                                <td>#<?php echo (int)$req['id']; ?></td>
                                <td class="fw-semibold text-indigo-950"><?php echo sanitize($req['student_name']); ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo sanitize($req['room_number']); ?></div>
                                    <small class="text-muted"><?php echo sanitize($req['hostel_name']); ?></small>
                                </td>
                                <td class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($req['request_date'])); ?></td>
                                <td><?php echo get_status_badge($req['status']); ?></td>
                                <td class="text-end">
                                    <a href="requests.php?id=<?php echo (int)$req['id']; ?>" class="btn btn-outline-primary btn-sm px-3 rounded-pill">
                                        <i class="bi bi-eye"></i> View & Evaluate
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-journal-x display-6 mb-2"></i>
                <h5>No Request Applications Found</h5>
                <p class="mb-0 small text-muted">No request matches the selected filter status.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- APPROVAL CONFIRMATION MODAL -->
<div class="modal fade" id="approveRequestModal" tabindex="-1" aria-labelledby="approveRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-success text-white" style="border-radius: 1rem 1rem 0 0; background: var(--success-gradient) !important;">
                <h5 class="modal-title fw-bold" id="approveRequestModalLabel"><i class="bi bi-check-circle-fill"></i> Approve Request Application</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="requests.php" method="POST">
                <input type="hidden" name="action" value="approve_request">
                <input type="hidden" id="approve_request_id" name="request_id">
                
                <div class="modal-body p-4">
                    <p id="approve_details" class="fw-semibold text-dark fs-6">Loading details...</p>
                    <p class="small text-muted mb-3">Approving this request will automatically establish an active allocation for the student and adjust room occupancy levels.</p>

                    <div class="mb-3">
                        <label for="approve_remarks" class="form-label fw-semibold">Add Warden Remarks <span class="small text-muted">(Optional)</span></label>
                        <textarea class="form-control" id="approve_remarks" name="admin_remarks" rows="3" placeholder="Enter instructions, check-in rules, key collection timing..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer p-3 border-top bg-light" style="border-radius: 0 0 1rem 1rem;">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4" style="background: var(--success-gradient) !important; border:none;">Approve & Allocate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- REJECTION CONFIRMATION MODAL -->
<div class="modal fade" id="rejectRequestModal" tabindex="-1" aria-labelledby="rejectRequestModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-danger text-white" style="border-radius: 1rem 1rem 0 0; background: var(--danger-gradient) !important;">
                <h5 class="modal-title fw-bold" id="rejectRequestModalLabel"><i class="bi bi-x-circle-fill"></i> Reject Request Application</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="requests.php" method="POST">
                <input type="hidden" name="action" value="reject_request">
                <input type="hidden" id="reject_request_id" name="request_id">
                
                <div class="modal-body p-4">
                    <p id="reject_details" class="fw-semibold text-dark fs-6">Loading details...</p>
                    <p class="small text-muted mb-3">Rejecting this request will mark the application "Rejected". The student will be notified and can request another room.</p>

                    <div class="mb-3">
                        <label for="reject_remarks" class="form-label fw-semibold">Rejection reason / Remarks <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_remarks" name="admin_remarks" rows="3" placeholder="Provide reason for rejection (e.g. gender mismatch, room details change...)" required></textarea>
                    </div>
                </div>
                
                <div class="modal-footer p-3 border-top bg-light" style="border-radius: 0 0 1rem 1rem;">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4" style="background: var(--danger-gradient) !important; border:none;">Reject Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
