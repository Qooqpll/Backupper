<?php

require_once 'Compress/ExtendedZip.php';
require_once 'Config.php';

class BackupDB
{
    private $path;

    private $name;

    private $dumpName;

    public function __construct($path = 'backup_db/', $name = 'dump.sql')
    {
        $this->path = $path;
        $this->name = $name;
    }

    private function createNameDB($name)
    {
        //2001-03-10_17-16
        $today = date('Y-m-d-H-i');
        $split = explode('.', $name);
        $this->dumpName = $this->path . $split[0] . '-' . $today . '-.' . $split[1];

        return $this->dumpName;
    }

    // бэкап через mysqldump
    public function backupMysqlDump()
    {
        exec('mysqldump --user=' . USER_DB . ' --password=' . PASSWORD_DB . NAME_DB . '>'  . $this->path);
    }

    //бэкап через php
    public function backupDB()
    {

        //подключение в бд
        $connect = new PDO("mysql:host=" . HOST_DB . ";dbname=" . NAME_DB, USER_DB, PASSWORD_DB);
        $getAllTableQuery = "SHOW TABLES";
        $statement = $connect->prepare($getAllTableQuery);
        $statement->execute();
        $result = $statement->fetchAll();

        if(isset($result)) {

            $output = '';

            foreach($result as $table) {

                $showTableQuery = "SHOW CREATE TABLE " . $table[0];
                $statement = $connect->prepare($showTableQuery);
                $statement->execute();
                $showTableResult = $statement->fetchAll();

                foreach($showTableResult as $showTableRow) {

                    $output .= "\n\n" . $showTableRow["Create Table"] . ";\n\n";

                }

                $selectQuery = "SELECT * FROM " . $table[0];
                $statement = $connect->prepare($selectQuery);
                $statement->execute();
                $totalRow = $statement->rowCount();

                for($count=0; $count < $totalRow; $count++)
                {

                    $singleResult = $statement->fetch(PDO::FETCH_ASSOC);
                    $tableColumnArray = array_keys($singleResult);
                    $tableValueArray = array_values($singleResult);
                    $output .= "\nINSERT INTO $table[0] (";
                    $output .= "" . implode(", ", $tableColumnArray) . ") VALUES (";
                    $output .= "'" . implode("','", $tableValueArray) . "');\n";

                }
            }

            $fileName = $this->createNameDB($this->name);
            $fileHandle = fopen(__DIR__ . '/' .$fileName   , 'w+');
            fwrite($fileHandle, $output);
            fclose($fileHandle);
            /*header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename=' . basename($fileName));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($fileName));*/
            //ob_clean();
            flush();
        }
    }

    public function getDumpName()
    {
        return $this->dumpName;
    }
}

//$db = new BackupDB();
//$db->backupDB();