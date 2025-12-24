<?php
/**
 * صفحة الخروج
 * تسجيل خروج المستخدم بأمان
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/connection.php';

// منع الوصول المباشر
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('تم حظر الوصول المباشر إلى هذا الملف');
}

// التحقق من أن المستخدم مسجل دخول
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL);
    exit;
}

// الحصول على معرف المستخدم
$userId = $_SESSION['user_id'];

// حذف ملفات التعريف (Remember Tokens)
$stmt = db()->prepare("
    DELETE FROM remember_tokens 
    WHERE user_id = ? AND expires_at > NOW()
");
$stmt->bind_param('i', $userId);
$stmt->execute();

// حذف الكوكي
setcookie('remember_token', '', time() - 3600, '/', SITE_DOMAIN, !DEBUG_MODE, true);

// إنهاء الجلسة
session_destroy();

// إعادة التوجيه للرئيسية
header('Location: ' . SITE_URL);
exit;
?>