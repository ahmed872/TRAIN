<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $department_id = $_POST['department_id'] ?: null;
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($name)) {
        header("Location: index.php?msg=" . urlencode("اسم الطبيب مطلوب"));
        exit;
    }

    $query = "INSERT INTO doctors (name, specialization, department_id, phone, email)
              VALUES (:name, :specialization, :department_id, :phone, :email)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':specialization', $specialization);
    $stmt->bindParam(':department_id', $department_id);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    header("Location: index.php?msg=" . urlencode("تم إضافة الطبيب بنجاح"));
    exit;
}

header("Location: index.php");
exit;
