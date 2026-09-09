<?php
include_once '../includes/auth.php';
include_once '../config/database.php';

// Redirect authenticated users to the departments page.
if (isset($_SESSION['user_id'])) {
    header("Location: /hospital-system/departments/index.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? '';
    $gender = $_POST['gender'] ?? 'male';
    $phone = trim($_POST['phone'] ?? '');

    $allowedRoles = [
        'patient' => 'Patient',
        'doctor' => 'Doctor',
        'receptionist' => 'Receptionist',
    ];

    if (empty($username) || empty($password) || empty($full_name) || !isset($allowedRoles[$role])) {
        $error = 'Please complete all fields and select an account type.';
    } else {
        $conn = getConnection();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, role, doctor_id)
                                    VALUES (:username, :password, :full_name, :role, NULL)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':role', $role);
            $stmt->execute();

            if ($role === 'patient') {
                $patientStmt = $conn->prepare("INSERT INTO patients (name, gender, phone)
                                               VALUES (:name, :gender, :phone)");
                $patientStmt->bindParam(':name', $full_name);
                $patientStmt->bindParam(':gender', $gender);
                $patientStmt->bindParam(':phone', $phone);
                $patientStmt->execute();
            }

            $userId = $conn->lastInsertId();
            $conn->commit();
            $_SESSION['user_id'] = $userId;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['full_name'] = $full_name;
            header("Location: /hospital-system/departments/index.php");
            exit;
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = 'The username already exists or the account could not be created.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <title>Create Account - Hospital System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center" style="min-height:100vh;">

    <div class="container" style="max-width:400px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="text-center mb-4">Create an Account</h4>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Full name</label>
                        <input type="text" name="full_name" class="form-control" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">I am registering as</label>
                        <select name="role" class="form-select" required>
                            <option value="">Select account type</option>
                            <option value="patient">Patient</option>
                            <option value="doctor">Doctor</option>
                            <option value="receptionist">Receptionist</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone (optional)</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Create account and continue</button>
                </form>
            </div>
        </div>
    </div>

</body>

</html>