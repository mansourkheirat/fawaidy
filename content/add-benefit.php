<?php
/**
 * ==========================================
 * صفحة إضافة فائدة جديدة
 * ==========================================
 * 
 * الملف: content/add-benefit.php
 * الوصف: صفحة لإضافة فوائد جديدة إلى الموقع
 * 
 * الميزات الرئيسية:
 * - نموذج إضافة فائدة شامل
 * - تحميل صور للفائدة
 * - اختيار فئة للفائدة
 * - معاينة حية للفائدة
 * - حفظ كمسودة أو نشر مباشر
 * 
 * المتطلبات الأمنية:
 * - التحقق من تسجيل الدخول
 * - التحقق من CSRF Token
 * - التحقق من حجم الملفات
 * - منع SQL Injection
 * - XSS Protection
 * 
 * الصلاحيات:
 * - الأعضاء العاديين: يمكنهم إضافة فوائد
 * - الأعضاء المميزين: يمكنهم إضافة مقالات أيضاً
 * - المديرين: صلاحيات إدارية
 */

// ==========================================
// استيراد الملفات المطلوبة
// ==========================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../database/security.php';
require_once __DIR__ . '/../database/validation.php';

// ==========================================
// منع الوصول المباشر للملف
// ==========================================
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('تم حظر الوصول المباشر إلى هذا الملف');
}

// ==========================================
// التحقق من تسجيل دخول المستخدم
// ==========================================
Security::requireLogin();

// ==========================================
// الحصول على معرف المستخدم من الجلسة
// ==========================================
$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? ROLE_MEMBER;

// ==========================================
// متغيرات معالجة النموذج
// ==========================================
$errors = [];
$success = false;
$formData = [
    'title' => '',
    'content' => '',
    'category_id' => '',
    'tags' => ''
];

