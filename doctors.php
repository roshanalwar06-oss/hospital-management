<?php
/**
 * =====================================================================
 * FILE: admin/doctors.php
 * PLACE AT: hospital-management/admin/doctors.php
 * PURPOSE: List all doctors with search, links to edit/delete/schedule
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM doctors WHERE name LIKE ? OR email LIKE ? OR specialization LIKE ? ORDER BY created_at DESC");
    $likeSearch = '%' . $search . '%';
    $stmt->execute([$likeSearch, $likeSearch, $likeSearch]);
} else {
    $stmt = $pdo->query("SELECT * FROM doctors ORDER BY created_at DESC");
}
$doctors = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Doctor Management';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="page-title mb-1">Doctor Management</h3>
        <p class="section-subtitle mb-0">Manage doctor profiles, schedules and availability.</p>
    </div>
    <a href="doctor_add.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add New Doctor</a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?php echo sanitize($flash['type']); ?> auto-dismiss"><?php echo sanitize($flash['message']); ?></div>
<?php endif; ?>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="fas fa-user-md me-2 text-primary"></i>All Doctors (<?php echo count($doctors); ?>)</span>
        <form method="GET" class="d-flex" style="max-width:320px;">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name or specialization..." value="<?php echo sanitize($search); ?>">
            <button class="btn btn-sm btn-primary ms-2"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th><th>Doctor</th><th>Specialization</th><th>Fee</th><th>Availability</th><th>Status</th><th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($doctors)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No doctors found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($doctors as $i => $d): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?php echo BASE_URL; ?>uploads/profile_images/<?php echo sanitize($d['profile_image']); ?>" class="avatar-sm" onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.png'" alt="">
                                    <div>
                                        <div class="fw-semibold"><?php echo sanitize($d['name']); ?></div>
                                        <small class="text-muted"><?php echo sanitize($d['email']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo sanitize($d['specialization'] ?: '-'); ?></td>
                            <td><?php echo formatCurrency($d['consultation_fee']); ?></td>
                            <td><span class="badge-status badge-<?php echo $d['availability']==='available'?'available':'occupied'; ?>"><?php echo ucfirst(str_replace('_',' ',$d['availability'])); ?></span></td>
                            <td><span class="badge-status badge-<?php echo $d['status']; ?>"><?php echo ucfirst($d['status']); ?></span></td>
                            <td class="text-end">
                                <a href="doctor_schedule.php?id=<?php echo $d['id']; ?>" class="btn btn-icon btn-outline-info" title="Schedule"><i class="fas fa-calendar-alt"></i></a>
                                <a href="doctor_edit.php?id=<?php echo $d['id']; ?>" class="btn btn-icon btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="doctor_delete.php?id=<?php echo $d['id']; ?>" class="btn btn-icon btn-outline-danger confirm-delete" data-name="<?php echo sanitize($d['name']); ?>" title="Delete"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
