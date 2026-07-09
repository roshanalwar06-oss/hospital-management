<?php
/**
 * =====================================================================
 * FILE: admin/doctor_add.php
 * PLACE AT: hospital-management/admin/doctor_add.php
 * PURPOSE: Admin form to add a new doctor record
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$errors = [];
$days = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

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
        $password       = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            $errors[] = 'Name, email and password are required.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters.';
        }

        if (empty($errors)) {
            $check = $pdo->prepare("SELECT id FROM doctors WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $errors[] = 'A doctor with this email already exists.';
            }
        }

        $profileImage = 'default.png';
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
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO doctors (name, email, password, phone, gender, specialization, qualification, experience_years, consultation_fee, schedule_days, schedule_time, address, profile_image)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hashed, $phone, $gender, $specialization, $qualification, $experience, $fee, $scheduleDays, $scheduleTime, $address, $profileImage]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Doctor added successfully.'];
            redirect('admin/doctors.php');
        }
    }
}

$pageTitle = 'Add Doctor';
$csrfToken = generateCSRFToken();
include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="page-title mb-1">Add New Doctor</h3>
        <p class="section-subtitle mb-0">Fill in the doctor's professional details.</p>
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
        <form method="POST" action="doctor_add.php" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">

            <h6 class="fw-bold text-primary mb-3">Basic Information</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Full Name <span class="required-star">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="Dr. John Doe">
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
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Password <span class="required-star">*</span></label>
                    <input type="password" name="password" class="form-control" required minlength="6">
                </div>
            </div>

            <h6 class="fw-bold text-primary mb-3 mt-4">Professional Details</h6>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label">Specialization</label>
                    <input type="text" name="specialization" class="form-control" placeholder="e.g. Cardiology">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Qualification</label>
                    <input type="text" name="qualification" class="form-control" placeholder="e.g. MBBS, MD">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Experience (Years)</label>
                    <input type="number" name="experience_years" class="form-control" min="0" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Consultation Fee (₹)</label>
                    <input type="number" step="0.01" name="consultation_fee" class="form-control" min="0" value="0">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Profile Image</label>
                    <input type="file" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                </div>
                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2"></textarea>
                </div>
            </div>

            <h6 class="fw-bold text-primary mb-3 mt-4">Schedule &amp; Availability</h6>
            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Available Days</label>
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($days as $day): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="schedule_days[]" value="<?php echo $day; ?>" id="day<?php echo $day; ?>" <?php echo in_array($day, ['Mon','Tue','Wed','Thu','Fri']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="day<?php echo $day; ?>"><?php echo $day; ?></label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Working Hours</label>
                    <input type="text" name="schedule_time" class="form-control" placeholder="09:00 AM - 05:00 PM" value="09:00 AM - 05:00 PM">
                </div>
            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-save me-1"></i> Save Doctor</button>
                <a href="doctors.php" class="btn btn-light px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
