<?php
/**
 * دوال مساعدة صغيرة ومستقلة خاصة بالموقع التسويقي الخارجي.
 * الموقع التسويقي عمداً لا يعتمد على includes/bootstrap.php (ولا بالتالي
 * على قاعدة البيانات أو معالج التثبيت) حتى يبقى قابلاً للعرض دوماً بغض
 * النظر عن حالة تثبيت النظام الداخلي.
 */

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('assetVersion')) {
    /**
     * رقم إصدار الملف الثابت (بناءً على وقت آخر تعديل فعلي عليه)، يُضاف كـ
     * query string لرابط الملف حتى يضطر أي كاش (متصفح أو CDN أو كاش على
     * مستوى الاستضافة) لجلب نسخة جديدة فور تغيّر محتوى الملف.
     */
    function assetVersion(string $relativePath): string
    {
        $fullPath = ROOT_PATH . '/' . ltrim($relativePath, '/');
        $mtime = @filemtime($fullPath);
        return $mtime !== false ? (string) $mtime : '1';
    }
}

if (!function_exists('site_csrf_token')) {
    function site_csrf_token(): string
    {
        if (empty($_SESSION['site_csrf_token'])) {
            $_SESSION['site_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['site_csrf_token'];
    }
}

if (!function_exists('site_csrf_field')) {
    function site_csrf_field(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . e(site_csrf_token()) . '">';
    }
}

if (!function_exists('site_verify_csrf')) {
    function site_verify_csrf(): bool
    {
        return hash_equals($_SESSION['site_csrf_token'] ?? '', $_POST['csrf_token'] ?? '');
    }
}

if (!function_exists('site_flash')) {
    function site_flash(string $type, string $message): void
    {
        $_SESSION['site_flash'][] = ['type' => $type, 'message' => $message];
    }
}

if (!function_exists('site_get_flash')) {
    function site_get_flash(): array
    {
        $messages = $_SESSION['site_flash'] ?? [];
        unset($_SESSION['site_flash']);
        return $messages;
    }
}

if (!function_exists('site_get_system_logo')) {
    /**
     * جلب شعار النظام (إن وُجد) دون الاعتماد على طبقة includes/bootstrap.php،
     * حتى يبقى الموقع التسويقي قابلاً للعرض حتى لو لم يكن النظام مثبّتاً بعد
     * أو تعذّر الاتصال بقاعدة البيانات.
     */
    function site_get_system_logo(): ?string
    {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
            $conn->set_charset('utf8mb4');
            $result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'system_logo' LIMIT 1");
            $row = $result ? $result->fetch_assoc() : null;
            $conn->close();
            return $row['setting_value'] ?? null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('site_get_platform_name')) {
    /**
     * اسم المنصة (إن ضبطه السوبر أدمن) بنفس استقلالية site_get_system_logo
     * عن طبقة includes/bootstrap.php؛ يرجع للاسم الافتراضي APP_NAME عند
     * عدم الضبط أو تعذّر الاتصال بقاعدة البيانات.
     */
    function site_get_platform_name(): string
    {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
            $conn->set_charset('utf8mb4');
            $result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'platform_name' LIMIT 1");
            $row = $result ? $result->fetch_assoc() : null;
            $conn->close();
            $name = $row['setting_value'] ?? null;
            return $name !== null && $name !== '' ? $name : APP_NAME;
        } catch (Throwable $e) {
            return APP_NAME;
        }
    }
}

if (!function_exists('site_get_setting')) {
    /**
     * إعداد عام واحد من system_settings (بنفس استقلالية دوال الشعار واسم
     * المنصة أعلاه)، يُستخدم لبيانات التواصل ونبذة التذييل القابلة للتعديل
     * من إعدادات السوبر أدمن. يرجع للقيمة الافتراضية المُمرَّرة عند عدم
     * الضبط أو تعذّر الاتصال بقاعدة البيانات.
     */
    function site_get_setting(string $key, string $default = ''): string
    {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
            $conn->set_charset('utf8mb4');
            $stmt = $conn->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1');
            $stmt->bind_param('s', $key);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $conn->close();
            $value = $row['setting_value'] ?? null;
            return $value !== null && $value !== '' ? $value : $default;
        } catch (Throwable $e) {
            return $default;
        }
    }
}
