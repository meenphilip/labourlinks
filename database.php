<?php
require_once __DIR__.'/../config/db_config.php';

class Database {
    private static $connection = null;

    public static function getConnection() {
        if (!self::$connection) {
            try {
                self::$connection = new PDO('sqlite:'.DB_FILE);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::initializeDB();
            } catch(PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$connection;
    }

    private static function initializeDB() {
        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                password TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS sessions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                session_id TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                expires_at TIMESTAMP NOT NULL,
                FOREIGN KEY(user_id) REFERENCES users(id)
            )"
        ];

        foreach ($tables as $table) {
            self::$connection->exec($table);
        }
    }
}
?>