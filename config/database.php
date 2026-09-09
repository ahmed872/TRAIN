<?php
/**
 * ملف الاتصال بقاعدة البيانات
 * أي ملف محتاج يتعامل مع الداتابيز بيعمل include_once للملف ده
 * وبعدين يستخدم getConnection() عادي
 *
 * ملاحظة مهمة: الاتصال بيرجع كائن PDO لأن كل الكود في المشروع مكتوب
 * بأسلوب PDO (named placeholders زي :id، وbindParam، وfetchAll).
 */

include_once __DIR__ . '/config.php';

function getConnection(): PDO
{
    // نفس الاتصال بيتعاد استخدامه في نفس الريكوست بدل ما نفتح اتصال جديد كل مرة
    static $conn = null;

    if ($conn instanceof PDO) {
        return $conn;
    }

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

    try {
        $conn = new PDO($dsn, DB_USER, DB_PASS, [
            // أي خطأ في الاستعلام يرمي PDOException بدل ما يعدي في صمت
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            // fetch() و fetchAll() يرجّعوا مصفوفة بأسماء الأعمدة بس
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // prepared statements حقيقية على مستوى السيرفر مش محاكاة
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // تفاصيل الخطأ تروح للّوج، والمستخدم يشوف رسالة عامة
        error_log('Database connection failed: ' . $e->getMessage());

        http_response_code(500);
        if (APP_DEBUG) {
            die('خطأ في الاتصال بقاعدة البيانات: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
        }
        die('خطأ في الاتصال بقاعدة البيانات. برجاء المحاولة لاحقًا.');
    }

    return $conn;
}
