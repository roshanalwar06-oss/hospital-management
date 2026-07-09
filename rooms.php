<?php
/**
 * =====================================================================
 * FILE: admin/rooms.php
 * PLACE AT: hospital-management/admin/rooms.php
 * PURPOSE: List all rooms (ICU / General Ward / Private) with status
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$filterType = trim($_GET['type'] ?? '');

$sql = "SELECT r.*, p.name AS patient_name FROM rooms r LEFT JOIN patients p ON r.patient_id = p.id WHERE 1=1";
$params = [];
if ($filterType !== '') {
    $sql .= " AND r.room_type = ?";
    $params[] = $filterType;
}
$sql .= " ORDER BY r.room_type, r.room_number";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

$totalRooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
$occupiedRooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status='occupied'")->fetchColumn();
$availableRooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status='available'")->fetchColumn();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Room Management';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="page-title mb-1">Room Management</h3>
        <p class="section-subtitle mb-0">Manage ICU, General Ward, and Private Room allocation.</p>
    </div>
    <a href="room_add.php" class="btn btn-primary"><i class="fas fa-plus me-1"></i> Add Room</a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?php echo sanitize($flash['type']); ?> auto-dismiss"><?php echo sanitize($flash['message']); ?></div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-sm-4">
        <div class="stat-card bg-blue"><i class="fas fa-bed stat-icon"></i>
            <div class="stat-value"><?php echo $totalRooms; ?></div><div class="stat-label">Total Rooms</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card bg-green"><i class="fas fa-door-open stat-icon"></i>
            <div class="stat-value"><?php echo $availableRooms; ?></div><div class="stat-label">Available</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="stat-card bg-orange"><i class="fas fa-door-closed stat-icon"></i>
            <div class="stat-value"><?php echo $occupiedRooms; ?></div><div class="stat-label">Occupied</div>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="fas fa-bed me-2 text-primary"></i>All Rooms</span>
        <form method="GET" class="d-flex gap-2">
            <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Types</option>
                <option value="ICU" <?php echo $filterType==='ICU'?'selected':''; ?>>ICU</option>
                <option value="General Ward" <?php echo $filterType==='General Ward'?'selected':''; ?>>General Ward</option>
                <option value="Private Room" <?php echo $filterType==='Private Room'?'selected':''; ?>>Private Room</option>
            </select>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Room #</th><th>Type</th><th>Price/Day</th><th>Status</th><th>Patient Assigned</th><th>Allocated Date</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($rooms)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No rooms found.</td></tr>
                    <?php else: foreach ($rooms as $r): ?>
                        <tr>
                            <td class="fw-semibold"><?php echo sanitize($r['room_number']); ?></td>
                            <td><?php echo sanitize($r['room_type']); ?></td>
                            <td><?php echo formatCurrency($r['price_per_day']); ?></td>
                            <td><span class="badge-status badge-<?php echo $r['status']==='available'?'available':'occupied'; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                            <td><?php echo sanitize($r['patient_name'] ?: '-'); ?></td>
                            <td><?php echo formatDate($r['allocated_date']); ?></td>
                            <td class="text-end">
                                <a href="room_edit.php?id=<?php echo $r['id']; ?>" class="btn btn-icon btn-outline-primary" title="Edit / Allocate"><i class="fas fa-edit"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
