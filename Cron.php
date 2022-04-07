<?php

require_once 'Compress/ExtendedZip.php';
require_once 'BackupDB.php';
require_once 'Disk/Disk.php';
require_once 'Token.php';
require_once 'Config.php';
require_once 'DB/SQLiteQuery.php';
require_once 'CheckFileDiff/FileChecker.php';

class Cron
{
    const SEPARATOR = '-';

    const YER = 1;
    const MONTH = 2;
    const DAY = 3;
    const HOUR = 4;
    const MINUTE = 5;

    const DUMP_NAME = 0;
    const ZIP_NAME = 0;

    const ZIP_FILE_NAME = 'zipName.txt';
    const DUMP_FILE_NAME = 'dumpName.txt';

    //проверка нужно ли создать бэкапы
    public function checkBackups()
    {
        $db = $this->SQLiteConnect();

        $backupSchedule = $db->getBackupSchedule();
        $date = date('Y-m-d-H-i');

        $prepareBackupSchedule = [];

        foreach ($backupSchedule as $item) {
            if ($item['time_update'] <= $date && $item['success'] == 0) {
                $prepareBackupSchedule[]  = $item;
            }
        }

        $lastItemBackupSchedule = end($prepareBackupSchedule);

        if($lastItemBackupSchedule) {
           foreach($prepareBackupSchedule as $item) {
               if($item['id'] == $lastItemBackupSchedule['id']) {
                   $this->creatingBackups();
                   $db->updateTodb(1, $item['id']);
                   continue;
               }
               $db->updateTodb(1, $item['id']);
           }
       }
    }

    //создаём интервал для расписания
    public function createScheduleBackups($qty, $interval, $time)
    {
        $count = 0;

        if ($this->checkSchedule()) {
            for ($i = 0; $qty > $i; $i++) {

                $count += $interval;
                $this->calculateTimeBackups($count, $time);

            }
        }
    }

    //сохраняем в БД расписание
    private function calculateTimeBackups($interval, $time)
    {
        $db = $this->SQLiteConnect();
        $date = date('Y-m-d-H-i', strtotime('+' . $interval . ' ' . $time));
        $this->deleteOldSchedule();
        //$db->createTableCron();
        $db->saveToDB($date);
    }

    //удаляем старое расписание
    private function deleteOldSchedule()
    {
        $db = $this->SQLiteConnect();

        $backupSchedule = $db->getBackupSchedule();

        foreach ($backupSchedule as $item) {
            if ($item['success'] == 1) {
                $db->deleteCompleteSchedule($item['id']);
            }
        }
    }

    //проверяем последние записи в расписании
    private function checkSchedule()
    {
        $db = $this->SQLiteConnect();
        $backupSchedule = $db->getBackupSchedule();
        if (empty($backupSchedule)) {
            
            return true;
        }

        $lastBackups = array_slice($backupSchedule, -2, 2, true);
        foreach ($lastBackups as $item) {
            var_dump($item);
            if ($item['success'] == 1 || count($lastBackups) < 2) {
                $db->deleteSchedule();
                return true;
            }

            return false;
        }
    }

    //получаем дату последнего бэкапа
    public function getLastBackupTime()
    {
        $files = scandir('backup');
        $file = end($files);
        $split = explode('-', $file);
        $time = $split[self::YER] . self::SEPARATOR . $split[self::MONTH] . self::SEPARATOR .
            $split[self::DAY] . self::SEPARATOR . $split[self::HOUR] . self::SEPARATOR . $split[self::MINUTE];

        return $time;
    }

    //создаём бэкапы
        public function creatingBackups()
    {
        $disk = new Disk(new Token());
        $zip = new ExtendedZip();
        $backupDB = new BackupDB(LOCAL_PATH, DUMP_NAME);


        $backupDB->backupDB();


        if ($this->checkQtyBackups($backupDB->getDumpName(), self::DUMP_FILE_NAME)) {
            $dumpName = $this->getBackupsName(self::DUMP_FILE_NAME, self::DUMP_NAME);
            $disk->deleteFile($dumpName);
            $this->deleteOldNameBackups(self::DUMP_FILE_NAME, $backupDB->getDumpName());
        }
        $disk->uploadFile($backupDB->getDumpName());
        executeDiff();

        $diff = array_diff(file('CheckFileDiff/Diff/old.txt'), file('CheckFileDiff/Diff/curr.txt'));
        $zip->zipTree(DIR_NAME, ZIP_NAME, DIRECTORY_NAME,
                FLAGS, LOCAL_NAME, EXTENSION, DIRECTORY, $diff);


        if ($this->checkQtyBackups($zip->getBackupName(), self::ZIP_FILE_NAME)) {
            $zipName = $this->getBackupsName(self::ZIP_FILE_NAME, self::ZIP_NAME);
            $disk->deleteFile($zipName);
            $this->deleteOldNameBackups(self::ZIP_FILE_NAME, $zip->getBackupName());
        }

        $disk->uploadFile($zip->getBackupName());

    }

    public function getQtyBackups($fileName)
    {
        return count(file($fileName));
    }

    public function getBackupsName($fileName, $flags)
    {
        $str = file($fileName);
        return trim($str[$flags]);
    }

    public function deleteOldNameBackups($file, $name)
    {
        $line = 0;
        $files = file($file);
        unset($files[$line]);
        file_put_contents($file, $files);
        $fileName = explode('/', $name);
        file_put_contents($file, $fileName[1] . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public function checkQtyBackups($name, $file)
    {
        if ($this->getQtyBackups($file) < LIMIT_BACKUP) {
            $fp = fopen($file, 'a+');
            $fileName = explode('/', $name);
            fwrite($fp, $fileName[1] . PHP_EOL);
            fclose($fp);

        } else {

            return true;
        }
    }

    public function SQLiteConnect()
    {
        return new SQLiteQuery(new PDO("sqlite:phpsqlite.db"));
    }

}

$cron = new Cron();
$db = $cron->SQLiteConnect();
//$db->createTableCron();
$cron->createScheduleBackups(QTY, INTERVAL, TIME);
$cron->checkBackups();
var_dump(date('Y-m-d-H-i'));

//$db->deleteSchedule();

var_dump($db->getBackupSchedule());

