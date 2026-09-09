<?php
include_once '../includes/auth.php';
requireRole(['admin', 'receptionist']);
include_once '../config/database.php';
include_once '../includes/validation.php';
$conn = getConnection();

$id = validId($_GET['id'] ?? $_POST['id'] ?? null);

if ($id === null) {
    redirectTo('/appointments/index.php', 'لم يتم تحديد الموعد', 'warning');
}

$allowedStatuses = ['scheduled', 'completed', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $patient_id       = validId($_POST['patient_id'] ?? null);
    $doctor_id        = validId($_POST['doctor_id'] ?? null);
    $appointment_date = normalizeDateTime($_POST['appointment_date'] ?? null);
    $status           = $_POST['status'] ?? 'scheduled';
    $notes            = trim($_POST['notes'] ?? '');

    if ($patient_id === null || $doctor_id === null || $appointment_date === null) {
        redirectTo('/appointments/update.php?id=' . $id, 'المريض والطبيب وتاريخ الموعد مطلوبين وبصيغة صحيحة', 'warning');
    }

    if (!in_array($status, $allowedStatuses, true)) {
        redirectTo('/appointments/update.php?id=' . $id, 'حالة الموعد غير صالحة', 'warning');
    }

    try {
        $stmt = $conn->prepare(
            'UPDATE appointments SET patient_id = :patient_id, doctor_id = :doctor_id,
             appointment_date = :appointment_date, status = :status, notes = :notes
             WHERE id = :id'
        );
        $stmt->bindValue(':patient_id', $patient_id, PDO::PARAM_INT);
        $stmt->bindValue(':doctor_id', $doctor_id, PDO::PARAM_INT);
        $stmt->bindParam(':appointment_date', $appointment_date);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            redirectTo('/appointments/update.php?id=' . $id, 'هذا الطبيب لديه موعد آخر في نفس التوقيت', 'warning');
        }

        error_log('Appointment update failed: ' . $e->getMessage());
        redirectTo('/appointments/update.php?id=' . $id, 'تعذّر حفظ التعديل', 'danger');
    }

    redirectTo('/appointments/index.php', 'تم تعديل الموعد بنجاح', 'success');
}

$stmt = $conn->prepare('SELECT * FROM appointments WHERE id = :id');
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$appointment = $stmt->fetch();

if (!$appointment) {
    redirectTo('/appointments/index.php', 'الموعد غير موجود', 'warning');
}

$patientsStmt = $conn->prepare('SELECT id, name FROM patients ORDER BY name');
$patientsStmt->execute();
$patients = $patientsStmt->fetchAll();

$doctorsStmt = $conn->prepare('SELECT id, name FROM doctors ORDER BY name');
$doctorsStmt->execute();
$doctors = $doctorsStmt->fetchAll();

include_once '../includes/header.php';

// تجهيز التاريخ لصيغة datetime-local
$dateValue = str_replace(' ', 'T', substr((string) $appointment['appointment_date'], 0, 16));

$statusLabels = ['scheduled' => 'محجوز', 'completed' => 'مكتمل', 'cancelled' => 'ملغي'];
?>

<h3>تعديل الموعد رقم <?= (int) $appointment['id'] ?></h3>

<form action="update.php" method="POST" class="bg-white p-4 rounded shadow-sm">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= (int) $appointment['id'] ?>">

    <div class="mb-3">
        <label class="form-label">المريض</label>
        <select name="patient_id" class="form-select" required>
            <?php foreach ($patients as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) $appointment['patient_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">الطبيب</label>
        <select name="doctor_id" class="form-select" required>
            <?php foreach ($doctors as $d): ?>
                <option value="<?= (int) $d['id'] ?>" <?= (int) $d['id'] === (int) $appointment['doctor_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">تاريخ ووقت الموعد</label>
        <input type="datetime-local" name="appointment_date" class="form-control"
            value="<?= htmlspecialchars($dateValue) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select">
            <?php foreach ($statusLabels as $value => $label): ?>
                <option value="<?= $value ?>" <?= $appointment['status'] === $value ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">ملاحظات</label>
        <textarea name="notes" class="form-control"><?= htmlspecialchars((string) $appointment['notes']) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
    <a href="index.php" class="btn btn-secondary">إلغاء</a>
</form>

<?php include_once '../includes/footer.php'; ?>
