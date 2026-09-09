<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $gender = $_POST['gender'] ?? 'male';
    $date_of_birth = $_POST['date_of_birth'] ?: null;
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name)) {
        header("Location: index.php?msg=" . urlencode("اسم المريض مطلوب"));
        exit;
    }

    $query = "INSERT INTO patients (name, gender, date_of_birth, phone, address)
              VALUES (:name, :gender, :date_of_birth, :phone, :address)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindParam(':date_of_birth', $date_of_birth);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':address', $address);
    $stmt->execute();

    header("Location: index.php?msg=" . urlencode("تم إضافة المريض بنجاح"));
    exit;
}

header("Location: index.php");
exit;
