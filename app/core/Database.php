<?php
namespace App\Core;

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        // Fetch from environment variables with fallback to local defaults
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'medin_db';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
        $this->port = getenv('DB_PORT') ?: '3306';
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $options = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ];
            $this->conn = new \PDO("mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name, $this->username, $this->password, $options);
            $this->conn->exec("set names utf8");
        } catch(\PDOException $exception) {
            // In production, log the error rather than displaying it
            error_log("Connection error: " . $exception->getMessage());
            die(json_encode(["success" => false, "message" => "Database connection failed: " . $exception->getMessage()]));
        }

        return $this->conn;
    }
}
?>
