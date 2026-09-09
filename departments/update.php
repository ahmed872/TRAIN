<?php
include_once '../includes/auth.php';
requireRole(['admin', 'receptionist']);
include_once '../config/database.php';
$conn = getConnection();

$id = validId($_GET['id'] ?? $_POST['id'] ?? null);

if ($id === null) {
    redirectTo('/departments/index.php', 'لم يتم تحديد القسم', 'warning');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $name        = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($name === '') {
        redirectTo('/departments/update.php?id=' . $id, 'اسم القسم مطلوب', 'warning');
    }

    try {
        $stmt = $conn->prepare(
            'UPDATE departments SET name = :name, description = :description WHERE id = :id'
        );
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':description', $description);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log('Department update failed: ' . $e->getMessage());
        redirectTo('/departments/update.php?id=' . $id, 'تعذّر حفظ التعديل', 'danger');
    }

    redirectTo('/departments/index.php', 'تم تعديل القسم بنجاح', 'success');
}

$stmt = $conn->prepare('SELECT * FROM departments WHERE id = :id');
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$department = $stmt->fetch();

if (!$department) {
    redirectTo('/departments/index.php', 'القسم غير موجود', 'warning');
}

include_once '../includes/header.php';
?>

<h3>تعديل القسم: <?= htmlspecialchars($department['name']) ?></h3>

<form action="update.php" method="POST" class="bg-white p-4 rounded shadow-sm">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= (int) $department['id'] ?>">

    <div class="mb-3">
        <label class="form-label">اسم القسم</label>
        <input type="text" name="name" class="form-control" maxlength="100"
            value="<?= htmlspecialchars($department['name']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">الوصف</label>
        <textarea name="description" class="form-control"><?= htmlspecialchars((string) $department['description']) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
    <a href="index.php" class="btn btn-secondary">إلغاء</a>
</form>

<?php include_once '../includes/footer.php'; ?>
