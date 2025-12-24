/**
 * ملف دوال التاريخ الهجري والميلادي
 * يتعامل مع تحويل التاريخ وعرضه بشكل صحيح
 */

class DateConverter {
    constructor() {
        this.daysArabic = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
        this.monthsGregorian = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
        this.monthsHijri = ['محرم', 'صفر', 'ربيع الأول', 'ربيع الثاني', 'جمادى الأولى', 'جمادى الآخرة', 'رجب', 'شعبان', 'رمضان', 'شوال', 'ذو القعدة', 'ذو الحجة'];
    }

    /**
     * تحويل التاريخ الميلادي إلى هجري
     * الصيغة: YYYY-MM-DD أو Date object
     */
    gregorianToHijri(gregorianDate) {
        if (typeof gregorianDate === 'string') {
            gregorianDate = new Date(gregorianDate);
        }

        const jd = this.gregorianToJD(gregorianDate);
        return this.jdToHijri(jd);
    }

    /**
     * تحويل التاريخ الهجري إلى ميلادي
     * الصيغة: {year, month, day}
     */
    hijriToGregorian(hijriDate) {
        const jd = this.hijriToJD(hijriDate);
        return this.jdToGregorian(jd);
    }

    /**
     * تحويل التاريخ الميلادي إلى رقم اليوم اليولياني
     */
    gregorianToJD(date) {
        const year = date.getFullYear();
        const month = date.getMonth() + 1;
        const day = date.getDate();

        let a = Math.floor((14 - month) / 12);
        let y = year + 4800 - a;
        let m = month + 12 * a - 3;

        return day + Math.floor((153 * m + 2) / 5) + 365 * y + Math.floor(y / 4) - Math.floor(y / 100) + Math.floor(y / 400) - 32045;
    }

    /**
     * تحويل رقم اليوم اليولياني إلى ميلادي
     */
    jdToGregorian(jd) {
        let a = jd + 32044;
        let b = Math.floor((4 * a + 3) / 146097);
        let c = a - Math.floor((146097 * b) / 4);
        let d = Math.floor((4 * c + 3) / 1461);
        let e = c - Math.floor((1461 * d) / 4);
        let m = Math.floor((5 * e + 2) / 153);

        const day = e - Math.floor((153 * m + 2) / 5) + 1;
        const month = m + 3 - 12 * Math.floor(m / 10);
        const year = 100 * b + d - 4800 + Math.floor(m / 10);

        return {
            year: parseInt(year),
            month: parseInt(month),
            day: parseInt(day)
        };
    }

    /**
     * تحويل التاريخ الهجري إلى رقم اليوم اليولياني
     */
    hijriToJD(hijriDate) {
        const year = hijriDate.year;
        const month = hijriDate.month;
        const day = hijriDate.day;

        return Math.floor((11 * year + 3) / 30) + 354 * year + 30 * month - Math.floor((month - 1) / 11) + day + 1948439.5;
    }

    /**
     * تحويل رقم اليوم اليولياني إلى هجري
     */
    jdToHijri(jd) {
        jd = Math.floor(jd) + 0.5;
        let z = Math.floor(jd);
        let a = Math.floor((z - 1948439.5) * 10000 / 3652425);
        let year = Math.floor(a + 1);
        let month = Math.floor(((jd - (year * 354 + Math.floor((3 + 11 * year) / 30) + 1948439.5)) / 29.5001) + 0.99);
        
        if (month === 0) {
            month = 1;
        }
        if (month > 12) {
            month = 12;
            year += 1;
        }

        let day = Math.floor(jd - (354 * year + 30 * month - Math.floor((month - 1) / 11) + 1948439.5)) + 1;

        return {
            year: parseInt(year),
            month: parseInt(month),
            day: parseInt(day)
        };
    }

    /**
     * الحصول على اسم اليوم بالعربية
     */
    getDayName(date) {
        if (typeof date === 'string') {
            date = new Date(date);
        }
        return this.daysArabic[date.getDay()];
    }

    /**
     * الحصول على اسم الشهر الميلادي بالعربية
     */
    getGregorianMonthName(month) {
        return this.monthsGregorian[month - 1];
    }

    /**
     * الحصول على اسم الشهر الهجري بالعربية
     */
    getHijriMonthName(month) {
        return this.monthsHijri[month - 1];
    }

    /**
     * تنسيق التاريخ الميلادي (يوم شهر سنة)
     */
    formatGregorian(date) {
        if (typeof date === 'string') {
            date = new Date(date);
        }

        const day = date.getDate();
        const month = this.getGregorianMonthName(date.getMonth() + 1);
        const year = date.getFullYear();

        return `${day} ${month} ${year}`;
    }

    /**
     * تنسيق التاريخ الهجري (يوم شهر سنة)
     */
    formatHijri(hijriDate) {
        const day = hijriDate.day;
        const month = this.getHijriMonthName(hijriDate.month);
        const year = hijriDate.year;

        return `${day} ${month} ${year}`;
    }

    /**
     * الحصول على التاريخ الحالي بصيغة مختصرة
     */
    getCurrentDateFormatted() {
        const today = new Date();
        const gregorian = {
            year: today.getFullYear(),
            month: today.getMonth() + 1,
            day: today.getDate()
        };
        const hijri = this.gregorianToHijri(today);

        return {
            dayName: this.getDayName(today),
            gregorian: this.formatGregorian(today),
            hijri: this.formatHijri(hijri),
            gregorianShort: `${gregorian.day}/${gregorian.month}/${gregorian.year}`,
            hijriShort: `${hijri.day}/${hijri.month}/${hijri.year}`
        };
    }
}

/**
 * إنشاء نسخة عامة من محول التاريخ
 */
const dateConverter = new DateConverter();

/**
 * تحديث عرض التاريخ في الشريط العلوي
 */
function updateDates() {
    const currentDate = dateConverter.getCurrentDateFormatted();

    // تحديث اسم اليوم
    const dayNameElement = document.getElementById('dayName');
    if (dayNameElement) {
        dayNameElement.textContent = currentDate.dayName;
    }

    // تحديث التاريخ الهجري
    const hijriDateElement = document.getElementById('hijriDate');
    if (hijriDateElement) {
        hijriDateElement.textContent = currentDate.hijri;
    }

    // تحديث التاريخ الميلادي
    const gregorianDateElement = document.getElementById('gregorianDate');
    if (gregorianDateElement) {
        gregorianDateElement.textContent = currentDate.gregorian;
    }
}

/**
 * تحويل سهل للتاريخ
 */
function convertToHijri(gregorianDate) {
    return dateConverter.gregorianToHijri(gregorianDate);
}

function convertToGregorian(hijriDate) {
    return dateConverter.hijriToGregorian(hijriDate);
}

function formatDate(date, format = 'gregorian') {
    if (format === 'hijri') {
        const hijri = dateConverter.gregorianToHijri(date);
        return dateConverter.formatHijri(hijri);
    }
    return dateConverter.formatGregorian(date);
}

// تحديث التاريخ عند تحميل الصفحة
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', updateDates);
} else {
    updateDates();
}