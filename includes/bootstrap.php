<?php
/**
 * ملف التمهيد - يتم تضمينه في بداية كل صفحة
 */
require_once __DIR__ . '/../config/config.php';

// إن لم يكن النظام مثبتاً بعد (ولم تُمرَّر بيانات اتصال عبر متغيرات البيئة
// لأغراض التطوير/الاختبار)، نوجّه المستخدم لمعالج التثبيت أولاً.
$__isConfigured = IS_INSTALLED || getenv('DB_HOST') !== false;
if (!$__isConfigured && strpos($_SERVER['SCRIPT_NAME'] ?? '', '/install/') === false) {
    header('Location: ' . BASE_URL . '/install/index.php');
    exit;
}
unset($__isConfigured);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
