<?php
// الملف ده بيتضمّن بعد includes/auth.php، فالجلسة والدوال المساعدة جاهزة.
$currentRole = currentRole();
$displayName = ($_SESSION['full_name'] ?? '') !== ''
    ? $_SESSION['full_name']
    : ($_SESSION['username'] ?? '');
$flash = takeFlash();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام إدارة المستشفى</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars(url('/assets/css/style.css')) ?>" rel="stylesheet">
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="<?= htmlspecialchars(url('/departments/index.php')) ?>">نظام المستشفى</a>
            <div class="navbar-nav me-auto">
                <?php
                // بنعرض بس الروابط اللي دور المستخدم الحالي مسموح له يفتحها،
                // عشان ما يدوسش على لينك ويترمي بره برسالة "غير مصرح".
                $navLinks = [
                    ['/departments/index.php',     'الأقسام',        ROLES],
                    ['/doctors/index.php',         'الأطباء',        ROLES],
                    ['/patients/index.php',        'المرضى',         ['admin', 'receptionist', 'doctor']],
                    ['/appointments/index.php',    'المواعيد',       ROLES],
                    ['/medical_records/index.php', 'السجلات الطبية', ['admin', 'doctor']],
                    ['/users/index.php',           'المستخدمون',     ['admin']],
                ];
                foreach ($navLinks as [$path, $label, $allowedRoles]):
                    if (!in_array($currentRole, $allowedRoles, true)) {
                        continue;
                    }
                    ?>
                    <a class="nav-link text-white" href="<?= htmlspecialchars(url($path)) ?>"><?= $label ?></a>
                <?php endforeach; ?>
            </div>
            <?php if (isset($_SESSION['username'])): ?>
                <div class="navbar-nav">
                    <span class="nav-link text-white-50"><?= htmlspecialchars($displayName) ?></span>
                    <a class="nav-link text-white" href="<?= htmlspecialchars(url('/auth/logout.php')) ?>">تسجيل الخروج</a>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <div class="container mb-5">

        <?php if ($flash): ?>
            <div class="alert alert-<?= htmlspecialchars($flash['type']) ?>">
                <?= htmlspecialchars($flash['text']) ?>
            </div>
        <?php endif; ?>
