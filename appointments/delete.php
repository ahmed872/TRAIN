<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: index.php?msg=" . urlencode("لم يتم تحديد الموعد"));
    exit;
}

$stmt = $conn->prepare("DELETE FROM appointments WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();

header("Location: index.php?msg=" . urlencode("تم حذف الموعد بنجاح"));
exit;
