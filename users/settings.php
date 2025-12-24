<?php
/**
 * ==========================================
 * صفحة الإعدادات الشخصية
 * ==========================================
 * 
 * الوصف:
 * صفحة إعدادات الحساب الشخصي للعضو
 * تحديث المعلومات الشخصية والأمان
 * 
 * الميزات:
 * - بطاقات إعدادات متعددة
 * - تحديث فوري بدون تحديث الصفحة (AJAX)
 * - إدارة الأمان والخصوصية
 * - بطاقات منفصلة لكل قسم
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../database/security.php';

// ==========================================
// منع الوصول المباشر
// ==========================================
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('تم حظر الوصول المباشر إلى هذا الملف');
}

// ==========================================
// التحقق من تسجيل الدخول
// ==========================================
Security::requireLogin();

$userId = $_SESSION['user_id'];

// ==========================================
// جلب بيانات المستخدم
// ==========================================
$stmt = db()->prepare("
    SELECT 
        id, full_name, username, email, avatar, gender, country,
        birth_date_hijri, birth_date_gregorian, 
        bio, education_level, major, job_title,
        phone, is_active, created_at
    FROM users 
    WHERE id = ? AND deleted_at IS NULL
    LIMIT 1
");

$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ' . SITE_URL);
    exit;
}

$user = $result->fetch_assoc();

// ==========================================
// جلب إعدادات الخصوصية والأمان
// ==========================================
$settingsStmt = db()->prepare("
    SELECT 
        show_email, show_phone, allow_messages, 
        two_factor_enabled, notifications_enabled
    FROM user_settings 
    WHERE user_id = ?
    LIMIT 1
");

$settingsStmt->bind_param('i', $userId);
$settingsStmt->execute();
$settingsResult = $settingsStmt->get_result();
$settings = $settingsResult->num_rows > 0 ? $settingsResult->fetch_assoc() : [
    'show_email' => 1,
    'show_phone' => 0,
    'allow_messages' => 1,
    'two_factor_enabled' => 0,
    'notifications_enabled' => 1
];

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="إعدادات الحساب الشخصي">
    
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/settings.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/cards.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/forms.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/buttons.css">
</head>
<body>

<!-- ==========================================
     الشريط العلوي
     ========================================== -->
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- ==========================================
     المحتوى الرئيسي
     ========================================== -->
<main style="padding-top: var(--header-height);">
    <div class="container">

        <!-- ==========================================
             عنوان الصفحة
             ========================================== -->
        <section class="settings-header">
            <h1 class="page-title">الإعدادات</h1>
            <p class="page-subtitle">إدارة إعدادات حسابك والخصوصية والأمان</p>
        </section>

        <!-- ==========================================
             التخطيط المزدوج (Sidebar + Content)
             ========================================== -->
        <div class="settings-layout">

            <!-- ==========================================
                 الشريط الجانبي (Sidebar)
                 ========================================== -->
            <aside class="settings-sidebar">
                <nav class="settings-nav">
                    <a href="#account-info" class="settings-nav-link active" data-section="account-info">
                        <span class="nav-icon">👤</span>
                        <span class="nav-text">معلومات الحساب</span>
                    </a>
                    <a href="#password" class="settings-nav-link" data-section="password">
                        <span class="nav-icon">🔐</span>
                        <span class="nav-text">كلمة المرور</span>
                    </a>
                    <a href="#personal-data" class="settings-nav-link" data-section="personal-data">
                        <span class="nav-icon">ℹ️</span>
                        <span class="nav-text">البيانات الشخصية</span>
                    </a>
                    <a href="#education-work" class="settings-nav-link" data-section="education-work">
                        <span class="nav-icon">🎓</span>
                        <span class="nav-text">التعليم والعمل</span>
                    </a>
                    <a href="#privacy" class="settings-nav-link" data-section="privacy">
                        <span class="nav-icon">👁️</span>
                        <span class="nav-text">الخصوصية</span>
                    </a>
                    <a href="#security" class="settings-nav-link" data-section="security">
                        <span class="nav-icon">🛡️</span>
                        <span class="nav-text">الأمان</span>
                    </a>
                </nav>
            </aside>

            <!-- ==========================================
                 محتوى الإعدادات
                 ========================================== -->
            <section class="settings-content">

                <!-- ==========================================
                     بطاقة معلومات الحساب
                     ========================================== -->
                <div class="settings-card" id="account-info" data-section="account-info">
                    <div class="settings-card-header">
                        <h2 class="settings-card-title">معلومات الحساب</h2>
                        <span class="settings-card-icon">👤</span>
                    </div>
                    <div class="settings-card-content">
                        <form class="settings-form" data-form="account-info">
                            <div class="form-group">
                                <label class="form-label">الاسم الكامل</label>
                                <p class="form-value"><?php echo htmlspecialchars($user['full_name']); ?></p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">اسم المستخدم</label>
                                <p class="form-value"><?php echo htmlspecialchars($user['username']); ?></p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">البريد الإلكتروني</label>
                                <p class="form-value"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div class="form-group">
                                <label class="form-label">تاريخ الانضمام</label>
                                <p class="form-value"><?php echo htmlspecialchars($user['created_at']); ?></p>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ==========================================
                     بطاقة تغيير كلمة المرور
                     ========================================== -->
                <div class="settings-card" id="password" data-section="password">
                    <div class="settings-card-header">
                        <h2 class="settings-card-title">تغيير كلمة المرور</h2>
                        <span class="settings-card-icon">🔐</span>
                    </div>
                    <div class="settings-card-content">
                        <form class="settings-form" data-form="password" id="passwordForm">
                            <div class="form-group">
                                <label for="current_password" class="form-label required">كلمة المرور الحالية</label>
                                <input 
                                    type="password" 
                                    id="current_password" 
                                    name="current_password" 
                                    class="form-control" 
                                    placeholder="أدخل كلمة المرور الحالية"
                                    dir="ltr"
                                    required
                                >
                            </div>
                            <div class="form-group">
                                <label for="new_password" class="form-label required">كلمة المرور الجديدة</label>
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password" 
                                    class="form-control" 
                                    placeholder="أدخل كلمة المرور الجديدة"
                                    dir="ltr"
                                    required
                                >
                            </div>
                            <div class="form-group">
                                <label for="confirm_password" class="form-label required">تأكيد كلمة المرور</label>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    class="form-control" 
                                    placeholder="أعد إدخال كلمة المرور"
                                    dir="ltr"
                                    required
                                >
                            </div>
                            <button type="submit" class="btn btn-primary">تحديث كلمة المرور</button>
                        </form>
                    </div>
                </div>

                <!-- ==========================================
                     بطاقة البيانات الشخصية
                     ========================================== -->
                <div class="settings-card" id="personal-data" data-section="personal-data">
                    <div class="settings-card-header">
                        <h2 class="settings-card-title">البيانات الشخصية</h2>
                        <span class="settings-card-icon">ℹ️</span>
                    </div>
                    <div class="settings-card-content">
                        <form class="settings-form" data-form="personal-data" id="personalDataForm">
                            <div class="form-row two-col">
                                <div class="form-group">
                                    <label for="gender" class="form-label">الجنس</label>
                                    <select id="gender" name="gender" class="form-control">
                                        <option value="unspecified" <?php echo $user['gender'] === 'unspecified' ? 'selected' : ''; ?>>غير محدد</option>
                                        <option value="male" <?php echo $user['gender'] === 'male' ? 'selected' : ''; ?>>ذكر</option>
                                        <option value="female" <?php echo $user['gender'] === 'female' ? 'selected' : ''; ?>>أنثى</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="country" class="form-label">البلد</label>
                                    <input 
                                        type="text" 
                                        id="country" 
                                        name="country" 
                                        class="form-control" 
                                        placeholder="أدخل بلدك"
                                        dir="rtl"
                                        value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>"
                                    >
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="bio" class="form-label">النبذة الشخصية</label>
                                <textarea 
                                    id="bio" 
                                    name="bio" 
                                    class="form-control" 
                                    placeholder="اكتب نبذة عن نفسك"
                                    dir="rtl"
                                    rows="4"
                                ><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                        </form>
                    </div>
                </div>

                <!-- ==========================================
                     بطاقة التعليم والعمل
                     ========================================== -->
                <div class="settings-card" id="education-work" data-section="education-work">
                    <div class="settings-card-header">
                        <h2 class="settings-card-title">التعليم والعمل</h2>
                        <span class="settings-card-icon">🎓</span>
                    </div>
                    <div class="settings-card-content">
                        <form class="settings-form" data-form="education-work" id="educationWorkForm">
                            <div class="form-row two-col">
                                <div class="form-group">
                                    <label for="education_level" class="form-label">المستوى الدراسي</label>
                                    <input 
                                        type="text" 
                                        id="education_level" 
                                        name="education_level" 
                                        class="form-control" 
                                        placeholder="مثل: بكالوريوس، ماجستير"
                                        value="<?php echo htmlspecialchars($user['education_level'] ?? ''); ?>"
                                    >
                                </div>
                                <div class="form-group">
                                    <label for="major" class="form-label">التخصص</label>
                                    <input 
                                        type="text" 
                                        id="major" 
                                        name="major" 
                                        class="form-control" 
                                        placeholder="مثل: الحاسوب، الطب"
                                        value="<?php echo htmlspecialchars($user['major'] ?? ''); ?>"
                                    >
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="job_title" class="form-label">الوظيفة</label>
                                <input 
                                    type="text" 
                                    id="job_title" 
                                    name="job_title" 
                                    class="form-control" 
                                    placeholder="أدخل وظيفتك"
                                    value="<?php echo htmlspecialchars($user['job_title'] ?? ''); ?>"
                                >
                            </div>
                            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                        </form>
                    </div>
                </div>

                <!-- ==========================================
                     بطاقة الخصوصية
                     ========================================== -->
                <div class="settings-card" id="privacy" data-section="privacy">
                    <div class="settings-card-header">
                        <h2 class="settings-card-title">الخصوصية</h2>
                        <span class="settings-card-icon">👁️</span>
                    </div>
                    <div class="settings-card-content">
                        <form class="settings-form" data-form="privacy" id="privacyForm">
                            <div class="form-group">
                                <div class="form-check">
                                    <input 
                                        type="checkbox" 
                                        id="show_email" 
                                        name="show_email"
                                        <?php echo $settings['show_email'] ? 'checked' : ''; ?>
                                    >
                                    <label for="show_email" class="form-check-label">
                                        إظهار البريد الإلكتروني في الملف الشخصي
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input 
                                        type="checkbox" 
                                        id="show_phone" 
                                        name="show_phone"
                                        <?php echo $settings['show_phone'] ? 'checked' : ''; ?>
                                    >
                                    <label for="show_phone" class="form-check-label">
                                        إظهار رقم الهاتف في الملف الشخصي
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="form-check">
                                    <input 
                                        type="checkbox" 
                                        id="allow_messages" 
                                        name="allow_messages"
                                        <?php echo $settings['allow_messages'] ? 'checked' : ''; ?>
                                    >
                                    <label for="allow_messages" class="form-check-label">
                                        السماح بالرسائل الخاصة
                                    </label>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                        </form>
                    </div>
                </div>

                <!-- ==========================================
                     بطاقة الأمان
                     ========================================== -->
                <div class="settings-card" id="security" data-section="security">
                    <div class="settings-card-header">
                        <h2 class="settings-card-title">الأمان</h2>
                        <span class="settings-card-icon">🛡️</span>
                    </div>
                    <div class="settings-card-content">
                        <div class="security-options">
                            <div class="security-option">
                                <h3>تحميل بيانات الحساب</h3>
                                <p>احصل على نسخة من جميع بيانات حسابك</p>
                                <button type="button" class="btn btn-secondary" id="downloadDataBtn">
                                    تحميل البيانات
                                </button>
                            </div>
                            <div class="security-option">
                                <h3>تسجيل الخروج من جميع الأجهزة</h3>
                                <p>قم بتسجيل الخروج من جميع أجهزتك</p>
                                <button type="button" class="btn btn-warning" id="logoutAllBtn">
                                    خروج من الكل
                                </button>
                            </div>
                            <div class="security-option">
                                <h3>قفل الحساب</h3>
                                <p>قفل حسابك مؤقتاً (سيتم إلغاء القفل تلقائياً)</p>
                                <button type="button" class="btn btn-danger" id="lockAccountBtn">
                                    قفل الحساب
                                </button>
                            </div>
                            <div class="security-option">
                                <h3>حذف الحساب نهائياً</h3>
                                <p>حذف حسابك وجميع البيانات المرتبطة به بشكل نهائي</p>
                                <button type="button" class="btn btn-danger" id="deleteAccountBtn">
                                    حذف الحساب
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </section>

        </div>

    </div>
</main>

<!-- ==========================================
     التذييل
     ========================================== -->
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- ==========================================
     JavaScript Files
     ========================================== -->
<script src="<?php echo SITE_URL; ?>js/main.js"></script>
<script src="<?php echo SITE_URL; ?>js/ajax.js"></script>
<script src="<?php echo SITE_URL; ?>js/settings.js"></script>

</body>
</html>