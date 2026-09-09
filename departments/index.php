<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

$stmt = $conn->prepare('SELECT * FROM departments ORDER BY id DESC');
$stmt->execute();
$departments = $stmt->fetchAll();

$canManage = in_array(currentRole(), ['admin', 'receptionist'], true);

include_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>الأقسام</h3>
    <?php if ($canManage): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ إضافة قسم</button>
    <?php endif; ?>
</div>

<table class="table table-bordered table-striped bg-white align-middle">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>اسم القسم</th>
            <th>الوصف</th>
            <?php if ($canManage): ?><th>إجراءات</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($departments)): ?>
            <tr>
                <td colspan="<?= $canManage ? 4 : 3 ?>" class="text-center">لا توجد أقسام مضافة حتى الآن.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($departments as $dept): ?>
            <tr>
                <td><?= (int) $dept['id'] ?></td>
                <td><?= htmlspecialchars($dept['name']) ?></td>
                <td><?= nl2br(htmlspecialchars((string) $dept['description'])) ?></td>
                <?php if ($canManage): ?>
                    <td>
                        <a href="update.php?id=<?= (int) $dept['id'] ?>" class="btn btn-sm btn-warning">تعديل</a>
                        <?= deleteButton('delete.php', (int) $dept['id'], 'هل أنت متأكد من حذف هذا القسم؟') ?>
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
                    <h5 class="modal-title">إضافة قسم</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم القسم</label>
                        <input type="text" name="name" class="form-control" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea name="description" class="form-control"></textarea>
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
