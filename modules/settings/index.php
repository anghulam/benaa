<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../includes/google_drive.php';
requirePermission('settings');

$companyId = currentCompanyId();
$company = dbFetchOne('SELECT * FROM companies WHERE id = ?', 'i', [$companyId]);
$plan = $company['plan_id'] ? dbFetchOne('SELECT * FROM subscription_plans WHERE id = ?', 'i', [$company['plan_id']]) : null;

$errors = [];
$isOwnerOrAdmin = in_array(currentUser()['role'], ['owner', 'admin'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isOwnerOrAdmin) {
    verifyCsrf();
    $form = post('form', 'general');

    if ($form === 'general') {
        $name = post('name');
        $phone = post('phone');
        $email = post('email');
        $address = post('address');
        $currency = post('currency', 'SAR');
        $taxNumber = post('tax_number');
        $commercialRegister = post('commercial_register');

        if ($name === '') $errors[] = 'الرجاء إدخال اسم الشركة';

        $logo = $company['logo'];
        $uploaded = handleFileUpload('logo', 'logos', ['jpg', 'jpeg', 'png', 'svg']);
        if ($uploaded) $logo = $uploaded;

        if (empty($errors)) {
            dbExecute(
                'UPDATE companies SET name=?, phone=?, email=?, address=?, currency=?, tax_number=?, commercial_register=?, logo=? WHERE id=?',
                'ssssssssi',
                [$name, $phone, $email, $address, $currency, $taxNumber, $commercialRegister, $logo, $companyId]
            );
            unset($_SESSION['company_cache']);
            flash('success', 'تم تحديث إعدادات الشركة بنجاح');
            redirect('/modules/settings/index.php');
        }
        $company = array_merge($company, compact('name', 'phone', 'email', 'address', 'currency', 'taxNumber', 'commercialRegister'));
    }

    if ($form === 'letterhead') {
        $marginTop = max(0, (int) post('letterhead_margin_top', '0'));
        $marginRight = max(0, (int) post('letterhead_margin_right', '0'));
        $marginBottom = max(0, (int) post('letterhead_margin_bottom', '0'));
        $marginLeft = max(0, (int) post('letterhead_margin_left', '0'));

        $letterhead = $company['letterhead'];
        $uploaded = handleFileUpload('letterhead', 'letterheads', ['jpg', 'jpeg', 'png', 'pdf']);
        if ($uploaded) $letterhead = $uploaded;

        dbExecute(
            'UPDATE companies SET letterhead=?, letterhead_margin_top=?, letterhead_margin_right=?, letterhead_margin_bottom=?, letterhead_margin_left=? WHERE id=?',
            'siiiii',
            [$letterhead, $marginTop, $marginRight, $marginBottom, $marginLeft, $companyId]
        );
        unset($_SESSION['company_cache']);
        flash('success', 'تم تحديث اللترهيد والهوامش بنجاح');
        redirect('/modules/settings/index.php');
    }
}

$googleConfigured = googleDriveIsConfigured();
$googleConnection = googleDriveConnection($companyId);

$pageTitle = 'إعدادات الشركة';
$pageSubtitle = 'إدارة بيانات وإعدادات شركتكم';
$activeModule = 'settings';
require __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card mb-3">
            <div class="card-header">البيانات الأساسية</div>
            <div class="card-body section-card">
                <?php foreach ($errors as $err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endforeach; ?>
                <form method="post" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="form" value="general">
                    <fieldset <?= $isOwnerOrAdmin ? '' : 'disabled' ?>>
                    <div class="row">
                        <div class="col-md-8 mb-3"><label class="form-label">اسم الشركة *</label><input type="text" name="name" class="form-control" required value="<?= e($company['name']) ?>"></div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">العملة</label>
                            <select name="currency" class="form-select">
                                <?php foreach (['SAR' => 'ريال سعودي', 'AED' => 'درهم إماراتي', 'EGP' => 'جنيه مصري', 'USD' => 'دولار أمريكي', 'KWD' => 'دينار كويتي'] as $code => $label): ?>
                                    <option value="<?= $code ?>" <?= $company['currency'] === $code ? 'selected' : '' ?>><?= e($label) ?> (<?= $code ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3"><label class="form-label">رقم الجوال</label><input type="text" name="phone" class="form-control" value="<?= e($company['phone']) ?>"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">البريد الإلكتروني</label><input type="email" name="email" class="form-control" value="<?= e($company['email']) ?>"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">السجل التجاري</label><input type="text" name="commercial_register" class="form-control" value="<?= e($company['commercial_register']) ?>"></div>
                        <div class="col-md-6 mb-3"><label class="form-label">الرقم الضريبي</label><input type="text" name="tax_number" class="form-control" value="<?= e($company['tax_number']) ?>"></div>
                        <div class="col-12 mb-3"><label class="form-label">العنوان</label><input type="text" name="address" class="form-control" value="<?= e($company['address']) ?>"></div>
                        <div class="col-12 mb-3">
                            <label class="form-label">شعار الشركة</label>
                            <input type="file" name="logo" class="form-control">
                            <?php if (!empty($company['logo'])): ?><img src="<?= BASE_URL ?>/uploads/<?= e($company['logo']) ?>" class="mt-2" style="max-height:60px;"><?php endif; ?>
                        </div>
                    </div>
                    <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ التغييرات</button>
                    </fieldset>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">اللترهيد (الترويسة المطبوعة) وهوامش الطباعة</div>
            <div class="card-body section-card">
                <p class="text-muted small">
                    ارفعوا صورة اللترهيد الخاصة بشركتكم (ترويسة وتذييل جاهزان بتصميمكم)، وحدّدوا الهوامش
                    التي يجب ترك محتوى الفاتورة خارجها حتى لا يتداخل مع تصميم اللترهيد عند الطباعة.
                </p>
                <form method="post" enctype="multipart/form-data">
                    <?= csrfField() ?>
                    <input type="hidden" name="form" value="letterhead">
                    <fieldset <?= $isOwnerOrAdmin ? '' : 'disabled' ?>>
                        <div class="mb-3">
                            <label class="form-label">صورة اللترهيد (JPG/PNG/PDF)</label>
                            <input type="file" name="letterhead" class="form-control">
                            <?php if (!empty($company['letterhead'])): ?>
                                <a href="<?= BASE_URL ?>/uploads/<?= e($company['letterhead']) ?>" target="_blank" class="small d-inline-block mt-2">عرض اللترهيد الحالي</a>
                            <?php endif; ?>
                        </div>
                        <div class="row">
                            <div class="col-md-3 col-6 mb-3">
                                <label class="form-label">الهامش العلوي (مم)</label>
                                <input type="number" min="0" name="letterhead_margin_top" class="form-control" value="<?= (int) $company['letterhead_margin_top'] ?>">
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <label class="form-label">الهامش السفلي (مم)</label>
                                <input type="number" min="0" name="letterhead_margin_bottom" class="form-control" value="<?= (int) $company['letterhead_margin_bottom'] ?>">
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <label class="form-label">الهامش الأيمن (مم)</label>
                                <input type="number" min="0" name="letterhead_margin_right" class="form-control" value="<?= (int) $company['letterhead_margin_right'] ?>">
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <label class="form-label">الهامش الأيسر (مم)</label>
                                <input type="number" min="0" name="letterhead_margin_left" class="form-control" value="<?= (int) $company['letterhead_margin_left'] ?>">
                            </div>
                        </div>
                        <button class="btn btn-brand"><i class="bi bi-check-lg"></i> حفظ اللترهيد والهوامش</button>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-3">
            <div class="card-header">الاشتراك الحالي</div>
            <div class="card-body section-card">
                <?php $sb = statusBadge($company['status']); ?>
                <p class="mb-2"><span class="text-muted">الحالة: </span><span class="badge bg-<?= $sb[1] ?>"><?= e($sb[0]) ?></span></p>
                <p class="mb-2"><span class="text-muted">الباقة: </span><?= e($plan['name'] ?? '—') ?></p>
                <?php if ($company['status'] === 'trial' && $company['trial_ends_at']): ?>
                    <p class="mb-2"><span class="text-muted">تنتهي التجربة في: </span><?= formatDate($company['trial_ends_at']) ?></p>
                <?php elseif ($company['subscription_ends_at']): ?>
                    <p class="mb-2"><span class="text-muted">ينتهي الاشتراك في: </span><?= formatDate($company['subscription_ends_at']) ?></p>
                <?php endif; ?>
                <?php if ($plan): ?>
                <hr>
                <ul class="list-unstyled small text-muted mb-0">
                    <li class="mb-1"><i class="bi bi-people me-1"></i> حتى <?= (int) $plan['max_users'] ?> مستخدم</li>
                    <li class="mb-1"><i class="bi bi-diagram-3 me-1"></i> حتى <?= (int) $plan['max_projects'] ?> مشروع</li>
                    <li><i class="bi bi-hdd me-1"></i> <?= (int) $plan['max_storage_mb'] ?> ميجابايت تخزين</li>
                </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-google me-1"></i> Google Drive</div>
            <div class="card-body section-card">
                <?php if (!$googleConfigured): ?>
                    <p class="text-muted small mb-0">ميزة ربط Google Drive غير مفعّلة حالياً من قبل مالك النظام.</p>
                <?php elseif ($googleConnection): ?>
                    <div class="alert alert-success py-2 small mb-3">
                        <i class="bi bi-check-circle"></i> متصل
                        <?php if ($googleConnection['drive_email']): ?> بحساب: <strong><?= e($googleConnection['drive_email']) ?></strong><?php endif; ?>
                    </div>
                    <p class="text-muted small">تُرفع نسخ الملفات (مثل ملفات العقود) تلقائياً لمجلد مخصص لشركتكم على Google Drive عند تفعيل ذلك من نموذج الرفع.</p>
                    <?php if ($isOwnerOrAdmin): ?>
                    <form method="post" action="<?= BASE_URL ?>/modules/settings/google_disconnect.php" data-confirm="هل تريد إلغاء ربط Google Drive؟">
                        <?= csrfField() ?>
                        <button class="btn btn-outline-danger btn-sm w-100"><i class="bi bi-x-circle"></i> إلغاء الربط</button>
                    </form>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted small">اربطوا حساب Google Drive الخاص بشركتكم لحفظ نسخ من ملفاتكم تلقائياً هناك.</p>
                    <?php if ($isOwnerOrAdmin): ?>
                    <a href="<?= BASE_URL ?>/modules/settings/google_connect.php" class="btn btn-brand btn-sm w-100"><i class="bi bi-link-45deg"></i> ربط Google Drive الآن</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
