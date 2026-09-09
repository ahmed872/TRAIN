<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? '';
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $prescription = trim($_POST['prescription'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($appointment_id)) {
        header("Location: index.php?msg=" . urlencode("لازم تختار الموعد"));
        exit;
    }

    $query = "INSERT INTO medical_records (appointment_id, diagnosis, prescription, notes)
              VALUES (:appointment_id, :diagnosis, :prescription, :notes)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':appointment_id', $appointment_id);
    $stmt->bindParam(':diagnosis', $diagnosis);
    $stmt->bindParam(':prescription', $prescription);
    $stmt->bindParam(':notes', $notes);
    $stmt->execute();

    header("Location: index.php?msg=" . urlencode("تم إضافة السجل الطبي بنجاح"));
    exit;
}

header("Location: index.php");
exit;
