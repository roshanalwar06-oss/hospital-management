<?php
/**
 * =====================================================================
 * FILE: admin/patients.php
 * PLACE AT: hospital-management/admin/patients.php
 * PURPOSE: List all patients with search, links to view/edit/delete
 * =====================================================================
 */
require_once '../config.php'; 
requireRole('admin');

$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE name LIKE ? OR email LIKE ? OR phone LIKE ? ORDER BY created_at DESC");
    $likeSearch = '%' . $search . '%';
    $stmt->execute([$likeSearch, $likeSearch, $likeSearch]);
} else {
    $stmt = $pdo->query("SELECT * FROM patients ORDER BY created_at DESC");
}
$patients = $stmt->fetchAll();

// Flash message support (after redirect from add/edit/delete)
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Patient Management';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="page-title mb-1">Patient Management</h3>
        <p class="section-subtitle mb-0">View, add, edit, or remove patient records.</p>
    </div>
    <a href="../patient_add.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add New Patient</a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?php echo sanitize($flash['type']); ?> auto-dismiss"><?php echo sanitize($flash['message']); ?></div>
<?php endif; ?>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="fas fa-list me-2 text-primary"></i>All Patients (<?php echo count($patients); ?>)</span>
        <form method="GET" class="d-flex" style="max-width:320px;">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, email or phone..." value="<?php echo sanitize($search); ?>">
            <button class="btn btn-sm btn-primary ms-2"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="patientsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Patient</th>
                        <th>Contact</th>
                        <th>Gender</th>
                        <th>Blood Group</th>
                        <th>Joined</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($patients)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No patients found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($patients as $i => $p): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?php echo BASE_URL; ?>uploads/profile_images/<?php echo sanitize($p['profile_image']); ?>" class="avatar-sm" onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.png'" alt="">
                                    <div>
                                        <div class="fw-semibold"><?php echo sanitize($p['name']); ?></div>
                                        <small class="text-muted"><?php echo sanitize($p['email']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo sanitize($p['phone'] ?: '-'); ?></td>
                            <td><?php echo sanitize($p['gender']); ?></td>
                            <td><?php echo sanitize($p['blood_group'] ?: '-'); ?></td>
                            <td><?php echo formatDate($p['created_at']); ?></td>
                            <td class="text-end">
                                <a href="../patient_view.php?id=<?php echo $p['id']; ?>" class="btn btn-icon btn-outline-info" title="View History"><i class="fas fa-eye"></i></a>
                                <a href="../patient_edit.php?id=<?php echo $p['id']; ?>" class="btn btn-icon btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="../patient_delete.php?id=<?php echo $p['id']; ?>" class="btn btn-icon btn-outline-danger confirm-delete" data-name="<?php echo sanitize($p['name']); ?>" title="Delete"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php';
