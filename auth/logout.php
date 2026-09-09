<?php
include_once '../includes/auth.php';

// تفريغ كل بيانات الجلسة وإنهاؤها
$_SESSION = [];

// مسح كوكي الجلسة من المتصفح كمان مش بس البيانات على السيرفر
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires'  => time() - 42000,
        'path'     => $params['path'],
        'domain'   => $params['domain'],
        'secure'   => $params['secure'],
        'httponly' => $params['httponly'],
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}

session_destroy();

header('Location: ' . url('/auth/login.php'));
exit;
