<?php
/**
 * إنشاء حساب جديد للمرضى فقط.
 * حسابات الأطباء وموظفي الاستقبال والأدمن بيضيفها الأدمن من صفحة المستخدمين،
 * عشان محدش يقدر يمنح نفسه صلاحيات أعلى من الفورم ده.
 */

include_once '../includes/auth.php';
include_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    redirectTo('/departments/index.php');
}

$error = '';
$old = ['username' => '', 'full_name' => '', 'gender' => 'male', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $username  = trim($_POST['username'] ?? '');
    $password  = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $gender    = $_POST['gender'] ?? 'male';
    $phone     = trim($_POST['phone'] ?? '');

    $old = ['username' => $username, 'full_name' => $full_name, 'gender' => $gender, 'phone' => $phone];

    // الدور مثبّت في السيرفر، مش بيتقرا من الفورم إطلاقًا
    $role = 'patient';

    if ($username === '' || $password === '' || $full_name === '') {
        $error = 'برجاء إكمال كل الحقول المطلوبة.';
    } elseif (mb_strlen($username) < 3 || mb_strlen($username) > 50) {
        $error = 'اسم المستخدم لازم يكون بين 3 و 50 حرف.';
    } elseif (mb_strlen($password) < 8) {
        $error = 'كلمة المرور لازم تكون 8 أحرف على الأقل.';
    } elseif (!in_array($gender, ['male', 'female'], true)) {
        $error = 'قيمة النوع غير صالحة.';
    } else {
        $conn = getConnection();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        try {
            $conn->beginTransaction();

            // بننشئ سجل المريض الأول عشان نربط المستخدم بيه
            $patientStmt = $conn->prepare(
                'INSERT INTO patients (name, gender, phone) VALUES (:name, :gender, :phone)'
            );
            $patientStmt->bindParam(':name', $full_name);
            $patientStmt->bindParam(':gender', $gender);
            $patientStmt->bindParam(':phone', $phone);
            $patientStmt->execute();
            $patientId = (int) $conn->lastInsertId();

            $stmt = $conn->prepare(
                'INSERT INTO users (username, password, full_name, role, patient_id)
                 VALUES (:username, :password, :full_name, :role, :patient_id)'
            );
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':role', $role);
            $stmt->bindValue(':patient_id', $patientId, PDO::PARAM_INT);
            $stmt->execute();

            // لازم ناخد الـ id بتاع المستخدم بعد آخر INSERT بتاعه مباشرة
            $userId = (int) $conn->lastInsertId();

            $conn->commit();

            loginUser([
                'id'        => $userId,
                'username'  => $username,
                'full_name' => $full_name,
                'role'       => $role,
                'doctor_id'  => null,
                'patient_id' => $patientId,
            ]);

            redirectTo('/departments/index.php', 'تم إنشاء الحساب بنجاح.', 'success');
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log('Registration failed: ' . $e->getMessage());
            $error = 'اسم المستخدم موجود بالفعل أو تعذّر إنشاء الحساب.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب - نظام المستشفى</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars(url('/assets/css/style.css')) ?>" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center" style="min-height:100vh;">

    <div class="container" style="max-width:400px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="text-center mb-4">إنشاء حساب مريض</h4>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">الاسم بالكامل</label>
                        <input type="text" name="full_name" class="form-control"
                            value="<?= htmlspecialchars($old['full_name']) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اسم المستخدم</label>
                        <input type="text" name="username" class="form-control"
                            value="<?= htmlspecialchars($old['username']) ?>" minlength="3" maxlength="50" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">كلمة المرور</label>
                        <input type="password" name="password" class="form-control" minlength="8" required>
                        <div class="form-text">8 أحرف على الأقل.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">النوع</label>
                        <select name="gender" class="form-select">
                            <option value="male" <?= $old['gender'] === 'male' ? 'selected' : '' ?>>ذكر</option>
                            <option value="female" <?= $old['gender'] === 'female' ? 'selected' : '' ?>>أنثى</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">رقم الهاتف (اختياري)</label>
                        <input type="text" name="phone" class="form-control"
                            value="<?= htmlspecialchars($old['phone']) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">إنشاء الحساب</button>
                </form>

                <p class="text-center mt-3 mb-0">
                    <a href="<?= htmlspecialchars(url('/auth/login.php')) ?>">لدي حساب بالفعل</a>
                </p>
            </div>
        </div>
    </div>

</body>

</html>
