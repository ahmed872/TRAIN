<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

$role = currentRole();
$canManage = in_array($role, ['admin', 'receptionist'], true);

// الطبيب يشوف مواعيده هو بس، والمريض يشوف مواعيده هو بس
$sql = 'SELECT appointments.*, patients.name AS patient_name, doctors.name AS doctor_name
        FROM appointments
        JOIN patients ON appointments.patient_id = patients.id
        JOIN doctors ON appointments.doctor_id = doctors.id';
$params = [];

if ($role === 'doctor' && !empty($_SESSION['doctor_id'])) {
    $sql .= ' WHERE appointments.doctor_id = :scope_id';
    $params[':scope_id'] = (int) $_SESSION['doctor_id'];
} elseif ($role === 'patient' && !empty($_SESSION['patient_id'])) {
    $sql .= ' WHERE appointments.patient_id = :scope_id';
    $params[':scope_id'] = (int) $_SESSION['patient_id'];
}

$sql .= ' ORDER BY appointments.appointment_date DESC';

$stmt = $conn->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, PDO::PARAM_INT);
}
$stmt->execute();
$appointments = $stmt->fetchAll();

$patients = [];
$doctors  = [];
if ($canManage) {
    $patientsStmt = $conn->prepare('SELECT id, name FROM patients ORDER BY name');
    $patientsStmt->execute();
    $patients = $patientsStmt->fetchAll();

    $doctorsStmt = $conn->prepare('SELECT id, name FROM doctors ORDER BY name');
    $doctorsStmt->execute();
    $doctors = $doctorsStmt->fetchAll();
}

include_once '../includes/header.php';

$statusLabels = [
    'scheduled' => 'محجوز',
    'completed' => 'مكتمل',
    'cancelled' => 'ملغي',
];
$statusClass = [
    'scheduled' => 'bg-primary',
    'completed' => 'bg-success',
    'cancelled' => 'bg-danger',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>المواعيد</h3>
    <?php if ($canManage): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ إضافة موعد</button>
    <?php endif; ?>
</div>

<table class="table table-bordered table-striped bg-white align-middle">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>المريض</th>
            <th>الطبيب</th>
            <th>التاريخ والوقت</th>
            <th>الحالة</th>
            <?php if ($canManage): ?><th>إجراءات</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($appointments)): ?>
            <tr>
                <td colspan="<?= $canManage ? 6 : 5 ?>" class="text-center">لا توجد مواعيد حتى الآن.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($appointments as $a): ?>
            <?php $status = (string) ($a['status'] ?? ''); ?>
            <tr>
                <td><?= (int) $a['id'] ?></td>
                <td><?= htmlspecialchars($a['patient_name']) ?></td>
                <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                <td><?= htmlspecialchars((string) $a['appointment_date']) ?></td>
                <td>
                    <span class="badge <?= $statusClass[$status] ?? 'bg-secondary' ?>">
                        <?= htmlspecialchars($statusLabels[$status] ?? 'غير معروف') ?>
                    </span>
                </td>
                <?php if ($canManage): ?>
                    <td>
                        <a href="update.php?id=<?= (int) $a['id'] ?>" class="btn btn-sm btn-warning">تعديل</a>
                        <?= deleteButton('delete.php', (int) $a['id'], 'هل أنت متأكد من حذف هذا الموعد؟') ?>
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
                    <h5 class="modal-title">إضافة موعد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">المريض</label>
                        <select name="patient_id" class="form-select" required>
                            <option value="">-- اختر المريض --</option>
                            <?php foreach ($patients as $p): ?>
                                <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الطبيب</label>
                        <select name="doctor_id" class="form-select" required>
                            <option value="">-- اختر الطبيب --</option>
                            <?php foreach ($doctors as $d): ?>
                                <option value="<?= (int) $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">تاريخ ووقت الموعد</label>
                        <input type="datetime-local" name="appointment_date" class="form-control" required>
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
