<?php
class SessionManager {
    private static $sessionStarted = false;

    public static function startSession() {
        if (!self::$sessionStarted) {
            session_start();
            self::$sessionStarted = true;
        }
    }

    public static function checkUserSession() {
        self::startSession();
        
        if (!isset($_SESSION['user_id'])) {
            if (basename($_SERVER['PHP_SELF']) !== 'login.php' && 
                basename($_SERVER['PHP_SELF']) !== 'registro.php') {
                header("Location: login.php");
                exit();
            }
            return false;
        }
        
        if ($_SESSION['user_type'] === 'admin' && 
            !strpos($_SERVER['PHP_SELF'], 'admin') && 
            basename($_SERVER['PHP_SELF']) !== 'logout.php') {
            header("Location: admin.php");
            exit();
        }
        
        if ($_SESSION['user_type'] !== 'admin' && 
            strpos($_SERVER['PHP_SELF'], 'admin')) {
            header("Location: home.php");
            exit();
        }
        
        return true;
    }
}
?>