<?php
include_once '../includes/auth.php';
requireRole(['admin', 'receptionist', 'doctor']);
include_once '../config/database.php';
$conn = getConnection();

$stmt = $conn->prepare('SELECT * FROM patients ORDER BY id DESC');
$stmt->execute();
$patients = $stmt->fetchAll();

$canManage = in_array(currentRole(), ['admin', 'receptionist'], true);

$genderLabels = ['male' => 'ذكر', 'female' => 'أنثى'];

include_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>المرضى</h3>
    <?php if ($canManage): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ إضافة مريض</button>
    <?php endif; ?>
</div>

<table class="table table-bordered table-striped bg-white align-middle">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>الاسم</th>
            <th>النوع</th>
            <th>تاريخ الميلاد</th>
            <th>الهاتف</th>
            <?php if ($canManage): ?><th>إجراءات</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($patients)): ?>
            <tr>
                <td colspan="<?= $canManage ? 6 : 5 ?>" class="text-center">لا يوجد مرضى مضافون حتى الآن.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($patients as $p): ?>
            <tr>
                <td><?= (int) $p['id'] ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= htmlspecialchars($genderLabels[$p['gender']] ?? '—') ?></td>
                <td><?= htmlspecialchars((string) $p['date_of_birth']) ?></td>
                <td><?= htmlspecialchars((string) $p['phone']) ?></td>
                <?php if ($canManage): ?>
                    <td>
                        <a href="update.php?id=<?= (int) $p['id'] ?>" class="btn btn-sm btn-warning">تعديل</a>
                        <?= deleteButton('delete.php', (int) $p['id'], 'هل أنت متأكد من حذف هذا المريض؟') ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if ($canManage): ?>
    <!-- Modal الإضافة -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="create.php" method="POST" class="modal-content">
                <?= csrfField() ?>
                <div class="modal-header">
                    <h5 class="modal-title">إضافة مريض</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم المريض</label>
                        <input type="text" name="name" class="form-control" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">النوع</label>
                        <select name="gender" class="form-select" required>
                            <option value="male">ذكر</option>
                            <option value="female">أنثى</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ الميلاد</label>
                        <input type="date" name="date_of_birth" class="form-control" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الهاتف</label>
                        <input type="text" name="phone" class="form-control" maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">العنوان</label>
                        <input type="text" name="address" class="form-control" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php include_once '../includes/footer.php'; ?>
