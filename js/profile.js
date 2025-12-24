/**
 * ==========================================
 * ملف وظائف الملف الشخصي
 * ==========================================
 * 
 * الوصف:
 * وظائف تفاعلية لصفحة الملف الشخصي
 * 
 * الميزات:
 * - تحميل البيانات الإضافية
 * - معالجة الأحداث
 * - تأثيرات الرسوم المتحركة
 * - إدارة الأعضاء
 */

class ProfileManager {
    /**
     * Constructor - تهيئة مدير الملف الشخصي
     */
    constructor() {
        this.userId = this.extractUserIdFromProfile();
        this.init();
    }

    /**
     * التهيئة الأساسية
     */
    init() {
        // تحميل البيانات الإضافية
        this.loadAdditionalData();
        
        // تعريب الأرقام
        this.localizeNumbers();
        
        // إضافة مستمعات الأحداث
        this.setupEventListeners();
        
        // تطبيق التأثيرات
        this.applyAnimations();
    }

    /**
     * استخراج معرف المستخدم من الملف الشخصي
     */
    extractUserIdFromProfile() {
        // يمكن استخراجه من attribute أو من الرابط
        const url = new URL(window.location.href);
        const params = new URLSearchParams(url.search);
        return params.get('user_id') || null;
    }

    /**
     * تحميل البيانات الإضافية عبر AJAX
     */
    loadAdditionalData() {
        if (!this.userId) return;

        // تحميل التعليقات على الملف الشخصي (لاحقاً)
        // تحميل المتابعين والمتابَعين (لاحقاً)
        // تحميل شارات العضو (لاحقاً)
    }

    /**
     * تعريب الأرقام (1-9 إلى ١-٩)
     */
    localizeNumbers() {
        const arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        
        const elements = document.querySelectorAll('.stat-number');
        elements.forEach(el => {
            const text = el.textContent;
            const arabicText = text.replace(/\d/g, digit => arabicNumbers[digit]);
            el.textContent = arabicText;
        });
    }

    /**
     * إعداد مستمعات الأحداث
     */
    setupEventListeners() {
        // أزرار عرض الكل
        const viewAllLinks = document.querySelectorAll('.view-all-link');
        viewAllLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // التأثير عند الضغط
                link.style.textDecoration = 'underline';
            });
        });

        // البطاقات - تأثير عند التمرير
        const cards = document.querySelectorAll('.benefit-card, .article-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.boxShadow = 'var(--shadow-lg)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.boxShadow = 'var(--shadow-md)';
            });
        });
    }

    /**
     * تطبيق التأثيرات والرسوم المتحركة
     */
    applyAnimations() {
        // تأثير دخول العناصر
        const profileCard = document.querySelector('.profile-header-card');
        if (profileCard) {
            profileCard.style.animation = 'fadeInDown 0.5s ease-out';
        }

        // تأثير على البطاقات
        const cards = document.querySelectorAll('.benefit-card, .article-card');
        cards.forEach((card, index) => {
            card.style.animation = `fadeInUp 0.5s ease-out ${index * 0.1}s both`;
        });
    }

    /**
     * نسخ الرابط الشخصي
     */
    copyProfileLink() {
        const link = window.location.href;
        navigator.clipboard.writeText(link).then(() => {
            if (app) {
                app.showSuccessMessage('تم نسخ الرابط');
            }
        }).catch(() => {
            if (app) {
                app.showErrorMessage('فشل نسخ الرابط');
            }
        });
    }

    /**
     * طباعة الملف الشخصي
     */
    printProfile() {
        window.print();
    }

    /**
     * مشاركة الملف الشخصي
     */
    shareProfile(platform) {
        const link = window.location.href;
        const title = document.querySelector('.profile-full-name').textContent;
        
        const shareUrls = {
            'facebook': `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(link)}`,
            'twitter': `https://twitter.com/intent/tweet?url=${encodeURIComponent(link)}&text=${encodeURIComponent(title)}`,
            'whatsapp': `https://wa.me/?text=${encodeURIComponent(link + ' - ' + title)}`,
            'email': `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(link)}`
        };

        if (shareUrls[platform]) {
            window.open(shareUrls[platform], '_blank');
        }
    }

    /**
     * حفظ الملف الشخصي كـ PDF (للمستقبل)
     */
    downloadProfilePDF() {
        // سيتم تنفيذه باستخدام مكتبة PDF لاحقاً
        if (app) {
            app.showInfoMessage('سيتم توفير هذه الميزة قريباً');
        }
    }
}

/**
 * تهيئة مدير الملف الشخصي
 */
let profileManager;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        profileManager = new ProfileManager();
    });
} else {
    profileManager = new ProfileManager();
}

/**
 * تعريفات CSS للرسوم المتحركة
 * (يتم إضافتها في ملف CSS منفصل)
 */
// @keyframes fadeInDown { ... }
// @keyframes fadeInUp { ... }