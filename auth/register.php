<?php
/**
 * صفحة التسجيل
 * استقبال بيانات التسجيل والتحقق منها
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../database/security.php';
require_once __DIR__ . '/../database/validation.php';

// منع الوصول المباشر
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('تم حظر الوصول المباشر إلى هذا الملف');
}

// إذا كان المستخدم مسجل دخول بالفعل، حوله للرئيسية
if (isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL);
    exit;
}

$errors = [];
$success = false;

// معالجة الطلب POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من CSRF Token
    if (!Security::verifyCSRFToken($_POST['_csrf_token'] ?? null)) {
        $errors['csrf'] = 'رمز الأمان غير صحيح';
    }

    if (empty($errors)) {
        // جمع البيانات
        $data = [
            'fullname' => $_POST['fullname'] ?? '',
            'username' => $_POST['username'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
            'gender' => $_POST['gender'] ?? 'unspecified',
            'country' => $_POST['country'] ?? ''
        ];

        // التحقق من البيانات
        if (Validation::validateRegistration($data)) {
            // البيانات صحيحة، إدراجها في قاعدة البيانات
            try {
                $stmt = db()->prepare("
                    INSERT INTO users (
                        full_name, username, email, password, 
                        gender, country, role, email_verified, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())
                ");

                $hashedPassword = Security::hashPassword($data['password']);
                $stmt->bind_param(
                    'ssssssi',
                    $data['fullname'],
                    $data['username'],
                    $data['email'],
                    $hashedPassword,
                    $data['gender'],
                    $data['country'],
                    $role = ROLE_MEMBER
                );

                if ($stmt->execute()) {
                    $userId = db()->lastInsertId();
                    
                    // توليد رمز التحقق
                    $verificationCode = Security::generateVerificationCode();
                    
                    // حفظ رمز التحقق في قاعدة البيانات
                    $verifyStmt = db()->prepare("
                        INSERT INTO verification_codes (user_id, code, type, expires_at)
                        VALUES (?, ?, 'email', DATE_ADD(NOW(), INTERVAL 24 HOUR))
                    ");
                    $verifyStmt->bind_param('is', $userId, $verificationCode);
                    $verifyStmt->execute();
                    
                    // إرسال البريد الإلكتروني (سيتم تنفيذه لاحقاً)
                    // sendVerificationEmail($data['email'], $verificationCode);
                    
                    $success = true;
                    $_SESSION['temp_user_id'] = $userId;
                    $_SESSION['temp_email'] = $data['email'];
                    
                } else {
                    $errors['database'] = 'حدث خطأ في إنشاء الحساب. يرجى المحاولة لاحقاً';
                }
            } catch (Exception $e) {
                error_log($e->getMessage());
                $errors['database'] = 'حدث خطأ في النظام';
            }
        } else {
            $errors = array_merge($errors, Validation::getErrors());
        }
    }
}

// الحصول على CSRF Token
$csrfToken = Security::generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التسجيل - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="سجل حساباً جديداً في موقع فوائدي">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/forms.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/messages.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/buttons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/auth.css">
</head>
<body class="auth-page">

<div class="auth-container">
    <div class="auth-box">
        
        <!-- شعار الموقع -->
        <div class="auth-header">
            <a href="<?php echo SITE_URL; ?>" class="auth-logo">
                <svg class="auth-logo-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="2"/>
                    <text x="50" y="60" font-size="40" text-anchor="middle" fill="currentColor">ف</text>
                </svg>
                <span><?php echo SITE_NAME; ?></span>
            </a>
            <h1 class="auth-title">إنشاء حساب جديد</h1>
            <p class="auth-subtitle">انضم إلينا وشارك الفوائد العلمية</p>
        </div>

        <!-- رسائل النجاح والخطأ -->
        <?php if ($success): ?>
        <div class="alert alert-success" role="alert">
            <strong>تم بنجاح!</strong> تم إنشاء حسابك. يرجى التحقق من بريدك الإلكتروني.
        </div>
        <?php endif; ?>

        <?php if (!empty($errors) && !$success): ?>
        <div class="alert alert-error" role="alert">
            <strong>خطأ:</strong>
            <ul style="margin: 10px 0 0 0; padding-right: 20px;">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- نموذج التسجيل -->
        <form id="registerForm" class="auth-form" method="POST" action="">
            
            <!-- CSRF Token -->
            <input type="hidden" name="_csrf_token" value="<?php echo $csrfToken; ?>">

            <!-- الاسم الكامل -->
            <div class="form-group">
                <label for="fullname" class="form-label required">الاسم الكامل</label>
                <input 
                    type="text" 
                    id="fullname" 
                    name="fullname" 
                    class="form-control" 
                    placeholder="أدخل اسمك الكامل بالعربية"
                    dir="rtl"
                    required
                    aria-label="الاسم الكامل"
                >
                <small class="form-help-text">أحرف عربية فقط، بدون أرقام أو رموز</small>
            </div>

            <!-- اسم المستخدم -->
            <div class="form-group">
                <label for="username" class="form-label required">اسم المستخدم</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    class="form-control" 
                    placeholder="اختر اسم مستخدم"
                    dir="ltr"
                    required
                    aria-label="اسم المستخدم"
                >
                <small class="form-help-text">أحرف وأرقام بلا عربي (يبدأ بحرف)</small>
            </div>

            <!-- البريد الإلكتروني -->
            <div class="form-group">
                <label for="email" class="form-label required">البريد الإلكتروني</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="أدخل بريدك الإلكتروني"
                    dir="ltr"
                    required
                    aria-label="البريد الإلكتروني"
                >
            </div>

            <!-- كلمة المرور -->
            <div class="form-group">
                <label for="password" class="form-label required">كلمة المرور</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="أدخل كلمة المرور"
                    dir="ltr"
                    required
                    aria-label="كلمة المرور"
                >
                <small class="form-help-text">8+ أحرف، حرف كبير، رقم، ورمز خاص</small>
                <div class="password-strength" id="passwordStrength"></div>
            </div>

            <!-- تأكيد كلمة المرور -->
            <div class="form-group">
                <label for="password_confirm" class="form-label required">تأكيد كلمة المرور</label>
                <input 
                    type="password" 
                    id="password_confirm" 
                    name="password_confirm" 
                    class="form-control" 
                    placeholder="أعد إدخال كلمة المرور"
                    dir="ltr"
                    required
                    aria-label="تأكيد كلمة المرور"
                >
            </div>

            <!-- الجنس -->
            <div class="form-group">
                <label class="form-label required">الجنس</label>
                <div class="form-radio-group">
                    <div class="form-check">
                        <input 
                            type="radio" 
                            id="gender_unspecified" 
                            name="gender" 
                            value="unspecified" 
                            checked
                            aria-label="غير محدد"
                        >
                        <label for="gender_unspecified" class="form-check-label">غير محدد</label>
                    </div>
                    <div class="form-check">
                        <input 
                            type="radio" 
                            id="gender_male" 
                            name="gender" 
                            value="male"
                            aria-label="ذكر"
                        >
                        <label for="gender_male" class="form-check-label">ذكر</label>
                    </div>
                    <div class="form-check">
                        <input 
                            type="radio" 
                            id="gender_female" 
                            name="gender" 
                            value="female"
                            aria-label="أنثى"
                        >
                        <label for="gender_female" class="form-check-label">أنثى</label>
                    </div>
                </div>
            </div>

            <!-- البلد -->
            <div class="form-group">
                <label for="country" class="form-label">البلد</label>
                <input 
                    type="text" 
                    id="country" 
                    name="country" 
                    class="form-control" 
                    placeholder="أدخل بلدك بالعربية"
                    dir="rtl"
                    aria-label="البلد"
                >
            </div>

            <!-- شروط الاستخدام -->
            <div class="form-group">
                <div class="form-check">
                    <input 
                        type="checkbox" 
                        id="terms" 
                        name="terms" 
                        required
                        aria-label="الموافقة على شروط الاستخدام"
                    >
                    <label for="terms" class="form-check-label">
                        أوافق على 
                        <a href="<?php echo SITE_URL; ?>terms" target="_blank" class="terms-link">شروط الاستخدام</a>
                    </label>
                </div>
            </div>

            <!-- الأزرار -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block" aria-label="تسجيل">
                    <span>تسجيل</span>
                </button>
                <button type="reset" class="btn btn-secondary btn-block" aria-label="مسح">
                    <span>مسح</span>
                </button>
            </div>

        </form>

        <!-- رابط الدخول -->
        <div class="auth-footer">
            <p>هل لديك حساب بالفعل؟ 
                <a href="<?php echo SITE_URL; ?>login" class="auth-link">سجل دخولك</a>
            </p>
        </div>

    </div>
</div>

<!-- JavaScript Files -->
<script src="<?php echo SITE_URL; ?>js/main.js"></script>
<script src="<?php echo SITE_URL; ?>js/auth-validation.js"></script>

</body>
</html>