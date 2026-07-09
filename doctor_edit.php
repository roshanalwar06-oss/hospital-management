<?php
/**
 * =====================================================================
 * FILE: admin/doctor_edit.php
 * PLACE AT: hospital-management/admin/doctor_edit.php
 * PURPOSE: Admin form to edit an existing doctor's details
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { redirect('admin/doctors.php'); }

$stmt = $pdo->prepare("SELECT * FROM doctors WHERE id = ?");
$stmt->execute([$id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Doctor not found.'];
    redirect('admin/doctors.php');
}

$errors = [];
$days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
$currentDays = explode(',', $doctor['schedule_days'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $name           = trim($_POST['name'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $phone          = trim($_POST['phone'] ?? '');
        $gender         = $_POST['gender'] ?? 'Male';
        $specialization = trim($_POST['specialization'] ?? '');
        $qualification  = trim($_POST['qualification'] ?? '');
        $experience     = (int)($_POST['experience_years'] ?? 0);
        $fee            = (float)($_POST['consultation_fee'] ?? 0);
        $scheduleDays   = isset($_POST['schedule_days']) ? implode(',', $_POST['schedule_days']) : '';
        $scheduleTime   = trim($_POST['schedule_time'] ?? '');
        $address        = trim($_POST['address'] ?? '');
        $availability   = $_POST['availability'] ?? 'available';
        $status         = $_POST['status'] ?? 'active';
        $newPassword    = $_POST['password'] ?? '';

        if ($name === '' || $email === '') {
            $errors[] = 'Name and email are required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM doctors WHERE email = ? AND id != ?");
            $check->execute([$email, $id]);
            if ($check->fetch()) {
                $errors[] = 'Another doctor already uses this email.';
            }
        }

        $profileImage = $doctor['profile_image'];
        if (empty($errors) && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed)) {
                $profileImage = 'doctor_' . uniqid() . '.' . $ext;
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
                    $stmt = $pdo->prepare("UPDATE doctors SET name=?, email=?, password=?, phone=?, gender=?, specialization=?, qualification=?, experience_years=?, consultation_fee=?, schedule_days=?, schedule_time=?, address=?, availability=?, status=?, profile_image=? WHERE id=?");
                    $stmt->execute([$name, $email, $hashed, $phone, $gender, $specialization, $qualification, $experience, $fee, $scheduleDays, $scheduleTime, $address, $availability, $status, $profileImage, $id]);
                }
            } else {
                $stmt = $pdo->prepare("UPDATE doctors SET name=?, email=?, phone=?, gender=?, specialization=?, qualification=?, experience_years=?, consultation_fee=?, schedule_days=?, schedule_time=?, address=?, availability=?, status=?, profile_image=? WHERE id=?");
                $stmt->execute([$name, $email, $phone, $gender, $specialization, $qualification, $experience, $fee, $scheduleDays, $scheduleTime, $address, $availability, $status, $profileImage, $id]);
            }

            if (empty($errors)) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Doctor updated successfully.'];
                redirect('admin/doctors.php');
            }
        }
        $doctor = array_merge($doctor, $_POST);
        $currentDays = $_POST['schedule_days'] ?? [];
    }
}

$pageTitle = 'Edit Doctor';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="page-title mb-1">Edit Doctor</h3>
        <p class="section-subtitle mb-0">Update <?php echo sanitize($doctor['name']); ?>'s information.</p>
    </div>
    <a href="doctors.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i> Back to List</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $e) echo '<div>' . sanitize($e) . '</div>'; ?>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body p-4">
        <form method="POST" action="doctor_edit.php?id=<?php echo $id; ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <div class="text-center mb-4">
                <img src="<?php echo BASE_URL; ?>uploads/profile_images/<?php echo sanitize($doctor['profile_image']); ?>" class="rounded-circle" style="width:90px;height:90px;object-fit:cover;" onerror="this.src='<?php echo BASE_URL; ?>assets/images/default.png'" alt="">
            </div>

            <h6 class="fw-bold text-primary mb-3">Basic Information</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="required-star">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?php echo sanitize($doctor['name']); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address <span class="required-star">*</span></label>
                    <input type="email" name="email" class="form-control" required value="<?php echo sanitize($doctor['email']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo sanitize($doctor['phone'] ?? ''); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="Male" <?php echo $doctor['gender']==='Male'?'selected':''; ?>>Male</option>
                        <option value="Female" <?php echo $doctor['gender']==='Female'?'selected':''; ?>>Female</option>
                        <option value="Other" <?php echo $doctor['gender']==='Other'?'selected':''; ?>>Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" minlength="6" placeholder="Leave blank to keep current">
                </div>
            </div>

            <h6 class="fw-bold text-primary mb-3 mt-4">Professional Details</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Specialization</label>
                    <input type="text" name="specialization" class="form-control" value="<?php echo sanitize($doctor['specialization'] ?? ''); ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Qualification</label>
                    <input type="text" name="qualification" class="form-control" value="<?php echo sanitize($doctor['qualification'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Experience (Years)</label>
                    <input type="number" name="experience_years" class="form-control" min="0" value="<?php echo (int)($doctor['experience_years'] ?? 0); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Consultation Fee (₹)</label>
                    <input type="number" step="0.01" name="consultation_fee" class="form-control" min="0" value="<?php echo (float)($doctor['consultation_fee'] ?? 0); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Availability</label>
                    <select name="availability" class="form-select">
                        <option value="available" <?php echo $doctor['availability']==='available'?'selected':''; ?>>Available</option>
                        <option value="unavailable" <?php echo $doctor['availability']==='unavailable'?'selected':''; ?>>Unavailable</option>
                        <option value="on_leave" <?php echo $doctor['availability']==='on_leave'?'selected':''; ?>>On Leave</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo $doctor['status']==='active'?'selected':''; ?>>Active</option>
                        <option value="inactive" <?php echo $doctor['status']==='inactive'?'selected':''; ?>>Inactive</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo sanitize($doctor['address'] ?? ''); ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Update Profile Image</label>
                    <input type="file" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                </div>
            </div>

            <h6 class="fw-bold text-primary mb-3 mt-4">Schedule</h6>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Available Days</label>
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($days as $day): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="schedule_days[]" value="<?php echo $day; ?>" id="day<?php echo $day; ?>" <?php echo in_array($day, $currentDays) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="day<?php echo $day; ?>"><?php echo $day; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Working Hours</label>
                    <input type="text" name="schedule_time" class="form-control" value="<?php echo sanitize($doctor['schedule_time'] ?? ''); ?>">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Update Doctor</button>
                <a href="doctors.php" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
