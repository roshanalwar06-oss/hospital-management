<?php
/**
 * =====================================================================
 * FILE: admin/doctor_delete.php
 * PLACE AT: hospital-management/admin/doctor_delete.php
 * PURPOSE: Deletes a doctor record (action script, no UI)
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM doctors WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Doctor deleted successfully.'];
    } catch (PDOException $e) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Cannot delete doctor — related records exist (appointments, lab tests, etc).'];
    }
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid doctor ID.'];
}

redirect('admin/doctors.php');
