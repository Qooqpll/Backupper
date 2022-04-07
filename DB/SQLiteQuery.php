<?php


class SQLiteQuery
{

    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function getTableList()
    {

        $stmt = $this->pdo->query("SELECT name
                               FROM sqlite_master
                               WHERE type = 'table'
                               ORDER BY name");
        $tables = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tables[] = $row['name'];
        }
        return $tables;
    }

    public function createTableCron()
    {
        $command = 'CREATE TABLE IF NOT EXISTS cron (
        id   INTEGER PRIMARY KEY,
        time_update TEXT,
        success INTEGER DEFAULT 0
      )';

        $this->pdo->exec($command);
    }

    public function getBackupSchedule()
    {
        $stmt = $this->pdo->query("SELECT * FROM cron");

        $tables = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tables[] = $row;
        }

        return $tables;
    }

    public function saveToDB($date)
    {
        $query = "INSERT INTO `cron` (`time_update`, `success`) VALUES ('$date', '0')";
        $stmt = $this->pdo->query($query);
        return $stmt;
    }

    public function updateToDB($success, $id)
    {
        $query = "UPDATE `cron` SET `success` = '$success' WHERE `cron`.`id` = '$id'";
        $stmt = $this->pdo->query($query);
        return $stmt;
    }

    public function deleteCompleteSchedule($id)
    {
        $query = "DELETE FROM `cron` WHERE `cron`.`id` = '$id'";
        $stmt = $this->pdo->query($query);
        return $stmt;
    }

    public function deleteSchedule()
    {
        foreach ($this->getBackupSchedule() as $item) {
            $id = 'id';
            $query = "DELETE FROM `cron` WHERE `cron`.`id` = '$item[$id]'";
            $stmt = $this->pdo->query($query);
        }
        return $stmt;
    }

    public function getConnection()
    {
        return new SQLiteQuery(new PDO("sqlite:" . SQLiteConnection::PATH_TO_SQLITE_FILE));
    }

}

