<?php
/**
 * =====================================================================
 * FILE: admin/staff_add.php
 * PLACE AT: hospital-management/admin/staff_add.php
 * PURPOSE: Add a new staff member (Receptionist / Nurse / Employee)
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $name        = trim($_POST['name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $role        = $_POST['role'] ?? 'receptionist';
        $designation = trim($_POST['designation'] ?? '');
        $salary      = (float)($_POST['salary'] ?? 0);
        $joiningDate = $_POST['joining_date'] ?: date('Y-m-d');
        $address     = trim($_POST['address'] ?? '');
        $password    = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            $errors[] = 'Name, email and password are required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }
        if (!in_array($role, ['receptionist', 'nurse', 'employee'])) {
            $errors[] = 'Invalid role selected.';
        }

        if (empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM staff WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $errors[] = 'A staff member with this email already exists.';
            }
        }

        $profileImage = 'default.png';
        if (empty($errors) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $profileImage = 'staff_' . uniqid() . '.' . $ext;
                move_uploaded_file($_FILES['profile_image']['tmp_name'], UPLOAD_PATH . 'profile_images/' . $profileImage);
            } else {
                $errors[] = 'Profile image must be JPG, JPEG, PNG or WEBP.';
            }
        }

        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO staff (name, email, password, phone, role, designation, salary, joining_date, address, profile_image)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed, $phone, $role, $designation, $salary, $joiningDate, $address, $profileImage]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Staff member added successfully.'];
            redirect('admin/staff.php');
        }
    }
}

$pageTitle = 'Add Staff Member';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="page-title mb-1">Add New Staff Member</h3>
    <a href="staff.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="staff_add.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="required-star">*</span></label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address <span class="required-star">*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Role <span class="required-star">*</span></label>
                    <select name="role" class="form-select" required>
                        <option value="receptionist">Receptionist</option>
                        <option value="nurse">Nurse</option>
                        <option value="employee">Employee</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-control" placeholder="e.g. Front Desk">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Salary (₹)</label>
                    <input type="number" step="0.01" min="0" name="salary" class="form-control" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Password <span class="required-star">*</span></label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Profile Image</label>
                    <input type="file" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Staff Member</button>
                <a href="staff.php" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
