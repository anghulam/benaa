<?php
/**
 * الإعدادات العامة للنظام
 */

// عدّل بيانات الاتصال بقاعدة البيانات حسب بيئة الاستضافة
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'benaa');
define('DB_PORT', getenv('DB_PORT') ?: 3306);

define('APP_NAME', 'بناء | Benaa');
define('APP_VERSION', '1.0.0');

// المسار الأساسي للتطبيق (بدون شرطة مائلة في النهاية)
define('BASE_URL', '');
define('ROOT_PATH', dirname(__DIR__));

define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

date_default_timezone_set('Asia/Riyadh');

// إعدادات الجلسة الآمنة
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// عرض الأخطاء أثناء التطوير فقط
define('APP_DEBUG', getenv('APP_DEBUG') === '1');
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
}
