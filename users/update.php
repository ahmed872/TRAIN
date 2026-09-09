<?php
include_once '../includes/auth.php';
requireAdmin();
include_once '../config/database.php';
$conn = getConnection();

$id = validId($_GET['id'] ?? $_POST['id'] ?? null);

if ($id === null) {
    redirectTo('/users/index.php', 'لم يتم تحديد المستخدم', 'warning');
}

$allowedRoles = ['admin', 'receptionist', 'doctor', 'patient'];

/** يعدّ الأدمن الباقيين غير المستخدم ده، عشان ما نقفلش النظام على الكل. */
function otherAdminsCount(PDO $conn, int $excludeUserId): int
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'admin' AND id != :id");
    $stmt->bindValue(':id', $excludeUserId, PDO::PARAM_INT);
    $stmt->execute();

    return (int) $stmt->fetch()['total'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $role      = $_POST['role'] ?? 'receptionist';
    $doctor_id = validId($_POST['doctor_id'] ?? null);

    if ($username === '') {
        redirectTo('/users/update.php?id=' . $id, 'اسم المستخدم مطلوب', 'warning');
    }

    // كانت الصلاحية بتتحفظ من غير أي تحقق — أي قيمة كانت تعدي
    if (!in_array($role, $allowedRoles, true)) {
        redirectTo('/users/update.php?id=' . $id, 'الصلاحية المختارة غير صالحة', 'warning');
    }

    if ($password !== '' && mb_strlen($password) < 8) {
        redirectTo('/users/update.php?id=' . $id, 'كلمة المرور لازم تكون 8 أحرف على الأقل', 'warning');
    }

    // منع تنزيل صلاحية آخر أدمن في النظام
    $currentStmt = $conn->prepare('SELECT role FROM users WHERE id = :id');
    $currentStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $currentStmt->execute();
    $existing = $currentStmt->fetch();

    if (!$existing) {
        redirectTo('/users/index.php', 'المستخدم غير موجود', 'warning');
    }

    if ($existing['role'] === 'admin' && $role !== 'admin' && otherAdminsCount($conn, $id) === 0) {
        redirectTo('/users/update.php?id=' . $id, 'لا يمكن تغيير صلاحية آخر مدير في النظام', 'danger');
    }

    // لو كتب كلمة مرور جديدة، حدّثها. لو سابها فاضية، سيب القديمة زي ما هي
    if ($password !== '') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            'UPDATE users SET username = :username, password = :password,
             full_name = :full_name, role = :role, doctor_id = :doctor_id
             WHERE id = :id'
        );
        $stmt->bindParam(':password', $hashedPassword);
    } else {
        $stmt = $conn->prepare(
            'UPDATE users SET username = :username,
             full_name = :full_name, role = :role, doctor_id = :doctor_id
             WHERE id = :id'
        );
    }

    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':full_name', $full_name);
    $stmt->bindParam(':role', $role);
    $stmt->bindValue(':doctor_id', $doctor_id, $doctor_id === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);

    try {
        $stmt->execute();
    } catch (PDOException $e) {
        error_log('User update failed: ' . $e->getMessage());
        redirectTo('/users/update.php?id=' . $id, 'اسم المستخدم موجود بالفعل', 'danger');
    }

    // لو الأدمن عدّل بيانات نفسه، نحدّث الجلسة عشان الواجهة تفضل متطابقة
    if ($id === (int) $_SESSION['user_id']) {
        $_SESSION['username']  = $username;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['role']      = $role;
        $_SESSION['doctor_id'] = $doctor_id;
    }

    redirectTo('/users/index.php', 'تم تعديل بيانات المستخدم بنجاح', 'success');
}

$stmt = $conn->prepare('SELECT id, username, full_name, role, doctor_id FROM users WHERE id = :id');
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
    redirectTo('/users/index.php', 'المستخدم غير موجود', 'warning');
}

$doctorsStmt = $conn->prepare('SELECT id, name FROM doctors ORDER BY name');
$doctorsStmt->execute();
$doctors = $doctorsStmt->fetchAll();

include_once '../includes/header.php';

$roleOptions = [
    'admin'        => 'مدير النظام',
    'receptionist' => 'موظف استقبال',
    'doctor'       => 'طبيب',
    'patient'      => 'مريض',
];
?>

<h3>تعديل المستخدم: <?= htmlspecialchars($user['username']) ?></h3>

<form action="update.php" method="POST" class="bg-white p-4 rounded shadow-sm">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">

    <div class="mb-3">
        <label class="form-label">اسم المستخدم</label>
        <input type="text" name="username" class="form-control" minlength="3" maxlength="50"
            value="<?= htmlspecialchars($user['username']) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">كلمة مرور جديدة (اتركها فارغة للإبقاء على القديمة)</label>
        <input type="password" name="password" class="form-control" minlength="8">
    </div>

    <div class="mb-3">
        <label class="form-label">الاسم بالكامل</label>
        <input type="text" name="full_name" class="form-control" maxlength="100"
            value="<?= htmlspecialchars((string) $user['full_name']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">الصلاحية</label>
        <select name="role" class="form-select" required>
            <?php foreach ($roleOptions as $value => $label): ?>
                <option value="<?= $value ?>" <?= $user['role'] === $value ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">ربط بطبيب (اختياري)</label>
        <select name="doctor_id" class="form-select">
            <option value="">-- بدون --</option>
            <?php foreach ($doctors as $d): ?>
                <option value="<?= (int) $d['id'] ?>" <?= (int) $d['id'] === (int) $user['doctor_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
    <a href="index.php" class="btn btn-secondary">إلغاء</a>
</form>

<?php include_once '../includes/footer.php'; ?>
