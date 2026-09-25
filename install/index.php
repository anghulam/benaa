<?php
/**
 * معالج تثبيت نظام بناء (Benaa) - خطوة بخطوة
 * لا يعتمد على اتصال قاعدة بيانات جاهز؛ يبني اتصاله الخاص من بيانات المستخدم.
 */

require_once __DIR__ . '/../includes/functions.php'; // يحمّل config.php و database.php (تعريف الصف فقط، بلا اتصال)

if (IS_INSTALLED) {
    $pageTitle = 'النظام مثبت بالفعل';
} else {
    $step = max(1, min(5, (int) get('step', '1')));
    // لا يمكن الوصول لخطوة لاحقة دون إتمام بيانات الخطوات السابقة
    if ($step >= 3 && empty($_SESSION['install_db'])) {
        $step = 2;
    }
    if ($step >= 4 && empty($_SESSION['install_schema_done'])) {
        $step = 3;
    }
}

$errors = [];
$success = null;

/** إنشاء اتصال مؤقت بقاعدة البيانات لأغراض التثبيت فقط */
function installConnect(array $db): mysqli
{
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = @new mysqli($db['host'], $db['user'], $db['pass'], '', (int) $db['port']);
    if ($conn->connect_error) {
        throw new RuntimeException($conn->connect_error);
    }
    return $conn;
}

