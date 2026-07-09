<?php
/**
 * =====================================================================
 * FILE: admin/patient_view.php
 * PLACE AT: hospital-management/admin/patient_view.php
 * PURPOSE: View full patient profile + appointment/billing/lab history
 * =====================================================================
 */
require_once __DIR__ . '/config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('admin/patients.php'); }

$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$id]);
$patient = $stmt->fetch();

if (!$patient) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Patient not found.'];
    redirect('admin/patients.php');
}

// ---- Appointment History ----
$stmt = $pdo->prepare("
    SELECT a.*, d.name AS doctor_name, d.specialization
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.patient_id = ?
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt->execute([$id]);
$appointments = $stmt->fetchAll();

// ---- Billing History ----
$stmt = $pdo->prepare("SELECT * FROM bills WHERE patient_id = ? ORDER BY bill_date DESC");
$stmt->execute([$id]);
$bills = $stmt->fetchAll();

// ---- Lab History ----
$stmt = $pdo->prepare("SELECT l.*, d.name AS doctor_name FROM laboratory l LEFT JOIN doctors d ON l.doctor_id = d.id WHERE l.patient_id = ? ORDER BY l.test_date DESC");
$stmt->execute([$id]);
$labs = $stmt->fetchAll();

$pageTitle = 'Patient History';
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="page-title mb-1">Patient Medical History</h3>
    <a href="patients.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<!-- ===================== PATIENT PROFILE CARD ===================== -->
<div class="card mb-4">
    <div class="card-body p-4">
        <div class="row align-items-center">
            <div class="col-md-2 text-center mb-3 mb-md-0">
                <img src="<?php echo BASE_URL; ?>uploads/profile_images/<?php echo sanitize($patient['profile_image']); ?>" class="rounded-circle" style="width:100px;height:100px;object-fit:cover;" onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.png'" alt="">
            </div>
            <div class="col-md-10">
                <div class="row g-3">
                    <div class="col-md-4"><strong>Name:</strong><br><?php echo sanitize($patient['name']); ?></div>
                    <div class="col-md-4"><strong>Email:</strong><br><?php echo sanitize($patient['email']); ?></div>
                    <div class="col-md-4"><strong>Phone:</strong><br><?php echo sanitize($patient['phone'] ?: '-'); ?></div>
                    <div class="col-md-4"><strong>Gender:</strong><br><?php echo sanitize($patient['gender']); ?></div>
                    <div class="col-md-4"><strong>Date of Birth:</strong><br><?php echo formatDate($patient['dob']); ?></div>
                    <div class="col-md-4"><strong>Blood Group:</strong><br><?php echo sanitize($patient['blood_group'] ?: '-'); ?></div>
                    <div class="col-md-8"><strong>Address:</strong><br><?php echo sanitize($patient['address'] ?: '-'); ?></div>
                    <div class="col-md-4"><strong>Status:</strong><br><span class="badge-status badge-<?php echo $patient['status']; ?>"><?php echo ucfirst($patient['status']); ?></span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ===================== TABS: APPOINTMENTS / BILLS / LABS ===================== -->
<ul class="nav nav-tabs mb-3" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabAppointments">Appointments (<?php echo count($appointments); ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabBills">Bills (<?php echo count($bills); ?>)</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabLabs">Lab Reports (<?php echo count($labs); ?>)</button></li>
</ul>

<div class="tab-content">
    <!-- Appointments Tab -->
    <div class="tab-pane fade show active" id="tabAppointments">
        <div class="card table-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Time</th><th>Doctor</th><th>Reason</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if (empty($appointments)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No appointment history.</td></tr>
                        <?php else: foreach ($appointments as $a): ?>
                            <tr>
                                <td><?php echo formatDate($a['appointment_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($a['appointment_time'])); ?></td>
                                <td><?php echo sanitize($a['doctor_name']); ?> <small class="text-muted">(<?php echo sanitize($a['specialization']); ?>)</small></td>
                                <td><?php echo sanitize($a['reason'] ?: '-'); ?></td>
                                <td><span class="badge-status badge-<?php echo $a['status']; ?>"><?php echo ucfirst($a['status']); ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bills Tab -->
    <div class="tab-pane fade" id="tabBills">
        <div class="card table-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Bill Date</th><th>Total Amount</th><th>Payment Status</th><th></th></tr></thead>
                        <tbody>
                        <?php if (empty($bills)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No billing history.</td></tr>
                        <?php else: foreach ($bills as $b): ?>
                            <tr>
                                <td><?php echo formatDate($b['bill_date']); ?></td>
                                <td><?php echo formatCurrency($b['total_amount']); ?></td>
                                <td><span class="badge-status badge-<?php echo $b['payment_status']; ?>"><?php echo ucfirst($b['payment_status']); ?></span></td>
                                <td><a href="billing_view.php?id=<?php echo $b['id']; ?>" class="btn btn-sm btn-outline-primary">View</a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Labs Tab -->
    <div class="tab-pane fade" id="tabLabs">
        <div class="card table-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Test Name</th><th>Date</th><th>Doctor</th><th>Status</th><th>Report</th></tr></thead>
                        <tbody>
                        <?php if (empty($labs)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No lab test history.</td></tr>
                        <?php else: foreach ($labs as $l): ?>
                            <tr>
                                <td><?php echo sanitize($l['test_name']); ?></td>
                                <td><?php echo formatDate($l['test_date']); ?></td>
                                <td><?php echo sanitize($l['doctor_name'] ?: '-'); ?></td>
                                <td><span class="badge-status badge-<?php echo $l['status']==='completed'?'completed':'pending'; ?>"><?php echo ucfirst(str_replace('_',' ',$l['status'])); ?></span></td>
                                <td>
                                    <?php if ($l['report_file']): ?>
                                        <a href="<?php echo BASE_URL; ?>uploads/lab_reports/<?php echo sanitize($l['report_file']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="fas fa-file-download"></i></a>
                                    <?php else: ?>-<?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php';
