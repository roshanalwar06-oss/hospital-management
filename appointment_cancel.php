<?php
/**
 * =====================================================================
 * FILE: admin/appointment_cancel.php
 * PLACE AT: hospital-management/admin/appointment_cancel.php
 * PURPOSE: Cancels an appointment (sets status = 'cancelled', keeps the
 *          record for history rather than hard-deleting it)
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Appointment cancelled successfully.'];
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid appointment ID.'];
}

redirect('admin/appointments.php');
