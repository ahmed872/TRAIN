<?php
include_once '../includes/auth.php';
requireRole(['admin']);
requirePostRequest();
requireCsrfToken();
include_once '../config/database.php';
$conn = getConnection();

$id = validId($_POST['id'] ?? null);

if ($id === null) {
    redirectTo('/departments/index.php', 'لم يتم تحديد القسم', 'warning');
}

try {
    $stmt = $conn->prepare('DELETE FROM departments WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        redirectTo('/departments/index.php', 'القسم غير موجود', 'warning');
    }
} catch (PDOException $e) {
    error_log('Department delete failed: ' . $e->getMessage());
    redirectTo('/departments/index.php', 'لا يمكن الحذف — القسم مرتبط ببيانات أخرى', 'danger');
}

redirectTo('/departments/index.php', 'تم حذف القسم بنجاح', 'success');
