/**
 * ==========================================
 * ملف وظائف صفحة الفوائد
 * ==========================================
 * 
 * الملف: js/benefits-page.js
 * الوصف: إدارة وظائف صفحة عرض الفوائد
 * 
 * المحتويات:
 * - معالجة الفلترة والبحث
 * - تأثيرات الحركة
 * - التفاعل مع البطاقات
 * - معالجة Pagination
 * - تحسينات الواجهة
 * 
 * الميزات:
 * - بحث فوري
 * - فلترة ديناميكية
 * - تأثيرات سلسة
 * - تحميل ديناميكي (للمستقبل)
 */

class BenefitsManager {
    /**
     * ==========================================
     * Constructor - تهيئة مدير الفوائد
     * ==========================================
     */
    constructor() {
        // ==========================================
        // تحديد العناصر من الـ DOM
        // ==========================================
        this.filterForm = document.getElementById('filterForm');
        this.benefitCards = document.querySelectorAll('.benefit-card');
        this.paginationLinks = document.querySelectorAll('.pagination-link');
        
        // ==========================================
        // التحقق من وجود العناصر
        // ==========================================
        if (!this.filterForm) {
            console.warn('لم يتم العثور على نموذج الفلترة');
            return;
        }

        // ==========================================
        // تهيئة المدير
        // ==========================================
        this.init();
    }

    /**
     * ==========================================
     * التهيئة الأساسية
     * ==========================================
     */
    init() {
        // ==========================================
        // إعداد مستمعات الأحداث
        // ==========================================
        this.setupEventListeners();
        
        // ==========================================
        // تطبيق التأثيرات الأولية
        // ==========================================
        this.applyInitialEffects();
    }

