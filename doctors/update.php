<?php
include_once '../includes/auth.php';
requireRole(['admin', 'receptionist']);
include_once '../config/database.php';
$conn = getConnection();

$id = validId($_GET['id'] ?? $_POST['id'] ?? null);

if ($id === null) {
    redirectTo('/doctors/index.php', 'لم يتم تحديد الطبيب', 'warning');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $name           = trim($_POST['name'] ?? '');
    $specialization = trim($_POST['specialization'] ?? '');
    $department_id  = validId($_POST['department_id'] ?? null);
    $phone          = trim($_POST['phone'] ?? '');
    $email          = trim($_POST['email'] ?? '');

    if ($name === '') {
        redirectTo('/doctors/update.php?id=' . $id, 'اسم الطبيب مطلوب', 'warning');
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectTo('/doctors/update.php?id=' . $id, 'صيغة البريد الإلكتروني غير صحيحة', 'warning');
    }

    try {
        $stmt = $conn->prepare(
            'UPDATE doctors SET name = :name, specialization = :specialization,
             department_id = :department_id, phone = :phone, email = :email
             WHERE id = :id'
        );
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':specialization', $specialization);
        $stmt->bindValue(':department_id', $department_id, $department_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':email', $email);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log('Doctor update failed: ' . $e->getMessage());
        redirectTo('/doctors/update.php?id=' . $id, 'تعذّر حفظ التعديل', 'danger');
    }

    redirectTo('/doctors/index.php', 'تم تعديل بيانات الطبيب بنجاح', 'success');
}

$stmt = $conn->prepare('SELECT * FROM doctors WHERE id = :id');
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$doctor = $stmt->fetch();

if (!$doctor) {
    redirectTo('/doctors/index.php', 'الطبيب غير موجود', 'warning');
}

$deptStmt = $conn->prepare('SELECT id, name FROM departments ORDER BY name');
$deptStmt->execute();
$departments = $deptStmt->fetchAll();

include_once '../includes/header.php';
?>

<h3>تعديل بيانات الطبيب: <?= htmlspecialchars($doctor['name']) ?></h3>

<form action="update.php" method="POST" class="bg-white p-4 rounded shadow-sm">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= (int) $doctor['id'] ?>">

    <div class="mb-3">
        <label class="form-label">اسم الطبيب</label>
        <input type="text" name="name" class="form-control" maxlength="100"
            value="<?= htmlspecialchars($doctor['name']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">التخصص</label>
        <input type="text" name="specialization" class="form-control" maxlength="100"
            value="<?= htmlspecialchars((string) $doctor['specialization']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">القسم</label>
        <select name="department_id" class="form-select">
            <option value="">-- اختر القسم --</option>
            <?php foreach ($departments as $dept): ?>
                <option value="<?= (int) $dept['id'] ?>" <?= (int) $dept['id'] === (int) $doctor['department_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($dept['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">الهاتف</label>
        <input type="text" name="phone" class="form-control" maxlength="20"
            value="<?= htmlspecialchars((string) $doctor['phone']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">البريد الإلكتروني</label>
        <input type="email" name="email" class="form-control" maxlength="100"
            value="<?= htmlspecialchars((string) $doctor['email']) ?>">
    </div>

    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
    <a href="index.php" class="btn btn-secondary">إلغاء</a>
</form>

<?php include_once '../includes/footer.php'; ?>
