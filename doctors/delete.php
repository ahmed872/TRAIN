<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: index.php?msg=" . urlencode("لم يتم تحديد الطبيب"));
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM doctors WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header("Location: index.php?msg=" . urlencode("تم حذف الطبيب بنجاح"));
} catch (PDOException $e) {
    header("Location: index.php?msg=" . urlencode("لا يمكن الحذف — الطبيب مرتبط بمواعيد"));
}
exit;
