<?php
include_once '../includes/auth.php';
requireRole(['admin', 'receptionist']);
include_once '../config/database.php';
include_once '../includes/validation.php';
$conn = getConnection();

$id = validId($_GET['id'] ?? $_POST['id'] ?? null);

if ($id === null) {
    redirectTo('/patients/index.php', 'لم يتم تحديد المريض', 'warning');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $name          = trim($_POST['name'] ?? '');
    $gender        = $_POST['gender'] ?? 'male';
    $date_of_birth = normalizeDate($_POST['date_of_birth'] ?? null);
    $phone         = trim($_POST['phone'] ?? '');
    $address       = trim($_POST['address'] ?? '');

    if ($name === '') {
        redirectTo('/patients/update.php?id=' . $id, 'اسم المريض مطلوب', 'warning');
    }

    if (!in_array($gender, ['male', 'female'], true)) {
        redirectTo('/patients/update.php?id=' . $id, 'قيمة النوع غير صالحة', 'warning');
    }

    if (($_POST['date_of_birth'] ?? '') !== '' && $date_of_birth === null) {
        redirectTo('/patients/update.php?id=' . $id, 'تاريخ الميلاد غير صالح', 'warning');
    }

    try {
        $stmt = $conn->prepare(
            'UPDATE patients SET name = :name, gender = :gender, date_of_birth = :date_of_birth,
             phone = :phone, address = :address WHERE id = :id'
        );
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindValue(':date_of_birth', $date_of_birth, $date_of_birth === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        error_log('Patient update failed: ' . $e->getMessage());
        redirectTo('/patients/update.php?id=' . $id, 'تعذّر حفظ التعديل', 'danger');
    }

    redirectTo('/patients/index.php', 'تم تعديل بيانات المريض بنجاح', 'success');
}

$stmt = $conn->prepare('SELECT * FROM patients WHERE id = :id');
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$patient = $stmt->fetch();

if (!$patient) {
    redirectTo('/patients/index.php', 'المريض غير موجود', 'warning');
}

include_once '../includes/header.php';
?>

<h3>تعديل بيانات المريض: <?= htmlspecialchars($patient['name']) ?></h3>

<form action="update.php" method="POST" class="bg-white p-4 rounded shadow-sm">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= (int) $patient['id'] ?>">

    <div class="mb-3">
        <label class="form-label">اسم المريض</label>
        <input type="text" name="name" class="form-control" maxlength="100"
            value="<?= htmlspecialchars($patient['name']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">النوع</label>
        <select name="gender" class="form-select" required>
            <option value="male" <?= $patient['gender'] === 'male' ? 'selected' : '' ?>>ذكر</option>
            <option value="female" <?= $patient['gender'] === 'female' ? 'selected' : '' ?>>أنثى</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">تاريخ الميلاد</label>
        <input type="date" name="date_of_birth" class="form-control" max="<?= date('Y-m-d') ?>"
            value="<?= htmlspecialchars((string) $patient['date_of_birth']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">الهاتف</label>
        <input type="text" name="phone" class="form-control" maxlength="20"
            value="<?= htmlspecialchars((string) $patient['phone']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">العنوان</label>
        <input type="text" name="address" class="form-control" maxlength="255"
            value="<?= htmlspecialchars((string) $patient['address']) ?>">
    </div>

    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
    <a href="index.php" class="btn btn-secondary">إلغاء</a>
</form>

<?php include_once '../includes/footer.php'; ?>
