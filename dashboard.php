<?php
/**
 * =====================================================================
 * FILE: admin/dashboard.php
 * PLACE AT: hospital-management/admin/dashboard.php
 * PURPOSE: Admin dashboard - statistics cards, today's appointments,
 *          patient/doctor counts, revenue summary
 * =====================================================================
 */
require_once __DIR__ . '/../config.php';
requireRole('admin');

// ---- Total Patients ----
$totalPatients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();

// ---- Total Doctors ----
$totalDoctors = $pdo->query("SELECT COUNT(*) FROM doctors WHERE status = 'active'")->fetchColumn();

// ---- Total Staff ----
$totalStaff = $pdo->query("SELECT COUNT(*) FROM staff WHERE status = 'active'")->fetchColumn();

// ---- Today's Appointments Count ----
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = CURDATE()");
$stmt->execute();
$todayAppointmentsCount = $stmt->fetchColumn();

// ---- Revenue Summary (This Month) ----
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE MONTH(bill_date) = MONTH(CURDATE()) AND YEAR(bill_date) = YEAR(CURDATE())");
$stmt->execute();
$monthlyRevenue = $stmt->fetchColumn();

// ---- Revenue Summary (Today) ----
$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bills WHERE bill_date = CURDATE()");
$stmt->execute();
$todayRevenue = $stmt->fetchColumn();

// ---- Pending Bills Count ----
$pendingBills = $pdo->query("SELECT COUNT(*) FROM bills WHERE payment_status != 'paid'")->fetchColumn();

// ---- Today's Appointments List ----
$stmt = $pdo->prepare("
    SELECT a.id, a.appointment_time, a.status, p.name AS patient_name, d.name AS doctor_name, d.specialization
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.appointment_date = CURDATE()
    ORDER BY a.appointment_time ASC
    LIMIT 10
");
$stmt->execute();
$todayAppointments = $stmt->fetchAll();

// ---- Recent Patients ----
$stmt = $pdo->prepare("SELECT id, name, email, phone, created_at FROM patients ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$recentPatients = $stmt->fetchAll();

// ---- Room Occupancy Summary ----
$stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM rooms GROUP BY status");
$stmt->execute();
$roomStats = $stmt->fetchAll();
$roomOccupied = 0; $roomAvailable = 0;
foreach ($roomStats as $rs) {
    if ($rs['status'] === 'occupied') $roomOccupied = $rs['cnt'];
    if ($rs['status'] === 'available') $roomAvailable = $rs['cnt'];
}

$pageTitle = 'Dashboard';
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h3 class="page-title mb-1">Welcome back, <?php echo sanitize($_SESSION['name']); ?> 👋</h3>
        <p class="section-subtitle mb-0">Here's what's happening in your hospital today.</p>
    </div>
</div>

<!-- ===================== STATISTICS CARDS ===================== -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-blue">
            <i class="fas fa-user-injured stat-icon"></i>
            <div class="stat-value"><?php echo number_format($totalPatients); ?></div>
            <div class="stat-label">Total Patients</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-teal">
            <i class="fas fa-user-md stat-icon"></i>
            <div class="stat-value"><?php echo number_format($totalDoctors); ?></div>
            <div class="stat-label">Active Doctors</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-orange">
            <i class="fas fa-calendar-day stat-icon"></i>
            <div class="stat-value"><?php echo number_format($todayAppointmentsCount); ?></div>
            <div class="stat-label">Today's Appointments</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-purple">
            <i class="fas fa-users-cog stat-icon"></i>
            <div class="stat-value"><?php echo number_format($totalStaff); ?></div>
            <div class="stat-label">Staff Members</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-green">
            <i class="fas fa-coins stat-icon"></i>
            <div class="stat-value"><?php echo formatCurrency($todayRevenue); ?></div>
            <div class="stat-label">Today's Revenue</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-pink">
            <i class="fas fa-chart-line stat-icon"></i>
            <div class="stat-value"><?php echo formatCurrency($monthlyRevenue); ?></div>
            <div class="stat-label">This Month's Revenue</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-orange">
            <i class="fas fa-file-invoice stat-icon"></i>
            <div class="stat-value"><?php echo number_format($pendingBills); ?></div>
            <div class="stat-label">Pending Bills</div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card bg-blue">
            <i class="fas fa-bed stat-icon"></i>
            <div class="stat-value"><?php echo $roomOccupied; ?>/<?php echo $roomOccupied + $roomAvailable; ?></div>
            <div class="stat-label">Rooms Occupied</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- ===================== TODAY'S APPOINTMENTS ===================== -->
    <div class="col-lg-7">
        <div class="card table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-calendar-check text-primary me-2"></i>Today's Appointments</span>
                <a href="appointments.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($todayAppointments)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No appointments scheduled for today.</td></tr>
                            <?php else: ?>
                                <?php foreach ($todayAppointments as $appt): ?>
                                <tr>
                                    <td><?php echo date('h:i A', strtotime($appt['appointment_time'])); ?></td>
                                    <td><?php echo sanitize($appt['patient_name']); ?></td>
                                    <td><?php echo sanitize($appt['doctor_name']); ?> <br><small class="text-muted"><?php echo sanitize($appt['specialization']); ?></small></td>
                                    <td><span class="badge-status badge-<?php echo $appt['status']; ?>"><?php echo ucfirst($appt['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== RECENT PATIENTS ===================== -->
    <div class="col-lg-5">
        <div class="card table-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-user-injured text-primary me-2"></i>Recently Registered Patients</span>
                <a href="patients.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>Name</th><th>Phone</th><th>Joined</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentPatients)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-4">No patients yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentPatients as $p): ?>
                                <tr>
                                    <td><?php echo sanitize($p['name']); ?></td>
                                    <td><?php echo sanitize($p['phone'] ?: '-'); ?></td>
                                    <td><?php echo formatDate($p['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
