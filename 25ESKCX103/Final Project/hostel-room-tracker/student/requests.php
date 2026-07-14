<?php
// Student Room Request Tracking & Cancellation Desk

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_role('student');
$user = get_logged_in_user();

$error = '';
$success = '';

// Handle Cancellation Action (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_request') {
    $request_id = (int)$_POST['request_id'];
    
    if (empty($request_id)) {
        $error = "Request ID is invalid.";
    } else {
        // Query to check if the request is still pending
        $stmt = $pdo->prepare("SELECT id, status FROM requests WHERE id = :id AND user_id = :user_id LIMIT 1");
        $stmt->execute(['id' => $request_id, 'user_id' => $user['id']]);
        $request = $stmt->fetch();
        
        if (!$request) {
            $error = "Request not found.";
        } elseif ($request['status'] !== 'Pending') {
            $error = "Only pending room requests can be cancelled.";
        } else {
            // Update request status to 'Cancelled'
            $stmt = $pdo->prepare("UPDATE requests SET status = 'Cancelled' WHERE id = :id AND user_id = :user_id");
            try {
                $stmt->execute(['id' => $request_id, 'user_id' => $user['id']]);
                $success = "Your room request has been successfully cancelled.";
            } catch (PDOException $e) {
                $error = "Failed to cancel request: " . $e->getMessage();
            }
        }
    }
}

// Fetch all requests submitted by the student
$stmt = $pdo->prepare("
    SELECT r.*, rm.room_number, rm.room_type, rm.price, h.name as hostel_name 
    FROM requests r 
    JOIN rooms rm ON r.room_id = rm.id 
    JOIN hostels h ON rm.hostel_id = h.id 
    WHERE r.user_id = :user_id 
    ORDER BY r.request_date DESC
");
$stmt->execute(['user_id' => $user['id']]);
$requests = $stmt->fetchAll();

$pageTitle = "My Room Requests";
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

<div class="card border-0 rounded-4 shadow-sm animated-fade-in">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold m-0 text-indigo-900"><i class="bi bi-send-fill text-indigo-600"></i> Request Applications List</h5>
    </div>
    <div class="card-body p-4 pt-0">
        <?php if (!empty($requests)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3">ID</th>
                            <th class="py-3">Hostel / Room</th>
                            <th class="py-3">Duration</th>
                            <th class="py-3">Planned Check-in</th>
                            <th class="py-3">Request Date</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td>#<?php echo (int)$req['id']; ?></td>
                                <td>
                                    <div class="fw-semibold text-indigo-900"><?php echo sanitize($req['hostel_name']); ?></div>
                                    <small class="text-muted">Room: <strong><?php echo sanitize($req['room_number']); ?></strong> (<?php echo sanitize($req['room_type']); ?>) | Price: <?php echo format_currency($req['price']); ?></small>
                                </td>
                                <td><?php echo sanitize($req['duration_months']); ?> Months</td>
                                <td class="small"><?php echo date('d M Y', strtotime($req['check_in_date'])); ?></td>
                                <td class="small text-muted"><?php echo date('d M Y, h:i A', strtotime($req['request_date'])); ?></td>
                                <td>
                                    <?php echo get_status_badge($req['status']); ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($req['status'] === 'Pending'): ?>
                                        <form action="requests.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this request?');">
                                            <input type="hidden" name="action" value="cancel_request">
                                            <input type="hidden" name="request_id" value="<?php echo (int)$req['id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger btn-sm px-3 rounded-pill">
                                                <i class="bi bi-x-octagon"></i> Cancel Request
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">No action available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Remarks Row if notes are present -->
                            <?php if (!empty($req['student_remarks']) || !empty($req['admin_remarks'])): ?>
                                <tr class="table-light">
                                    <td colspan="7" class="p-3">
                                        <div class="row g-2 small">
                                            <?php if (!empty($req['student_remarks'])): ?>
                                                <div class="col-md-6 border-end-md">
                                                    <span class="text-muted font-weight-bold d-block mb-1"><i class="bi bi-chat-text"></i> Your Remarks:</span>
                                                    <p class="mb-0 text-dark-50 italic">"<?php echo sanitize($req['student_remarks']); ?>"</p>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($req['admin_remarks'])): ?>
                                                <div class="col-md-6">
                                                    <span class="text-success font-weight-bold d-block mb-1"><i class="bi bi-chat-left-dots-fill"></i> Administrator Notes:</span>
                                                    <p class="mb-0 text-success bg-success bg-opacity-5 p-2 rounded border border-success border-opacity-10 italic">"<?php echo sanitize($req['admin_remarks']); ?>"</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-journal-x display-6 text-muted mb-3 d-block"></i>
                <h5>No Request History Found</h5>
                <p class="mb-3 px-3">You have not submitted any room request applications yet.</p>
                <a href="rooms.php" class="btn btn-primary-gradient px-4"><i class="bi bi-door-open-fill"></i> Browse Rooms Catalog</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
