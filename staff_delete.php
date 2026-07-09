<?php
/**
 * =====================================================================
 * FILE: admin/staff_delete.php
 * PLACE AT: hospital-management/admin/staff_delete.php
 * PURPOSE: Deletes a staff record (action script, no UI)
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM staff WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Staff member deleted successfully.'];
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid staff ID.'];
}

redirect('admin/staff.php');
