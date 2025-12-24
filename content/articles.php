<?php
/**
 * ==========================================
 * صفحة عرض المقالات
 * ==========================================
 * 
 * الملف: content/articles.php
 * الوصف: صفحة لعرض المقالات (متاحة للأعضاء المميزين فقط)
 * 
 * الميزات الرئيسية:
 * - عرض المقالات المنشورة فقط
 * - البحث والفلترة عن المقالات
 * - فلترة حسب الفئة
 * - ترتيب حسب التاريخ أو الشهرة
 * - تقسيم الصفحات (Pagination)
 * - عرض المقالة الكاملة
 * 
 * المتطلبات الأمنية:
 * - عرض المقالات المنشورة فقط
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
// معالجة متغيرات الطلب بأمان
// ==========================================

// الصفحة الحالية
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);

// البحث
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$search = htmlspecialchars($search);

// الفئة
$category = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : 0;

// الترتيب
$sortBy = isset($_GET['sort']) ? trim($_GET['sort']) : 'latest';
$allowedSorts = ['latest', 'popular', 'trending'];
$sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'latest';

// معرّف المقالة المختارة
$selectedArticle = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

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
    FROM articles
    WHERE status = 'published' AND deleted_at IS NULL
";

$countParams = [];
$countTypes = '';

// إضافة شرط البحث
if (!empty($search)) {
    $countQuery .= " AND (title LIKE ? OR content LIKE ?)";
    $searchPattern = '%' . $search . '%';
    $countParams = [$searchPattern, $searchPattern];
    $countTypes = 'ss';
}

// إضافة شرط الفئة
if ($category > 0) {
    $countQuery .= " AND category_id = ?";
    $countParams[] = $category;
    $countTypes .= 'i';
}

// ==========================================
// تنفيذ استعلام العد
// ==========================================
$countStmt = db()->prepare($countQuery);
if (!empty($countParams)) {
    $countStmt->bind_param($countTypes, ...$countParams);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalItems = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalItems / $itemsPerPage);

// ==========================================
// بناء الاستعلام الرئيسي
// ==========================================
$dataQuery = "
    SELECT 
        a.id, a.user_id, a.category_id, a.title, a.content, 
        a.tags, a.views_count, a.created_at,
        u.username, u.full_name,
        c.name as category_name
    FROM articles a
    JOIN users u ON a.user_id = u.id
    JOIN categories c ON a.category_id = c.id
    WHERE a.status = 'published' AND a.deleted_at IS NULL
";

$dataParams = [];
$dataTypes = '';

// إضافة شروط البحث والفلترة
if (!empty($search)) {
    $dataQuery .= " AND (a.title LIKE ? OR a.content LIKE ?)";
    $searchPattern = '%' . $search . '%';
    $dataParams = [$searchPattern, $searchPattern];
    $dataTypes = 'ss';
}

if ($category > 0) {
    $dataQuery .= " AND a.category_id = ?";
    $dataParams[] = $category;
    $dataTypes .= 'i';
}

// ==========================================
// إضافة الترتيب
// ==========================================
switch ($sortBy) {
    case 'popular':
        $dataQuery .= " ORDER BY a.views_count DESC";
        break;
    case 'trending':
        $dataQuery .= " ORDER BY a.created_at DESC";
        break;
    case 'latest':
    default:
        $dataQuery .= " ORDER BY a.created_at DESC";
}

// ==========================================
// إضافة التقسيم
// ==========================================
$dataQuery .= " LIMIT ? OFFSET ?";
$dataParams[] = $itemsPerPage;
$dataParams[] = $offset;
$dataTypes .= 'ii';

// ==========================================
// تنفيذ الاستعلام الرئيسي
// ==========================================
$dataStmt = db()->prepare($dataQuery);
if (!empty($dataParams)) {
    $dataStmt->bind_param($dataTypes, ...$dataParams);
}
$dataStmt->execute();
$articlesResult = $dataStmt->get_result();

$articles = [];
while ($article = $articlesResult->fetch_assoc()) {
    $articles[] = $article;
}

// ==========================================
// جلب المقالة الكاملة إذا تم اختيارها
// ==========================================
$selectedArticleData = null;
if ($selectedArticle > 0) {
    $articleStmt = db()->prepare("
        SELECT 
            a.id, a.user_id, a.category_id, a.title, a.content, 
            a.tags, a.views_count, a.created_at,
            u.username, u.full_name,
            c.name as category_name
        FROM articles a
        JOIN users u ON a.user_id = u.id
        JOIN categories c ON a.category_id = c.id
        WHERE a.id = ? AND a.status = 'published' AND a.deleted_at IS NULL
        LIMIT 1
    ");
    
    $articleStmt->bind_param('i', $selectedArticle);
    $articleStmt->execute();
    $articleResult = $articleStmt->get_result();
    
    if ($articleResult->num_rows > 0) {
        $selectedArticleData = $articleResult->fetch_assoc();
        
        // تحديث عدد المشاهدات
        $updateStmt = db()->prepare("
            UPDATE articles SET views_count = views_count + 1 WHERE id = ?
        ");
        $updateStmt->bind_param('i', $selectedArticle);
        $updateStmt->execute();
    }
}

// ==========================================
// جلب الفئات للفلترة
// ==========================================
$categoriesStmt = db()->prepare("
    SELECT id, name
    FROM categories
    WHERE is_active = 1
    ORDER BY name ASC
");
$categoriesStmt->execute();
$categoriesResult = $categoriesStmt->get_result();
$categories = [];
while ($cat = $categoriesResult->fetch_assoc()) {
    $categories[] = $cat;
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المقالات - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="اطلع على أفضل المقالات العلمية المفصلة">
    
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
            <h1 class="page-title">المقالات العلمية</h1>
            <p class="page-subtitle">اطلع على أفضل المقالات المفصلة والشاملة</p>
        </section>

        <!-- ==========================================
             شريط الفلترة والبحث
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
                            placeholder="ابحث عن مقالة..."
                            value="<?php echo htmlspecialchars($search); ?>"
                            dir="rtl"
                        >
                        <button type="submit" class="btn btn-primary btn-sm">
                            <span>بحث</span>
                        </button>
                    </div>

                    <!-- قائمة الفئات -->
                    <div class="filter-item">
                        <select name="category" class="form-control" onchange="document.getElementById('filterForm').submit()">
                            <option value="">جميع الفئات</option>
                            <?php foreach ($categories as $cat): ?>
                            <option 
                                value="<?php echo $cat['id']; ?>"
                                <?php echo $category == $cat['id'] ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- الترتيب -->
                    <div class="filter-item">
                        <select name="sort" class="form-control" onchange="document.getElementById('filterForm').submit()">
                            <option value="latest" <?php echo $sortBy == 'latest' ? 'selected' : ''; ?>>الأحدث</option>
                            <option value="popular" <?php echo $sortBy == 'popular' ? 'selected' : ''; ?>>الأكثر مشاهدة</option>
                            <option value="trending" <?php echo $sortBy == 'trending' ? 'selected' : ''; ?>>الشهيرة</option>
                        </select>
                    </div>

                </form>
            </div>
        </section>

        <!-- ==========================================
             عرض المقالة الكاملة
             ========================================== -->
        <?php if ($selectedArticleData): ?>
        <section class="benefit-detail-section">
            <div class="benefit-detail-card">
                <div class="benefit-detail-header">
                    <h2 class="benefit-detail-title">
                        <?php echo htmlspecialchars($selectedArticleData['title']); ?>
                    </h2>
                    <span class="benefit-detail-category">
                        <?php echo htmlspecialchars($selectedArticleData['category_name']); ?>
                    </span>
                </div>

                <div class="benefit-detail-meta">
                    <div class="meta-left">
                        <span class="meta-author">
                            بقلم: <a href="<?php echo SITE_URL . htmlspecialchars($selectedArticleData['username']); ?>">
                                <?php echo htmlspecialchars($selectedArticleData['full_name']); ?>
                            </a>
                        </span>
                        <span class="meta-date">
                            📅 <?php echo date('d/m/Y', strtotime($selectedArticleData['created_at'])); ?>
                        </span>
                    </div>
                    <div class="meta-right">
                        <span class="meta-views">
                            👁️ <?php echo $selectedArticleData['views_count']; ?> مشاهدة
                        </span>
                    </div>
                </div>

                <div class="benefit-detail-content">
                    <?php echo nl2br(htmlspecialchars($selectedArticleData['content'])); ?>
                </div>

                <?php if (!empty($selectedArticleData['tags'])): ?>
                <div class="benefit-detail-tags">
                    <?php 
                    $tags = array_filter(explode(',', $selectedArticleData['tags']));
                    foreach ($tags as $tag): 
                    ?>
                    <a href="?q=<?php echo urlencode(trim($tag)); ?>" class="tag">
                        #<?php echo htmlspecialchars(trim($tag)); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <div class="benefit-detail-actions">
                    <a href="?" class="btn btn-secondary">← العودة للقائمة</a>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- ==========================================
             النتائج والرسائل
             ========================================== -->
        <?php if ($totalItems > 0 && !$selectedArticleData): ?>
        <section class="results-info">
            <p>
                تم العثور على <strong><?php echo $totalItems; ?></strong> مقالة
                <?php if (!empty($search)): ?>
                    عن كلمة "<strong><?php echo htmlspecialchars($search); ?></strong>"
                <?php endif; ?>
            </p>
        </section>
        <?php endif; ?>

        <!-- ==========================================
             شبكة المقالات
             ========================================== -->
        <?php if (!empty($articles) && !$selectedArticleData): ?>
        <section class="benefits-grid-section">
            <div class="benefits-grid">
                <?php foreach ($articles as $article): ?>
                <article class="benefit-card">
                    <!-- العنوان -->
                    <h3 class="benefit-title">
                        <a href="?id=<?php echo $article['id']; ?>">
                            <?php echo htmlspecialchars($article['title']); ?>
                        </a>
                    </h3>

                    <!-- الفئة -->
                    <span class="benefit-category">
                        <?php echo htmlspecialchars($article['category_name']); ?>
                    </span>

                    <!-- المحتوى المختصر -->
                    <p class="benefit-content">
                        <?php echo htmlspecialchars(substr($article['content'], 0, 150)); ?>...
                    </p>

                    <!-- المعلومات -->
                    <div class="benefit-meta">
                        <!-- المؤلف -->
                        <div class="benefit-author">
                            <a href="<?php echo SITE_URL . htmlspecialchars($article['username']); ?>">
                                <?php echo htmlspecialchars($article['full_name']); ?>
                            </a>
                        </div>

                        <!-- التاريخ والمشاهدات -->
                        <div class="benefit-stats">
                            <span class="benefit-date">
                                📅 <?php echo date('d/m/Y', strtotime($article['created_at'])); ?>
                            </span>
                            <span class="benefit-views">
                                👁️ <?php echo $article['views_count']; ?>
                            </span>
                        </div>
                    </div>

                    <!-- الزر -->
                    <a href="?id=<?php echo $article['id']; ?>" class="btn btn-sm btn-outline-primary">
                        اقرأ المقالة
                    </a>
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
                    <?php if ($page > 1): ?>
                    <li class="pagination-item">
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?><?php echo $category > 0 ? '&category=' . $category : ''; ?>" class="pagination-link">
                            السابقة
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php 
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <li class="pagination-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?><?php echo $category > 0 ? '&category=' . $category : ''; ?>" class="pagination-link">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <li class="pagination-item">
                        <a href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?><?php echo $category > 0 ? '&category=' . $category : ''; ?>" class="pagination-link">
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
             رسالة عدم وجود نتائج
             ========================================== -->
        <section class="no-results">
            <div class="no-results-box">
                <h2>لا توجد مقالات</h2>
                <p>لم نجد أي مقالات تطابق بحثك</p>
                <a href="?" class="btn btn-primary">عرض جميع المقالات</a>
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