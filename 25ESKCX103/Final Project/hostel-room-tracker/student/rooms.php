<?php
// Student Room Catalog Browser & Request Submission Desk

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_role('student');
$user = get_logged_in_user();

$error = '';
$success = '';

// Handle Room Request Submission (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_request') {
    $room_id = (int)$_POST['room_id'];
    $check_in_date = trim($_POST['check_in_date']);
    $duration_months = (int)$_POST['duration_months'];
    $student_remarks = trim($_POST['student_remarks']);

    if (empty($room_id) || empty($check_in_date) || empty($duration_months)) {
        $error = "Check-in date and duration are required fields.";
    } elseif (strtotime($check_in_date) < strtotime(date('Y-m-d'))) {
        $error = "Check-in date cannot be in the past.";
    } else {
        // Business Rule: Check if the user already has a Pending request
        $stmt = $pdo->prepare("SELECT id FROM requests WHERE user_id = :user_id AND status = 'Pending'");
        $stmt->execute(['user_id' => $user['id']]);
        if ($stmt->fetch()) {
            $error = "You already have an active pending room request. Please cancel it or wait for admin review before submitting a new one.";
        } else {
            // Check if room is available and has vacancy
            $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $room_id]);
            $room = $stmt->fetch();
            
            if (!$room || $room['status'] !== 'Available' || $room['current_occupancy'] >= $room['capacity']) {
                $error = "Sorry, this room is no longer available or is already fully occupied.";
            } else {
                // Securely insert request
                $stmt = $pdo->prepare("
                    INSERT INTO requests (user_id, room_id, status, duration_months, check_in_date, student_remarks) 
                    VALUES (:user_id, :room_id, 'Pending', :duration, :check_in, :remarks)
                ");
                try {
                    $stmt->execute([
                        'user_id' => $user['id'],
                        'room_id' => $room_id,
                        'duration' => $duration_months,
                        'check_in' => $check_in_date,
                        'remarks' => !empty($student_remarks) ? $student_remarks : null
                    ]);
                    $success = "Your request for Room " . sanitize($room['room_number']) . " has been submitted successfully and is pending approval!";
                } catch (PDOException $e) {
                    $error = "Failed to submit request: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch all hostels for the filtering dropdown
$hostels = $pdo->query("SELECT * FROM hostels ORDER BY name")->fetchAll();

// Fetch all active rooms (excluding Maintenance)
$rooms = $pdo->query("
    SELECT r.*, h.name as hostel_name 
    FROM rooms r 
    JOIN hostels h ON r.hostel_id = h.id 
    WHERE r.status != 'Maintenance' 
    ORDER BY h.name, r.room_number
")->fetchAll();

$pageTitle = "Browse Available Rooms";
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

<!-- Filter & Search Widget Container -->
<div class="card border-0 rounded-4 shadow-sm mb-4 animated-fade-in">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-3 text-indigo-900"><i class="bi bi-funnel-fill text-indigo-600"></i> Catalog Search Filters</h5>
        <div class="row g-3">
            <div class="col-md-3">
                <label for="roomSearch" class="form-label small text-muted">Search Room Number</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control border-start-0 ps-0" id="roomSearch" placeholder="e.g. B101">
                </div>
            </div>
            
            <div class="col-md-3">
                <label for="filterHostel" class="form-label small text-muted">Filter Hostel Building</label>
                <select class="form-select" id="filterHostel">
                    <option value="all">All Hostels</option>
                    <?php foreach ($hostels as $h): ?>
                        <option value="<?php echo (int)$h['id']; ?>"><?php echo sanitize($h['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label for="filterType" class="form-label small text-muted">Filter Comfort Type</label>
                <select class="form-select" id="filterType">
                    <option value="all">All Types</option>
                    <option value="AC">AC Rooms</option>
                    <option value="Non-AC">Non-AC Rooms</option>
                </select>
            </div>

            <div class="col-md-3">
                <label for="filterStatus" class="form-label small text-muted">Filter Vacancy status</label>
                <select class="form-select" id="filterStatus">
                    <option value="all">All Available</option>
                    <option value="Available">Has Vacancy</option>
                    <option value="Full">Fully Occupied</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Room Grid Catalog -->
<div class="row g-4 animated-fade-in" id="roomGrid">
    <?php if (!empty($rooms)): ?>
        <?php foreach ($rooms as $room): 
            $bedsLeft = $room['capacity'] - $room['current_occupancy'];
            $occupancyPercent = ($room['capacity'] > 0) ? ($room['current_occupancy'] / $room['capacity']) * 100 : 0;
            $isFull = ($bedsLeft <= 0 || $room['status'] === 'Full');
            
            // Adjust room status on card dynamically based on bed counts
            $actualStatus = $isFull ? 'Full' : 'Available';
            ?>
            <div class="col-md-6 col-lg-4 room-card-item" 
                 data-room-number="<?php echo sanitize($room['room_number']); ?>"
                 data-hostel-id="<?php echo (int)$room['hostel_id']; ?>"
                 data-room-type="<?php echo sanitize($room['room_type']); ?>"
                 data-room-status="<?php echo $actualStatus; ?>">
                
                <div class="card h-100 room-card">
                    <div class="room-card-header d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-indigo-100 text-indigo-800 fw-bold border border-indigo-200 px-2.5 py-1.5 rounded-pill fs-7">
                                Room <?php echo sanitize($room['room_number']); ?>
                            </span>
                        </div>
                        <span class="room-price-tag">
                            <?php echo format_currency($room['price']); ?><span class="small text-muted fs-7">/mo</span>
                        </span>
                    </div>
                    <div class="card-body p-4">
                        <h6 class="fw-bold mb-3 text-indigo-900"><?php echo sanitize($room['hostel_name']); ?></h6>
                        
                        <div class="d-flex gap-2 mb-4">
                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2.5 py-1.5 rounded-pill small border">
                                <i class="bi bi-wind"></i> <?php echo sanitize($room['room_type']); ?>
                            </span>
                            <span class="badge bg-secondary bg-opacity-10 text-secondary px-2.5 py-1.5 rounded-pill small border">
                                <i class="bi bi-people"></i> <?php echo sanitize($room['capacity']); ?> Sharing
                            </span>
                        </div>
                        
                        <!-- Occupancy meter -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Beds Occupancy</span>
                                <span class="fw-semibold text-dark"><?php echo sanitize($room['current_occupancy']); ?> / <?php echo sanitize($room['capacity']); ?> Filled</span>
                            </div>
                            <div class="progress">
                                <div class="progress-bar <?php echo $isFull ? 'bg-danger' : 'bg-primary'; ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo $occupancyPercent; ?>%" 
                                     aria-valuenow="<?php echo $room['current_occupancy']; ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="<?php echo $room['capacity']; ?>"></div>
                            </div>
                            <div class="mt-2 text-end small">
                                <?php if ($isFull): ?>
                                    <span class="text-danger fw-semibold"><i class="bi bi-exclamation-circle-fill"></i> No vacancy left</span>
                                <?php else: ?>
                                    <span class="text-success fw-semibold"><i class="bi bi-patch-check"></i> <?php echo $bedsLeft; ?> beds remaining</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Card Action -->
                        <?php if ($isFull): ?>
                            <button class="btn btn-outline-secondary w-100 py-2.5" disabled>
                                <i class="bi bi-person-fill-slash"></i> Room Fully Booked
                            </button>
                        <?php else: ?>
                            <button class="btn btn-primary-gradient w-100 py-2.5" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#requestRoomModal"
                                    data-room-id="<?php echo (int)$room['id']; ?>"
                                    data-room-number="<?php echo sanitize($room['room_number']); ?>"
                                    data-room-type="<?php echo sanitize($room['room_type']); ?>"
                                    data-room-capacity="<?php echo sanitize($room['capacity']); ?>"
                                    data-room-price="<?php echo format_currency($room['price']); ?>"
                                    data-hostel-name="<?php echo sanitize($room['hostel_name']); ?>">
                                <i class="bi bi-box-arrow-in-up-right"></i> Request Allocation
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12" id="noRoomsAlert">
            <div class="alert alert-warning py-4 text-center border-dashed">
                <i class="bi bi-door-closed display-5 d-block text-warning mb-2"></i>
                <h5>No Rooms Registered in the Catalog</h5>
                <p class="mb-0 text-muted">There are no rooms active in the system configuration right now.</p>
            </div>
        </div>
    <?php endif; ?>

    <!-- Client-side Search Helper Alert -->
    <div class="col-12" id="noRoomsAlert" style="display: none;">
        <div class="alert alert-info py-4 text-center">
            <i class="bi bi-emoji-frown display-5 d-block text-indigo-500 mb-2"></i>
            <h5>No Rooms Match Your Filter Settings</h5>
            <p class="mb-0 text-muted">Try adjusting your comfort type, search spelling, or building selection.</p>
        </div>
    </div>
</div>

<!-- ROOM REQUEST SUBMISSION MODAL -->
<div class="modal fade" id="requestRoomModal" tabindex="-1" aria-labelledby="requestRoomModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header bg-primary text-white" style="border-radius: 1rem 1rem 0 0; background: var(--primary-gradient) !important;">
                <h5 class="modal-title fw-bold" id="requestRoomModalLabel"><i class="bi bi-send-plus-fill"></i> Submit Room Request</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="rooms.php" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="action" value="submit_request">
                <input type="hidden" id="modal_room_id" name="room_id">
                
                <div class="modal-body p-4">
                    <div class="bg-light p-3 rounded-3 border mb-3">
                        <div class="small text-muted font-weight-bold uppercase">Selected Choice:</div>
                        <div id="modal_room_details" class="fw-bold text-indigo-900 fs-6">Loading details...</div>
                        <div class="mt-2 text-indigo-600 fw-bold">Price: <span id="modal_room_price">...</span> / Month</div>
                    </div>

                    <div class="mb-3">
                        <label for="check_in_date" class="form-label fw-semibold">Check-in date</label>
                        <input type="date" class="form-control" id="check_in_date" name="check_in_date" min="<?php echo date('Y-m-d'); ?>" required>
                        <div class="invalid-feedback">Please choose a valid upcoming check-in date.</div>
                    </div>

                    <div class="mb-3">
                        <label for="duration_months" class="form-label fw-semibold">Residency Duration</label>
                        <select class="form-select" id="duration_months" name="duration_months" required>
                            <option value="3">3 Months (Short Term)</option>
                            <option value="6">6 Months (Semester stay)</option>
                            <option value="12" selected>12 Months (Full academic session)</option>
                            <option value="24">24 Months (Two-year session)</option>
                        </select>
                        <div class="invalid-feedback">Please specify duration.</div>
                    </div>

                    <div class="mb-3">
                        <label for="student_remarks" class="form-label fw-semibold">Remarks & Preferences <span class="small text-muted">(Optional)</span></label>
                        <textarea class="form-control" id="student_remarks" name="student_remarks" rows="3" placeholder="Share food preferences, roommates wishes, or medical requests..."></textarea>
                    </div>
                </div>
                
                <div class="modal-footer p-3 border-top bg-light" style="border-radius: 0 0 1rem 1rem;">
                    <button type="button" class="btn btn-outline-secondary px-3" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary-gradient px-4">Submit Request Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
