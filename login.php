<?php
/**
 * =====================================================================
 * FILE: login.php
 * PLACE AT: hospital-management/login.php (project root)
 * PURPOSE: Unified login for Admin / Doctor / Receptionist / Patient.
 *          The chosen role determines which table is queried.
 * =====================================================================
 */
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    // FIX: admin dashboard lives at project root (dashboard.php),
    // all other roles live under their own folder (role/dashboard.php)
    redirect($_SESSION['role'] === 'admin' ? 'dashboard.php' : $_SESSION['role'] . '/dashboard.php');
    exit; // defensive: make sure execution stops after redirect
}

$errors = [];
$selectedRole = $_GET['role'] ?? ($_POST['role'] ?? 'admin');
$allowedRoles = ['admin', 'doctor', 'receptionist', 'patient'];
if (!in_array($selectedRole, $allowedRoles, true)) {
    $selectedRole = 'admin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---- CSRF check ----
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? '';

        if ($email === '' || $password === '') {
            $errors[] = 'Please enter both email and password.';
        } elseif (!in_array($role, $allowedRoles, true)) {
            $errors[] = 'Invalid role selected.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // FIX: basic server-side email format validation
            $errors[] = 'Please enter a valid email address.';
        } else {
            // Map role -> table name using an explicit whitelist
            // (defense-in-depth so a table name is never built by concatenation)
            $tableMap = [
                'admin'        => 'admins',
                'doctor'       => 'doctors',
                'receptionist' => 'staff',
                'patient'      => 'patients',
            ];
            $table = $tableMap[$role];

            try {
                if ($role === 'receptionist') {
                    // staff table also holds nurses/employees, restrict to receptionist role
                    $stmt = $pdo->prepare("SELECT * FROM staff WHERE email = ? AND role = 'receptionist' LIMIT 1");
                    $stmt->execute([$email]);
                } else {
                    $stmt = $pdo->prepare("SELECT * FROM {$table} WHERE email = ? LIMIT 1");
                    $stmt->execute([$email]);
                }

                $user = $stmt->fetch();


                // FIX: guard against a missing/undefined 'password' column causing a warning
                if ($user && isset($user['password']) && password_verify($password, $user['password'])) {

                    if (isset($user['status']) && $user['status'] !== 'active') {
                        $errors[] = 'Your account has been deactivated. Please contact the administrator.';
                    } else {
                        // FIX: regenerate session ID on privilege change to prevent session fixation
                        session_regenerate_id(true);

                        // Set session variables
                        $_SESSION['user_id']       = $user['id'];
                        $_SESSION['role']          = $role;
                        // FIX: fall back to full_name if the table uses that column instead of name
                        $_SESSION['name']          = $user['name'] ?? ($user['full_name'] ?? '');
                        $_SESSION['email']         = $user['email'];
                        $_SESSION['profile_image'] = $user['profile_image'] ?? 'default.png';
                        // Redirect according to the logged-in role
if ($role === 'admin') {
    redirect('admin/dashboard.php');
} elseif ($role === 'doctor') {
    redirect('doctor/dashboard.php');
} elseif ($role === 'receptionist') {
    redirect('receptionist/dashboard.php');
} elseif ($role === 'patient') {
    redirect('patient/dashboard.php');
}

exit;

                    }
                } else {
                    $errors[] = 'Invalid email or password for the selected role.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Login failed. Please try again later.';
            }
        }
    }
}

$pageTitle = 'Login';
$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>body{padding-top:0;}</style>
</head>
<body>
<div class="auth-page">
    <div class="auth-card">
        <div class="row g-0">
            <div class="col-lg-5 d-none d-lg-flex">
                <div class="auth-side">
                    <div class="brand-icon mb-4" style="width:56px;height:56px;font-size:1.5rem;"><i class="fas fa-hospital"></i></div>
                    <h2 class="fw-bold mb-3"><?php echo SITE_NAME; ?></h2>
                    <p style="opacity:0.9;">Sign in to manage patients, doctors, appointments, billing and more — all from a single, secure dashboard.</p>
                    <ul class="list-unstyled mt-4" style="opacity:0.9;">
                        <li class="mb-2"><i class="fas fa-check-circle me-2"></i>Role-based secure access</li>
                        <li class="mb-2"><i class="fas fa-check-circle me-2"></i>Real-time appointment tracking</li>
                        <li class="mb-2"><i class="fas fa-check-circle me-2"></i>Integrated billing &amp; pharmacy</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="auth-form-side">
                    <h4 class="fw-bold mb-1">Welcome Back</h4>
                    <p class="section-subtitle mb-4">Please select your role and sign in to continue.</p>

                    <!-- Role Tabs -->
                    <ul class="nav nav-pills role-tabs mb-4 gap-2" id="roleTabs">
                        <li class="nav-item flex-fill">
                            <button type="button" class="nav-link w-100 <?php echo $selectedRole==='admin'?'active':''; ?>" data-role="admin"><i class="fas fa-user-shield me-1"></i> Admin</button>
                        </li>
                        <li class="nav-item flex-fill">
                            <button type="button" class="nav-link w-100 <?php echo $selectedRole==='doctor'?'active':''; ?>" data-role="doctor"><i class="fas fa-user-md me-1"></i> Doctor</button>
                        </li>
                        <li class="nav-item flex-fill">
                            <button type="button" class="nav-link w-100 <?php echo $selectedRole==='receptionist'?'active':''; ?>" data-role="receptionist"><i class="fas fa-user-tie me-1"></i> Reception</button>
                        </li>
                        <li class="nav-item flex-fill">
                            <button type="button" class="nav-link w-100 <?php echo $selectedRole==='patient'?'active':''; ?>" data-role="patient"><i class="fas fa-user me-1"></i> Patient</button>
                        </li>
                    </ul>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger auto-dismiss">
                            <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="login.php" autocomplete="off">
                        <input type="hidden" name="csrf_token" value="<?php echo sanitize($csrfToken); ?>">
                        <input type="hidden" name="role" id="roleInput" value="<?php echo sanitize($selectedRole); ?>">

                        <div class="mb-3">
                            <label class="form-label">Email Address <span class="required-star">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-envelope text-muted"></i></span>
                                <input type="email" name="email" class="form-control" placeholder="you@hospital.com" required value="<?php echo isset($_POST['email']) ? sanitize($_POST['email']) : ''; ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password <span class="required-star">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-lock text-muted"></i></span>
                                <input type="password" name="password" id="passwordField" class="form-control" placeholder="••••••••" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" data-target="#passwordField"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember">
                                <label class="form-check-label small" for="remember">Remember me</label>
                            </div>
                            <a href="forgot_password.php" class="small">Forgot Password?</a>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            <i class="fas fa-sign-in-alt me-2"></i>Sign In
                        </button>
                    </form>

                    <p class="text-center mt-4 mb-0 small text-muted">
                        Don't have an account? <a href="register.php" class="fw-bold">Register as Patient</a>
                    </p>
                    <p class="text-center mt-2 mb-0">
                        <a href="index.php" class="small"><i class="fas fa-arrow-left me-1"></i>Back to Home</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/script.js"></script>
<script>
    // Role tab switcher - updates hidden input used by the login form
    document.querySelectorAll('#roleTabs .nav-link').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#roleTabs .nav-link').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('roleInput').value = this.getAttribute('data-role');
        });
    });
</script>
</body>
</html>