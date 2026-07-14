<?php
// Administrator Room Config Panel

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_role('admin');
$user = get_logged_in_user();

$error = '';
$success = '';
$editRoom = null;

// 1. Process Add Room (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_room') {
    $hostel_id = (int)$_POST['hostel_id'];
    $room_number = trim($_POST['room_number']);
    $room_type = $_POST['room_type'] ?? '';
    $capacity = (int)$_POST['capacity'];
    $price = (float)$_POST['price'];
    $status = $_POST['status'] ?? 'Available';
    
    if (empty($hostel_id) || empty($room_number) || empty($room_type) || empty($capacity) || empty($price)) {
        $error = "All fields except status are required.";
    } elseif ($capacity <= 0 || $price < 0) {
        $error = "Capacity must be positive and price cannot be negative.";
    } else {
        // Check duplication
        $stmt = $pdo->prepare("SELECT id FROM rooms WHERE hostel_id = :hostel_id AND room_number = :room_number");
        $stmt->execute(['hostel_id' => $hostel_id, 'room_number' => $room_number]);
        if ($stmt->fetch()) {
            $error = "Room number '$room_number' already exists in the selected hostel building.";
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO rooms (hostel_id, room_number, room_type, capacity, price, status) 
                VALUES (:hostel_id, :room_number, :room_type, :capacity, :price, :status)
            ");
            try {
                $stmt->execute([
                    'hostel_id' => $hostel_id,
                    'room_number' => $room_number,
                    'room_type' => $room_type,
                    'capacity' => $capacity,
                    'price' => $price,
                    'status' => $status
                ]);
                $success = "Room '$room_number' has been created successfully.";
            } catch (PDOException $e) {
                $error = "Failed to create room: " . $e->getMessage();
            }
        }
    }
}

// 2. Process Edit Room (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_room') {
    $room_id = (int)$_POST['room_id'];
    $hostel_id = (int)$_POST['hostel_id'];
    $room_number = trim($_POST['room_number']);
    $room_type = $_POST['room_type'] ?? '';
    $capacity = (int)$_POST['capacity'];
    $price = (float)$_POST['price'];
    $status = $_POST['status'] ?? 'Available';
    
    if (empty($hostel_id) || empty($room_number) || empty($room_type) || empty($capacity) || empty($price)) {
        $error = "All fields except status are required.";
    } elseif ($capacity <= 0 || $price < 0) {
        $error = "Capacity must be positive and price cannot be negative.";
    } else {
        // Check duplication (excluding current ID)
        $stmt = $pdo->prepare("SELECT id FROM rooms WHERE hostel_id = :hostel_id AND room_number = :room_number AND id != :id");
        $stmt->execute(['hostel_id' => $hostel_id, 'room_number' => $room_number, 'id' => $room_id]);
        if ($stmt->fetch()) {
            $error = "Room number '$room_number' already exists in the selected hostel building.";
        } else {
            // Update the room
            $stmt = $pdo->prepare("
                UPDATE rooms 
                SET hostel_id = :hostel_id, room_number = :room_number, room_type = :room_type, 
                    capacity = :capacity, price = :price, status = :status 
                WHERE id = :id
            ");
            try {
                $stmt->execute([
                    'hostel_id' => $hostel_id,
                    'room_number' => $room_number,
                    'room_type' => $room_type,
                    'capacity' => $capacity,
                    'price' => $price,
                    'status' => $status,
                    'id' => $room_id
                ]);
                $success = "Room config updated successfully.";
            } catch (PDOException $e) {
                $error = "Failed to update room: " . $e->getMessage();
            }
        }
    }
}

// 3. Process Delete Room (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_room') {
    $room_id = (int)$_POST['room_id'];
    
    $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = :id");
    try {
        $stmt->execute(['id' => $room_id]);
        $success = "Room configuration and its active occupancies have been deleted.";
    } catch (PDOException $e) {
        $error = "Failed to delete room: " . $e->getMessage();
    }
}

// Check if we are editing an item
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $edit_id]);
    $editRoom = $stmt->fetch();
}

// Fetch all hostels for dropdown
$hostelsList = $pdo->query("SELECT * FROM hostels ORDER BY name")->fetchAll();

