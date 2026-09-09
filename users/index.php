<?php
include_once '../includes/auth.php';
requireAdmin();
include_once '../config/database.php';
$conn = getConnection();

$query = "SELECT users.*, doctors.name AS doctor_name
          FROM users
          LEFT JOIN doctors ON users.doctor_id = doctors.id
          ORDER BY users.id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll();

$doctorsStmt = $conn->prepare("SELECT * FROM doctors ORDER BY name");
$doctorsStmt->execute();
$doctors = $doctorsStmt->fetchAll();

include_once '../includes/header.php';

$roleLabels = [
    'admin' => 'Administrator',
    'receptionist' => 'Receptionist',
    'doctor' => 'Doctor',
    'patient' => 'Patient',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Users</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        + Add User
    </button>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-info"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<table class="table table-bordered table-striped bg-white">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Username</th>
            <th>Full Name</th>
            <th>Role</th>
            <th>Linked Doctor</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($users)): ?>
            <tr>
                <td colspan="6" class="text-center">No users have been added yet.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td><?= $roleLabels[$u['role']] ?? htmlspecialchars($u['role']) ?></td>
                <td><?= htmlspecialchars($u['doctor_name'] ?? '—') ?></td>
                <td>
                    <a href="update.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="delete.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-danger"
                        onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
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
                <h5 class="modal-title">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="admin">Administrator</option>
                        <option value="receptionist" selected>Receptionist</option>
                        <option value="doctor">Doctor</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Link to Doctor (optional)</label>
                    <select name="doctor_id" class="form-select">
                        <option value="">-- None --</option>
                        <?php foreach ($doctors as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>