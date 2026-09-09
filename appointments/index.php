<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

$query = "SELECT appointments.*, patients.name AS patient_name, doctors.name AS doctor_name
          FROM appointments
          JOIN patients ON appointments.patient_id = patients.id
          JOIN doctors ON appointments.doctor_id = doctors.id
          ORDER BY appointments.appointment_date DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$appointments = $stmt->fetchAll();

$patientsStmt = $conn->prepare("SELECT * FROM patients ORDER BY name");
$patientsStmt->execute();
$patients = $patientsStmt->fetchAll();

$doctorsStmt = $conn->prepare("SELECT * FROM doctors ORDER BY name");
$doctorsStmt->execute();
$doctors = $doctorsStmt->fetchAll();

include_once '../includes/header.php';

$statusLabels = [
    'scheduled' => 'Scheduled',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];
$statusClass = [
    'scheduled' => 'bg-primary',
    'completed' => 'bg-success',
    'cancelled' => 'bg-danger',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Appointments</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        + Add Appointment
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
            <th>Date and Time</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($appointments)): ?>
            <tr>
                <td colspan="6" class="text-center">No appointments have been added yet.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($appointments as $a): ?>
            <tr>
                <td><?= $a['id'] ?></td>
                <td><?= htmlspecialchars($a['patient_name']) ?></td>
                <td><?= htmlspecialchars($a['doctor_name']) ?></td>
                <td><?= htmlspecialchars($a['appointment_date']) ?></td>
                <td><span class="badge <?= $statusClass[$a['status']] ?>"><?= $statusLabels[$a['status']] ?></span></td>
                <td>
                    <a href="update.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="delete.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-danger"
                        onclick="return confirm('Are you sure you want to delete this appointment?')">Delete</a>
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
                <h5 class="modal-title">Add Appointment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Patient</label>
                    <select name="patient_id" class="form-select" required>
                        <option value="">-- Select Patient --</option>
                        <?php foreach ($patients as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Doctor</label>
                    <select name="doctor_id" class="form-select" required>
                        <option value="">-- Select Doctor --</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Appointment Date and Time</label>
                    <input type="datetime-local" name="appointment_date" class="form-control" required>
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