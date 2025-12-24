<?php
/**
 * ==========================================
 * صفحة الفوائد المفضلة
 * ==========================================
 * 
 * الملف: content/favorites.php
 * الوصف: صفحة لعرض الفوائد المفضلة للعضو
 * 
 * الميزات الرئيسية:
 * - عرض الفوائد المفضلة للعضو الحالي فقط
 * - إمكانية حذف الفائدة من المفضلة
 * - البحث والفلترة للفوائد المفضلة
 * - عرض معلومات الفائدة
 * - تقسيم الصفحات (Pagination)
 * 
 * المتطلبات الأمنية:
 * - التحقق من تسجيل الدخول
 * - عرض فوائد المستخدم فقط
 * - منع SQL Injection
 * - XSS Protection
 */

// ==========================================
// استيراد الملفات المطلوبة
// ==========================================
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/connection.php';
require_once __DIR__ . '/../database/security.php';

// ==========================================
// منع الوصول المباشر للملف
// ==========================================
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('تم حظر الوصول المباشر إلى هذا الملف');
}

// ==========================================
// التحقق من تسجيل الدخول
// ==========================================
Security::requireLogin();

$userId = $_SESSION['user_id'];

// ==========================================
// معالجة متغيرات الطلب بأمان
// ==========================================

// الصفحة الحالية
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);

// البحث
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$search = htmlspecialchars($search);

// الترتيب
$sortBy = isset($_GET['sort']) ? trim($_GET['sort']) : 'latest';
$allowedSorts = ['latest', 'popular', 'oldest'];
$sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'latest';

// ==========================================
// حساب OFFSET للتقسيم
// ==========================================
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;

// ==========================================
// بناء استعلام العد
// ==========================================
$countQuery = "
    SELECT COUNT(*) as total
    FROM favorites f
    JOIN benefits b ON f.benefit_id = b.id
    WHERE f.user_id = ? AND b.status = 'published' AND b.deleted_at IS NULL
";

$countParams = [$userId];
$countTypes = 'i';

// إضافة شرط البحث
if (!empty($search)) {
    $countQuery .= " AND (b.title LIKE ? OR b.content LIKE ?)";
    $searchPattern = '%' . $search . '%';
    array_push($countParams, $searchPattern, $searchPattern);
    $countTypes .= 'ss';
}

// ==========================================
// تنفيذ استعلام العد
// ==========================================
$countStmt = db()->prepare($countQuery);
$countStmt->bind_param($countTypes, ...$countParams);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// ==========================================
// بناء الاستعلام الرئيسي
// ==========================================
$dataQuery = "
    SELECT 
        b.id, b.user_id, b.category_id, b.title, b.content, 
        b.tags, b.views_count, b.created_at,
        u.username, u.full_name,
        c.name as category_name,
        f.created_at as favorite_date
    FROM favorites f
    JOIN benefits b ON f.benefit_id = b.id
    JOIN users u ON b.user_id = u.id
    JOIN categories c ON b.category_id = c.id
    WHERE f.user_id = ? AND b.status = 'published' AND b.deleted_at IS NULL
";

$dataParams = [$userId];
$dataTypes = 'i';

// إضافة شرط البحث
if (!empty($search)) {
    $dataQuery .= " AND (b.title LIKE ? OR b.content LIKE ?)";
    $searchPattern = '%' . $search . '%';
    array_push($dataParams, $searchPattern, $searchPattern);
    $dataTypes .= 'ss';
}

// ==========================================
// إضافة الترتيب
// ==========================================
switch ($sortBy) {
    case 'popular':
        $dataQuery .= " ORDER BY b.views_count DESC";
        break;
    case 'oldest':
        $dataQuery .= " ORDER BY f.created_at ASC";
        break;
    case 'latest':
    default:
        $dataQuery .= " ORDER BY f.created_at DESC";
}

// ==========================================
// إضافة التقسيم
// ==========================================
$dataQuery .= " LIMIT ? OFFSET ?";
array_push($dataParams, $itemsPerPage, $offset);
$dataTypes .= 'ii';

// ==========================================
// تنفيذ الاستعلام الرئيسي
// ==========================================
$dataStmt = db()->prepare($dataQuery);
$dataStmt->bind_param($dataTypes, ...$dataParams);
$dataStmt->execute();
$favoritesResult = $dataStmt->get_result();

