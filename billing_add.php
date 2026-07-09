<?php
/**
 * =====================================================================
 * FILE: admin/billing_add.php
 * PLACE AT: hospital-management/admin/billing_add.php
 * PURPOSE: Generate a new bill for a patient with itemized charges
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$errors = [];
$patients = $pdo->query("SELECT id, name, phone FROM patients ORDER BY name ASC")->fetchAll();

// Pre-select patient/appointment if passed via query string
$preselectPatientId = (int)($_GET['patient_id'] ?? 0);
$preselectApptId    = (int)($_GET['appointment_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $patientId     = (int)($_POST['patient_id'] ?? 0);
        $appointmentId = (int)($_POST['appointment_id'] ?? 0) ?: null;
        $billDate      = $_POST['bill_date'] ?? date('Y-m-d');
        $consultation  = (float)($_POST['consultation_charge'] ?? 0);
        $room          = (float)($_POST['room_charge'] ?? 0);
        $medicine      = (float)($_POST['medicine_charge'] ?? 0);
        $lab           = (float)($_POST['lab_charge'] ?? 0);
        $other         = (float)($_POST['other_charge'] ?? 0);
        $discount      = (float)($_POST['discount'] ?? 0);
        $tax           = (float)($_POST['tax'] ?? 0);
        $paymentStatus = $_POST['payment_status'] ?? 'unpaid';

        if ($patientId <= 0) {
            $errors[] = 'Please select a patient.';
        }

        $subtotal = $consultation + $room + $medicine + $lab + $other;
        $totalAmount = max(0, $subtotal - $discount + $tax);

        if (empty($errors)) {
            $stmt = $pdo->prepare("INSERT INTO bills (patient_id, appointment_id, bill_date, consultation_charge, room_charge, medicine_charge, lab_charge, other_charge, discount, tax, total_amount, payment_status)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$patientId, $appointmentId, $billDate, $consultation, $room, $medicine, $lab, $other, $discount, $tax, $totalAmount, $paymentStatus]);
            $billId = $pdo->lastInsertId();

            // If marked paid or partial, log a corresponding payment record
            if ($paymentStatus !== 'unpaid') {
                $paidAmount = ($paymentStatus === 'paid') ? $totalAmount : (float)($_POST['amount_paid_now'] ?? 0);
                if ($paidAmount > 0) {
                    $payStmt = $pdo->prepare("INSERT INTO payments (bill_id, payment_date, amount_paid, payment_mode, transaction_id) VALUES (?, ?, ?, ?, ?)");
                    $payStmt->execute([$billId, $billDate, $paidAmount, $_POST['payment_mode'] ?? 'Cash', trim($_POST['transaction_id'] ?? '')]);
                }
            }

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Bill generated successfully.'];
            redirect('admin/billing_view.php?id=' . $billId);
        }
    }
}

$pageTitle = 'Generate Bill';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="page-title mb-1">Generate New Bill</h3>
    <a href="billing.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="billing_add.php" id="billForm">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="appointment_id" value="<?php echo $preselectApptId ?: ''; ?>">

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Patient <span class="required-star">*</span></label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">-- Choose Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo $preselectPatientId==$p['id']?'selected':''; ?>><?php echo sanitize($p['name']); ?> <?php echo $p['phone'] ? '(' . sanitize($p['phone']) . ')' : ''; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Bill Date</label>
                    <input type="date" name="bill_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>

            <h6 class="fw-bold text-primary mb-3">Charges (₹)</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Consultation Charge</label>
                    <input type="number" step="0.01" min="0" name="consultation_charge" class="form-control bill-input" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Room Charge</label>
                    <input type="number" step="0.01" min="0" name="room_charge" class="form-control bill-input" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Medicine Charge</label>
                    <input type="number" step="0.01" min="0" name="medicine_charge" class="form-control bill-input" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Lab Charge</label>
                    <input type="number" step="0.01" min="0" name="lab_charge" class="form-control bill-input" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Other Charge</label>
                    <input type="number" step="0.01" min="0" name="other_charge" class="form-control bill-input" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Discount</label>
                    <input type="number" step="0.01" min="0" name="discount" class="form-control bill-input" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tax</label>
                    <input type="number" step="0.01" min="0" name="tax" class="form-control bill-input" value="0">
                </div>
            </div>

            <div class="alert alert-info d-flex justify-content-between align-items-center">
                <strong>Total Amount:</strong>
                <span class="fs-4 fw-bold" id="totalDisplay">₹0.00</span>
            </div>

            <h6 class="fw-bold text-primary mb-3">Payment</h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Payment Status</label>
                    <select name="payment_status" id="paymentStatus" class="form-select">
                        <option value="unpaid">Unpaid</option>
                        <option value="partial">Partial Payment</option>
                        <option value="paid">Paid in Full</option>
                    </select>
                </div>
                <div class="col-md-4 payment-extra" style="display:none;">
                    <label class="form-label">Amount Paid Now</label>
                    <input type="number" step="0.01" min="0" name="amount_paid_now" class="form-control">
                </div>
                <div class="col-md-4 payment-extra" style="display:none;">
                    <label class="form-label">Payment Mode</label>
                    <select name="payment_mode" class="form-select">
                        <option>Cash</option><option>Card</option><option>UPI</option><option>Insurance</option><option>Net Banking</option>
                    </select>
                </div>
                <div class="col-md-4 payment-extra" style="display:none;">
                    <label class="form-label">Transaction ID (optional)</label>
                    <input type="text" name="transaction_id" class="form-control">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-file-invoice-dollar me-1"></i> Generate Bill</button>
                <a href="billing.php" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
    // Auto-calculate total amount as charges change
    const inputs = document.querySelectorAll('.bill-input');
    const totalDisplay = document.getElementById('totalDisplay');

    function recalcTotal() {
        let consultation = parseFloat(document.querySelector('[name=consultation_charge]').value) || 0;
        let room = parseFloat(document.querySelector('[name=room_charge]').value) || 0;
        let medicine = parseFloat(document.querySelector('[name=medicine_charge]').value) || 0;
        let lab = parseFloat(document.querySelector('[name=lab_charge]').value) || 0;
        let other = parseFloat(document.querySelector('[name=other_charge]').value) || 0;
        let discount = parseFloat(document.querySelector('[name=discount]').value) || 0;
        let tax = parseFloat(document.querySelector('[name=tax]').value) || 0;
        let total = Math.max(0, (consultation + room + medicine + lab + other) - discount + tax);
        totalDisplay.textContent = '₹' + total.toFixed(2);
    }
    inputs.forEach(el => el.addEventListener('input', recalcTotal));

    // Show/hide payment fields based on status
    document.getElementById('paymentStatus').addEventListener('change', function () {
        const show = this.value !== 'unpaid';
        document.querySelectorAll('.payment-extra').forEach(el => el.style.display = show ? 'block' : 'none');
    });
</script>

<?php include '../includes/footer.php'; ?>
