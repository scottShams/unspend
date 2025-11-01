<?php

// Load environment variables
require_once __DIR__ . '/env.php';

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . Env::get('DB_HOST', 'localhost') . ";dbname=" . Env::get('DB_NAME', 'unSpend'),
                Env::get('DB_USER', 'root'),
                Env::get('DB_PASS', ''),
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    // Prevent cloning of the instance
    private function __clone() { }

    // Prevent unserializing of the instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}