<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    header("Location: index.php?msg=" . urlencode("لم يتم تحديد القسم"));
    exit;
}

// لو الفورم اتبعت (POST) نفذ التعديل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        header("Location: update.php?id=$id&msg=" . urlencode("اسم القسم مطلوب"));
        exit;
    }

    $query = "UPDATE departments SET name = :name, description = :description WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header("Location: index.php?msg=" . urlencode("تم تعديل القسم بنجاح"));
    exit;
}

// لو GET، هات بيانات القسم الحالية واعرضها في الفورم
$query = "SELECT * FROM departments WHERE id = :id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':id', $id);
$stmt->execute();
$department = $stmt->fetch();

if (!$department) {
    header("Location: index.php?msg=" . urlencode("القسم غير موجود"));
    exit;
}

include_once '../includes/header.php';
?>

<h3>Edit Department: <?= htmlspecialchars($department['name']) ?></h3>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<form action="update.php" method="POST" class="bg-white p-4 rounded shadow-sm">
    <input type="hidden" name="id" value="<?= $department['id'] ?>">

    <div class="mb-3">
        <label class="form-label">Department Name</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($department['name']) ?>"
            required>
    </div>

    <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control"><?= htmlspecialchars($department['description']) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include_once '../includes/footer.php'; ?>