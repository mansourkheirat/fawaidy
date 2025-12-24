<?php
/**
 * صفحة إعادة تعيين كلمة المرور
 * تعيين كلمة مرور جديدة بناءً على الرمز المرسل
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../database/security.php';
require_once __DIR__ . '/../database/validation.php';

// منع الوصول المباشر
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('تم حظر الوصول المباشر إلى هذا الملف');
}

$errors = [];
$success = false;
$token = '';
$user = null;

// الحصول على الرمز من الرابط
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];

    // التحقق من صحة الرمز
    $hashedToken = hash('sha256', $token);
    
    $stmt = db()->prepare("
        SELECT pr.user_id, pr.expires_at, u.email 
        FROM password_resets pr
        JOIN users u ON pr.user_id = u.id
        WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used_at IS NULL
        LIMIT 1
    ");

    $stmt->bind_param('s', $hashedToken);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $errors['token'] = 'رابط الاستعادة غير صحيح أو منتهي الصلاحية';
    } else {
        $user = $result->fetch_assoc();
    }
} else {
    $errors['token'] = 'رابط الاستعادة مفقود';
}

// معالجة الطلب POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    // التحقق من CSRF Token
    if (!Security::verifyCSRFToken($_POST['_csrf_token'] ?? null)) {
        $errors['csrf'] = 'رمز الأمان غير صحيح';
    }

    if (empty($errors)) {
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        // التحقق من البيانات
        if (!Validation::validatePassword($password)) {
            $errors = array_merge($errors, Validation::getErrors());
        } elseif (!Validation::validatePasswordMatch($password, $passwordConfirm)) {
            $errors = array_merge($errors, Validation::getErrors());
        }

        if (empty($errors)) {
            // تحديث كلمة المرور
            $hashedPassword = Security::hashPassword($password);

            $updateStmt = db()->prepare("
                UPDATE users SET password = ? WHERE id = ?
            ");
            $updateStmt->bind_param('si', $hashedPassword, $user['user_id']);
            $updateStmt->execute();

            // تعليم الرمز كمستخدم
            $useStmt = db()->prepare("
                UPDATE password_resets SET used_at = NOW()
                WHERE user_id = ? AND token = ?
            ");
            $useStmt->bind_param('is', $user['user_id'], $hashedToken);
            $useStmt->execute();

            $success = true;
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
    <title>إعادة تعيين كلمة المرور - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="اعد تعيين كلمة المرور الخاصة بك">
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
            <h1 class="auth-title">إعادة تعيين كلمة المرور</h1>
            <p class="auth-subtitle">أدخل كلمة مرور جديدة</p>
        </div>

        <!-- رسائل النجاح والخطأ -->
        <?php if ($success): ?>
        <div class="alert alert-success" role="alert">
            <strong>تم بنجاح!</strong> تم تغيير كلمة المرور. يمكنك الآن 
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

        <!-- نموذج إعادة التعيين -->
        <?php if (!empty($errors) && isset($errors['token'])): ?>
        <!-- رابط غير صحيح -->
        <div style="text-align: center; padding: var(--spacing-lg);">
            <p style="color: var(--color-gray); margin-bottom: var(--spacing-lg);">
                هذا الرابط غير صحيح أو منتهي الصلاحية.
            </p>
            <a href="<?php echo SITE_URL; ?>forgot-password" class="btn btn-primary">
                طلب رابط جديد
            </a>
        </div>
        <?php elseif ($user && !$success): ?>
        <form id="resetPasswordForm" class="auth-form" method="POST" action="">
            
            <!-- CSRF Token -->
            <input type="hidden" name="_csrf_token" value="<?php echo $csrfToken; ?>">

            <!-- كلمة المرور الجديدة -->
            <div class="form-group">
                <label for="password" class="form-label required">كلمة المرور الجديدة</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="أدخل كلمة المرور الجديدة"
                    dir="ltr"
                    required
                    aria-label="كلمة المرور الجديدة"
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

            <!-- الأزرار -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block" aria-label="تحديث">
                    <span>تحديث كلمة المرور</span>
                </button>
            </div>

        </form>
        <?php endif; ?>

    </div>
</div>

<!-- JavaScript Files -->
<script src="<?php echo SITE_URL; ?>js/main.js"></script>
<script src="<?php echo SITE_URL; ?>js/auth-validation.js"></script>

</body>
</html>