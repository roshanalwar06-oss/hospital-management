<?php
/**
 * =====================================================================
 * FILE: admin/staff.php
 * PLACE AT: hospital-management/admin/staff.php
 * PURPOSE: List all staff members (Receptionists, Nurses, Employees)
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$filterRole = trim($_GET['role'] ?? '');
$search     = trim($_GET['search'] ?? '');

$sql = "SELECT * FROM staff WHERE 1=1";
$params = [];
if ($filterRole !== '') {
    $sql .= " AND role = ?";
    $params[] = $filterRole;
}
if ($search !== '') {
    $sql .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$staffList = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Staff Management';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="page-title mb-1">Staff Management</h3>
        <p class="section-subtitle mb-0">Manage receptionists, nurses, and general employees.</p>
    </div>
    <a href="staff_add.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Staff Member</a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?php echo sanitize($flash['type']); ?> auto-dismiss"><?php echo sanitize($flash['message']); ?></div>
<?php endif; ?>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="fas fa-users-cog me-2 text-primary"></i>All Staff (<?php echo count($staffList); ?>)</span>
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <select name="role" class="form-select form-select-sm" onchange="this.form.submit()" style="width:auto;">
                <option value="">All Roles</option>
                <option value="receptionist" <?php echo $filterRole==='receptionist'?'selected':''; ?>>Receptionist</option>
                <option value="nurse" <?php echo $filterRole==='nurse'?'selected':''; ?>>Nurse</option>
                <option value="employee" <?php echo $filterRole==='employee'?'selected':''; ?>>Employee</option>
            </select>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name/email..." value="<?php echo sanitize($search); ?>">
            <button class="btn btn-sm btn-primary"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>Staff Member</th><th>Role</th><th>Designation</th><th>Phone</th><th>Salary</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($staffList)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No staff members found.</td></tr>
                    <?php else: foreach ($staffList as $i => $s): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="<?php echo BASE_URL; ?>uploads/profile_images/<?php echo sanitize($s['profile_image']); ?>" class="avatar-sm" onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.png'" alt="">
                                    <div>
                                        <div class="fw-semibold"><?php echo sanitize($s['name']); ?></div>
                                        <small class="text-muted"><?php echo sanitize($s['email']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><span class="badge-status badge-confirmed"><?php echo ucfirst($s['role']); ?></span></td>
                            <td><?php echo sanitize($s['designation'] ?: '-'); ?></td>
                            <td><?php echo sanitize($s['phone'] ?: '-'); ?></td>
                            <td><?php echo formatCurrency($s['salary']); ?></td>
                            <td><span class="badge-status badge-<?php echo $s['status']; ?>"><?php echo ucfirst($s['status']); ?></span></td>
                            <td class="text-end">
                                <a href="staff_edit.php?id=<?php echo $s['id']; ?>" class="btn btn-icon btn-outline-primary" title="Edit"><i class="fas fa-edit"></i></a>
                                <a href="staff_delete.php?id=<?php echo $s['id']; ?>" class="btn btn-icon btn-outline-danger confirm-delete" data-name="<?php echo sanitize($s['name']); ?>" title="Delete"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
