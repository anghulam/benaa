<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/site/inc/functions.php';

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => '', 'company_name' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!site_verify_csrf()) {
        $errors[] = 'انتهت صلاحية النموذج، الرجاء إعادة المحاولة.';
    } else {
        foreach (array_keys($old) as $field) {
            $old[$field] = trim($_POST[$field] ?? '');
        }

        if ($old['name'] === '') $errors[] = 'الرجاء إدخال الاسم';
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'البريد الإلكتروني غير صحيح';
        if ($old['message'] === '') $errors[] = 'الرجاء كتابة رسالتكم';

        if (empty($errors)) {
            $saved = false;
            try {
                $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT);
                $conn->set_charset('utf8mb4');
                $stmt = $conn->prepare('INSERT INTO contact_messages (name, email, phone, company_name, message) VALUES (?,?,?,?,?)');
                $stmt->bind_param('sssss', $old['name'], $old['email'], $old['phone'], $old['company_name'], $old['message']);
                $stmt->execute();
                $stmt->close();
                $conn->close();
                $saved = true;
            } catch (Throwable $e) {
                error_log('contact form save error: ' . $e->getMessage());
                $saved = false;
            }

            if ($saved) {
                site_flash('success', 'تم إرسال رسالتكم بنجاح، سيتواصل معكم فريقنا في أقرب وقت.');
                header('Location: ' . BASE_URL . '/contact.php');
                exit;
            }

            $errors[] = 'تعذّر إرسال رسالتكم حالياً بسبب مشكلة تقنية مؤقتة. الرجاء المحاولة لاحقاً أو التواصل معنا مباشرة عبر البريد الإلكتروني.';
        }
    }
}

$pageTitle = 'تواصل معنا';
$pageDescription = 'لديكم سؤال أو تحتاجون مساعدة قبل الاشتراك؟ تواصلوا مع فريق بناء وسنعود إليكم في أقرب وقت.';
$activeNav = 'contact';
require __DIR__ . '/site/inc/header.php';
?>

<section class="page-hero">
    <div class="container">
        <h1>نحن هنا لمساعدتكم</h1>
        <p>لديكم استفسار عن النظام أو الباقات؟ أرسلوا لنا رسالة وسنعود إليكم في أقرب وقت.</p>
    </div>
</section>

<section class="site-section">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-5">
                <h3 class="fw-bold mb-4">بيانات التواصل</h3>
                <ul class="icon-check-list mb-4">
                    <li><i class="bi bi-envelope"></i> info@example.com</li>
                    <li><i class="bi bi-telephone"></i> 966+ 5XXXXXXXX</li>
                    <li><i class="bi bi-clock"></i> الأحد - الخميس، 9 صباحاً - 5 مساءً</li>
                </ul>
                <div class="feature-card feature-card--inline">
                    <div class="feature-icon bg-soft-amber"><i class="bi bi-lightbulb"></i></div>
                    <h5>تفضّلون تجربة النظام مباشرة؟</h5>
                    <p>يمكنكم البدء بفترة تجريبية مجانية لمدة 14 يوماً دون الحاجة للتواصل أولاً.</p>
                    <a href="<?= BASE_URL ?>/modules/auth/register.php" class="btn btn-brand btn-sm mt-3">ابدأ تجربتك المجانية</a>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body section-card">
                        <?php foreach ($errors as $err): ?>
                            <div class="alert alert-danger"><?= e($err) ?></div>
                        <?php endforeach; ?>
                        <form method="post" novalidate>
                            <?= site_csrf_field() ?>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">الاسم الكامل *</label>
                                    <input type="text" name="name" class="form-control" required value="<?= e($old['name']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">البريد الإلكتروني *</label>
                                    <input type="email" name="email" class="form-control" required value="<?= e($old['email']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">رقم الجوال</label>
                                    <input type="text" name="phone" class="form-control" value="<?= e($old['phone']) ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">اسم الشركة</label>
                                    <input type="text" name="company_name" class="form-control" value="<?= e($old['company_name']) ?>">
                                </div>
                                <div class="col-12 mb-3">
                                    <label class="form-label">رسالتكم *</label>
                                    <textarea name="message" class="form-control" rows="5" required><?= e($old['message']) ?></textarea>
                                </div>
                            </div>
                            <button class="btn btn-brand px-4"><i class="bi bi-send me-1"></i> إرسال الرسالة</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/site/inc/footer.php'; ?>
