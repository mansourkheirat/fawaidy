/**
 * ملف معالج AJAX العام
 * يتعامل مع جميع طلبات AJAX في الموقع
 */

class AjaxHandler {
    constructor() {
        this.baseUrl = this.getBaseUrl();
        this.csrfToken = this.getCSRFToken();
        this.pendingRequests = new Map();
    }

    /**
     * الحصول على URL الأساسي للموقع
     */
    getBaseUrl() {
        const pathname = window.location.pathname;
        const parts = pathname.split('/');
        
        // البحث عن مجلد Fawaidy
        const fawidyIndex = parts.indexOf('Fawaidy');
        if (fawidyIndex !== -1) {
            return parts.slice(0, fawidyIndex + 1).join('/') + '/';
        }
        
        return '/';
    }

    /**
     * الحصول على CSRF Token
     */
    getCSRFToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) {
            return meta.getAttribute('content');
        }
        
        // محاولة الحصول من الـ cookie
        const cookies = document.cookie.split(';');
        for (let cookie of cookies) {
            const parts = cookie.trim().split('=');
            if (parts[0] === '_csrf_token') {
                return decodeURIComponent(parts[1]);
            }
        }
        
        return null;
    }

    /**
     * طلب GET عام
     */
    async get(url, options = {}) {
        return this.request(url, 'GET', null, options);
    }

    /**
     * طلب POST عام
     */
    async post(url, data = {}, options = {}) {
        return this.request(url, 'POST', data, options);
    }

    /**
     * طلب PUT عام
     */
    async put(url, data = {}, options = {}) {
        return this.request(url, 'PUT', data, options);
    }

    /**
     * طلب DELETE عام
     */
    async delete(url, options = {}) {
        return this.request(url, 'DELETE', null, options);
    }

    /**
     * طلب عام
     */
    async request(url, method = 'GET', data = null, options = {}) {
        const {
            showLoader = true,
            timeout = 30000,
            headers = {},
            onProgress = null
        } = options;

        try {
            // إظهار المحمل
            if (showLoader) {
                this.showLoader(true);
            }

            // إنشاء الطلب
            const fetchOptions = {
                method: method,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': this.csrfToken || '',
                    ...headers
                },
                signal: AbortSignal.timeout(timeout)
            };

            // إضافة البيانات للطلب
            if (method !== 'GET' && data) {
                if (data instanceof FormData) {
                    fetchOptions.body = data;
                } else {
                    fetchOptions.body = JSON.stringify(data);
                    fetchOptions.headers['Content-Type'] = 'application/json';
                }
            }

            // تنفيذ الطلب
            const response = await fetch(this.baseUrl + url, fetchOptions);

            // التحقق من الاستجابة
            if (!response.ok) {
                throw new Error(`HTTP Error: ${response.status}`);
            }

            // معالجة الرد
            let result;
            const contentType = response.headers.get('content-type');
            
            if (contentType && contentType.includes('application/json')) {
                result = await response.json();
            } else {
                result = await response.text();
            }

            // إخفاء المحمل
            if (showLoader) {
                this.showLoader(false);
            }

            return result;

        } catch (error) {
            // إخفاء المحمل
            if (showLoader) {
                this.showLoader(false);
            }

            this.handleError(error, method);
            throw error;
        }
    }

    /**
     * إظهار/إخفاء المحمل
     */
    showLoader(show = true) {
        let loader = document.getElementById('ajaxLoader');
        
        if (show) {
            if (!loader) {
                loader = document.createElement('div');
                loader.id = 'ajaxLoader';
                loader.className = 'ajax-loader';
                loader.innerHTML = `
                    <div class="spinner"></div>
                    <p>جاري التحميل...</p>
                `;
                document.body.appendChild(loader);
            }
            loader.style.display = 'flex';
        } else {
            if (loader) {
                loader.style.display = 'none';
            }
        }
    }

    /**
     * معالجة الأخطاء
     */
    handleError(error, method = 'GET') {
        console.error(`خطأ في طلب ${method}:`, error);

        if (error.name === 'AbortError') {
            if (app) {
                app.showErrorMessage('انتهت مهلة الانتظار، يرجى المحاولة مرة أخرى');
            }
        } else if (error instanceof TypeError && error.message.includes('fetch')) {
            if (app) {
                app.showErrorMessage('خطأ في الاتصال، تحقق من اتصالك بالإنترنت');
            }
        } else {
            if (app) {
                app.showErrorMessage('حدث خطأ أثناء معالجة الطلب');
            }
        }
    }

    /**
     * إرسال نموذج عبر AJAX
     */
    async submitForm(formElement, options = {}) {
        const {
            url = null,
            method = 'POST',
            showMessage = true,
            onSuccess = null,
            onError = null
        } = options;

        try {
            // الحصول على بيانات النموذج
            const formData = new FormData(formElement);

            // إضافة CSRF token
            if (this.csrfToken && !formData.has('_csrf_token')) {
                formData.append('_csrf_token', this.csrfToken);
            }

            // تحديد الرابط
            const endpoint = url || formElement.action || window.location.pathname;

            // إرسال الطلب
            const result = await this.post(endpoint, formData, {
                showLoader: true
            });

            // التحقق من النتيجة
            if (result.success) {
                if (showMessage) {
                    if (app) {
                        app.showSuccessMessage(result.message || 'تم بنجاح');
                    }
                }

                // تنفيذ callback النجاح
                if (typeof onSuccess === 'function') {
                    onSuccess(result);
                }

                return result;
            } else {
                if (showMessage) {
                    if (app) {
                        app.showErrorMessage(result.message || 'حدث خطأ');
                    }
                }

                // تنفيذ callback الخطأ
                if (typeof onError === 'function') {
                    onError(result);
                }

                // عرض أخطاء الحقول
                if (result.errors && typeof result.errors === 'object') {
                    this.highlightFormErrors(formElement, result.errors);
                }

                throw new Error(result.message || 'فشل الطلب');
            }

        } catch (error) {
            console.error('خطأ في إرسال النموذج:', error);
            throw error;
        }
    }

    /**
     * تحديد أخطاء الحقول
     */
    highlightFormErrors(formElement, errors) {
        // إزالة الأخطاء السابقة
        formElement.querySelectorAll('.is-invalid').forEach(el => {
            el.classList.remove('is-invalid');
        });

        // إضافة الأخطاء الجديدة
        Object.keys(errors).forEach(fieldName => {
            const field = formElement.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.classList.add('is-invalid');

                // عرض رسالة الخطأ
                let errorElement = field.nextElementSibling;
                if (!errorElement || !errorElement.classList.contains('form-error')) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'form-error';
                    field.parentNode.insertBefore(errorElement, field.nextSibling);
                }
                errorElement.textContent = errors[fieldName];
                errorElement.style.display = 'block';
            }
        });
    }

    /**
     * تحميل محتوى في عنصر
     */
    async loadInto(element, url, options = {}) {
        try {
            const result = await this.get(url, options);
            
            if (typeof element === 'string') {
                element = document.querySelector(element);
            }

            if (element) {
                element.innerHTML = result;
            }

            return result;
        } catch (error) {
            console.error('خطأ في تحميل المحتوى:', error);
        }
    }

    /**
     * حذف عنصر عبر AJAX
     */
    async deleteItem(url, options = {}) {
        const { confirm = true } = options;

        if (confirm && !window.confirm('هل أنت متأكد من حذف هذا العنصر؟')) {
            return false;
        }

        try {
            const result = await this.delete(url, options);
            
            if (result.success && app) {
                app.showSuccessMessage(result.message || 'تم الحذف بنجاح');
            }

            return result;
        } catch (error) {
            console.error('خطأ في الحذف:', error);
            return false;
        }
    }

    /**
     * تحديث عنصر عبر AJAX
     */
    async updateItem(url, data, options = {}) {
        try {
            const result = await this.put(url, data, options);
            
            if (result.success && app) {
                app.showSuccessMessage(result.message || 'تم التحديث بنجاح');
            }

            return result;
        } catch (error) {
            console.error('خطأ في التحديث:', error);
            return false;
        }
    }

    /**
     * تحميل نموذج عبر AJAX
     */
    async loadForm(url, targetElement) {
        try {
            const formHTML = await this.get(url);
            
            if (typeof targetElement === 'string') {
                targetElement = document.querySelector(targetElement);
            }

            if (targetElement) {
                targetElement.innerHTML = formHTML;
            }

            return formHTML;
        } catch (error) {
            console.error('خطأ في تحميل النموذج:', error);
        }
    }

    /**
     * البحث عبر AJAX
     */
    async search(query, url, options = {}) {
        if (!query || query.trim().length === 0) {
            return [];
        }

        try {
            const result = await this.get(`${url}?q=${encodeURIComponent(query)}`, options);
            return result.results || result;
        } catch (error) {
            console.error('خطأ في البحث:', error);
            return [];
        }
    }

    /**
     * تحميل الصفحة التالية (Pagination)
     */
    async loadMore(url, options = {}) {
        try {
            const result = await this.get(url, options);
            return result;
        } catch (error) {
            console.error('خطأ في تحميل المزيد:', error);
        }
    }
}

/**
 * إنشاء نسخة عامة من معالج AJAX
 */
let ajax;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        ajax = new AjaxHandler();
    });
} else {
    ajax = new AjaxHandler();
}