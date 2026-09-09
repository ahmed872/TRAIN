<?php
include_once '../includes/auth.php';
requireAdmin();
requirePostRequest();
requireCsrfToken();
include_once '../config/database.php';
$conn = getConnection();

$id = validId($_POST['id'] ?? null);

if ($id === null) {
    redirectTo('/users/index.php', 'لم يتم تحديد المستخدم', 'warning');
}

// الأدمن ما يقدرش يحذف حساب نفسه ويقفل على نفسه بره النظام
if ($id === (int) $_SESSION['user_id']) {
    redirectTo('/users/index.php', 'لا يمكنك حذف حسابك الشخصي', 'danger');
}

$roleStmt = $conn->prepare('SELECT role FROM users WHERE id = :id');
$roleStmt->bindValue(':id', $id, PDO::PARAM_INT);
$roleStmt->execute();
$target = $roleStmt->fetch();

if (!$target) {
    redirectTo('/users/index.php', 'المستخدم غير موجود', 'warning');
}

// ولا يقدر يحذف آخر أدمن باقي في النظام
if ($target['role'] === 'admin') {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'admin' AND id != :id");
    $countStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $countStmt->execute();

    if ((int) $countStmt->fetch()['total'] === 0) {
        redirectTo('/users/index.php', 'لا يمكن حذف آخر مدير في النظام', 'danger');
    }
}

try {
    $stmt = $conn->prepare('DELETE FROM users WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
} catch (PDOException $e) {
    error_log('User delete failed: ' . $e->getMessage());
    redirectTo('/users/index.php', 'تعذّر حذف المستخدم', 'danger');
}

redirectTo('/users/index.php', 'تم حذف المستخدم بنجاح', 'success');
