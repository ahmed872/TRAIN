<?php
/**
 * صفحة تشخيص سريعة: بتقول لك المسارات والداتابيز والحساب الحالي.
 * افتحها من المتصفح: http://localhost/hospital-system/tools/diagnose.php
 * امسح الملف ده قبل أي نشر حقيقي.
 */

include_once __DIR__ . '/../includes/auth.php';
include_once __DIR__ . '/../config/database.php';

header('Content-Type: text/html; charset=utf-8');

function row(string $label, string $value, ?bool $ok = null): void
{
    $color = $ok === null ? '#333' : ($ok ? '#137333' : '#c5221f');
    printf(
        '<tr><td style="padding:6px 12px;border-bottom:1px solid #eee">%s</td>'
        . '<td style="padding:6px 12px;border-bottom:1px solid #eee;color:%s;font-family:monospace">%s</td></tr>',
        htmlspecialchars($label),
        $color,
        htmlspecialchars($value)
    );
}

echo '<html dir="rtl"><meta charset="utf-8"><body style="font-family:sans-serif;padding:24px">';
echo '<h2>تشخيص النظام</h2><table style="border-collapse:collapse;min-width:640px">';

// ---------- المسارات ----------
row('BASE_URL المحسوبة', BASE_URL === '' ? '(فارغة — المشروع في جذر الموقع)' : BASE_URL);
row('رابط صفحة الدخول', url('/auth/login.php'));
row('DOCUMENT_ROOT', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '—'));
row('SCRIPT_NAME', (string) ($_SERVER['SCRIPT_NAME'] ?? '—'));

$expected = url('/auth/login.php');
$looksRight = str_ends_with($expected, '/auth/login.php');
row('هل الروابط سليمة؟', $looksRight ? 'نعم' : 'لا', $looksRight);

// ---------- الجلسة ----------
echo '<tr><td colspan="2" style="padding:14px 12px 4px;font-weight:bold">الجلسة</td></tr>';
$loggedIn = isset($_SESSION['user_id']);
row('مسجّل دخول؟', $loggedIn ? 'نعم' : 'لا', $loggedIn);
if ($loggedIn) {
    row('اسم المستخدم', (string) ($_SESSION['username'] ?? '—'));
    $role = currentRole();
    row('الصلاحية', $role);
    row('يقدر يضيف/يعدّل؟', in_array($role, ['admin', 'receptionist'], true) ? 'نعم' : 'لا — الأزرار مخفية',
        in_array($role, ['admin', 'receptionist'], true));
    row('يقدر يحذف؟', $role === 'admin' ? 'نعم' : 'لا — الحذف للمدير فقط', $role === 'admin');
}

// ---------- قاعدة البيانات ----------
echo '<tr><td colspan="2" style="padding:14px 12px 4px;font-weight:bold">قاعدة البيانات</td></tr>';
try {
    $conn = getConnection();
    row('الاتصال', 'ناجح', true);

    $tables = $conn->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    row('الجداول', implode(', ', $tables) ?: '(لا يوجد)', count($tables) === 6);

    $cols = $conn->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    $hasPatientId = in_array('patient_id', $cols, true);
    row('العمود users.patient_id', $hasPatientId ? 'موجود' : 'ناقص — شغّل migrate.sql', $hasPatientId);

    $users = $conn->query('SELECT username, role, password FROM users ORDER BY id')->fetchAll();
    row('عدد المستخدمين', (string) count($users), count($users) > 0);
    foreach ($users as $u) {
        $usable = (bool) password_get_info((string) $u['password'])['algo'];
        row('  • ' . $u['username'] . ' (' . $u['role'] . ')',
            $usable ? 'كلمة المرور صالحة' : 'كلمة المرور غير مشفّرة — لن تعمل', $usable);
    }
} catch (Throwable $e) {
    row('الاتصال', 'فشل: ' . $e->getMessage(), false);
}

echo '</table><p style="margin-top:20px"><a href="' . htmlspecialchars(url('/auth/login.php')) . '">اذهب لصفحة الدخول</a></p>';
echo '</body></html>';
