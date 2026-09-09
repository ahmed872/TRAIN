<?php
include_once '../includes/auth.php';
requireRole(['admin', 'receptionist']);
requirePostRequest();
requireCsrfToken();
include_once '../config/database.php';
include_once '../includes/validation.php';
$conn = getConnection();

$patient_id       = validId($_POST['patient_id'] ?? null);
$doctor_id        = validId($_POST['doctor_id'] ?? null);
$appointment_date = normalizeDateTime($_POST['appointment_date'] ?? null);
$notes            = trim($_POST['notes'] ?? '');

if ($patient_id === null || $doctor_id === null || $appointment_date === null) {
    redirectTo('/appointments/index.php', 'المريض والطبيب وتاريخ الموعد مطلوبين وبصيغة صحيحة', 'warning');
}

try {
    // القيد UNIQUE على (doctor_id, appointment_date) في قاعدة البيانات هو اللي
    // بيمنع الحجز المزدوج فعليًا حتى لو جالنا طلبين في نفس اللحظة.
    $stmt = $conn->prepare(
        'INSERT INTO appointments (patient_id, doctor_id, appointment_date, notes)
         VALUES (:patient_id, :doctor_id, :appointment_date, :notes)'
    );
    $stmt->bindValue(':patient_id', $patient_id, PDO::PARAM_INT);
    $stmt->bindValue(':doctor_id', $doctor_id, PDO::PARAM_INT);
    $stmt->bindParam(':appointment_date', $appointment_date);
    $stmt->bindParam(':notes', $notes);
    $stmt->execute();
} catch (PDOException $e) {
    // 23000 = انتهاك قيد (تكرار الموعد أو مفتاح أجنبي غير موجود)
    if ($e->getCode() === '23000') {
        redirectTo('/appointments/index.php', 'هذا الطبيب لديه موعد آخر في نفس التوقيت، أو أن المريض/الطبيب غير موجود', 'warning');
    }

    error_log('Appointment create failed: ' . $e->getMessage());
    redirectTo('/appointments/index.php', 'تعذّر إضافة الموعد', 'danger');
}

redirectTo('/appointments/index.php', 'تم إضافة الموعد بنجاح', 'success');
