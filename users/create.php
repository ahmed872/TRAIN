<?php
include_once '../includes/auth.php';
requireAdmin();
requirePostRequest();
requireCsrfToken();
include_once '../config/database.php';
$conn = getConnection();

$username  = trim($_POST['username'] ?? '');
$password  = $_POST['password'] ?? '';
$full_name = trim($_POST['full_name'] ?? '');
$role      = $_POST['role'] ?? 'receptionist';
$doctor_id = validId($_POST['doctor_id'] ?? null);

$allowedRoles = ['admin', 'receptionist', 'doctor'];

if ($username === '' || $password === '') {
    redirectTo('/users/index.php', 'اسم المستخدم وكلمة المرور مطلوبين', 'warning');
}

if (!in_array($role, $allowedRoles, true)) {
    redirectTo('/users/index.php', 'الصلاحية المختارة غير صالحة', 'warning');
}

if (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
    redirectTo('/users/index.php', 'اسم المستخدم لازم يكون بين 3 و 50 حرف', 'warning');
}

if (mb_strlen($password) < 8) {
    redirectTo('/users/index.php', 'كلمة المرور لازم تكون 8 أحرف على الأقل', 'warning');
}

// تشفير كلمة المرور - أبدًا متخزنش الباسورد كنص عادي
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare(
        'INSERT INTO users (username, password, full_name, role, doctor_id)
         VALUES (:username, :password, :full_name, :role, :doctor_id)'
    );
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':full_name', $full_name);
    $stmt->bindParam(':role', $role);
    $stmt->bindValue(':doctor_id', $doctor_id, $doctor_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->execute();
} catch (PDOException $e) {
    error_log('User create failed: ' . $e->getMessage());
    redirectTo('/users/index.php', 'اسم المستخدم موجود بالفعل', 'danger');
}

redirectTo('/users/index.php', 'تم إضافة المستخدم بنجاح', 'success');
