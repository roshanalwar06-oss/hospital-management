<?php
/**
 * =====================================================================
 * FILE: admin/medicine_add.php
 * PLACE AT: hospital-management/admin/medicine_add.php
 * PURPOSE: Add a new medicine to the pharmacy inventory
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

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
            $stmt = $pdo->prepare("INSERT INTO medicines (name, category, manufacturer, price, stock_quantity, unit, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $category, $manufacturer, $price, $stock, $unit, $expiry]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Medicine added successfully.'];
            redirect('admin/pharmacy.php');
        }
    }
}

$pageTitle = 'Add Medicine';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="page-title mb-1">Add New Medicine</h3>
    <a href="pharmacy.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="medicine_add.php">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Medicine Name <span class="required-star">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-control" placeholder="e.g. Analgesic, Antibiotic">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Manufacturer</label>
                    <input type="text" name="manufacturer" class="form-control">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Unit</label>
                    <select name="unit" class="form-select">
                        <option>Tablet</option><option>Capsule</option><option>Syrup</option>
                        <option>Injection</option><option>Ointment</option><option>Bottle</option><option>Strip</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Price (₹) <span class="required-star">*</span></label>
                    <input type="number" step="0.01" min="0" name="price" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Stock Quantity <span class="required-star">*</span></label>
                    <input type="number" min="0" name="stock_quantity" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expiry Date</label>
                    <input type="date" name="expiry_date" class="form-control">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Medicine</button>
                <a href="pharmacy.php" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
