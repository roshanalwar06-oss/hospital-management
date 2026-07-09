<?php
/**
 * =====================================================================
 * FILE: admin/patient_edit.php
 * PLACE AT: hospital-management/admin/patient_edit.php
 * PURPOSE: Admin form to edit an existing patient's details
 * =====================================================================
 */
require_once __DIR__ . '/config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('admin/patients.php'); }

$stmt = $pdo->prepare("SELECT * FROM patients WHERE id = ?");
$stmt->execute([$id]);
$patient = $stmt->fetch();

if (!$patient) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Patient not found.'];
    redirect('admin/patients.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $gender   = $_POST['gender'] ?? 'Male';
        $dob      = $_POST['dob'] ?: null;
        $bloodGrp = trim($_POST['blood_group'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        $status   = $_POST['status'] ?? 'active';
        $newPassword = $_POST['password'] ?? '';

        if ($name === '' || $email === '') {
            $errors[] = 'Name and email are required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM patients WHERE email = ? AND id != ?");
            $check->execute([$email, $id]);
            if ($check->fetch()) {
                $errors[] = 'Another patient already uses this email.';
            }
        }

        // ---- Handle profile image upload (optional) ----
        $profileImage = $patient['profile_image'];
        if (empty($errors) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $profileImage = 'patient_' . uniqid() . '.' . $ext;
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
                    $stmt = $pdo->prepare("UPDATE patients SET name=?, email=?, password=?, phone=?, gender=?, dob=?, blood_group=?, address=?, status=?, profile_image=? WHERE id=?");
                    $stmt->execute([$name, $email, $hashed, $phone, $gender, $dob, $bloodGrp, $address, $status, $profileImage, $id]);
                }
            } else {
                $stmt = $pdo->prepare("UPDATE patients SET name=?, email=?, phone=?, gender=?, dob=?, blood_group=?, address=?, status=?, profile_image=? WHERE id=?");
                $stmt->execute([$name, $email, $phone, $gender, $dob, $bloodGrp, $address, $status, $profileImage, $id]);
            }

            if (empty($errors)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Patient updated successfully.'];
                redirect('admin/patients.php');
            }
        }
    }
    // Re-fill form with posted data on error
    $patient = array_merge($patient, $_POST);
}

$pageTitle = 'Edit Patient';
$csrfToken = generateCSRFToken();
include 'includes/header.php';
include 'includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="page-title mb-1">Edit Patient</h3>
        <p class="section-subtitle mb-0">Update <?php echo sanitize($patient['name']); ?>'s information.</p>
    </div>
    <a href="patients.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="patient_edit.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <div class="text-center mb-4">
                <img src="<?php echo BASE_URL; ?>uploads/profile_images/<?php echo sanitize($patient['profile_image']); ?>" class="rounded-circle" style="width:90px;height:90px;object-fit:cover;" onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.png'" alt="">
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="required-star">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?php echo sanitize($patient['name']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address <span class="required-star">*</span></label>
                    <input type="email" name="email" class="form-control" required value="<?php echo sanitize($patient['email']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo sanitize($patient['phone'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="Male" <?php echo $patient['gender']==='Male'?'selected':''; ?>>Male</option>
                        <option value="Female" <?php echo $patient['gender']==='Female'?'selected':''; ?>>Female</option>
                        <option value="Other" <?php echo $patient['gender']==='Other'?'selected':''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" class="form-control" value="<?php echo sanitize($patient['dob'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select">
                        <option value="">Select</option>
                        <?php foreach (['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg): ?>
                            <option <?php echo $patient['blood_group']===$bg?'selected':''; ?>><?php echo $bg; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo sanitize($patient['address'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo $patient['status']==='active'?'selected':''; ?>>Active</option>
                        <option value="inactive" <?php echo $patient['status']==='inactive'?'selected':''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" minlength="6" placeholder="Leave blank to keep current">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Update Profile Image</label>
                    <input type="file" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update Patient</button>
                <a href="patients.php" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php';
