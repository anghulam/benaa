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
