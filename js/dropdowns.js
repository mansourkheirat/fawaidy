/**
 * ملف إدارة القوائم المنسدلة
 * يتعامل مع فتح وإغلاق القوائم المنسدلة
 */

class DropdownManager {
    constructor() {
        this.activeDropdown = null;
        this.init();
    }

    /**
     * تهيئة القوائم المنسدلة
     */
    init() {
        // البحث عن جميع أزرار القوائم المنسدلة
        const dropdownButtons = document.querySelectorAll('[data-dropdown-toggle]');
        
        dropdownButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.stopPropagation();
                const targetId = button.getAttribute('data-dropdown-toggle');
                this.toggle(targetId, button);
            });
        });

        // إغلاق القوائم عند الضغط خارجها
        document.addEventListener('click', () => {
            this.closeAll();
        });
    }

    /**
     * فتح/إغلاق قائمة
     */
    toggle(dropdownId, button = null) {
        const dropdown = document.getElementById(dropdownId);
        if (!dropdown) return;

        // إذا كانت القائمة مفتوحة، أغلقها
        if (dropdown.classList.contains('active')) {
            this.close(dropdownId);
            return;
        }

        // إغلاق جميع القوائم الأخرى
        this.closeAll();

        // فتح القائمة الحالية
        dropdown.classList.add('active');
        
        if (button) {
            button.classList.add('active');
        }

        this.activeDropdown = dropdownId;
    }

    /**
     * فتح قائمة
     */
    open(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        if (!dropdown) return;

        this.closeAll();
        dropdown.classList.add('active');
        this.activeDropdown = dropdownId;
    }

    /**
     * إغلاق قائمة
     */
    close(dropdownId) {
        const dropdown = document.getElementById(dropdownId);
        if (!dropdown) return;

        dropdown.classList.remove('active');
        
        const button = document.querySelector(`[data-dropdown-toggle="${dropdownId}"]`);
        if (button) {
            button.classList.remove('active');
        }

        if (this.activeDropdown === dropdownId) {
            this.activeDropdown = null;
        }
    }

    /**
     * إغلاق جميع القوائم
     */
    closeAll() {
        const dropdowns = document.querySelectorAll('.dropdown-menu.active');
        dropdowns.forEach(dropdown => {
            dropdown.classList.remove('active');
        });

        const buttons = document.querySelectorAll('[data-dropdown-toggle].active');
        buttons.forEach(button => {
            button.classList.remove('active');
        });

        this.activeDropdown = null;
    }
}

/**
 * تهيئة مدير القوائم عند تحميل الصفحة
 */
let dropdownManager;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        dropdownManager = new DropdownManager();
    });
} else {
    dropdownManager = new DropdownManager();
}

/**
 * دوال مساعدة للوصول من أي مكان
 */
function toggleDropdown(dropdownId, button = null) {
    if (dropdownManager) {
        dropdownManager.toggle(dropdownId, button);
    }
}

function openDropdown(dropdownId) {
    if (dropdownManager) {
        dropdownManager.open(dropdownId);
    }
}

function closeDropdown(dropdownId) {
    if (dropdownManager) {
        dropdownManager.close(dropdownId);
    }
}

function closeAllDropdowns() {
    if (dropdownManager) {
        dropdownManager.closeAll();
    }
}