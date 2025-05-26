<?php
class SessionManager {
    public static function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_lifetime' => 86400, // 1 day
                'cookie_secure' => true,
                'cookie_httponly' => true,
                'cookie_samesite' => 'Strict'
            ]);
        }
    }

    public static function createUserSession($user) {
        self::startSession();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in'] = true;
        session_regenerate_id(true);
    }

    public static function isLoggedIn() {
        self::startSession();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    public static function getCurrentUser() {
        self::startSession();
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? null
        ];
    }

    public static function destroySession() {
        self::startSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
?>