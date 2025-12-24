<?php
/**
 * ملف الاتصال بقاعدة البيانات
 * يتعامل مع الاتصال الآمن باستخدام MySQLi
 */

require_once __DIR__ . '/../config/config.php';

// منع الوصول المباشر للملف
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    exit('تم حظر الوصول المباشر إلى هذا الملف');
}

class Database {
    private static $instance = null;
    private $connection;
    private $last_query = '';
    private $query_count = 0;

    /**
     * Constructor - منع الإنشاء المباشر للكلاس
     */
    private function __construct() {
        $this->connect();
    }

    /**
     * الحصول على نسخة وحيدة من الاتصال (Singleton Pattern)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * الاتصال بقاعدة البيانات
     */
    private function connect() {
        try {
            // إنشاء الاتصال
            $this->connection = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                DB_NAME
            );

            // التحقق من الاتصال
            if ($this->connection->connect_error) {
                throw new Exception("خطأ في الاتصال: " . $this->connection->connect_error);
            }

            // تعيين الترميز
            $this->connection->set_charset(DB_CHARSET);

            // تعيين المنطقة الزمنية
            $this->connection->query("SET time_zone = '" . SITE_TIMEZONE . "'");

        } catch (Exception $e) {
            error_log($e->getMessage());
            if (DEBUG_MODE) {
                die("خطأ في قاعدة البيانات: " . $e->getMessage());
            } else {
                die("حدث خطأ في الموقع، يرجى المحاولة لاحقاً");
            }
        }
    }

    /**
     * تنفيذ استعلام مع التحضير الآمن
     */
    public function prepare($query) {
        $this->last_query = $query;
        return $this->connection->prepare($query);
    }

    /**
     * تنفيذ استعلام مباشر (للاستعلامات الآمنة فقط)
     */
    public function query($query) {
        $this->last_query = $query;
        $this->query_count++;
        return $this->connection->query($query);
    }

    /**
     * الحصول على آخر ID مُدرج
     */
    public function lastInsertId() {
        return $this->connection->insert_id;
    }

    /**
     * الحصول على عدد الصفوف المتأثرة
     */
    public function affectedRows() {
        return $this->connection->affected_rows;
    }

    /**
     * الحصول على رسالة الخطأ الأخيرة
     */
    public function getError() {
        return $this->connection->error;
    }

    /**
     * الحصول على آخر استعلام
     */
    public function getLastQuery() {
        return $this->last_query;
    }

    /**
     * الحصول على عدد الاستعلامات
     */
    public function getQueryCount() {
        return $this->query_count;
    }

    /**
     * بدء معاملة
     */
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }

    /**
     * تأكيد المعاملة
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * التراجع عن المعاملة
     */
    public function rollback() {
        return $this->connection->rollback();
    }

    /**
     * إغلاق الاتصال
     */
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    /**
     * منع النسخ والاستنساخ
     */
    private function __clone() {}
    public function __wakeup() {}
}

// إنشاء دالة مساعدة للوصول إلى الاتصال بسهولة
function getDB() {
    return Database::getInstance()->connection;
}

// إنشاء دالة مساعدة للوصول إلى كائن Database
function db() {
    return Database::getInstance();
}

// إغلاق الاتصال عند انتهاء السكريبت
register_shutdown_function(function() {
    Database::getInstance()->close();
});
?>