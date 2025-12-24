<?php
/**
 * الصفحة الرئيسية للموقع
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/database/connection.php';
require_once __DIR__ . '/database/security.php';

// منع الوصول المباشر
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('تم حظر الوصول المباشر إلى هذا الملف');
}
?>

<?php include __DIR__ . '/includes/header.php'; ?>

<!-- المحتوى الرئيسي -->
<main style="padding-top: var(--header-height);">
    <div class="container">
        
        <!-- ================================
             بطاقة ترحيبية مميزة
             ================================ -->
        <section class="welcome-section">
            <div class="welcome-card">
                <div class="welcome-right">
                    <h1 class="welcome-title">أهلاً بك في فوائدي</h1>
                    <p class="welcome-subtitle">منصة متكاملة لنشر الفوائد العلمية والمعرفية المميزة</p>
                    <?php if ($isLoggedIn): ?>
                        <a href="<?php echo SITE_URL; ?>add-benefit" class="btn btn-primary btn-lg">
                            <span class="btn-icon">➕</span>
                            <span>أضف فائدة جديدة</span>
                        </a>
                    <?php else: ?>
                        <a href="<?php echo SITE_URL; ?>register" class="btn btn-primary btn-lg">
                            <span class="btn-icon">📝</span>
                            <span>ابدأ الآن</span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="welcome-left"></div>
            </div>
        </section>

        <!-- ================================
             شريط البحث
             ================================ -->
        <section class="search-section">
            <div class="search-container">
                <form id="searchForm" class="search-form">
                    <div class="search-input-wrapper">
                        <input 
                            type="search" 
                            id="searchInput" 
                            placeholder="ابحث عن فوائد..." 
                            class="search-input"
                            aria-label="البحث عن فوائد"
                        >
                        <button type="submit" class="search-btn" aria-label="بحث">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="10" cy="10" r="6" fill="none" stroke="currentColor" stroke-width="2"/>
                                <line x1="14" y1="14" x2="20" y2="20" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <!-- ================================
             آخر الفوائد المضافة
             ================================ -->
        <section class="recent-benefits-section">
            <h2 class="section-title">آخر الفوائد</h2>
            <div class="benefits-grid" id="recentBenefits">
                <!-- يتم تحميل البيانات عبر AJAX -->
                <div class="loading-skeleton">
                    <div class="skeleton-card"></div>
                    <div class="skeleton-card"></div>
                    <div class="skeleton-card"></div>
                </div>
            </div>
        </section>

        <!-- ================================
             الفئات والمباحث
             ================================ -->
        <section class="categories-section">
            <h2 class="section-title">الفئات والمباحث</h2>
            <div class="categories-grid" id="categories">
                <!-- يتم تحميل الفئات عبر AJAX -->
                <div class="loading-skeleton">
                    <div class="skeleton-card"></div>
                    <div class="skeleton-card"></div>
                    <div class="skeleton-card"></div>
                </div>
            </div>
            <div class="text-center mt-3">
                <a href="<?php echo SITE_URL; ?>categories" class="btn btn-outline-primary">
                    عرض جميع الفئات
                </a>
            </div>
        </section>

        <!-- ================================
             آخر المقالات (للأعضاء المميزين)
             ================================ -->
        <section class="recent-articles-section">
            <h2 class="section-title">آخر المقالات</h2>
            <div class="articles-grid" id="recentArticles">
                <!-- يتم تحميل المقالات عبر AJAX -->
                <div class="loading-skeleton">
                    <div class="skeleton-card"></div>
                    <div class="skeleton-card"></div>
                    <div class="skeleton-card"></div>
                </div>
            </div>
        </section>

        <!-- ================================
             بطاقة فارغة (للمستقبل)
             ================================ -->
        <section class="placeholder-section">
            <!-- سيتم ملء هذا القسم لاحقاً -->
        </section>

    </div>
</main>

<!-- ================================
     التذييل
     ================================ -->
<?php include __DIR__ . '/includes/footer.php'; ?>

<!-- ملفات JavaScript -->
<script src="<?php echo SITE_URL; ?>js/ajax.js"></script>
<script>
// تحميل البيانات من قاعدة البيانات
document.addEventListener('DOMContentLoaded', function() {
    loadRecentBenefits();
    loadCategories();
    loadRecentArticles();
    
    // معالجة البحث
    document.getElementById('searchForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const query = document.getElementById('searchInput').value;
        if (query.trim()) {
            window.location.href = '<?php echo SITE_URL; ?>benefits?q=' + encodeURIComponent(query);
        }
    });
});

/**
 * تحميل آخر الفوائد
 */
