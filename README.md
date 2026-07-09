# Hospital Management System

A full-featured, role-based Hospital Management System built with Core PHP, MySQL (PDO), Bootstrap 5, and Font Awesome. Designed to run on XAMPP.

## Tech Stack

- HTML5, CSS3, Bootstrap 5, JavaScript
- Core PHP (no framework)
- MySQL with PDO Prepared Statements
- Font Awesome icons
- XAMPP (Apache + MySQL + PHP)

## Features by Role

### Admin
- Dashboard with system-wide statistics
- Manage Patients (CRUD)
- Manage Doctors (CRUD)
- Manage Appointments (CRUD)
- Billing management
- Pharmacy management
- Laboratory management
- Rooms management
- Staff management
- Reports
- Profile management

### Doctor
- Dashboard with today's appointments and stats
- View and manage appointments
- View assigned patients
- Manage schedule/availability
- Profile management

### Receptionist
- Dashboard with quick stats
- Manage patient registration
- Manage appointments
- Generate and manage bills/invoices
- Profile management

### Patient
- Dashboard with personal stats (upcoming appointments, bills, lab reports)
- Book new appointments
- View and cancel appointments
- View medical history (visits and prescriptions)
- View and pay bills
- View lab reports
- Profile management (including password change and profile photo upload)

## Security Features

- Role-based access control on every page (session-based)
- CSRF protection on all forms (token generation + verification)
- PDO Prepared Statements throughout (no raw SQL concatenation)
- Password hashing using PHP's `password_hash()` / `password_verify()`
- Input validation and sanitization on all forms
- File upload validation (MIME type checks, size limits) for profile photos and lab reports
- `htmlspecialchars()` used throughout to prevent XSS

## Folder Structure