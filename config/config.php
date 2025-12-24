<?php
/**
 * ملف الإعدادات العامة للموقع
 * يحتوي على إعدادات قاعدة البيانات والثوابت الأساسية
 */

// منع الوصول المباشر للملف
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('تم حظر الوصول المباشر إلى هذا الملف');
}

// ================================
// إعدادات قاعدة البيانات
// ================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'fawaidy_db');
define('DB_CHARSET', 'utf8mb4');

// ================================
// إعدادات الموقع
// ================================
define('SITE_NAME', 'فوائدي');
define('SITE_URL', 'http://localhost/Fawaidy/');
define('SITE_DOMAIN', 'localhost');

// ================================
// إعدادات الأمان
// ================================
define('JWT_SECRET', 'your_secret_key_here_change_this');
define('CSRF_TOKEN_NAME', '_csrf_token');
define('SESSION_TIMEOUT', 3600); // ساعة واحدة

// ================================
// إعدادات البريد الإلكتروني
// ================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');
define('ADMIN_EMAIL', 'admin@fawaidy.com');

// ================================
// إعدادات الملفات والمساحة
// ================================
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// ================================
// إعدادات الصفحات
// ================================
define('ITEMS_PER_PAGE', 10);
define('RECENT_ITEMS', 3);

// ================================
// إعدادات الأمان المتقدمة
// ================================
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_OPTIONS', ['cost' => 12]);

// ================================
// إعدادات التوقيت
// ================================
date_default_timezone_set('UTC');
define('SITE_TIMEZONE', 'Asia/Riyadh');

// ================================
// وضع التطوير/الإنتاج
// ================================
define('ENVIRONMENT', 'development'); // development أو production
define('DEBUG_MODE', ENVIRONMENT === 'development');

// إذا كان وضع التطوير، نعرض الأخطاء
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/error.log');
}

// ================================
// ألوان الموقع
// ================================
define('COLOR_PRIMARY', '#1a1a2e');      // الأسود الداكن
define('COLOR_SECONDARY', '#ffffff');   // الأبيض
define('COLOR_GRAY', '#6c757d');        // الرمادي
define('COLOR_ACCENT', '#003d82');      // الأزرق الداكن

// ================================
// رتب الأعضاء
// ================================
define('ROLE_SUPER_ADMIN', 4);  // المدير العام
define('ROLE_ADMIN', 3);        // المدير
define('ROLE_PREMIUM', 2);      // عضو مميز
define('ROLE_MEMBER', 1);       // عضو عادي

// بدء الجلسة بشكل آمن
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_TIMEOUT,
        'path' => '/',
        'domain' => SITE_DOMAIN,
        'secure' => !DEBUG_MODE, // true في الإنتاج
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}
?>