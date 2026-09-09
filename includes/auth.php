<?php
session_start();

/**
 * يتأكد إن فيه مستخدم مسجل دخول، ولو لأ يحوله للتسجيل.
 */
function requireLogin()
{
    if (!isset($_SESSION['user_id'])) {
        header("Location: /hospital-system/auth/login.php");
        exit;
    }
}

/**
 * يتأكد إن المستخدم مسجل دخول وصلاحيته "أدمن"، لو لأ يرفض الدخول
 */
function requireAdmin()
{
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header("Location: /hospital-system/departments/index.php?msg=" . urlencode("غير مصرح لك بالدخول لهذه الصفحة"));
        exit;
    }
}
