<?php
/**
 * أداة إعادة تعيين كلمة مرور مستخدم من سطر الأوامر.
 *
 *   php tools/reset-password.php --list
 *   php tools/reset-password.php admin "كلمة المرور الجديدة"
 *   php tools/reset-password.php admin "كلمة المرور" --create
 *
 * بتستخدم password_hash زي باقي النظام، فالحساب يقدر يسجّل دخول بعدها عادي.
 * مفيدة كمان لو عندك حسابات قديمة كلمات مرورها متخزنة كنص عادي أو MD5،
 * لأن password_verify مش بتقبل غير هاش bcrypt.
 */

// الملف ده جوه مجلد الويب، فلازم يرفض الاشتغال عن طريق المتصفح.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('هذه الأداة تعمل من سطر الأوامر فقط.');
}

include_once __DIR__ . '/../config/database.php';

$args   = array_slice($argv, 1);
$create = in_array('--create', $args, true);
$list   = in_array('--list', $args, true);
$args   = array_values(array_filter($args, static fn($a) => !str_starts_with($a, '--')));

$conn = getConnection();

// --list: بيعرض المستخدمين وحالة كلمة المرور بتاع كل واحد
if ($list) {
    $rows = $conn->query('SELECT id, username, full_name, role, password FROM users ORDER BY id')->fetchAll();

    if (!$rows) {
        exit("لا يوجد أي مستخدمين في قاعدة البيانات.\n");
    }

    printf("%-4s %-20s %-14s %s\n", 'id', 'username', 'role', 'كلمة المرور');
    echo str_repeat('-', 66), "\n";

    foreach ($rows as $r) {
        // الهاش الصالح بيبدأ بـ $2y$ (bcrypt) أو $argon2
        $info  = password_get_info((string) $r['password']);
        $state = $info['algo'] ? 'مشفّرة — تعمل' : 'غير مشفّرة — لن تعمل';
        printf("%-4d %-20s %-14s %s\n", $r['id'], $r['username'], $r['role'], $state);
    }

    exit(0);
}

if (count($args) < 2) {
    fwrite(STDERR, "الاستخدام:\n"
        . "  php tools/reset-password.php --list\n"
        . "  php tools/reset-password.php <username> <new-password> [--create]\n");
    exit(1);
}

[$username, $password] = $args;

if (mb_strlen($password) < 8) {
    fwrite(STDERR, "كلمة المرور لازم تكون 8 أحرف على الأقل.\n");
    exit(1);
}

$stmt = $conn->prepare('SELECT id FROM users WHERE username = :username');
$stmt->bindParam(':username', $username);
$stmt->execute();
$user = $stmt->fetch();

$hash = password_hash($password, PASSWORD_DEFAULT);

if ($user) {
    $update = $conn->prepare('UPDATE users SET password = :password WHERE id = :id');
    $update->bindParam(':password', $hash);
    $update->bindValue(':id', (int) $user['id'], PDO::PARAM_INT);
    $update->execute();

    echo "تم تعيين كلمة مرور جديدة للمستخدم \"{$username}\".\n";
} elseif ($create) {
    $role = 'admin';
    $insert = $conn->prepare(
        'INSERT INTO users (username, password, full_name, role) VALUES (:username, :password, :full_name, :role)'
    );
    $insert->bindParam(':username', $username);
    $insert->bindParam(':password', $hash);
    $insert->bindParam(':full_name', $username);
    $insert->bindParam(':role', $role);
    $insert->execute();

    echo "تم إنشاء حساب مدير جديد باسم \"{$username}\".\n";
} else {
    fwrite(STDERR, "المستخدم \"{$username}\" غير موجود. ضيف ‎--create‎ لو عايز تنشئ حساب مدير جديد بالاسم ده.\n");
    exit(1);
}

// تحقق نهائي إن الهاش المخزّن فعلاً بيقبل كلمة المرور دي
$verify = $conn->prepare('SELECT password FROM users WHERE username = :username');
$verify->bindParam(':username', $username);
$verify->execute();

if (password_verify($password, (string) $verify->fetch()['password'])) {
    echo "تم التحقق: تقدر تسجّل دخول بكلمة المرور الجديدة.\n";
    exit(0);
}

fwrite(STDERR, "تحذير: تعذّر التحقق من كلمة المرور بعد الحفظ.\n");
exit(1);