$favorites = [];
while ($favorite = $favoritesResult->fetch_assoc()) {
    $favorites[] = $favorite;
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الفوائد المفضلة - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="فوائدك المفضلة والمحفوظة">
    
    <!-- ==========================================
         استيراد ملفات CSS
         ========================================== -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/responsive.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/cards.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/buttons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/forms.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>css/benefits-page.css">
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
        <section class="page-header">
            <h1 class="page-title">الفوائد المفضلة</h1>
            <p class="page-subtitle">فوائدك المحفوظة والمفضلة</p>
        </section>

        <!-- ==========================================
             شريط البحث
             ========================================== -->
        <section class="filter-section">
            <div class="filter-container">
                <form id="filterForm" class="filter-form" method="GET" action="">
                    
                    <!-- حقل البحث -->
                    <div class="filter-item search-item">
                        <input 
                            type="text" 
                            name="q" 
                            class="form-control search-input"
                            placeholder="ابحث في المفضلة..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            dir="rtl"
                        >
                        <button type="submit" class="btn btn-primary btn-sm">
                            <span>بحث</span>
                        </button>
                    </div>

                    <!-- الترتيب -->
                    <div class="filter-item">
                        <select name="sort" class="form-control" onchange="document.getElementById('filterForm').submit()">
                            <option value="latest" <?php echo $sortBy == 'latest' ? 'selected' : ''; ?>>الأحدث مفضلة</option>
                            <option value="oldest" <?php echo $sortBy == 'oldest' ? 'selected' : ''; ?>>الأقدم مفضلة</option>
                            <option value="popular" <?php echo $sortBy == 'popular' ? 'selected' : ''; ?>>الأكثر مشاهدة</option>
                        </select>
                    </div>

                </form>
            </div>
        </section>

        <!-- ==========================================
             النتائج والرسائل
             ========================================== -->
        <?php if ($totalItems > 0): ?>
        <section class="results-info">
            <p>
                لديك <strong><?php echo $totalItems; ?></strong> فائدة مفضلة
                <?php if (!empty($search)): ?>
                    تطابق كلمة "<strong><?php echo htmlspecialchars($search); ?></strong>"
                <?php endif; ?>
            </p>
        </section>
        <?php endif; ?>

        <!-- ==========================================
             شبكة الفوائد المفضلة
             ========================================== -->
        <?php if (!empty($favorites)): ?>
        <section class="benefits-grid-section">
            <div class="benefits-grid">
                <?php foreach ($favorites as $benefit): ?>
                <article class="benefit-card">
                    <!-- العنوان -->
                    <h3 class="benefit-title">
                        <a href="<?php echo SITE_URL; ?>benefits?id=<?php echo $benefit['id']; ?>">
                            <?php echo htmlspecialchars($benefit['title']); ?>
                        </a>
                    </h3>

                    <!-- الفئة -->
                    <span class="benefit-category">
                        <?php echo htmlspecialchars($benefit['category_name']); ?>
                    </span>

                    <!-- المحتوى المختصر -->
                    <p class="benefit-content">
                        <?php echo htmlspecialchars(substr($benefit['content'], 0, 150)); ?>...
                    </p>

                    <!-- المعلومات -->
                    <div class="benefit-meta">
                        <!-- المؤلف -->
                        <div class="benefit-author">
                            <a href="<?php echo SITE_URL . htmlspecialchars($benefit['username']); ?>">
                                <?php echo htmlspecialchars($benefit['full_name']); ?>
                            </a>
                        </div>

                        <!-- الإحصائيات -->
                        <div class="benefit-stats">
                            <span class="benefit-date">
                                📅 <?php echo date('d/m/Y', strtotime($benefit['created_at'])); ?>
                            </span>
                            <span class="benefit-views">
                                👁️ <?php echo $benefit['views_count']; ?>
                            </span>
                        </div>
                    </div>

                    <!-- الأزرار -->
                    <div class="benefit-actions">
                        <a href="<?php echo SITE_URL; ?>benefits?id=<?php echo $benefit['id']; ?>" class="btn btn-sm btn-outline-primary">
                            اقرأ المزيد
                        </a>
                        <button class="btn btn-sm btn-danger remove-favorite" 
                                data-benefit-id="<?php echo $benefit['id']; ?>"
                                aria-label="حذف من المفضلة">
                            حذف من المفضلة
                        </button>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ==========================================
             تقسيم الصفحات
             ========================================== -->
        <?php if ($totalPages > 1): ?>
        <section class="pagination-section">
            <nav class="pagination" aria-label="تصفح الصفحات">
                <ul class="pagination-list">
                    <!-- الصفحة السابقة -->
                    <?php if ($page > 1): ?>
                    <li class="pagination-item">
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?>&sort=<?php echo $sortBy; ?>" class="pagination-link">
                            السابقة
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- الصفحات -->
                    <?php 
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <li class="pagination-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?>&sort=<?php echo $sortBy; ?>" class="pagination-link">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <!-- الصفحة التالية -->
                    <?php if ($page < $totalPages): ?>
                    <li class="pagination-item">
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?>&sort=<?php echo $sortBy; ?>" class="pagination-link">
                            التالية
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </section>
        <?php endif; ?>

        <?php else: ?>
        <!-- ==========================================
             رسالة عدم وجود فوائد مفضلة
             ========================================== -->
        <section class="no-results">
            <div class="no-results-box">
                <h2>لا توجد فوائد مفضلة</h2>
                <p>
                    <?php if (!empty($search)): ?>
                        لم نجد فوائد مفضلة تطابق بحثك
                    <?php else: ?>
                        لم تحفظ أي فوائد في المفضلة حتى الآن
                    <?php endif; ?>
                </p>
                <a href="<?php echo SITE_URL; ?>benefits" class="btn btn-primary">
                    استكشف الفوائد
                </a>
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
     ملفات JavaScript
     ========================================== -->
<script src="<?php echo SITE_URL; ?>js/main.js"></script>
<script src="<?php echo SITE_URL; ?>js/benefits-page.js"></script>

</body>
</html>