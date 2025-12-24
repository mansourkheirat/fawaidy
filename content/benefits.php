<?php
/**
 * ==========================================
 * صفحة عرض الفوائد
 * ==========================================
 * 
 * الملف: content/benefits.php
 * الوصف: صفحة لعرض جميع الفوائد الموجودة في الموقع
 * 
 * الميزات الرئيسية:
 * - عرض الفوائد المنشورة
 * - البحث عن الفوائد
 * - تصفية حسب الفئة
 * - ترتيب حسب التاريخ أو الشهرة
 * - تقسيم الصفحات (Pagination)
 * - معلومات الفائدة (المؤلف، التاريخ، الآراء)
 * 
 * المتطلبات الأمنية:
 * - عرض الفوائد المنشورة فقط
 * - تصفية آمنة من البيانات
 * - منع SQL Injection
 * - XSS Protection
 * 
 * الصلاحيات:
 * - يمكن لأي شخص عرض الفوائد
 * - الأعضاء المسجلين يمكنهم إضافة للمفضلات
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
$page = max(1, $page); // التأكد من أن الصفحة >= 1

// البحث
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$search = htmlspecialchars($search); // حماية من XSS

// الفئة
$category = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : 0;

// الترتيب
$sortBy = isset($_GET['sort']) ? trim($_GET['sort']) : 'latest';
$allowedSorts = ['latest', 'popular', 'trending'];
$sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'latest';

// ==========================================
// حساب OFFSET للتقسيم
// ==========================================
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;

// ==========================================
// بناء استعلام العد (للتقسيم)
// ==========================================
$countQuery = "
    SELECT COUNT(*) as total
    FROM benefits
    WHERE status = 'published' AND deleted_at IS NULL
";
$countParams = [];
$countTypes = '';

// إضافة شرط البحث
if (!empty($search)) {
    $countQuery .= " AND (title LIKE ? OR content LIKE ?)";
    $searchPattern = '%' . Security::escapeSql($search) . '%';
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
// بناء الاستعلام الرئيسي (جلب الفوائد)
// ==========================================
$dataQuery = "
    SELECT 
        b.id, b.user_id, b.category_id, b.title, b.content, 
        b.tags, b.views_count, b.created_at,
        u.username, u.full_name, u.avatar,
        c.name as category_name
    FROM benefits b
    JOIN users u ON b.user_id = u.id
    JOIN categories c ON b.category_id = c.id
    WHERE b.status = 'published' AND b.deleted_at IS NULL
";

$dataParams = [];
$dataTypes = '';

// إضافة شروط البحث والفلترة
if (!empty($search)) {
    $dataQuery .= " AND (b.title LIKE ? OR b.content LIKE ?)";
    $dataParams = [$searchPattern, $searchPattern];
    $dataTypes = 'ss';
}

if ($category > 0) {
    $dataQuery .= " AND b.category_id = ?";
    $dataParams[] = $category;
    $dataTypes .= 'i';
}

// ==========================================
// إضافة الترتيب
// ==========================================
switch ($sortBy) {
    case 'popular':
        $dataQuery .= " ORDER BY b.views_count DESC";
        break;
    case 'trending':
        $dataQuery .= " ORDER BY b.created_at DESC LIMIT 100";
        break;
    case 'latest':
    default:
        $dataQuery .= " ORDER BY b.created_at DESC";
}

// ==========================================
// إضافة التقسيم (Pagination)
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
$benefitsResult = $dataStmt->get_result();

$benefits = [];
while ($benefit = $benefitsResult->fetch_assoc()) {
    $benefits[] = $benefit;
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
    <title>الفوائد - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="اطلع على جميع الفوائد العلمية المنشورة على الموقع">
    
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
            <h1 class="page-title">الفوائد العلمية</h1>
            <p class="page-subtitle">اطلع على أحدث الفوائد والمعلومات من أعضاء الموقع</p>
        </section>

        <!-- ==========================================
             شريط الفلترة والبحث
             ========================================== -->
        <section class="filter-section">
            <div class="filter-container">
                
                <!-- ==========================================
                     نموذج البحث والفلترة
                     ========================================== -->
                <form id="filterForm" class="filter-form" method="GET" action="">
                    
                    <!-- حقل البحث -->
                    <div class="filter-item search-item">
                        <input 
                            type="text" 
                            name="q" 
                            class="form-control search-input"
                            placeholder="ابحث عن فائدة..."
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
             النتائج والرسائل
             ========================================== -->
        <?php if ($totalItems > 0): ?>
        <section class="results-info">
            <p>
                تم العثور على <strong><?php echo $totalItems; ?></strong> فائدة
                <?php if (!empty($search)): ?>
                    عن كلمة "<strong><?php echo htmlspecialchars($search); ?></strong>"
                <?php endif; ?>
            </p>
        </section>
        <?php endif; ?>

        <!-- ==========================================
             شبكة الفوائد
             ========================================== -->
        <?php if (!empty($benefits)): ?>
        <section class="benefits-grid-section">
            <div class="benefits-grid">
                <?php foreach ($benefits as $benefit): ?>
                <article class="benefit-card">
                    <!-- العنوان -->
                    <h3 class="benefit-title">
                        <a href="<?php echo SITE_URL; ?>benefit/<?php echo $benefit['id']; ?>">
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

                        <!-- التاريخ والمشاهدات -->
                        <div class="benefit-stats">
                            <span class="benefit-date">
                                📅 <?php echo date('d/m/Y', strtotime($benefit['created_at'])); ?>
                            </span>
                            <span class="benefit-views">
                                👁️ <?php echo $benefit['views_count']; ?>
                            </span>
                        </div>
                    </div>

                    <!-- الزر -->
                    <a href="<?php echo SITE_URL; ?>benefit/<?php echo $benefit['id']; ?>" class="btn btn-sm btn-outline-primary">
                        اقرأ المزيد
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ==========================================
             تقسيم الصفحات (Pagination)
             ========================================== -->
        <?php if ($totalPages > 1): ?>
        <section class="pagination-section">
            <nav class="pagination" aria-label="تصفح الصفحات">
                <ul class="pagination-list">
                    <!-- الصفحة السابقة -->
                    <?php if ($page > 1): ?>
                    <li class="pagination-item">
                        <a href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?><?php echo $category > 0 ? '&category=' . $category : ''; ?>" class="pagination-link">
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
                        <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?><?php echo $category > 0 ? '&category=' . $category : ''; ?>" class="pagination-link">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <!-- الصفحة التالية -->
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
                <h2>لا توجد فوائد</h2>
                <p>لم نجد أي فوائد تطابق بحثك</p>
                <a href="?page=1" class="btn btn-primary">عرض جميع الفوائد</a>
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