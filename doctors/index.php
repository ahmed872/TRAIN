<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

// جلب الأطباء مع اسم القسم بتاعهم (JOIN)
$stmt = $conn->prepare(
    'SELECT doctors.*, departments.name AS department_name
     FROM doctors
     LEFT JOIN departments ON doctors.department_id = departments.id
     ORDER BY doctors.id DESC'
);
$stmt->execute();
$doctors = $stmt->fetchAll();

// جلب الأقسام عشان القائمة المنسدلة في مودال الإضافة
$deptStmt = $conn->prepare('SELECT id, name FROM departments ORDER BY name');
$deptStmt->execute();
$departments = $deptStmt->fetchAll();

$canManage = in_array(currentRole(), ['admin', 'receptionist'], true);

include_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>الأطباء</h3>
    <?php if ($canManage): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ إضافة طبيب</button>
    <?php endif; ?>
</div>

<table class="table table-bordered table-striped bg-white align-middle">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>الاسم</th>
            <th>التخصص</th>
            <th>القسم</th>
            <th>الهاتف</th>
            <?php if ($canManage): ?><th>إجراءات</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($doctors)): ?>
            <tr>
                <td colspan="<?= $canManage ? 6 : 5 ?>" class="text-center">لا يوجد أطباء مضافون حتى الآن.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($doctors as $doc): ?>
            <tr>
                <td><?= (int) $doc['id'] ?></td>
                <td><?= htmlspecialchars($doc['name']) ?></td>
                <td><?= htmlspecialchars((string) $doc['specialization']) ?></td>
                <td><?= htmlspecialchars($doc['department_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars((string) $doc['phone']) ?></td>
                <?php if ($canManage): ?>
                    <td>
                        <a href="update.php?id=<?= (int) $doc['id'] ?>" class="btn btn-sm btn-warning">تعديل</a>
                        <?= deleteButton('delete.php', (int) $doc['id'], 'هل أنت متأكد من حذف هذا الطبيب؟') ?>
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
                    <h5 class="modal-title">إضافة طبيب</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم الطبيب</label>
                        <input type="text" name="name" class="form-control" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">التخصص</label>
                        <input type="text" name="specialization" class="form-control" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">القسم</label>
                        <select name="department_id" class="form-select">
                            <option value="">-- اختر القسم --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= (int) $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الهاتف</label>
                        <input type="text" name="phone" class="form-control" maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control" maxlength="100">
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
