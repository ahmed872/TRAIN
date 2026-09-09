<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        header("Location: index.php?msg=" . urlencode("اسم القسم مطلوب"));
        exit;
    }

    $query = "INSERT INTO departments (name, description) VALUES (:name, :description)";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->execute();

    header("Location: index.php?msg=" . urlencode("تم إضافة القسم بنجاح"));
    exit;
}

header("Location: index.php");
exit;
