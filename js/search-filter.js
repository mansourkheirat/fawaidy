/**
 * ==========================================
 * نظام البحث والفلترة المتقدم
 * ==========================================
 * 
 * الملف: js/search-filter.js
 * الوصف: نظام بحث وفلترة متقدم مع AutoComplete
 * 
 * المحتويات:
 * - البحث الفوري
 * - الاقتراحات التلقائية
 * - الفلترة المتقدمة
 * - حفظ البحث السابق
 * - التصنيفات الشهيرة
 */

class SearchFilter {
    /**
     * Constructor - تهيئة نظام البحث
     */
    constructor() {
        this.baseUrl = this.getBaseUrl();
        this.searchInput = document.querySelector('.search-input');
        this.filterForm = document.getElementById('filterForm');
        this.recentSearches = [];
        
        if (!this.searchInput) {
            return;
        }

        this.init();
    }

    /**
     * التهيئة الأساسية
     */
    init() {
        this.loadRecentSearches();
        this.setupEventListeners();
        this.createAutocomplete();
    }

    /**
     * إعداد مستمعات الأحداث
     */
    setupEventListeners() {
        // البحث الفوري
        this.searchInput.addEventListener('input', (e) => {
            this.handleSearch(e.target.value);
        });

        // البحث عند الضغط على Enter
        this.searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.performSearch();
            }
        });

        // مسح البحث
        this.searchInput.addEventListener('click', () => {
            this.searchInput.focus();
        });

        // التعامل مع مفاتيح الأسهم في الاقتراحات
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                this.handleArrowKeys(e);
            }
        });
    }

    /**
     * معالجة البحث الفوري
     */
    async handleSearch(query) {
        if (!query || query.trim().length < 2) {
            this.hideAutocomplete();
            return;
        }

        // محاكاة البحث (في الواقع، يتم الاتصال بـ API)
        const suggestions = await this.getSuggestions(query);
        this.showAutocomplete(suggestions);
    }

    /**
     * الحصول على الاقتراحات من الخادم
     */
    async getSuggestions(query) {
        try {
            const response = await fetch(
                `${this.baseUrl}api/search/suggestions.php?q=${encodeURIComponent(query)}`,
                {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            const data = await response.json();
            return data.suggestions || [];
        } catch (error) {
            console.error('خطأ في الحصول على الاقتراحات:', error);
            return [];
        }
    }

    /**
     * إنشاء عنصر Autocomplete
     */
    createAutocomplete() {
        const autocomplete = document.createElement('div');
        autocomplete.className = 'search-autocomplete';
        autocomplete.innerHTML = `
            <div class="autocomplete-list"></div>
            <div class="autocomplete-recent">
                <div class="autocomplete-section-title">البحث السابق</div>
                <div class="autocomplete-recent-items"></div>
            </div>
            <div class="autocomplete-popular">
                <div class="autocomplete-section-title">بحث شهير</div>
                <div class="autocomplete-popular-items"></div>
            </div>
        `;

        this.searchInput.parentNode.insertBefore(autocomplete, this.searchInput.nextSibling);
        this.autocomplete = autocomplete;

        // إغلاق Autocomplete عند الضغط خارجه
        document.addEventListener('click', (e) => {
            if (!this.searchInput.contains(e.target) && !this.autocomplete.contains(e.target)) {
                this.hideAutocomplete();
            }
        });
    }

    /**
     * عرض الاقتراحات
     */
    showAutocomplete(suggestions) {
        const list = this.autocomplete.querySelector('.autocomplete-list');
        
        if (suggestions.length === 0) {
            list.innerHTML = '<div class="autocomplete-empty">لا توجد نتائج</div>';
        } else {
            list.innerHTML = suggestions.map((s, i) => `
                <div class="autocomplete-item" data-index="${i}">
                    <span class="autocomplete-icon">🔍</span>
                    <span class="autocomplete-text">${this.escapeHTML(s.title)}</span>
                    <span class="autocomplete-category">${this.escapeHTML(s.category || '')}</span>
                </div>
            `).join('');

            // إضافة مستمعات الأحداث
            const items = list.querySelectorAll('.autocomplete-item');
            items.forEach(item => {
                item.addEventListener('click', () => {
                    this.selectSuggestion(suggestions[item.getAttribute('data-index')]);
                });
            });
        }

        this.autocomplete.classList.add('active');
    }

    /**
     * إخفاء Autocomplete
     */
    hideAutocomplete() {
        this.autocomplete.classList.remove('active');
    }

    /**
     * اختيار اقتراح
     */
    selectSuggestion(suggestion) {
        this.searchInput.value = suggestion.title;
        this.addToRecentSearches(suggestion);
        this.hideAutocomplete();
        this.performSearch();
    }

    /**
     * تنفيذ البحث
     */
    performSearch() {
        const query = this.searchInput.value.trim();
        if (!query) return;

        // إرسال النموذج أو إعادة التوجيه
        if (this.filterForm) {
            this.filterForm.submit();
        } else {
            window.location.href = `${this.baseUrl}benefits?q=${encodeURIComponent(query)}`;
        }
    }

    /**
     * إضافة البحث إلى السجل
     */
    addToRecentSearches(search) {
        // إزالة البحث المكرر
        this.recentSearches = this.recentSearches.filter(s => s.title !== search.title);
        
        // إضافة البحث الجديد في البداية
        this.recentSearches.unshift(search);
        
        // الحفاظ على 10 عناصر فقط
        if (this.recentSearches.length > 10) {
            this.recentSearches.pop();
        }

        // حفظ في Session Storage
        try {
            sessionStorage.setItem('recentSearches', JSON.stringify(this.recentSearches));
        } catch (e) {
            console.warn('لا يمكن حفظ البحث السابق');
        }

        this.renderRecentSearches();
    }

    /**
     * تحميل البحث السابق
     */
    loadRecentSearches() {
        try {
            const saved = sessionStorage.getItem('recentSearches');
            if (saved) {
                this.recentSearches = JSON.parse(saved);
            }
        } catch (e) {
            console.warn('لا يمكن تحميل البحث السابق');
        }

        this.renderRecentSearches();
    }

    /**
     * عرض البحث السابق
     */
    renderRecentSearches() {
        const recentContainer = this.autocomplete.querySelector('.autocomplete-recent-items');
        
        if (this.recentSearches.length === 0) {
            recentContainer.innerHTML = '<div class="autocomplete-empty-small">لم يكن هناك بحث سابق</div>';
            return;
        }

        recentContainer.innerHTML = this.recentSearches.slice(0, 5).map(s => `
            <div class="autocomplete-recent-item" data-search="${this.escapeHTML(s.title)}">
                <span class="recent-icon">⏱️</span>
                <span class="recent-text">${this.escapeHTML(s.title)}</span>
            </div>
        `).join('');

        // إضافة مستمعات الأحداث
        const items = recentContainer.querySelectorAll('.autocomplete-recent-item');
        items.forEach(item => {
            item.addEventListener('click', () => {
                this.searchInput.value = item.getAttribute('data-search');
                this.performSearch();
            });
        });
    }

    /**
     * معالجة مفاتيح الأسهم
     */
    handleArrowKeys(e) {
        const items = this.autocomplete.querySelectorAll('.autocomplete-item');
        if (items.length === 0) return;

        let current = this.autocomplete.querySelector('.autocomplete-item.selected');
        let nextIndex = 0;

        if (current) {
            nextIndex = Array.from(items).indexOf(current);
            items[nextIndex].classList.remove('selected');
        }

        if (e.key === 'ArrowDown') {
            nextIndex = (nextIndex + 1) % items.length;
        } else {
            nextIndex = (nextIndex - 1 + items.length) % items.length;
        }

        items[nextIndex].classList.add('selected');
        items[nextIndex].scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        e.preventDefault();
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
        return String(text).replace(/[&<>"']/g, char => map[char]);
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
     * مسح البحث السابق
     */
    clearRecentSearches() {
        this.recentSearches = [];
        try {
            sessionStorage.removeItem('recentSearches');
        } catch (e) {
            console.warn('لا يمكن حذف البحث السابق');
        }
        this.renderRecentSearches();
    }
}

/**
 * تهيئة نظام البحث
 */
let searchFilter;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        searchFilter = new SearchFilter();
    });
} else {
    searchFilter = new SearchFilter();
}