<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    header("Location: index.php?msg=" . urlencode("لم يتم تحديد الموعد"));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_POST['patient_id'] ?? '';
    $doctor_id = $_POST['doctor_id'] ?? '';
    $appointment_date = $_POST['appointment_date'] ?? '';
    $status = $_POST['status'] ?? 'scheduled';
    $notes = trim($_POST['notes'] ?? '');

    if (empty($patient_id) || empty($doctor_id) || empty($appointment_date)) {
        header("Location: update.php?id=$id&msg=" . urlencode("المريض والطبيب وتاريخ الموعد مطلوبين"));
        exit;
    }

    // التحقق من عدم وجود موعد تاني لنفس الطبيب في نفس التوقيت (باستثناء الموعد الحالي نفسه)
    if ($status !== 'cancelled') {
        $checkQuery = "SELECT id FROM appointments
                       WHERE doctor_id = :doctor_id
                       AND appointment_date = :appointment_date
                       AND status != 'cancelled'
                       AND id != :id";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindParam(':doctor_id', $doctor_id);
        $checkStmt->bindParam(':appointment_date', $appointment_date);
        $checkStmt->bindParam(':id', $id);
        $checkStmt->execute();

        if ($checkStmt->fetch()) {
            header("Location: update.php?id=$id&msg=" . urlencode("هذا الطبيب لديه موعد آخر في نفس التوقيت"));
            exit;
        }
    }

    $query = "UPDATE appointments SET patient_id = :patient_id, doctor_id = :doctor_id,
              appointment_date = :appointment_date, status = :status, notes = :notes
              WHERE id = :id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':patient_id', $patient_id);
    $stmt->bindParam(':doctor_id', $doctor_id);
    $stmt->bindParam(':appointment_date', $appointment_date);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':notes', $notes);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header("Location: index.php?msg=" . urlencode("تم تعديل الموعد بنجاح"));
    exit;
}

$stmt = $conn->prepare("SELECT * FROM appointments WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$appointment = $stmt->fetch();

if (!$appointment) {
    header("Location: index.php?msg=" . urlencode("الموعد غير موجود"));
    exit;
}

$patientsStmt = $conn->prepare("SELECT * FROM patients ORDER BY name");
$patientsStmt->execute();
$patients = $patientsStmt->fetchAll();

$doctorsStmt = $conn->prepare("SELECT * FROM doctors ORDER BY name");
$doctorsStmt->execute();
$doctors = $doctorsStmt->fetchAll();

include_once '../includes/header.php';

// تجهيز التاريخ لصيغة datetime-local
$dateValue = str_replace(' ', 'T', substr($appointment['appointment_date'], 0, 16));
?>

<h3>Edit Appointment #<?= $appointment['id'] ?></h3>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<form action="update.php" method="POST" class="bg-white p-4 rounded shadow-sm">
    <input type="hidden" name="id" value="<?= $appointment['id'] ?>">

    <div class="mb-3">
        <label class="form-label">Patient</label>
        <select name="patient_id" class="form-select" required>
            <?php foreach ($patients as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $p['id'] == $appointment['patient_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($p['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Doctor</label>
        <select name="doctor_id" class="form-select" required>
            <?php foreach ($doctors as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $d['id'] == $appointment['doctor_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Appointment Date and Time</label>
        <input type="datetime-local" name="appointment_date" class="form-control"
            value="<?= htmlspecialchars($dateValue) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            <option value="scheduled" <?= $appointment['status'] === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
            <option value="completed" <?= $appointment['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
            <option value="cancelled" <?= $appointment['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control"><?= htmlspecialchars($appointment['notes']) ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include_once '../includes/footer.php'; ?>