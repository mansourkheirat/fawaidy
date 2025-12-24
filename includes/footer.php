<?php
/**
 * ملف التذييل
 * يحتوي على هيكل التذييل للموقع
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../database/connection.php';

// منع الوصول المباشر
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('تم حظر الوصول المباشر إلى هذا الملف');
}
?>

<!-- ================================
     التذييل
     ================================ -->
<footer class="main-footer">
    <div class="footer-container">
        
        <!-- ================================
             الجزء الأول: الشعار والفقرة
             ================================ -->
        <section class="footer-section footer-brand">
            <a href="<?php echo SITE_URL; ?>" class="footer-logo">
                <svg class="footer-logo-icon" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="50" cy="50" r="45" fill="none" stroke="currentColor" stroke-width="2"/>
                    <text x="50" y="60" font-size="40" text-anchor="middle" fill="currentColor" font-family="Arial">ف</text>
                </svg>
                <span class="footer-logo-text"><?php echo SITE_NAME; ?></span>
            </a>
            <p class="footer-description">
                منصة متكاملة لنشر الفوائد العلمية والمعرفية المميزة، حيث نجمع أفضل المحتويات المفيدة من جميع أنحاء العالم.
            </p>
        </section>

        <!-- ================================
             الجزء الثاني: روابط التواصل الاجتماعي
             ================================ -->
        <section class="footer-section footer-social">
            <h3 class="footer-title">تابعنا</h3>
            <ul class="social-links">
                <li>
                    <a href="#" class="social-link facebook" title="فيسبوك" aria-label="فيسبوك">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 3a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14m-.5 15.5v-5.3a3.26 3.26 0 0 0-3.26-3.26c-.85 0-1.84.52-2.32 1.39v-1.2h-2.5v8.5h2.5v-4.34c0-.77.62-1.4 1.40-1.4.77 0 1.4.63 1.4 1.4v4.34h2.5M6.88 8.56a1.68 1.68 0 0 0 1.68-1.68c0-.93-.75-1.69-1.68-1.69a1.69 1.69 0 0 0-1.69 1.69c0 .93.76 1.68 1.69 1.68m1.39 9.94v-8.5H5.5v8.5h2.77z"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="#" class="social-link twitter" title="تويتر" aria-label="تويتر">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22.46 6c-.86.38-1.78.64-2.75.76 1-.6 1.76-1.55 2.12-2.68-.93.55-1.96.95-3.06 1.17-.88-.94-2.13-1.53-3.51-1.53-2.66 0-4.81 2.16-4.81 4.81 0 .38.04.75.13 1.1-4-.2-7.63-2.15-10.04-5.11-.42.73-.66 1.57-.66 2.47 0 1.67.85 3.14 2.14 4.01-.79-.03-1.54-.24-2.19-.6v.06c0 2.33 1.66 4.28 3.86 4.72-.4.11-.83.17-1.27.17-.31 0-.62-.03-.92-.08.62 1.91 2.41 3.3 4.54 3.34-1.65 1.29-3.73 2.06-5.99 2.06-.39 0-.77-.02-1.15-.07 2.14 1.37 4.68 2.17 7.39 2.17 8.87 0 13.7-7.35 13.7-13.7 0-.21 0-.41-.01-.62.94-.68 1.76-1.53 2.41-2.5z"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="#" class="social-link instagram" title="إنستجرام" aria-label="إنستجرام">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="12" cy="12" r="6" fill="none" stroke="currentColor" stroke-width="2"/>
                            <circle cx="17.5" cy="6.5" r="1.5" fill="currentColor"/>
                        </svg>
                    </a>
                </li>
                <li>
                    <a href="#" class="social-link youtube" title="يوتيوب" aria-label="يوتيوب">
                        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M23 7v10a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h18a2 2 0 0 1 2 2m-10 7l-5-3v6l5-3z"/>
                        </svg>
                    </a>
                </li>
            </ul>
        </section>

        <!-- ================================
             الجزء الثالث: روابط مواقع صديقة
             ================================ -->
        <section class="footer-section footer-links">
            <h3 class="footer-title">مواقع صديقة</h3>
            <ul class="footer-links-list">
                <li><a href="#">موقع صديق 1</a></li>
                <li><a href="#">موقع صديق 2</a></li>
                <li><a href="#">موقع صديق 3</a></li>
                <li><a href="#">موقع صديق 4</a></li>
            </ul>
        </section>

        <!-- ================================
             الجزء الرابع: روابط الصفحات المهمة
             ================================ -->
        <section class="footer-section footer-pages">
            <h3 class="footer-title">روابط مهمة</h3>
            <ul class="footer-links-list">
                <li><a href="<?php echo SITE_URL; ?>privacy">سياسة الخصوصية</a></li>
                <li><a href="<?php echo SITE_URL; ?>terms">شروط الاستخدام</a></li>
                <li><a href="<?php echo SITE_URL; ?>about">نبذة عن الموقع</a></li>
                <li><a href="<?php echo SITE_URL; ?>contact">تواصل معنا</a></li>
            </ul>
        </section>

        <!-- ================================
             الجزء الخامس: معلومات التواصل
             ================================ -->
        <section class="footer-section footer-contact">
            <h3 class="footer-title">تواصل معنا</h3>
            <ul class="contact-info">
                <li>
                    <span class="contact-icon">📧</span>
                    <a href="mailto:<?php echo ADMIN_EMAIL; ?>"><?php echo ADMIN_EMAIL; ?></a>
                </li>
                <li>
                    <span class="contact-icon">📞</span>
                    <a href="tel:+966123456789">+966 123456789</a>
                </li>
                <li>
                    <span class="contact-icon">📍</span>
                    <span>الرياض، المملكة العربية السعودية</span>
                </li>
            </ul>
        </section>

    </div>

    <!-- ================================
         شريط الحقوق
         ================================ -->
    <div class="footer-bottom">
        <div class="footer-copyright">
            <p>&copy; <?php echo date('Y'); ?> <strong><?php echo SITE_NAME; ?></strong>. جميع الحقوق محفوظة.</p>
        </div>
        <div class="footer-credits">
            <p>تطوير: <a href="#">فريق التطوير</a></p>
        </div>
    </div>
</footer>

<!-- CSS التذييل -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>css/footer.css">

<!-- إغلاق البدن والـ HTML -->
</body>
</html>