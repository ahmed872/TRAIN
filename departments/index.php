<?php
include_once '../includes/auth.php';
requireLogin();
include_once '../config/database.php';
$conn = getConnection();

$query = "SELECT * FROM departments ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$departments = $stmt->fetchAll();

include_once '../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Departments</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        + Add Department
    </button>
</div>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-info"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<table class="table table-bordered table-striped bg-white">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Department Name</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($departments)): ?>
            <tr>
                <td colspan="4" class="text-center">No departments have been added yet.</td>
            </tr>
        <?php endif; ?>
        <?php foreach ($departments as $dept): ?>
            <tr>
                <td><?= $dept['id'] ?></td>
                <td><?= htmlspecialchars($dept['name']) ?></td>
                <td><?= htmlspecialchars($dept['description']) ?></td>
                <td>
                    <a href="update.php?id=<?= $dept['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                    <a href="delete.php?id=<?= $dept['id'] ?>" class="btn btn-sm btn-danger"
                        onclick="return confirm('Are you sure you want to delete this department?')">Delete</a>
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
                <h5 class="modal-title">Add Department</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Department Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>