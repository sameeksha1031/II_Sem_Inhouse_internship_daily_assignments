<?php
// Administrator Hostel Config Panel

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_role('admin');
$user = get_logged_in_user();

$error = '';
$success = '';
$editHostel = null;

// 1. Process Add Hostel (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_hostel') {
    $name = trim($_POST['name']);
    $type = $_POST['type'] ?? '';
    
    if (empty($name) || empty($type)) {
        $error = "All fields are required.";
    } else {
        // Check duplication
        $stmt = $pdo->prepare("SELECT id FROM hostels WHERE name = :name");
        $stmt->execute(['name' => $name]);
        if ($stmt->fetch()) {
            $error = "A hostel with that name already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO hostels (name, type) VALUES (:name, :type)");
            try {
                $stmt->execute(['name' => $name, 'type' => $type]);
                $success = "Hostel '$name' has been added successfully.";
            } catch (PDOException $e) {
                $error = "Failed to add hostel: " . $e->getMessage();
            }
        }
    }
}

// 2. Process Edit Hostel (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_hostel') {
    $hostel_id = (int)$_POST['hostel_id'];
    $name = trim($_POST['name']);
    $type = $_POST['type'] ?? '';
    
    if (empty($name) || empty($type)) {
        $error = "All fields are required.";
    } else {
        // Check duplication (excluding current ID)
        $stmt = $pdo->prepare("SELECT id FROM hostels WHERE name = :name AND id != :id");
        $stmt->execute(['name' => $name, 'id' => $hostel_id]);
        if ($stmt->fetch()) {
            $error = "Another hostel with that name already exists.";
        } else {
            $stmt = $pdo->prepare("UPDATE hostels SET name = :name, type = :type WHERE id = :id");
            try {
                $stmt->execute(['name' => $name, 'type' => $type, 'id' => $hostel_id]);
                $success = "Hostel configuration updated successfully.";
            } catch (PDOException $e) {
                $error = "Failed to update hostel: " . $e->getMessage();
            }
        }
    }
}

// 3. Process Delete Hostel (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_hostel') {
    $hostel_id = (int)$_POST['hostel_id'];
    
    $stmt = $pdo->prepare("DELETE FROM hostels WHERE id = :id");
    try {
        $stmt->execute(['id' => $hostel_id]);
        $success = "Hostel and its associated rooms have been deleted.";
    } catch (PDOException $e) {
        $error = "Failed to delete hostel: " . $e->getMessage();
    }
}

// Check if we are editing an item
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM hostels WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $edit_id]);
    $editHostel = $stmt->fetch();
}

// Fetch all hostels with room and bed statistics
$hostelsList = $pdo->query("
    SELECT h.*, COUNT(r.id) as total_rooms, SUM(r.capacity) as total_beds 
    FROM hostels h 
    LEFT JOIN rooms r ON h.id = r.hostel_id 
    GROUP BY h.id 
    ORDER BY h.name
")->fetchAll();

$pageTitle = "Manage Hostels";
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
                    <?php echo $editHostel ? '<i class="bi bi-pencil-square text-indigo-600"></i> Edit Hostel Building' : '<i class="bi bi-plus-circle-fill text-indigo-600"></i> Add Hostel Building'; ?>
                </h5>
            </div>
            <div class="card-body p-4 pt-0">
                <form action="hostels.php" method="POST" class="needs-validation" novalidate>
                    <?php if ($editHostel): ?>
                        <input type="hidden" name="action" value="edit_hostel">
                        <input type="hidden" name="hostel_id" value="<?php echo (int)$editHostel['id']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="add_hostel">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">Building Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                               placeholder="e.g. Tagore PG Residence" 
                               value="<?php echo $editHostel ? sanitize($editHostel['name']) : ''; ?>" required>
                        <div class="invalid-feedback">Building name is required.</div>
                    </div>

                    <div class="mb-3">
                        <label for="type" class="form-label">Category restriction</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="" disabled selected>Select category...</option>
                            <option value="boys" <?php echo ($editHostel && $editHostel['type'] === 'boys') ? 'selected' : ''; ?>>Boys Only</option>
                            <option value="girls" <?php echo ($editHostel && $editHostel['type'] === 'girls') ? 'selected' : ''; ?>>Girls Only</option>
                            <option value="coed" <?php echo ($editHostel && $editHostel['type'] === 'coed') ? 'selected' : ''; ?>>Co-ed</option>
                        </select>
                        <div class="invalid-feedback">Please select a category type.</div>
                    </div>

                    <div class="d-grid mt-4 gap-2">
                        <button type="submit" class="btn btn-primary-gradient py-2">
                            <?php echo $editHostel ? '<i class="bi bi-save"></i> Save Changes' : '<i class="bi bi-plus-circle"></i> Create Hostel'; ?>
                        </button>
                        <?php if ($editHostel): ?>
                            <a href="hostels.php" class="btn btn-outline-secondary py-2">Cancel Edit</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Hostels List Card -->
    <div class="col-lg-8">
        <div class="card border-0 rounded-4 shadow-sm">
            <div class="card-header bg-white py-3 border-0">
                <h5 class="fw-bold m-0 text-indigo-900"><i class="bi bi-buildings-fill text-indigo-600"></i> Registered Hostels Directory</h5>
            </div>
            <div class="card-body p-4 pt-0">
                <?php if (!empty($hostelsList)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle m-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Building Name</th>
                                    <th>Restricted Category</th>
                                    <th>Rooms Count</th>
                                    <th>Total Beds</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hostelsList as $h): ?>
                                    <tr>
                                        <td>#<?php echo (int)$h['id']; ?></td>
                                        <td class="fw-semibold text-indigo-950"><?php echo sanitize($h['name']); ?></td>
                                        <td><?php echo get_hostel_type_label($h['type']); ?></td>
                                        <td><span class="badge bg-secondary bg-opacity-10 text-secondary border px-2 py-1 rounded"><?php echo (int)$h['total_rooms']; ?> Rooms</span></td>
                                        <td><span class="badge bg-indigo-100 text-indigo-800 px-2 py-1 rounded"><?php echo (int)($h['total_beds'] ?? 0); ?> Beds</span></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <a href="hostels.php?edit=<?php echo (int)$h['id']; ?>" class="btn btn-outline-primary btn-sm px-2.5 rounded-pill">
                                                    <i class="bi bi-pencil-fill"></i> Edit
                                                </a>
                                                <form action="hostels.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this hostel? All associated rooms and active allocations will be deleted!');">
                                                    <input type="hidden" name="action" value="delete_hostel">
                                                    <input type="hidden" name="hostel_id" value="<?php echo (int)$h['id']; ?>">
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
                        <i class="bi bi-buildings display-6 text-muted mb-2"></i>
                        <h5>No Buildings Registered</h5>
                        <p class="mb-0">Create the first building in the form on the left.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
