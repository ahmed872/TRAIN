<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

// جلب الأطباء مع اسم القسم بتاعهم (JOIN)
$query = "SELECT doctors.*, departments.name AS department_name
          FROM doctors
          LEFT JOIN departments ON doctors.department_id = departments.id
          ORDER BY doctors.id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$doctors = $stmt->fetchAll();

// جلب الأقسام عشان القائمة المنسدلة في مودال الإضافة
$deptStmt = $conn->prepare("SELECT * FROM departments ORDER BY name");
$deptStmt->execute();
$departments = $deptStmt->fetchAll();

include_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Doctors</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        + Add Doctor
    </button>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-info"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<table class="table table-bordered table-striped bg-white">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Specialization</th>
            <th>Department</th>
            <th>Phone</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($doctors)): ?>
            <tr>
                <td colspan="6" class="text-center">No doctors have been added yet.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($doctors as $doc): ?>
            <tr>
                <td><?= $doc['id'] ?></td>
                <td><?= htmlspecialchars($doc['name']) ?></td>
                <td><?= htmlspecialchars($doc['specialization']) ?></td>
                <td><?= htmlspecialchars($doc['department_name'] ?? '—') ?></td>
                <td><?= htmlspecialchars($doc['phone']) ?></td>
                <td>
                    <a href="update.php?id=<?= $doc['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="delete.php?id=<?= $doc['id'] ?>" class="btn btn-sm btn-danger"
                        onclick="return confirm('Are you sure you want to delete this doctor?')">Delete</a>
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
                <h5 class="modal-title">Add Doctor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Doctor Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Specialization</label>
                    <input type="text" name="specialization" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Department</label>
                    <select name="department_id" class="form-select">
                        <option value="">-- Select Department --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>