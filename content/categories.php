<?php
/**
 * ==========================================
 * صفحة الفئات والمباحث
 * ==========================================
 * 
 * الملف: content/categories.php
 * الوصف: صفحة لعرض جميع الفئات والمباحث
 * 
 * الميزات الرئيسية:
 * - عرض جميع الفئات النشطة
 * - عدد الفوائد في كل فئة
 * - عرض الفوائد بناءً على الفئة المختارة
 * - البحث والفلترة في الفوائد
 * - تقسيم الصفحات
 * 
 * المتطلبات الأمنية:
 * - عرض الفئات النشطة فقط
 * - عرض الفوائد المنشورة فقط
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

// الفئة المختارة
$selectedCategory = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : 0;

// الصفحة الحالية
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);

// البحث
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$search = htmlspecialchars($search);

// الترتيب
$sortBy = isset($_GET['sort']) ? trim($_GET['sort']) : 'latest';
$allowedSorts = ['latest', 'popular'];
$sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'latest';

// ==========================================
// جلب جميع الفئات النشطة
// ==========================================
$categoriesStmt = db()->prepare("
    SELECT 
        c.id, c.name, c.description, 
        COUNT(b.id) as benefits_count
    FROM categories c
    LEFT JOIN benefits b ON c.id = b.category_id AND b.status = 'published' AND b.deleted_at IS NULL
    WHERE c.is_active = 1
    GROUP BY c.id
    ORDER BY c.name ASC
");

$categoriesStmt->execute();
$categoriesResult = $categoriesStmt->get_result();

$categories = [];
while ($category = $categoriesResult->fetch_assoc()) {
    $categories[] = $category;
}

// ==========================================
// إذا تم اختيار فئة، جلب الفوائد الخاصة بها
// ==========================================
$benefits = [];
$selectedCategoryData = null;
$totalPages = 1;

if ($selectedCategory > 0) {
    // ==========================================
    // التحقق من وجود الفئة
    // ==========================================
    $catStmt = db()->prepare("
        SELECT id, name, description FROM categories WHERE id = ? AND is_active = 1
    ");
    $catStmt->bind_param('i', $selectedCategory);
    $catStmt->execute();
    $catResult = $catStmt->get_result();

    if ($catResult->num_rows === 0) {
        $selectedCategory = 0;
    } else {
        $selectedCategoryData = $catResult->fetch_assoc();

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
            FROM benefits
            WHERE category_id = ? AND status = 'published' AND deleted_at IS NULL
        ";

        $countParams = [$selectedCategory];
        $countTypes = 'i';

        // إضافة شرط البحث
        if (!empty($search)) {
            $countQuery .= " AND (title LIKE ? OR content LIKE ?)";
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
                b.id, b.user_id, b.title, b.content, 
                b.tags, b.views_count, b.created_at,
                u.username, u.full_name
            FROM benefits b
            JOIN users u ON b.user_id = u.id
            WHERE b.category_id = ? AND b.status = 'published' AND b.deleted_at IS NULL
        ";

        $dataParams = [$selectedCategory];
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
        if ($sortBy === 'popular') {
            $dataQuery .= " ORDER BY b.views_count DESC";
        } else {
            $dataQuery .= " ORDER BY b.created_at DESC";
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
        $benefitsResult = $dataStmt->get_result();

        while ($benefit = $benefitsResult->fetch_assoc()) {
            $benefits[] = $benefit;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الفئات والمباحث - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="استكشف جميع فئات الفوائد العلمية">
    
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
            <h1 class="page-title">الفئات والمباحث</h1>
            <p class="page-subtitle">استكشف جميع المواضيع والفئات العلمية</p>
        </section>

        <!-- ==========================================
             إذا لم تكن هناك فئة مختارة: عرض جميع الفئات
             ========================================== -->
        <?php if ($selectedCategory === 0): ?>

        <section class="categories-grid-section">
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <div class="category-icon">📚</div>
                    <h3 class="category-name">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </h3>
                    <p class="category-count">
                        <?php echo $category['benefits_count']; ?> فائدة
                    </p>
                    <?php if (!empty($category['description'])): ?>
                    <p class="category-description">
                        <?php echo htmlspecialchars($category['description']); ?>
                    </p>
                    <?php endif; ?>
                    <a href="?category=<?php echo $category['id']; ?>" class="btn btn-sm btn-outline-primary">
                        عرض الفوائد
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <?php else: ?>

        <!-- ==========================================
             إذا تم اختيار فئة: عرض الفوائد الخاصة بها
             ========================================== -->

        <!-- رابط العودة والفئة المختارة -->
        <section class="selected-category-section">
            <div class="category-header">
                <a href="?" class="btn btn-secondary btn-sm">← الفئات</a>
                <h2 class="category-title">
                    <?php echo htmlspecialchars($selectedCategoryData['name']); ?>
                </h2>
            </div>
            <?php if (!empty($selectedCategoryData['description'])): ?>
            <p class="category-description">
                <?php echo htmlspecialchars($selectedCategoryData['description']); ?>
            </p>
            <?php endif; ?>
        </section>

        <!-- شريط البحث والفلترة -->
        <section class="filter-section">
            <div class="filter-container">
                <form id="filterForm" class="filter-form" method="GET" action="">
                    <input type="hidden" name="category" value="<?php echo $selectedCategory; ?>">
                    
                    <!-- حقل البحث -->
                    <div class="filter-item search-item">
                        <input 
                            type="text" 
                            name="q" 
                            class="form-control search-input"
                            placeholder="ابحث في هذه الفئة..."
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
                            <option value="latest" <?php echo $sortBy == 'latest' ? 'selected' : ''; ?>>الأحدث</option>
                            <option value="popular" <?php echo $sortBy == 'popular' ? 'selected' : ''; ?>>الأكثر مشاهدة</option>
                        </select>
                    </div>

                </form>
            </div>
        </section>

        <!-- النتائج -->
        <?php if (!empty($benefits)): ?>
        <section class="results-info">
            <p>
                <?php echo count($benefits); ?> من <?php echo $totalItems; ?> فائدة
            </p>
        </section>

        <!-- شبكة الفوائد -->
        <section class="benefits-grid-section">
            <div class="benefits-grid">
                <?php foreach ($benefits as $benefit): ?>
                <article class="benefit-card">
                    <!-- العنوان -->
                    <h3 class="benefit-title">
                        <a href="<?php echo SITE_URL; ?>benefits?id=<?php echo $benefit['id']; ?>">
                            <?php echo htmlspecialchars($benefit['title']); ?>
                        </a>
                    </h3>

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

                    <!-- الزر -->
                    <a href="<?php echo SITE_URL; ?>benefits?id=<?php echo $benefit['id']; ?>" class="btn btn-sm btn-outline-primary">
                        اقرأ المزيد
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- تقسيم الصفحات -->
        <?php if ($totalPages > 1): ?>
        <section class="pagination-section">
            <nav class="pagination" aria-label="تصفح الصفحات">
                <ul class="pagination-list">
                    <?php if ($page > 1): ?>
                    <li class="pagination-item">
                        <a href="?category=<?php echo $selectedCategory; ?>&page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?>&sort=<?php echo $sortBy; ?>" class="pagination-link">
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
                        <a href="?category=<?php echo $selectedCategory; ?>&page=<?php echo $i; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?>&sort=<?php echo $sortBy; ?>" class="pagination-link">
                            <?php echo $i; ?>
                        </a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                    <li class="pagination-item">
                        <a href="?category=<?php echo $selectedCategory; ?>&page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&q=' . urlencode($search) : ''; ?>&sort=<?php echo $sortBy; ?>" class="pagination-link">
                            التالية
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </section>
        <?php endif; ?>

        <?php else: ?>
        <!-- رسالة عدم وجود نتائج -->
        <section class="no-results">
            <div class="no-results-box">
                <h2>لا توجد فوائد</h2>
                <p>لم نجد فوائد في هذه الفئة</p>
                <a href="?" class="btn btn-primary">العودة للفئات</a>
            </div>
        </section>
        <?php endif; ?>

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