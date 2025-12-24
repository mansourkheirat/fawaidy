<?php
/**
 * ==========================================
 * صفحة الملف الشخصي للعضو
 * ==========================================
 * 
 * الوصف:
 * عرض المعلومات الشخصية للعضو
 * عرض الفوائد والمقالات
 * عرض معلومات التواصل والتعليم
 * 
 * الميزات:
 * - عرض البيانات الشخصية
 * - عرض إحصائيات العضو
 * - عرض آخر الفوائد
 * - عرض آخر المقالات (للأعضاء المميزين)
 * - شارات الرتب والحالة
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
// الحصول على اسم المستخدم من الرابط
// ==========================================
$username = isset($_GET['username']) ? trim($_GET['username']) : '';

if (empty($username)) {
    header('Location: ' . SITE_URL);
    exit;
}

// ==========================================
// جلب بيانات المستخدم
// ==========================================
$stmt = db()->prepare("
    SELECT 
        id, full_name, username, email, avatar, gender, country,
        birth_date_hijri, birth_date_gregorian, 
        bio, education_level, major, job_title,
        role, is_active, created_at, last_login
    FROM users 
    WHERE LOWER(username) = LOWER(?) AND deleted_at IS NULL AND is_active = 1
    LIMIT 1
");

$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    header('Location: ' . SITE_URL);
    exit;
}

$user = $result->fetch_assoc();
$userId = $user['id'];

// ==========================================
// جلب إحصائيات المستخدم
// ==========================================
$statsStmt = db()->prepare("
    SELECT 
        (SELECT COUNT(*) FROM benefits WHERE user_id = ?) as benefits_count,
        (SELECT COUNT(*) FROM articles WHERE user_id = ?) as articles_count,
        (SELECT COUNT(*) FROM favorites WHERE user_id = ?) as favorites_count
");

$statsStmt->bind_param('iii', $userId, $userId, $userId);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc();

// ==========================================
// جلب آخر الفوائد
// ==========================================
$benefitsStmt = db()->prepare("
    SELECT id, title, content, created_at, views_count
    FROM benefits 
    WHERE user_id = ? AND status = 'published'
    ORDER BY created_at DESC
    LIMIT 3
");

$benefitsStmt->bind_param('i', $userId);
$benefitsStmt->execute();
$benefitsResult = $benefitsStmt->get_result();
$recentBenefits = [];
while ($benefit = $benefitsResult->fetch_assoc()) {
    $recentBenefits[] = $benefit;
}

// ==========================================
// جلب آخر المقالات (للأعضاء المميزين فقط)
// ==========================================
$recentArticles = [];
if ($user['role'] >= ROLE_PREMIUM) {
    $articlesStmt = db()->prepare("
        SELECT id, title, content, created_at, views_count
        FROM articles 
        WHERE user_id = ? AND status = 'published'
        ORDER BY created_at DESC
        LIMIT 3
    ");

    $articlesStmt->bind_param('i', $userId);
    $articlesStmt->execute();
    $articlesResult = $articlesStmt->get_result();
    while ($article = $articlesResult->fetch_assoc()) {
        $recentArticles[] = $article;
    }
}

// ==========================================
// تحديد ألوان وأيقونات الرتب
// ==========================================
$roleInfo = [
    ROLE_SUPER_ADMIN => ['اسم' => 'المدير العام', 'اللون' => '#dc3545', 'الأيقونة' => '👑'],
    ROLE_ADMIN => ['اسم' => 'المدير', 'اللون' => '#fd7e14', 'الأيقونة' => '🔐'],
    ROLE_PREMIUM => ['اسم' => 'عضو مميز', 'اللون' => '#ffc107', 'الأيقونة' => '⭐'],
    ROLE_MEMBER => ['اسم' => 'عضو', 'اللون' => '#6c757d', 'الأيقونة' => '👤']
];

$currentRole = $roleInfo[$user['role']] ?? $roleInfo[ROLE_MEMBER];

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['full_name']); ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="الملف الشخصي للعضو <?php echo htmlspecialchars($user['username']); ?>">
    
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/profile.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/cards.css">
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
             بطاقة الملف الشخصي الرئيسية
             ========================================== -->
        <section class="profile-header-section">
            <div class="profile-header-card">
                
                <!-- صورة الملف الشخصي -->
                <div class="profile-avatar-container">
                    <div class="profile-avatar">
                        <?php if (!empty($user['avatar'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar']); ?>" 
                                 alt="<?php echo htmlspecialchars($user['full_name']); ?>"
                                 class="avatar-image">
                        <?php else: ?>
                            <span class="avatar-initials">
                                <?php echo substr($user['full_name'], 0, 1); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- معلومات الملف الشخصي -->
                <div class="profile-info-container">
                    <h1 class="profile-full-name"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p class="profile-username">@<?php echo htmlspecialchars($user['username']); ?></p>
                    
                    <!-- شارة الرتبة -->
                    <div class="profile-role-badge" style="background-color: <?php echo $currentRole['اللون']; ?>;">
                        <span class="badge-icon"><?php echo $currentRole['الأيقونة']; ?></span>
                        <span class="badge-text"><?php echo $currentRole['اسم']; ?></span>
                    </div>

                    <!-- النبذة -->
                    <?php if (!empty($user['bio'])): ?>
                    <p class="profile-bio"><?php echo htmlspecialchars($user['bio']); ?></p>
                    <?php endif; ?>

                    <!-- الإحصائيات -->
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['benefits_count']; ?></span>
                            <span class="stat-label">فائدة</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['articles_count']; ?></span>
                            <span class="stat-label">مقالة</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['favorites_count']; ?></span>
                            <span class="stat-label">مفضلة</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ==========================================
             البيانات الشخصية
             ========================================== -->
        <section class="profile-section">
            <h2 class="section-title">البيانات الشخصية</h2>
            
            <div class="profile-data-grid">
                <?php if (!empty($user['country'])): ?>
                <div class="profile-data-item">
                    <span class="data-label">البلد</span>
                    <span class="data-value"><?php echo htmlspecialchars($user['country']); ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($user['gender'])): ?>
                <div class="profile-data-item">
                    <span class="data-label">الجنس</span>
                    <span class="data-value">
                        <?php 
                        $genders = ['male' => 'ذكر', 'female' => 'أنثى', 'unspecified' => 'غير محدد'];
                        echo $genders[$user['gender']] ?? 'غير محدد';
                        ?>
                    </span>
                </div>
                <?php endif; ?>

                <?php if (!empty($user['birth_date_gregorian'])): ?>
                <div class="profile-data-item">
                    <span class="data-label">تاريخ الازدياد</span>
                    <span class="data-value"><?php echo htmlspecialchars($user['birth_date_gregorian']); ?></span>
                </div>
                <?php endif; ?>

                <div class="profile-data-item">
                    <span class="data-label">عضو منذ</span>
                    <span class="data-value"><?php echo htmlspecialchars($user['created_at']); ?></span>
                </div>
            </div>
        </section>

        <!-- ==========================================
             التعليم والعمل
             ========================================== -->
        <?php if (!empty($user['education_level']) || !empty($user['major']) || !empty($user['job_title'])): ?>
        <section class="profile-section">
            <h2 class="section-title">التعليم والعمل</h2>
            
            <div class="profile-data-grid">
                <?php if (!empty($user['education_level'])): ?>
                <div class="profile-data-item">
                    <span class="data-label">المستوى الدراسي</span>
                    <span class="data-value"><?php echo htmlspecialchars($user['education_level']); ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($user['major'])): ?>
                <div class="profile-data-item">
                    <span class="data-label">التخصص</span>
                    <span class="data-value"><?php echo htmlspecialchars($user['major']); ?></span>
                </div>
                <?php endif; ?>

                <?php if (!empty($user['job_title'])): ?>
                <div class="profile-data-item">
                    <span class="data-label">الوظيفة</span>
                    <span class="data-value"><?php echo htmlspecialchars($user['job_title']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- ==========================================
             آخر الفوائد
             ========================================== -->
        <?php if (!empty($recentBenefits)): ?>
        <section class="profile-section">
            <div class="section-header">
                <h2 class="section-title">آخر الفوائد</h2>
                <a href="<?php echo SITE_URL; ?>benefits?author=<?php echo $user['id']; ?>" class="view-all-link">
                    عرض الكل
                </a>
            </div>
            
            <div class="benefits-grid">
                <?php foreach ($recentBenefits as $benefit): ?>
                <div class="benefit-card">
                    <h3 class="benefit-title"><?php echo htmlspecialchars($benefit['title']); ?></h3>
                    <p class="benefit-content">
                        <?php echo htmlspecialchars(substr($benefit['content'], 0, 100)); ?>...
                    </p>
                    <div class="benefit-meta">
                        <span class="benefit-views">👁️ <?php echo $benefit['views_count']; ?></span>
                        <span class="benefit-date"><?php echo $benefit['created_at']; ?></span>
                    </div>
                    <a href="<?php echo SITE_URL; ?>benefit/<?php echo $benefit['id']; ?>" class="btn btn-sm btn-outline-primary">
                        اقرأ المزيد
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <!-- ==========================================
             آخر المقالات (للأعضاء المميزين)
             ========================================== -->
        <?php if ($user['role'] >= ROLE_PREMIUM && !empty($recentArticles)): ?>
        <section class="profile-section">
            <div class="section-header">
                <h2 class="section-title">آخر المقالات</h2>
                <a href="<?php echo SITE_URL; ?>articles?author=<?php echo $user['id']; ?>" class="view-all-link">
                    عرض الكل
                </a>
            </div>
            
            <div class="articles-grid">
                <?php foreach ($recentArticles as $article): ?>
                <div class="article-card">
                    <h3 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                    <p class="article-content">
                        <?php echo htmlspecialchars(substr($article['content'], 0, 100)); ?>...
                    </p>
                    <div class="article-meta">
                        <span class="article-views">👁️ <?php echo $article['views_count']; ?></span>
                        <span class="article-date"><?php echo $article['created_at']; ?></span>
                    </div>
                    <a href="<?php echo SITE_URL; ?>article/<?php echo $article['id']; ?>" class="btn btn-sm btn-outline-primary">
                        اقرأ المقالة
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

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
<script src="<?php echo SITE_URL; ?>js/profile.js"></script>

</body>
</html>