<?php
include_once '../includes/auth.php';
requireRole(['admin', 'receptionist']);
requirePostRequest();
requireCsrfToken();
include_once '../config/database.php';
include_once '../includes/validation.php';
$conn = getConnection();

$name          = trim($_POST['name'] ?? '');
$gender        = $_POST['gender'] ?? 'male';
$date_of_birth = normalizeDate($_POST['date_of_birth'] ?? null);
$phone         = trim($_POST['phone'] ?? '');
$address       = trim($_POST['address'] ?? '');

if ($name === '') {
    redirectTo('/patients/index.php', 'اسم المريض مطلوب', 'warning');
}

if (!in_array($gender, ['male', 'female'], true)) {
    redirectTo('/patients/index.php', 'قيمة النوع غير صالحة', 'warning');
}

if (($_POST['date_of_birth'] ?? '') !== '' && $date_of_birth === null) {
    redirectTo('/patients/index.php', 'تاريخ الميلاد غير صالح', 'warning');
}

try {
    $stmt = $conn->prepare(
        'INSERT INTO patients (name, gender, date_of_birth, phone, address)
         VALUES (:name, :gender, :date_of_birth, :phone, :address)'
    );
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindValue(':date_of_birth', $date_of_birth, $date_of_birth === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':address', $address);
    $stmt->execute();
} catch (PDOException $e) {
    error_log('Patient create failed: ' . $e->getMessage());
    redirectTo('/patients/index.php', 'تعذّر إضافة المريض', 'danger');
}

redirectTo('/patients/index.php', 'تم إضافة المريض بنجاح', 'success');
