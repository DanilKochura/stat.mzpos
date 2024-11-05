<?php

use AmoCRM\Client\AmoCRMApiClient;
use App\Events\ProgressUpdated;
use App\UseCases\PusherProgress;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use League\OAuth2\Client\Token\AccessToken;


Route::middleware('auth')->group(function (){
    Route::get('/', function () {
        return redirect()->route('home');
        return view('welcome');
    });
    Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index']);
    Route::get('/home/{first?}/{second?}', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    Route::post('/home', [App\Http\Controllers\HomeController::class, 'load'])->name('load');

});


Route::get('/test', function (){
    abort(419);
});
Route::get('/start-loading/{period}', function ($period) {
    $first_period = $period;
    $first = explode('|', $first_period);
    $from_first = str_replace('/', '-', trim($first[0], ' +'));
    $to_first = str_replace('/', '-', trim($first[1], ' +'));
    \App\UseCases\ReportService::conversion($from_first, $to_first);

    return response()->json(['status' => 'completed']);
});
Route::get('/tesst', function (){
    return view('test');

});
Route::get('/postSales', [\App\UseCases\ReportService::class, 'postSales']);
Route::get('/post', function (){
   return view('_postsales');
});
Route::get('/year', [\App\UseCases\ReportService::class, 'YearReport']);
//
//Route::post('/main', function (\Illuminate\Support\Facades\Request $request){
//   \Illuminate\Support\Facades\Log::info(print_r($request, 1));
//});
//
//Route::get('/progress', function () {
//    return response()->json(['progress' => Session::get('loading_progress', 0)]);
//});


Route::middleware('guest')->group(function (){
    Route::get('/managers/{first?}/{second?}', [App\Http\Controllers\HomeController::class, 'managers'])->name('managers');
    Route::post('/managers', [App\Http\Controllers\HomeController::class, 'managers_load'])->name('managers.load');
    Route::get('/vidget', function (){
        return view('vidget');
    });
});


Route::get('/sign', function (){
    event(new ProgressUpdated(50));
    dd();
    function generatePusherAuthSignature($method, $path, $params, $app_secret) {
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
    "name": "progress-updated",
    "channels": ["progress-channel"],
    "data": "40"
}';

// Добавляем MD5-хэш тела запроса в параметры
    $params['body_md5'] = md5($body);

// Генерация подписи
    $params['auth_signature'] = generatePusherAuthSignature($method, $path, $params, $app_secret);

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

    if (curl_errno($ch)) {
        echo 'Error: ' . curl_error($ch);
    } else {
        echo "HTTP Code: $http_code\n";
        echo "Response: $response\n";
    }

    curl_close($ch);




});


require __DIR__.'/auth.php';
