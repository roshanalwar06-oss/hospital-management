<?php
/**
 * =====================================================================
 * FILE: admin/appointment_add.php
 * PLACE AT: hospital-management/admin/appointment_add.php
 * PURPOSE: Admin/receptionist-style form to book a new appointment
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$errors = [];

$patients = $pdo->query("SELECT id, name, phone FROM patients WHERE status='active' ORDER BY name ASC")->fetchAll();
$doctors  = $pdo->query("SELECT id, name, specialization, schedule_days, schedule_time FROM doctors WHERE status='active' ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $patientId = (int)($_POST['patient_id'] ?? 0);
        $doctorId  = (int)($_POST['doctor_id'] ?? 0);
        $date      = $_POST['appointment_date'] ?? '';
        $time      = $_POST['appointment_time'] ?? '';
        $reason    = trim($_POST['reason'] ?? '');

        if ($patientId <= 0 || $doctorId <= 0 || $date === '' || $time === '') {
            $errors[] = 'Patient, Doctor, Date and Time are all required.';
        }

        if (empty($errors) && strtotime($date) < strtotime(date('Y-m-d'))) {
            $errors[] = 'Appointment date cannot be in the past.';
        }

        // Check for double-booking of the same doctor at the same date/time
        if (empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status NOT IN ('cancelled')");
            $check->execute([$doctorId, $date, $time]);
            if ($check->fetch()) {
                $errors[] = 'This doctor already has an appointment at the selected date and time. Please choose another slot.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason, status, booked_by)
                                    VALUES (?, ?, ?, ?, ?, 'confirmed', 'admin')");
            $stmt->execute([$patientId, $doctorId, $date, $time, $reason]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Appointment booked successfully.'];
            redirect('admin/appointments.php');
        }
    }
}

$pageTitle = 'Book Appointment';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="page-title mb-1">Book New Appointment</h3>
        <p class="section-subtitle mb-0">Schedule a patient with a doctor.</p>
    </div>
    <a href="appointments.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="appointment_add.php">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Select Patient <span class="required-star">*</span></label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">-- Choose Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo sanitize($p['name']); ?> <?php echo $p['phone'] ? '(' . sanitize($p['phone']) . ')' : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Select Doctor <span class="required-star">*</span></label>
                    <select name="doctor_id" id="doctorSelect" class="form-select" required>
                        <option value="">-- Choose Doctor --</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?php echo $d['id']; ?>" data-days="<?php echo sanitize($d['schedule_days']); ?>" data-time="<?php echo sanitize($d['schedule_time']); ?>">
                                Dr. <?php echo sanitize($d['name']); ?> — <?php echo sanitize($d['specialization']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted" id="doctorScheduleHint"></small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Appointment Date <span class="required-star">*</span></label>
                    <input type="date" name="appointment_date" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Appointment Time <span class="required-star">*</span></label>
                    <input type="time" name="appointment_time" class="form-control" required>
                </div>
                <div class="col-12">
                    <label class="form-label">Reason for Visit</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="e.g. Regular checkup, fever, follow-up..."></textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-calendar-check me-1"></i> Book Appointment</button>
                <a href="appointments.php" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('doctorSelect').addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const days = opt.getAttribute('data-days');
        const time = opt.getAttribute('data-time');
        const hint = document.getElementById('doctorScheduleHint');
        if (days) {
            hint.textContent = 'Available: ' + days + ' | ' + (time || '');
        } else {
            hint.textContent = '';
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
