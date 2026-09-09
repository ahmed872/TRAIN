<?php
include_once '../includes/auth.php';
requireRole(['admin']);
requirePostRequest();
requireCsrfToken();
include_once '../config/database.php';
$conn = getConnection();

$id = validId($_POST['id'] ?? null);

if ($id === null) {
    redirectTo('/doctors/index.php', 'لم يتم تحديد الطبيب', 'warning');
}

try {
    $stmt = $conn->prepare('DELETE FROM doctors WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        redirectTo('/doctors/index.php', 'الطبيب غير موجود', 'warning');
    }
} catch (PDOException $e) {
    error_log('Doctor delete failed: ' . $e->getMessage());
    redirectTo('/doctors/index.php', 'لا يمكن الحذف — الطبيب مرتبط بمواعيد', 'danger');
}

redirectTo('/doctors/index.php', 'تم حذف الطبيب بنجاح', 'success');
