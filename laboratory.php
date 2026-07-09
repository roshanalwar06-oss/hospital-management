<?php
/**
 * =====================================================================
 * FILE: admin/laboratory.php
 * PLACE AT: hospital-management/admin/laboratory.php
 * PURPOSE: List all lab tests with status filter
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$filterStatus = trim($_GET['status'] ?? '');
$search       = trim($_GET['search'] ?? '');

$sql = "SELECT l.*, p.name AS patient_name, d.name AS doctor_name
        FROM laboratory l
        JOIN patients p ON l.patient_id = p.id
        LEFT JOIN doctors d ON l.doctor_id = d.id
        WHERE 1=1";
$params = [];

if ($filterStatus !== '') {
    $sql .= " AND l.status = ?";
    $params[] = $filterStatus;
}
if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR l.test_name LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$sql .= " ORDER BY l.test_date DESC, l.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$labs = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Laboratory';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="page-title mb-1">Laboratory Management</h3>
        <p class="section-subtitle mb-0">Manage lab tests, results, and uploaded reports.</p>
    </div>
    <a href="lab_add.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> New Lab Test</a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?php echo sanitize($flash['type']); ?> auto-dismiss"><?php echo sanitize($flash['message']); ?></div>
<?php endif; ?>

<div class="card table-card">
    <div class="card-header">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Search Patient / Test</label>
                <input type="text" name="search" class="form-control form-control-sm" value="<?php echo sanitize($search); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="pending" <?php echo $filterStatus==='pending'?'selected':''; ?>>Pending</option>
                    <option value="in_progress" <?php echo $filterStatus==='in_progress'?'selected':''; ?>>In Progress</option>
                    <option value="completed" <?php echo $filterStatus==='completed'?'selected':''; ?>>Completed</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-sm btn-primary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="laboratory.php" class="btn btn-sm btn-light"><i class="fas fa-redo"></i></a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Test Name</th><th>Patient</th><th>Doctor</th><th>Date</th><th>Price</th><th>Status</th><th>Report</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($labs)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No lab tests found.</td></tr>
                    <?php else: foreach ($labs as $l): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo sanitize($l['test_name']); ?></td>
                            <td><?php echo sanitize($l['patient_name']); ?></td>
                            <td><?php echo sanitize($l['doctor_name'] ?: '-'); ?></td>
                            <td><?php echo formatDate($l['test_date']); ?></td>
                            <td><?php echo formatCurrency($l['price']); ?></td>
                            <td><span class="badge-status badge-<?php echo $l['status']==='completed'?'completed':'pending'; ?>"><?php echo ucfirst(str_replace('_',' ',$l['status'])); ?></span></td>
                            <td>
                                <?php if ($l['report_file']): ?>
                                    <a href="<?php echo BASE_URL; ?>uploads/lab_reports/<?php echo sanitize($l['report_file']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-pdf"></i></a>
                                <?php else: ?>
                                    <span class="text-muted small">Not uploaded</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="lab_upload.php?id=<?php echo $l['id']; ?>" class="btn btn-icon btn-outline-primary" title="Update / Upload Report"><i class="fas fa-upload"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
