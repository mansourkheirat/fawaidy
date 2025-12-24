<?php
/**
 * ملف دوال التحقق من البيانات
 * يتعامل مع التحقق من صحة جميع المدخلات
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/connection.php';

// منع الوصول المباشر للملف
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('تم حظر الوصول المباشر إلى هذا الملف');
}

class Validation {
    private static $errors = [];

    /**
     * التحقق من الاسم الكامل (عربي فقط)
     */
    public static function validateFullName($name) {
        // تنظيف المدخل
        $name = trim($name);
        
        // التحقق من أنه ليس فارغاً
        if (empty($name)) {
            self::$errors['fullname'] = 'الاسم الكامل مطلوب';
            return false;
        }
        
        // التحقق من أنه عربي فقط (بدون أرقام أو رموز)
        if (!preg_match('/^[\u0600-\u06FF\s]+$/u', $name)) {
            self::$errors['fullname'] = 'الاسم الكامل يجب أن يحتوي على أحرف عربية فقط';
            return false;
        }
        
        // التحقق من الطول
        if (strlen($name) < 3 || strlen($name) > 100) {
            self::$errors['fullname'] = 'الاسم يجب أن يكون بين 3 و 100 حرف';
            return false;
        }
        
        // التحقق من عدم وجود الاسم مسبقاً
        $stmt = db()->prepare("SELECT id FROM users WHERE full_name = ? AND deleted_at IS NULL");
        $stmt->bind_param('s', $name);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            self::$errors['fullname'] = 'هذا الاسم مستخدم بالفعل';
            return false;
        }
        
        return true;
    }

    /**
     * التحقق من اسم المستخدم
     */
    public static function validateUsername($username) {
        $username = trim($username);
        
        // التحقق من أنه ليس فارغاً
        if (empty($username)) {
            self::$errors['username'] = 'اسم المستخدم مطلوب';
            return false;
        }
        
        // التحقق من الصيغة (يبدأ بحرف، بدون عربي، بدون مساحات)
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9._-]{2,29}$/i', $username)) {
            self::$errors['username'] = 'اسم المستخدم غير صحيح. يجب أن يبدأ بحرف ويتضمن أحرف وأرقام و .- فقط';
            return false;
        }
        
        // التحقق من عدم وجود الاسم مسبقاً (case-insensitive)
        $stmt = db()->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(?) AND deleted_at IS NULL");
        $stmt->bind_param('s', $username);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            self::$errors['username'] = 'اسم المستخدم موجود بالفعل';
            return false;
        }
        
        return true;
    }

    /**
     * التحقق من البريد الإلكتروني
     */
    public static function validateEmail($email) {
        $email = trim($email);
        
        // التحقق من الصيغة
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            self::$errors['email'] = 'البريد الإلكتروني غير صحيح';
            return false;
        }
        
        // التحقق من عدم استخدام البريد مسبقاً
        $stmt = db()->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            self::$errors['email'] = 'هذا البريد الإلكتروني مسجل بالفعل';
            return false;
        }
        
        return true;
    }

    /**
     * التحقق من قوة كلمة المرور
     * يجب أن تحتوي على: 8+ أحرف، حرف كبير، رقم، رمز
     */
    public static function validatePassword($password) {
        // التحقق من الطول
        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            self::$errors['password'] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
            return false;
        }
        
        // التحقق من وجود حرف كبير
        if (!preg_match('/[A-Z]/', $password)) {
            self::$errors['password'] = 'كلمة المرور يجب أن تحتوي على حرف كبير على الأقل';
            return false;
        }
        
        // التحقق من وجود رقم
        if (!preg_match('/[0-9]/', $password)) {
            self::$errors['password'] = 'كلمة المرور يجب أن تحتوي على رقم على الأقل';
            return false;
        }
        
        // التحقق من وجود رمز خاص
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
            self::$errors['password'] = 'كلمة المرور يجب أن تحتوي على رمز خاص واحد على الأقل';
            return false;
        }
        
        // التحقق من عدم وجود أحرف عربية أو مساحات
        if (preg_match('/[\u0600-\u06FF\s]/u', $password)) {
            self::$errors['password'] = 'كلمة المرور لا يجب أن تحتوي على أحرف عربية أو مساحات';
            return false;
        }
        
        return true;
    }

    /**
     * التحقق من تطابق كلمتي المرور
     */
    public static function validatePasswordMatch($password1, $password2) {
        if ($password1 !== $password2) {
            self::$errors['password_confirm'] = 'كلمات المرور غير متطابقة';
            return false;
        }
        return true;
    }

    /**
     * التحقق من البلد (عربي فقط)
     */
    public static function validateCountry($country) {
        $country = trim($country);
        
        if (empty($country)) {
            self::$errors['country'] = 'البلد مطلوب';
            return false;
        }
        
        if (!preg_match('/^[\u0600-\u06FF\s]+$/u', $country)) {
            self::$errors['country'] = 'البلد يجب أن يكون بالعربية فقط';
            return false;
        }
        
        return true;
    }

    /**
     * التحقق من الجنس
     */
    public static function validateGender($gender) {
        $allowed = ['unspecified', 'male', 'female'];
        if (!in_array($gender, $allowed)) {
            self::$errors['gender'] = 'الجنس غير صحيح';
            return false;
        }
        return true;
    }

    /**
     * التحقق من تاريخ الميلاد
     */
    public static function validateBirthDate($date) {
        // التحقق من صيغة التاريخ
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            self::$errors['birth_date'] = 'صيغة التاريخ غير صحيحة';
            return false;
        }
        
        // التحقق من صحة التاريخ
        $d = DateTime::createFromFormat('Y-m-d', $date);
        if (!$d || $d->format('Y-m-d') !== $date) {
            self::$errors['birth_date'] = 'التاريخ غير صحيح';
            return false;
        }
        
        return true;
    }

    /**
     * الحصول على الأخطاء
     */
    public static function getErrors() {
        return self::$errors;
    }

    /**
     * التحقق من وجود أخطاء
     */
    public static function hasErrors() {
        return !empty(self::$errors);
    }

    /**
     * إعادة تعيين الأخطاء
     */
    public static function clearErrors() {
        self::$errors = [];
    }

    /**
     * إضافة خطأ مخصص
     */
    public static function addError($field, $message) {
        self::$errors[$field] = $message;
    }

    /**
     * التحقق من البيانات بشكل شامل (للتسجيل)
     */
    public static function validateRegistration($data) {
        self::clearErrors();
        
        // التحقق من كل حقل
        if (!self::validateFullName($data['fullname'] ?? '')) {
            return false;
        }
        
        if (!self::validateUsername($data['username'] ?? '')) {
            return false;
        }
        
        if (!self::validateEmail($data['email'] ?? '')) {
            return false;
        }
        
        if (!self::validatePassword($data['password'] ?? '')) {
            return false;
        }
        
        if (!self::validatePasswordMatch($data['password'] ?? '', $data['password_confirm'] ?? '')) {
            return false;
        }
        
        if (!self::validateGender($data['gender'] ?? 'unspecified')) {
            return false;
        }
        
        if (!empty($data['country'])) {
            if (!self::validateCountry($data['country'])) {
                return false;
            }
        }
        
        return !self::hasErrors();
    }
}

// دوال مساعدة
function validate($type, $value) {
    $method = 'validate' . ucfirst($type);
    if (method_exists('Validation', $method)) {
        return Validation::$method($value);
    }
    return false;
}

function getValidationErrors() {
    return Validation::getErrors();
}
?>