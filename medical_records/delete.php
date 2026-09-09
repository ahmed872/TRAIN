<?php
include_once '../includes/auth.php';
requireRole(['admin']);
requirePostRequest();
requireCsrfToken();
include_once '../config/database.php';
$conn = getConnection();

$id = validId($_POST['id'] ?? null);

if ($id === null) {
    redirectTo('/medical_records/index.php', 'لم يتم تحديد السجل', 'warning');
}

try {
    $stmt = $conn->prepare('DELETE FROM medical_records WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        redirectTo('/medical_records/index.php', 'السجل غير موجود', 'warning');
    }
} catch (PDOException $e) {
    error_log('Medical record delete failed: ' . $e->getMessage());
    redirectTo('/medical_records/index.php', 'تعذّر حذف السجل الطبي', 'danger');
}

redirectTo('/medical_records/index.php', 'تم حذف السجل الطبي بنجاح', 'success');
