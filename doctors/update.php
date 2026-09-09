<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    header("Location: index.php?msg=" . urlencode("لم يتم تحديد الطبيب"));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $department_id = $_POST['department_id'] ?: null;
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($name)) {
        header("Location: update.php?id=$id&msg=" . urlencode("اسم الطبيب مطلوب"));
        exit;
    }

    $query = "UPDATE doctors SET name = :name, specialization = :specialization,
              department_id = :department_id, phone = :phone, email = :email
              WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':specialization', $specialization);
    $stmt->bindParam(':department_id', $department_id);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header("Location: index.php?msg=" . urlencode("تم تعديل بيانات الطبيب بنجاح"));
    exit;
}

$stmt = $conn->prepare("SELECT * FROM doctors WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$doctor = $stmt->fetch();

if (!$doctor) {
    header("Location: index.php?msg=" . urlencode("الطبيب غير موجود"));
    exit;
}

$deptStmt = $conn->prepare("SELECT * FROM departments ORDER BY name");
$deptStmt->execute();
$departments = $deptStmt->fetchAll();

include_once '../includes/header.php';
?>

<h3>Edit Doctor: <?= htmlspecialchars($doctor['name']) ?></h3>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<form action="update.php" method="POST" class="bg-white p-4 rounded shadow-sm">
    <input type="hidden" name="id" value="<?= $doctor['id'] ?>">

    <div class="mb-3">
        <label class="form-label">Doctor Name</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($doctor['name']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Specialization</label>
        <input type="text" name="specialization" class="form-control"
            value="<?= htmlspecialchars($doctor['specialization']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-select">
            <option value="">-- Select Department --</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?= $dept['id'] ?>" <?= $dept['id'] == $doctor['department_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dept['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($doctor['phone']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($doctor['email']) ?>">
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include_once '../includes/footer.php'; ?>