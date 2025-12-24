<?php
/**
 * ==========================================
 * صفحة عرض جميع الفوائد
 * ==========================================
 * 
 * الملف: content/benefits.php
 * الوصف: صفحة لعرض جميع الفوائد المنشورة في الموقع
 * 
 * الميزات الرئيسية:
 * - عرض الفوائد المنشورة فقط
 * - البحث عن الفوائد
 * - تصفية حسب الفئة
 * - ترتيب حسب التاريخ أو الشهرة
 * - تقسيم الصفحات (Pagination)
 * - عرض معلومات الفائدة (المؤلف، التاريخ، الآراء)
 * - عرض الفائدة الكاملة عند الضغط
 * 
 * المتطلبات الأمنية:
 * - عرض الفوائد المنشورة فقط
 * - تصفية آمنة من البيانات
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

// معرّف الفائدة المختارة (للعرض الكامل)
$selectedBenefit = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

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
// بناء الاستعلام الرئيسي (جلب الفوائد)
// ==========================================
$dataQuery = "
    SELECT 
        b.id, b.user_id, b.category_id, b.title, b.content, 
        b.tags, b.views_count, b.created_at,
        u.username, u.full_name,
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
        $dataQuery .= " ORDER BY b.created_at DESC";
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
    $dataStmt->bind_param($dataTypes, ...$countParams);
}
$dataStmt->execute();
$benefitsResult = $dataStmt->get_result();

$benefits = [];
while ($benefit = $benefitsResult->fetch_assoc()) {
    $benefits[] = $benefit;
}

// ==========================================
// جلب الفائدة الكاملة إذا تم اختيارها
// ==========================================
$selectedBenefitData = null;
if ($selectedBenefit > 0) {
    $benefitStmt = db()->prepare("
        SELECT 
            b.id, b.user_id, b.category_id, b.title, b.content, 
            b.tags, b.views_count, b.created_at,
            u.username, u.full_name,
            c.name as category_name
        FROM benefits b
        JOIN users u ON b.user_id = u.id
        JOIN categories c ON b.category_id = c.id
        WHERE b.id = ? AND b.status = 'published' AND b.deleted_at IS NULL
        LIMIT 1
    ");
    
    $benefitStmt->bind_param('i', $selectedBenefit);
    $benefitStmt->execute();
    $benefitResult = $benefitStmt->get_result();
    
    if ($benefitResult->num_rows > 0) {
        $selectedBenefitData = $benefitResult->fetch_assoc();
        
        // تحديث عدد المشاهدات
        $updateStmt = db()->prepare("
            UPDATE benefits SET views_count = views_count + 1 WHERE id = ?
        ");
        $updateStmt->bind_param('i', $selectedBenefit);
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

// ==========================================
// التحقق من تسجيل الدخول للمفضلة
// ==========================================
$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;
$userFavorites = [];

if ($isLoggedIn && $userId) {
    $favStmt = db()->prepare("
        SELECT benefit_id FROM favorites WHERE user_id = ?
    ");
    $favStmt->bind_param('i', $userId);
    $favStmt->execute();
    $favResult = $favStmt->get_result();
    
    while ($fav = $favResult->fetch_assoc()) {
        $userFavorites[] = $fav['benefit_id'];
    }
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
             عرض الفائدة الكاملة
             ========================================== -->
        <?php if ($selectedBenefitData): ?>
        <section class="benefit-detail-section">
            <div class="benefit-detail-card">
                <div class="benefit-detail-header">
                    <h2 class="benefit-detail-title">
                        <?php echo htmlspecialchars($selectedBenefitData['title']); ?>
                    </h2>
                    <span class="benefit-detail-category">
                        <?php echo htmlspecialchars($selectedBenefitData['category_name']); ?>
                    </span>
                </div>

                <div class="benefit-detail-meta">
                    <div class="meta-left">
                        <span class="meta-author">
                            بقلم: <a href="<?php echo SITE_URL . htmlspecialchars($selectedBenefitData['username']); ?>">
                                <?php echo htmlspecialchars($selectedBenefitData['full_name']); ?>
                            </a>
                        </span>
                        <span class="meta-date">
                            📅 <?php echo date('d/m/Y', strtotime($selectedBenefitData['created_at'])); ?>
                        </span>
                    </div>
                    <div class="meta-right">
                        <span class="meta-views">
                            👁️ <?php echo $selectedBenefitData['views_count']; ?> مشاهدة
                        </span>
                        <?php if ($isLoggedIn): ?>
                        <button class="btn-favorite <?php echo in_array($selectedBenefitData['id'], $userFavorites) ? 'active' : ''; ?>" 
                                data-benefit-id="<?php echo $selectedBenefitData['id']; ?>"
                                aria-label="إضافة للمفضلة">
                            ⭐
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="benefit-detail-content">
                    <?php echo nl2br(htmlspecialchars($selectedBenefitData['content'])); ?>
                </div>

                <?php if (!empty($selectedBenefitData['tags'])): ?>
                <div class="benefit-detail-tags">
                    <?php 
                    $tags = array_filter(explode(',', $selectedBenefitData['tags']));
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
        <?php if ($totalItems > 0 && !$selectedBenefitData): ?>
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
        <?php if (!empty($benefits) && !$selectedBenefitData): ?>
        <section class="benefits-grid-section">
            <div class="benefits-grid">
                <?php foreach ($benefits as $benefit): ?>
                <article class="benefit-card">
                    <!-- العنوان -->
                    <h3 class="benefit-title">
                        <a href="?id=<?php echo $benefit['id']; ?>">
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
                    <a href="?id=<?php echo $benefit['id']; ?>" class="btn btn-sm btn-outline-primary">
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

        <?php elseif (empty($benefits) && !$selectedBenefitData): ?>
        <!-- ==========================================
             رسالة عدم وجود نتائج
             ========================================== -->
        <section class="no-results">
            <div class="no-results-box">
                <h2>لا توجد فوائد</h2>
                <p>لم نجد أي فوائد تطابق بحثك</p>
                <a href="?" class="btn btn-primary">عرض جميع الفوائد</a>
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