<?php
include_once '../includes/auth.php';
requireRole(['admin', 'receptionist']);
requirePostRequest();
requireCsrfToken();
include_once '../config/database.php';
$conn = getConnection();

$name           = trim($_POST['name'] ?? '');
$specialization = trim($_POST['specialization'] ?? '');
$department_id  = validId($_POST['department_id'] ?? null);
$phone          = trim($_POST['phone'] ?? '');
$email          = trim($_POST['email'] ?? '');

if ($name === '') {
    redirectTo('/doctors/index.php', 'اسم الطبيب مطلوب', 'warning');
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirectTo('/doctors/index.php', 'صيغة البريد الإلكتروني غير صحيحة', 'warning');
}

try {
    $stmt = $conn->prepare(
        'INSERT INTO doctors (name, specialization, department_id, phone, email)
         VALUES (:name, :specialization, :department_id, :phone, :email)'
    );
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':specialization', $specialization);
    $stmt->bindValue(':department_id', $department_id, $department_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
} catch (PDOException $e) {
    error_log('Doctor create failed: ' . $e->getMessage());
    redirectTo('/doctors/index.php', 'تعذّر إضافة الطبيب — تأكد من صحة القسم المختار', 'danger');
}

redirectTo('/doctors/index.php', 'تم إضافة الطبيب بنجاح', 'success');
