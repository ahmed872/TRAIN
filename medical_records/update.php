<?php
include_once '../includes/auth.php';
requireRole(['admin', 'doctor']);
include_once '../config/database.php';
$conn = getConnection();

$id = validId($_GET['id'] ?? $_POST['id'] ?? null);

if ($id === null) {
    redirectTo('/medical_records/index.php', 'لم يتم تحديد السجل', 'warning');
}

/**
 * يتأكد إن الطبيب الحالي هو صاحب الموعد المرتبط بالسجل ده.
 */
function assertRecordIsEditable(PDO $conn, int $recordId): void
{
    if (currentRole() !== 'doctor') {
        return;
    }

    $stmt = $conn->prepare(
        'SELECT appointments.doctor_id
         FROM medical_records
         JOIN appointments ON medical_records.appointment_id = appointments.id
         WHERE medical_records.id = :id'
    );
    $stmt->bindValue(':id', $recordId, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if (!$row || (int) $row['doctor_id'] !== (int) ($_SESSION['doctor_id'] ?? 0)) {
        redirectTo('/medical_records/index.php', 'غير مصرح لك بتعديل هذا السجل', 'danger');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();
    assertRecordIsEditable($conn, $id);

    $appointment_id = validId($_POST['appointment_id'] ?? null);
    $diagnosis      = trim($_POST['diagnosis'] ?? '');
    $prescription   = trim($_POST['prescription'] ?? '');
    $notes          = trim($_POST['notes'] ?? '');

    if ($appointment_id === null) {
        redirectTo('/medical_records/update.php?id=' . $id, 'لازم تختار الموعد', 'warning');
    }

    try {
        $stmt = $conn->prepare(
            'UPDATE medical_records SET appointment_id = :appointment_id, diagnosis = :diagnosis,
             prescription = :prescription, notes = :notes WHERE id = :id'
        );
        $stmt->bindValue(':appointment_id', $appointment_id, PDO::PARAM_INT);
        $stmt->bindParam(':diagnosis', $diagnosis);
        $stmt->bindParam(':prescription', $prescription);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            redirectTo('/medical_records/update.php?id=' . $id, 'هذا الموعد له سجل طبي بالفعل', 'warning');
        }

        error_log('Medical record update failed: ' . $e->getMessage());
        redirectTo('/medical_records/update.php?id=' . $id, 'تعذّر حفظ التعديل', 'danger');
    }

    redirectTo('/medical_records/index.php', 'تم تعديل السجل الطبي بنجاح', 'success');
}

assertRecordIsEditable($conn, $id);

$stmt = $conn->prepare('SELECT * FROM medical_records WHERE id = :id');
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$record = $stmt->fetch();

if (!$record) {
    redirectTo('/medical_records/index.php', 'السجل غير موجود', 'warning');
}

// المواعيد المتاحة = المواعيد بدون سجل + الموعد المرتبط بالسجل الحالي
$apptStmt = $conn->prepare(
    'SELECT appointments.id, appointments.appointment_date,
            patients.name AS patient_name, doctors.name AS doctor_name
     FROM appointments
     JOIN patients ON appointments.patient_id = patients.id
     JOIN doctors ON appointments.doctor_id = doctors.id
     LEFT JOIN medical_records ON medical_records.appointment_id = appointments.id
     WHERE medical_records.id IS NULL OR medical_records.id = :id
     ORDER BY appointments.appointment_date DESC'
);
$apptStmt->bindValue(':id', $id, PDO::PARAM_INT);
$apptStmt->execute();
$appointments = $apptStmt->fetchAll();

include_once '../includes/header.php';
?>

<h3>تعديل السجل الطبي رقم <?= (int) $record['id'] ?></h3>

<form action="update.php" method="POST" class="bg-white p-4 rounded shadow-sm">
    <?= csrfField() ?>
    <input type="hidden" name="id" value="<?= (int) $record['id'] ?>">

    <div class="mb-3">
        <label class="form-label">الموعد</label>
        <select name="appointment_id" class="form-select" required>
            <?php foreach ($appointments as $a): ?>
                <option value="<?= (int) $a['id'] ?>" <?= (int) $a['id'] === (int) $record['appointment_id'] ? 'selected' : '' ?>>
                    #<?= (int) $a['id'] ?> — <?= htmlspecialchars($a['patient_name']) ?>
                    مع د. <?= htmlspecialchars($a['doctor_name']) ?>
                    (<?= htmlspecialchars((string) $a['appointment_date']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">التشخيص</label>
        <textarea name="diagnosis" class="form-control"><?= htmlspecialchars((string) $record['diagnosis']) ?></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">الروشتة / العلاج</label>
        <textarea name="prescription" class="form-control"><?= htmlspecialchars((string) $record['prescription']) ?></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">ملاحظات</label>
        <textarea name="notes" class="form-control"><?= htmlspecialchars((string) $record['notes']) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
    <a href="index.php" class="btn btn-secondary">إلغاء</a>
</form>

<?php include_once '../includes/footer.php'; ?>
