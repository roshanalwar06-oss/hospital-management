<?php
/**
 * =====================================================================
 * FILE: admin/medicine_edit.php
 * PLACE AT: hospital-management/admin/medicine_edit.php
 * PURPOSE: Edit medicine details and update stock quantity
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('admin/pharmacy.php'); }

$stmt = $pdo->prepare("SELECT * FROM medicines WHERE id = ?");
$stmt->execute([$id]);
$medicine = $stmt->fetch();

if (!$medicine) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Medicine not found.'];
    redirect('admin/pharmacy.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $name         = trim($_POST['name'] ?? '');
        $category     = trim($_POST['category'] ?? '');
        $manufacturer = trim($_POST['manufacturer'] ?? '');
        $price        = (float)($_POST['price'] ?? 0);
        $stock        = (int)($_POST['stock_quantity'] ?? 0);
        $unit         = trim($_POST['unit'] ?? 'Tablet');
        $expiry       = $_POST['expiry_date'] ?: null;

        if ($name === '') {
            $errors[] = 'Medicine name is required.';
        }
        if ($price < 0 || $stock < 0) {
            $errors[] = 'Price and stock quantity cannot be negative.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE medicines SET name=?, category=?, manufacturer=?, price=?, stock_quantity=?, unit=?, expiry_date=? WHERE id=?");
            $stmt->execute([$name, $category, $manufacturer, $price, $stock, $unit, $expiry, $id]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Medicine updated successfully.'];
            redirect('admin/pharmacy.php');
        }
        $medicine = array_merge($medicine, $_POST);
    }
}

$pageTitle = 'Edit Medicine';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="page-title mb-1">Edit Medicine / Update Stock</h3>
    <a href="pharmacy.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="medicine_edit.php?id=<?php echo $id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Medicine Name <span class="required-star">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?php echo sanitize($medicine['name']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-control" value="<?php echo sanitize($medicine['category'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Manufacturer</label>
                    <input type="text" name="manufacturer" class="form-control" value="<?php echo sanitize($medicine['manufacturer'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Unit</label>
                    <select name="unit" class="form-select">
                        <?php foreach (['Tablet','Capsule','Syrup','Injection','Ointment','Bottle','Strip'] as $u): ?>
                            <option <?php echo $medicine['unit']===$u?'selected':''; ?>><?php echo $u; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Price (₹) <span class="required-star">*</span></label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" required value="<?php echo (float)$medicine['price']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Stock Quantity <span class="required-star">*</span></label>
                    <input type="number" min="0" name="stock_quantity" class="form-control" required value="<?php echo (int)$medicine['stock_quantity']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control" value="<?php echo sanitize($medicine['expiry_date'] ?? ''); ?>">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update Medicine</button>
                <a href="pharmacy.php" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
