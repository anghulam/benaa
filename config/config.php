<?php
/**
 * الإعدادات العامة للنظام
 */

define('APP_NAME', 'بناء | Benaa');
define('APP_VERSION', '1.0.0');

// المسار الأساسي للتطبيق (بدون شرطة مائلة في النهاية)
define('BASE_URL', '');
define('ROOT_PATH', dirname(__DIR__));

define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

date_default_timezone_set('Asia/Riyadh');

// عرض الأخطاء أثناء التطوير فقط
define('APP_DEBUG', getenv('APP_DEBUG') === '1');
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
    ini_set('display_errors', '0');
}

/**
 * إعدادات الاتصال بقاعدة البيانات
 *
 * يقوم معالج التثبيت (install/index.php) بإنشاء ملف config/installed.php
 * تلقائياً بعد إتمام التثبيت بنجاح، وهذا الملف يحتوي على بيانات الاتصال
 * الفعلية بقاعدة البيانات الخاصة بالاستضافة. هذا الملف غير موجود ضمن
 * الكود المصدري (مستثنى عبر .gitignore) لأنه يحتوي بيانات حساسة خاصة
 * بكل استضافة على حدة.
 *
 * إن لم يوجد الملف، نعتبر النظام غير مثبت بعد، ونسمح فقط بالاعتماد على
 * متغيرات البيئة (مفيد للتطوير المحلي والاختبار الآلي دون المرور
 * بمعالج التثبيت).
 */
$installedConfigFile = __DIR__ . '/installed.php';
define('IS_INSTALLED', file_exists($installedConfigFile));

if (IS_INSTALLED) {
    require $installedConfigFile;
} else {
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_NAME', getenv('DB_NAME') ?: 'benaa');
    define('DB_PORT', getenv('DB_PORT') ?: 3306);
}

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
