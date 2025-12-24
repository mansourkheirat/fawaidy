<?php
/**
 * ملف الشريط العلوي
 * يحتوي على هيكل الشريط العلوي للموقع
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../database/security.php';

// منع الوصول المباشر
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('تم حظر الوصول المباشر إلى هذا الملف');
}

// متغيرات المستخدم
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
$userRole = $_SESSION['user_role'] ?? ROLE_MEMBER;
$isAdmin = $userRole >= ROLE_ADMIN;
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo SITE_NAME; ?> - فوائد علمية مميزة</title>
    <meta name="description" content="موقع فوائدي لنشر الفوائد العلمية المميزة">
    <meta name="author" content="<?php echo SITE_NAME; ?>">
    <meta name="keywords" content="فوائد,علم,معرفة">
    
    <!-- الأيقونة -->
    <link rel="icon" type="image/svg+xml" href="<?php echo SITE_URL; ?>assets/icons/favicon.svg">
    
    <!-- ملفات CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/header.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/footer.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/buttons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/cards.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/forms.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/dropdowns.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/messages.css">
</head>
<body>

<!-- ================================
     الشريط العلوي
     ================================ -->
<header class="main-header">
    <div class="header-container">
        
        <!-- الجزء الأيمن: التاريخ -->
        <div class="header-right">
            <div class="date-section">
                <div class="day-name" id="dayName"></div>
                <div class="dates">
                    <div class="hijri-date" id="hijriDate"></div>
                    <div class="gregorian-date" id="gregorianDate"></div>
                </div>
            </div>
        </div>
        
        <!-- الجزء الأوسط: الشعار -->
        <div class="header-center">
            <a href="<?php echo SITE_URL; ?>" class="logo">
                <svg class="logo-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <!-- يمكن إضافة أيقونة مخصصة هنا -->
                    <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="2"/>
                    <text x="50" y="60" font-size="40" text-anchor="middle" fill="currentColor" font-family="Arial">ف</text>
                </svg>
                <span class="logo-text"><?php echo SITE_NAME; ?></span>
            </a>
        </div>
        
        <!-- الجزء الأيسر: قائمة المستخدم والملاحة -->
        <div class="header-left">
            
            <!-- أزرار الملاحة -->
            <nav class="header-nav">
                <a href="<?php echo SITE_URL; ?>" class="nav-link">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">الرئيسة</span>
                </a>
                <a href="<?php echo SITE_URL; ?>members" class="nav-link">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">الأعضاء</span>
                </a>
                <a href="<?php echo SITE_URL; ?>benefits" class="nav-link">
                    <span class="nav-icon">💡</span>
                    <span class="nav-text">فوائدي</span>
                </a>
                <?php if ($isLoggedIn): ?>
                <a href="<?php echo SITE_URL; ?>favorites" class="nav-link">
                    <span class="nav-icon">⭐</span>
                    <span class="nav-text">مفضلتي</span>
                </a>
                <?php endif; ?>
            </nav>
            
            <!-- قائمة المستخدم -->
            <?php if ($isLoggedIn): ?>
            <div class="user-menu-container">
                <button class="user-menu-btn" id="userMenuBtn">
                    <span class="username"><?php echo htmlspecialchars($username); ?></span>
                    <svg class="dropdown-arrow" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 10l5 5 5-5z" fill="currentColor"/>
                    </svg>
                </button>
                
                <!-- القائمة المنسدلة -->
                <div class="dropdown-menu" id="userDropdown">
                    <a href="<?php echo SITE_URL . htmlspecialchars($username); ?>" class="dropdown-item">
                        <span class="item-icon">👤</span>
                        <span class="item-text">الملف الشخصي</span>
                    </a>
                    <a href="<?php echo SITE_URL; ?>settings" class="dropdown-item">
                        <span class="item-icon">⚙️</span>
                        <span class="item-text">الإعدادات</span>
                    </a>
                    <?php if ($isAdmin): ?>
                    <hr class="dropdown-divider">
                    <a href="<?php echo SITE_URL; ?>admin" class="dropdown-item admin-item">
                        <span class="item-icon">🔐</span>
                        <span class="item-text">الإدارة</span>
                    </a>
                    <?php endif; ?>
                    <hr class="dropdown-divider">
                    <a href="<?php echo SITE_URL; ?>logout" class="dropdown-item logout-item">
                        <span class="item-icon">🚪</span>
                        <span class="item-text">الخروج</span>
                    </a>
                </div>
            </div>
            <?php else: ?>
            <!-- أزرار الدخول والتسجيل -->
            <div class="auth-buttons">
                <a href="<?php echo SITE_URL; ?>login" class="btn btn-secondary btn-sm">
                    <span>دخول</span>
                </a>
                <a href="<?php echo SITE_URL; ?>register" class="btn btn-primary btn-sm">
                    <span>تسجيل</span>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- سكريبت الشريط العلوي -->
<script src="<?php echo SITE_URL; ?>js/main.js"></script>
<script src="<?php echo SITE_URL; ?>js/dates.js"></script>
<script src="<?php echo SITE_URL; ?>js/dropdowns.js"></script>

<script>
// تهيئة التاريخ
document.addEventListener('DOMContentLoaded', function() {
    updateDates();
    
    // تحديث التاريخ كل دقيقة
    setInterval(updateDates, 60000);
    
    // تهيئة القائمة المنسدلة
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    
    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
        });
        
        document.addEventListener('click', function() {
            userDropdown.classList.remove('active');
        });
        
        userDropdown.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});
</script>

<?php
// إغلاق الاتصال تلقائياً عند الحاجة
// تم تعيينه في ملف connection.php
?>