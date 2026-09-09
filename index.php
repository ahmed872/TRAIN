<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: /hospital-system/departments/index.php");
} else {
    header("Location: /hospital-system/auth/login.php");
}
exit;