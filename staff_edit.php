<?php
/**
 * =====================================================================
 * FILE: admin/staff_edit.php
 * PLACE AT: hospital-management/admin/staff_edit.php
 * PURPOSE: Edit an existing staff member's details
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('admin/staff.php'); }

$stmt = $pdo->prepare("SELECT * FROM staff WHERE id = ?");
$stmt->execute([$id]);
$staff = $stmt->fetch();

if (!$staff) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Staff member not found.'];
    redirect('admin/staff.php');
}

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
        $joiningDate = $_POST['joining_date'] ?: null;
        $address     = trim($_POST['address'] ?? '');
        $status      = $_POST['status'] ?? 'active';
        $newPassword = $_POST['password'] ?? '';

        if ($name === '' || $email === '') {
            $errors[] = 'Name and email are required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM staff WHERE email = ? AND id != ?");
            $check->execute([$email, $id]);
            if ($check->fetch()) {
                $errors[] = 'Another staff member already uses this email.';
            }
        }

        $profileImage = $staff['profile_image'];
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
            if ($newPassword !== '') {
                if (strlen($newPassword) < 6) {
                    $errors[] = 'New password must be at least 6 characters.';
                } else {
                    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE staff SET name=?, email=?, password=?, phone=?, role=?, designation=?, salary=?, joining_date=?, address=?, status=?, profile_image=? WHERE id=?");
                    $stmt->execute([$name, $email, $hashed, $phone, $role, $designation, $salary, $joiningDate, $address, $status, $profileImage, $id]);
                }
            } else {
                $stmt = $pdo->prepare("UPDATE staff SET name=?, email=?, phone=?, role=?, designation=?, salary=?, joining_date=?, address=?, status=?, profile_image=? WHERE id=?");
                $stmt->execute([$name, $email, $phone, $role, $designation, $salary, $joiningDate, $address, $status, $profileImage, $id]);
            }

            if (empty($errors)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Staff member updated successfully.'];
                redirect('admin/staff.php');
            }
        }
        $staff = array_merge($staff, $_POST);
    }
}

$pageTitle = 'Edit Staff Member';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="page-title mb-1">Edit Staff Member</h3>
    <a href="staff.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="staff_edit.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <div class="text-center mb-4">
                <img src="<?php echo BASE_URL; ?>uploads/profile_images/<?php echo sanitize($staff['profile_image']); ?>" class="rounded-circle" style="width:90px;height:90px;object-fit:cover;" onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.png'" alt="">
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="required-star">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?php echo sanitize($staff['name']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address <span class="required-star">*</span></label>
                    <input type="email" name="email" class="form-control" required value="<?php echo sanitize($staff['email']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo sanitize($staff['phone'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <option value="receptionist" <?php echo $staff['role']==='receptionist'?'selected':''; ?>>Receptionist</option>
                        <option value="nurse" <?php echo $staff['role']==='nurse'?'selected':''; ?>>Nurse</option>
                        <option value="employee" <?php echo $staff['role']==='employee'?'selected':''; ?>>Employee</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Designation</label>
                    <input type="text" name="designation" class="form-control" value="<?php echo sanitize($staff['designation'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Salary (₹)</label>
                    <input type="number" step="0.01" min="0" name="salary" class="form-control" value="<?php echo (float)$staff['salary']; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control" value="<?php echo sanitize($staff['joining_date'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo $staff['status']==='active'?'selected':''; ?>>Active</option>
                        <option value="inactive" <?php echo $staff['status']==='inactive'?'selected':''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo sanitize($staff['address'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" minlength="6" placeholder="Leave blank to keep current">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Update Profile Image</label>
                    <input type="file" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update Staff Member</button>
                <a href="staff.php" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
