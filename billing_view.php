<?php
/**
 * =====================================================================
 * FILE: admin/billing_view.php
 * PLACE AT: hospital-management/admin/billing_view.php
 * PURPOSE: View and print a single bill/invoice
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('admin/billing.php'); }

$stmt = $pdo->prepare("SELECT b.*, p.name AS patient_name, p.email AS patient_email, p.phone AS patient_phone, p.address AS patient_address
                        FROM bills b JOIN patients p ON b.patient_id = p.id WHERE b.id = ?");
$stmt->execute([$id]);
$bill = $stmt->fetch();

if (!$bill) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Bill not found.'];
    redirect('admin/billing.php');
}

// ---- Update payment status manually ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $newStatus = $_POST['payment_status'] ?? $bill['payment_status'];
    $stmt = $pdo->prepare("UPDATE bills SET payment_status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $id]);

    if (!empty($_POST['amount_paid_now']) && (float)$_POST['amount_paid_now'] > 0) {
        $payStmt = $pdo->prepare("INSERT INTO payments (bill_id, payment_date, amount_paid, payment_mode, transaction_id) VALUES (?, CURDATE(), ?, ?, ?)");
        $payStmt->execute([$id, (float)$_POST['amount_paid_now'], $_POST['payment_mode'] ?? 'Cash', trim($_POST['transaction_id'] ?? '')]);
    }

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Payment status updated.'];
    redirect('admin/billing_view.php?id=' . $id);
}

$payments = $pdo->prepare("SELECT * FROM payments WHERE bill_id = ? ORDER BY payment_date DESC");
$payments->execute([$id]);
$paymentHistory = $payments->fetchAll();
$totalPaid = array_sum(array_column($paymentHistory, 'amount_paid'));
$balanceDue = max(0, $bill['total_amount'] - $totalPaid);

$pageTitle = 'View Bill';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h3 class="page-title mb-1">Invoice #INV-<?php echo str_pad($bill['id'], 4, '0', STR_PAD_LEFT); ?></h3>
    <div class="d-flex gap-2">
        <button onclick="printSection()" class="btn btn-primary"><i class="fas fa-print me-1"></i> Print Bill</button>
        <a href="billing.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body p-4 p-md-5" id="printArea">
        <div class="d-flex justify-content-between align-items-start flex-wrap mb-4 pb-4 border-bottom">
            <div>
                <h4 class="fw-bold text-primary mb-1"><i class="fas fa-hospital me-2"></i><?php echo SITE_NAME; ?></h4>
                <p class="text-muted small mb-0">123 Health Street, Medical City<br>Phone: +91 98765 43210 | Email: info@hospital.com</p>
            </div>
            <div class="text-md-end mt-3 mt-md-0">
                <h5 class="fw-bold">INVOICE</h5>
                <p class="mb-0 small"><strong>Invoice #:</strong> INV-<?php echo str_pad($bill['id'], 4, '0', STR_PAD_LEFT); ?></p>
                <p class="mb-0 small"><strong>Date:</strong> <?php echo formatDate($bill['bill_date']); ?></p>
                <span class="badge-status badge-<?php echo $bill['payment_status']; ?>"><?php echo ucfirst($bill['payment_status']); ?></span>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="fw-bold text-muted">BILLED TO</h6>
                <p class="mb-0"><strong><?php echo sanitize($bill['patient_name']); ?></strong></p>
                <p class="mb-0 small text-muted"><?php echo sanitize($bill['patient_email']); ?></p>
                <p class="mb-0 small text-muted"><?php echo sanitize($bill['patient_phone'] ?: '-'); ?></p>
                <p class="mb-0 small text-muted"><?php echo sanitize($bill['patient_address'] ?: ''); ?></p>
            </div>
        </div>

        <div class="table-responsive mb-4">
            <table class="table table-bordered">
                <thead class="table-light"><tr><th>Description</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                    <tr><td>Consultation Charge</td><td class="text-end"><?php echo formatCurrency($bill['consultation_charge']); ?></td></tr>
                    <tr><td>Room Charge</td><td class="text-end"><?php echo formatCurrency($bill['room_charge']); ?></td></tr>
                    <tr><td>Medicine Charge</td><td class="text-end"><?php echo formatCurrency($bill['medicine_charge']); ?></td></tr>
                    <tr><td>Lab Charge</td><td class="text-end"><?php echo formatCurrency($bill['lab_charge']); ?></td></tr>
                    <tr><td>Other Charge</td><td class="text-end"><?php echo formatCurrency($bill['other_charge']); ?></td></tr>
                    <tr><td>Discount</td><td class="text-end">- <?php echo formatCurrency($bill['discount']); ?></td></tr>
                    <tr><td>Tax</td><td class="text-end">+ <?php echo formatCurrency($bill['tax']); ?></td></tr>
                    <tr class="table-light"><td class="fw-bold">TOTAL AMOUNT</td><td class="text-end fw-bold fs-5"><?php echo formatCurrency($bill['total_amount']); ?></td></tr>
                    <tr><td>Amount Paid</td><td class="text-end text-success"><?php echo formatCurrency($totalPaid); ?></td></tr>
                    <tr><td class="fw-bold">Balance Due</td><td class="text-end fw-bold text-danger"><?php echo formatCurrency($balanceDue); ?></td></tr>
                </tbody>
            </table>
        </div>

        <p class="text-center text-muted small mb-0">Thank you for choosing <?php echo SITE_NAME; ?>. Get well soon!</p>
    </div>
</div>

<!-- ===================== PAYMENT MANAGEMENT (not printed) ===================== -->
<div class="row g-3 no-print">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-money-check-alt me-2 text-primary"></i>Update Payment</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <div class="mb-3">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select">
                            <option value="unpaid" <?php echo $bill['payment_status']==='unpaid'?'selected':''; ?>>Unpaid</option>
                            <option value="partial" <?php echo $bill['payment_status']==='partial'?'selected':''; ?>>Partial</option>
                            <option value="paid" <?php echo $bill['payment_status']==='paid'?'selected':''; ?>>Paid</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Record New Payment (optional)</label>
                        <input type="number" step="0.01" min="0" name="amount_paid_now" class="form-control" placeholder="Amount received">
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <select name="payment_mode" class="form-select">
                                <option>Cash</option><option>Card</option><option>UPI</option><option>Insurance</option><option>Net Banking</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" name="transaction_id" class="form-control" placeholder="Transaction ID (optional)">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-history me-2 text-primary"></i>Payment History</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Date</th><th>Amount</th><th>Mode</th></tr></thead>
                        <tbody>
                        <?php if (empty($paymentHistory)): ?>
                            <tr><td colspan="3" class="text-center text-muted py-3">No payments recorded yet.</td></tr>
                        <?php else: foreach ($paymentHistory as $pay): ?>
                            <tr>
                                <td><?php echo formatDate($pay['payment_date']); ?></td>
                                <td><?php echo formatCurrency($pay['amount_paid']); ?></td>
                                <td><?php echo sanitize($pay['payment_mode']); ?></td>
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
