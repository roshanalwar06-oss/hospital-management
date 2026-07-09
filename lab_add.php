<?php
/**
 * =====================================================================
 * FILE: admin/lab_add.php
 * PLACE AT: hospital-management/admin/lab_add.php
 * PURPOSE: Schedule a new laboratory test for a patient
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$errors = [];
$patients = $pdo->query("SELECT id, name FROM patients ORDER BY name ASC")->fetchAll();
$doctors  = $pdo->query("SELECT id, name FROM doctors WHERE status='active' ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $patientId = (int)($_POST['patient_id'] ?? 0);
        $doctorId  = (int)($_POST['doctor_id'] ?? 0) ?: null;
        $testName  = trim($_POST['test_name'] ?? '');
        $testDate  = $_POST['test_date'] ?? date('Y-m-d');
        $price     = (float)($_POST['price'] ?? 0);

        if ($patientId <= 0 || $testName === '') {
            $errors[] = 'Patient and Test Name are required.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO laboratory (patient_id, doctor_id, test_name, test_date, price, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$patientId, $doctorId, $testName, $testDate, $price]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Lab test scheduled successfully.'];
            redirect('admin/laboratory.php');
        }
    }
}

$pageTitle = 'New Lab Test';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="page-title mb-1">Schedule New Lab Test</h3>
    <a href="laboratory.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="lab_add.php">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Patient <span class="required-star">*</span></label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">-- Choose Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['id']; ?>"><?php echo sanitize($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Referring Doctor</label>
                    <select name="doctor_id" class="form-select">
                        <option value="">-- None --</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?php echo $d['id']; ?>">Dr. <?php echo sanitize($d['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Test Name <span class="required-star">*</span></label>
                    <input type="text" name="test_name" class="form-control" required placeholder="e.g. Complete Blood Count (CBC)">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Test Date</label>
                    <input type="date" name="test_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Price (₹)</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="0">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Schedule Test</button>
                <a href="laboratory.php" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
