<?php
/**
 * ملف دوال الأمان والحماية
 * يتعامل مع التشفير والحماية من الهجمات
 */

require_once __DIR__ . '/../config/config.php';

// منع الوصول المباشر للملف
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('تم حظر الوصول المباشر إلى هذا الملف');
}

class Security {
    /**
     * تشفير كلمة المرور
     */
    public static function hashPassword($password) {
        return password_hash(
            $password,
            PASSWORD_HASH_ALGO,
            PASSWORD_HASH_OPTIONS
        );
    }

    /**
     * التحقق من كلمة المرور
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * تحديث كلمة المرور إذا لزم الأمر (rehashing)
     */
    public static function passwordNeedsRehash($hash) {
        return password_needs_rehash(
            $hash,
            PASSWORD_HASH_ALGO,
            PASSWORD_HASH_OPTIONS
        );
    }

    /**
     * تنظيف المدخلات من XSS
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = self::sanitize($value);
            }
            return $input;
        }
        
        return htmlspecialchars(
            trim($input),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    /**
     * الحماية من SQL Injection (استخدام Prepared Statements)
     */
    public static function escapeSql($value) {
        return db()->connection->real_escape_string($value);
    }

    /**
     * إنشاء رمز CSRF
     */
    public static function generateCSRFToken() {
        if (empty($_SESSION[CSRF_TOKEN_NAME])) {
            $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        }
        return $_SESSION[CSRF_TOKEN_NAME];
    }

    /**
     * التحقق من رمز CSRF
     */
    public static function verifyCSRFToken($token = null) {
        $token = $token ?? $_POST[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        
        if (!$token || !hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token)) {
            return false;
        }
        return true;
    }

    /**
     * إنشاء رمز التحقق من البريد الإلكتروني
     */
    public static function generateVerificationCode($length = 6) {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * إنشاء رمز استعادة كلمة المرور
     */
    public static function generatePasswordResetToken() {
        return bin2hex(random_bytes(32));
    }

    /**
     * تشفير البيانات الحساسة
     */
    public static function encrypt($data) {
        $cipher = "AES-256-CBC";
        $key = hash('sha256', JWT_SECRET);
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
        
        $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * فك تشفير البيانات
     */
    public static function decrypt($data) {
        $cipher = "AES-256-CBC";
        $key = hash('sha256', JWT_SECRET);
        $data = base64_decode($data);
        $iv = substr($data, 0, openssl_cipher_iv_length($cipher));
        $encrypted = substr($data, openssl_cipher_iv_length($cipher));
        
        return openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
    }

    /**
     * التحقق من صلاحيات المستخدم
     */
    public static function checkPermission($requiredRole) {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        $userRole = $_SESSION['user_role'] ?? ROLE_MEMBER;
        return $userRole >= $requiredRole;
    }

    /**
     * حماية الصفحات الإدارية
     */
    public static function requireAdmin() {
        if (!self::checkPermission(ROLE_ADMIN)) {
            header('Location: ' . SITE_URL);
            exit('لا توجد صلاحيات كافية');
        }
    }

    /**
     * حماية الصفحات الخاصة بالمستخدمين المسجلين
     */
    public static function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . SITE_URL . 'login');
            exit;
        }
    }

    /**
     * التحقق من صحة البريد الإلكتروني
     */
    public static function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * التحقق من صحة رقم الهاتف
     */
    public static function isValidPhone($phone) {
        return preg_match('/^[0-9+\-\s()]{10,}$/', $phone);
    }

    /**
     * منع الوصول المباشر إلى الملفات الحساسة
     */
    public static function preventDirectAccess() {
        if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
            exit('تم حظر الوصول المباشر إلى هذا الملف');
        }
    }

    /**
     * إنشاء JWT Token
     */
    public static function createJWT($data, $expiration = 3600) {
        $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
        $payload = json_encode(array_merge($data, [
            'iat' => time(),
            'exp' => time() + $expiration
        ]));
        
        $header_encoded = base64_encode($header);
        $payload_encoded = base64_encode($payload);
        
        $signature = hash_hmac(
            'sha256',
            "$header_encoded.$payload_encoded",
            JWT_SECRET,
            true
        );
        $signature_encoded = base64_encode($signature);
        
        return "$header_encoded.$payload_encoded.$signature_encoded";
    }

    /**
     * التحقق من صحة JWT Token
     */
    public static function verifyJWT($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }
        
        list($header_encoded, $payload_encoded, $signature_encoded) = $parts;
        
        $signature = hash_hmac(
            'sha256',
            "$header_encoded.$payload_encoded",
            JWT_SECRET,
            true
        );
        $signature_expected = base64_encode($signature);
        
        if (!hash_equals($signature_expected, $signature_encoded)) {
            return false;
        }
        
        $payload = json_decode(base64_decode($payload_encoded), true);
        
        if ($payload['exp'] < time()) {
            return false;
        }
        
        return $payload;
    }

    /**
     * تحديد معايير الأمان للرؤوس
     */
    public static function setSecurityHeaders() {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        if (!DEBUG_MODE) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\';');
        }
    }
}

// تطبيق رؤوس الأمان تلقائياً
Security::setSecurityHeaders();

// دوال مساعدة
function sanitize($input) {
    return Security::sanitize($input);
}

function hashPassword($password) {
    return Security::hashPassword($password);
}

function verifyPassword($password, $hash) {
    return Security::verifyPassword($password, $hash);
}

function getCSRFToken() {
    return Security::generateCSRFToken();
}
?>