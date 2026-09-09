<?php
include_once '../includes/auth.php';
requireAdmin();
include_once '../config/database.php';
$conn = getConnection();

$stmt = $conn->prepare(
    'SELECT users.id, users.username, users.full_name, users.role, users.doctor_id,
            doctors.name AS doctor_name
     FROM users
     LEFT JOIN doctors ON users.doctor_id = doctors.id
     ORDER BY users.id DESC'
);
$stmt->execute();
$users = $stmt->fetchAll();

$doctorsStmt = $conn->prepare('SELECT id, name FROM doctors ORDER BY name');
$doctorsStmt->execute();
$doctors = $doctorsStmt->fetchAll();

include_once '../includes/header.php';

$roleLabels = [
    'admin'        => 'مدير النظام',
    'receptionist' => 'موظف استقبال',
    'doctor'       => 'طبيب',
    'patient'      => 'مريض',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>المستخدمون</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ إضافة مستخدم</button>
</div>

<table class="table table-bordered table-striped bg-white align-middle">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>اسم المستخدم</th>
            <th>الاسم بالكامل</th>
            <th>الصلاحية</th>
            <th>الطبيب المرتبط</th>
            <th>إجراءات</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($users)): ?>
            <tr>
                <td colspan="6" class="text-center">لا يوجد مستخدمون حتى الآن.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
            <?php $isSelf = (int) $u['id'] === (int) $_SESSION['user_id']; ?>
            <tr>
                <td><?= (int) $u['id'] ?></td>
                <td>
                    <?= htmlspecialchars($u['username']) ?>
                    <?php if ($isSelf): ?><span class="badge bg-secondary">أنت</span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string) $u['full_name']) ?></td>
                <td><?= htmlspecialchars($roleLabels[$u['role']] ?? (string) $u['role']) ?></td>
                <td><?= htmlspecialchars($u['doctor_name'] ?? '—') ?></td>
                <td>
                    <a href="update.php?id=<?= (int) $u['id'] ?>" class="btn btn-sm btn-warning">تعديل</a>
                    <?php if (!$isSelf): ?>
                        <?= deleteButton('delete.php', (int) $u['id'], 'هل أنت متأكد من حذف هذا المستخدم؟') ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Modal الإضافة -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="create.php" method="POST" class="modal-content">
            <?= csrfField() ?>
            <div class="modal-header">
                <h5 class="modal-title">إضافة مستخدم</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">اسم المستخدم</label>
                    <input type="text" name="username" class="form-control" minlength="3" maxlength="50" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password" name="password" class="form-control" minlength="8" required>
                    <div class="form-text">8 أحرف على الأقل.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">الاسم بالكامل</label>
                    <input type="text" name="full_name" class="form-control" maxlength="100">
                </div>
                <div class="mb-3">
                    <label class="form-label">الصلاحية</label>
                    <select name="role" class="form-select" required>
                        <option value="admin">مدير النظام</option>
                        <option value="receptionist" selected>موظف استقبال</option>
                        <option value="doctor">طبيب</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">ربط بطبيب (اختياري)</label>
                    <select name="doctor_id" class="form-select">
                        <option value="">-- بدون --</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
        </form>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>
