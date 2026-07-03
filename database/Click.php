<?php

require_once "DatabaseHandler.php";

class Click extends DatabaseHandler {
    public function __construct()
    {
        $this->createTable();
    }

    public function create(int $offerId, int $sessionId): void
    {
        $statement = $this->connect()->prepare("INSERT INTO clicks (offer_id, session_id) VALUES (?, ?)");
        $statement->execute([
            $offerId,
            $sessionId
        ]);
    }

    public function getAll(int $sessionId): array
    {
        $q = $this->connect()->prepare("SELECT * FROM clicks WHERE session_id=? AND completed=1 ORDER BY completed_at DESC");
        $q->execute([$sessionId]);
       return $q->fetchAll();
    }

    public function countDistinctCompleted(int $sessionId): int
    {
        $q = $this->connect()->prepare("SELECT COUNT(DISTINCT offer_id) FROM clicks WHERE session_id=? AND completed=1");
        $q->execute([$sessionId]);
        return (int)$q->fetchColumn();
    }

    public function isOfferCompleted(int $offerId, int $sessionId): bool
    {
        $q = $this->connect()->prepare("SELECT 1 FROM clicks WHERE offer_id=? AND session_id=? AND completed=1 LIMIT 1");
        $q->execute([$offerId, $sessionId]);
        return (bool)$q->fetchColumn();
    }

    public function markCompleted(int $offerId, int $sessionId): void
    {
        if ($this->isOfferCompleted($offerId, $sessionId)) {
            return;
        }

        $dateTime = (new DateTime())->format("Y-m-d H:i:s");

        $q = $this->connect()->prepare("UPDATE clicks SET completed=1, completed_at=? WHERE offer_id=? AND session_id=?");
        $q->execute([$dateTime, $offerId, $sessionId]);
        if ($q->rowCount() > 0) {
            return;
        }

        $statement = $this->connect()->prepare(
            "INSERT INTO clicks (offer_id, session_id, completed, completed_at) VALUES (?, ?, 1, ?)"
        );
        $statement->execute([$offerId, $sessionId, $dateTime]);
    }

    public function update(int $completed, DateTime $completedAt, int $offerId, int $sessionId): void
    {
        $dateTime = $completedAt->format("Y-m-d H:i:s");
        $q = $this->connect()->prepare("UPDATE clicks SET completed=?, completed_at=? WHERE offer_id=? AND session_id=?");
        $q->execute([
            $completed,
            $dateTime,
            $offerId,
            $sessionId
        ]);
    }

    /*
     * deleteBySessionId will bulk delete all the clicks with the associated session ID
     * */
    public function deleteBySessionId(int $sessionId): void
    {
        $q = $this->connect()->prepare("DELETE FROM clicks WHERE session_id=?");
        $q->execute([
            $sessionId
        ]);
    }

    private function createTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS `clicks` (
    `id` INT AUTO_INCREMENT NOT NULL,
    `offer_id` INT,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `completed` BOOL DEFAULT false,
    `completed_at` DATETIME,
    `session_id` INT,
    PRIMARY KEY(id),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
);";
        $conn = $this->connect();
        $conn->exec($sql);
    }
}