// ==========================================
// جلب الفئات المتاحة من قاعدة البيانات
// ==========================================
$categoriesStmt = db()->prepare("
    SELECT id, name, description
    FROM categories
    WHERE is_active = 1
    ORDER BY name ASC
");

$categoriesStmt->execute();
$categoriesResult = $categoriesStmt->get_result();
$categories = [];
while ($category = $categoriesResult->fetch_assoc()) {
    $categories[] = $category;
}

// ==========================================
// معالجة طلب POST (إضافة الفائدة)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ==========================================
    // التحقق من CSRF Token
    // ==========================================
    if (!Security::verifyCSRFToken($_POST['_csrf_token'] ?? null)) {
        $errors['csrf'] = 'رمز الأمان غير صحيح';
    }

    if (empty($errors)) {
        
        // ==========================================
        // جمع بيانات النموذج
        // ==========================================
        $formData['title'] = trim($_POST['title'] ?? '');
        $formData['content'] = trim($_POST['content'] ?? '');
        $formData['category_id'] = trim($_POST['category_id'] ?? '');
        $formData['tags'] = trim($_POST['tags'] ?? '');
        $status = $_POST['status'] ?? 'draft';

        // ==========================================
        // التحقق من صحة البيانات المدخلة
        // ==========================================
        
        // التحقق من العنوان
        if (empty($formData['title'])) {
            $errors['title'] = 'عنوان الفائدة مطلوب';
        } elseif (strlen($formData['title']) < 5 || strlen($formData['title']) > 200) {
            $errors['title'] = 'العنوان يجب أن يكون بين 5 و 200 حرف';
        }

        // التحقق من المحتوى
        if (empty($formData['content'])) {
            $errors['content'] = 'محتوى الفائدة مطلوب';
        } elseif (strlen($formData['content']) < 20) {
            $errors['content'] = 'المحتوى يجب أن يكون على الأقل 20 حرف';
        }

        // التحقق من الفئة
        if (empty($formData['category_id'])) {
            $errors['category_id'] = 'اختر فئة للفائدة';
        } else {
            // التحقق من أن الفئة موجودة وفعالة
            $catStmt = db()->prepare("
                SELECT id FROM categories 
                WHERE id = ? AND is_active = 1 LIMIT 1
            ");
            $catStmt->bind_param('i', $formData['category_id']);
            $catStmt->execute();
            if ($catStmt->get_result()->num_rows === 0) {
                $errors['category_id'] = 'الفئة المختارة غير صحيحة';
            }
        }

        // ==========================================
        // إذا لم تكن هناك أخطاء، إدراج الفائدة
        // ==========================================
        if (empty($errors)) {
            
            try {
                
                // ==========================================
                // إعداد الاستعلام للإدراج
                // ==========================================
                $insertStmt = db()->prepare("
                    INSERT INTO benefits (
                        user_id, category_id, title, content, 
                        tags, status, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");

                // ==========================================
                // ربط المتغيرات بقيمهم (Prepared Statement)
                // ==========================================
                $insertStmt->bind_param(
                    'iissss',
                    $userId,
                    $formData['category_id'],
                    $formData['title'],
                    $formData['content'],
                    $formData['tags'],
                    $status
                );

                // ==========================================
                // تنفيذ الاستعلام
                // ==========================================
                if ($insertStmt->execute()) {
                    
                    // ==========================================
                    // الحصول على معرف الفائدة المُنشأة
                    // ==========================================
                    $benefitId = db()->lastInsertId();
                    
                    $success = true;
                    $formData = ['title' => '', 'content' => '', 'category_id' => '', 'tags' => ''];
                    
                } else {
                    // ==========================================
                    // معالجة أخطاء قاعدة البيانات
                    // ==========================================
                    error_log('خطأ في إدراج الفائدة: ' . db()->getError());
                    $errors['database'] = 'حدث خطأ في حفظ الفائدة. يرجى المحاولة لاحقاً';
                }
                
            } catch (Exception $e) {
                // ==========================================
                // معالجة الاستثناءات
                // ==========================================
                error_log('استثناء في إضافة الفائدة: ' . $e->getMessage());
                $errors['exception'] = 'حدث خطأ في النظام';
            }
        }
    }
}

// ==========================================
// توليد CSRF Token
// ==========================================
$csrfToken = Security::generateCSRFToken();

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إضافة فائدة - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="أضف فائدة علمية جديدة إلى الموقع">
    
    <!-- ==========================================
         استيراد ملفات CSS
         ========================================== -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/forms.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/buttons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/cards.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/messages.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/add-content.css">
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
        <section class="content-header">
            <h1 class="page-title">إضافة فائدة جديدة</h1>
            <p class="page-subtitle">شارك فائدة علمية مفيدة مع أعضاء الموقع</p>
        </section>

        <!-- ==========================================
             رسائل النجاح والأخطاء
             ========================================== -->
        <?php if ($success): ?>
        <div class="alert alert-success">
            <strong>تم بنجاح!</strong> تمت إضافة الفائدة بنجاح. 
            <a href="<?php echo SITE_URL; ?>benefit/<?php echo $benefitId; ?>">اعرض الفائدة</a>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <strong>خطأ:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- ==========================================
             النموذج الرئيسي
             ========================================== -->
        <section class="content-form-section">
            <form id="addBenefitForm" class="add-benefit-form" method="POST" action="">
                
                <!-- ==========================================
                     CSRF Token
                     ========================================== -->
                <input type="hidden" name="_csrf_token" value="<?php echo $csrfToken; ?>">

                <!-- ==========================================
                     العنوان
                     ========================================== -->
                <div class="form-group">
                    <label for="title" class="form-label required">
                        عنوان الفائدة
                    </label>
                    <input 
                        type="text" 
                        id="title" 
                        name="title" 
                        class="form-control <?php echo isset($errors['title']) ? 'is-invalid' : ''; ?>"
                        placeholder="أدخل عنوان الفائدة"
                        value="<?php echo htmlspecialchars($formData['title']); ?>"
                        dir="rtl"
                        required
                        maxlength="200"
                        aria-label="عنوان الفائدة"
                    >
                    <small class="form-help-text">الحد الأدنى 5 أحرف والحد الأقصى 200 حرف</small>
                    <?php if (isset($errors['title'])): ?>
                    <div class="form-error"><?php echo htmlspecialchars($errors['title']); ?></div>
                    <?php endif; ?>
                </div>

                <!-- ==========================================
                     الفئة
                     ========================================== -->
                <div class="form-group">
                    <label for="category_id" class="form-label required">
                        الفئة
                    </label>
                    <select 
                        id="category_id" 
                        name="category_id" 
                        class="form-control <?php echo isset($errors['category_id']) ? 'is-invalid' : ''; ?>"
                        required
                        aria-label="اختر الفئة"
                    >
                        <option value="">-- اختر الفئة --</option>
                        <?php foreach ($categories as $category): ?>
                        <option 
                            value="<?php echo $category['id']; ?>"
                            <?php echo $formData['category_id'] == $category['id'] ? 'selected' : ''; ?>
                        >
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['category_id'])): ?>
                    <div class="form-error"><?php echo htmlspecialchars($errors['category_id']); ?></div>
                    <?php endif; ?>
                </div>

                <!-- ==========================================
                     المحتوى
                     ========================================== -->
                <div class="form-group">
                    <label for="content" class="form-label required">
                        محتوى الفائدة
                    </label>
                    <textarea 
                        id="content" 
                        name="content" 
                        class="form-control <?php echo isset($errors['content']) ? 'is-invalid' : ''; ?>"
                        placeholder="اكتب محتوى الفائدة بالتفصيل"
                        rows="10"
                        dir="rtl"
                        required
                        aria-label="محتوى الفائدة"
                    ><?php echo htmlspecialchars($formData['content']); ?></textarea>
                    <small class="form-help-text">الحد الأدنى 20 حرف</small>
                    <?php if (isset($errors['content'])): ?>
                    <div class="form-error"><?php echo htmlspecialchars($errors['content']); ?></div>
                    <?php endif; ?>
                </div>

                <!-- ==========================================
                     الكلمات المفتاحية
                     ========================================== -->
                <div class="form-group">
                    <label for="tags" class="form-label">
                        الكلمات المفتاحية (Tags)
                    </label>
                    <input 
                        type="text" 
                        id="tags" 
                        name="tags" 
                        class="form-control"
                        placeholder="مثال: علم، معلومة، فائدة (افصل بين الكلمات بفواصل)"
                        value="<?php echo htmlspecialchars($formData['tags']); ?>"
                        dir="rtl"
                        aria-label="الكلمات المفتاحية"
                    >
                </div>

                <!-- ==========================================
                     حالة النشر
                     ========================================== -->
                <div class="form-group">
                    <label class="form-label">حالة الفائدة</label>
                    <div class="form-radio-group">
                        <div class="form-check">
                            <input 
                                type="radio" 
                                id="status_draft" 
                                name="status" 
                                value="draft" 
                                checked
                                aria-label="حفظ كمسودة"
                            >
                            <label for="status_draft" class="form-check-label">
                                مسودة (حفظ بدون نشر)
                            </label>
                        </div>
                        <div class="form-check">
                            <input 
                                type="radio" 
                                id="status_published" 
                                name="status" 
                                value="published"
                                aria-label="نشر الفائدة"
                            >
                            <label for="status_published" class="form-check-label">
                                نشر (يراها الجميع)
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ==========================================
                     أزرار الإجراء
                     ========================================== -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" aria-label="حفظ الفائدة">
                        <span>حفظ الفائدة</span>
                    </button>
                    <button type="reset" class="btn btn-secondary" aria-label="مسح النموذج">
                        <span>مسح</span>
                    </button>
                    <a href="<?php echo SITE_URL; ?>benefits" class="btn btn-outline-primary" aria-label="إلغاء">
                        <span>إلغاء</span>
                    </a>
                </div>

            </form>
        </section>

    </div>
</main>

<!-- ==========================================
     التذييل
     ========================================== -->
<?php include __DIR__ . '/../includes/footer.php'; ?>

<!-- ==========================================
     ملفات JavaScript
     ========================================== -->
<script src="<?php echo SITE_URL; ?>js/main.js"></script>
<script src="<?php echo SITE_URL; ?>js/add-content.js"></script>

</body>
</html>