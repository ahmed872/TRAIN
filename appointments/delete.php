<?php
include_once '../includes/auth.php';
requireRole(['admin']);
requirePostRequest();
requireCsrfToken();
include_once '../config/database.php';
$conn = getConnection();

$id = validId($_POST['id'] ?? null);

if ($id === null) {
    redirectTo('/appointments/index.php', 'لم يتم تحديد الموعد', 'warning');
}

try {
    $stmt = $conn->prepare('DELETE FROM appointments WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        redirectTo('/appointments/index.php', 'الموعد غير موجود', 'warning');
    }
} catch (PDOException $e) {
    error_log('Appointment delete failed: ' . $e->getMessage());
    redirectTo('/appointments/index.php', 'لا يمكن الحذف — الموعد مرتبط بسجل طبي', 'danger');
}

redirectTo('/appointments/index.php', 'تم حذف الموعد بنجاح', 'success');
