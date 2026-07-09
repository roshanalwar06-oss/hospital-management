<?php
/**
 * =====================================================================
 * FILE: admin/room_edit.php
 * PLACE AT: hospital-management/admin/room_edit.php
 * PURPOSE: Edit room details and allocate/discharge a patient
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('admin/rooms.php'); }

$stmt = $pdo->prepare("SELECT * FROM rooms WHERE id = ?");
$stmt->execute([$id]);
$room = $stmt->fetch();

if (!$room) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Room not found.'];
    redirect('admin/rooms.php');
}

$patients = $pdo->query("SELECT id, name FROM patients WHERE status='active' ORDER BY name ASC")->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $roomNumber = trim($_POST['room_number'] ?? '');
        $roomType   = $_POST['room_type'] ?? 'General Ward';
        $price      = (float)($_POST['price_per_day'] ?? 0);
        $status     = $_POST['status'] ?? 'available';
        $patientId  = (int)($_POST['patient_id'] ?? 0) ?: null;

        if ($roomNumber === '') {
            $errors[] = 'Room number is required.';
        }

        // If status is "available", clear patient assignment
        if ($status === 'available') {
            $patientId = null;
            $allocatedDate = null;
            $dischargeDate = date('Y-m-d');
        } else {
            $allocatedDate = $room['allocated_date'] ?: date('Y-m-d');
            $dischargeDate = null;
            if ($status === 'occupied' && !$patientId) {
                $errors[] = 'Please select a patient to occupy this room.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE rooms SET room_number=?, room_type=?, price_per_day=?, status=?, patient_id=?, allocated_date=?, discharge_date=? WHERE id=?");
            $stmt->execute([$roomNumber, $roomType, $price, $status, $patientId, $allocatedDate, $dischargeDate, $id]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Room updated successfully.'];
            redirect('admin/rooms.php');
        }
        $room = array_merge($room, $_POST);
    }
}

$pageTitle = 'Edit Room';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="page-title mb-1">Edit Room / Allocate Patient</h3>
    <a href="rooms.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="room_edit.php?id=<?php echo $id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Room Number <span class="required-star">*</span></label>
                    <input type="text" name="room_number" class="form-control" required value="<?php echo sanitize($room['room_number']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Room Type</label>
                    <select name="room_type" class="form-select">
                        <?php foreach (['ICU','General Ward','Private Room'] as $t): ?>
                            <option value="<?php echo $t; ?>" <?php echo $room['room_type']===$t?'selected':''; ?>><?php echo $t; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Price Per Day (₹)</label>
                    <input type="number" step="0.01" min="0" name="price_per_day" class="form-control" value="<?php echo (float)$room['price_per_day']; ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" id="statusSelect" class="form-select">
                        <option value="available" <?php echo $room['status']==='available'?'selected':''; ?>>Available</option>
                        <option value="occupied" <?php echo $room['status']==='occupied'?'selected':''; ?>>Occupied</option>
                        <option value="maintenance" <?php echo $room['status']==='maintenance'?'selected':''; ?>>Under Maintenance</option>
                    </select>
                </div>
                <div class="col-md-6" id="patientField" style="<?php echo $room['status']==='occupied' ? '' : 'display:none;'; ?>">
                    <label class="form-label">Assign Patient</label>
                    <select name="patient_id" class="form-select">
                        <option value="">-- Choose Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo (isset($room['patient_id']) && $room['patient_id']==$p['id'])?'selected':''; ?>><?php echo sanitize($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update Room</button>
                <a href="rooms.php" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById('statusSelect').addEventListener('change', function () {
        document.getElementById('patientField').style.display = (this.value === 'occupied') ? 'block' : 'none';
    });
</script>

<?php include '../includes/footer.php'; ?>
