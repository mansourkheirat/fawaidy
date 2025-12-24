/**
 * ملف الجافاسكريبت العام للموقع
 * يتضمن الدوال الأساسية والعام
 */

/**
 * فئة للتعامل مع المنطق العام
 */
class App {
    constructor() {
        this.siteUrl = this.getSiteUrl();
        this.csrfToken = this.getCSRFToken();
        this.init();
    }

    /**
     * تهيئة التطبيق
     */
    init() {
        // تحديث الشريط العلوي عند التمرير
        window.addEventListener('scroll', () => this.updateHeaderOnScroll());
        
        // معالجة النماذج
        this.initForms();
        
        // تهيئة الرسائل
        this.initMessages();
        
        // تحسين الوصول (Accessibility)
        this.improveAccessibility();
    }

    /**
     * الحصول على URL الموقع
     */
    getSiteUrl() {
        const scripts = document.querySelectorAll('script');
        for (let script of scripts) {
            if (script.src && script.src.includes('js/main.js')) {
                return script.src.replace(/\/js\/main\.js.*/, '/');
            }
        }
        return window.location.origin + '/Fawaidy/';
    }

    /**
     * الحصول على token CSRF من الـ HTML
     */
    getCSRFToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : null;
    }

    /**
     * تحديث الشريط العلوي عند التمرير
     */
    updateHeaderOnScroll() {
        const header = document.querySelector('.main-header');
        if (!header) return;

        if (window.scrollY > 0) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    }

    /**
     * تهيئة النماذج
     */
    initForms() {
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                // إضافة CSRF token إذا لم يكن موجوداً
                if (this.csrfToken && !form.querySelector('[name="_csrf_token"]')) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = '_csrf_token';
                    input.value = this.csrfToken;
                    form.appendChild(input);
                }
            });
        });
    }

    /**
     * تهيئة الرسائل
     */
    initMessages() {
        const messages = document.querySelectorAll('[data-message]');
        messages.forEach(message => {
            const timeout = message.getAttribute('data-message-timeout') || 5000;
            setTimeout(() => {
                this.hideMessage(message);
            }, parseInt(timeout));
        });
    }

    /**
     * إظهار رسالة نجاح
     */
    showSuccessMessage(text, timeout = 5000) {
        this.showMessage(text, 'success', timeout);
    }

    /**
     * إظهار رسالة خطأ
     */
    showErrorMessage(text, timeout = 5000) {
        this.showMessage(text, 'error', timeout);
    }

    /**
     * إظهار رسالة معلومات
     */
    showInfoMessage(text, timeout = 5000) {
        this.showMessage(text, 'info', timeout);
    }

    /**
     * إظهار رسالة تحذير
     */
    showWarningMessage(text, timeout = 5000) {
        this.showMessage(text, 'warning', timeout);
    }

    /**
     * إظهار رسالة بشكل عام
     */
    showMessage(text, type = 'info', timeout = 5000) {
        const container = document.querySelector('.messages-container') || this.createMessagesContainer();
        
        const message = document.createElement('div');
        message.className = `message message-${type}`;
        message.setAttribute('data-message', type);
        message.innerHTML = `
            <div class="message-content">
                <span class="message-icon"></span>
                <span class="message-text">${this.escapeHTML(text)}</span>
                <button class="message-close" type="button" aria-label="إغلاق الرسالة"></button>
            </div>
        `;

        container.appendChild(message);

        // إغلاق الرسالة عند الضغط على الزر
        message.querySelector('.message-close').addEventListener('click', () => {
            this.hideMessage(message);
        });

        // إغلاق الرسالة تلقائياً
        if (timeout > 0) {
            setTimeout(() => this.hideMessage(message), timeout);
        }

        return message;
    }

    /**
     * إخفاء رسالة
     */
    hideMessage(message) {
        message.classList.add('removing');
        setTimeout(() => {
            message.remove();
        }, 300);
    }

    /**
     * إنشاء حاوية الرسائل
     */
    createMessagesContainer() {
        let container = document.querySelector('.messages-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'messages-container';
            document.body.insertBefore(container, document.body.firstChild);
        }
        return container;
    }

    /**
     * حماية HTML من XSS
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
     * تحسين الوصول
     */
    improveAccessibility() {
        // إضافة skip link
        const main = document.querySelector('main');
        if (main && !main.id) {
            main.id = 'main-content';
        }

        // تحسين التركيز على الأزرار
        const buttons = document.querySelectorAll('button, [role="button"]');
        buttons.forEach(button => {
            button.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    button.click();
                }
            });
        });
    }

    /**
     * تحميل محتوى عبر AJAX
     */
    async loadContent(url, method = 'GET', data = null) {
        try {
            const options = {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfToken || ''
                }
            };

            if (method !== 'GET' && data) {
                if (data instanceof FormData) {
                    options.body = data;
                } else {
                    options.body = JSON.stringify(data);
                    options.headers['Content-Type'] = 'application/json';
                }
            }

            const response = await fetch(url, options);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('خطأ في تحميل المحتوى:', error);
            this.showErrorMessage('حدث خطأ في تحميل المحتوى، يرجى المحاولة لاحقاً');
            return null;
        }
    }

    /**
     * التحقق من الاتصال بالإنترنت
     */
    isOnline() {
        return navigator.onLine;
    }

    /**
     * تعيين استماع الاتصال بالإنترنت
     */
    onlineHandler() {
        window.addEventListener('online', () => {
            this.showSuccessMessage('تم استعادة الاتصال بالإنترنت');
        });

        window.addEventListener('offline', () => {
            this.showWarningMessage('تم فقدان الاتصال بالإنترنت');
        });
    }

    /**
     * إعادة تعيين نموذج
     */
    resetForm(formId) {
        const form = document.getElementById(formId);
        if (form) {
            form.reset();
        }
    }

    /**
     * التحقق من صحة البريد الإلكتروني
     */
    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    /**
     * التحقق من صحة رقم الهاتف
     */
    isValidPhone(phone) {
        const re = /^[0-9+\-\s()]{10,}$/;
        return re.test(phone);
    }

    /**
     * التحقق من قوة كلمة المرور
     */
    getPasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[!@#$%^&*()_+\-=\[\]{};:'".,<>?\/\\|`~]/.test(password)) strength++;

        return strength;
    }

    /**
     * تحويل الصورة إلى base64
     */
    fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(file);
        });
    }
}

/**
 * إنشاء نسخة عامة من التطبيق
 */
let app;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        app = new App();
    });
} else {
    app = new App();
}