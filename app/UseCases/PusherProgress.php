<?php

namespace App\UseCases;

class PusherProgress
{
    public static function sendProgress($progress, $code)
    {

// Пример использования:

        $app_id = env('PUSHER_APP_ID');
        $app_key = env('PUSHER_APP_KEY');
        $app_secret = env('PUSHER_APP_SECRET');
        $cluster = 'eu'; // Замените на ваш кластер, если необходимо
        $method = 'POST';
        $path = "/apps/$app_id/events";



// Параметры, которые будут только в строке запроса (query string)
        $params = [
            'auth_key' => $app_key,
            'auth_timestamp' => time(),
            'auth_version' => '1.0',
        ];

// Тело запроса (body)
        $body = '{
    "name": "progress-updated-'.$code.'",
    "channels": ["progress-channel"],
    "data": "'.$progress.'"
}';

// Добавляем MD5-хэш тела запроса в параметры
        $params['body_md5'] = md5($body);

// Генерация подписи
        $params['auth_signature'] = self::generatePusherAuthSignature($method, $path, $params, $app_secret);

// Формируем URL для запроса (с параметрами аутентификации)
        $url = "https://api-$cluster.pusher.com$path?" . http_build_query($params);

// Отправка события через cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1); // Явно указываем использование HTTP/1.1

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

//        if (curl_errno($ch)) {
//            echo 'Error: ' . curl_error($ch);
//        } else {
//            echo "HTTP Code: $http_code\n";
//            echo "Response: $response\n";
//        }
//
        curl_close($ch);
    }
    public static function generatePusherAuthSignature($method, $path, $params, $app_secret) {
        // Сортируем параметры по ключу
        ksort($params);

        // Создаем строку из параметров запроса
        $queryString = http_build_query($params);

        // Формируем строку для подписи
        $stringToSign = "$method\n$path\n$queryString";

        // Генерируем HMAC-SHA256 подпись
        $auth_signature = hash_hmac('sha256', $stringToSign, $app_secret);

        return $auth_signature;
    }






}
