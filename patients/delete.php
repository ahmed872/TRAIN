<?php
include_once '../includes/auth.php';
requireRole(['admin']);
requirePostRequest();
requireCsrfToken();
include_once '../config/database.php';
$conn = getConnection();

$id = validId($_POST['id'] ?? null);

if ($id === null) {
    redirectTo('/patients/index.php', 'لم يتم تحديد المريض', 'warning');
}

try {
    $stmt = $conn->prepare('DELETE FROM patients WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        redirectTo('/patients/index.php', 'المريض غير موجود', 'warning');
    }
} catch (PDOException $e) {
    error_log('Patient delete failed: ' . $e->getMessage());
    redirectTo('/patients/index.php', 'لا يمكن الحذف — المريض مرتبط بمواعيد', 'danger');
}

redirectTo('/patients/index.php', 'تم حذف المريض بنجاح', 'success');
