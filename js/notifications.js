/**
 * ==========================================
 * نظام إدارة الإشعارات
 * ==========================================
 * 
 * الملف: js/notifications.js
 * الوصف: نظام شامل للإشعارات والتنبيهات
 * 
 * المحتويات:
 * - إظهار الإشعارات
 * - مركز الإشعارات
 * - إدارة الإشعارات المقروءة
 * - الإشعارات الحية
 */

class NotificationSystem {
    /**
     * Constructor - تهيئة نظام الإشعارات
     */
    constructor() {
        this.notifications = [];
        this.notificationCenter = null;
        this.popupContainer = null;
        this.baseUrl = this.getBaseUrl();
        
        this.init();
    }

    /**
     * التهيئة الأساسية
     */
    init() {
        this.createNotificationCenter();
        this.createPopupContainer();
        this.setupEventListeners();
        this.loadNotifications();
    }

    /**
     * إنشاء مركز الإشعارات
     */
    createNotificationCenter() {
        let center = document.querySelector('.notification-center');
        if (!center) {
            center = document.createElement('div');
            center.className = 'notification-center';
            center.innerHTML = `
                <div class="notification-center-header">
                    <h3 class="notification-center-title">الإشعارات</h3>
                    <button class="notification-center-close" aria-label="إغلاق الإشعارات">×</button>
                </div>
                <div class="notification-list"></div>
                <div class="notification-center-footer">
                    <button class="btn btn-secondary btn-sm" id="markAllAsRead">تحديد الكل كمقروء</button>
                    <button class="btn btn-secondary btn-sm" id="clearNotifications">مسح الكل</button>
                </div>
            `;
            document.body.appendChild(center);
        }
        this.notificationCenter = center;
    }

    /**
     * إنشاء حاوية النوافذ المنفثقة
     */
    createPopupContainer() {
        let container = document.querySelector('.notification-popup-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'notification-popup-container';
            document.body.appendChild(container);
        }
        this.popupContainer = container;
    }

