<?php
/**
 * =====================================================================
 * FILE: admin/billing.php
 * PLACE AT: hospital-management/admin/billing.php
 * PURPOSE: List all bills with payment status filter
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$filterStatus = trim($_GET['status'] ?? '');
$search       = trim($_GET['search'] ?? '');

$sql = "SELECT b.*, p.name AS patient_name, p.phone AS patient_phone
        FROM bills b JOIN patients p ON b.patient_id = p.id WHERE 1=1";
$params = [];

if ($filterStatus !== '') {
    $sql .= " AND b.payment_status = ?";
    $params[] = $filterStatus;
}
if ($search !== '') {
    $sql .= " AND p.name LIKE ?";
    $params[] = '%' . $search . '%';
}
$sql .= " ORDER BY b.bill_date DESC, b.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bills = $stmt->fetchAll();

$totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bills")->fetchColumn();
$totalUnpaid  = $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE payment_status != 'paid'")->fetchColumn();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Billing System';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="page-title mb-1">Billing System</h3>
        <p class="section-subtitle mb-0">Generate, print, and track patient invoices.</p>
    </div>
    <a href="billing_add.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Generate New Bill</a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?php echo sanitize($flash['type']); ?> auto-dismiss"><?php echo sanitize($flash['message']); ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-4">
        <div class="stat-card bg-green"><i class="fas fa-coins stat-icon"></i>
            <div class="stat-value"><?php echo formatCurrency($totalRevenue); ?></div>
            <div class="stat-label">Total Billed Revenue</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="stat-card bg-orange"><i class="fas fa-exclamation-circle stat-icon"></i>
            <div class="stat-value"><?php echo formatCurrency($totalUnpaid); ?></div>
            <div class="stat-label">Outstanding Amount</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="stat-card bg-blue"><i class="fas fa-file-invoice stat-icon"></i>
            <div class="stat-value"><?php echo count($bills); ?></div>
            <div class="stat-label">Total Bills</div>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="card-header">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Search Patient</label>
                <input type="text" name="search" class="form-control form-control-sm" value="<?php echo sanitize($search); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Payment Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="paid" <?php echo $filterStatus==='paid'?'selected':''; ?>>Paid</option>
                    <option value="partial" <?php echo $filterStatus==='partial'?'selected':''; ?>>Partial</option>
                    <option value="unpaid" <?php echo $filterStatus==='unpaid'?'selected':''; ?>>Unpaid</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-sm btn-primary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="billing.php" class="btn btn-sm btn-light"><i class="fas fa-redo"></i></a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Bill #</th><th>Patient</th><th>Date</th><th>Total</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($bills)): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No bills found.</td></tr>
                    <?php else: foreach ($bills as $b): ?>
                        <tr>
                            <td>#INV-<?php echo str_pad($b['id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td><?php echo sanitize($b['patient_name']); ?><br><small class="text-muted"><?php echo sanitize($b['patient_phone'] ?: ''); ?></small></td>
                            <td><?php echo formatDate($b['bill_date']); ?></td>
                            <td class="fw-semibold"><?php echo formatCurrency($b['total_amount']); ?></td>
                            <td><span class="badge-status badge-<?php echo $b['payment_status']; ?>"><?php echo ucfirst($b['payment_status']); ?></span></td>
                            <td class="text-end">
                                <a href="billing_view.php?id=<?php echo $b['id']; ?>" class="btn btn-icon btn-outline-primary" title="View / Print"><i class="fas fa-file-invoice"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
