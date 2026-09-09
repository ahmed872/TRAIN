<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

$query = "SELECT medical_records.*, appointments.appointment_date,
                 patients.name AS patient_name, doctors.name AS doctor_name
          FROM medical_records
          JOIN appointments ON medical_records.appointment_id = appointments.id
          JOIN patients ON appointments.patient_id = patients.id
          JOIN doctors ON appointments.doctor_id = doctors.id
          ORDER BY medical_records.id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$records = $stmt->fetchAll();

// المواعيد لعرضها في القائمة المنسدلة (مع اسم المريض والدكتور للتوضيح)
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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Medical Records</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        + Add Medical Record
    </button>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-info"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<table class="table table-bordered table-striped bg-white">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Patient</th>
            <th>Doctor</th>
            <th>Appointment Date</th>
            <th>Diagnosis</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($records)): ?>
            <tr>
                <td colspan="6" class="text-center">No medical records have been added yet.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($records as $r): ?>
            <tr>
                <td><?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['patient_name']) ?></td>
                <td><?= htmlspecialchars($r['doctor_name']) ?></td>
                <td><?= htmlspecialchars($r['appointment_date']) ?></td>
                <td><?= htmlspecialchars($r['diagnosis']) ?></td>
                <td>
                    <a href="update.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="delete.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-danger"
                        onclick="return confirm('Are you sure you want to delete this record?')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Modal الإضافة -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="create.php" method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Medical Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Appointment</label>
                    <select name="appointment_id" class="form-select" required>
                        <option value="">-- Select Appointment --</option>
                        <?php foreach ($appointments as $a): ?>
                            <option value="<?= $a['id'] ?>">
                                #<?= $a['id'] ?> — <?= htmlspecialchars($a['patient_name']) ?>
                                with Dr. <?= htmlspecialchars($a['doctor_name']) ?>
                                (<?= htmlspecialchars($a['appointment_date']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Diagnosis</label>
                    <textarea name="diagnosis" class="form-control"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Prescription / Treatment</label>
                    <textarea name="prescription" class="form-control"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>