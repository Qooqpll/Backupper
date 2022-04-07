<?php

class ExtendedZip extends ZipArchive
{

    private $backupName;

    // Функция для добавления поддерева в архив
    private function addTree($dirname, $localname = '', $extension, $directory, $diff)
    {
        if ($localname)
            $this->addEmptyDir($localname);
        $this->_addTree($dirname, $localname, $extension, $directory, $diff);
    }

    //Создаёт имя для архива
    public function createNameArchive($name, $flags = 'zip')
    {
        //2001-03-10_17-16
        $today = date('Y-m-d-H-i');
        $split = explode('.', $name);
        $backupName = $split[0] . '-' . $today . '-.' . $split[1];

        return $this->backupName = $backupName;
    }

    // функция для рекурсии
    private function _addTree($dirname, $localname, $extension, $directory, $diff)
    {
        $files = array();
        $directories = array();
        $count = 0;
        foreach ($diff as $item) {
            $count++;
            $file = explode(' ', trim($item));
            unset($file[0]);
            $files[] = $file[1];
        }
        foreach ($files as $file) {
            $directoryPath = explode('/', $file);
            $directoryPath = array_slice($directoryPath, 1, -1);
            $directories[] = trim(implode('/', $directoryPath));
        }

        $dir = opendir($this->createDir() . '/' . $dirname);
        while ($filename = readdir($dir)) {

            // Удаляем . и .. из файлов
            if ($filename == '.' || $filename == '..')
                continue;

            // Проверка переменной $path
            $path = $dirname . '/' . $filename;
            $localpath = $localname ? ($localname . '/' . $filename) : $filename;
            if (is_dir($path)) {

                // Добавляем директорию в рекурсию
                if (!in_array($path, $directory)) {
                    if (!in_array($localpath, $directories) && !empty($diff)) {
                        continue;
                    }
                    $this->addEmptyDir($localpath);
                    $this->_addTree($path, $localpath, $extension, $directory, $diff);

                }
            } else if (is_file($path)) {
                $extFile = pathinfo($path, PATHINFO_EXTENSION);

                // Проверяем расширение файлов
                if (!in_array($extFile, $extension)) {
                    if (!in_array($path, $files) && !empty($diff)) {
                        continue;
                    }
                    //Добалвяем файл в архив
                    $this->addFile($path, $localpath);
                }
            }
        }
        closedir($dir);
    }

    //Метод который возвращает имя архива
    public function getBackupName()
    {
        return $this->backupName;
    }

    public function createDir()
    {
        $dir = explode('\\', __DIR__);
        $localDisk = $dir[0];
        $localDisk .= '\\';
        unset($dir[count($dir)-1]);
        unset($dir[0]);
        $path = implode('\\', $dir);
        return $localDisk . $path;
    }

    // функция помощник для создания архива
    public function zipTree($dirFilename = 'backup', $zipFilename = 'output.zip', $dirname = 'files_for_backup',
                            $flags = ZipArchive::CREATE, $localname = '', $extension = [], $directory = [], $diff = []
    )
    {
        $zip = new self();
        $dirFilename = $this->createDir() . '/' .$dirFilename . '/' . $zipFilename;
        $zip->open($this->createNameArchive($dirFilename), $flags);
        $zip->addTree($dirname, $localname, $extension, $directory, $diff);
        $zip->close();
    }
}