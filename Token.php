<?php

require_once 'Config.php';

class Token
{

    private $token;

    private $refreshToken;

    private $expiresIn;

    private function getTokenOAuth()
    {
        // Идентификатор приложения
        $client_id = CLIENT_ID;
        // Пароль приложения
        $client_secret = CLIENT_SECRET;

        // Если скрипт был вызван с указанием параметра "code" в URL,
        // то выполняется запрос на получение токена
        if(empty($this->token)) {
            if (isset($_GET['code'])) {
                // Формирование параметров (тела) POST-запроса с указанием кода подтверждения
                $query = array(
                    'grant_type' => 'authorization_code',
                    'code' => $_GET['code'],
                    'client_id' => $client_id,
                    'client_secret' => $client_secret
                );
                $query = http_build_query($query);

                // Формирование заголовков POST-запроса
                $header = "Content-type: application/x-www-form-urlencoded";

                // Выполнение POST-запроса и вывод результата
                $opts = array('http' =>
                    array(
                        'method' => 'POST',
                        'header' => $header,
                        'content' => $query
                    )
                );
                $context = stream_context_create($opts);
                $result = file_get_contents('https://oauth.yandex.ru/token', false, $context);
                $result = json_decode($result);
        }

            if(empty($this->expiresIn)) {
                $this->expiresIn = $result->expires_in;
            }

            if(empty($this->refreshToken)) {
                $this->refreshToken = $result->refresh_token;
            }

            if(empty($this->token)) {
                $this->token = $result->access_token;
            }
        }
    }

    public function getToken()
    {
        $file = file(__DIR__ . '\\token.txt');
        if(empty($file)) {
            $this->getTokenOAuth();
            file_put_contents(__DIR__ .'token.txt', $this->token);
        }

        if(!empty($file)) {
            return file_get_contents(__DIR__ . '/token.txt');
        }
        return $this->token;
    }

    public function getRefreshToken()
    {
        $query = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->getToken(),
            'client_id' => CLIENT_ID,
            'client_secret' => CLIENT_SECRET

        );
        $query = http_build_query($query);

        // Формирование заголовков POST-запроса
        $header = "Content-type: application/x-www-form-urlencoded";

        // Выполнение POST-запроса и вывод результата
        $opts = array('http' =>
            array(
                'method' => 'POST',
                'header' => $header,
                'content' => $query
            )
        );
        $context = stream_context_create($opts);
        $result = file_get_contents('https://oauth.yandex.ru/token', false, $context);
        $result = json_decode($result);

        if(!isset($this->expiresIn)) {
            $this->expiresIn = $result->expires_in;
        }

        if(!isset($this->refreshToken)) {
            $this->refreshToken = $result->refresh_token;
        }

        if(!isset($this->token)) {
            $this->token = $result->access_token;
        }
        return $this->token;
    }

}
