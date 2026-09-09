<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: index.php?msg=" . urlencode("لم يتم تحديد القسم"));
    exit;
}

try {
    $query = "DELETE FROM departments WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header("Location: index.php?msg=" . urlencode("تم حذف القسم بنجاح"));
} catch (PDOException $e) {
    header("Location: index.php?msg=" . urlencode("لا يمكن الحذف — القسم مرتبط ببيانات أخرى"));
}
exit;