    /**
     * ==========================================
     * إعداد مستمعات الأحداث
     * ==========================================
     */
    setupEventListeners() {
        
        // ==========================================
        // معالجة تغيير القائمة المنسدلة للفئات
        // ==========================================
        const categorySelect = this.filterForm.querySelector('select[name="category"]');
        if (categorySelect) {
            categorySelect.addEventListener('change', () => {
                this.handleCategoryChange();
            });
        }

        // ==========================================
        // معالجة تغيير الترتيب
        // ==========================================
        const sortSelect = this.filterForm.querySelector('select[name="sort"]');
        if (sortSelect) {
            sortSelect.addEventListener('change', () => {
                this.handleSortChange();
            });
        }

        // ==========================================
        // معالجة البحث
        // ==========================================
        const searchInput = this.filterForm.querySelector('input[name="q"]');
        if (searchInput) {
            // البحث عند الضغط على Enter
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.handleSearch();
                }
            });
        }

        // ==========================================
        // إضافة تأثيرات لبطاقات الفوائد
        // ==========================================
        this.benefitCards.forEach(card => {
            this.setupCardEffects(card);
        });

        // ==========================================
        // معالجة روابط التقسيم
        // ==========================================
        this.paginationLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                this.handlePaginationClick(e, link);
            });
        });
    }

    /**
     * ==========================================
     * معالجة تغيير الفئة
     * ==========================================
     */
    handleCategoryChange() {
        // ==========================================
        // إظهار مؤشر التحميل
        // ==========================================
        this.showLoadingState();
        
        // ==========================================
        // إرسال النموذج
        // ==========================================
        setTimeout(() => {
            this.filterForm.submit();
        }, 300);
    }

    /**
     * ==========================================
     * معالجة تغيير الترتيب
     * ==========================================
     */
    handleSortChange() {
        // ==========================================
        // إظهار مؤشر التحميل
        // ==========================================
        this.showLoadingState();
        
        // ==========================================
        // إرسال النموذج
        // ==========================================
        setTimeout(() => {
            this.filterForm.submit();
        }, 300);
    }

    /**
     * ==========================================
     * معالجة البحث
     * ==========================================
     */
    handleSearch() {
        // ==========================================
        // إظهار مؤشر التحميل
        // ==========================================
        this.showLoadingState();
        
        // ==========================================
        // إرسال النموذج
        // ==========================================
        setTimeout(() => {
            this.filterForm.submit();
        }, 300);
    }

    /**
     * ==========================================
     * إعداد التأثيرات لبطاقة الفائدة
     * ==========================================
     */
    setupCardEffects(card) {
        
        // ==========================================
        // تأثير عند الدخول بالماوس
        // ==========================================
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-4px)';
            card.style.boxShadow = 'var(--shadow-lg)';
        });

        // ==========================================
        // تأثير عند المغادرة
        // ==========================================
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(0)';
            card.style.boxShadow = 'var(--shadow-md)';
        });

        // ==========================================
        // تأثير عند التركيز (للوصول بلوحة المفاتيح)
        // ==========================================
        const link = card.querySelector('a');
        if (link) {
            link.addEventListener('focus', () => {
                card.style.outline = '2px solid var(--color-accent)';
                card.style.outlineOffset = '2px';
            });

            link.addEventListener('blur', () => {
                card.style.outline = 'none';
            });
        }
    }

    /**
     * ==========================================
     * معالجة نقرة رابط التقسيم
     * ==========================================
     */
    handlePaginationClick(e, link) {
        // ==========================================
        // في المستقبل، يمكن تحميل الصفحة بـ AJAX
        // بدلاً من تحديث الصفحة بالكامل
        // ==========================================
        
        // حالياً نسمح بالسلوك الافتراضي
        // e.preventDefault();
        // this.loadPageViaAjax(link.href);
    }

    /**
     * ==========================================
     * إظهار مؤشر التحميل
     * ==========================================
     */
    showLoadingState() {
        // ==========================================
        // إضافة كلاس التحميل للشبكة
        // ==========================================
        const grid = document.querySelector('.benefits-grid');
        if (grid) {
            grid.style.opacity = '0.5';
            grid.style.pointerEvents = 'none';
        }
    }

    /**
     * ==========================================
     * إخفاء مؤشر التحميل
     * ==========================================
     */
    hideLoadingState() {
        const grid = document.querySelector('.benefits-grid');
        if (grid) {
            grid.style.opacity = '1';
            grid.style.pointerEvents = 'auto';
        }
    }

    /**
     * ==========================================
     * تطبيق التأثيرات الأولية
     * ==========================================
     */
    applyInitialEffects() {
        
        // ==========================================
        // إضافة تأثير الظهور للبطاقات
        // ==========================================
        this.benefitCards.forEach((card, index) => {
            card.style.animation = `fadeInUp 0.5s ease-out ${index * 0.1}s both`;
        });

        // ==========================================
        // إضافة تأثير الظهور لعناصر التقسيم
        // ==========================================
        this.paginationLinks.forEach((link, index) => {
            link.style.animation = `fadeIn 0.5s ease-out ${0.5 + index * 0.05}s both`;
        });
    }

    /**
     * ==========================================
     * تحميل صفحة عبر AJAX (للمستقبل)
     * ==========================================
     */
    loadPageViaAjax(url) {
        // ==========================================
        // هذه الدالة للمستقبل عندما نريد تحميل
        // الفوائد بدون تحديث الصفحة
        // ==========================================
        /*
        if (ajax) {
            ajax.get(url, { showLoader: true }).then(result => {
                // تحديث المحتوى
                // تحديث الـ URL
                window.history.pushState({}, '', url);
                this.hideLoadingState();
            });
        }
        */
    }

    /**
     * ==========================================
     * عرض رسالة النتائج
     * ==========================================
     */
    showResultsMessage(count, searchQuery = '') {
        const resultsInfo = document.querySelector('.results-info');
        if (!resultsInfo) return;

        if (count === 0) {
            resultsInfo.innerHTML = `
                <p>لم نجد نتائج لـ 
                <strong>${htmlEscape(searchQuery)}</strong>
                </p>
            `;
        } else {
            resultsInfo.innerHTML = `
                <p>
                تم العثور على 
                <strong>${count}</strong> 
                فائدة
                ${searchQuery ? ` عن كلمة "<strong>${htmlEscape(searchQuery)}</strong>"` : ''}
                </p>
            `;
        }
    }

    /**
     * ==========================================
     * حماية من XSS
     * ==========================================
     */
    htmlEscape(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, char => map[char]);
    }
}

/**
 * ==========================================
 * تهيئة مدير الفوائد عند تحميل الصفحة
 * ==========================================
 */
let benefitsManager;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        benefitsManager = new BenefitsManager();
    });
} else {
    benefitsManager = new BenefitsManager();
}

/**
 * ==========================================
 * تعريف الرسوم المتحركة في CSS
 * ==========================================
 * 
 * @keyframes fadeInUp {
 *   from {
 *     opacity: 0;
 *     transform: translateY(20px);
 *   }
 *   to {
 *     opacity: 1;
 *     transform: translateY(0);
 *   }
 * }
 * 
 * @keyframes fadeIn {
 *   from { opacity: 0; }
 *   to { opacity: 1; }
 * }
 */