<?php
/**
 * =====================================================================
 * FILE: admin/profile.php
 * PLACE AT: hospital-management/admin/profile.php
 * PURPOSE: Admin's own profile update + change password
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$id = (int)$_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
$stmt->execute([$id]);
$admin = $stmt->fetch();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $formType = $_POST['form_type'] ?? '';

        // ---- Update Profile Info ----
        if ($formType === 'profile') {
            $name  = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');

            if ($name === '' || $email === '') {
                $errors[] = 'Name and email are required.';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Please enter a valid email address.';
            }

            if (empty($errors)) {
                $check = $pdo->prepare("SELECT id FROM admins WHERE email = ? AND id != ?");
                $check->execute([$email, $id]);
                if ($check->fetch()) {
                    $errors[] = 'Another account already uses this email.';
                }
            }

            $profileImage = $admin['profile_image'];
            if (empty($errors) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['jpg', 'jpeg', 'png', 'webp'];
                $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $profileImage = 'admin_' . uniqid() . '.' . $ext;
                    move_uploaded_file($_FILES['profile_image']['tmp_name'], UPLOAD_PATH . 'profile_images/' . $profileImage);
                } else {
                    $errors[] = 'Profile image must be JPG, JPEG, PNG or WEBP.';
                }
            }

            if (empty($errors)) {
                $stmt = $pdo->prepare("UPDATE admins SET name=?, email=?, phone=?, profile_image=? WHERE id=?");
                $stmt->execute([$name, $email, $phone, $profileImage, $id]);

                $_SESSION['name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['profile_image'] = $profileImage;

                $success = 'Profile updated successfully.';
                $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
                $stmt->execute([$id]);
                $admin = $stmt->fetch();
            }

        // ---- Change Password ----
        } elseif ($formType === 'password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword     = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (!password_verify($currentPassword, $admin['password'])) {
                $errors[] = 'Current password is incorrect.';
            }
            if (strlen($newPassword) < 6) {
                $errors[] = 'New password must be at least 6 characters.';
            }
            if ($newPassword !== $confirmPassword) {
                $errors[] = 'New password and confirmation do not match.';
            }

            if (empty($errors)) {
                $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                $stmt->execute([$hashed, $id]);
                $success = 'Password changed successfully.';
            }
        }
    }
}

$pageTitle = 'My Profile';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="mb-4">
    <h3 class="page-title mb-1">My Profile</h3>
    <p class="section-subtitle mb-0">Manage your account information and password.</p>
</div>

<?php if ($success): ?><div class="alert alert-success auto-dismiss"><?php echo sanitize($success); ?></div><?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-user-cog me-2 text-primary"></i>Update Profile</div>
            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="form_type" value="profile">

                    <div class="text-center mb-4">
                        <img src="<?php echo BASE_URL; ?>uploads/profile_images/<?php echo sanitize($admin['profile_image']); ?>" class="rounded-circle" style="width:100px;height:100px;object-fit:cover;" onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.png'" alt="">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required value="<?php echo sanitize($admin['name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required value="<?php echo sanitize($admin['email']); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control" value="<?php echo sanitize($admin['phone'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Profile Image</label>
                        <input type="file" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i> Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="fas fa-lock me-2 text-primary"></i>Change Password</div>
            <div class="card-body p-4">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="form_type" value="password">

                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="6">
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-key me-1"></i> Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
