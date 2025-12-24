/**
 * ==========================================
 * ملف وظائف صفحة الإعدادات
 * ==========================================
 * 
 * الوصف:
 * إدارة الإعدادات والتحديثات الفورية
 * 
 * الميزات:
 * - التنقل بين الأقسام
 * - التحديث الفوري (AJAX)
 * - التحقق من البيانات
 * - معالجة الأحداث
 */

class SettingsManager {
    /**
     * Constructor - تهيئة مدير الإعدادات
     */
    constructor() {
        this.init();
    }

    /**
     * التهيئة الأساسية
     */
    init() {
        this.setupNavigation();
        this.setupFormHandlers();
        this.setupSecurityButtons();
    }

    /**
     * إعداد الملاحة بين الأقسام
     */
    setupNavigation() {
        const navLinks = document.querySelectorAll('.settings-nav-link');

        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();

                // الحصول على معرف القسم
                const sectionId = link.getAttribute('data-section');

                // إزالة الـ active من جميع الروابط
                navLinks.forEach(l => l.classList.remove('active'));

                // إضافة active للرابط الحالي
                link.classList.add('active');

                // إخفاء جميع البطاقات
                const cards = document.querySelectorAll('.settings-card');
                cards.forEach(card => {
                    card.style.display = 'none';
                });

                // إظهار البطاقة المطلوبة
                const targetCard = document.querySelector(`[data-section="${sectionId}"]`);
                if (targetCard) {
                    targetCard.style.display = 'block';
                    targetCard.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });
    }

    /**
     * إعداد معالجات النماذج
     */
    setupFormHandlers() {
        const forms = document.querySelectorAll('.settings-form');

        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleFormSubmit(form);
            });
        });
    }

    /**
     * معالجة إرسال النموذج
     */
    handleFormSubmit(form) {
        const formType = form.getAttribute('data-form');

        if (formType === 'password') {
            this.handlePasswordChange(form);
        } else if (formType === 'personal-data') {
            this.handlePersonalDataChange(form);
        } else if (formType === 'education-work') {
            this.handleEducationWorkChange(form);
        } else if (formType === 'privacy') {
            this.handlePrivacyChange(form);
        }
    }

    /**
     * معالجة تغيير كلمة المرور
     */
    handlePasswordChange(form) {
        const currentPassword = form.querySelector('#current_password').value;
        const newPassword = form.querySelector('#new_password').value;
        const confirmPassword = form.querySelector('#confirm_password').value;

        // التحقق من البيانات
        if (!currentPassword) {
            if (app) app.showErrorMessage('أدخل كلمة المرور الحالية');
            return;
        }

        if (!newPassword) {
            if (app) app.showErrorMessage('أدخل كلمة المرور الجديدة');
            return;
        }

        if (newPassword !== confirmPassword) {
            if (app) app.showErrorMessage('كلمات المرور غير متطابقة');
            return;
        }

        // إرسال الطلب عبر AJAX
        if (ajax) {
            ajax.post('api/settings/change-password.php', {
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            }).then(result => {
                if (result.success) {
                    form.reset();
                    if (app) app.showSuccessMessage('تم تغيير كلمة المرور بنجاح');
                } else {
                    if (app) app.showErrorMessage(result.message || 'فشل تغيير كلمة المرور');
                }
            }).catch(error => {
                if (app) app.showErrorMessage('حدث خطأ في تغيير كلمة المرور');
            });
        }
    }

    /**
     * معالجة تغيير البيانات الشخصية
     */
    handlePersonalDataChange(form) {
        const gender = form.querySelector('#gender').value;
        const country = form.querySelector('#country').value;
        const bio = form.querySelector('#bio').value;

        if (ajax) {
            ajax.post('api/settings/update-personal-data.php', {
                gender: gender,
                country: country,
                bio: bio
            }).then(result => {
                if (result.success) {
                    if (app) app.showSuccessMessage('تم تحديث البيانات الشخصية');
                } else {
                    if (app) app.showErrorMessage(result.message || 'فشل التحديث');
                }
            }).catch(error => {
                if (app) app.showErrorMessage('حدث خطأ في التحديث');
            });
        }
    }

    /**
     * معالجة تغيير التعليم والعمل
     */
    handleEducationWorkChange(form) {
        const educationLevel = form.querySelector('#education_level').value;
        const major = form.querySelector('#major').value;
        const jobTitle = form.querySelector('#job_title').value;

        if (ajax) {
            ajax.post('api/settings/update-education-work.php', {
                education_level: educationLevel,
                major: major,
                job_title: jobTitle
            }).then(result => {
                if (result.success) {
                    if (app) app.showSuccessMessage('تم تحديث بيانات التعليم والعمل');
                } else {
                    if (app) app.showErrorMessage(result.message || 'فشل التحديث');
                }
            }).catch(error => {
                if (app) app.showErrorMessage('حدث خطأ في التحديث');
            });
        }
    }

    /**
     * معالجة تغيير الخصوصية
     */
    handlePrivacyChange(form) {
        const showEmail = form.querySelector('#show_email').checked;
        const showPhone = form.querySelector('#show_phone').checked;
        const allowMessages = form.querySelector('#allow_messages').checked;

        if (ajax) {
            ajax.post('api/settings/update-privacy.php', {
                show_email: showEmail ? 1 : 0,
                show_phone: showPhone ? 1 : 0,
                allow_messages: allowMessages ? 1 : 0
            }).then(result => {
                if (result.success) {
                    if (app) app.showSuccessMessage('تم تحديث إعدادات الخصوصية');
                } else {
                    if (app) app.showErrorMessage(result.message || 'فشل التحديث');
                }
            }).catch(error => {
                if (app) app.showErrorMessage('حدث خطأ في التحديث');
            });
        }
    }

    /**
     * إعداد أزرار الأمان
     */
    setupSecurityButtons() {
        // تحميل البيانات
        const downloadDataBtn = document.getElementById('downloadDataBtn');
        if (downloadDataBtn) {
            downloadDataBtn.addEventListener('click', () => {
                this.downloadAccountData();
            });
        }

        // تسجيل الخروج من الكل
        const logoutAllBtn = document.getElementById('logoutAllBtn');
        if (logoutAllBtn) {
            logoutAllBtn.addEventListener('click', () => {
                this.logoutFromAllDevices();
            });
        }

        // قفل الحساب
        const lockAccountBtn = document.getElementById('lockAccountBtn');
        if (lockAccountBtn) {
            lockAccountBtn.addEventListener('click', () => {
                this.lockAccount();
            });
        }

        // حذف الحساب
        const deleteAccountBtn = document.getElementById('deleteAccountBtn');
        if (deleteAccountBtn) {
            deleteAccountBtn.addEventListener('click', () => {
                this.deleteAccount();
            });
        }
    }

    /**
     * تحميل بيانات الحساب
     */
    downloadAccountData() {
        if (ajax) {
            ajax.get('api/settings/download-data.php', {
                showLoader: true
            }).then(result => {
                if (result.success) {
                    // تحميل الملف
                    const link = document.createElement('a');
                    link.href = 'data:application/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(result.data));
                    link.download = 'account-data.json';
                    link.click();

                    if (app) app.showSuccessMessage('تم تحميل البيانات');
                } else {
                    if (app) app.showErrorMessage(result.message || 'فشل التحميل');
                }
            }).catch(error => {
                if (app) app.showErrorMessage('حدث خطأ في التحميل');
            });
        }
    }

    /**
     * تسجيل الخروج من جميع الأجهزة
     */
    logoutFromAllDevices() {
        if (!confirm('هل أنت متأكد من رغبتك في تسجيل الخروج من جميع الأجهزة؟')) {
            return;
        }

        if (ajax) {
            ajax.post('api/settings/logout-all-devices.php', {}).then(result => {
                if (result.success) {
                    if (app) app.showSuccessMessage('تم تسجيل الخروج من جميع الأجهزة');
                    // إعادة توجيه للدخول
                    setTimeout(() => {
                        window.location.href = window.location.origin + '/Fawaidy/login';
                    }, 1500);
                } else {
                    if (app) app.showErrorMessage(result.message || 'فشلت العملية');
                }
            }).catch(error => {
                if (app) app.showErrorMessage('حدث خطأ');
            });
        }
    }

    /**
     * قفل الحساب
     */
    lockAccount() {
        if (!confirm('هل أنت متأكد من رغبتك في قفل حسابك؟')) {
            return;
        }

        if (ajax) {
            ajax.post('api/settings/lock-account.php', {}).then(result => {
                if (result.success) {
                    if (app) app.showSuccessMessage('تم قفل حسابك');
                    setTimeout(() => {
                        window.location.href = window.location.origin + '/Fawaidy/';
                    }, 1500);
                } else {
                    if (app) app.showErrorMessage(result.message || 'فشلت العملية');
                }
            }).catch(error => {
                if (app) app.showErrorMessage('حدث خطأ');
            });
        }
    }

    /**
     * حذف الحساب نهائياً
     */
    deleteAccount() {
        if (!confirm('تحذير: هذا الإجراء نهائي ولا يمكن التراجع عنه. هل أنت متأكد؟')) {
            return;
        }

        if (!confirm('آخر تحذير: سيتم حذف جميع بيانات حسابك. هل متأكد حقاً؟')) {
            return;
        }

        if (ajax) {
            ajax.post('api/settings/delete-account.php', {}).then(result => {
                if (result.success) {
                    if (app) app.showSuccessMessage('تم حذف حسابك');
                    setTimeout(() => {
                        window.location.href = window.location.origin + '/Fawaidy/';
                    }, 1500);
                } else {
                    if (app) app.showErrorMessage(result.message || 'فشلت العملية');
                }
            }).catch(error => {
                if (app) app.showErrorMessage('حدث خطأ');
            });
        }
    }
}

/**
 * تهيئة مدير الإعدادات
 */
let settingsManager;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        settingsManager = new SettingsManager();
    });
} else {
    settingsManager = new SettingsManager();
}