<?php

include_once '../config/database.php';
$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? '';
    $doctor_id = $_POST['doctor_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    if (empty($patient_id) || empty($doctor_id) || empty($appointment_date)) {
        header("Location: index.php?msg=" . urlencode("المريض والطبيب وتاريخ الموعد مطلوبين"));
        exit;
    }

    // مفيش دكتور له معادين في نفس الوقت
    $checkQuery = "SELECT id FROM appointments
                   WHERE doctor_id = :doctor_id
                   AND appointment_date = :appointment_date
                   AND status != 'cancelled'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':doctor_id', $doctor_id);
    $checkStmt->bindParam(':appointment_date', $appointment_date);
    $checkStmt->execute();

    if ($checkStmt->fetch()) {
        header("Location: index.php?msg=" . urlencode("هذا الطبيب لديه موعد آخر في نفس التوقيت"));
        exit;
    }

    $query = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, notes)
              VALUES (:patient_id, :doctor_id, :appointment_date, :notes)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':patient_id', $patient_id);
    $stmt->bindParam(':doctor_id', $doctor_id);
    $stmt->bindParam(':appointment_date', $appointment_date);
    $stmt->bindParam(':notes', $notes);
    $stmt->execute();

    header("Location: index.php?msg=" . urlencode("تم إضافة الموعد بنجاح"));
    exit;
}

header("Location: index.php");
exit;
