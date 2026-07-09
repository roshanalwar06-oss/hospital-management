<?php
/**
 * =====================================================================
 * FILE: logout.php
 * PLACE AT: hospital-management/logout.php (project root)
 * PURPOSE: Destroys the current session and redirects to login page
 * =====================================================================
 */
require_once __DIR__ . '/config.php';

// Clear all session variables
$_SESSION = [];

// Destroy the session cookie
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}

// Destroy the session itself
session_destroy();

// Redirect to login with a logged-out flag
header('Location: ' . BASE_URL . 'login.php?loggedout=1');
exit();
