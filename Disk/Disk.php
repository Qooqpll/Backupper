<?php

class Disk
{
    /**
     * @var Token
     */
    private $token;

    CONST DIR_NAME = 'backup';

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function checkExistsDirectory($directory)
    {
        if(empty($directory['description'])) {
            return false;
        }

        foreach ($directory as $dir) {
            if($dir == self::DIR_NAME) {

                return true;
            }
        }

        return false;
    }

    public function deleteFile($fileName)
    {
        $token = $this->token->getToken();
        var_dump($token);
        // Файл или папка на Диске.
        $path = $fileName;
        var_dump($path);

        $ch = curl_init('https://cloud-api.yandex.net/v1/disk/resources?path=' . urlencode($path) . '&permanently=true');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . $token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (in_array($http_code, array(202, 204))) {
            echo 'Успешно удалено';
        }
        var_dump($res);
    }

    public function getInfoDirectory()
    {
        $token = $this->token->getToken();

        // Выведем список корневой папки.
        $path = '/' . self::DIR_NAME . '/';

        // Оставим только названия и тип.
        $fields = '_embedded.items.name,_embedded.items.type';

        $limit = 100;

        $ch = curl_init('https://cloud-api.yandex.net/v1/disk/resources?path=' .
            urlencode($path) . '&fields=' . $fields . '&limit=' . $limit);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . $token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($res, true);
        return $res;
    }

    public function createDirectory()
    {

        $token = $this->token->getToken();

        // Путь новой директории.
        $path = '/' . self::DIR_NAME;

        $ch = curl_init('https://cloud-api.yandex.net/v1/disk/resources/?path=' . urlencode($path));
        curl_setopt($ch, CURLOPT_PUT, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . $token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($res, true);
        print_r($res);
    }

    public function getInformationUser()
    {
        $url = 'https://cloud-api.yandex.net/v1/disk/';

        $token = $this->token->getToken();
        $authorization = 'Authorization: OAuth ' . $token;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [$authorization]);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $return = json_decode(curl_exec($curl), true);
        curl_close($curl);

    }

    public function uploadFile($backupName)
    {
        $info = $this->getInfoDirectory();

        if(!$this->checkExistsDirectory($info)) {
            $this->createDirectory();
        }

        //Получаем токен
        try {
            $token = $this->token->getToken();
            var_dump($token);
        }catch (Exception $e) {
            $token = $this->token->getRefreshToken();
        }

        $url = 'https://cloud-api.yandex.net/v1/disk/resources/upload?path=d/backup/1';

        // Путь и имя файла на нашем сервере.
        var_dump($backupName);
        $file = $backupName;

        // Папка на Яндекс Диске (уже должна быть создана).
        $path = 'disk:/' . self::DIR_NAME . '/';

        // Запрашиваем URL для загрузки.
        $ch = curl_init('https://cloud-api.yandex.net/v1/disk/resources/upload?path=' . urlencode($path . basename($file)));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: OAuth ' . $token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);

        $res = json_decode($res, true);
        if (empty($res['error'])) {
            // Если ошибки нет, то отправляем файл на полученный URL.
            $fp = fopen($file, 'r');

            $ch = curl_init($res['href']);
            curl_setopt($ch, CURLOPT_PUT, true);
            curl_setopt($ch, CURLOPT_UPLOAD, true);
            curl_setopt($ch, CURLOPT_INFILESIZE, filesize($file));
            curl_setopt($ch, CURLOPT_INFILE, $fp);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code == 201) {
                echo 'Файл успешно загружен.';
            }
        }
    }
}
