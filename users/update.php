<?php
include_once '../includes/auth.php';
requireAdmin();
include_once '../config/database.php';
$conn = getConnection();

$id = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$id) {
    header("Location: index.php?msg=" . urlencode("لم يتم تحديد المستخدم"));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? 'receptionist';
    $doctor_id = $_POST['doctor_id'] ?: null;

    if (empty($username)) {
        header("Location: update.php?id=$id&msg=" . urlencode("اسم المستخدم مطلوب"));
        exit;
    }

    // لو كتب كلمة مرور جديدة، حدّثها. لو سابها فاضية، سيب القديمة زي ما هي
    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE users SET username = :username, password = :password,
                  full_name = :full_name, role = :role, doctor_id = :doctor_id
                  WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':password', $hashedPassword);
    } else {
        $query = "UPDATE users SET username = :username,
                  full_name = :full_name, role = :role, doctor_id = :doctor_id
                  WHERE id = :id";
        $stmt = $conn->prepare($query);
    }

    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':full_name', $full_name);
    $stmt->bindParam(':role', $role);
    $stmt->bindParam(':doctor_id', $doctor_id);
    $stmt->bindParam(':id', $id);

    try {
        $stmt->execute();
        header("Location: index.php?msg=" . urlencode("تم تعديل بيانات المستخدم بنجاح"));
    } catch (PDOException $e) {
        header("Location: update.php?id=$id&msg=" . urlencode("اسم المستخدم موجود بالفعل"));
    }
    exit;
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
    header("Location: index.php?msg=" . urlencode("المستخدم غير موجود"));
    exit;
}

$doctorsStmt = $conn->prepare("SELECT * FROM doctors ORDER BY name");
$doctorsStmt->execute();
$doctors = $doctorsStmt->fetchAll();

include_once '../includes/header.php';
?>

<h3>Edit User: <?= htmlspecialchars($user['username']) ?></h3>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php endif; ?>

<form action="update.php" method="POST" class="bg-white p-4 rounded shadow-sm">
    <input type="hidden" name="id" value="<?= $user['id'] ?>">

    <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>"
            required>
    </div>

    <div class="mb-3">
        <label class="form-label">New Password (leave blank to keep the current password)</label>
        <input type="password" name="password" class="form-control">
    </div>

    <div class="mb-3">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
            <option value="receptionist" <?= $user['role'] === 'receptionist' ? 'selected' : '' ?>>Receptionist</option>
            <option value="doctor" <?= $user['role'] === 'doctor' ? 'selected' : '' ?>>Doctor</option>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Link to Doctor (optional)</label>
        <select name="doctor_id" class="form-select">
            <option value="">-- None --</option>
            <?php foreach ($doctors as $d): ?>
                <option value="<?= $d['id'] ?>" <?= $d['id'] == $user['doctor_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($d['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">Save Changes</button>
    <a href="index.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include_once '../includes/footer.php'; ?>