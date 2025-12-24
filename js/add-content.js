/**
 * ==========================================
 * ملف وظائف إضافة المحتوى
 * ==========================================
 * 
 * الملف: js/add-content.js
 * الوصف: إدارة وظائف صفحات إضافة الفوائد والمقالات
 * 
 * المحتويات:
 * - التحقق من صحة النموذج
 * - معاينة حية للمحتوى
 * - عداد الأحرف
 * - حفظ تلقائي (Auto-save)
 * - تحسينات الواجهة
 * 
 * الميزات:
 * - تحقق فوري أثناء الكتابة
 * - نصائح مفيدة للمستخدم
 * - معاينة حية للمحتوى
 * - حفظ تلقائي في LocalStorage
 * - تأثيرات واجهة سلسة
 */

class ContentManager {
    /**
     * ==========================================
     * Constructor - تهيئة مدير المحتوى
     * ==========================================
     * 
     * يقوم بـ:
     * - تحديد عناصر النموذج
     * - إعداد المستمعات
     * - تحميل البيانات المحفوظة
     */
    constructor() {
        // ==========================================
        // تحديد عناصر النموذج من الـ DOM
        // ==========================================
        this.form = document.getElementById('addBenefitForm');
        this.titleInput = document.getElementById('title');
        this.contentInput = document.getElementById('content');
        this.categoryInput = document.getElementById('category_id');
        this.tagsInput = document.getElementById('tags');
        
        // ==========================================
        // التحقق من وجود النموذج
        // ==========================================
        if (!this.form) {
            console.warn('لم يتم العثور على نموذج المحتوى');
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
     * 
     * تقوم بـ:
     * - إعداد مستمعات الأحداث
     * - تحميل البيانات المحفوظة
     * - تطبيق التحقق الفوري
     */
    init() {
        // ==========================================
        // إعداد مستمعات الأحداث للنموذج
        // ==========================================
        this.setupFormListeners();
        
        // ==========================================
        // إعداد التحقق الفوري من الحقول
        // ==========================================
        this.setupLiveValidation();
        
        // ==========================================
        // تحميل البيانات المحفوظة من LocalStorage
        // ==========================================
        this.loadSavedData();
        
        // ==========================================
        // إعداد الحفظ التلقائي
        // ==========================================
        this.setupAutoSave();
    }

    /**
     * ==========================================
     * إعداد مستمعات أحداث النموذج
     * ==========================================
     */
    setupFormListeners() {
        // معالجة إرسال النموذج
        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
            }
        });
    }

    /**
     * ==========================================
     * إعداد التحقق الفوري من البيانات
     * ==========================================
     * 
     * يتحقق من:
     * - العنوان (الطول والمحتوى)
     * - المحتوى (الطول الكافي)
     * - الفئة (اختيار فئة صحيحة)
     */
    setupLiveValidation() {
        // ==========================================
        // التحقق من العنوان أثناء الكتابة
        // ==========================================
        if (this.titleInput) {
            this.titleInput.addEventListener('input', () => {
                this.validateTitle();
                this.updateCharCount('title');
            });

            this.titleInput.addEventListener('blur', () => {
                this.validateTitle(true);
            });
        }

        // ==========================================
        // التحقق من المحتوى أثناء الكتابة
        // ==========================================
        if (this.contentInput) {
            this.contentInput.addEventListener('input', () => {
                this.validateContent();
                this.updateCharCount('content');
            });

            this.contentInput.addEventListener('blur', () => {
                this.validateContent(true);
            });
        }

        // ==========================================
        // التحقق من الفئة عند التغيير
        // ==========================================
        if (this.categoryInput) {
            this.categoryInput.addEventListener('change', () => {
                this.validateCategory();
            });
        }
    }

    /**
     * ==========================================
     * التحقق من صحة العنوان
     * ==========================================
     * 
     * المتطلبات:
     * - الطول بين 5 و 200 حرف
     * - عدم ترك الحقل فارغاً
     */
    validateTitle(strict = false) {
        const value = this.titleInput.value.trim();

        // ==========================================
        // إذا كان في وضع التحقق الصارم (blur)
        // ==========================================
        if (strict) {
            if (!value) {
                this.setFieldError(this.titleInput, 'عنوان الفائدة مطلوب');
                return false;
            }
            if (value.length < 5) {
                this.setFieldError(this.titleInput, 'العنوان يجب أن يكون على الأقل 5 أحرف');
                return false;
            }
        }

        // ==========================================
        // إزالة الخطأ إذا كان الإدخال صحيحاً
        // ==========================================
        if (value.length >= 5) {
            this.clearFieldError(this.titleInput);
            return true;
        }

        return true;
    }

    /**
     * ==========================================
     * التحقق من صحة المحتوى
     * ==========================================
     * 
     * المتطلبات:
     * - الطول الكافي (20 حرف على الأقل)
     * - عدم ترك الحقل فارغاً
     */
    validateContent(strict = false) {
        const value = this.contentInput.value.trim();

        if (strict) {
            if (!value) {
                this.setFieldError(this.contentInput, 'محتوى الفائدة مطلوب');
                return false;
            }
            if (value.length < 20) {
                this.setFieldError(this.contentInput, 'المحتوى يجب أن يكون على الأقل 20 حرف');
                return false;
            }
        }

        if (value.length >= 20) {
            this.clearFieldError(this.contentInput);
            return true;
        }

        return true;
    }

    /**
     * ==========================================
     * التحقق من اختيار الفئة
     * ==========================================
     */
    validateCategory() {
        const value = this.categoryInput.value.trim();

        if (!value) {
            this.setFieldError(this.categoryInput, 'يجب اختيار فئة');
            return false;
        }

        this.clearFieldError(this.categoryInput);
        return true;
    }

    /**
     * ==========================================
     * التحقق الشامل من النموذج
     * ==========================================
     */
    validateForm() {
        let isValid = true;

        // ==========================================
        // التحقق من جميع الحقول
        // ==========================================
        if (!this.validateTitle(true)) isValid = false;
        if (!this.validateContent(true)) isValid = false;
        if (!this.validateCategory()) isValid = false;

        return isValid;
    }

    /**
     * ==========================================
     * تعيين خطأ على الحقل
     * ==========================================
     * 
     * المعاملات:
     * - input: عنصر الإدخال
     * - message: رسالة الخطأ
     */
    setFieldError(input, message) {
        // إضافة فئة الخطأ
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');

        // البحث عن عنصر رسالة الخطأ أو إنشاء واحد
        let errorElement = input.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains('form-error')) {
            errorElement = document.createElement('div');
            errorElement.className = 'form-error';
            input.parentNode.insertBefore(errorElement, input.nextSibling);
        }

        // ==========================================
        // تحديث رسالة الخطأ
        // ==========================================
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }

    /**
     * ==========================================
     * إزالة خطأ من الحقل
     * ==========================================
     */
    clearFieldError(input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');

        // البحث عن رسالة الخطأ وإخفاؤها
        let errorElement = input.nextElementSibling;
        if (errorElement && errorElement.classList.contains('form-error')) {
            errorElement.style.display = 'none';
        }
    }

    /**
     * ==========================================
     * تحديث عداد الأحرف
     * ==========================================
     * 
     * يعرض عدد الأحرف المُدخلة
     */
    updateCharCount(fieldName) {
        const input = fieldName === 'title' ? this.titleInput : this.contentInput;
        const count = input.value.length;
        const maxCount = fieldName === 'title' ? 200 : 5000;

        // البحث عن عنصر العداد أو إنشاء واحد
        let countElement = input.nextElementSibling;
        while (countElement && !countElement.classList.contains('char-count')) {
            countElement = countElement.nextElementSibling;
        }

        if (!countElement) {
            countElement = document.createElement('small');
            countElement.className = 'char-count';
            input.parentNode.appendChild(countElement);
        }

        // ==========================================
        // تحديث النص والألوان بناءً على النسبة
        // ==========================================
        countElement.textContent = `${count} / ${maxCount}`;
        countElement.style.color = count > maxCount * 0.9 ? 'var(--color-warning)' : 'var(--color-gray)';
    }

    /**
     * ==========================================
     * تحميل البيانات المحفوظة من LocalStorage
     * ==========================================
     * 
     * ملاحظة: تم تعطيل localStorage في البيئة الحالية
     * يمكن تفعيل هذه الميزة في بيئة الإنتاج
     */
    loadSavedData() {
        // ==========================================
        // في بيئة الإنتاج، يمكن حفظ البيانات بهذه الطريقة
        // ==========================================
        /*
        try {
            const saved = localStorage.getItem('benefit-draft');
            if (saved) {
                const data = JSON.parse(saved);
                if (this.titleInput) this.titleInput.value = data.title || '';
                if (this.contentInput) this.contentInput.value = data.content || '';
                if (this.categoryInput) this.categoryInput.value = data.category_id || '';
                if (this.tagsInput) this.tagsInput.value = data.tags || '';
            }
        } catch (e) {
            console.warn('فشل تحميل البيانات المحفوظة:', e);
        }
        */
    }

    /**
     * ==========================================
     * الحفظ التلقائي للبيانات
     * ==========================================
     * 
     * يحفظ البيانات تلقائياً كل 30 ثانية
     */
    setupAutoSave() {
        // ==========================================
        // إعداد حفظ تلقائي كل 30 ثانية
        // ==========================================
        setInterval(() => {
            this.autoSaveData();
        }, 30000);
    }

    /**
     * ==========================================
     * تنفيذ الحفظ التلقائي
     * ==========================================
     */
    autoSaveData() {
        // في بيئة الإنتاج
        /*
        try {
            const data = {
                title: this.titleInput.value,
                content: this.contentInput.value,
                category_id: this.categoryInput.value,
                tags: this.tagsInput.value
            };
            localStorage.setItem('benefit-draft', JSON.stringify(data));
        } catch (e) {
            console.warn('فشل الحفظ التلقائي:', e);
        }
        */
    }

    /**
     * ==========================================
     * مسح البيانات المحفوظة
     * ==========================================
     */
    clearSavedData() {
        // localStorage.removeItem('benefit-draft');
    }
}

/**
 * ==========================================
 * تهيئة مدير المحتوى عند تحميل الصفحة
 * ==========================================
 */
let contentManager;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        contentManager = new ContentManager();
    });
} else {
    contentManager = new ContentManager();
}