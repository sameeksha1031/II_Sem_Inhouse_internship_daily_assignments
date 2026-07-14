<?php
// Administrator Student Records Directory

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

require_role('admin');
$user = get_logged_in_user();

// Fetch students list with active allocations
$students = $pdo->query("
    SELECT u.*, rm.room_number, h.name as hostel_name 
    FROM users u 
    LEFT JOIN allocations a ON u.id = a.user_id AND a.status = 'Active' 
    LEFT JOIN rooms rm ON a.room_id = rm.id 
    LEFT JOIN hostels h ON rm.hostel_id = h.id 
    WHERE u.role = 'student' 
    ORDER BY u.name
")->fetchAll();

$pageTitle = "Student Directory";
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="card border-0 rounded-4 shadow-sm animated-fade-in">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold m-0 text-indigo-900"><i class="bi bi-people-fill text-indigo-600"></i> Registered Student Records</h5>
    </div>
    <div class="card-body p-4 pt-0">
        <?php if (!empty($students)): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle m-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Phone Number</th>
                            <th>Gender</th>
                            <th>Assigned Room</th>
                            <th>Join Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $student): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border px-2.5 py-1.5 rounded fw-bold">
                                        <?php echo sanitize($student['student_id'] ?? 'Not set'); ?>
                                    </span>
                                </td>
                                <td class="fw-semibold text-indigo-950"><?php echo sanitize($student['name']); ?></td>
                                <td><a href="mailto:<?php echo sanitize($student['email']); ?>" class="text-decoration-none"><?php echo sanitize($student['email']); ?></a></td>
                                <td><?php echo sanitize($student['phone'] ?? '-'); ?></td>
                                <td class="capitalize"><?php echo sanitize($student['gender']); ?></td>
                                <td>
                                    <?php if (!empty($student['room_number'])): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-10 px-3 py-1.5 rounded-pill">
                                            <i class="bi bi-house-door-fill"></i> Room <?php echo sanitize($student['room_number']); ?> (<?php echo sanitize($student['hostel_name']); ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border px-3 py-1.5 rounded-pill">
                                            <i class="bi bi-slash-circle"></i> Unallocated
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?php echo date('d M Y', strtotime($student['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-people display-6 text-muted mb-2"></i>
                <h5>No Registered Students</h5>
                <p class="mb-0 small text-muted">There are no student accounts registered in the database yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
