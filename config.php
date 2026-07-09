<?php error_reporting(E_ALL);
ini_set('display_errors', 1);
// For generating hashed password for initial admin user
/**
 * =====================================================================
 * FILE: config.php
 * PURPOSE: Central configuration - Database connection, Session, Constants
 * PLACE AT: hospital-management/config.php (project root)
 * =====================================================================
 * This file is included at the TOP of every page in the project using:
 *      require_once __DIR__ . '/config.php';   (from root)
 *      require_once '../config.php';           (from sub-folders)
 * =====================================================================
 */

// Start the session on every page (needed for login/auth state)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -----------------------------------------------------------------
// ERROR REPORTING (Turn OFF display_errors in production!)
// -----------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', 1);

// -----------------------------------------------------------------
// DATABASE CREDENTIALS (default XAMPP settings)
// -----------------------------------------------------------------
define('DB_HOST', 'localhost');
define('DB_NAME', 'hospital_management');
define('DB_USER', 'root');
define('DB_PASS', '');          // Default XAMPP MySQL password is empty
define('DB_CHARSET', 'utf8mb4');

// -----------------------------------------------------------------
// GLOBAL PATH CONSTANTS
// -----------------------------------------------------------------
// BASE_URL should match your XAMPP htdocs folder name for this project.
// Example: if project is at C:/xampp/htdocs/hospital-management
// then BASE_URL = http://localhost/hospital-management/
define('BASE_URL', 'http://localhost/hospital-management/');
define('UPLOAD_PATH', __DIR__ . '/uploads/');
define('SITE_NAME', 'CarePlus Hospital Management System');

// -----------------------------------------------------------------
// DATABASE CONNECTION (PDO) - Used everywhere for prepared statements
// -----------------------------------------------------------------
try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw exceptions on errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // fetch as associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                    // use REAL prepared statements
    ];

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

} catch (PDOException $e) {
    // Never expose raw DB errors to end users in production
    die('Database Connection Failed: ' . $e->getMessage());
}

// -----------------------------------------------------------------
// GLOBAL HELPER FUNCTIONS
// -----------------------------------------------------------------

/**
 * Sanitize user input (basic XSS protection for output)
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper
 */
function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit();
}

/**
 * Check if a user (any role) is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Restrict page access to specific role(s).
 * Usage: requireRole('admin');  or  requireRole(['admin','doctor']);
 */
function requireRole($roles) {
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    if (!isLoggedIn() || !in_array($_SESSION['role'], $roles)) {
        redirect('login.php');
    }
}

/**
 * Generate a CSRF token and store in session
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token from a submitted form
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format currency for display (Indian Rupees)
 */
function formatCurrency($amount) {
    return '₹' . number_format((float)$amount, 2);
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'd M Y') {
    if (empty($date) || $date === '0000-00-00') return '-';
    return date($format, strtotime($date));
}
