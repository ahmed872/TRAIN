<?php
include_once '../includes/auth.php';

// تفريغ كل بيانات الجلسة وإنهاؤها
$_SESSION = [];
session_destroy();

header("Location: /hospital-system/auth/login.php");
exit;
