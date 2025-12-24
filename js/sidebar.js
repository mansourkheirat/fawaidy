/**
 * ==========================================
 * ملف وظائف الشريط الجانبي
 * ==========================================
 * 
 * الملف: js/sidebar.js
 * الوصف: إدارة الشريط الجانبي والملاحة
 * 
 * المحتويات:
 * - فتح وإغلاق الشريط الجانبي
 * - إدارة القوائم والفئات
 * - تحديث النشط
 * - معالجة الأحداث
 * - تأثيرات حركية
 */

class SidebarManager {
    /**
     * Constructor - تهيئة مدير الشريط الجانبي
     */
    constructor() {
        this.sidebar = document.querySelector('.sidebar');
        this.sidebarToggle = document.querySelector('.sidebar-toggle');
        this.navItems = document.querySelectorAll('.sidebar-nav-item');
        this.categories = document.querySelectorAll('.sidebar-category');
        
        // التحقق من وجود العناصر
        if (!this.sidebar) {
            console.warn('لم يتم العثور على الشريط الجانبي');
            return;
        }

        // تهيئة المدير
        this.init();
    }

    /**
     * التهيئة الأساسية
     */
    init() {
        this.setupEventListeners();
        this.setActiveItem();
        this.setupResponsive();
    }

    /**
     * إعداد مستمعات الأحداث
     */
    setupEventListeners() {
        // زر التبديل
        if (this.sidebarToggle) {
            this.sidebarToggle.addEventListener('click', () => {
                this.toggle();
            });
        }

        // عناصر التنقل
        this.navItems.forEach(item => {
            item.addEventListener('click', (e) => {
                this.handleNavItemClick(e, item);
            });
        });

        // الفئات
        this.categories.forEach(category => {
            category.addEventListener('click', (e) => {
                this.handleCategoryClick(e, category);
            });
        });

        // إغلاق الشريط الجانبي عند الضغط خارجه (في الأجهزة الصغيرة)
        document.addEventListener('click', (e) => {
            this.handleOutsideClick(e);
        });

        // زر الرجوع في الهاتف
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.close();
            }
        });
    }

    /**
     * فتح/إغلاق الشريط الجانبي
     */
    toggle() {
        if (this.sidebar.classList.contains('active')) {
            this.close();
        } else {
            this.open();
        }
    }

    /**
     * فتح الشريط الجانبي
     */
    open() {
        this.sidebar.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    /**
     * إغلاق الشريط الجانبي
     */
    close() {
        this.sidebar.classList.remove('active');
        document.body.style.overflow = 'auto';
    }

    /**
     * معالجة نقرة عنصر التنقل
     */
    handleNavItemClick(e, item) {
        e.preventDefault();

        // إزالة الـ active من جميع العناصر
        this.navItems.forEach(nav => nav.classList.remove('active'));

        // إضافة active للعنصر الحالي
        item.classList.add('active');

        // الحصول على الرابط
        const href = item.getAttribute('href') || item.getAttribute('data-href');
        if (href && href !== '#') {
            // إغلاق الشريط الجانبي
            this.close();

            // التنقل
            setTimeout(() => {
                window.location.href = href;
            }, 200);
        }
    }

    /**
     * معالجة نقرة الفئة
     */
    handleCategoryClick(e, category) {
        e.preventDefault();

        // إزالة الـ active من جميع الفئات
        this.categories.forEach(cat => cat.classList.remove('active'));

        // إضافة active للفئة الحالية
        category.classList.add('active');

        // الحصول على الرابط
        const href = category.getAttribute('href');
        if (href && href !== '#') {
            // إغلاق الشريط الجانبي
            this.close();

            // التنقل
            setTimeout(() => {
                window.location.href = href;
            }, 200);
        }
    }

    /**
     * معالجة الضغط خارج الشريط الجانبي
     */
    handleOutsideClick(e) {
        // في الأجهزة الصغيرة فقط
        if (window.innerWidth > 768) {
            return;
        }

        // التحقق من الضغط خارج الشريط
        if (
            this.sidebar.classList.contains('active') &&
            !this.sidebar.contains(e.target) &&
            !this.sidebarToggle.contains(e.target)
        ) {
            this.close();
        }
    }

    /**
     * تحديد العنصر النشط بناءً على الرابط الحالي
     */
    setActiveItem() {
        const currentUrl = window.location.pathname;

        this.navItems.forEach(item => {
            const href = item.getAttribute('href') || item.getAttribute('data-href');
            if (href && currentUrl.includes(href)) {
                item.classList.add('active');
            }
        });

        this.categories.forEach(category => {
            const href = category.getAttribute('href');
            if (href && currentUrl.includes(href)) {
                category.classList.add('active');
            }
        });
    }

    /**
     * إعداد التجاوب
     */
    setupResponsive() {
        const mediaQuery = window.matchMedia('(max-width: 768px)');

        mediaQuery.addListener((e) => {
            if (e.matches) {
                // الأجهزة الصغيرة
                this.close();
            }
        });
    }

    /**
     * تحميل الفئات ديناميكياً
     */
    async loadCategories() {
        try {
            const response = await fetch(this.getBaseUrl() + 'api/categories.php');
            const data = await response.json();

            if (data.success && data.categories) {
                this.renderCategories(data.categories);
            }
        } catch (error) {
            console.error('خطأ في تحميل الفئات:', error);
        }
    }

    /**
     * عرض الفئات
     */
    renderCategories(categories) {
        const categoriesContainer = document.querySelector('.sidebar-categories');
        if (!categoriesContainer) return;

        categoriesContainer.innerHTML = categories.map(category => `
            <a href="${this.getBaseUrl()}categories?cat=${category.id}" 
               class="sidebar-category"
               data-category-id="${category.id}">
                ${this.escapeHTML(category.name)}
            </a>
        `).join('');

        // إعادة إعداد مستمعات الأحداث
        this.categories = document.querySelectorAll('.sidebar-category');
        this.categories.forEach(category => {
            category.addEventListener('click', (e) => {
                this.handleCategoryClick(e, category);
            });
        });
    }

    /**
     * الحصول على URL الأساسي
     */
    getBaseUrl() {
        const pathname = window.location.pathname;
        const parts = pathname.split('/');
        const fawidyIndex = parts.indexOf('Fawaidy');
        if (fawidyIndex !== -1) {
            return parts.slice(0, fawidyIndex + 1).join('/') + '/';
        }
        return '/';
    }

    /**
     * حماية من XSS
     */
    escapeHTML(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, char => map[char]);
    }

    /**
     * تحديث الشريط الجانبي
     */
    refresh() {
        this.loadCategories();
        this.setActiveItem();
    }
}

/**
 * تهيئة مدير الشريط الجانبي عند تحميل الصفحة
 */
let sidebarManager;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        sidebarManager = new SidebarManager();
    });
} else {
    sidebarManager = new SidebarManager();
}

/**
 * دوال مساعدة للوصول من أي مكان
 */
function toggleSidebar() {
    if (sidebarManager) {
        sidebarManager.toggle();
    }
}

function openSidebar() {
    if (sidebarManager) {
        sidebarManager.open();
    }
}

function closeSidebar() {
    if (sidebarManager) {
        sidebarManager.close();
    }
}