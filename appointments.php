<?php
/**
 * =====================================================================
 * FILE: admin/appointments.php
 * PLACE AT: hospital-management/admin/appointments.php
 * PURPOSE: List all appointments with filters (date, status, doctor)
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$filterDate   = trim($_GET['date'] ?? '');
$filterStatus = trim($_GET['status'] ?? '');
$search       = trim($_GET['search'] ?? '');

$sql = "SELECT a.*, p.name AS patient_name, p.phone AS patient_phone, d.name AS doctor_name, d.specialization
        FROM appointments a
        JOIN patients p ON a.patient_id = p.id
        JOIN doctors d ON a.doctor_id = d.id
        WHERE 1=1";
$params = [];

if ($filterDate !== '') {
    $sql .= " AND a.appointment_date = ?";
    $params[] = $filterDate;
}
if ($filterStatus !== '') {
    $sql .= " AND a.status = ?";
    $params[] = $filterStatus;
}
if ($search !== '') {
    $sql .= " AND (p.name LIKE ? OR d.name LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$pageTitle = 'Appointment Management';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
    <div>
        <h3 class="page-title mb-1">Appointment Management</h3>
        <p class="section-subtitle mb-0">Book, reschedule, cancel, and track appointment status.</p>
    </div>
    <a href="appointment_add.php" class="btn btn-primary"><i class="fas fa-calendar-plus me-1"></i> Book Appointment</a>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?php echo sanitize($flash['type']); ?> auto-dismiss"><?php echo sanitize($flash['message']); ?></div>
<?php endif; ?>

<div class="card table-card">
    <div class="card-header">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1">Date</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?php echo sanitize($filterDate); ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-1">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    <?php foreach (['pending','confirmed','completed','cancelled','rescheduled'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php echo $filterStatus===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-1">Search Patient/Doctor</label>
                <input type="text" name="search" class="form-control form-control-sm" value="<?php echo sanitize($search); ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-sm btn-primary w-100"><i class="fas fa-filter me-1"></i>Filter</button>
                <a href="appointments.php" class="btn btn-sm btn-light" title="Reset"><i class="fas fa-redo"></i></a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr><th>Date</th><th>Time</th><th>Patient</th><th>Doctor</th><th>Reason</th><th>Status</th><th class="text-end">Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No appointments found.</td></tr>
                    <?php else: foreach ($appointments as $a): ?>
                        <tr>
                            <td><?php echo formatDate($a['appointment_date']); ?></td>
                            <td><?php echo date('h:i A', strtotime($a['appointment_time'])); ?></td>
                            <td><?php echo sanitize($a['patient_name']); ?><br><small class="text-muted"><?php echo sanitize($a['patient_phone'] ?: ''); ?></small></td>
                            <td><?php echo sanitize($a['doctor_name']); ?><br><small class="text-muted"><?php echo sanitize($a['specialization'] ?: ''); ?></small></td>
                            <td><?php echo sanitize($a['reason'] ?: '-'); ?></td>
                            <td><span class="badge-status badge-<?php echo $a['status']; ?>"><?php echo ucfirst($a['status']); ?></span></td>
                            <td class="text-end">
                                <a href="appointment_edit.php?id=<?php echo $a['id']; ?>" class="btn btn-icon btn-outline-primary" title="Edit / Reschedule"><i class="fas fa-edit"></i></a>
                                <?php if ($a['status'] !== 'cancelled' && $a['status'] !== 'completed'): ?>
                                    <a href="appointment_cancel.php?id=<?php echo $a['id']; ?>" class="btn btn-icon btn-outline-danger confirm-delete" data-name="this appointment" title="Cancel"><i class="fas fa-times"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
