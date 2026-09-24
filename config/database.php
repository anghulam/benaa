<?php
/**
 * الاتصال بقاعدة البيانات باستخدام MySQLi
 */

require_once __DIR__ . '/config.php';

class Database
{
    private static ?mysqli $connection = null;

    public static function getConnection(): mysqli
    {
        if (self::$connection === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
            try {
                self::$connection = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
                self::$connection->set_charset('utf8mb4');
            } catch (mysqli_sql_exception $e) {
                error_log('Database connection error: ' . $e->getMessage());
                http_response_code(500);
                die('تعذّر الاتصال بقاعدة البيانات. الرجاء المحاولة لاحقاً أو مراجعة إعدادات النظام.');
            }
        }

        return self::$connection;
    }
}

/**
 * دالة مختصرة للحصول على اتصال قاعدة البيانات
 */
function db(): mysqli
{
    return Database::getConnection();
}
