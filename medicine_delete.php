<?php
/**
 * =====================================================================
 * FILE: admin/medicine_delete.php
 * PLACE AT: hospital-management/admin/medicine_delete.php
 * PURPOSE: Deletes a medicine record (action script, no UI)
 * =====================================================================
 */
require_once '../config.php';
requireRole('admin');

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    $stmt = $pdo->prepare("DELETE FROM medicines WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Medicine deleted successfully.'];
} else {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Invalid medicine ID.'];
}

redirect('admin/pharmacy.php');