    /**
     * إعداد مستمعات الأحداث
     */
    setupEventListeners() {
        // زر إغلاق مركز الإشعارات
        const closeBtn = this.notificationCenter.querySelector('.notification-center-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeCenter());
        }

        // تحديد الكل كمقروء
        const markAllBtn = document.getElementById('markAllAsRead');
        if (markAllBtn) {
            markAllBtn.addEventListener('click', () => this.markAllAsRead());
        }

        // مسح الكل
        const clearBtn = document.getElementById('clearNotifications');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => this.clearAll());
        }

        // إغلاق مركز الإشعارات عند الضغط خارجه
        document.addEventListener('click', (e) => {
            if (
                this.notificationCenter.classList.contains('active') &&
                !this.notificationCenter.contains(e.target) &&
                !e.target.closest('.notification-badge')
            ) {
                this.closeCenter();
            }
        });
    }

    /**
     * إظهار إشعار منفثق
     */
    showPopup(title, message, type = 'info', duration = 5000) {
        const popup = document.createElement('div');
        popup.className = `notification-popup ${type}`;
        
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ⓘ'
        };

        popup.innerHTML = `
            <span class="notification-popup-icon">${icons[type] || '•'}</span>
            <div class="notification-popup-content">
                <div class="notification-popup-title">${this.escapeHTML(title)}</div>
                <div class="notification-popup-message">${this.escapeHTML(message)}</div>
            </div>
            <button class="notification-popup-close" aria-label="إغلاق">×</button>
        `;

        this.popupContainer.appendChild(popup);

        // معالجة الإغلاق
        const closeBtn = popup.querySelector('.notification-popup-close');
        closeBtn.addEventListener('click', () => {
            this.removePopup(popup);
        });

        // إغلاق تلقائي
        if (duration > 0) {
            setTimeout(() => {
                this.removePopup(popup);
            }, duration);
        }
    }

    /**
     * إزالة نافذة منفثقة
     */
    removePopup(popup) {
        popup.classList.add('removing');
        setTimeout(() => {
            popup.remove();
        }, 300);
    }

    /**
     * تحميل الإشعارات من الخادم
     */
    async loadNotifications() {
        try {
            const response = await fetch(this.baseUrl + 'api/notifications.php');
            const data = await response.json();

            if (data.success && data.notifications) {
                this.notifications = data.notifications;
                this.renderNotifications();
                this.updateBadge();
            }
        } catch (error) {
            console.error('خطأ في تحميل الإشعارات:', error);
        }
    }

    /**
     * عرض الإشعارات في مركز الإشعارات
     */
    renderNotifications() {
        const list = this.notificationCenter.querySelector('.notification-list');
        
        if (this.notifications.length === 0) {
            list.innerHTML = `
                <div class="notification-empty">
                    <div class="notification-empty-icon">🔔</div>
                    <p class="notification-empty-text">لا توجد إشعارات</p>
                </div>
            `;
            return;
        }

        list.innerHTML = this.notifications.map(notif => `
            <div class="notification-item ${notif.is_read ? 'read' : 'unread'}" 
                 data-notification-id="${notif.id}">
                <span class="notification-icon">${this.getNotificationIcon(notif.type)}</span>
                <div class="notification-content">
                    <div class="notification-title">${this.escapeHTML(notif.title)}</div>
                    <div class="notification-message">${this.escapeHTML(notif.message)}</div>
                    <div class="notification-time">${this.getTimeAgo(notif.created_at)}</div>
                </div>
                ${notif.is_read ? '' : '<div class="notification-dot"></div>'}
            </div>
        `).join('');

        // إضافة مستمعات الأحداث
        const items = list.querySelectorAll('.notification-item');
        items.forEach(item => {
            item.addEventListener('click', () => this.handleNotificationClick(item));
        });
    }

    /**
     * معالجة نقرة الإشعار
     */
    async handleNotificationClick(item) {
        const id = item.getAttribute('data-notification-id');
        
        // تحديث حالة الإشعار
        await this.markAsRead(id);
        
        // التنقل إذا كان هناك رابط
        const notification = this.notifications.find(n => n.id == id);
        if (notification && notification.link) {
            window.location.href = notification.link;
        }
    }

    /**
     * تحديد إشعار كمقروء
     */
    async markAsRead(id) {
        try {
            const response = await fetch(this.baseUrl + 'api/notifications/mark-as-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ notification_id: id })
            });

            const data = await response.json();
            if (data.success) {
                const notification = this.notifications.find(n => n.id == id);
                if (notification) {
                    notification.is_read = 1;
                    this.renderNotifications();
                    this.updateBadge();
                }
            }
        } catch (error) {
            console.error('خطأ في تحديث الإشعار:', error);
        }
    }

    /**
     * تحديد جميع الإشعارات كمقروءة
     */
    async markAllAsRead() {
        try {
            const response = await fetch(this.baseUrl + 'api/notifications/mark-all-as-read.php', {
                method: 'POST'
            });

            const data = await response.json();
            if (data.success) {
                this.notifications.forEach(n => n.is_read = 1);
                this.renderNotifications();
                this.updateBadge();
                this.showPopup('نجح', 'تم تحديد جميع الإشعارات كمقروءة', 'success', 3000);
            }
        } catch (error) {
            console.error('خطأ:', error);
        }
    }

    /**
     * مسح جميع الإشعارات
     */
    async clearAll() {
        if (!confirm('هل تريد حذف جميع الإشعارات؟')) {
            return;
        }

        try {
            const response = await fetch(this.baseUrl + 'api/notifications/clear-all.php', {
                method: 'POST'
            });

            const data = await response.json();
            if (data.success) {
                this.notifications = [];
                this.renderNotifications();
                this.updateBadge();
                this.showPopup('نجح', 'تم حذف جميع الإشعارات', 'success', 3000);
            }
        } catch (error) {
            console.error('خطأ:', error);
        }
    }

    /**
     * تحديث شارة الإشعارات
     */
    updateBadge() {
        const unreadCount = this.notifications.filter(n => !n.is_read).length;
        const badge = document.querySelector('.notification-badge');
        
        if (badge) {
            if (unreadCount > 0) {
                badge.setAttribute('data-unread', unreadCount);
            } else {
                badge.removeAttribute('data-unread');
            }
        }
    }

    /**
     * فتح مركز الإشعارات
     */
    openCenter() {
        this.notificationCenter.classList.add('active');
        this.loadNotifications();
    }

    /**
     * إغلاق مركز الإشعارات
     */
    closeCenter() {
        this.notificationCenter.classList.remove('active');
    }

    /**
     * الحصول على أيقونة الإشعار
     */
    getNotificationIcon(type) {
        const icons = {
            'success': '✓',
            'error': '✕',
            'warning': '⚠',
            'info': 'ⓘ',
            'message': '💬',
            'like': '❤️',
            'comment': '💭',
            'follow': '👤'
        };
        return icons[type] || '•';
    }

    /**
     * حساب الوقت المنقضي
     */
    getTimeAgo(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'الآن';
        if (seconds < 3600) return `منذ ${Math.floor(seconds / 60)} دقيقة`;
        if (seconds < 86400) return `منذ ${Math.floor(seconds / 3600)} ساعة`;
        return `منذ ${Math.floor(seconds / 86400)} يوم`;
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
     * إرسال إشعار
     */
    async sendNotification(userId, title, message, type = 'info', link = null) {
        try {
            const response = await fetch(this.baseUrl + 'api/notifications/send.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    user_id: userId,
                    title: title,
                    message: message,
                    type: type,
                    link: link
                })
            });

            return await response.json();
        } catch (error) {
            console.error('خطأ في إرسال الإشعار:', error);
        }
    }
}

/**
 * تهيئة نظام الإشعارات
 */
let notificationSystem;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        notificationSystem = new NotificationSystem();
    });
} else {
    notificationSystem = new NotificationSystem();
}

/**
 * دوال مساعدة للوصول من أي مكان
 */
function showNotificationPopup(title, message, type = 'info', duration = 5000) {
    if (notificationSystem) {
        notificationSystem.showPopup(title, message, type, duration);
    }
}

function openNotificationCenter() {
    if (notificationSystem) {
        notificationSystem.openCenter();
    }
}

function closeNotificationCenter() {
    if (notificationSystem) {
        notificationSystem.closeCenter();
    }
}