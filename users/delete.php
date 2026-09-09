<?php
include_once '../includes/auth.php';
requireAdmin();
include_once '../config/database.php';
$conn = getConnection();

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: index.php?msg=" . urlencode("لم يتم تحديد المستخدم"));
    exit;
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();

header("Location: index.php?msg=" . urlencode("تم حذف المستخدم بنجاح"));
exit;
