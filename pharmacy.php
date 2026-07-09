<?php
/**
 * =====================================================================
 * FILE: admin/pharmacy.php
 * PLACE AT: hospital-management/admin/pharmacy.php
 * PURPOSE: List all medicines, highlight low stock / near expiry
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$search = trim($_GET['search'] ?? '');

if ($search !== '') {
    $stmt = $pdo->prepare("SELECT * FROM medicines WHERE name LIKE ? OR category LIKE ? OR manufacturer LIKE ? ORDER BY name ASC");
    $like = '%' . $search . '%';
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT * FROM medicines ORDER BY name ASC");
}
$medicines = $stmt->fetchAll();

$lowStockCount = $pdo->query("SELECT COUNT(*) FROM medicines WHERE stock_quantity <= 20")->fetchColumn();
$expiringCount = $pdo->query("SELECT COUNT(*) FROM medicines WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)")->fetchColumn();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Pharmacy';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="page-title mb-1">Pharmacy Management</h3>
        <p class="section-subtitle mb-0">Manage medicine inventory and stock levels.</p>
    </div>
    <a href="medicine_add.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Medicine</a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?php echo sanitize($flash['type']); ?> auto-dismiss"><?php echo sanitize($flash['message']); ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-4">
        <div class="stat-card bg-blue"><i class="fas fa-pills stat-icon"></i>
            <div class="stat-value"><?php echo count($medicines); ?></div>
            <div class="stat-label">Total Medicines</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="stat-card bg-orange"><i class="fas fa-exclamation-triangle stat-icon"></i>
            <div class="stat-value"><?php echo $lowStockCount; ?></div>
            <div class="stat-label">Low Stock (≤20 units)</div>
        </div>
    </div>
    <div class="col-sm-6 col-md-4">
        <div class="stat-card bg-pink"><i class="fas fa-hourglass-end stat-icon"></i>
            <div class="stat-value"><?php echo $expiringCount; ?></div>
            <div class="stat-label">Expiring Within 60 Days</div>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="fas fa-pills me-2 text-primary"></i>Medicine Inventory</span>
        <form method="GET" class="d-flex" style="max-width:320px;">
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Search medicine..." value="<?php echo sanitize($search); ?>">
            <button class="btn btn-sm btn-primary ms-2"><i class="fas fa-search"></i></button>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>#</th><th>Medicine</th><th>Category</th><th>Manufacturer</th><th>Price</th><th>Stock</th><th>Expiry</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($medicines)): ?>
                        <tr><td colspan="8" class="text-center text-muted py-4">No medicines found.</td></tr>
                    <?php else: foreach ($medicines as $i => $m): ?>
                        <?php
                            $isLow = $m['stock_quantity'] <= 20;
                            $isExpiring = $m['expiry_date'] && strtotime($m['expiry_date']) <= strtotime('+60 days');
                        ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td class="fw-semibold"><?php echo sanitize($m['name']); ?></td>
                            <td><?php echo sanitize($m['category'] ?: '-'); ?></td>
                            <td><?php echo sanitize($m['manufacturer'] ?: '-'); ?></td>
                            <td><?php echo formatCurrency($m['price']); ?> / <?php echo sanitize($m['unit']); ?></td>
                            <td>
                                <span class="badge-status <?php echo $isLow ? 'badge-cancelled' : 'badge-available'; ?>"><?php echo $m['stock_quantity']; ?> units</span>
                            </td>
                            <td class="<?php echo $isExpiring ? 'text-danger fw-semibold' : ''; ?>"><?php echo formatDate($m['expiry_date']); ?></td>
                            <td class="text-end">
                                <a href="medicine_edit.php?id=<?php echo $m['id']; ?>" class="btn btn-icon btn-outline-primary" title="Edit / Update Stock"><i class="fas fa-edit"></i></a>
                                <a href="medicine_delete.php?id=<?php echo $m['id']; ?>" class="btn btn-icon btn-outline-danger confirm-delete" data-name="<?php echo sanitize($m['name']); ?>" title="Delete"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
