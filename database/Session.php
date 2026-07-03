<?php

require_once "DatabaseHandler.php";

class Session extends DatabaseHandler {
    public function __construct()
    {
        $this->createTable();
        $this->migrateColumns();
    }

    public function create(string $ipAddress, string $affSub4, string $username, string $platform, int $followers): void
    {
        $statement = $this->connect()->prepare("INSERT INTO sessions (ip_address, aff_sub4, username, platform, followers) VALUES (?, ?, ?, ?, ?)");
        $statement->execute([
            $ipAddress,
            $affSub4,
            $username,
            $platform,
            $followers
        ]);
    }

    public function get(string $ipAddress, string $affSub4)
    {
        $q = $this->connect()->prepare("SELECT * FROM sessions WHERE ip_address=? AND aff_sub4=?");
        $q->execute([$ipAddress, $affSub4]);
        $session = $q->fetch(PDO::FETCH_ASSOC);
        if ($session) {
            return $session;
        }

        return $this->getLatestByAffSub4($affSub4);
    }

    public function getLatestByAffSub4(string $affSub4)
    {
        $q = $this->connect()->prepare("SELECT * FROM sessions WHERE aff_sub4=? ORDER BY id DESC LIMIT 1");
        $q->execute([$affSub4]);
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    public function getForUpdate(string $ipAddress, string $affSub4)
    {
        $q = $this->connect()->prepare("SELECT * FROM sessions WHERE ip_address=? AND aff_sub4=? FOR UPDATE");
        $q->execute([$ipAddress, $affSub4]);
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    public function getForUpdateByAffSub4(string $affSub4)
    {
        $q = $this->connect()->prepare("SELECT * FROM sessions WHERE aff_sub4=? ORDER BY id DESC LIMIT 1 FOR UPDATE");
        $q->execute([$affSub4]);
        return $q->fetch(PDO::FETCH_ASSOC);
    }

    public function syncIpAddress(int $sessionId, string $ipAddress): void
    {
        $q = $this->connect()->prepare("UPDATE sessions SET ip_address=? WHERE id=?");
        $q->execute([$ipAddress, $sessionId]);
    }

    public function isFulfilled(?array $session): bool
    {
        return is_array($session) && !empty($session["fulfilled_at"]);
    }

    public function markFulfilled(int $sessionId, string $smmOrderId): void
    {
        $q = $this->connect()->prepare("UPDATE sessions SET fulfilled_at=?, smm_order_id=? WHERE id=?");
        $q->execute([
            (new DateTime())->format("Y-m-d H:i:s"),
            $smmOrderId,
            $sessionId
        ]);
    }

    public function update(string $ipAddress, string $affSub4, string $username, string $platform, int $followers): void
    {
        $q = $this->connect()->prepare("UPDATE sessions SET username=?, platform=?, followers=? WHERE ip_address=? AND aff_sub4=?");
        $q->execute([
            $username,
            $platform,
            $followers,
            $ipAddress,
            $affSub4
        ]);
    }

    public function delete(string $ipAddress, string $affSub4): void
    {
        $q = $this->connect()->prepare("DELETE FROM sessions WHERE ip_address=? AND aff_sub4=?");
        $q->execute([$ipAddress, $affSub4]);
    }

    private function createTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `sessions` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `aff_sub4` VARCHAR(255),
    `username` VARCHAR(255),
    `platform` VARCHAR(255),
    `followers` INT,
    `ip_address` VARCHAR(255) NOT NULL,
    `fulfilled_at` DATETIME NULL,
    `smm_order_id` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY(id)
);";
        $conn = $this->connect();
        $conn->exec($sql);
    }

    private function migrateColumns(): void
    {
        $conn = $this->connect();
        $columns = $conn->query("SHOW COLUMNS FROM sessions")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array("fulfilled_at", $columns, true)) {
            $conn->exec("ALTER TABLE sessions ADD COLUMN fulfilled_at DATETIME NULL");
        }
        if (!in_array("smm_order_id", $columns, true)) {
            $conn->exec("ALTER TABLE sessions ADD COLUMN smm_order_id VARCHAR(255) NULL");
        }
    }
}
