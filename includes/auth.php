<?php
/**
 * طبقة الجلسات والصلاحيات وحماية الـ CSRF.
 * كل صفحة محمية بتعمل include_once للملف ده وبعدين بتنادي requireLogin()
 * أو requireAdmin() حسب الحاجة.
 */

include_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    // تقسية كوكي الجلسة قبل ما نبدأها
    session_set_cookie_params([
        'httponly' => true,                                     // مش متاحة لجافاسكريبت
        'samesite' => 'Lax',                                    // تقلل هجمات CSRF
        'secure'   => !empty($_SERVER['HTTPS']),                // HTTPS بس لو متاح
        'path'     => BASE_URL === '' ? '/' : BASE_URL . '/',
    ]);
    session_start();
}

/** أدوار المستخدمين المسموح بيها في النظام. */
const ROLES = ['admin', 'receptionist', 'doctor', 'patient'];

/** الأدوار اللي حد يقدر يسجّل بيها بنفسه من صفحة إنشاء الحساب. */
const SELF_SIGNUP_ROLES = ['patient'];

/**
 * يتأكد إن فيه مستخدم مسجل دخول، ولو لأ يحوله للتسجيل.
 */
function requireLogin(): void
{
    if (!isset($_SESSION['user_id'])) {
        redirectTo('/auth/login.php', 'برجاء تسجيل الدخول أولًا.', 'warning');
    }
}

/**
 * يرجّع دور المستخدم الحالي، أو سلسلة فاضية لو مفيش مستخدم.
 */
function currentRole(): string
{
    return (string) ($_SESSION['role'] ?? '');
}

/**
 * يتأكد إن المستخدم مسجل دخول وصلاحيته "أدمن"، لو لأ يرفض الدخول
 */
function requireAdmin(): void
{
    requireLogin();

    if (currentRole() !== 'admin') {
        redirectTo('/departments/index.php', 'غير مصرح لك بالدخول لهذه الصفحة', 'danger');
    }
}

/**
 * يتأكد إن دور المستخدم ضمن قائمة أدوار معينة.
 */
function requireRole(array $allowedRoles): void
{
    requireLogin();

    if (!in_array(currentRole(), $allowedRoles, true)) {
        redirectTo('/departments/index.php', 'غير مصرح لك بتنفيذ هذا الإجراء', 'danger');
    }
}

/**
 * يجهّز توكن CSRF للجلسة (بيتعمل مرة واحدة ويفضل ثابت طول الجلسة).
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * يطبع حقل مخفي بالتوكن عشان يتحط جوه أي فورم.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * يتحقق إن الطلب جاي من فورم بتاعنا فعلًا، ولو لأ يرفضه.
 * بيتنادى في أول أي عملية بتغيّر بيانات (POST).
 */
function requireCsrfToken(): void
{
    $submitted = $_POST['csrf_token'] ?? '';

    if (!is_string($submitted) || $submitted === '' || empty($_SESSION['csrf_token'])
        || !hash_equals($_SESSION['csrf_token'], $submitted)) {
        http_response_code(419);
        exit('انتهت صلاحية الجلسة أو الطلب غير صالح. برجاء إعادة تحميل الصفحة والمحاولة مرة أخرى.');
    }
}

/**
 * يرفض أي طلب مش POST — بيتستخدم في صفحات الحذف والإضافة.
 */
function requirePostRequest(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('هذا الإجراء يتطلب إرسال الطلب بطريقة POST.');
    }
}

/**
 * يسجّل دخول المستخدم ويجدّد معرّف الجلسة (حماية من session fixation).
 */
function loginUser(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id']   = (int) $user['id'];
    $_SESSION['username']  = (string) $user['username'];
    $_SESSION['role']      = (string) $user['role'];
    $_SESSION['full_name'] = (string) ($user['full_name'] ?? '');
    $_SESSION['doctor_id'] = isset($user['doctor_id']) ? (int) $user['doctor_id'] : null;
    $_SESSION['patient_id'] = isset($user['patient_id']) ? (int) $user['patient_id'] : null;

    // توكن جديد مع الجلسة الجديدة
    unset($_SESSION['csrf_token']);
    csrfToken();
}

/**
 * يقرأ معرّف رقمي من الطلب ويتأكد إنه رقم موجب صالح، وإلا يرجّع null.
 */
function validId(mixed $value): ?int
{
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $id === false ? null : $id;
}

/**
 * يبني فورم صغير لزرار الحذف.
 * الحذف لازم يكون POST ومعاه توكن CSRF، عشان مجرد فتح رابط
 * (أو استباق المتصفح للروابط) ما يقدرش يمسح بيانات.
 */
function deleteButton(string $action, int $id, string $confirmMessage): string
{
    return '<form action="' . htmlspecialchars($action, ENT_QUOTES, 'UTF-8') . '" method="POST"'
        . ' class="d-inline" onsubmit="return confirm('
        . htmlspecialchars(json_encode($confirmMessage, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8')
        . ');">'
        . csrfField()
        . '<input type="hidden" name="id" value="' . $id . '">'
        . '<button type="submit" class="btn btn-sm btn-danger">حذف</button>'
        . '</form>';
}
