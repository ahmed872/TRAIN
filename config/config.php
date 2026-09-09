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
        $projectRoot = rtrim(str_replace('\\', '/', (string) realpath(__DIR__ . '/..')), '/');
        $scriptFile  = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
        $scriptName  = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));

        $base = '';

        // نشوف الملف اللي بيتنفذ دلوقتي تحت جذر المشروع بكام مستوى، وبعدين
        // نشيل نفس العدد من آخر SCRIPT_NAME. الطريقة دي مش بتعتمد على
        // DOCUMENT_ROOT، وبالتالي ما بتتأثرش باختلاف حالة الأحرف في مسارات
        // ويندوز (D: مقابل d:) اللي كانت بتخلي المسار يطلع فاضي وكل الروابط تبوظ.
        if ($scriptFile !== '' && $scriptName !== '' && stripos($scriptFile, $projectRoot) === 0) {
            $relative = trim(substr($scriptFile, strlen($projectRoot)), '/');
            $depth    = $relative === '' ? 0 : substr_count($relative, '/') + 1;
            $parts    = explode('/', trim($scriptName, '/'));
            $keep     = max(0, count($parts) - $depth);
            $base     = $keep === 0 ? '' : '/' . implode('/', array_slice($parts, 0, $keep));
        } else {
            // احتياطي: مقارنة بـ DOCUMENT_ROOT، بدون حساسية لحالة الأحرف
            $documentRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
            if ($documentRoot !== '' && stripos($projectRoot, $documentRoot) === 0) {
                $base = substr($projectRoot, strlen($documentRoot));
            }
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
