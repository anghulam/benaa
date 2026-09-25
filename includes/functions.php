<?php
/**
 * دوال مساعدة عامة تُستخدم في جميع أنحاء النظام
 */

require_once __DIR__ . '/../config/database.php';

/** تنقية النصوص المدخلة من المستخدم لعرضها بأمان */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * رقم إصدار الملف الثابت (بناءً على وقت آخر تعديل فعلي عليه)، يُضاف كـ query
 * string لرابط الملف (مثال: style.css?v=1234) حتى يضطر أي كاش (متصفح أو
 * CDN أو كاش على مستوى الاستضافة) لجلب نسخة جديدة فور تغيّر محتوى الملف،
 * بدل الاحتفاظ بنسخة قديمة مرتبطة بنفس اسم الملف إلى ما لا نهاية.
 */
function assetVersion(string $relativePath): string
{
    $fullPath = ROOT_PATH . '/' . ltrim($relativePath, '/');
    $mtime = @filemtime($fullPath);
    return $mtime !== false ? (string) $mtime : '1';
}

/**
 * إعدادات النظام العامة (على مستوى المنصة بأكملها، لا شركة بعينها)
 * تُستخدم لشعار النظام وبيانات اعتماد Google OAuth.
 */
function getSystemSetting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        try {
            $rows = dbFetchAll('SELECT setting_key, setting_value FROM system_settings');
            $cache = array_column($rows, 'setting_value', 'setting_key');
        } catch (mysqli_sql_exception $e) {
            error_log('getSystemSetting: ' . $e->getMessage());
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

function setSystemSetting(string $key, ?string $value): void
{
    dbExecute(
        'INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)',
        'ss',
        [$key, $value]
    );
}

/** اسم المنصة المعروض في كل الواجهات؛ يعتمد على ما ضبطه السوبر أدمن من
 * إعدادات النظام، ويرجع للاسم الافتراضي (APP_NAME) إن لم يُضبط بعد */
function appName(): string
{
    $name = getSystemSetting('platform_name');
    return $name !== null && $name !== '' ? $name : APP_NAME;
}

/** باقات الأسعار المعروضة في الموقع التسويقي؛ تُقرأ من إعدادات السوبر أدمن
 * إن وُجدت، وإلا ترجع للباقات الافتراضية */
function getPricingPlans(): array
{
    require_once __DIR__ . '/pricing_defaults.php';
    $json = getSystemSetting('pricing_plans');
    if ($json) {
        $decoded = json_decode($json, true);
        if (is_array($decoded) && count($decoded) > 0) {
            return $decoded;
        }
    }
    return defaultPricingPlans();
}

/** تنقية سلسلة نصية وإزالة الفراغات الزائدة */
function clean(?string $value): string
{
    return trim($value ?? '');
}

/** إعادة توجيه إلى مسار داخل التطبيق */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

/** تخزين رسالة تنبيه مؤقتة تظهر بعد إعادة التوجيه */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** استرجاع وعرض جميع رسائل التنبيه المخزنة ثم مسحها */
function getFlashMessages(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** تنسيق رقم كعملة مع اسم العملة */
function formatMoney(float $amount, string $currency = 'ر.س'): string
{
    return number_format($amount, 2) . ' ' . $currency;
}

/** تنسيق تاريخ ميلادي بصيغة عربية مختصرة */
function formatDate(?string $date): string
{
    if (empty($date) || $date === '0000-00-00') {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('Y-m-d', $ts) : '—';
}

/** توليد رقم مرجعي فريد (فاتورة/عقد/أمر شراء) */
function generateReferenceNumber(string $prefix): string
{
    return strtoupper($prefix) . '-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

/** تنفيذ استعلام SELECT وإرجاع النتائج كمصفوفة ترابطية */
function dbFetchAll(string $sql, string $types = '', array $params = []): array
{
    $stmt = db()->prepare($sql);
    if ($types !== '' && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    return $rows;
}

/** تنفيذ استعلام SELECT وإرجاع صف واحد فقط */
function dbFetchOne(string $sql, string $types = '', array $params = []): ?array
{
    $rows = dbFetchAll($sql, $types, $params);
    return $rows[0] ?? null;
}

/** تنفيذ استعلام INSERT/UPDATE/DELETE وإرجاع معرّف الإدراج الأخير أو عدد الصفوف المتأثرة */
function dbExecute(string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $stmt = db()->prepare($sql);
    if ($types !== '' && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

/** تسجيل حدث في سجل النشاطات */
function logActivity(string $action, string $description = ''): void
{
    $companyId = currentCompanyId();
    $userId = currentUserId();
    dbExecute(
        'INSERT INTO activity_log (company_id, user_id, action, description) VALUES (?, ?, ?, ?)',
        'iiss',
        [$companyId, $userId, $action, $description]
    );
}

/** الحصول على قيمة POST بأمان بعد التنقية */
function post(string $key, string $default = ''): string
{
    return clean($_POST[$key] ?? $default);
}

/** الحصول على قيمة GET بأمان بعد التنقية */
function get(string $key, string $default = ''): string
{
    return clean($_GET[$key] ?? $default);
}

/** التحقق من رمز CSRF */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die('انتهت صلاحية الجلسة أو الطلب غير صالح. الرجاء إعادة تحميل الصفحة والمحاولة مرة أخرى.');
    }
}

/** رفع ملف بأمان إلى مجلد محدد وإرجاع اسم الملف الجديد */
function handleFileUpload(string $inputName, string $subFolder, array $allowedExt = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx']): ?string
{
    if (empty($_FILES[$inputName]['name']) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$inputName];
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        flash('danger', 'حجم الملف كبير جداً، الحد الأقصى 5 ميجابايت');
        return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        flash('danger', 'صيغة الملف غير مسموحة');
        return null;
    }

    $targetDir = UPLOAD_PATH . '/' . $subFolder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $newName = bin2hex(random_bytes(16)) . '.' . $ext;
    $targetPath = $targetDir . '/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        flash('danger', 'تعذّر رفع الملف');
        return null;
    }

    return $subFolder . '/' . $newName;
}

/** ضبط pagination بسيط */
function paginate(int $totalRows, int $perPage = 20): array
{
    $page = max(1, (int) get('page', '1'));
    $totalPages = max(1, (int) ceil($totalRows / $perPage));
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    return compact('page', 'totalPages', 'perPage', 'offset');
}

/** ترجمة حالات النظام المختلفة إلى نص عربي وكلاس Bootstrap */
function statusBadge(string $status): array
{
    $map = [
        'trial' => ['تجريبي', 'secondary'],
        'active' => ['نشط', 'success'],
        'suspended' => ['موقوف', 'danger'],
        'expired' => ['منتهي', 'dark'],
        'inactive' => ['غير نشط', 'secondary'],
        'planning' => ['تخطيط', 'secondary'],
        'in_progress' => ['قيد التنفيذ', 'primary'],
        'on_hold' => ['متوقف مؤقتاً', 'warning'],
        'completed' => ['مكتمل', 'success'],
        'cancelled' => ['ملغي', 'danger'],
        'draft' => ['مسودة', 'secondary'],
        'terminated' => ['منتهي', 'danger'],
        'sent' => ['مرسلة', 'info'],
        'partial' => ['مدفوعة جزئياً', 'warning'],
        'paid' => ['مدفوعة بالكامل', 'success'],
        'overdue' => ['متأخرة', 'danger'],
        'pending' => ['قيد الانتظار', 'warning'],
        'ordered' => ['تم الطلب', 'info'],
        'received' => ['تم الاستلام', 'success'],
        'present' => ['حاضر', 'success'],
        'absent' => ['غائب', 'danger'],
        'leave' => ['إجازة', 'info'],
        'sick' => ['إجازة مرضية', 'warning'],
        'new' => ['جديدة', 'danger'],
        'read' => ['مقروءة', 'secondary'],
        'replied' => ['تم الرد', 'success'],
    ];
    return $map[$status] ?? [$status, 'secondary'];
}

/** اسم الشهر العربي المختصر من رقم الشهر (1-12) */
function arabicMonthShort(int $month): string
{
    $months = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل', 5 => 'مايو', 6 => 'يونيو',
        7 => 'يوليو', 8 => 'أغسطس', 9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];
    return $months[$month] ?? '';
}

/** ترجمة طريقة الدفع إلى نص عربي */
function paymentMethodLabel(string $method): string
{
    $map = [
        'cash' => 'نقدي',
        'bank_transfer' => 'تحويل بنكي',
        'cheque' => 'شيك',
        'card' => 'بطاقة',
        'other' => 'أخرى',
    ];
    return $map[$method] ?? $method;
}
