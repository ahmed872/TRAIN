<?php
include_once '../includes/auth.php';
requireRole(['admin', 'receptionist']);
requirePostRequest();
requireCsrfToken();
include_once '../config/database.php';
$conn = getConnection();

$name        = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');

if ($name === '') {
    redirectTo('/departments/index.php', 'اسم القسم مطلوب', 'warning');
}

try {
    $stmt = $conn->prepare('INSERT INTO departments (name, description) VALUES (:name, :description)');
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->execute();
} catch (PDOException $e) {
    error_log('Department create failed: ' . $e->getMessage());
    redirectTo('/departments/index.php', 'تعذّر إضافة القسم — قد يكون الاسم مستخدمًا بالفعل', 'danger');
}

redirectTo('/departments/index.php', 'تم إضافة القسم بنجاح', 'success');
