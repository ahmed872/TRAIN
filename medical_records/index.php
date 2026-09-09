<?php
include_once '../includes/auth.php';
// السجلات الطبية بيانات حساسة — المرضى ما بيشوفوش الجدول ده
requireRole(['admin', 'doctor']);
include_once '../config/database.php';
$conn = getConnection();

$role = currentRole();
$canManage = in_array($role, ['admin', 'doctor'], true);

$sql = 'SELECT medical_records.*, appointments.appointment_date,
               patients.name AS patient_name, doctors.name AS doctor_name
        FROM medical_records
        JOIN appointments ON medical_records.appointment_id = appointments.id
        JOIN patients ON appointments.patient_id = patients.id
        JOIN doctors ON appointments.doctor_id = doctors.id';
$params = [];

// الطبيب يشوف سجلات مرضاه هو بس
if ($role === 'doctor' && !empty($_SESSION['doctor_id'])) {
    $sql .= ' WHERE appointments.doctor_id = :doctor_id';
    $params[':doctor_id'] = (int) $_SESSION['doctor_id'];
}

$sql .= ' ORDER BY medical_records.id DESC';

$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_INT);
}
$stmt->execute();
$records = $stmt->fetchAll();

// المواعيد اللي لسه ملهاش سجل طبي، عشان القائمة المنسدلة
$apptSql = 'SELECT appointments.id, appointments.appointment_date,
                   patients.name AS patient_name, doctors.name AS doctor_name
            FROM appointments
            JOIN patients ON appointments.patient_id = patients.id
            JOIN doctors ON appointments.doctor_id = doctors.id
            LEFT JOIN medical_records ON medical_records.appointment_id = appointments.id
            WHERE medical_records.id IS NULL';
$apptParams = [];

if ($role === 'doctor' && !empty($_SESSION['doctor_id'])) {
    $apptSql .= ' AND appointments.doctor_id = :doctor_id';
    $apptParams[':doctor_id'] = (int) $_SESSION['doctor_id'];
}

$apptSql .= ' ORDER BY appointments.appointment_date DESC';

$apptStmt = $conn->prepare($apptSql);
foreach ($apptParams as $key => $value) {
    $apptStmt->bindValue($key, $value, PDO::PARAM_INT);
}
$apptStmt->execute();
$appointments = $apptStmt->fetchAll();

include_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>السجلات الطبية</h3>
    <?php if ($canManage): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ إضافة سجل طبي</button>
    <?php endif; ?>
</div>

<table class="table table-bordered table-striped bg-white align-middle">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>المريض</th>
            <th>الطبيب</th>
            <th>تاريخ الموعد</th>
            <th>التشخيص</th>
            <?php if ($canManage): ?><th>إجراءات</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($records)): ?>
            <tr>
                <td colspan="<?= $canManage ? 6 : 5 ?>" class="text-center">لا توجد سجلات طبية حتى الآن.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($records as $r): ?>
            <tr>
                <td><?= (int) $r['id'] ?></td>
                <td><?= htmlspecialchars($r['patient_name']) ?></td>
                <td><?= htmlspecialchars($r['doctor_name']) ?></td>
                <td><?= htmlspecialchars((string) $r['appointment_date']) ?></td>
                <td><?= nl2br(htmlspecialchars((string) $r['diagnosis'])) ?></td>
                <?php if ($canManage): ?>
                    <td>
                        <a href="update.php?id=<?= (int) $r['id'] ?>" class="btn btn-sm btn-warning">تعديل</a>
                        <?= deleteButton('delete.php', (int) $r['id'], 'هل أنت متأكد من حذف هذا السجل؟') ?>
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
                    <h5 class="modal-title">إضافة سجل طبي</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">الموعد</label>
                        <select name="appointment_id" class="form-select" required>
                            <option value="">-- اختر الموعد --</option>
                            <?php foreach ($appointments as $a): ?>
                                <option value="<?= (int) $a['id'] ?>">
                                    #<?= (int) $a['id'] ?> — <?= htmlspecialchars($a['patient_name']) ?>
                                    مع د. <?= htmlspecialchars($a['doctor_name']) ?>
                                    (<?= htmlspecialchars((string) $a['appointment_date']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($appointments)): ?>
                            <div class="form-text">كل المواعيد لها سجلات بالفعل.</div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">التشخيص</label>
                        <textarea name="diagnosis" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الروشتة / العلاج</label>
                        <textarea name="prescription" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ملاحظات</label>
                        <textarea name="notes" class="form-control"></textarea>
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
