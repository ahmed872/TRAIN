<?php
/**
 * صفحة تسجيل الدخول: بتتحقق من اسم المستخدم وكلمة المرور المخزنة (hash)
 * وبتفتح الجلسة لو البيانات صح.
 */

include_once '../includes/auth.php';
include_once '../config/database.php';

// المستخدم المسجل دخوله مالوش لازمة هنا
if (isset($_SESSION['user_id'])) {
    redirectTo('/departments/index.php');
}

$error = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'برجاء إدخال اسم المستخدم وكلمة المرور.';
    } else {
        $conn = getConnection();
        $stmt = $conn->prepare(
            'SELECT id, username, password, full_name, role, doctor_id, patient_id
             FROM users WHERE username = :username LIMIT 1'
        );
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch();

        // بنتحقق من الهاش حتى لو المستخدم مش موجود، عشان وقت الرد ما يفضحش
        // إن الاسم موجود ولا لأ (timing attack)، والرسالة عامة للسبب نفسه.
        $storedHash = $user['password'] ?? '$2y$12$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidix';

        if ($user && password_verify($password, $storedHash)) {
            // لو إعدادات التشفير اتغيرت، نعيد تخزين الهاش بالصيغة الجديدة
            if (password_needs_rehash($storedHash, PASSWORD_DEFAULT)) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $rehash = $conn->prepare('UPDATE users SET password = :password WHERE id = :id');
                $rehash->bindParam(':password', $newHash);
                $rehash->bindValue(':id', (int) $user['id'], PDO::PARAM_INT);
                $rehash->execute();
            }

            loginUser($user);
            redirectTo('/departments/index.php');
        } else {
            password_verify($password, $storedHash);
            $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
        }
    }
}

$flash = takeFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - نظام المستشفى</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars(url('/assets/css/style.css')) ?>" rel="stylesheet">
</head>

<body class="bg-light d-flex align-items-center" style="min-height:100vh;">

    <div class="container" style="max-width:400px;">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="text-center mb-4">تسجيل الدخول</h4>

                <?php if ($flash): ?>
                    <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
                        <?= htmlspecialchars($flash['text']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label">اسم المستخدم</label>
                        <input type="text" name="username" class="form-control"
                            value="<?= htmlspecialchars($username) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">كلمة المرور</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">دخول</button>
                </form>

                <p class="text-center mt-3 mb-0">
                    <a href="<?= htmlspecialchars(url('/auth/register.php')) ?>">إنشاء حساب مريض جديد</a>
                </p>
            </div>
        </div>
    </div>

</body>

</html>
