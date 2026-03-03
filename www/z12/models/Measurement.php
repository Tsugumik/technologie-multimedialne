<?php
class Measurement {
    private $pdo;

    public function __construct($pdoConnection) {
        $this->pdo = $pdoConnection;
    }

    public function save($x1, $x2, $x3, $x4, $x5, $pozar, $zalanie, $wentylacja) {
        $stmt = $this->pdo->prepare("INSERT INTO pomiary (x1, x2, x3, x4, x5, pozar, zalanie, wentylacja) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$x1, $x2, $x3, $x4, $x5, $pozar, $zalanie, $wentylacja]);
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM pomiary ORDER BY datetime DESC");
        return $stmt->fetchAll();
    }

    public function getLatest() {
        $stmt = $this->pdo->query("SELECT * FROM pomiary ORDER BY id DESC LIMIT 1");
        return $stmt->fetch();
    }
}
