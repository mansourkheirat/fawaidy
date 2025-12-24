<?php
/**
 * صفحة التحقق من البريد الإلكتروني
 * التحقق من رمز التحقق المرسل للبريد
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

// التحقق من وجود معرف المستخدم المؤقت
if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_email'])) {
    header('Location: ' . SITE_URL . 'register');
    exit;
}

$userId = $_SESSION['temp_user_id'];
$email = $_SESSION['temp_email'];

// معالجة الطلب POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من CSRF Token
    if (!Security::verifyCSRFToken($_POST['_csrf_token'] ?? null)) {
        $errors['csrf'] = 'رمز الأمان غير صحيح';
    }

    if (empty($errors)) {
        $verificationCode = trim($_POST['verification_code'] ?? '');

        if (empty($verificationCode)) {
            $errors['code'] = 'رمز التحقق مطلوب';
        } else {
            // البحث عن رمز التحقق
            $stmt = db()->prepare("
                SELECT id FROM verification_codes
                WHERE user_id = ? AND code = ? AND type = 'email' 
                AND expires_at > NOW() AND used_at IS NULL
                LIMIT 1
            ");

            $stmt->bind_param('is', $userId, $verificationCode);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                // تحديث حالة المستخدم
                $updateStmt = db()->prepare("
                    UPDATE users SET email_verified = 1 WHERE id = ?
                ");
                $updateStmt->bind_param('i', $userId);
                $updateStmt->execute();

                // تعليم الرمز كمستخدم
                $useStmt = db()->prepare("
                    UPDATE verification_codes SET used_at = NOW()
                    WHERE user_id = ? AND code = ? AND type = 'email'
                ");
                $useStmt->bind_param('is', $userId, $verificationCode);
                $useStmt->execute();

                $success = true;

                // مسح البيانات المؤقتة
                unset($_SESSION['temp_user_id']);
                unset($_SESSION['temp_email']);
            } else {
                $errors['code'] = 'رمز التحقق غير صحيح أو منتهي الصلاحية';
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
    <title>التحقق من البريد - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="التحقق من بريدك الإلكتروني">
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
            <h1 class="auth-title">التحقق من البريد</h1>
            <p class="auth-subtitle">أدخل الرمز المرسل إلى بريدك الإلكتروني</p>
        </div>

        <!-- رسائل النجاح والخطأ -->
        <?php if ($success): ?>
        <div class="alert alert-success" role="alert">
            <strong>تم بنجاح!</strong> تم التحقق من بريدك الإلكتروني. يمكنك الآن 
            <a href="<?php echo SITE_URL; ?>login" style="color: inherit; font-weight: bold;">تسجيل الدخول</a>
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

        <!-- نموذج التحقق -->
        <form id="verifyForm" class="auth-form" method="POST" action="">
            
            <!-- CSRF Token -->
            <input type="hidden" name="_csrf_token" value="<?php echo $csrfToken; ?>">

            <p style="text-align: center; color: var(--color-gray); margin-bottom: var(--spacing-lg);">
                البريد الإلكتروني: <strong><?php echo htmlspecialchars($email); ?></strong>
            </p>

            <!-- رمز التحقق -->
            <div class="form-group">
                <label for="verification_code" class="form-label required">رمز التحقق</label>
                <input 
                    type="text" 
                    id="verification_code" 
                    name="verification_code" 
                    class="form-control" 
                    placeholder="أدخل الرمز المكون من 6 أحرف"
                    dir="ltr"
                    maxlength="12"
                    required
                    aria-label="رمز التحقق"
                    autocomplete="off"
                >
                <small class="form-help-text">الرمز صالح لمدة 24 ساعة</small>
            </div>

            <!-- الأزرار -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block" aria-label="تحقق">
                    <span>تحقق</span>
                </button>
            </div>

        </form>

        <!-- رابط إعادة الإرسال -->
        <div class="auth-footer">
            <p>لم تتلق الرمز؟ 
                <a href="#" class="auth-link" id="resendLink">أعد الإرسال</a>
            </p>
        </div>

    </div>
</div>

<!-- JavaScript Files -->
<script src="<?php echo SITE_URL; ?>js/main.js"></script>
<script>
// معالجة إعادة الإرسال
document.getElementById('resendLink').addEventListener('click', function(e) {
    e.preventDefault();
    
    if (confirm('هل تريد إعادة إرسال الرمز؟')) {
        // سيتم تنفيذ هذا عبر AJAX لاحقاً
        alert('سيتم إرسال الرمز مرة أخرى إلى بريدك الإلكتروني');
    }
});
</script>

</body>
</html>