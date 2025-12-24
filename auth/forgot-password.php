<?php
/**
 * صفحة نسيان كلمة المرور
 * إرسال رابط استعادة كلمة المرور للبريد الإلكتروني
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../database/security.php';

// منع الوصول المباشر
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('تم حظر الوصول المباشر إلى هذا الملف');
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
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $errors['email'] = 'البريد الإلكتروني مطلوب';
        } else {
            // البحث عن المستخدم
            $stmt = db()->prepare("
                SELECT id, email, username FROM users 
                WHERE email = ? AND deleted_at IS NULL
                LIMIT 1
            ");

            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // إنشاء رمز استعادة
                $resetToken = Security::generatePasswordResetToken();
                $hashedToken = hash('sha256', $resetToken);

                // حفظ الرمز في قاعدة البيانات
                $insertStmt = db()->prepare("
                    INSERT INTO password_resets (user_id, token, expires_at)
                    VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))
                ");

                $insertStmt->bind_param('is', $user['id'], $hashedToken);
                $insertStmt->execute();

                // إنشاء رابط الاستعادة
                $resetLink = SITE_URL . 'reset-password/' . $resetToken;

                // سيتم إرسال البريد لاحقاً
                // sendPasswordResetEmail($email, $resetLink);

                $success = true;
            } else {
                // لا نكشف ما إذا كان البريد موجوداً أم لا (أمان)
                $success = true;
            }
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
    <title>نسيان كلمة المرور - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="استعد كلمة المرور الخاصة بك">
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
            <h1 class="auth-title">استعادة كلمة المرور</h1>
            <p class="auth-subtitle">أدخل بريدك الإلكتروني لإعادة تعيين كلمتك</p>
        </div>

        <!-- رسائل النجاح والخطأ -->
        <?php if ($success): ?>
        <div class="alert alert-success" role="alert">
            <strong>تم الإرسال!</strong> تحقق من بريدك الإلكتروني للحصول على رابط الاستعادة.
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

        <!-- نموذج الاستعادة -->
        <?php if (!$success): ?>
        <form id="forgotPasswordForm" class="auth-form" method="POST" action="">
            
            <!-- CSRF Token -->
            <input type="hidden" name="_csrf_token" value="<?php echo $csrfToken; ?>">

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
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                >
            </div>

            <!-- الأزرار -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block" aria-label="إرسال">
                    <span>إرسال رابط الاستعادة</span>
                </button>
            </div>

        </form>
        <?php endif; ?>

        <!-- رابط العودة -->
        <div class="auth-footer">
            <p>
                <a href="<?php echo SITE_URL; ?>login" class="auth-link">العودة للدخول</a>
            </p>
        </div>

    </div>
</div>

<!-- JavaScript Files -->
<script src="<?php echo SITE_URL; ?>js/main.js"></script>
<script src="<?php echo SITE_URL; ?>js/auth-validation.js"></script>

</body>
</html>