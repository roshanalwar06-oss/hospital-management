<?php
/**
 * =====================================================================
 * FILE: admin/doctor_schedule.php
 * PLACE AT: hospital-management/admin/doctor_schedule.php
 * PURPOSE: View a doctor's weekly schedule/availability and their
 *          upcoming appointments calendar-style list
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('admin/doctors.php'); }

$stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
$stmt->execute([$id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Doctor not found.'];
    redirect('admin/doctors.php');
}

// Quick availability toggle (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $availability = $_POST['availability'] ?? 'available';
    $stmt = $pdo->prepare("UPDATE doctors SET availability = ? WHERE id = ?");
    $stmt->execute([$availability, $id]);
    $doctor['availability'] = $availability;
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Availability updated.'];
    redirect('admin/doctor_schedule.php?id=' . $id);
}

// Upcoming appointments for this doctor (next 20)
$stmt = $pdo->prepare("
    SELECT a.*, p.name AS patient_name, p.phone AS patient_phone
    FROM appointments a
    JOIN patients p ON a.patient_id = p.id
    WHERE a.doctor_id = ? AND a.appointment_date >= CURDATE() AND a.status != 'cancelled'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
    LIMIT 20
");
$stmt->execute([$id]);
$upcoming = $stmt->fetchAll();

$scheduleDays = !empty($doctor['schedule_days']) ? explode(',', $doctor['schedule_days']) : [];
$allDays = ['Mon'=>'Monday','Tue'=>'Tuesday','Wed'=>'Wednesday','Thu'=>'Thursday','Fri'=>'Friday','Sat'=>'Saturday','Sun'=>'Sunday'];

$pageTitle = 'Doctor Schedule';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="page-title mb-1">Doctor Schedule &amp; Availability</h3>
    <a href="doctors.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<div class="row g-3">
    <!-- Doctor Info + Availability Toggle -->
    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-body text-center p-4">
                <img src="<?php echo BASE_URL; ?>uploads/profile_images/<?php echo sanitize($doctor['profile_image']); ?>" class="rounded-circle mb-3" style="width:90px;height:90px;object-fit:cover;" onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.png'" alt="">
                <h5 class="fw-bold mb-0"><?php echo sanitize($doctor['name']); ?></h5>
                <p class="text-muted mb-3"><?php echo sanitize($doctor['specialization'] ?: 'General'); ?></p>
                <span class="badge-status badge-<?php echo $doctor['availability']==='available'?'available':'occupied'; ?> mb-3 d-inline-block">
                    <?php echo ucfirst(str_replace('_',' ',$doctor['availability'])); ?>
                </span>

                <form method="POST" class="mt-2">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <label class="form-label small">Update Availability</label>
                    <select name="availability" class="form-select mb-2" onchange="this.form.submit()">
                        <option value="available" <?php echo $doctor['availability']==='available'?'selected':''; ?>>Available</option>
                        <option value="unavailable" <?php echo $doctor['availability']==='unavailable'?'selected':''; ?>>Unavailable</option>
                        <option value="on_leave" <?php echo $doctor['availability']==='on_leave'?'selected':''; ?>>On Leave</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="fas fa-calendar-week me-2 text-primary"></i>Weekly Schedule</div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php foreach ($allDays as $short => $full): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <?php echo $full; ?>
                            <?php if (in_array($short, $scheduleDays)): ?>
                                <span class="badge-status badge-available"><?php echo sanitize($doctor['schedule_time'] ?: 'Working'); ?></span>
                            <?php else: ?>
                                <span class="badge-status badge-inactive">Off</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Upcoming Appointments -->
    <div class="col-lg-8">
        <div class="card table-card">
            <div class="card-header"><i class="fas fa-calendar-check me-2 text-primary"></i>Upcoming Appointments</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Contact</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php if (empty($upcoming)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No upcoming appointments.</td></tr>
                        <?php else: foreach ($upcoming as $a): ?>
                            <tr>
                                <td><?php echo formatDate($a['appointment_date']); ?></td>
                                <td><?php echo date('h:i A', strtotime($a['appointment_time'])); ?></td>
                                <td><?php echo sanitize($a['patient_name']); ?></td>
                                <td><?php echo sanitize($a['patient_phone'] ?: '-'); ?></td>
                                <td><span class="badge-status badge-<?php echo $a['status']; ?>"><?php echo ucfirst($a['status']); ?></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
