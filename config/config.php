<?php
/**
 * إعدادات التطبيق العامة.
 * أي ملف محتاج BASE_URL أو بيانات الاتصال بيعمل include_once للملف ده.
 */

// ---------------------------------------------------------------------------
// بيانات الاتصال بقاعدة البيانات
// بتتقرا من متغيرات البيئة عشان ما نحطش الباسورد في الكود،
// ولو مش متظبطة بتستخدم القيم الافتراضية بتاعة بيئة التطوير المحلية.
// ---------------------------------------------------------------------------
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'hospital');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', 'utf8mb4');

// في بيئة الإنتاج خلي القيمة دي false عشان تفاصيل الأخطاء ما تظهرش للمستخدم
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL));

// ---------------------------------------------------------------------------
// المسار الأساسي للتطبيق على السيرفر.
// بيتحسب أوتوماتيك من مكان المشروع بالنسبة لـ DOCUMENT_ROOT، عشان المشروع
// يشتغل من أي فولدر من غير ما نعدّل الروابط يدويًا. تقدر تتجاوزه بـ APP_BASE_URL.
// ---------------------------------------------------------------------------
if (!defined('BASE_URL')) {
    $configuredBase = getenv('APP_BASE_URL');

    if ($configuredBase !== false && $configuredBase !== '') {
        $base = '/' . trim(str_replace('\\', '/', $configuredBase), '/');
    } else {
        $projectRoot = str_replace('\\', '/', (string) realpath(__DIR__ . '/..'));
        $documentRoot = str_replace('\\', '/', rtrim((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''), '/'));

        $base = '';
        if ($documentRoot !== '' && str_starts_with($projectRoot, $documentRoot)) {
            $base = substr($projectRoot, strlen($documentRoot));
        }
    }

    define('BASE_URL', rtrim($base, '/'));
}

/**
 * يبني رابط مطلق بالنسبة لجذر التطبيق.
 * url('/auth/login.php') => '/hospital-system/auth/login.php'
 */
function url(string $path): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * يحوّل المستخدم لصفحة تانية جوه التطبيق ويوقف التنفيذ.
 * الرسالة بتتحط في الجلسة مش في الـ query string عشان ما تتلاعبش من الرابط.
 */
function redirectTo(string $path, ?string $message = null, string $type = 'info'): never
{
    if ($message !== null) {
        $_SESSION['flash'] = ['text' => $message, 'type' => $type];
    }

    header('Location: ' . url($path));
    exit;
}

/**
 * يرجّع رسالة الـ flash المخزنة (لو فيه) ويمسحها عشان ما تتكررش.
 */
function takeFlash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $flash;
}
