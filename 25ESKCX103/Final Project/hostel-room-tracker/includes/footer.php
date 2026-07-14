<?php
// Shared HTML footer structure

$scriptName = $_SERVER['SCRIPT_NAME'];
$isSubdir = (strpos($scriptName, '/student/') !== false || strpos($scriptName, '/admin/') !== false);
$basePath = $isSubdir ? '../' : '';
$user = get_logged_in_user();
?>

<?php if (is_logged_in()): ?>
        <!-- Footer info inside layout -->
        <footer class="mt-5 pt-3 border-top text-muted small text-center">
            <p>&copy; <?php echo date('Y'); ?> Hostel Room Request Tracker. All rights reserved.</p>
        </footer>
    </main>
</div>
<?php else: ?>
    <!-- Footer info outside layout (like Login, Installer) -->
    <footer class="mt-5 text-muted small text-center py-4">
        <p>&copy; <?php echo date('Y'); ?> Hostel Room Request Tracker. All rights reserved.</p>
    </footer>
<?php endif; ?>

<!-- Bootstrap 5 Bundle JS (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JavaScript -->
<script src="<?php echo $basePath; ?>js/main.js"></script>
</body>
</html>