if (!IS_INSTALLED && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if ($step === 1) {
        redirect('/install/index.php?step=2');
    }

    if ($step === 2) {
        $db = [
            'host' => post('db_host', 'localhost'),
            'port' => post('db_port', '3306'),
            'user' => post('db_user', 'root'),
            'pass' => $_POST['db_pass'] ?? '',
            'name' => post('db_name', 'benaa'),
        ];

        try {
            $conn = installConnect($db);
            $dbNameEscaped = $conn->real_escape_string($db['name']);
            if (!$conn->query("CREATE DATABASE IF NOT EXISTS `$dbNameEscaped` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
                throw new RuntimeException('تعذّر إنشاء قاعدة البيانات: ' . $conn->error);
            }
            $conn->close();
            $_SESSION['install_db'] = $db;
            redirect('/install/index.php?step=3');
        } catch (Throwable $e) {
            $errors[] = 'تعذّر الاتصال بقاعدة البيانات: ' . $e->getMessage();
        }
    }

    if ($step === 3) {
        try {
            $db = $_SESSION['install_db'];
            $conn = installConnect($db);
            $dbNameEscaped = $conn->real_escape_string($db['name']);
            $conn->select_db($db['name']) or $conn->query("CREATE DATABASE IF NOT EXISTS `$dbNameEscaped`");
            $conn->select_db($db['name']);

            $existing = $conn->query("SHOW TABLES LIKE 'companies'");
            $alreadyHasTables = $existing && $existing->num_rows > 0;

            if (!$alreadyHasTables) {
                $sql = file_get_contents(__DIR__ . '/../database/schema.sql');
                if ($sql === false) {
                    throw new RuntimeException('تعذّر قراءة ملف database/schema.sql');
                }
                if (!$conn->multi_query($sql)) {
                    throw new RuntimeException('خطأ أثناء تنفيذ ملف الهيكل: ' . $conn->error);
                }
                // تفريغ كل النتائج المتبقية من multi_query
                do {
                    if ($result = $conn->store_result()) {
                        $result->free();
                    }
                } while ($conn->more_results() && $conn->next_result());

                if ($conn->errno) {
                    throw new RuntimeException('خطأ أثناء تنفيذ ملف الهيكل: ' . $conn->error);
                }
            }

            $conn->close();
            $_SESSION['install_schema_done'] = true;
            $success = $alreadyHasTables
                ? 'تم العثور على قاعدة بيانات تحتوي جداول مسبقاً، تم تخطي إعادة الإنشاء.'
                : 'تم إنشاء جميع الجداول والبيانات الأولية بنجاح.';
            redirect('/install/index.php?step=4');
        } catch (Throwable $e) {
            $errors[] = 'حدث خطأ أثناء استيراد هيكل قاعدة البيانات: ' . $e->getMessage();
        }
    }

    if ($step === 4) {
        $name = post('admin_name');
        $email = post('admin_email');
        $password = $_POST['admin_password'] ?? '';
        $passwordConfirm = $_POST['admin_password_confirm'] ?? '';

        if ($name === '') $errors[] = 'الرجاء إدخال اسم المشرف';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
        if (strlen($password) < 8) $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
        if ($password !== $passwordConfirm) $errors[] = 'كلمتا المرور غير متطابقتين';

        if (empty($errors)) {
            try {
                $db = $_SESSION['install_db'];
                $conn = installConnect($db);
                $conn->select_db($db['name']);

                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $conn->prepare('SELECT id FROM users WHERE is_super_admin = 1 ORDER BY id LIMIT 1');
                $stmt->execute();
                $existingAdmin = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($existingAdmin) {
                    $stmt = $conn->prepare('UPDATE users SET name = ?, email = ?, password = ?, status = "active" WHERE id = ?');
                    $stmt->bind_param('sssi', $name, $email, $hash, $existingAdmin['id']);
                } else {
                    $stmt = $conn->prepare('INSERT INTO users (company_id, name, email, password, role, is_super_admin, status) VALUES (NULL, ?, ?, ?, "owner", 1, "active")');
                    $stmt->bind_param('sss', $name, $email, $hash);
                }
                if (!$stmt->execute()) {
                    throw new RuntimeException($stmt->error);
                }
                $stmt->close();
                $conn->close();

                $_SESSION['install_admin_done'] = true;
                redirect('/install/index.php?step=5');
            } catch (Throwable $e) {
                $errors[] = 'حدث خطأ أثناء إنشاء حساب المشرف: ' . $e->getMessage();
            }
        }
    }

    if ($step === 5) {
        $db = $_SESSION['install_db'];
        $configContent = "<?php\n"
            . "/**\n * ملف إعداد الاتصال بقاعدة البيانات - يُنشأ تلقائياً بواسطة معالج التثبيت\n"
            . " * لا تشارك هذا الملف أو ترفعه علناً، فهو يحتوي بيانات اتصال حساسة.\n */\n"
            . "define('DB_HOST', " . var_export($db['host'], true) . ");\n"
            . "define('DB_USER', " . var_export($db['user'], true) . ");\n"
            . "define('DB_PASS', " . var_export($db['pass'], true) . ");\n"
            . "define('DB_NAME', " . var_export($db['name'], true) . ");\n"
            . "define('DB_PORT', " . var_export((int) $db['port'], true) . ");\n";

        if (file_put_contents(__DIR__ . '/../config/installed.php', $configContent) === false) {
            $errors[] = 'تعذّر كتابة ملف الإعداد config/installed.php. تأكد من صلاحيات الكتابة على مجلد config، أو أنشئ الملف يدوياً بنفس المحتوى الظاهر أدناه.';
        } else {
            unset($_SESSION['install_db'], $_SESSION['install_schema_done'], $_SESSION['install_admin_done']);
            $success = 'تم التثبيت بنجاح!';
        }
    }
}

$requirements = [
    ['label' => 'إصدار PHP 8.0 أو أحدث (الحالي: ' . PHP_VERSION . ')', 'ok' => version_compare(PHP_VERSION, '8.0.0', '>=')],
    ['label' => 'امتداد mysqli مفعّل', 'ok' => extension_loaded('mysqli')],
    ['label' => 'مجلد uploads/ قابل للكتابة', 'ok' => is_writable(UPLOAD_PATH)],
    ['label' => 'مجلد config/ قابل للكتابة (لحفظ إعداد قاعدة البيانات)', 'ok' => is_writable(__DIR__ . '/../config')],
];
$requirementsPass = !in_array(false, array_column($requirements, 'ok'), true);

$pageTitle = $pageTitle ?? 'تثبيت النظام';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?> | <?= e(APP_NAME) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.rtl.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/vendor/bootstrap-icons/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= BASE_URL ?>/assets/css/style.css?v=<?= assetVersion('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="auth-page" style="align-items:flex-start;">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <div class="auth-logo mx-auto">ب</div>
                    <h3 class="fw-bold text-white mt-3 mb-0"><?= e(APP_NAME) ?></h3>
                    <p class="text-white-50">معالج التثبيت</p>
                </div>

                <div class="card">
                    <div class="card-body section-card">

                        <?php if (IS_INSTALLED): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-check-circle-fill text-success" style="font-size:56px;"></i>
                                <h4 class="fw-bold mt-3">النظام مثبت بالفعل</h4>
                                <p class="text-muted">تم تثبيت النظام مسبقاً. لحماية بياناتكم، لا يمكن إعادة تشغيل معالج التثبيت.</p>
                                <p class="text-muted small">لإعادة التثبيت من جديد (مثلاً على بيئة تطوير)، احذفوا الملف <code>config/installed.php</code> يدوياً من الخادم.</p>
                                <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn btn-brand mt-2">الذهاب لتسجيل الدخول</a>
                            </div>
                        <?php else: ?>

                        <!-- مؤشر الخطوات -->
                        <div class="d-flex justify-content-between mb-4 flex-wrap gap-2">
                            <?php
                            $stepLabels = ['المتطلبات', 'قاعدة البيانات', 'استيراد الهيكل', 'حساب المشرف', 'الإنهاء'];
                            foreach ($stepLabels as $i => $label):
                                $n = $i + 1;
                                $state = $n < $step ? 'done' : ($n === $step ? 'active' : 'pending');
                            ?>
                                <div class="text-center flex-fill">
                                    <div class="mx-auto d-flex align-items-center justify-content-center rounded-circle mb-1"
                                         style="width:32px;height:32px;font-weight:700;font-size:13px;
                                         background: <?= $state === 'pending' ? '#e2e8f0' : ($state === 'active' ? '#4f46e5' : '#16a34a') ?>;
                                         color: <?= $state === 'pending' ? '#64748b' : '#fff' ?>;">
                                        <?= $state === 'done' ? '<i class="bi bi-check-lg"></i>' : $n ?>
                                    </div>
                                    <div class="small text-muted"><?= e($label) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php foreach ($errors as $err): ?>
                            <div class="alert alert-danger"><?= e($err) ?></div>
                        <?php endforeach; ?>
                        <?php if ($success && $step !== 5): ?>
                            <div class="alert alert-success"><?= e($success) ?></div>
                        <?php endif; ?>

                        <?php if ($step === 1): ?>
                            <h5 class="fw-bold mb-3">فحص متطلبات التشغيل</h5>
                            <ul class="list-group mb-4">
                                <?php foreach ($requirements as $r): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <?= e($r['label']) ?>
                                        <?php if ($r['ok']): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-lg"></i> جاهز</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger"><i class="bi bi-x-lg"></i> غير متوفر</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <?php if (!$requirementsPass): ?>
                                <div class="alert alert-warning">الرجاء معالجة المتطلبات غير المتوفرة أعلاه قبل المتابعة.</div>
                            <?php endif; ?>
                            <form method="post">
                                <?= csrfField() ?>
                                <button class="btn btn-brand w-100 py-2" <?= $requirementsPass ? '' : 'disabled' ?>>
                                    متابعة <i class="bi bi-arrow-left"></i>
                                </button>
                            </form>

                        <?php elseif ($step === 2): ?>
                            <h5 class="fw-bold mb-3">بيانات الاتصال بقاعدة البيانات</h5>
                            <p class="text-muted small">أدخل بيانات اتصال MySQL/MariaDB الخاصة باستضافتكم. سيتم إنشاء قاعدة البيانات تلقائياً إن لم تكن موجودة.</p>
                            <form method="post">
                                <?= csrfField() ?>
                                <div class="row">
                                    <div class="col-md-8 mb-3">
                                        <label class="form-label">عنوان الخادم (Host)</label>
                                        <input type="text" name="db_host" class="form-control" value="<?= e($_SESSION['install_db']['host'] ?? 'localhost') ?>" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">المنفذ (Port)</label>
                                        <input type="text" name="db_port" class="form-control" value="<?= e($_SESSION['install_db']['port'] ?? '3306') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">اسم المستخدم</label>
                                        <input type="text" name="db_user" class="form-control" value="<?= e($_SESSION['install_db']['user'] ?? 'root') ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">كلمة المرور</label>
                                        <input type="password" name="db_pass" class="form-control" value="<?= e($_SESSION['install_db']['pass'] ?? '') ?>">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">اسم قاعدة البيانات</label>
                                        <input type="text" name="db_name" class="form-control" value="<?= e($_SESSION['install_db']['name'] ?? 'benaa') ?>" required>
                                    </div>
                                </div>
                                <button class="btn btn-brand w-100 py-2">اختبار الاتصال ومتابعة <i class="bi bi-arrow-left"></i></button>
                            </form>

                        <?php elseif ($step === 3): ?>
                            <h5 class="fw-bold mb-3">استيراد هيكل قاعدة البيانات</h5>
                            <p class="text-muted small">سيتم الآن إنشاء جميع الجداول والبيانات الأولية (خطط الاشتراك، إلخ) داخل قاعدة البيانات <code><?= e($_SESSION['install_db']['name'] ?? '') ?></code>.</p>
                            <form method="post">
                                <?= csrfField() ?>
                                <button class="btn btn-brand w-100 py-2">استيراد الآن <i class="bi bi-arrow-left"></i></button>
                            </form>

                        <?php elseif ($step === 4): ?>
                            <h5 class="fw-bold mb-3">إنشاء حساب مالك النظام (Super Admin)</h5>
                            <p class="text-muted small">هذا الحساب يملك صلاحية إدارة كل الشركات المشتركة والباقات في النظام.</p>
                            <form method="post">
                                <?= csrfField() ?>
                                <div class="mb-3">
                                    <label class="form-label">الاسم الكامل</label>
                                    <input type="text" name="admin_name" class="form-control" required value="<?= e(post('admin_name', 'مدير النظام')) ?>">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" name="admin_email" class="form-control" required value="<?= e(post('admin_email')) ?>">
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">كلمة المرور</label>
                                        <input type="password" name="admin_password" class="form-control" required minlength="8">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">تأكيد كلمة المرور</label>
                                        <input type="password" name="admin_password_confirm" class="form-control" required minlength="8">
                                    </div>
                                </div>
                                <button class="btn btn-brand w-100 py-2">إنشاء الحساب ومتابعة <i class="bi bi-arrow-left"></i></button>
                            </form>

                        <?php elseif ($step === 5): ?>
                            <?php if ($success): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-rocket-takeoff text-warning" style="font-size:56px;"></i>
                                    <h4 class="fw-bold mt-3">تم التثبيت بنجاح!</h4>
                                    <p class="text-muted">نظامكم جاهز الآن للاستخدام. لأسباب أمنية، يُنصح بحماية أو حذف مجلد <code>install/</code> من الخادم بعد التأكد من نجاح التثبيت.</p>
                                    <a href="<?= BASE_URL ?>/modules/auth/login.php" class="btn btn-brand mt-2">تسجيل الدخول الآن</a>
                                </div>
                            <?php else: ?>
                                <h5 class="fw-bold mb-3">إنهاء التثبيت</h5>
                                <p class="text-muted small">الخطوة الأخيرة: حفظ إعدادات الاتصال بقاعدة البيانات في ملف <code>config/installed.php</code>.</p>
                                <form method="post">
                                    <?= csrfField() ?>
                                    <button class="btn btn-brand w-100 py-2">إنهاء التثبيت <i class="bi bi-check-lg"></i></button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?= BASE_URL ?>/assets/vendor/bootstrap/bootstrap.bundle.min.js"></script>
</body>
</html>
