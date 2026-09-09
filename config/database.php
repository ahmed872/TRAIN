<?php
/**
 * ملف الاتصال بقاعدة البيانات
 * أي ملف محتاج يتعامل مع الداتابيز بيعمل include_once للملف ده
 * وبعدين يستخدم getConnection() عادي
 */

function getConnection()
{
    $host = "localhost";
    $db_name = "hospital";
    $username = "root";
    $password = "";

    $conn = new mysqli($host, $username, $password, $db_name);
    if ($conn->connect_error) {
        die("خطأ في الاتصال بقاعدة البيانات: " . $conn->connect_error);
    }
    return $conn;
}
