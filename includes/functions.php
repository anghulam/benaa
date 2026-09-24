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
    ];
    return $map[$status] ?? [$status, 'secondary'];
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
