<?php
include_once __DIR__ . '/includes/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . url('/departments/index.php'));
} else {
    header('Location: ' . url('/auth/login.php'));
}
exit;
