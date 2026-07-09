<?php
/**
 * =====================================================================
 * FILE: admin/room_add.php
 * PLACE AT: hospital-management/admin/room_add.php
 * PURPOSE: Add a new room (ICU / General Ward / Private Room)
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $roomNumber = trim($_POST['room_number'] ?? '');
        $roomType   = $_POST['room_type'] ?? 'General Ward';
        $price      = (float)($_POST['price_per_day'] ?? 0);

        if ($roomNumber === '') {
            $errors[] = 'Room number is required.';
        }

        if (empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM rooms WHERE room_number = ?");
            $check->execute([$roomNumber]);
            if ($check->fetch()) {
                $errors[] = 'A room with this number already exists.';
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type, price_per_day, status) VALUES (?, ?, ?, 'available')");
            $stmt->execute([$roomNumber, $roomType, $price]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Room added successfully.'];
            redirect('admin/rooms.php');
        }
    }
}

$pageTitle = 'Add Room';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="page-title mb-1">Add New Room</h3>
    <a href="rooms.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="room_add.php">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Room Number <span class="required-star">*</span></label>
                    <input type="text" name="room_number" class="form-control" required placeholder="e.g. ICU-102">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Room Type</label>
                    <select name="room_type" class="form-select">
                        <option value="ICU">ICU</option>
                        <option value="General Ward">General Ward</option>
                        <option value="Private Room">Private Room</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Price Per Day (₹)</label>
                    <input type="number" step="0.01" min="0" name="price_per_day" class="form-control" value="0">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Room</button>
                <a href="rooms.php" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
