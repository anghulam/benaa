<?php
/**
 * طبقة المصادقة والصلاحيات متعددة المستأجرين (Multi-Tenant)
 */

require_once __DIR__ . '/functions.php';

/** خريطة الأدوار وأسمائها بالعربية */
function roleLabel(string $role): string
{
    $labels = [
        'owner' => 'مالك الشركة',
        'admin' => 'مدير النظام',
        'manager' => 'مدير مشاريع',
        'accountant' => 'محاسب',
        'engineer' => 'مهندس',
        'employee' => 'موظف',
    ];
    return $labels[$role] ?? $role;
}

/**
 * قائمة الوحدات القابلة للتخصيص في مصفوفة الصلاحيات، مع تسمياتها العربية.
 * لا تشمل "dashboard" (متاحة دوماً لكل مستخدم مسجّل) ولا "roles" (إدارة
 * الصلاحيات نفسها، متاحة فقط للمالك ومدير النظام ولا يمكن تخصيصها).
 */
function permissionModulesList(): array
{
    return [
        'clients' => 'العملاء',
        'projects' => 'المشاريع',
        'contracts' => 'العقود',
        'invoices' => 'الفواتير',
        'payments' => 'الدفعات',
        'expenses' => 'المصروفات',
        'employees' => 'الموظفون',
        'attendance' => 'الحضور والانصراف',
        'payroll' => 'الرواتب',
        'suppliers' => 'الموردون',
        'materials' => 'المخزون والمواد',
        'purchases' => 'أوامر الشراء',
        'users' => 'المستخدمون',
        'settings' => 'إعدادات الشركة',
        'reports' => 'التقارير',
    ];
}

/** قائمة الأدوار القابلة لتخصيص صلاحياتها (كل الأدوار عدا المالك، الذي يملك كل الصلاحيات دوماً) */
function customizableRoles(): array
{
    return ['admin', 'manager', 'accountant', 'engineer', 'employee'];
}

/** الصلاحيات الافتراضية المرتبطة بكل دور - تُستخدم قبل أي تخصيص من صاحب الشركة */
function defaultRolePermissions(string $role): array
{
    $all = array_keys(permissionModulesList());

    $map = [
        'owner' => $all,
        'admin' => $all,
        'manager' => ['clients', 'projects', 'contracts', 'invoices', 'expenses', 'attendance', 'materials', 'purchases', 'reports'],
        'accountant' => ['clients', 'invoices', 'payments', 'expenses', 'payroll', 'reports'],
        'engineer' => ['projects', 'contracts', 'materials', 'attendance'],
        'employee' => ['attendance'],
    ];

    return $map[$role] ?? [];
}

/**
 * جلب تخصيص صلاحيات دور معيّن ضمن شركة معيّنة، إن وُجد (خريطة module_key => allowed).
 * ترجع null إن لم يقم صاحب الشركة بتخصيص هذا الدور بعد، فيُعتمَد حينها على المصفوفة الافتراضية.
 * يُخزَّن الناتج في ذاكرة تخزين مؤقت ثابتة (static) لتفادي تكرار الاستعلام لكل رابط في القائمة الجانبية.
 */
function companyRoleOverrides(int $companyId, string $role): ?array
{
    static $cache = [];
    $key = $companyId . ':' . $role;
    if (!array_key_exists($key, $cache)) {
        $rows = dbFetchAll('SELECT module_key, allowed FROM role_permissions WHERE company_id = ? AND role = ?', 'is', [$companyId, $role]);
        $cache[$key] = empty($rows) ? null : array_map('boolval', array_column($rows, 'allowed', 'module_key'));
    }
    return $cache[$key];
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function currentCompanyId(): ?int
{
    return $_SESSION['company_id'] ?? null;
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    if (!isset($_SESSION['user_cache'])) {
        $_SESSION['user_cache'] = dbFetchOne('SELECT * FROM users WHERE id = ? LIMIT 1', 'i', [currentUserId()]);
    }
    return $_SESSION['user_cache'];
}

function currentCompany(): ?array
{
    $companyId = currentCompanyId();
    if (!$companyId) {
        return null;
    }
    if (!isset($_SESSION['company_cache'])) {
        $_SESSION['company_cache'] = dbFetchOne('SELECT * FROM companies WHERE id = ? LIMIT 1', 'i', [$companyId]);
    }
    return $_SESSION['company_cache'];
}

function isSuperAdmin(): bool
{
    $user = currentUser();
    return $user && (int) $user['is_super_admin'] === 1;
}

function can(string $module): bool
{
    $user = currentUser();
    if (!$user) {
        return false;
    }
    if ((int) $user['is_super_admin'] === 1) {
        return true;
    }
    if ($module === 'dashboard') {
        return true; // متاحة دوماً لأي مستخدم مسجّل داخل الشركة
    }
    if ($user['role'] === 'owner') {
        return true; // المالك يملك كل الصلاحيات دائماً ولا يمكن تقييده
    }
    if ($module === 'roles') {
        // إدارة مصفوفة الصلاحيات نفسها متاحة فقط للمالك (أعلاه) ومدير النظام،
        // ولا تخضع للتخصيص عبر المصفوفة تفادياً لحبس الجميع من إدارتها
        return $user['role'] === 'admin';
    }

    $companyId = currentCompanyId();
    $overrides = $companyId ? companyRoleOverrides($companyId, $user['role']) : null;
    if ($overrides !== null) {
        return !empty($overrides[$module]);
    }

    return in_array($module, defaultRolePermissions($user['role']), true);
}

/** يجب أن يكون المستخدم مسجلاً للدخول */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        redirect('/modules/auth/login.php');
    }
    $company = currentCompany();
    if ($company && !isSuperAdmin() && in_array($company['status'], ['suspended', 'expired'], true)) {
        flash('danger', 'تم إيقاف اشتراك شركتكم. الرجاء التواصل مع الدعم الفني لتجديد الاشتراك.');
        redirect('/modules/auth/login.php');
    }
}

