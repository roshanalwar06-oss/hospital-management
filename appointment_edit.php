<?php
/**
 * =====================================================================
 * FILE: admin/appointment_edit.php
 * PLACE AT: hospital-management/admin/appointment_edit.php
 * PURPOSE: Edit, reschedule, and update the status of an appointment
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('admin/appointments.php'); }

$stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = ?");
$stmt->execute([$id]);
$appointment = $stmt->fetch();

if (!$appointment) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Appointment not found.'];
    redirect('admin/appointments.php');
}

$patients = $pdo->query("SELECT id, name, phone FROM patients ORDER BY name ASC")->fetchAll();
$doctors  = $pdo->query("SELECT id, name, specialization FROM doctors ORDER BY name ASC")->fetchAll();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $patientId = (int)($_POST['patient_id'] ?? 0);
        $doctorId  = (int)($_POST['doctor_id'] ?? 0);
        $date      = $_POST['appointment_date'] ?? '';
        $time      = $_POST['appointment_time'] ?? '';
        $reason    = trim($_POST['reason'] ?? '');
        $status    = $_POST['status'] ?? 'pending';

        if ($patientId <= 0 || $doctorId <= 0 || $date === '' || $time === '') {
            $errors[] = 'Patient, Doctor, Date and Time are all required.';
        }

        // Double-booking check (exclude current appointment)
        if (empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status NOT IN ('cancelled') AND id != ?");
            $check->execute([$doctorId, $date, $time, $id]);
            if ($check->fetch()) {
                $errors[] = 'This doctor already has another appointment at the selected date and time.';
            }
        }

        if (empty($errors)) {
            // If date/time changed from original, mark as rescheduled (unless explicitly setting another status)
            $isRescheduled = ($date !== $appointment['appointment_date'] || $time !== $appointment['appointment_time']);
            if ($isRescheduled && $status === $appointment['status']) {
                $status = 'rescheduled';
            }

            $stmt = $pdo->prepare("UPDATE appointments SET patient_id=?, doctor_id=?, appointment_date=?, appointment_time=?, reason=?, status=? WHERE id=?");
            $stmt->execute([$patientId, $doctorId, $date, $time, $reason, $status, $id]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Appointment updated successfully.'];
            redirect('admin/appointments.php');
        }
        $appointment = array_merge($appointment, $_POST);
    }
}

$pageTitle = 'Edit Appointment';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="page-title mb-1">Edit / Reschedule Appointment</h3>
    <a href="appointments.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="appointment_edit.php?id=<?php echo $id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Patient <span class="required-star">*</span></label>
                    <select name="patient_id" class="form-select" required>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $appointment['patient_id']==$p['id']?'selected':''; ?>><?php echo sanitize($p['name']); ?> <?php echo $p['phone'] ? '(' . sanitize($p['phone']) . ')' : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Doctor <span class="required-star">*</span></label>
                    <select name="doctor_id" class="form-select" required>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?php echo $d['id']; ?>" <?php echo $appointment['doctor_id']==$d['id']?'selected':''; ?>>Dr. <?php echo sanitize($d['name']); ?> — <?php echo sanitize($d['specialization']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Appointment Date <span class="required-star">*</span></label>
                    <input type="date" name="appointment_date" class="form-control" required value="<?php echo sanitize($appointment['appointment_date']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Appointment Time <span class="required-star">*</span></label>
                    <input type="time" name="appointment_time" class="form-control" required value="<?php echo sanitize(substr($appointment['appointment_time'],0,5)); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach (['pending','confirmed','completed','cancelled','rescheduled'] as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo $appointment['status']===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Reason for Visit</label>
                    <textarea name="reason" class="form-control" rows="3"><?php echo sanitize($appointment['reason'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update Appointment</button>
                <a href="appointments.php" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
