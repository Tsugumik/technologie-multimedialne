<?php
class Visitor {
    private $pdo;

    public function __construct($pdoConnection) {
        $this->pdo = $pdoConnection;
        $this->initTable();
    }

    private function initTable() {
        $sql = "CREATE TABLE IF NOT EXISTS visitors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(50),
            user_agent TEXT,
            resolution VARCHAR(50),
            color_depth INT,
            cookies_enabled TINYINT(1),
            latitude FLOAT,
            longitude FLOAT,
            visited_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
    }

    public function save($ip, $ua, $resolution, $color_depth, $cookies, $lat, $lng) {
        $stmt = $this->pdo->prepare("INSERT INTO visitors (ip_address, user_agent, resolution, color_depth, cookies_enabled, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$ip, $ua, $resolution, $color_depth, $cookies, $lat, $lng]);
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM visitors ORDER BY visited_at DESC");
        return $stmt->fetchAll();
    }
}
