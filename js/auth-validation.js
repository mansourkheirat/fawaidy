/**
 * ملف التحقق من بيانات التسجيل والدخول
 * التحقق على جانب العميل قبل الإرسال للخادم
 */

class AuthValidation {
    constructor() {
        this.form = null;
        this.init();
    }

    /**
     * تهيئة التحقق
     */
    init() {
        // للتسجيل
        const registerForm = document.getElementById('registerForm');
        if (registerForm) {
            this.setupRegisterValidation(registerForm);
        }

        // للدخول
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            this.setupLoginValidation(loginForm);
        }
    }

    /**
     * إعداد التحقق للتسجيل
     */
    setupRegisterValidation(form) {
        // التحقق من الاسم الكامل أثناء الكتابة
        const fullnameInput = form.querySelector('#fullname');
        if (fullnameInput) {
            fullnameInput.addEventListener('blur', () => {
                this.validateFullName(fullnameInput);
            });
            fullnameInput.addEventListener('input', () => {
                this.validateFullName(fullnameInput, true);
            });
        }

        // التحقق من اسم المستخدم
        const usernameInput = form.querySelector('#username');
        if (usernameInput) {
            usernameInput.addEventListener('blur', () => {
                this.validateUsername(usernameInput);
            });
            usernameInput.addEventListener('input', () => {
                this.validateUsername(usernameInput, true);
            });
        }

        // التحقق من البريد الإلكتروني
        const emailInput = form.querySelector('#email');
        if (emailInput) {
            emailInput.addEventListener('blur', () => {
                this.validateEmail(emailInput);
            });
        }

        // التحقق من كلمة المرور
        const passwordInput = form.querySelector('#password');
        if (passwordInput) {
            passwordInput.addEventListener('input', () => {
                this.validatePassword(passwordInput);
                this.showPasswordStrength(passwordInput);
            });
        }

        // التحقق من تطابق كلمات المرور
        const passwordConfirmInput = form.querySelector('#password_confirm');
        if (passwordConfirmInput) {
            passwordConfirmInput.addEventListener('input', () => {
                if (passwordInput) {
                    this.validatePasswordMatch(passwordInput, passwordConfirmInput);
                }
            });
        }

        // التحقق عند الإرسال
        form.addEventListener('submit', (e) => {
            if (!this.validateForm(form)) {
                e.preventDefault();
            }
        });
    }

    /**
     * إعداد التحقق للدخول
     */
    setupLoginValidation(form) {
        // التحقق من البريد/اسم المستخدم
        const emailInput = form.querySelector('#email');
        if (emailInput) {
            emailInput.addEventListener('blur', () => {
                this.validateEmailOrUsername(emailInput);
            });
        }

        // التحقق من كلمة المرور
        const passwordInput = form.querySelector('#password');
        if (passwordInput) {
            passwordInput.addEventListener('blur', () => {
                this.validatePasswordForLogin(passwordInput);
            });
        }

        // التحقق عند الإرسال
        form.addEventListener('submit', (e) => {
            if (!this.validateLoginForm(form)) {
                e.preventDefault();
            }
        });
    }

    /**
     * التحقق من الاسم الكامل (عربي فقط)
     */
    validateFullName(input, liveValidation = false) {
        const value = input.value.trim();
        const arabicRegex = /^[\u0600-\u06FF\s]+$/u;

        if (!value) {
            this.setFieldError(input, 'الاسم الكامل مطلوب');
            return false;
        }

        if (value.length < 3 || value.length > 100) {
            this.setFieldError(input, 'الاسم يجب أن يكون بين 3 و 100 حرف');
            return false;
        }

        if (!arabicRegex.test(value)) {
            this.setFieldError(input, 'الاسم يجب أن يحتوي على أحرف عربية فقط');
            return false;
        }

        if (liveValidation) {
            this.clearFieldError(input);
        } else {
            // التحقق من عدم وجود الاسم في قاعدة البيانات
            this.checkNameAvailability(value, input);
        }

        return true;
    }

    /**
     * التحقق من اسم المستخدم
     */
    validateUsername(input, liveValidation = false) {
        const value = input.value.trim();
        const usernameRegex = /^[a-zA-Z][a-zA-Z0-9._-]{2,29}$/i;

        if (!value) {
            this.setFieldError(input, 'اسم المستخدم مطلوب');
            return false;
        }

        if (!usernameRegex.test(value)) {
            this.setFieldError(input, 'اسم المستخدم غير صحيح (يبدأ بحرف، بدون عربي)');
            return false;
        }

        if (liveValidation) {
            this.clearFieldError(input);
        } else {
            // التحقق من عدم وجود اسم المستخدم
            this.checkUsernameAvailability(value, input);
        }

        return true;
    }

    /**
     * التحقق من البريد الإلكتروني
     */
    validateEmail(input) {
        const value = input.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (!value) {
            this.setFieldError(input, 'البريد الإلكتروني مطلوب');
            return false;
        }

        if (!emailRegex.test(value)) {
            this.setFieldError(input, 'البريد الإلكتروني غير صحيح');
            return false;
        }

        // التحقق من عدم استخدام البريد
        this.checkEmailAvailability(value, input);
        return true;
    }

    /**
     * التحقق من البريد أو اسم المستخدم (للدخول)
     */
    validateEmailOrUsername(input) {
        const value = input.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const usernameRegex = /^[a-zA-Z0-9._-]+$/i;

        if (!value) {
            this.setFieldError(input, 'البريد الإلكتروني أو اسم المستخدم مطلوب');
            return false;
        }

        if (!emailRegex.test(value) && !usernameRegex.test(value)) {
            this.setFieldError(input, 'يرجى إدخال بريد إلكتروني صحيح أو اسم مستخدم صحيح');
            return false;
        }

        this.clearFieldError(input);
        return true;
    }

    /**
     * التحقق من كلمة المرور
     */
    validatePassword(input) {
        const value = input.value;

        if (!value) {
            this.setFieldError(input, 'كلمة المرور مطلوبة');
            return false;
        }

        if (value.length < 8) {
            this.setFieldError(input, 'كلمة المرور يجب أن تكون 8 أحرف على الأقل');
            return false;
        }

        if (!/[A-Z]/.test(value)) {
            this.setFieldError(input, 'كلمة المرور يجب أن تحتوي على حرف كبير');
            return false;
        }

        if (!/[0-9]/.test(value)) {
            this.setFieldError(input, 'كلمة المرور يجب أن تحتوي على رقم');
            return false;
        }

        if (!/[!@#$%^&*()_+\-=\[\]{};:'".,<>?\/\\|`~]/.test(value)) {
            this.setFieldError(input, 'كلمة المرور يجب أن تحتوي على رمز خاص');
            return false;
        }

        this.clearFieldError(input);
        return true;
    }

    /**
     * التحقق من كلمة المرور للدخول
     */
    validatePasswordForLogin(input) {
        const value = input.value;

        if (!value) {
            this.setFieldError(input, 'كلمة المرور مطلوبة');
            return false;
        }

        this.clearFieldError(input);
        return true;
    }

    /**
     * التحقق من تطابق كلمات المرور
     */
    validatePasswordMatch(password, passwordConfirm) {
        if (!password.value) {
            return;
        }

        if (password.value !== passwordConfirm.value) {
            this.setFieldError(passwordConfirm, 'كلمات المرور غير متطابقة');
            return false;
        }

        this.clearFieldError(passwordConfirm);
        return true;
    }

    /**
     * عرض قوة كلمة المرور
     */
    showPasswordStrength(input) {
        const strength = this.getPasswordStrength(input.value);
        let container = input.nextElementSibling;

        if (!container || !container.classList.contains('password-strength')) {
            container = document.createElement('div');
            container.className = 'password-strength';
            input.parentNode.insertBefore(container, input.nextSibling);
        }

        const strengthHTML = `
            <div class="password-strength-meter">
                <div class="password-strength-meter-fill ${strength.class}"></div>
            </div>
            <small class="password-strength-text">${strength.text}</small>
        `;

        container.innerHTML = strengthHTML;
    }

    /**
     * حساب قوة كلمة المرور
     */
    getPasswordStrength(password) {
        let strength = 0;

        if (password.length >= 8) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[!@#$%^&*()_+\-=\[\]{};:'".,<>?\/\\|`~]/.test(password)) strength++;

        switch (strength) {
            case 1:
                return { text: 'ضعيفة', class: 'weak' };
            case 2:
                return { text: 'متوسطة', class: 'fair' };
            case 3:
                return { text: 'قوية', class: 'good' };
            case 4:
                return { text: 'قوية جداً', class: 'strong' };
            default:
                return { text: '', class: '' };
        }
    }

    /**
     * التحقق من النموذج بالكامل (التسجيل)
     */
    validateForm(form) {
        const fullnameInput = form.querySelector('#fullname');
        const usernameInput = form.querySelector('#username');
        const emailInput = form.querySelector('#email');
        const passwordInput = form.querySelector('#password');
        const passwordConfirmInput = form.querySelector('#password_confirm');
        const termsInput = form.querySelector('#terms');

        let isValid = true;

        if (!this.validateFullName(fullnameInput)) isValid = false;
        if (!this.validateUsername(usernameInput)) isValid = false;
        if (!this.validateEmail(emailInput)) isValid = false;
        if (!this.validatePassword(passwordInput)) isValid = false;
        if (!this.validatePasswordMatch(passwordInput, passwordConfirmInput)) isValid = false;

        if (termsInput && !termsInput.checked) {
            this.setFieldError(termsInput, 'يجب الموافقة على شروط الاستخدام');
            isValid = false;
        }

        return isValid;
    }

    /**
     * التحقق من نموذج الدخول
     */
    validateLoginForm(form) {
        const emailInput = form.querySelector('#email');
        const passwordInput = form.querySelector('#password');

        let isValid = true;

        if (!this.validateEmailOrUsername(emailInput)) isValid = false;
        if (!this.validatePasswordForLogin(passwordInput)) isValid = false;

        return isValid;
    }

    /**
     * تعيين خطأ الحقل
     */
    setFieldError(input, message) {
        input.classList.add('is-invalid');
        input.classList.remove('is-valid');

        let errorElement = input.nextElementSibling;
        if (!errorElement || !errorElement.classList.contains('form-error')) {
            errorElement = document.createElement('div');
            errorElement.className = 'form-error';
            input.parentNode.insertBefore(errorElement, input.nextSibling);
        }

        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }

    /**
     * إزالة خطأ الحقل
     */
    clearFieldError(input) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');

        let errorElement = input.nextElementSibling;
        if (errorElement && errorElement.classList.contains('form-error')) {
            errorElement.style.display = 'none';
        }
    }

    /**
     * التحقق من توفر الاسم
     */
    checkNameAvailability(name, input) {
        // سيتم تنفيذ هذا عبر AJAX لاحقاً
        console.log('التحقق من توفر الاسم:', name);
    }

    /**
     * التحقق من توفر اسم المستخدم
     */
    checkUsernameAvailability(username, input) {
        // سيتم تنفيذ هذا عبر AJAX لاحقاً
        console.log('التحقق من توفر اسم المستخدم:', username);
    }

    /**
     * التحقق من توفر البريد الإلكتروني
     */
    checkEmailAvailability(email, input) {
        // سيتم تنفيذ هذا عبر AJAX لاحقاً
        console.log('التحقق من توفر البريد:', email);
    }
}

/**
 * تهيئة التحقق عند تحميل الصفحة
 */
let authValidation;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        authValidation = new AuthValidation();
    });
} else {
    authValidation = new AuthValidation();
}