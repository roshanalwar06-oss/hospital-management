<?php
/**
 * =====================================================================
 * FILE: forgot_password.php
 * PLACE AT: hospital-management/forgot_password.php (project root)
 * PURPOSE: Optional simplified password reset (no mail server required,
 *          ideal for local XAMPP demo). The user selects their role,
 *          enters the registered email + phone number to verify
 *          identity, then sets a new password directly.
 *
 * NOTE: For a real production system you would email a signed,
 *       expiring reset link instead of verifying via phone number.
 * =====================================================================
 */
require_once __DIR__ . '/config.php';

$errors = [];
$success = false;
$step = 1; // Step 1 = verify identity, Step 2 = set new password
$allowedRoles = ['admin', 'doctor', 'receptionist', 'patient'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $role  = $_POST['role'] ?? '';
        $email = trim($_POST['email'] ?? '');

        if (!in_array($role, $allowedRoles)) {
            $errors[] = 'Please select a valid role.';
        }

        $table = ($role === 'receptionist') ? 'staff' : $role . 's';

        // ----------------- STEP 2: Actually reset the password -----------------
        if (isset($_POST['action']) && $_POST['action'] === 'reset_password' && empty($errors)) {
            $phone       = trim($_POST['phone'] ?? '');
            $newPassword = $_POST['new_password'] ?? '';
            $confirm     = $_POST['confirm_password'] ?? '';

            if (strlen($newPassword) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            }
            if ($newPassword !== $confirm) {
                $errors[] = 'Passwords do not match.';
            }

            if (empty($errors)) {
                if ($role === 'receptionist') {
                    $stmt = $pdo->prepare("SELECT id FROM staff WHERE email = ? AND phone = ? AND role = 'receptionist'");
                } else {
                    $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE email = ? AND phone = ?");
                }
                $stmt->execute([$email, $phone]);
                $user = $stmt->fetch();

                if (!$user) {
                    $errors[] = 'Email and phone number do not match our records.';
                    $step = 1;
                } else {
                    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE {$table} SET password = ? WHERE id = ?");
                    $update->execute([$hashed, $user['id']]);
                    $success = true;
                }
            } else {
                $step = 2; // stay on step 2 to show validation errors
            }

        // ----------------- STEP 1: Verify email exists -----------------
        } elseif (empty($errors)) {
            if ($role === 'receptionist') {
                $stmt = $pdo->prepare("SELECT id FROM staff WHERE email = ? AND role = 'receptionist'");
            } else {
                $stmt = $pdo->prepare("SELECT id FROM {$table} WHERE email = ?");
            }
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $step = 2; // move to password reset step
            } else {
                $errors[] = 'No account found with that email for the selected role.';
            }
        }
    }
}

$pageTitle = 'Forgot Password';
$csrfToken = generateCSRFToken();
$postedRole  = $_POST['role'] ?? 'patient';
$postedEmail = $_POST['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>body{padding-top:0;}</style>
</head>
<body>
<div class="auth-page">
    <div class="auth-card" style="max-width:520px;">
        <div class="auth-form-side">
            <div class="text-center mb-4">
                <div class="brand-icon mx-auto mb-3" style="width:56px;height:56px;font-size:1.5rem;"><i class="fas fa-key"></i></div>
                <h4 class="fw-bold mb-1">Reset Your Password</h4>
                <p class="section-subtitle">Verify your identity to set a new password.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success text-center">
                    <i class="fas fa-check-circle me-2"></i>Password reset successful!<br>
                    <a href="login.php" class="fw-bold">Click here to Login</a>
                </div>
            <?php else: ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
                    </div>
                <?php endif; ?>

                <?php if ($step === 1): ?>
                    <!-- STEP 1: Identify account by role + email -->
                    <form method="POST" action="forgot_password.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                        <div class="mb-3">
                            <label class="form-label">I am a</label>
                            <select name="role" class="form-select" required>
                                <option value="admin" <?php echo $postedRole==='admin'?'selected':''; ?>>Admin</option>
                                <option value="doctor" <?php echo $postedRole==='doctor'?'selected':''; ?>>Doctor</option>
                                <option value="receptionist" <?php echo $postedRole==='receptionist'?'selected':''; ?>>Receptionist</option>
                                <option value="patient" <?php echo $postedRole==='patient'?'selected':''; ?>>Patient</option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Registered Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            <i class="fas fa-search me-2"></i>Verify Account
                        </button>
                    </form>

                <?php else: ?>
                    <!-- STEP 2: Confirm phone + set new password -->
                    <form method="POST" action="forgot_password.php">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="role" value="<?php echo sanitize($postedRole); ?>">
                        <input type="hidden" name="email" value="<?php echo sanitize($postedEmail); ?>">

                        <div class="alert alert-info small"><i class="fas fa-info-circle me-1"></i>Verifying: <strong><?php echo sanitize($postedEmail); ?></strong></div>

                        <div class="mb-3">
                            <label class="form-label">Registered Phone Number</label>
                            <input type="text" name="phone" class="form-control" required placeholder="Confirm the phone number on your account">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            <i class="fas fa-check me-2"></i>Reset Password
                        </button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>

            <p class="text-center mt-4 mb-0">
                <a href="login.php" class="small"><i class="fas fa-arrow-left me-1"></i>Back to Login</a>
            </p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