// Fetch all rooms with hostel names
$roomsList = $pdo->query("
    SELECT r.*, h.name as hostel_name 
    FROM rooms r 
    JOIN hostels h ON r.hostel_id = h.id 
    ORDER BY h.name, r.room_number
")->fetchAll();

$pageTitle = "Manage Rooms";
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
    <!-- Left Column: Add or Edit Form Card -->
    <div class="col-lg-4">
        <div class="card border-0 rounded-4 shadow-sm">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold m-0 text-indigo-900">
                    <?php echo $editRoom ? '<i class="bi bi-pencil-square text-indigo-600"></i> Edit Room Details' : '<i class="bi bi-plus-circle-fill text-indigo-600"></i> Create New Room'; ?>
                </h5>
            </div>
            <div class="card-body p-4 pt-0">
                <form action="rooms.php" method="POST" class="needs-validation" novalidate>
                    <?php if ($editRoom): ?>
                        <input type="hidden" name="action" value="edit_room">
                        <input type="hidden" name="room_id" value="<?php echo (int)$editRoom['id']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="add_room">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="hostel_id" class="form-label">Hostel Building</label>
                        <select class="form-select" id="hostel_id" name="hostel_id" required>
                            <option value="" disabled selected>Select hostel...</option>
                            <?php foreach ($hostelsList as $h): ?>
                                <option value="<?php echo (int)$h['id']; ?>" <?php echo ($editRoom && $editRoom['hostel_id'] === $h['id']) ? 'selected' : ''; ?>>
                                    <?php echo sanitize($h['name']); ?> (<?php echo ucfirst(sanitize($h['type'])); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Hostel building is required.</div>
                    </div>

                    <div class="mb-3">
                        <label for="room_number" class="form-label">Room Number</label>
                        <input type="text" class="form-control" id="room_number" name="room_number" 
                               placeholder="e.g. B104" 
                               value="<?php echo $editRoom ? sanitize($editRoom['room_number']) : ''; ?>" required>
                        <div class="invalid-feedback">Room number is required.</div>
                    </div>

                    <div class="mb-3">
                        <label for="room_type" class="form-label">Comfort Type</label>
                        <select class="form-select" id="room_type" name="room_type" required>
                            <option value="Non-AC" <?php echo ($editRoom && $editRoom['room_type'] === 'Non-AC') ? 'selected' : ''; ?>>Non-AC Room</option>
                            <option value="AC" <?php echo ($editRoom && $editRoom['room_type'] === 'AC') ? 'selected' : ''; ?>>AC Room</option>
                        </select>
                        <div class="invalid-feedback">Room type is required.</div>
                    </div>

                    <div class="mb-3">
                        <label for="capacity" class="form-label">Sharing Capacity <span class="small text-muted">(Beds count)</span></label>
                        <input type="number" class="form-control" id="capacity" name="capacity" min="1" max="10"
                               placeholder="e.g. 2" 
                               value="<?php echo $editRoom ? sanitize($editRoom['capacity']) : '2'; ?>" required>
                        <div class="invalid-feedback">Sharing capacity must be 1-10.</div>
                    </div>

                    <div class="mb-3">
                        <label for="price" class="form-label">Monthly rent (₹)</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0"
                               placeholder="e.g. 5000" 
                               value="<?php echo $editRoom ? sanitize($editRoom['price']) : ''; ?>" required>
                        <div class="invalid-feedback">Please input a valid price.</div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Availability status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Available" <?php echo ($editRoom && $editRoom['status'] === 'Available') ? 'selected' : ''; ?>>Available</option>
                            <option value="Full" <?php echo ($editRoom && $editRoom['status'] === 'Full') ? 'selected' : ''; ?>>Full (Force Occupied)</option>
                            <option value="Maintenance" <?php echo ($editRoom && $editRoom['status'] === 'Maintenance') ? 'selected' : ''; ?>>Maintenance</option>
                        </select>
                        <div class="invalid-feedback">Room status is required.</div>
                    </div>

                    <div class="d-grid mt-4 gap-2">
                        <button type="submit" class="btn btn-primary-gradient py-2">
                            <?php echo $editRoom ? '<i class="bi bi-save"></i> Save Changes' : '<i class="bi bi-plus-circle"></i> Create Room'; ?>
                        </button>
                        <?php if ($editRoom): ?>
                            <a href="rooms.php" class="btn btn-outline-secondary py-2">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Rooms List Catalog Card -->
    <div class="col-lg-8">
        <div class="card border-0 rounded-4 shadow-sm">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold m-0 text-indigo-900"><i class="bi bi-door-open-fill text-indigo-600"></i> Registered Rooms Catalog</h5>
            </div>
            <div class="card-body p-4 pt-0">
                <?php if (!empty($roomsList)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Hostel Building</th>
                                    <th>Room #</th>
                                    <th>Comfort Type</th>
                                    <th>Occupancy</th>
                                    <th>Fee</th>
                                    <th>Status</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roomsList as $r): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold text-indigo-950"><?php echo sanitize($r['hostel_name']); ?></div>
                                        </td>
                                        <td><span class="badge bg-indigo-100 text-indigo-800 fw-bold px-2 py-1 rounded">Room <?php echo sanitize($r['room_number']); ?></span></td>
                                        <td><span class="small"><?php echo sanitize($r['room_type']); ?></span></td>
                                        <td>
                                            <span class="small font-weight-bold">
                                                <?php echo sanitize($r['current_occupancy']); ?> / <?php echo sanitize($r['capacity']); ?> Beds
                                            </span>
                                        </td>
                                        <td class="small fw-bold text-indigo-600"><?php echo format_currency($r['price']); ?></td>
                                        <td><?php echo get_room_status_badge($r['status']); ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <a href="rooms.php?edit=<?php echo (int)$r['id']; ?>" class="btn btn-outline-primary btn-sm px-2.5 rounded-pill">
                                                    <i class="bi bi-pencil-fill"></i> Edit
                                                </a>
                                                <form action="rooms.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this room configuration? Associated allocations will be deleted.');">
                                                    <input type="hidden" name="action" value="delete_room">
                                                    <input type="hidden" name="room_id" value="<?php echo (int)$r['id']; ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm px-2.5 rounded-pill">
                                                        <i class="bi bi-trash3-fill"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-door-closed display-6 text-muted mb-2"></i>
                        <h5>No Rooms Configured</h5>
                        <p class="mb-0">Create the first room using the form on the left.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
