<?php
/**
 * =====================================================================
 * FILE: index.php
 * PLACE AT: hospital-management/index.php (project root)
 * PURPOSE: Public landing page. Redirects logged-in users straight to
 *          their dashboard; otherwise shows a welcome/marketing page
 *          with links to Login and Register.
 * =====================================================================
 */
require_once __DIR__ . '/config.php';

// If already logged in, go straight to the correct dashboard
if (isLoggedIn()) {
    redirect($_SESSION['role'] . '/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Welcome</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { padding-top: 0; }
        .hero {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e3a8a 55%, #0d9488 100%);
            color: #fff;
            display: flex;
            align-items: center;
        }
        .hero-badge { background: rgba(255,255,255,0.12); border-radius: 30px; padding: 6px 16px; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 8px; }
        .feature-card { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1); border-radius: 14px; padding: 1.25rem; height: 100%; }
        .feature-card i { font-size: 1.6rem; color: #5eead4; }
        .role-btn { border-radius: 12px; padding: 0.85rem 1rem; font-weight: 600; border: 1px solid rgba(255,255,255,0.25); color: #fff; background: rgba(255,255,255,0.08); transition: 0.2s; }
        .role-btn:hover { background: #fff; color: #1e3a8a; }
    </style>
</head>
<body>
<div class="hero">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="hero-badge"><i class="fas fa-hospital"></i> Complete Hospital ERP Solution</span>
                <h1 class="display-5 fw-800 mt-3 mb-3" style="font-weight:800;">Welcome to <?php echo SITE_NAME; ?></h1>
                <p class="lead" style="color:#cbd5e1;">Manage patients, doctors, appointments, billing, pharmacy, laboratory, rooms and staff — all from one modern, unified dashboard.</p>

                <div class="d-flex flex-wrap gap-3 mt-4">
                    <a href="login.php" class="btn btn-light btn-lg px-4 fw-bold"><i class="fas fa-sign-in-alt me-2"></i>Login to Portal</a>
                    <a href="register.php" class="btn btn-outline-light btn-lg px-4"><i class="fas fa-user-plus me-2"></i>Register as Patient</a>
                </div>

                <div class="row g-3 mt-5">
                    <div class="col-md-4">
                        <div class="feature-card">
                            <i class="fas fa-calendar-check mb-2 d-block"></i>
                            <h6 class="fw-bold mb-1">Smart Scheduling</h6>
                            <small style="color:#cbd5e1;">Book, reschedule &amp; track appointments in real-time.</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <i class="fas fa-file-invoice-dollar mb-2 d-block"></i>
                            <h6 class="fw-bold mb-1">Integrated Billing</h6>
                            <small style="color:#cbd5e1;">Generate, print and track patient invoices instantly.</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="feature-card">
                            <i class="fas fa-flask mb-2 d-block"></i>
                            <h6 class="fw-bold mb-1">Pharmacy &amp; Lab</h6>
                            <small style="color:#cbd5e1;">Manage medicine stock and lab test reports easily.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="p-4 rounded-4" style="background: rgba(255,255,255,0.08); border:1px solid rgba(255,255,255,0.15);">
                    <h5 class="fw-bold mb-3"><i class="fas fa-users me-2"></i>Quick Access by Role</h5>
                    <div class="d-grid gap-3">
                        <a href="login.php?role=admin" class="role-btn"><i class="fas fa-user-shield me-2"></i>Administrator Login</a>
                        <a href="login.php?role=doctor" class="role-btn"><i class="fas fa-user-md me-2"></i>Doctor Login</a>
                        <a href="login.php?role=receptionist" class="role-btn"><i class="fas fa-user-tie me-2"></i>Receptionist Login</a>
                        <a href="login.php?role=patient" class="role-btn"><i class="fas fa-user me-2"></i>Patient Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
