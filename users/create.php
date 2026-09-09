<?php
include_once '../includes/auth.php';
requireAdmin();
include_once '../config/database.php';
$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'receptionist';
    $doctor_id = $_POST['doctor_id'] ?: null;
    $allowedRoles = ['admin', 'receptionist', 'doctor'];

    if (empty($username) || empty($password) || !in_array($role, $allowedRoles, true)) {
        header("Location: index.php?msg=" . urlencode("اسم المستخدم وكلمة المرور مطلوبين"));
        exit;
    }

    // تشفير كلمة المرور - أبدًا متخزنش الباسورد كنص عادي
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $query = "INSERT INTO users (username, password, full_name, role, doctor_id)
              VALUES (:username, :password, :full_name, :role, :doctor_id)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':full_name', $full_name);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':doctor_id', $doctor_id);

    try {
        $stmt->execute();
        header("Location: index.php?msg=" . urlencode("تم إضافة المستخدم بنجاح"));
    } catch (PDOException $e) {
        header("Location: index.php?msg=" . urlencode("اسم المستخدم موجود بالفعل"));
    }
    exit;
}

header("Location: index.php");
exit;