function loadRecentBenefits() {
    const container = document.getElementById('recentBenefits');
    
    fetch('<?php echo SITE_URL; ?>api/benefits.php?limit=3', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.benefits.length > 0) {
            container.innerHTML = data.benefits.map(benefit => `
                <div class="benefit-card">
                    <h3 class="benefit-title">${escapeHTML(benefit.title)}</h3>
                    <p class="benefit-content">${escapeHTML(benefit.content.substring(0, 100))}...</p>
                    <div class="benefit-meta">
                        <span class="benefit-author">${escapeHTML(benefit.author)}</span>
                        <span class="benefit-date">${benefit.date}</span>
                    </div>
                    <a href="<?php echo SITE_URL; ?>benefit/${benefit.id}" class="btn btn-sm btn-outline-primary">
                        اقرأ أكثر
                    </a>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-center">لا توجد فوائد في الوقت الحالي</p>';
        }
    })
    .catch(error => {
        console.error('خطأ:', error);
        container.innerHTML = '<p class="text-center text-danger">حدث خطأ في تحميل الفوائد</p>';
    });
}

/**
 * تحميل الفئات
 */
function loadCategories() {
    const container = document.getElementById('categories');
    
    fetch('<?php echo SITE_URL; ?>api/categories.php?limit=6', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.categories.length > 0) {
            container.innerHTML = data.categories.map(category => `
                <div class="category-card">
                    <div class="category-icon">${category.icon || '📁'}</div>
                    <h3 class="category-name">${escapeHTML(category.name)}</h3>
                    <p class="category-count">${category.count || 0} فائدة</p>
                    <a href="<?php echo SITE_URL; ?>categories?cat=${category.id}" class="btn btn-sm btn-secondary">
                        عرض
                    </a>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-center">لا توجد فئات في الوقت الحالي</p>';
        }
    })
    .catch(error => {
        console.error('خطأ:', error);
        container.innerHTML = '<p class="text-center text-danger">حدث خطأ في تحميل الفئات</p>';
    });
}

/**
 * تحميل آخر المقالات
 */
function loadRecentArticles() {
    const container = document.getElementById('recentArticles');
    
    fetch('<?php echo SITE_URL; ?>api/articles.php?limit=3', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.articles.length > 0) {
            container.innerHTML = data.articles.map(article => `
                <div class="article-card">
                    <h3 class="article-title">${escapeHTML(article.title)}</h3>
                    <p class="article-content">${escapeHTML(article.content.substring(0, 100))}...</p>
                    <div class="article-meta">
                        <span class="article-author">${escapeHTML(article.author)}</span>
                        <span class="article-date">${article.date}</span>
                    </div>
                    <a href="<?php echo SITE_URL; ?>article/${article.id}" class="btn btn-sm btn-outline-primary">
                        اقرأ المقالة
                    </a>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-center">لا توجد مقالات في الوقت الحالي</p>';
        }
    })
    .catch(error => {
        console.error('خطأ:', error);
        // لا نعرض رسالة خطأ للمقالات إذا لم تكن موجودة
    });
}

/**
 * دالة لحماية HTML من XSS
 */
function escapeHTML(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, char => map[char]);
}
</script>

</body>
</html>