/** يجب أن يملك المستخدم صلاحية الوصول لوحدة معينة */
function requirePermission(string $module): void
{
    requireLogin();
    if (!can($module)) {
        http_response_code(403);
        die('عذراً، لا تملك صلاحية الوصول إلى هذه الصفحة.');
    }
}

function requireSuperAdmin(): void
{
    requireLogin();
    if (!isSuperAdmin()) {
        http_response_code(403);
        die('هذه الصفحة مخصصة لمالك النظام فقط.');
    }
}

/** تسجيل دخول المستخدم */
function attemptLogin(string $email, string $password): array
{
    $user = dbFetchOne('SELECT * FROM users WHERE email = ? AND status = "active" LIMIT 1', 's', [$email]);

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة'];
    }

    if ($user['company_id'] !== null) {
        $company = dbFetchOne('SELECT * FROM companies WHERE id = ? LIMIT 1', 'i', [$user['company_id']]);
        if (!$company) {
            return ['success' => false, 'message' => 'حساب الشركة غير موجود'];
        }
        if (in_array($company['status'], ['suspended', 'expired'], true)) {
            return ['success' => false, 'message' => 'تم إيقاف اشتراك الشركة، الرجاء التواصل مع الدعم الفني'];
        }
    }

    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['company_id'] = $user['company_id'] !== null ? (int) $user['company_id'] : null;
    unset($_SESSION['user_cache'], $_SESSION['company_cache']);

    dbExecute('UPDATE users SET last_login_at = NOW() WHERE id = ?', 'i', [$user['id']]);
    logActivity('login', 'تسجيل دخول للنظام');

    return ['success' => true];
}

function logout(): void
{
    logActivity('logout', 'تسجيل خروج من النظام');
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * تسجيل شركة جديدة مع حساب المالك (Owner) - بداية الاشتراك التجريبي
 */
function registerCompany(string $companyName, string $ownerName, string $email, string $password, string $phone): array
{
    $existing = dbFetchOne('SELECT id FROM users WHERE email = ? LIMIT 1', 's', [$email]);
    if ($existing) {
        return ['success' => false, 'message' => 'البريد الإلكتروني مستخدم مسبقاً'];
    }

    $conn = db();
    $conn->begin_transaction();

    try {
        $slugBase = trim(preg_replace('/[^a-z0-9]+/i', '-', $companyName), '-');
        $slugBase = strtolower($slugBase ?: 'company');
        $slug = $slugBase . '-' . substr(bin2hex(random_bytes(3)), 0, 6);

        $trialPlan = dbFetchOne('SELECT * FROM subscription_plans WHERE slug = "trial" LIMIT 1');
        $trialDays = $trialPlan['duration_days'] ?? 14;

        $stmt = dbExecute(
            'INSERT INTO companies (name, slug, phone, email, plan_id, status, trial_ends_at) VALUES (?, ?, ?, ?, ?, "trial", DATE_ADD(NOW(), INTERVAL ? DAY))',
            'ssssii',
            [$companyName, $slug, $phone, $email, $trialPlan['id'] ?? null, $trialDays]
        );
        $companyId = $conn->insert_id;

        $hash = password_hash($password, PASSWORD_BCRYPT);
        dbExecute(
            'INSERT INTO users (company_id, name, email, password, phone, role, status) VALUES (?, ?, ?, ?, ?, "owner", "active")',
            'issss',
            [$companyId, $ownerName, $email, $hash, $phone]
        );

        // تصنيفات مصروفات افتراضية
        $defaultCategories = ['مواد بناء', 'رواتب وأجور', 'نقل ومواصلات', 'إيجارات', 'أخرى'];
        foreach ($defaultCategories as $cat) {
            dbExecute('INSERT INTO expense_categories (company_id, name) VALUES (?, ?)', 'is', [$companyId, $cat]);
        }

        $conn->commit();
        return ['success' => true, 'company_id' => $companyId];
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('registerCompany error: ' . $e->getMessage());
        return ['success' => false, 'message' => 'حدث خطأ أثناء إنشاء الحساب، الرجاء المحاولة لاحقاً'];
    }
}
