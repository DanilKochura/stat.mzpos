<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div class="mb-5"style="margin-bottom: 20px">
                <p style="font-size: 30px;
    font-weight: 400;">МЦПО Аналитика</p>
            </div>
            <div class="flex justify-content-evenly">
                <img src="https://lk.mzpo-s.ru/build/images/logo/mirk-logo.png" class="mx-1" alt="" style="max-width: 80%; margin: 10px">
                <img src="https://lk.mzpo-s.ru/build/images/logo/mzpo-logo.png" class=" mx-2" alt=""  style="max-width: 80%; margin: 10px">
            </div>


            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
