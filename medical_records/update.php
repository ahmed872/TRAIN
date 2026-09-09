<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    header("Location: index.php?msg=" . urlencode("لم يتم تحديد السجل"));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'] ?? '';
    $diagnosis = trim($_POST['diagnosis'] ?? '');
    $prescription = trim($_POST['prescription'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if (empty($appointment_id)) {
        header("Location: update.php?id=$id&msg=" . urlencode("لازم تختار الموعد"));
        exit;
    }

    $query = "UPDATE medical_records SET appointment_id = :appointment_id, diagnosis = :diagnosis,
              prescription = :prescription, notes = :notes WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':appointment_id', $appointment_id);
    $stmt->bindParam(':diagnosis', $diagnosis);
    $stmt->bindParam(':prescription', $prescription);
    $stmt->bindParam(':notes', $notes);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header("Location: index.php?msg=" . urlencode("تم تعديل السجل الطبي بنجاح"));
    exit;
}

$stmt = $conn->prepare("SELECT * FROM medical_records WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$record = $stmt->fetch();

if (!$record) {
    header("Location: index.php?msg=" . urlencode("السجل غير موجود"));
    exit;
}

$apptStmt = $conn->prepare(
    "SELECT appointments.id, appointments.appointment_date,
            patients.name AS patient_name, doctors.name AS doctor_name
     FROM appointments
     JOIN patients ON appointments.patient_id = patients.id
     JOIN doctors ON appointments.doctor_id = doctors.id
     ORDER BY appointments.appointment_date DESC"
);
$apptStmt->execute();
$appointments = $apptStmt->fetchAll();

include_once '../includes/header.php';
?>

<h3>Edit Medical Record #<?= $record['id'] ?></h3>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<form action="update.php" method="POST" class="bg-white p-4 rounded shadow-sm">
    <input type="hidden" name="id" value="<?= $record['id'] ?>">

    <div class="mb-3">
        <label class="form-label">Appointment</label>
        <select name="appointment_id" class="form-select" required>
            <?php foreach ($appointments as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $a['id'] == $record['appointment_id'] ? 'selected' : '' ?>>
                    #<?= $a['id'] ?> — <?= htmlspecialchars($a['patient_name']) ?>
                    with Dr. <?= htmlspecialchars($a['doctor_name']) ?>
                    (<?= htmlspecialchars($a['appointment_date']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Diagnosis</label>
        <textarea name="diagnosis" class="form-control"><?= htmlspecialchars($record['diagnosis']) ?></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">Prescription / Treatment</label>
        <textarea name="prescription" class="form-control"><?= htmlspecialchars($record['prescription']) ?></textarea>
    </div>

    <div class="mb-3">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control"><?= htmlspecialchars($record['notes']) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include_once '../includes/footer.php'; ?>