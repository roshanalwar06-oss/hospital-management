<?php
/**
 * =====================================================================
 * FILE: admin/patient_delete.php
 * PLACE AT: hospital-management/admin/patient_delete.php
 * PURPOSE: Deletes a patient record (action script, no UI)
 * =====================================================================
 */
require_once __DIR__ . '/config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        $stmt = $pdo->prepare("DELETE FROM patients WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Patient deleted successfully.'];
    } catch (PDOException $e) {
        // Likely a foreign key constraint (patient has related appointments/bills)
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Cannot delete patient — related records exist (appointments, bills, etc).'];
    }
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid patient ID.'];
}

redirect('admin/patients.php');
