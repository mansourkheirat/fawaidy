<?php
/**
 * صفحة الدخول
 * التحقق من بيانات المستخدم وإنشاء جلسة
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../database/security.php';

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
$remember = false;

// معالجة الطلب POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من CSRF Token
    if (!Security::verifyCSRFToken($_POST['_csrf_token'] ?? null)) {
        $errors['csrf'] = 'رمز الأمان غير صحيح';
    }

    if (empty($errors)) {
        // جمع البيانات
        $emailOrUsername = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // التحقق من البيانات المطلوبة
        if (empty($emailOrUsername)) {
            $errors['email'] = 'البريد الإلكتروني أو اسم المستخدم مطلوب';
        }

        if (empty($password)) {
            $errors['password'] = 'كلمة المرور مطلوبة';
        }

        if (empty($errors)) {
            // البحث عن المستخدم
            $stmt = db()->prepare("
                SELECT id, username, email, password, role, email_verified, 
                       is_active, is_locked, deleted_at
                FROM users 
                WHERE (email = ? OR username = ?) AND deleted_at IS NULL
                LIMIT 1
            ");

            $stmt->bind_param('ss', $emailOrUsername, $emailOrUsername);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // التحقق من حالة الحساب
                if (!$user['is_active']) {
                    $errors['account'] = 'حسابك معطل. يرجى التواصل مع الدعم';
                } elseif ($user['is_locked']) {
                    $errors['account'] = 'حسابك مقفل. يرجى محاولة لاحقاً';
                } elseif (!$user['email_verified']) {
                    $errors['account'] = 'يجب التحقق من بريدك الإلكتروني أولاً';
                } elseif (!Security::verifyPassword($password, $user['password'])) {
                    // كلمة المرور غير صحيحة
                    $errors['password'] = 'كلمة المرور غير صحيحة';
                    
                    // تسجيل محاولة دخول فاشلة
                    $this->logFailedLoginAttempt($user['id']);
                } else {
                    // تسجيل الدخول بنجاح
                    $success = true;

                    // إنشاء الجلسة
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];

                    // إنشاء ملف تعريف (Remember Me)
                    if ($remember) {
                        $rememberToken = bin2hex(random_bytes(32));
                        $hashedToken = hash('sha256', $rememberToken);

                        $rememberStmt = db()->prepare("
                            INSERT INTO remember_tokens (user_id, token, expires_at)
                            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
                        ");
                        $rememberStmt->bind_param('is', $user['id'], $hashedToken);
                        $rememberStmt->execute();

                        // حفظ الرمز في الكوكي
                        setcookie(
                            'remember_token',
                            $rememberToken,
                            time() + (30 * 24 * 60 * 60),
                            '/',
                            SITE_DOMAIN,
                            !DEBUG_MODE,
                            true
                        );
                    }

                    // تحديث آخر دخول
                    $updateStmt = db()->prepare("
                        UPDATE users SET last_login = NOW() WHERE id = ?
                    ");
                    $updateStmt->bind_param('i', $user['id']);
                    $updateStmt->execute();

                    // إعادة التوجيه
                    header('Location: ' . SITE_URL);
                    exit;
                }
            } else {
                $errors['email'] = 'بيانات الدخول غير صحيحة';
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
    <title>الدخول - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="سجل دخولك إلى موقع فوائدي">
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
            <h1 class="auth-title">تسجيل الدخول</h1>
            <p class="auth-subtitle">أهلاً بعودتك</p>
        </div>

        <!-- رسائل الخطأ -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-error" role="alert">
            <strong>خطأ في الدخول:</strong>
            <ul style="margin: 10px 0 0 0; padding-right: 20px;">
                <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <!-- نموذج الدخول -->
        <form id="loginForm" class="auth-form" method="POST" action="">
            
            <!-- CSRF Token -->
            <input type="hidden" name="_csrf_token" value="<?php echo $csrfToken; ?>">

            <!-- البريد الإلكتروني أو اسم المستخدم -->
            <div class="form-group">
                <label for="email" class="form-label required">البريد الإلكتروني أو اسم المستخدم</label>
                <input 
                    type="text" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    placeholder="أدخل بريدك الإلكتروني أو اسم المستخدم"
                    dir="ltr"
                    required
                    aria-label="البريد الإلكتروني أو اسم المستخدم"
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
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
            </div>

            <!-- تذكرني -->
            <div class="form-group">
                <div class="form-check">
                    <input 
                        type="checkbox" 
                        id="remember" 
                        name="remember"
                        aria-label="تذكرني"
                    >
                    <label for="remember" class="form-check-label">تذكرني</label>
                </div>
            </div>

            <!-- الأزرار -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-block" aria-label="دخول">
                    <span>دخول</span>
                </button>
                <button type="reset" class="btn btn-secondary btn-block" aria-label="مسح">
                    <span>مسح</span>
                </button>
            </div>

            <!-- رابط نسيت كلمة المرور -->
            <div style="text-align: center; margin-top: var(--spacing-lg);">
                <a href="<?php echo SITE_URL; ?>forgot-password" class="auth-link" style="font-size: var(--font-size-small);">
                    نسيت كلمة المرور؟
                </a>
            </div>

        </form>

        <!-- رابط التسجيل -->
        <div class="auth-footer">
            <p>ليس لديك حساب؟ 
                <a href="<?php echo SITE_URL; ?>register" class="auth-link">سجل الآن</a>
            </p>
        </div>

    </div>
</div>

<!-- JavaScript Files -->
<script src="<?php echo SITE_URL; ?>js/main.js"></script>
<script src="<?php echo SITE_URL; ?>js/auth-validation.js"></script>

</body>
</html>