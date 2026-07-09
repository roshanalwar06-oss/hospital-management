<?php
/**
 * =====================================================================
 * FILE: admin/lab_upload.php
 * PLACE AT: hospital-management/admin/lab_upload.php
 * PURPOSE: Update lab test status, result summary, and upload the
 *          report file (PDF/image)
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('admin/laboratory.php'); }

$stmt = $pdo->prepare("SELECT l.*, p.name AS patient_name FROM laboratory l JOIN patients p ON l.patient_id = p.id WHERE l.id = ?");
$stmt->execute([$id]);
$lab = $stmt->fetch();

if (!$lab) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Lab test record not found.'];
    redirect('admin/laboratory.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $status  = $_POST['status'] ?? 'pending';
        $summary = trim($_POST['result_summary'] ?? '');
        $price   = (float)($_POST['price'] ?? 0);
        $reportFile = $lab['report_file'];

        // ---- Handle report file upload ----
        if (isset($_FILES['report_file']) && $_FILES['report_file']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
            $ext = strtolower(pathinfo($_FILES['report_file']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $reportFile = 'lab_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['report_file']['tmp_name'], UPLOAD_PATH . 'lab_reports/' . $reportFile);
            } else {
                $errors[] = 'Report file must be PDF, JPG, JPEG or PNG.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE laboratory SET status=?, result_summary=?, price=?, report_file=? WHERE id=?");
            $stmt->execute([$status, $summary, $price, $reportFile, $id]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Lab test record updated successfully.'];
            redirect('admin/laboratory.php');
        }
        $lab = array_merge($lab, $_POST);
    }
}

$pageTitle = 'Update Lab Test';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="page-title mb-1">Update Lab Test / Upload Report</h3>
    <a href="laboratory.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <div class="mb-4">
            <strong>Patient:</strong> <?php echo sanitize($lab['patient_name']); ?><br>
            <strong>Test:</strong> <?php echo sanitize($lab['test_name']); ?><br>
            <strong>Test Date:</strong> <?php echo formatDate($lab['test_date']); ?>
        </div>

        <form method="POST" action="lab_upload.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="pending" <?php echo $lab['status']==='pending'?'selected':''; ?>>Pending</option>
                        <option value="in_progress" <?php echo $lab['status']==='in_progress'?'selected':''; ?>>In Progress</option>
                        <option value="completed" <?php echo $lab['status']==='completed'?'selected':''; ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Price (₹)</label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" value="<?php echo (float)$lab['price']; ?>">
                </div>
                <div class="col-12">
                    <label class="form-label">Result Summary</label>
                    <textarea name="result_summary" class="form-control" rows="4" placeholder="Enter test findings/summary..."><?php echo sanitize($lab['result_summary'] ?? ''); ?></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Upload Report File (PDF/Image)</label>
                    <input type="file" name="report_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    <?php if (!empty($lab['report_file'])): ?>
                        <small class="text-muted">Current file: <a href="<?php echo BASE_URL; ?>uploads/lab_reports/<?php echo sanitize($lab['report_file']); ?>" target="_blank"><?php echo sanitize($lab['report_file']); ?></a></small>
                    <?php endif; ?>
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Changes</button>
                <a href="laboratory.php" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
