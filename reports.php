<?php
/**
 * =====================================================================
 * FILE: admin/reports.php
 * PLACE AT: hospital-management/admin/reports.php
 * PURPOSE: Daily / Monthly / Revenue / Patient reports with date filters
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$reportType = $_GET['type'] ?? 'daily';
$dateFrom   = $_GET['date_from'] ?? date('Y-m-01');
$dateTo     = $_GET['date_to'] ?? date('Y-m-d');

// ---- Log this report generation (optional history) ----
if (isset($_GET['generate'])) {
    $stmt = $pdo->prepare("INSERT INTO reports (report_type, generated_by, date_from, date_to) VALUES (?, ?, ?, ?)");
    $stmt->execute([$reportType, $_SESSION['user_id'], $dateFrom, $dateTo]);
}

$data = [];

if ($reportType === 'daily') {
    // Today's summary
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
    $stmt->execute();
    $data['appointments_today'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE DATE(created_at) = CURDATE()");
    $stmt->execute();
    $data['new_patients_today'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE bill_date = CURDATE()");
    $stmt->execute();
    $data['revenue_today'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT a.appointment_time, p.name AS patient_name, d.name AS doctor_name, a.status
        FROM appointments a JOIN patients p ON a.patient_id=p.id JOIN doctors d ON a.doctor_id=d.id
        WHERE a.appointment_date = CURDATE() ORDER BY a.appointment_time ASC
    ");
    $stmt->execute();
    $data['appointment_list'] = $stmt->fetchAll();

} elseif ($reportType === 'monthly') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE MONTH(appointment_date)=MONTH(CURDATE()) AND YEAR(appointment_date)=YEAR(CURDATE())");
    $stmt->execute();
    $data['appointments_month'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())");
    $stmt->execute();
    $data['new_patients_month'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE MONTH(bill_date)=MONTH(CURDATE()) AND YEAR(bill_date)=YEAR(CURDATE())");
    $stmt->execute();
    $data['revenue_month'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT DATE(bill_date) as d, COALESCE(SUM(total_amount),0) as total
        FROM bills WHERE MONTH(bill_date)=MONTH(CURDATE()) AND YEAR(bill_date)=YEAR(CURDATE())
        GROUP BY DATE(bill_date) ORDER BY d ASC
    ");
    $stmt->execute();
    $data['daily_breakdown'] = $stmt->fetchAll();

} elseif ($reportType === 'revenue') {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE bill_date BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateTo]);
    $data['total_revenue'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE payment_status='paid' AND bill_date BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateTo]);
    $data['collected_revenue'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE payment_status!='paid' AND bill_date BETWEEN ? AND ?");
    $stmt->execute([$dateFrom, $dateTo]);
    $data['pending_revenue'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT b.*, p.name AS patient_name FROM bills b JOIN patients p ON b.patient_id = p.id
        WHERE b.bill_date BETWEEN ? AND ? ORDER BY b.bill_date DESC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $data['bill_list'] = $stmt->fetchAll();

} elseif ($reportType === 'patient') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients WHERE created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)");
    $stmt->execute([$dateFrom, $dateTo]);
    $data['new_patients'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM patients");
    $stmt->execute();
    $data['total_patients'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT gender, COUNT(*) as cnt FROM patients GROUP BY gender");
    $stmt->execute();
    $data['gender_breakdown'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT * FROM patients WHERE created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY) ORDER BY created_at DESC");
    $stmt->execute([$dateFrom, $dateTo]);
    $data['patient_list'] = $stmt->fetchAll();
}

$pageTitle = 'Reports';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h3 class="page-title mb-1">Reports &amp; Analytics</h3>
        <p class="section-subtitle mb-0">Generate daily, monthly, revenue, and patient reports.</p>
    </div>
    <button onclick="window.print()" class="btn btn-outline-primary"><i class="fas fa-print me-1"></i> Print Report</button>
</div>

<!-- ===================== REPORT TYPE TABS ===================== -->
<ul class="nav nav-pills mb-4 gap-2 no-print">
    <li><a class="btn <?php echo $reportType==='daily'?'btn-primary':'btn-outline-primary'; ?>" href="?type=daily">Daily Report</a></li>
    <li><a class="btn <?php echo $reportType==='monthly'?'btn-primary':'btn-outline-primary'; ?>" href="?type=monthly">Monthly Report</a></li>
    <li><a class="btn <?php echo $reportType==='revenue'?'btn-primary':'btn-outline-primary'; ?>" href="?type=revenue">Revenue Report</a></li>
    <li><a class="btn <?php echo $reportType==='patient'?'btn-primary':'btn-outline-primary'; ?>" href="?type=patient">Patient Report</a></li>
</ul>

<?php if (in_array($reportType, ['revenue','patient'])): ?>
<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="type" value="<?php echo sanitize($reportType); ?>">
            <div class="col-md-4">
                <label class="form-label small">From Date</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo sanitize($dateFrom); ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label small">To Date</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo sanitize($dateTo); ?>">
            </div>
            <div class="col-md-4">
                <button class="btn btn-primary w-100"><i class="fas fa-chart-line me-1"></i> Generate Report</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<h4 class="mb-3"><?php echo ucfirst($reportType); ?> Report — <?php echo date('d M Y'); ?></h4>

<?php if ($reportType === 'daily'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="stat-card bg-blue"><i class="fas fa-calendar-day stat-icon"></i><div class="stat-value"><?php echo $data['appointments_today']; ?></div><div class="stat-label">Appointments Today</div></div></div>
        <div class="col-md-4"><div class="stat-card bg-teal"><i class="fas fa-user-plus stat-icon"></i><div class="stat-value"><?php echo $data['new_patients_today']; ?></div><div class="stat-label">New Patients Today</div></div></div>
        <div class="col-md-4"><div class="stat-card bg-green"><i class="fas fa-coins stat-icon"></i><div class="stat-value"><?php echo formatCurrency($data['revenue_today']); ?></div><div class="stat-label">Revenue Today</div></div></div>
    </div>
    <div class="card table-card">
        <div class="card-header">Today's Appointment Detail</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($data['appointment_list'])): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">No data.</td></tr>
                <?php else: foreach ($data['appointment_list'] as $row): ?>
                    <tr><td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td><td><?php echo sanitize($row['patient_name']); ?></td><td><?php echo sanitize($row['doctor_name']); ?></td><td><span class="badge-status badge-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td></tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($reportType === 'monthly'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="stat-card bg-blue"><i class="fas fa-calendar-alt stat-icon"></i><div class="stat-value"><?php echo $data['appointments_month']; ?></div><div class="stat-label">Appointments This Month</div></div></div>
        <div class="col-md-4"><div class="stat-card bg-teal"><i class="fas fa-user-plus stat-icon"></i><div class="stat-value"><?php echo $data['new_patients_month']; ?></div><div class="stat-label">New Patients This Month</div></div></div>
        <div class="col-md-4"><div class="stat-card bg-green"><i class="fas fa-coins stat-icon"></i><div class="stat-value"><?php echo formatCurrency($data['revenue_month']); ?></div><div class="stat-label">Revenue This Month</div></div></div>
    </div>
    <div class="card table-card">
        <div class="card-header">Daily Revenue Breakdown (This Month)</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Date</th><th>Revenue</th></tr></thead>
                <tbody>
                <?php if (empty($data['daily_breakdown'])): ?>
                    <tr><td colspan="2" class="text-center text-muted py-3">No data.</td></tr>
                <?php else: foreach ($data['daily_breakdown'] as $row): ?>
                    <tr><td><?php echo formatDate($row['d']); ?></td><td><?php echo formatCurrency($row['total']); ?></td></tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($reportType === 'revenue'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="stat-card bg-blue"><i class="fas fa-coins stat-icon"></i><div class="stat-value"><?php echo formatCurrency($data['total_revenue']); ?></div><div class="stat-label">Total Billed</div></div></div>
        <div class="col-md-4"><div class="stat-card bg-green"><i class="fas fa-check-circle stat-icon"></i><div class="stat-value"><?php echo formatCurrency($data['collected_revenue']); ?></div><div class="stat-label">Collected</div></div></div>
        <div class="col-md-4"><div class="stat-card bg-orange"><i class="fas fa-hourglass-half stat-icon"></i><div class="stat-value"><?php echo formatCurrency($data['pending_revenue']); ?></div><div class="stat-label">Pending</div></div></div>
    </div>
    <div class="card table-card">
        <div class="card-header">Bills in Selected Range</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Bill #</th><th>Patient</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($data['bill_list'])): ?>
                    <tr><td colspan="5" class="text-center text-muted py-3">No data.</td></tr>
                <?php else: foreach ($data['bill_list'] as $row): ?>
                    <tr><td>#INV-<?php echo str_pad($row['id'],4,'0',STR_PAD_LEFT); ?></td><td><?php echo sanitize($row['patient_name']); ?></td><td><?php echo formatDate($row['bill_date']); ?></td><td><?php echo formatCurrency($row['total_amount']); ?></td><td><span class="badge-status badge-<?php echo $row['payment_status']; ?>"><?php echo ucfirst($row['payment_status']); ?></span></td></tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($reportType === 'patient'): ?>
    <div class="row g-3 mb-4">
        <div class="col-md-4"><div class="stat-card bg-blue"><i class="fas fa-user-plus stat-icon"></i><div class="stat-value"><?php echo $data['new_patients']; ?></div><div class="stat-label">New Patients in Range</div></div></div>
        <div class="col-md-4"><div class="stat-card bg-teal"><i class="fas fa-user-injured stat-icon"></i><div class="stat-value"><?php echo $data['total_patients']; ?></div><div class="stat-label">Total Patients (All Time)</div></div></div>
        <div class="col-md-4">
            <div class="stat-card bg-purple"><i class="fas fa-venus-mars stat-icon"></i>
                <div class="stat-value" style="font-size:1rem;">
                    <?php foreach ($data['gender_breakdown'] as $g): echo sanitize($g['gender']) . ': ' . $g['cnt'] . '  '; endforeach; ?>
                </div>
                <div class="stat-label">Gender Breakdown</div>
            </div>
        </div>
    </div>
    <div class="card table-card">
        <div class="card-header">Patients Registered in Selected Range</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Registered</th></tr></thead>
                <tbody>
                <?php if (empty($data['patient_list'])): ?>
                    <tr><td colspan="4" class="text-center text-muted py-3">No data.</td></tr>
                <?php else: foreach ($data['patient_list'] as $row): ?>
                    <tr><td><?php echo sanitize($row['name']); ?></td><td><?php echo sanitize($row['email']); ?></td><td><?php echo sanitize($row['phone'] ?: '-'); ?></td><td><?php echo formatDate($row['created_at']); ?></td></tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
