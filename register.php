<?php
/**
 * =====================================================================
 * FILE: register.php
 * PLACE AT: hospital-management/register.php (project root)
 * PURPOSE: Public self-registration form for new Patients only.
 *          Admins/Doctors/Staff are created by the Admin from the panel.
 * =====================================================================
 */
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    redirect($_SESSION['role'] . '/dashboard.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please refresh and try again.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $gender   = $_POST['gender'] ?? 'Male';
        $dob      = $_POST['dob'] ?? null;
        $bloodGrp = trim($_POST['blood_group'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        // ---- Validation ----
        if ($name === '' || $email === '' || $password === '') {
            $errors[] = 'Name, email and password are required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        if ($password !== $confirm) {
            $errors[] = 'Password and Confirm Password do not match.';
        }

        // ---- Check duplicate email ----
        if (empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM patients WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $errors[] = 'An account with this email already exists.';
            }
        }

        // ---- Insert new patient ----
        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $dobValue = !empty($dob) ? $dob : null;

            $stmt = $pdo->prepare("INSERT INTO patients (name, email, password, phone, gender, dob, blood_group, address)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashedPassword, $phone, $gender, $dobValue, $bloodGrp, $address]);

            $success = true;
        }
    }
}

$pageTitle = 'Register';
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>body{padding-top:0;}</style>
</head>
<body>
<div class="auth-page">
    <div class="auth-card" style="max-width:750px;">
        <div class="auth-form-side">
            <div class="text-center mb-4">
                <div class="brand-icon mx-auto mb-3" style="width:56px;height:56px;font-size:1.5rem;"><i class="fas fa-user-plus"></i></div>
                <h4 class="fw-bold mb-1">Create Patient Account</h4>
                <p class="section-subtitle">Register to book appointments and manage your health records online.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>Registration successful! You can now
                    <a href="login.php?role=patient" class="fw-bold">login here</a>.
                </div>
            <?php else: ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="register.php" autocomplete="off">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="required-star">*</span></label>
                            <input type="text" name="name" class="form-control" required value="<?php echo isset($_POST['name']) ? sanitize($_POST['name']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email Address <span class="required-star">*</span></label>
                            <input type="email" name="email" class="form-control" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo isset($_POST['phone']) ? sanitize($_POST['phone']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="dob" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Blood Group</label>
                            <select name="blood_group" class="form-select">
                                <option value="">Select</option>
                                <option>A+</option><option>A-</option>
                                <option>B+</option><option>B-</option>
                                <option>O+</option><option>O-</option>
                                <option>AB+</option><option>AB-</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password <span class="required-star">*</span></label>
                            <input type="password" name="password" class="form-control" required minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm Password <span class="required-star">*</span></label>
                            <input type="password" name="confirm_password" class="form-control" required minlength="6">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mt-4">
                        <i class="fas fa-user-plus me-2"></i>Register
                    </button>
                </form>

                <p class="text-center mt-4 mb-0 small text-muted">
                    Already have an account? <a href="login.php?role=patient" class="fw-bold">Login here</a>
                </p>
            <?php endif; ?>
            <p class="text-center mt-2 mb-0">
                <a href="index.php" class="small"><i class="fas fa-arrow-left me-1"></i>Back to Home</a>
            </p>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
