<?php
include_once '../includes/auth.php';
requireRole(['admin', 'doctor']);
requirePostRequest();
requireCsrfToken();
include_once '../config/database.php';
$conn = getConnection();

$appointment_id = validId($_POST['appointment_id'] ?? null);
$diagnosis      = trim($_POST['diagnosis'] ?? '');
$prescription   = trim($_POST['prescription'] ?? '');
$notes          = trim($_POST['notes'] ?? '');

if ($appointment_id === null) {
    redirectTo('/medical_records/index.php', 'لازم تختار الموعد', 'warning');
}

// الطبيب ما يقدرش يكتب سجل لموعد مش بتاعه
if (currentRole() === 'doctor') {
    $ownStmt = $conn->prepare('SELECT doctor_id FROM appointments WHERE id = :id');
    $ownStmt->bindValue(':id', $appointment_id, PDO::PARAM_INT);
    $ownStmt->execute();
    $appointment = $ownStmt->fetch();

    if (!$appointment || (int) $appointment['doctor_id'] !== (int) ($_SESSION['doctor_id'] ?? 0)) {
        redirectTo('/medical_records/index.php', 'غير مصرح لك بإضافة سجل لهذا الموعد', 'danger');
    }
}

try {
    $stmt = $conn->prepare(
        'INSERT INTO medical_records (appointment_id, diagnosis, prescription, notes)
         VALUES (:appointment_id, :diagnosis, :prescription, :notes)'
    );
    $stmt->bindValue(':appointment_id', $appointment_id, PDO::PARAM_INT);
    $stmt->bindParam(':diagnosis', $diagnosis);
    $stmt->bindParam(':prescription', $prescription);
    $stmt->bindParam(':notes', $notes);
    $stmt->execute();
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        redirectTo('/medical_records/index.php', 'هذا الموعد له سجل طبي بالفعل، أو أن الموعد غير موجود', 'warning');
    }

    error_log('Medical record create failed: ' . $e->getMessage());
    redirectTo('/medical_records/index.php', 'تعذّر إضافة السجل الطبي', 'danger');
}

redirectTo('/medical_records/index.php', 'تم إضافة السجل الطبي بنجاح', 'success');
