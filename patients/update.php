<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    header("Location: index.php?msg=" . urlencode("لم يتم تحديد المريض"));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $gender = $_POST['gender'] ?? 'male';
    $date_of_birth = $_POST['date_of_birth'] ?: null;
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name)) {
        header("Location: update.php?id=$id&msg=" . urlencode("اسم المريض مطلوب"));
        exit;
    }

    $query = "UPDATE patients SET name = :name, gender = :gender, date_of_birth = :date_of_birth,
              phone = :phone, address = :address WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':gender', $gender);
    $stmt->bindParam(':date_of_birth', $date_of_birth);
    $stmt->bindParam(':phone', $phone);
    $stmt->bindParam(':address', $address);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header("Location: index.php?msg=" . urlencode("تم تعديل بيانات المريض بنجاح"));
    exit;
}

$stmt = $conn->prepare("SELECT * FROM patients WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$patient = $stmt->fetch();

if (!$patient) {
    header("Location: index.php?msg=" . urlencode("المريض غير موجود"));
    exit;
}

include_once '../includes/header.php';
?>

<h3>Edit Patient: <?= htmlspecialchars($patient['name']) ?></h3>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<form action="update.php" method="POST" class="bg-white p-4 rounded shadow-sm">
    <input type="hidden" name="id" value="<?= $patient['id'] ?>">

    <div class="mb-3">
        <label class="form-label">Patient Name</label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($patient['name']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Gender</label>
        <select name="gender" class="form-select" required>
            <option value="male" <?= $patient['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= $patient['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Date of Birth</label>
        <input type="date" name="date_of_birth" class="form-control"
            value="<?= htmlspecialchars($patient['date_of_birth']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($patient['phone']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($patient['address']) ?>">
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include_once '../includes/footer.php'; ?>